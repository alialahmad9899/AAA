// ============================================================================
// اسم الملف: lib/main.dart
// مشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: نقطة الانطلاق الرئيسية مع إضافة المعالجة التلقائية للروابط العميقة (App Links)
// ============================================================================

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:app_links/app_links.dart';
import 'theme/app_theme.dart';

// استيراد الشاشات والخدمات الرئيسية للتطبيق
import 'screens/brand_intro_screen.dart';
import 'screens/main_home_screen.dart';
import 'screens/my_interests_screen.dart';
import 'utils/member_profile_link.dart';
import 'services/pending_profile_link_service.dart';

// 🟢 خدمة الإشعارات المركزية مع مفتاح الملاحة العام
import 'services/notification_service.dart';
import 'services/api_service.dart';
import 'services/fcm_service.dart';

void _mainDebugLog(String message) {
  if (kDebugMode) debugPrint(message);
}

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  // Paint the branded startup immediately; the intro resolves the saved
  // session while its logo animation is running.
  runApp(const MyApp());

  // Start optional services after the app can paint.
  _setupDeepLinks();
  WidgetsBinding.instance.addPostFrameCallback((_) {
    unawaited(_initializeNotificationServices());
  });
}

Future<void> _initializeNotificationServices() async {
  try {
    await ApiService.syncPendingLogout();
    await NotificationService.initialize();
    // A cold-started, already-authenticated member must register the current
    // FCM token even though no login screen is shown this time.
    await NotificationService.syncCurrentToken();
  } catch (e) {
    _mainDebugLog('⚠️ خطأ أثناء تهيئة Firebase والإشعارات: $e');
  }
}

/// دالة مخصصة للالتقاط والاستجابة للروابط العميقة المفتوحة
void _setupDeepLinks() async {
  final appLinks = AppLinks();

  // أ) معالجة الرابط إذا فتح التطبيق من حالة الإغلاق الكامل (Cold Start)
  try {
    final Uri? initialUri = await appLinks.getInitialLink();
    if (initialUri != null) {
      _handleDeepLinkUri(initialUri);
    }
  } catch (e) {
    _mainDebugLog('⚠️ خطأ أثناء قراءة الرابط الابتدائي: $e');
  }

  // ب) الاستماع للروابط المنقورة أثناء تشغيل التطبيق بالخلفية
  appLinks.uriLinkStream.listen((Uri uri) {
    _handleDeepLinkUri(uri);
  }, onError: (err) {
    _mainDebugLog('⚠️ خطأ في استقبال تدفق الروابط العميقة: $err');
  });
}

/// تحليل الرابط واستخراج المعاملات للتوجه لشاشة بروفايل العضو
void _handleDeepLinkUri(Uri uri) {
  final int? memberReference = MemberProfileLink.parseProfileUri(uri);
  if (memberReference == null) return;

  unawaited(() async {
    await PendingProfileLinkService.save(memberReference);
    if (!PendingProfileLinkService.isAuthenticatedHomeReady) return;

    final context = FCMService.navigatorKey.currentContext;
    if (context != null && context.mounted) {
      await PendingProfileLinkService.openPending(context);
    }
  }());
}

/// الكلاس الرئيسي للتطبيق المسؤول عن ضبط الثيم العام والمسار الابتدائي
class MyApp extends StatelessWidget {
  final bool? isLoggedIn;

  const MyApp({super.key, this.isLoggedIn});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'وصال',
      debugShowCheckedModeBanner: false,

      // 🟢 مفتاح الملاحة العام الخاص بـ FCMService للتوجيه المباشر للشاشات عند النقر على الإشعارات والروابط
      navigatorKey: FCMService.navigatorKey,

      theme: AppTheme.light,
      // 🟢 جدول المسارات المباشرة داخل التطبيق
      routes: {
        '/my-interests': (context) => const MyInterestsScreen(),
      },
      // المستخدم المسجّل يتجاوز شاشة الترحيب؛ غير المسجّل يرى افتتاحية وصال.
      home: isLoggedIn == true
          ? const MainHomeScreen()
          : BrandIntroScreen(checkSession: isLoggedIn == null),
    );
  }
}

// ============================================================================
// اسم الملف: lib/services/startup_manager.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: مدير جدولة تحميل بيانات الشاشة الرئيسية بشكل متتابع لتجنب الجهد
//        الزائد على المعالج (CPU) وضمان سلاسة رسم الواجهات (UI Rendering).
// ============================================================================

import 'package:flutter/foundation.dart';
import '../models/member_profile_model.dart';
import '../models/chat_model.dart';
import '../models/member_model.dart';
import '../models/notification_model.dart';
import 'api_service.dart';

class StartupManager {
  /// جلب بيانات الواجهة الرئيسية بجدولة زمنية سلسة ومتتابعة.
  ///
  /// تضمن هذه الدالة تحميل البيانات خطوة بخطوة مع إعطاء محرك Flutter مهلة زمنية
  /// قصيرة بين كل طلب وآخر لرسم الشاشة وتفادي تقطيع الواجهة (UI Jank / Lag).
  ///
  /// المعلمات:
  /// - [onProfileLoaded]: تُستدعى عند اكتمال جلب الملف الشخصي للمستخدم.
  /// - [onChatsLoaded]: تُستدعى عند اكتمال جلب قائمة المحادثات.
  /// - [onMatchesLoaded]: تُستدعى عند اكتمال جلب التوافقات والبروفايلات الموصى بها.
  /// - [onNotificationsLoaded]: تُستدعى عند اكتمال جلب قائمة الإشعارات.
  /// - [isMounted]: فحص اختياري للتحقق مما إذا كانت الشاشة لا تزال نشطة قبل تنفيذ التوابع.
  static Future<void> loadHomeScreenDataDataSequentially({
    required Function(MemberProfileModel? profile) onProfileLoaded,
    required Function(List<ChatThreadModel> chats) onChatsLoaded,
    required Function(List<MemberModel> matches) onMatchesLoaded,
    required Function(List<NotificationModel> notifications)
        onNotificationsLoaded,
    bool Function()? isMounted,
  }) async {
    // 1. جلب الملف الشخصي للمستخدم الحالي أولاً
    try {
      final profile = await ApiService.getCurrentUserProfile();
      if (isMounted == null || isMounted()) {
        onProfileLoaded(profile);
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint("⚠️ [StartupManager Error]: فشل جلب الملف الشخصي: $e");
      }
    }

    // انتظر 150 ملي ثانية لإعطاء مجال للمحرك لرسم الشاشة بسلاسة
    await Future.delayed(const Duration(milliseconds: 150));
    if (isMounted != null && !isMounted()) return;

    // 2. جلب قائمة المحادثات
    try {
      final chats = await ApiService.getChatThreads();
      if (isMounted == null || isMounted()) {
        onChatsLoaded(chats);
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint("⚠️ [StartupManager Error]: فشل جلب قائمة المحادثات: $e");
      }
    }

    await Future.delayed(const Duration(milliseconds: 150));
    if (isMounted != null && !isMounted()) return;

    // 3. جلب البروفايلات المطابقة
    try {
      final matches = await ApiService.getMatchedProfiles();
      if (isMounted == null || isMounted()) {
        onMatchesLoaded(matches);
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint("⚠️ [StartupManager Error]: فشل جلب قائمة التوافقات: $e");
      }
    }

    await Future.delayed(const Duration(milliseconds: 150));
    if (isMounted != null && !isMounted()) return;

    // 4. جلب الإشعارات في النهاية
    try {
      final notifications = await ApiService.getNotifications();
      if (isMounted == null || isMounted()) {
        onNotificationsLoaded(notifications);
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint("⚠️ [StartupManager Error]: فشل جلب قائمة الإشعارات: $e");
      }
    }
  }

  /// دالة باسم مصحح إملائياً تعمل كاختصار متوافق تماماً مع الاسم الأساسي.
  static Future<void> loadHomeScreenDataSequentially({
    required Function(MemberProfileModel? profile) onProfileLoaded,
    required Function(List<ChatThreadModel> chats) onChatsLoaded,
    required Function(List<MemberModel> matches) onMatchesLoaded,
    required Function(List<NotificationModel> notifications)
        onNotificationsLoaded,
    bool Function()? isMounted,
  }) async {
    return loadHomeScreenDataDataSequentially(
      onProfileLoaded: onProfileLoaded,
      onChatsLoaded: onChatsLoaded,
      onMatchesLoaded: onMatchesLoaded,
      onNotificationsLoaded: onNotificationsLoaded,
      isMounted: isMounted,
    );
  }
}

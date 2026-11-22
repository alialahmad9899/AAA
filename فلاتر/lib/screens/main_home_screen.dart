// ============================================================================
// اسم الملف: lib/screens/main_home_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: الشاشة الرئيسية - إضافة أيقونة زوار الملف الشخصي وتدويل النصوص بالكامل
//        مع دعم الترجمة الديناميكية المزدوجة والتحديث اللحظي للغة.
// ============================================================================

import 'dart:async';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../constants/colors.dart';

import '../services/api_service.dart';
import '../services/pending_profile_link_service.dart';
import '../models/app_announcement.dart';
import '../services/notification_service.dart';
import 'notifications_screen.dart';
import 'my_profile_viewers_screen.dart';

import 'home/tabs/auto_matching_tab.dart';
import 'home/chats_tab.dart';
import 'home/tabs/search_tab.dart';
import 'home/tabs/account_tab.dart';
import 'support_tickets_screen.dart';

class MainHomeScreen extends StatefulWidget {
  final String currentLanguage;

  const MainHomeScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<MainHomeScreen> createState() => _MainHomeScreenState();
}

class _MainHomeScreenState extends State<MainHomeScreen>
    with WidgetsBindingObserver {
  int _currentIndex = 0;
  late String _currentLanguage;
  int _unreadNotificationCount = 0;

  bool _isModalActive = false;
  bool _isBlocked = false;
  bool _isResolvingBlockedStatus = true;
  bool _isCheckingBlockedStatus = false;
  String _blockingReason = '';
  bool _hasCheckedAppAnnouncement = false;
  Timer? _blockedStatusTimer;
  final Set<int> _loadedTabs = {0};

  final Map<String, Map<String, String>> _localizedStrings = {
    'ar': {
      'title': 'التطابق الذكي',
      'chats': 'المحادثات',
      'search': 'البحث والفلترة',
      'account': 'الحساب الشخصي',
      'home': 'الرئيسية',
      'no_notif': 'لا توجد إشعارات جديدة حالياً',
      'notifications_title': 'مركز التنبيهات',
      'profile_viewers_title': 'من زار ملفي الشخصي',
      'mark_all_read': 'تحديد الكل كمقروء',
      'delete_all_chats': 'مسح جميع المحادثات',
      'registered_user': 'المستخدم المسجل',
      'edit_profile': 'تعديل الملف الشخصي',
      'subscriptions': 'الاشتراكات الشهرية',
      'expectations': 'تعديل توقعات الشريك',
      'blocked': 'الحسابات المحظورة',
      'language': 'اللغة',
      'delete_account': 'حذف الحساب',
      'about': 'عن التطبيق',
      'faq': 'دليل الاستخدام (الأسئلة الشائعة)',
      'support': 'التواصل مع الدعم',
      'privacy': 'سياسة الخصوصية والشروط',
      'logout': 'تسجيل الخروج',
      'select_lang': 'اختر اللغة',
      'no_chats': 'لا توجد محادثات نشطة حالياً',
      'search_hint': 'ابحث بالإسم، المدينة، أو المهنة...',
      'locked_chat_title': 'المحادثات مقفولة',
      'locked_chat_desc': 'ميزة المحادثات المباشرة خاصة بالباقات المدفوعة.',
      'upgrade': 'ترقية الحساب',
      'cancel': 'إلغاء',
      'lang_name': 'العربية',
      'no_data': 'لا توجد اقتراحات تطابق حالياً من السيرفر',
      'no_smart_matches': 'لا توجد نتائج مطابقة حالياً. حدّث توقعات الشريك لزيادة فرص العثور على توافق.',
      'delete_chat': 'حذف المحادثة',
      'delete_confirm': 'هل أنت متأكد من رغبتك في حذف هذه المحادثة بالكامل؟',
      'delete_all_confirm':
          'هل أنت متأكد من مسح جميع المحادثات والسجلات نهائياً من السيرفر؟',
      'delete_action': 'حذف',
      'express_interest': 'إبداء اهتمام',
      'match_rate': 'تطابق ممتاز',
      'next': 'التالي',
      'like_btn': 'إعجاب',
      'message_btn': 'مراسلة',
      'apply_search': 'تطبيق نتائج البحث',
      'applying_filters': 'جاري تطبيق الفلاتر...',
      'retry': 'إعادة المحاولة',
      'like_failed': 'تعذر إرسال الإعجاب، يرجى المحاولة لاحقاً',
      'like_success': 'تم إرسال الإعجاب بنجاح',
      'liked_already': 'تم الإعجاب',
      'deleting_chats': 'جاري حذف المحادثات من السيرفر...',
      'delete_all_success': 'تم حذف جميع المحادثات بنجاح',
      'delete_all_error': 'حدث خطأ أثناء حذف بعض المحادثات',
      'matches_fetch_failed': 'تعذر جلب المطابقات، تأكد من الاتصال بالإنترنت',
      'advanced_search': 'تحديد خيارات البحث المتقدم',
      'age_filter': 'العمر',
      'residence_filter': 'الإقامة',
      'status_filter': 'الحالة',
      'edu_filter': 'التعليم',
      'coming_soon': 'سيتم توفير هذه الميزة لاحقاً',
      'exit_title': 'إغلاق التطبيق',
      'exit_desc': 'هل أنت متأكد من رغبتك في إغلاق تطبيق وصال؟',
      'exit_confirm': 'خروج',
      'single': 'أعزب',
      'divorced': 'مطلق',
      'widowed': 'أرمل',
      'married': 'متزوج',
      'muslim': 'مسلم',
      'christian': 'مسيحي',
      'unspecified': 'غير محدد',
    },
    'en': {
      'title': 'Smart Match',
      'chats': 'Chats',
      'search': 'Search & Filter',
      'account': 'Profile',
      'home': 'Home',
      'no_notif': 'No new notifications',
      'notifications_title': 'Notifications Center',
      'profile_viewers_title': 'Profile Visitors',
      'mark_all_read': 'Mark All as Read',
      'delete_all_chats': 'Clear All Chats',
      'registered_user': 'Registered User',
      'edit_profile': 'Edit Profile',
      'subscriptions': 'Monthly Subscriptions',
      'expectations': 'Partner Expectations',
      'blocked': 'Blocked Accounts',
      'language': 'Language',
      'delete_account': 'Delete Account',
      'about': 'About App',
      'faq': 'User Guide (FAQ)',
      'support': 'Contact Support',
      'privacy': 'Privacy Policy & Terms',
      'logout': 'Logout',
      'select_lang': 'Select Language',
      'no_chats': 'No active chats currently',
      'search_hint': 'Search by name, city, or job...',
      'locked_chat_title': 'Chats Locked',
      'locked_chat_desc': 'Direct chat feature is for premium packages.',
      'upgrade': 'Upgrade Account',
      'cancel': 'Cancel',
      'lang_name': 'English',
      'no_data': 'No match suggestions available from server',
      'no_smart_matches': 'No matching profiles yet. Update your partner expectations to improve your results.',
      'delete_chat': 'Delete Chat',
      'delete_confirm': 'Are you sure you want to completely delete this chat?',
      'delete_all_confirm':
          'Are you sure you want to permanently delete ALL chats from server?',
      'delete_action': 'Delete',
      'express_interest': 'Like Profile',
      'match_rate': 'High Match',
      'next': 'Next',
      'like_btn': 'Like',
      'message_btn': 'Message',
      'apply_search': 'Apply Search',
      'applying_filters': 'Applying filters...',
      'retry': 'Retry',
      'like_failed': 'Failed to send like, please try again',
      'like_success': 'Interest expressed successfully',
      'liked_already': 'Liked',
      'deleting_chats': 'Deleting chats from server...',
      'delete_all_success': 'All chats deleted successfully',
      'delete_all_error': 'An error occurred while deleting some chats',
      'matches_fetch_failed': 'Failed to fetch matches, check your connection',
      'advanced_search': 'Advanced Search Options',
      'age_filter': 'Age',
      'residence_filter': 'Residence',
      'status_filter': 'Status',
      'edu_filter': 'Education',
      'coming_soon': 'This feature will be available soon',
      'exit_title': 'Exit Application',
      'exit_desc': 'Are you sure you want to exit Wesal?',
      'exit_confirm': 'Exit',
      'single': 'Single',
      'divorced': 'Divorced',
      'widowed': 'Widowed',
      'married': 'Married',
      'muslim': 'Muslim',
      'christian': 'Christian',
      'unspecified': 'Unspecified',
    }
  };

  String _txt(String key) => _localizedStrings[_currentLanguage]?[key] ?? key;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        PendingProfileLinkService.openPending(context);
      }
    });
    _currentLanguage = widget.currentLanguage;
    WidgetsBinding.instance.addObserver(this);
    NotificationService.foregroundMessageVersion
        .addListener(_onForegroundNotificationReceived);
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await _loadSavedLanguage();
      if (!mounted) return;
      await _loadCachedBlockedStatus();
      await _refreshBlockedStatus();
      if (!mounted) return;
      _blockedStatusTimer?.cancel();
      _blockedStatusTimer = Timer.periodic(
        const Duration(seconds: 20),
        (_) => unawaited(_refreshBlockedStatus()),
      );
      if (!_isBlocked) {
        _fetchNotificationsCount();
        unawaited(_loadAndShowAppAnnouncement());
        unawaited(_showNotificationPermissionGuidance());
      }
    });
  }

  Future<void> _showNotificationPermissionGuidance() async {
    if (!mounted) return;
    final prefs = await SharedPreferences.getInstance();
    final status = await NotificationService.getPermissionStatus();
    if (!mounted || status == 'authorized' || status == 'provisional') return;

    final bool hasShownGuidance =
        prefs.getBool('notifications_permission_guidance_shown') ?? false;
    if (status == 'notDetermined' && hasShownGuidance) return;
    if (status == 'denied' && hasShownGuidance) return;

    final bool isArabic = _currentLanguage.toLowerCase().startsWith('ar');
    final bool? shouldRequest = await showDialog<bool>(
      context: context,
      barrierDismissible: true,
      builder: (dialogContext) => Directionality(
        textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: AlertDialog(
          backgroundColor: AppColors.cardColor,
          title: Text(
            isArabic ? 'فعّل تنبيهات وصال' : 'Enable Wesal notifications',
            style: const TextStyle(
              color: AppColors.textDark,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
          content: Text(
            isArabic
                ? 'حتى تصلك رسائل المحادثات والتنبيهات المهمة في وقتها، يمكنك السماح للتطبيق بإرسال الإشعارات.'
                : 'Allow notifications so chat messages and important account updates can reach you on time.',
            style: const TextStyle(color: AppColors.textMuted, fontFamily: 'Cairo'),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: Text(isArabic ? 'ليس الآن' : 'Not now'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(dialogContext, true),
              child: Text(isArabic ? 'متابعة' : 'Continue'),
            ),
          ],
        ),
      ),
    );

    await prefs.setBool('notifications_permission_guidance_shown', true);
    if (!mounted || shouldRequest != true) return;

    final newStatus = await NotificationService.requestUserPermission();
    if (!mounted ||
        newStatus == 'authorized' ||
        newStatus == 'provisional' ||
        newStatus == 'notDetermined') {
      return;
    }

    final bool? openSettings = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => Directionality(
        textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: AlertDialog(
          backgroundColor: AppColors.cardColor,
          title: Text(
            isArabic ? 'الإشعارات متوقفة' : 'Notifications are disabled',
            style: const TextStyle(color: AppColors.textDark, fontFamily: 'Cairo'),
          ),
          content: Text(
            isArabic
                ? 'رفضت صلاحية الإشعارات. يمكنك تفعيلها يدوياً من إعدادات التطبيق في جهازك.'
                : 'Notification permission was denied. You can enable it manually in your device settings.',
            style: const TextStyle(color: AppColors.textMuted, fontFamily: 'Cairo'),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: Text(isArabic ? 'إغلاق' : 'Close'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(dialogContext, true),
              child: Text(isArabic ? 'فتح الإعدادات' : 'Open settings'),
            ),
          ],
        ),
      ),
    );
    if (openSettings == true) {
      await NotificationService.openSystemNotificationSettings();
    }
  }

  Future<void> _loadAndShowAppAnnouncement() async {
    if (_hasCheckedAppAnnouncement || !mounted || _isBlocked) return;
    _hasCheckedAppAnnouncement = true;

    try {
      final payload = await ApiService.getMemberAppAnnouncement(
        lang: _currentLanguage,
      );
      if (!mounted || _isBlocked || payload == null) return;

      final announcement = AppAnnouncement.fromJson(payload);
      if (!announcement.hasContent || _isModalActive) return;

      final bool isArabic = _currentLanguage.toLowerCase().startsWith('ar');
      _isModalActive = true;
      try {
        await showDialog<void>(
          context: context,
          barrierDismissible: true,
          builder: (dialogContext) {
            return Directionality(
              textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
              child: Dialog(
                backgroundColor: Colors.transparent,
                elevation: 0,
                insetPadding: const EdgeInsets.symmetric(horizontal: 22, vertical: 24),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    color: AppColors.cardColor,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(
                      color: AppColors.primaryTeal.withValues(alpha: 0.18),
                      width: 1.2,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.textDark.withValues(alpha: 0.12),
                        blurRadius: 28,
                        offset: const Offset(0, 12),
                      ),
                    ],
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 48,
                              height: 48,
                              decoration: BoxDecoration(
                                color: AppColors.primaryTeal.withValues(alpha: 0.10),
                                borderRadius: BorderRadius.circular(15),
                              ),
                              child: Icon(
                                announcement.type == 'app_update'
                                    ? Icons.system_update_alt_rounded
                                    : Icons.campaign_rounded,
                                color: AppColors.primaryTeal,
                                size: 27,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                announcement.type == 'app_update'
                                    ? (isArabic ? 'تحديث التطبيق' : 'App update')
                                    : (isArabic ? 'ملاحظة من وصال' : 'A note from Wesal'),
                                style: const TextStyle(
                                  color: AppColors.primaryTeal,
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ),
                            IconButton(
                              tooltip: isArabic ? 'إغلاق' : 'Close',
                              visualDensity: VisualDensity.compact,
                              onPressed: () => Navigator.of(dialogContext).pop(),
                              icon: const Icon(Icons.close_rounded, color: AppColors.textMuted),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        Text(
                          announcement.title,
                          textAlign: TextAlign.start,
                          style: const TextStyle(
                            color: AppColors.textDark,
                            fontSize: 19,
                            height: 1.4,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          announcement.message,
                          textAlign: TextAlign.start,
                          style: const TextStyle(
                            color: AppColors.textDark,
                            fontSize: 14,
                            height: 1.8,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        const SizedBox(height: 22),
                        if (announcement.hasButton)
                          SizedBox(
                            height: 50,
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: () async {
                                Navigator.of(dialogContext).pop();
                                final uri = Uri.parse(announcement.buttonUrl);
                                try {
                                  final launched = await launchUrl(
                                    uri,
                                    mode: LaunchMode.externalApplication,
                                  );
                                  if (!launched) {
                                    debugPrint('[App Announcement] Unable to open CTA URL.');
                                  }
                                } catch (error) {
                                  debugPrint('[App Announcement] CTA launch failed: $error');
                                }
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primaryTeal,
                                foregroundColor: Colors.white,
                                elevation: 0,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                              ),
                              icon: const Icon(Icons.open_in_new_rounded, size: 18),
                              label: Text(
                                announcement.buttonText,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 14,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ),
                          ),
                        TextButton(
                          onPressed: () => Navigator.of(dialogContext).pop(),
                          child: Text(
                            isArabic ? 'إغلاق' : 'Close',
                            style: const TextStyle(
                              color: AppColors.textMuted,
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              fontFamily: 'Cairo',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      } finally {
        _isModalActive = false;
      }
    } catch (error) {
      debugPrint('[App Announcement] Failed to load or show popup: $error');
    }
  }

  void _onForegroundNotificationReceived() {
    if (!mounted) return;
    unawaited(_refreshBlockedStatus());
    if (!_isBlocked) {
      _fetchNotificationsCount();
    }
  }

  Future<void> _loadCachedBlockedStatus() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getInt('user_id') ?? 0;
      if (userId <= 0 ||
          prefs.getInt('wesal_blocked_user_id') != userId ||
          prefs.getBool('wesal_account_blocked') != true) {
        return;
      }
      if (!mounted) return;
      setState(() {
        _isBlocked = true;
        _blockingReason = prefs.getString('wesal_blocking_reason') ?? '';
      });
    } catch (error) {
      debugPrint('[Blocked Account Cache] $error');
    }
  }

  Future<void> _refreshBlockedStatus() async {
    if (!mounted || _isCheckingBlockedStatus) return;
    _isCheckingBlockedStatus = true;
    try {
      final status = await ApiService.getBlockedAccountStatus(lang: _currentLanguage);
      if (!mounted) return;
      if (status != null) {
        setState(() {
          _isBlocked = status.isBlocked;
          _blockingReason = status.reason;
          _isResolvingBlockedStatus = false;
        });
        try {
          final prefs = await SharedPreferences.getInstance();
          final userId = prefs.getInt('user_id') ?? 0;
          if (userId > 0) {
            await prefs.setInt('wesal_blocked_user_id', userId);
            await prefs.setBool('wesal_account_blocked', status.isBlocked);
            if (status.isBlocked && status.reason.isNotEmpty) {
              await prefs.setString('wesal_blocking_reason', status.reason);
            } else {
              await prefs.remove('wesal_blocking_reason');
            }
          }
        } catch (cacheError) {
          debugPrint('[Blocked Account Cache Save] $cacheError');
        }
      } else {
        setState(() => _isResolvingBlockedStatus = false);
      }
    } catch (error) {
      if (mounted) setState(() => _isResolvingBlockedStatus = false);
      debugPrint('[Blocked Account Status] $error');
    } finally {
      _isCheckingBlockedStatus = false;
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      unawaited(_refreshBlockedStatus().then((_) {
        if (!mounted || _isBlocked) return;
        _fetchNotificationsCount();
        unawaited(NotificationService.syncCurrentToken());
      }));
    }
  }

  @override
  void dispose() {
    _blockedStatusTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    NotificationService.foregroundMessageVersion
        .removeListener(_onForegroundNotificationReceived);
    super.dispose();
  }

  @override
  void didUpdateWidget(MainHomeScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.currentLanguage != widget.currentLanguage) {
      setState(() {
        _currentLanguage = widget.currentLanguage;
      });
    }
  }

  Future<void> _loadSavedLanguage() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final savedLang =
          prefs.getString('app_lang') ?? prefs.getString('app_language');
      if (savedLang != null && mounted) {
        setState(() {
          _currentLanguage = savedLang;
        });
      }
    } catch (e) {
      debugPrint('خطأ في تحميل اللغة المحفوظة: $e');
    }
  }

  Future<void> _fetchNotificationsCount() async {
    if (_isBlocked) return;
    try {
      final dynamic rawList = await ApiService.getNotifications();
      if (rawList is! List) return;

      int unread = 0;
      for (var n in rawList) {
        if (n == null) continue;
        if (n is Map) {
          final isNew = n['is_new'] ?? n['isNew'] ?? n['unread'];
          if (isNew == true || isNew == 1 || isNew == '1') {
            unread++;
          }
        } else {
          try {
            if ((n as dynamic).isNew == true) {
              unread++;
            }
          } catch (_) {}
        }
      }

      if (mounted) {
        setState(() {
          _unreadNotificationCount = unread;
        });
      }
    } catch (e) {
      debugPrint('خطأ في جلب عداد الإشعارات: $e');
    }
  }

  void _handleNotificationPressed() async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => NotificationsScreen(
          currentLanguage: _currentLanguage,
        ),
      ),
    );
    if (mounted) {
      _fetchNotificationsCount();
    }
  }

  void _handleProfileViewersPressed() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => MyProfileViewersScreen(
          currentLanguage: _currentLanguage,
        ),
      ),
    );
  }

  Future<void> _showSubscriptionModalDirectly() async {
    if (_isModalActive || !mounted) return;
    _isModalActive = true;

    try {
      await AccountTab.showSubscriptionsModalDirectly(
        context,
        isArabic: _currentLanguage == 'ar',
      );
    } finally {
      _isModalActive = false;
    }
  }

  Future<bool> _showExitConfirmationDialog() async {
    if (!mounted || _isModalActive || _isBlocked) return false;
    _isModalActive = true;

    final result = await showDialog<bool>(
      context: context,
      builder: (context) => Directionality(
        textDirection:
            _currentLanguage == 'ar' ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          insetPadding: const EdgeInsets.symmetric(horizontal: 40),
          child: SingleChildScrollView(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(28),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
                child: Container(
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    color: AppColors.cardColor,
                    borderRadius: BorderRadius.circular(28),
                    border: Border.all(
                      color: AppColors.lineColor,
                      width: 1.5,
                    ),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: AppColors.sectionBackground,
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: Colors.white.withValues(alpha: 0.3),
                          ),
                        ),
                        child: const Icon(
                          Icons.exit_to_app_rounded,
                          color: AppColors.primaryTeal,
                          size: 32,
                        ),
                      ),
                      const SizedBox(height: 14),
                      Text(
                        _txt('exit_title'),
                        style: const TextStyle(
                          color: AppColors.textDark,
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _txt('exit_desc'),
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 14,
                          height: 1.4,
                          fontFamily: 'Cairo',
                        ),
                      ),
                      const SizedBox(height: 20),
                      Row(
                        children: [
                          Expanded(
                            child: TextButton(
                              onPressed: () => Navigator.pop(context, false),
                              child: Text(
                                _txt('cancel'),
                                style: const TextStyle(
                                    color: AppColors.textMuted, fontFamily: 'Cairo'),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primaryTeal,
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                padding:
                                    const EdgeInsets.symmetric(vertical: 12),
                              ),
                              onPressed: () => Navigator.pop(context, true),
                              child: Text(
                                _txt('exit_confirm'),
                                style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo'),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );

    _isModalActive = false;
    return result ?? false;
  }

  Future<void> _openBlockedSupportMessage() async {
    await Navigator.push(context, MaterialPageRoute(builder: (_) => BlockedSupportMessageScreen(currentLanguage: _currentLanguage)));
    if (mounted) unawaited(_refreshBlockedStatus());
  }

  Widget _buildBlockedAccessScreen() {
    final bool isArabic = _currentLanguage.toLowerCase().startsWith('ar');
    final String reason = _blockingReason.isNotEmpty
        ? _blockingReason
        : (isArabic
            ? 'لم يتم تسجيل سبب إضافي.'
            : 'No further reason was provided.');

    return SafeArea(
      child: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 520),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: AppColors.cardColor,
                borderRadius: BorderRadius.circular(26),
                border: Border.all(
                  color: AppColors.error.withValues(alpha: 0.30),
                  width: 1.2,
                ),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.textDark.withValues(alpha: 0.08),
                    blurRadius: 24,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 74,
                    height: 74,
                    decoration: BoxDecoration(
                      color: AppColors.error.withValues(alpha: 0.10),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.gpp_bad_rounded,
                      color: AppColors.error,
                      size: 40,
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    isArabic ? 'تم حظر حسابك' : 'Your account is blocked',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: AppColors.textDark,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isArabic
                        ? 'أنت محظور من استخدام التطبيق بسبب مخالفة قواعد وشروط الاستخدام.'
                        : 'You are blocked from using the app because the Terms of Use were violated.',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: AppColors.textDark,
                      fontSize: 14,
                      height: 1.7,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  const SizedBox(height: 18),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.sectionBackground,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.lineColor),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isArabic ? 'السبب:' : 'Reason:',
                          style: const TextStyle(
                            color: AppColors.textDark,
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        const SizedBox(height: 7),
                        Text(
                          reason,
                          textAlign: TextAlign.start,
                          style: const TextStyle(
                            color: AppColors.textDark,
                            fontSize: 14,
                            height: 1.6,
                            fontFamily: 'Cairo',
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 22),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: ElevatedButton.icon(
                      onPressed: _openBlockedSupportMessage,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryTeal,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(15),
                        ),
                      ),
                      icon: const Icon(
                        Icons.support_agent_rounded,
                        color: Colors.white,
                      ),
                      label: Text(
                        isArabic
                            ? 'إرسال رسالة للدعم'
                            : 'Send a message to support',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _onTabSelected(int index) {
    if (_isBlocked || _isResolvingBlockedStatus) return;
    if (index < 0 || index > 3) return;
    if (_currentIndex == index && _loadedTabs.contains(index)) return;

    setState(() {
      _loadedTabs.add(index);
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    final List<Widget> pages = [
      AutoMatchingTab(
        currentLanguage: _currentLanguage,
        isActive: _currentIndex == 0,
        txt: _txt,
        unreadNotificationCount: _unreadNotificationCount,
        onNotificationPressed: _handleNotificationPressed,
        onUpgradeRequested: _showSubscriptionModalDirectly,
      ),
      _loadedTabs.contains(1)
          ? ChatsTab(
              currentLanguage: _currentLanguage,
              isActive: _currentIndex == 1,
              txt: _txt,
              unreadNotificationCount: _unreadNotificationCount,
              onNotificationPressed: _handleNotificationPressed,
            )
          : const SizedBox.shrink(),
      _loadedTabs.contains(2)
          ? SearchTab(
              currentLanguage: _currentLanguage,
              txt: _txt,
              unreadNotificationCount: _unreadNotificationCount,
              onNotificationPressed: _handleNotificationPressed,
            )
          : const SizedBox.shrink(),
      _loadedTabs.contains(3)
          ? AccountTab(
              currentLanguage: _currentLanguage,
              txt: _txt,
              unreadNotificationCount: _unreadNotificationCount,
              onNotificationPressed: _handleNotificationPressed,
              onProfileViewersPressed: _handleProfileViewersPressed,
              onLanguageChanged: (newLang) async {
                if (mounted) {
                  setState(() {
                    _currentLanguage = newLang;
                  });
                }
                try {
                  final prefs = await SharedPreferences.getInstance();
                  await prefs.setString('app_lang', newLang);
                  await prefs.setString('app_language', newLang);
                } catch (e) {
                  debugPrint('خطأ في حفظ اللغة: $e');
                }
              },
            )
          : const SizedBox.shrink(),
    ];

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (bool didPop, dynamic result) async {
        if (didPop || _isBlocked || _isResolvingBlockedStatus) return;
        final bool shouldExit = await _showExitConfirmationDialog();
        if (shouldExit) {
          SystemNavigator.pop();
        }
      },
      child: Directionality(
        textDirection:
            _currentLanguage == 'ar' ? TextDirection.rtl : TextDirection.ltr,
        child: Scaffold(
          body: _isResolvingBlockedStatus
              ? const Center(child: CircularProgressIndicator(color: AppColors.primaryTeal))
              : _isBlocked
                  ? _buildBlockedAccessScreen()
                  : Container(
                      width: double.infinity,
                      height: double.infinity,
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          colors: [AppColors.backgroundColor, AppColors.sectionBackground],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                      ),
                      child: SafeArea(
                        bottom: false,
                        child: IndexedStack(index: _currentIndex, children: pages),
                      ),
                    ),
          bottomNavigationBar: _isBlocked || _isResolvingBlockedStatus
              ? null
              : SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                    child: _buildGlassBottomNavigationBar(),
                  ),
                ),
        ),
      ),
    );
  }

  Widget _buildGlassBottomNavigationBar() {
    return ClipRRect(
      borderRadius: BorderRadius.circular(28),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
        child: Container(
          height: 78,
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 7),
          decoration: BoxDecoration(
            color: AppColors.cardColor.withValues(alpha: 0.97),
            borderRadius: BorderRadius.circular(28),
            border: Border.all(color: AppColors.lineColor, width: 1.2),
            boxShadow: [
              BoxShadow(
                color: AppColors.textDark.withValues(alpha: 0.10),
                blurRadius: 24,
                offset: const Offset(0, 9),
              ),
            ],
          ),
          child: Row(
            children: [
              _buildNavItem(0, Icons.favorite_outline_rounded,
                  Icons.favorite_rounded, _txt('home')),
              _buildNavItem(1, Icons.chat_bubble_outline_rounded,
                  Icons.chat_bubble_rounded, _txt('chats')),
              _buildNavItem(2, Icons.search_rounded,
                  Icons.search_rounded, _txt('search')),
              _buildNavItem(3, Icons.person_outline_rounded,
                  Icons.person_rounded, _txt('account')),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNavItem(
      int index, IconData icon, IconData activeIcon, String label) {
    final bool isSelected = _currentIndex == index;

    return Expanded(
      child: Semantics(
        button: true,
        selected: isSelected,
        label: label,
        child: GestureDetector(
          onTap: () => _onTabSelected(index),
          behavior: HitTestBehavior.opaque,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 240),
            curve: Curves.easeOutCubic,
            margin: const EdgeInsets.symmetric(horizontal: 3),
            decoration: BoxDecoration(
              color: isSelected
                  ? AppColors.accentLight.withValues(alpha: 0.27)
                  : Colors.transparent,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              mainAxisSize: MainAxisSize.min,
              children: [
                AnimatedContainer(
                  duration: const Duration(milliseconds: 240),
                  width: isSelected ? 37 : 33,
                  height: isSelected ? 37 : 33,
                  decoration: BoxDecoration(
                    color: isSelected
                        ? AppColors.primaryTeal
                        : AppColors.sectionBackground,
                    shape: BoxShape.circle,
                    boxShadow: isSelected
                        ? [
                            BoxShadow(
                              color: AppColors.primaryTeal
                                  .withValues(alpha: 0.18),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ]
                        : null,
                  ),
                  child: Icon(
                    isSelected ? activeIcon : icon,
                    color:
                        isSelected ? Colors.white : AppColors.textMuted,
                    size: 19,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: isSelected
                        ? AppColors.primaryTeal
                        : AppColors.textMuted,
                    fontWeight:
                        isSelected ? FontWeight.w900 : FontWeight.w600,
                    fontSize: 10.5,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

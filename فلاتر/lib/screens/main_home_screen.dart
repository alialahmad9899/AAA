// ============================================================================
// اسم الملف: lib/screens/main_home_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: الشاشة الرئيسية - إضافة أيقونة زوار الملف الشخصي وتدويل النصوص بالكامل
//        مع دعم الترجمة الديناميكية المزدوجة والتحديث اللحظي للغة.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../services/api_service.dart';
import 'notifications_screen.dart';
import 'my_profile_viewers_screen.dart';

import 'home/tabs/auto_matching_tab.dart';
import 'home/chats_tab.dart';
import 'home/tabs/search_tab.dart';
import 'home/tabs/account_tab.dart';

class MainHomeScreen extends StatefulWidget {
  final String currentLanguage;

  const MainHomeScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<MainHomeScreen> createState() => _MainHomeScreenState();
}

class _MainHomeScreenState extends State<MainHomeScreen> {
  int _currentIndex = 0;
  late String _currentLanguage;
  int _unreadNotificationCount = 0;

  bool _isModalActive = false;
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
    _currentLanguage = widget.currentLanguage;
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await _loadSavedLanguage();
      if (mounted) {
        _fetchNotificationsCount();
      }
    });
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
    if (!mounted || _isModalActive) return false;
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
                    color: const Color(0xFF01579B).withValues(alpha: 0.92),
                    borderRadius: BorderRadius.circular(28),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.35),
                      width: 1.5,
                    ),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.15),
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: Colors.white.withValues(alpha: 0.3),
                          ),
                        ),
                        child: const Icon(
                          Icons.exit_to_app_rounded,
                          color: Colors.white,
                          size: 32,
                        ),
                      ),
                      const SizedBox(height: 14),
                      Text(
                        _txt('exit_title'),
                        style: const TextStyle(
                          color: Colors.white,
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
                          color: Colors.white.withValues(alpha: 0.85),
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
                                    color: Colors.white70, fontFamily: 'Cairo'),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.white,
                                foregroundColor: const Color(0xFF0288D1),
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

  void _onTabSelected(int index) {
    if (index < 0 || index > 3) return;
    if (_currentIndex == index && _loadedTabs.contains(index)) return;

    setState(() {
      _loadedTabs.add(index);
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    const Color primarySkyBlue = Color(0xFF0288D1);

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
        if (didPop) return;
        final bool shouldExit = await _showExitConfirmationDialog();
        if (shouldExit) {
          SystemNavigator.pop();
        }
      },
      child: Scaffold(
        body: Directionality(
          textDirection:
              _currentLanguage == 'ar' ? TextDirection.rtl : TextDirection.ltr,
          child: Container(
            width: double.infinity,
            height: double.infinity,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  Color(0xFF01579B),
                  Color(0xFF0288D1),
                  Color(0xFF4FC3F7),
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: SafeArea(
              child: Stack(
                children: [
                  Padding(
                    padding: const EdgeInsets.only(bottom: 76.0),
                    child: IndexedStack(
                      index: _currentIndex,
                      children: pages,
                    ),
                  ),
                  Positioned(
                    bottom: 12,
                    left: 16,
                    right: 16,
                    child: _buildGlassBottomNavigationBar(primarySkyBlue),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildGlassBottomNavigationBar(Color primarySkyBlue) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(30),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
        child: Container(
          height: 62,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.20),
            borderRadius: BorderRadius.circular(30),
            border: Border.all(
              color: Colors.white.withValues(alpha: 0.40),
              width: 1.5,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.08),
                blurRadius: 20,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildNavItem(
                  0, Icons.favorite_outline, Icons.favorite, _txt('home')),
              _buildNavItem(1, Icons.chat_bubble_outline, Icons.chat_bubble,
                  _txt('chats')),
              _buildNavItem(2, Icons.search, Icons.search, _txt('search')),
              _buildNavItem(
                  3, Icons.person_outline, Icons.person, _txt('account')),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNavItem(
      int index, IconData icon, IconData activeIcon, String label) {
    final bool isSelected = _currentIndex == index;

    return GestureDetector(
      onTap: () => _onTabSelected(index),
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOutCubic,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(22),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.1),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  )
                ]
              : [],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              isSelected ? activeIcon : icon,
              color: isSelected ? const Color(0xFF0288D1) : Colors.white,
              size: 20,
            ),
            if (isSelected) ...[
              const SizedBox(width: 6),
              Text(
                label,
                style: const TextStyle(
                  color: Color(0xFF0288D1),
                  fontWeight: FontWeight.bold,
                  fontSize: 13,
                  fontFamily: 'Cairo',
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

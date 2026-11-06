// ============================================================================
// اسم الملف: lib/screens/home/tabs/account_tab.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: تبويب الحساب الشخصي - معالجة دقيقة لبيانات الجنس والإحصائيات الحقيقية
// ============================================================================

import 'dart:io';
import 'dart:ui';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter_stripe/flutter_stripe.dart';

import '../../../services/api_service.dart';
import '../../../services/profile_cache_service.dart';
import '../../../services/notification_service.dart';
import '../../../models/member_profile_model.dart';
import '../../../widgets/about_app_dialog.dart';
import '../../../widgets/app_snackbar.dart';
import '../../../widgets/package_badge_widget.dart';
import '../../../widgets/vip_survey_wizard_dialog.dart';
import '../../welcome_screen.dart';
import '../../edit_profile_screen.dart';
import '../../partner_expectations_screen.dart';
import '../../blocked_accounts_screen.dart';
import '../../user_guide_screen.dart';
import '../../support_tickets_screen.dart';
import '../../vip_services_screen.dart';

// ============================================================================
// 🟢 عناصر رسم الأعلام (Performance Optimized Flag Widgets)
// ============================================================================

class SyrianFlagWidget extends StatelessWidget {
  final double width;
  final double height;

  const SyrianFlagWidget({
    super.key,
    this.width = 24,
    this.height = 16,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(3)),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(3),
        child: Column(
          children: [
            Expanded(child: Container(color: const Color(0xFF007A3D))),
            Expanded(
              child: Container(
                color: Colors.white,
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    Icon(Icons.star, size: 4.5, color: Color(0xFFD32F2F)),
                    Icon(Icons.star, size: 4.5, color: Color(0xFFD32F2F)),
                    Icon(Icons.star, size: 4.5, color: Color(0xFFD32F2F)),
                  ],
                ),
              ),
            ),
            Expanded(child: Container(color: Colors.black)),
          ],
        ),
      ),
    );
  }
}

class UKFlagWidget extends StatelessWidget {
  final double width;
  final double height;

  const UKFlagWidget({
    super.key,
    this.width = 24,
    this.height = 16,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: const Color(0xFF012169),
        borderRadius: BorderRadius.circular(3),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(3),
        child: Stack(
          children: [
            Center(
              child: Container(width: width, height: 4, color: Colors.white),
            ),
            Center(
              child: Container(width: 4, height: height, color: Colors.white),
            ),
            Center(
              child: Container(
                width: width,
                height: 2.2,
                color: const Color(0xFFC8102E),
              ),
            ),
            Center(
              child: Container(
                width: 2.2,
                height: height,
                color: const Color(0xFFC8102E),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ============================================================================
// 🟢 الشاشة الرئيسية لتبويب الحساب
// ============================================================================

class AccountTab extends StatefulWidget {
  final String currentLanguage;
  final String Function(String) txt;
  final int unreadNotificationCount;
  final VoidCallback onNotificationPressed;
  final VoidCallback onProfileViewersPressed;
  final Function(String) onLanguageChanged;

  const AccountTab({
    super.key,
    required this.currentLanguage,
    required this.txt,
    required this.unreadNotificationCount,
    required this.onNotificationPressed,
    required this.onProfileViewersPressed,
    required this.onLanguageChanged,
  });

  static Future<void> showSubscriptionsModalDirectly(
    BuildContext context, {
    bool isArabic = true,
    VoidCallback? onSubscriptionUpdated,
  }) async {
    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (subContext) => _SubscriptionsModalContent(
        isArabic: isArabic,
        onSubscriptionUpdated: () async {
          await ProfileCacheService.clearCache();
          if (onSubscriptionUpdated != null) {
            onSubscriptionUpdated();
          }
          if (!context.mounted) return;
          final accountState =
              context.findAncestorStateOfType<AccountTabState>();
          if (accountState != null) {
            accountState.refreshProfile();
          }
        },
      ),
    );

    if (!context.mounted) return;

    if (result != null && result['showVipSurvey'] == true) {
      final double amount = (result['amount'] is num)
          ? (result['amount'] as num).toDouble()
          : (double.tryParse(result['amount']?.toString() ?? '0') ?? 0.0);
      final String packageName = result['packageName']?.toString() ?? '';
      final int? packagePaymentId = (result['packagePaymentId'] is int)
          ? result['packagePaymentId']
          : int.tryParse(result['packagePaymentId']?.toString() ?? '');

      if (amount >= 50 || packageName.toUpperCase().contains('VIP')) {
        final String? token = await ApiService.getToken();
        if (context.mounted) {
          showDialog(
            context: context,
            barrierDismissible: false,
            builder: (dialogCtx) => VipSurveyWizardDialog(
              packagePaymentId: packagePaymentId,
              userToken: token ?? '',
              baseUrl: ApiService.baseUrl,
              isArabic: isArabic,
            ),
          );
        }
      }
    }
  }

  static Future<void> showQuotaExceededModal(
    BuildContext context, {
    bool isArabic = true,
    VoidCallback? onSubscriptionUpdated,
  }) async {
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (modalContext) => Directionality(
        textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: const Color(0xFF0F172A).withValues(alpha: 0.96),
            borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
            border: Border.all(
                color: const Color(0xFF38BDF8).withValues(alpha: 0.4)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.white30,
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              const SizedBox(height: 20),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.amber.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.amber, width: 1.5),
                ),
                child: const Icon(Icons.favorite_border_rounded,
                    color: Colors.amber, size: 48),
              ),
              const SizedBox(height: 16),
              Text(
                isArabic
                    ? 'استنفدت حصة الإعجابات المجانية! 🎯'
                    : 'Free Likes Limit Reached! 🎯',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Cairo',
                ),
              ),
              const SizedBox(height: 10),
              Text(
                isArabic
                    ? 'لقد استنفدت الإعجابات المجانية الـ 5 المتاحة لك. اشترك الآن في إحدى باقات وصال المميزة لمتابعة إبداء الاهتمام ورؤية التوافق!'
                    : 'You have used all 5 free likes. Upgrade to premium package to keep sending interest requests!',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 13.5,
                  fontFamily: 'Cairo',
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0288D1),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16)),
                  ),
                  icon: const Icon(Icons.workspace_premium_rounded,
                      color: Colors.amber, size: 22),
                  label: Text(
                    isArabic
                        ? 'ترقية الحساب والاشتراك الآن 🚀'
                        : 'Upgrade Account Now 🚀',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  onPressed: () {
                    Navigator.pop(modalContext);
                    if (!context.mounted) return;
                    showSubscriptionsModalDirectly(
                      context,
                      isArabic: isArabic,
                      onSubscriptionUpdated: onSubscriptionUpdated,
                    );
                  },
                ),
              ),
              const SizedBox(height: 10),
            ],
          ),
        ),
      ),
    );
  }

  @override
  State<AccountTab> createState() => AccountTabState();
}

class AccountTabState extends State<AccountTab> {
  String _userName = '';
  String _userPhoto = '';
  bool _isLoadingUser = true;
  MemberProfileModel? _userProfile;

  bool get _isAr => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _loadUserProfileData();
  }

  Future<void> _loadUserProfileData({bool forceRefresh = false}) async {
    if (forceRefresh) {
      await ProfileCacheService.clearCache();
    }

    try {
      // Start the full profile request in parallel, but do not block the account
      // header on unrelated sections such as education or preferences.
      final profileFuture = ApiService.getCurrentUserProfile(
        lang: widget.currentLanguage,
      );
      final summary =
          await ApiService.getCurrentUserSummary(lang: widget.currentLanguage);

      final summaryName = (summary?['name'] ?? '').toString().trim();
      final summaryPhoto = (summary?['photo'] ?? '').toString().trim();
      if (mounted) {
        setState(() {
          if (summaryName.isNotEmpty) _userName = summaryName;
          if (summaryPhoto.isNotEmpty) _userPhoto = summaryPhoto;
          _isLoadingUser = false;
        });
      }

      final MemberProfileModel? profile = await profileFuture;
      final profileName = profile?.basicInfo.fullName.trim() ?? '';
      final profilePhoto = profile?.basicInfo.photo.trim() ?? '';
      if (mounted) {
        setState(() {
          _userProfile = profile;
          if (_userName.isEmpty && profileName.isNotEmpty) {
            _userName = profileName;
          }
          if (_userPhoto.isEmpty && profilePhoto.isNotEmpty) {
            _userPhoto = profilePhoto;
          }
        });
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [AccountTab] Failed to load account identity: $e');
      }
      if (mounted) {
        setState(() {
          _isLoadingUser = false;
        });
      }
    }
  }

  Future<void> refreshProfile() async {
    await _loadUserProfileData(forceRefresh: true);
  }

  Future<void> showSubscriptionsModal() async {
    await AccountTab.showSubscriptionsModalDirectly(
      context,
      isArabic: _isAr,
      onSubscriptionUpdated: () async {
        await _loadUserProfileData(forceRefresh: true);
      },
    );
  }

  Future<void> _performFullLogout() async {
    // إيقاف ربط التوكن فوراً حتى لا تعيد أحداث onTokenRefresh ربط الجهاز
    // بالحساب الذي يجري تسجيل الخروج منه.
    NotificationService.resetSession();

    try {
      await ApiService.logout();
    } catch (e) {
      debugPrint("⚠️ تعذر إتمام طلب تسجيل الخروج على السيرفر: $e");
    } finally {
      // تنظيف بيانات الجلسة فقط مع الإبقاء على لغة التطبيق وإعداداته العامة.
      await ApiService.clearToken();
      await ProfileCacheService.clearCache();
      final SharedPreferences prefs = await SharedPreferences.getInstance();
      await prefs.remove('last_processed_initial_msg_id');

      if (mounted) {
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (context) => const WelcomeScreen()),
          (route) => false,
        );
      }
    }
  }

  Future<void> _showDeleteAccountDialog() async {
    final bool? confirm = await showDialog<bool>(
      context: context,
      builder: (context) => Directionality(
        textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
        child: AlertDialog(
          backgroundColor: const Color(0xFF013B5E),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              const Icon(Icons.warning_amber_rounded,
                  color: Colors.redAccent, size: 28),
              const SizedBox(width: 10),
              Text(_isAr ? 'تأكيد حذف الحساب' : 'Confirm Deletion',
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontFamily: 'Cairo',
                      fontWeight: FontWeight.bold)),
            ],
          ),
          content: Text(
            _isAr
                ? 'هل أنت متأكد من رغبتك في حذف حسابك نهائياً؟ هذا الإجراء غير قابل للتراجع وسيقوم بمسح كافة بياناتك من النظام.'
                : 'Are you sure you want to delete your account permanently? This action cannot be undone.',
            style: const TextStyle(
                color: Colors.white70, fontSize: 14, fontFamily: 'Cairo'),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(_isAr ? 'إلغاء' : 'Cancel',
                  style: const TextStyle(
                      color: Colors.white70, fontFamily: 'Cairo')),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.redAccent,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12))),
              onPressed: () => Navigator.pop(context, true),
              child: Text(_isAr ? 'حذف الحساب' : 'Delete Account',
                  style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo')),
            ),
          ],
        ),
      ),
    );

    if (confirm == true && mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (dialogContext) => Center(
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: const Color(0xFF0F172A),
              borderRadius: BorderRadius.circular(18),
              border:
                  Border.all(color: Colors.redAccent.withValues(alpha: 0.5)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const CircularProgressIndicator(color: Colors.redAccent),
                const SizedBox(height: 16),
                Text(
                  _isAr ? 'جاري حذف الحساب...' : 'Deleting account...',
                  style: const TextStyle(
                    color: Colors.white,
                    fontFamily: 'Cairo',
                    fontSize: 14,
                    decoration: TextDecoration.none,
                  ),
                ),
              ],
            ),
          ),
        ),
      );

      try {
        final res = await ApiService.deleteAccount();

        if (!mounted) return;
        Navigator.of(context, rootNavigator: true).pop();

        if (res['success'] == true) {
          AppSnackBar.show(
            context: context,
            type: NotificationType.success,
            isArabic: _isAr,
            messageAr: res['message'] ?? 'تم حذف حسابك بنجاح',
            messageEn: 'Your account has been deleted successfully',
          );

          await _performFullLogout();
        } else {
          AppSnackBar.show(
            context: context,
            type: NotificationType.error,
            isArabic: _isAr,
            messageAr: res['message'] ?? 'فشل حذف الحساب، يرجى المحاولة لاحقاً',
            messageEn: 'Failed to delete account, please try again',
          );
        }
      } catch (e) {
        if (!mounted) return;
        Navigator.of(context, rootNavigator: true).pop();
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isAr,
          messageAr: 'حدث خطأ أثناء التواصل مع السيرفر لحذف الحساب',
          messageEn: 'An error occurred while deleting account from server',
        );
      }
    }
  }

  Widget _buildCompactHeader(String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 4.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                  color: Colors.white.withValues(alpha: 0.35), width: 1.2),
            ),
            child: Row(
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(
                    color: Color(0xFF4FC3F7),
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
          Row(
            children: [
              Container(
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  shape: BoxShape.circle,
                  border: Border.all(
                      color: Colors.white.withValues(alpha: 0.35), width: 1.2),
                ),
                child: IconButton(
                  constraints:
                      const BoxConstraints(minWidth: 38, minHeight: 38),
                  padding: EdgeInsets.zero,
                  icon: const Icon(Icons.remove_red_eye_outlined,
                      color: Colors.white, size: 22),
                  tooltip: widget.txt('profile_viewers_title'),
                  onPressed: widget.onProfileViewersPressed,
                ),
              ),
              const SizedBox(width: 8),
              Container(
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  shape: BoxShape.circle,
                  border: Border.all(
                      color: Colors.white.withValues(alpha: 0.35), width: 1.2),
                ),
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    IconButton(
                      constraints:
                          const BoxConstraints(minWidth: 38, minHeight: 38),
                      padding: EdgeInsets.zero,
                      icon: const Icon(Icons.notifications_none_rounded,
                          color: Colors.white, size: 22),
                      onPressed: widget.onNotificationPressed,
                    ),
                    if (widget.unreadNotificationCount > 0)
                      Positioned(
                        top: 6,
                        right: 6,
                        child: Container(
                          padding: const EdgeInsets.all(3),
                          decoration: const BoxDecoration(
                              color: Colors.redAccent, shape: BoxShape.circle),
                          child: Text(
                            '${widget.unreadNotificationCount}',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 9,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// 🟢 تصميم الهيدر الرئيسي مع قراءة الأعداد والإحصائيات الحقيقية المظبوطة
  Widget _buildModernProfileHeader(String displayName, bool isFemale) {
    final int age = _userProfile?.basicInfo.age ?? 0;

    // Real database counts are independent from subscription quotas.
    final int sentInterestsDisplay = _userProfile?.sentInterestsCount ?? 0;
    final int profileVisitorsDisplay = _userProfile?.profileVisitorsCount ?? 0;
    final String residencyCountry =
        _userProfile?.residenceInfo?.residencyCountry.trim() ?? '';

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 12.0),
      padding: const EdgeInsets.all(16.0),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            const Color(0xFF0288D1).withValues(alpha: 0.25),
            const Color(0xFF0F172A).withValues(alpha: 0.85),
            const Color(0xFF004D40).withValues(alpha: 0.20),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: const Color(0xFF38BDF8).withValues(alpha: 0.4),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0288D1).withValues(alpha: 0.15),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            textDirection: TextDirection.ltr,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Stack(
                children: [
                  Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(
                        colors: isFemale
                            ? [const Color(0xFFF48FB1), const Color(0xFFC2185B)]
                            : [
                                const Color(0xFF38BDF8),
                                const Color(0xFF0288D1)
                              ],
                      ),
                    ),
                    child: CircleAvatar(
                      radius: 40,
                      backgroundColor: const Color(0xFF01579B),
                      child: _isLoadingUser
                          ? const CircularProgressIndicator(
                              color: Colors.white, strokeWidth: 2)
                          : _userPhoto.isNotEmpty
                              ? ClipRRect(
                                  borderRadius: BorderRadius.circular(40),
                                  child: CachedNetworkImage(
                                    imageUrl: _userPhoto,
                                    width: 80,
                                    height: 80,
                                    fit: BoxFit.cover,
                                    placeholder: (context, url) =>
                                        Container(color: Colors.white12),
                                    errorWidget: (context, url, error) =>
                                        const Icon(
                                      Icons.person_rounded,
                                      size: 44,
                                      color: Colors.white,
                                    ),
                                  ),
                                )
                              : const Icon(Icons.person_rounded,
                                  size: 44, color: Colors.white),
                    ),
                  ),
                  Positioned(
                    bottom: 2,
                    right: 2,
                    child: Container(
                      width: 14,
                      height: 14,
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981),
                        shape: BoxShape.circle,
                        border: Border.all(
                            color: const Color(0xFF0F172A), width: 2),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Directionality(
                  textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        displayName,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          if (age > 0) ...[
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.cake_rounded,
                                      color: Color(0xFFFFB74D), size: 13),
                                  const SizedBox(width: 4),
                                  Text(
                                    _isAr ? '$age سنة' : '$age yrs',
                                    style: const TextStyle(
                                      color: Color(0xE6FFFFFF),
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                          ],
                          if (_userProfile != null)
                            Expanded(
                              child: SingleChildScrollView(
                                scrollDirection: Axis.horizontal,
                                child: (_userProfile!.basicInfo.packageName
                                            .isNotEmpty &&
                                        _userProfile!.isSubscribedActive)
                                    ? PackageBadgeWidget(
                                        packageName: _userProfile!
                                            .basicInfo.packageName,
                                        isArabic: _isAr,
                                      )
                                    : Container(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: 8, vertical: 3),
                                            decoration: BoxDecoration(
                                              color: Colors.amber
                                                  .withValues(alpha: 0.2),
                                              borderRadius:
                                                  BorderRadius.circular(8),
                                              border: Border.all(
                                                  color: Colors.amber
                                                      .withValues(alpha: 0.6)),
                                            ),
                                            child: Text(
                                              _isAr ? 'مجاني 🔒' : 'Free 🔒',
                                              style: const TextStyle(
                                                color: Colors.amber,
                                                fontSize: 11,
                                                fontWeight: FontWeight.bold,
                                                fontFamily: 'Cairo',
                                              ),
                                            ),
                                          ),
                              ),
                            ),
                        ],
                      ),
                      if (residencyCountry.isNotEmpty) ...[
                        const SizedBox(height: 7),
                        Align(
                          alignment: _isAr
                              ? Alignment.centerRight
                              : Alignment.centerLeft,
                          child: Container(
                            constraints: const BoxConstraints(maxWidth: 210),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 9,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFF38BDF8)
                                  .withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(
                                color: const Color(0xFF38BDF8)
                                    .withValues(alpha: 0.25),
                              ),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(
                                  Icons.location_on_outlined,
                                  color: Color(0xFF80DEEA),
                                  size: 13,
                                ),
                                const SizedBox(width: 4),
                                Flexible(
                                  child: Text(
                                    residencyCountry,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      color: Color(0xE6FFFFFF),
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            height: 1,
            color: Colors.white.withValues(alpha: 0.12),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              // 🟢 عدد إبداء الاهتمام المرسل
              Expanded(
                child: InkWell(
                  onTap: () {
                    Navigator.pushNamed(
                      context,
                      '/my-interests',
                      arguments: {'currentLanguage': widget.currentLanguage},
                    );
                  },
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFE91E63).withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                          color:
                              const Color(0xFFE91E63).withValues(alpha: 0.35)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.favorite_rounded,
                            color: Color(0xFFF48FB1), size: 18),
                        const SizedBox(width: 6),
                        Flexible(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                _isAr ? 'الاهتمام المرسل' : 'Sent Interests',
                                style: const TextStyle(
                                  color: Colors.white70,
                                  fontSize: 10.5,
                                  fontFamily: 'Cairo',
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                              Text(
                                '$sentInterestsDisplay',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 14,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),

              // 🟢 عدد زوار الملف الشخصي
              Expanded(
                child: InkWell(
                  onTap: widget.onProfileViewersPressed,
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFF00ACC1).withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                          color:
                              const Color(0xFF00ACC1).withValues(alpha: 0.35)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.remove_red_eye_rounded,
                            color: Color(0xFF80DEEA), size: 18),
                        const SizedBox(width: 6),
                        Flexible(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                _isAr ? 'زوار ملفي' : 'Profile Visitors',
                                style: const TextStyle(
                                  color: Colors.white70,
                                  fontSize: 10.5,
                                  fontFamily: 'Cairo',
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                              Text(
                                '$profileVisitorsDisplay',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 14,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(right: 6, left: 6, top: 18, bottom: 8),
      child: Row(
        children: [
          Icon(icon, color: const Color(0xFF81D4FA), size: 18),
          const SizedBox(width: 8),
          Text(title,
              style: const TextStyle(
                  color: Color(0xFFE0F7FA),
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Cairo')),
        ],
      ),
    );
  }

  Widget _buildMenuItem({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
    List<Color>? gradientColors,
    Color textColor = Colors.white,
    bool showDivider = true,
  }) {
    final List<Color> colors = gradientColors ??
        [
          Colors.white.withValues(alpha: 0.25),
          Colors.white.withValues(alpha: 0.12)
        ];

    return Column(
      children: [
        Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(16),
            child: Padding(
              padding:
                  const EdgeInsets.symmetric(horizontal: 14.0, vertical: 12.0),
              child: Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                          colors: colors,
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(icon, color: Colors.white, size: 20),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                      child: Text(title,
                          style: TextStyle(
                              color: textColor,
                              fontSize: 15,
                              fontWeight: FontWeight.w600,
                              fontFamily: 'Cairo'))),
                  Icon(
                      _isAr
                          ? Icons.arrow_back_ios_new_rounded
                          : Icons.arrow_forward_ios_rounded,
                      size: 14,
                      color: Colors.white.withValues(alpha: 0.5)),
                ],
              ),
            ),
          ),
        ),
        if (showDivider)
          Divider(
              height: 1,
              indent: 62,
              endIndent: 14,
              color: Colors.white.withValues(alpha: 0.12)),
      ],
    );
  }

  Widget _buildLanguageSwitcherTile() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14.0, vertical: 12.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                      colors: [Color(0xFF00B0FF), Color(0xFF00838F)]),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.language_rounded,
                    color: Colors.white, size: 20),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(_isAr ? 'لغة التطبيق' : 'App Language',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo')),
                    Text(
                        _isAr
                            ? 'اختر لغة الواجهة'
                            : 'Select interface language',
                        style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.65),
                            fontSize: 12,
                            fontFamily: 'Cairo')),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            height: 48,
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: const Color(0xFF002942).withValues(alpha: 0.7),
              borderRadius: BorderRadius.circular(24),
              border: Border.all(
                  color: Colors.white.withValues(alpha: 0.25), width: 1.2),
            ),
            child: Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () async {
                      if (_isAr) {
                        final prefs = await SharedPreferences.getInstance();
                        await prefs.setString('app_language', 'en');
                        await prefs.setString('app_lang', 'en');
                        if (mounted) {
                          widget.onLanguageChanged('en');
                        }
                      }
                    },
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      decoration: BoxDecoration(
                          color: !_isAr ? Colors.white : Colors.transparent,
                          borderRadius: BorderRadius.circular(20)),
                      child: Center(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const UKFlagWidget(),
                            const SizedBox(width: 8),
                            Text('English',
                                style: TextStyle(
                                    color: !_isAr
                                        ? const Color(0xFF01579B)
                                        : Colors.white,
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: GestureDetector(
                    onTap: () async {
                      if (!_isAr) {
                        final prefs = await SharedPreferences.getInstance();
                        await prefs.setString('app_language', 'ar');
                        await prefs.setString('app_lang', 'ar');
                        if (mounted) {
                          widget.onLanguageChanged('ar');
                        }
                      }
                    },
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      decoration: BoxDecoration(
                          color: _isAr ? Colors.white : Colors.transparent,
                          borderRadius: BorderRadius.circular(20)),
                      child: Center(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const SyrianFlagWidget(),
                            const SizedBox(width: 8),
                            Text('العربية',
                                style: TextStyle(
                                    color: _isAr
                                        ? const Color(0xFF01579B)
                                        : Colors.white,
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo')),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildGlassGroupCard({required List<Widget> children}) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(24),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.14),
            borderRadius: BorderRadius.circular(24),
            border: Border.all(
                color: Colors.white.withValues(alpha: 0.3), width: 1.2),
          ),
          child: Column(children: children),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final String displayName =
        _userName.isNotEmpty ? _userName : widget.txt('registered_user');

    // 🟢 التحقق الدقيق والآمن من جنس المستخدم (أنثى أم ذكر)
    final bool isFemale = _userProfile != null &&
        (_userProfile!.basicInfo.isFemale == true ||
            _userProfile!.basicInfo.gender
                .trim()
                .toLowerCase()
                .contains('أنثى') ||
            _userProfile!.basicInfo.gender
                .trim()
                .toLowerCase()
                .contains('انثى') ||
            _userProfile!.basicInfo.gender
                .trim()
                .toLowerCase()
                .contains('female'));

    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: Column(
        children: [
          _buildCompactHeader(widget.txt('account')),
          const SizedBox(height: 6),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => _loadUserProfileData(forceRefresh: true),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(
                    horizontal: 18.0, vertical: 10.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildModernProfileHeader(displayName, isFemale),
                    _buildSectionTitle(
                        _isAr ? 'إدارة الملف والشخصية' : 'Profile Management',
                        Icons.person_pin_rounded),
                    _buildGlassGroupCard(
                      children: [
                        _buildMenuItem(
                          icon: Icons.edit_note_rounded,
                          title: widget.txt('edit_profile'),
                          gradientColors: const [
                            Color(0xFF0288D1),
                            Color(0xFF00ACC1)
                          ],
                          onTap: () async {
                            final updated = await Navigator.push(
                              context,
                              MaterialPageRoute(
                                  builder: (context) => EditProfileScreen(
                                      currentLanguage: widget.currentLanguage)),
                            );
                            if (mounted && updated == true) {
                              await ProfileCacheService.clearCache();
                              _loadUserProfileData(forceRefresh: true);
                            }
                          },
                        ),
                        _buildMenuItem(
                          icon: Icons.tune_rounded,
                          title: widget.txt('expectations'),
                          gradientColors: const [
                            Color(0xFF8E24AA),
                            Color(0xFFAB47BC)
                          ],
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                  builder: (context) =>
                                      PartnerExpectationsScreen(
                                          currentLanguage:
                                              widget.currentLanguage)),
                            );
                          },
                        ),
                        _buildMenuItem(
                          icon: Icons.workspace_premium_rounded,
                          title: widget.txt('subscriptions'),
                          gradientColors: const [
                            Color(0xFFFF8F00),
                            Color(0xFFFFB300)
                          ],
                          onTap: showSubscriptionsModal,
                        ),
                        _buildMenuItem(
                          icon: Icons.diamond_rounded,
                          title: _isAr
                              ? 'الخدمات الإضافية VIP'
                              : 'VIP Extra Services',
                          gradientColors: const [
                            Color(0xFFFFD700),
                            Color(0xFFFFA000)
                          ],
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => VipServicesScreen(
                                  currentLanguage: widget.currentLanguage,
                                ),
                              ),
                            );
                          },
                        ),
                        _buildMenuItem(
                          icon: Icons.favorite_rounded,
                          title: _isAr ? 'طلبات الاهتمام' : 'Interest Requests',
                          gradientColors: const [
                            Color(0xFFE91E63),
                            Color(0xFFF48FB1)
                          ],
                          onTap: () {
                            Navigator.pushNamed(
                              context,
                              '/my-interests',
                              arguments: {
                                'currentLanguage': widget.currentLanguage
                              },
                            );
                          },
                        ),
                        _buildMenuItem(
                          icon: Icons.block_rounded,
                          title: widget.txt('blocked'),
                          gradientColors: const [
                            Color(0xFFD32F2F),
                            Color(0xFFE53935)
                          ],
                          showDivider: false,
                          onTap: () {
                            Navigator.push(
                                context,
                                MaterialPageRoute(
                                    builder: (context) => BlockedAccountsScreen(
                                        currentLanguage:
                                            widget.currentLanguage)));
                          },
                        ),
                      ],
                    ),
                    _buildSectionTitle(
                        _isAr ? 'التفضيلات واللغة' : 'Preferences & Language',
                        Icons.translate_rounded),
                    _buildGlassGroupCard(
                        children: [_buildLanguageSwitcherTile()]),
                    _buildSectionTitle(
                        _isAr ? 'الدعم والمعلومات' : 'Support & Information',
                        Icons.info_outline_rounded),
                    _buildGlassGroupCard(
                      children: [
                        _buildMenuItem(
                          icon: Icons.menu_book_rounded,
                          title: widget.txt('faq'),
                          gradientColors: const [
                            Color(0xFF00897B),
                            Color(0xFF26A69A)
                          ],
                          onTap: () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                  builder: (context) => UserGuideScreen(
                                      currentLanguage:
                                          widget.currentLanguage))),
                        ),
                        _buildMenuItem(
                          icon: Icons.support_agent_rounded,
                          title: widget.txt('support'),
                          gradientColors: const [
                            Color(0xFF43A047),
                            Color(0xFF66BB6A)
                          ],
                          onTap: () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                  builder: (context) => SupportTicketsScreen(
                                      currentLanguage:
                                          widget.currentLanguage))),
                        ),
                        _buildMenuItem(
                          icon: Icons.auto_awesome_rounded,
                          title: widget.txt('about'),
                          gradientColors: const [
                            Color(0xFF00ACC1),
                            Color(0xFF26C6DA)
                          ],
                          showDivider: false,
                          onTap: () => AboutAppDialog.show(context,
                              currentLanguage: widget.currentLanguage),
                        ),
                      ],
                    ),
                    _buildSectionTitle(
                        _isAr ? 'إجراءات الحساب' : 'Account Actions',
                        Icons.security_rounded),
                    _buildGlassGroupCard(
                      children: [
                        _buildMenuItem(
                          icon: Icons.delete_forever_rounded,
                          title: _isAr ? 'حذف الحساب' : 'Delete Account',
                          textColor: const Color.fromARGB(255, 243, 1, 1),
                          gradientColors: const [
                            Color(0xFFC62828),
                            Color.fromARGB(255, 232, 4, 0)
                          ],
                          onTap: _showDeleteAccountDialog,
                        ),
                        _buildMenuItem(
                          icon: Icons.logout_rounded,
                          title: widget.txt('logout'),
                          textColor: const Color.fromARGB(255, 243, 1, 1),
                          gradientColors: const [
                            Color(0xFFB71C1C),
                            Color.fromARGB(255, 232, 4, 0)
                          ],
                          showDivider: false,
                          onTap: _performFullLogout,
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// محتوى نافذة الاشتراكات
// ============================================================================

class _SubscriptionsModalContent extends StatefulWidget {
  final bool isArabic;
  final VoidCallback? onSubscriptionUpdated;

  const _SubscriptionsModalContent({
    required this.isArabic,
    this.onSubscriptionUpdated,
  });

  @override
  State<_SubscriptionsModalContent> createState() =>
      _SubscriptionsModalContentState();
}

class _SubscriptionsModalContentState
    extends State<_SubscriptionsModalContent> {
  static const String _officialShamCashCode =
      '0f50e77304209ae23d85b7971f1089b7';

  bool _isLoading = true;
  bool _isFreeFemale = false;
  List<dynamic> _packages = [];
  List<dynamic> _manualMethods = [];
  String _currencySymbol = '\$';
  int? _selectedPackageId;
  double _selectedAmount = 0;
  String _selectedPackageName = '';

  bool _hasActiveSubscription = false;
  String _activePackageName = '';
  int _remainingDays = 0;
  final int _totalValidityDays = 30;
  String _expiryDateStr = '';
  int _remainingViewersQuota = 0;

  bool _showPaymentStep = false;
  int _selectedPaymentCategory = 2;

  int? _selectedManualMethodId;
  final TextEditingController _accountNameController = TextEditingController();
  File? _proofFile;
  bool _isSubmitting = false;

  String? _cardErrorMessage;

  @override
  void initState() {
    super.initState();
    _loadSubscriptionData();
  }

  @override
  void dispose() {
    _accountNameController.dispose();
    super.dispose();
  }

  String _getTranslatedPackageName(String rawName, bool isArabic) {
    final String clean = rawName.trim();
    if (clean.isEmpty) return '';
    if (!isArabic) {
      final String lower = clean.toLowerCase();
      if (lower.contains('أسبوعي') ||
          lower.contains('اسبوعي') ||
          lower.contains('weekly')) {
        return 'Weekly Subscription';
      } else if (lower.contains('شهري') ||
          lower.contains('الشهري') ||
          lower.contains('monthly')) {
        return 'Monthly Subscription';
      } else if (lower.contains('3') ||
          lower.contains('ثلاث') ||
          lower.contains('3-month')) {
        return '3-Month Subscription';
      } else if (lower.contains('سنوي') ||
          lower.contains('السنوي') ||
          lower.contains('yearly') ||
          lower.contains('annual')) {
        return 'Yearly Subscription';
      } else if (lower.contains('أساسية') ||
          lower.contains('اساسية') ||
          lower.contains('basic')) {
        return 'Basic Package';
      } else if (lower.contains('vip') ||
          lower.contains('ملكي') ||
          lower.contains('royal')) {
        return 'Royal VIP Package';
      }
    }
    return rawName;
  }

  Future<void> _loadSubscriptionData() async {
    try {
      final pkgRes = await ApiService.getActivePackages();
      final methods = await ApiService.getManualPaymentMethods();
      final profile = await ApiService.getCurrentUserProfile();

      bool isFemaleUser = false;
      if (profile != null) {
        final genderStr = profile.basicInfo.gender.trim().toLowerCase();
        if (profile.basicInfo.isFemale == true ||
            genderStr.contains('female') ||
            genderStr.contains('أنثى') ||
            genderStr.contains('انثى')) {
          isFemaleUser = true;
        }

        _parseSubscriptionValidity(profile);
      }

      if (mounted) {
        setState(() {
          _isFreeFemale = (pkgRes['is_free'] == true) || isFemaleUser;
          _packages = (pkgRes['data'] is List) ? pkgRes['data'] : [];
          final serverCurrencySymbol = pkgRes['currency_symbol']?.toString().trim();
          if (serverCurrencySymbol != null && serverCurrencySymbol.isNotEmpty) {
            _currencySymbol = serverCurrencySymbol;
          }
          _manualMethods = methods;
          if (_manualMethods.isNotEmpty) {
            final first = _manualMethods.first;
            if (first is Map) {
              _selectedManualMethodId = (first['id'] is int)
                  ? first['id']
                  : int.tryParse(first['id']?.toString() ?? '');
            }
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _parseSubscriptionValidity(MemberProfileModel profile) {
    _hasActiveSubscription = false;
    _remainingDays = 0;
    _expiryDateStr = '';
    _activePackageName = '';
    _remainingViewersQuota = 0;

    try {
      final validityString = profile.basicInfo.packageValidity;
      if (validityString.isNotEmpty && profile.isSubscribedActive) {
        final DateTime? expiryDate = DateTime.tryParse(validityString);
        if (expiryDate != null) {
          final DateTime now = DateTime.now();
          final DateTime today = DateTime(now.year, now.month, now.day);
          final DateTime lastValidDay = DateTime(
            expiryDate.year,
            expiryDate.month,
            expiryDate.day,
          );
          // package_validity is inclusive: the account remains active on this date.
          // Show zero days remaining on the expiry date without hiding the active plan.
          final int days = lastValidDay.difference(today).inDays;

          if (days >= 0) {
            _hasActiveSubscription = true;
            _remainingDays = days;
            _expiryDateStr =
                "${expiryDate.year}-${expiryDate.month.toString().padLeft(2, '0')}-${expiryDate.day.toString().padLeft(2, '0')}";

            final String rawName = profile.basicInfo.packageName.isNotEmpty
                ? profile.basicInfo.packageName
                : (widget.isArabic ? 'الباقة المميزة' : 'Active Package');

            _activePackageName =
                _getTranslatedPackageName(rawName, widget.isArabic);
            _remainingViewersQuota = profile.basicInfo.remainingProfileViewers;
          }
        }
      }
    } catch (_) {}
  }

  Future<void> _pickProofImage() async {
    try {
      final ImagePicker picker = ImagePicker();
      final XFile? image =
          await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
      if (image != null && mounted) {
        setState(() => _proofFile = File(image.path));
      }
    } catch (e) {
      if (mounted) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: widget.isArabic,
          messageAr: 'تعذر اختيار الصورة من المعرض',
          messageEn: 'Failed to pick image from gallery',
        );
      }
    }
  }

  void _showShamCashSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => Directionality(
        textDirection: widget.isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          backgroundColor: Colors.transparent,
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(28),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A).withValues(alpha: 0.95),
                  borderRadius: BorderRadius.circular(28),
                  border: Border.all(
                    color: const Color(0xFFF59E0B).withValues(alpha: 0.5),
                    width: 1.5,
                  ),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF59E0B).withValues(alpha: 0.18),
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: const Color(0xFFF59E0B).withValues(alpha: 0.5),
                        ),
                      ),
                      child: const Icon(
                        Icons.access_time_filled_rounded,
                        color: Color(0xFFF59E0B),
                        size: 48,
                      ),
                    ),
                    const SizedBox(height: 18),
                    Text(
                      widget.isArabic
                          ? 'تم استلام طلب شام كاش! ✨'
                          : 'Request Received! ✨',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 19,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      widget.isArabic
                          ? 'سيتم مطابقة التحويل وتفعيل/تجديد الاشتراك بمدة أقصاها 24 ساعة، وسيصلك إشعار فور تفعيل الباقة.\n\nشكراً لثقتك بنا ❤'
                          : 'Your subscription will be activated within 24 hours after verifying the transfer.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 14,
                        fontFamily: 'Cairo',
                        height: 1.5,
                      ),
                    ),
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFFF59E0B),
                          padding: const EdgeInsets.symmetric(vertical: 13),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        onPressed: () async {
                          Navigator.pop(dialogContext);
                          await ProfileCacheService.clearCache();
                          if (widget.onSubscriptionUpdated != null) {
                            widget.onSubscriptionUpdated!();
                          }
                          if (mounted) {
                            Navigator.pop(context, {
                              'showVipSurvey': true,
                              'amount': _selectedAmount,
                              'packageName': _selectedPackageName,
                              'packagePaymentId': null,
                            });
                          }
                        },
                        child: Text(
                          widget.isArabic ? 'موافق' : 'OK',
                          style: const TextStyle(
                            color: Colors.black,
                            fontSize: 15,
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
      ),
    );
  }

  void _showInstantVisaSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => Directionality(
        textDirection: widget.isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          backgroundColor: Colors.transparent,
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(28),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A).withValues(alpha: 0.96),
                  borderRadius: BorderRadius.circular(28),
                  border: Border.all(
                    color: const Color(0xFF10B981).withValues(alpha: 0.6),
                    width: 1.8,
                  ),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981).withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: const Color(0xFF10B981),
                          width: 1.5,
                        ),
                      ),
                      child: const Icon(
                        Icons.verified_rounded,
                        color: Color(0xFF10B981),
                        size: 52,
                      ),
                    ),
                    const SizedBox(height: 18),
                    Text(
                      widget.isArabic
                          ? 'مبروك! تم تفعيل الاشتراك فوراً 🎉'
                          : 'Subscription Activated Instantly! 🎉',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 19,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      widget.isArabic
                          ? 'تمت عملية الدفع ببطاقة الفيزا بنجاح! تم تفعيل ($_selectedPackageName) بجميع ميزاتها لحسابك الآن.'
                          : 'Payment successful! ($_selectedPackageName) has been activated for your account.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 14,
                        fontFamily: 'Cairo',
                        height: 1.5,
                      ),
                    ),
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF10B981),
                          padding: const EdgeInsets.symmetric(vertical: 13),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        onPressed: () async {
                          Navigator.pop(dialogContext);
                          await ProfileCacheService.clearCache();
                          if (widget.onSubscriptionUpdated != null) {
                            widget.onSubscriptionUpdated!();
                          }
                          if (mounted) {
                            Navigator.pop(context, {
                              'showVipSurvey': true,
                              'amount': _selectedAmount,
                              'packageName': _selectedPackageName,
                              'packagePaymentId': null,
                            });
                          }
                        },
                        child: Text(
                          widget.isArabic
                              ? 'بدء الاستخدام الأن 🚀'
                              : 'Start Now 🚀',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 15,
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
      ),
    );
  }

  Future<void> _submitShamCashPayment() async {
    if (_isSubmitting) return;

    if (_selectedPackageId == null || _selectedManualMethodId == null) {
      AppSnackBar.show(
        context: context,
        type: NotificationType.warning,
        isArabic: widget.isArabic,
        messageAr: 'يرجى اختيار الباقة وطريقة الدفع أولاً',
        messageEn: 'Please select package and payment method first',
      );
      return;
    }

    if (_accountNameController.text.trim().isEmpty) {
      AppSnackBar.show(
        context: context,
        type: NotificationType.warning,
        isArabic: widget.isArabic,
        messageAr: 'يرجى إدخال اسم حسابك على تطبيق وصال',
        messageEn: 'Please enter your account name on Wesal app',
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final res = await ApiService.purchasePackageManual(
        packageId: _selectedPackageId!,
        amount: _selectedAmount,
        manualPaymentId: _selectedManualMethodId!,
        transactionId: _accountNameController.text.trim(),
        paymentProofFile: _proofFile,
      );

      if (!mounted) return;

      if (res['success'] == true) {
        await ProfileCacheService.clearCache();
        if (widget.onSubscriptionUpdated != null) {
          widget.onSubscriptionUpdated!();
        }
        if (!mounted) return;
        setState(() => _isSubmitting = false);
        _showShamCashSuccessDialog();
      } else {
        setState(() => _isSubmitting = false);
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: widget.isArabic,
          messageAr: res['message'] ?? 'فشل إرسال الإشعار',
          messageEn: res['message'] ?? 'Failed to submit payment proof',
        );
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: widget.isArabic,
        messageAr: 'تعذر الاتصال بالسيرفر لإتمام الدفع',
        messageEn: 'Failed to connect to server for payment',
      );
    }
  }

  Future<void> _startStripeNativePaymentSheet() async {
    if (_isSubmitting) return;

    final int? packageId = _selectedPackageId;
    if (packageId == null) {
      setState(() {
        _cardErrorMessage = widget.isArabic
            ? 'يرجى اختيار الباقة أولاً'
            : 'Please select a package first';
      });
      return;
    }

    setState(() {
      _isSubmitting = true;
      _cardErrorMessage = null;
    });

    try {
      final createResponse = await ApiService.post(
        '/stripe/create-payment-intent',
        {'package_id': packageId},
      );

      if (!mounted) return;

      final clientSecret = createResponse?['client_secret']?.toString();
      final paymentIntentId =
          createResponse?['payment_intent_id']?.toString();
      final publishableKey =
          createResponse?['publishable_key']?.toString() ?? '';

      if (createResponse == null ||
          createResponse['result'] != true ||
          clientSecret == null ||
          clientSecret.isEmpty ||
          paymentIntentId == null ||
          paymentIntentId.isEmpty) {
        setState(() {
          _isSubmitting = false;
          _cardErrorMessage = createResponse?['message']?.toString() ??
              (widget.isArabic
                  ? 'لم يتمكن السيرفر من إنشاء عملية دفع آمنة'
                  : 'The server could not create a secure payment');
        });
        return;
      }

      if (publishableKey.isNotEmpty) {
        Stripe.publishableKey = publishableKey;
      }

      await Stripe.instance.initPaymentSheet(
        paymentSheetParameters: SetupPaymentSheetParameters(
          paymentIntentClientSecret: clientSecret,
          merchantDisplayName: 'تطبيق وصال - Wesal',
          style: ThemeMode.dark,
          appearance: const PaymentSheetAppearance(
            colors: PaymentSheetAppearanceColors(
              primary: Color(0xFF0288D1),
              background: Color(0xFF0F172A),
              componentBackground: Color(0xFF1E293B),
              componentText: Colors.white,
              primaryText: Colors.white,
              secondaryText: Colors.white70,
              placeholderText: Colors.white38,
              icon: Color(0xFF38BDF8),
              error: Colors.redAccent,
            ),
            shapes: PaymentSheetShape(
              borderRadius: 16.0,
              borderWidth: 1.2,
            ),
          ),
        ),
      );

      if (!mounted) return;
      await Stripe.instance.presentPaymentSheet();
      if (!mounted) return;

      // لا نعتمد على نجاح واجهة Stripe وحدها. السيرفر يسترجع PaymentIntent
      // من Stripe ويتحقق من المستخدم والباقة والمبلغ قبل تفعيل الاشتراك.
      final confirmation = await ApiService.post(
        '/stripe/confirm-payment-intent',
        {
          'package_id': packageId,
          'payment_intent_id': paymentIntentId,
        },
      );

      if (!mounted) return;

      if (confirmation == null ||
          confirmation['result'] != true ||
          confirmation['success'] == false) {
        setState(() {
          _isSubmitting = false;
          _cardErrorMessage = widget.isArabic
              ? 'قد تكون عملية الدفع تمت، لكن لم يصل تأكيد التفعيل من السيرفر. لا تعاود الدفع الآن؛ حدّث حالة الاشتراك أو تواصل مع الدعم مع الاحتفاظ برقم العملية: $paymentIntentId'
              : 'Payment may have completed, but the server did not confirm activation. Do not pay again. Refresh your subscription or contact support with payment reference: $paymentIntentId';
        });
        return;
      }

      await ProfileCacheService.clearCache();
      if (widget.onSubscriptionUpdated != null) {
        widget.onSubscriptionUpdated!();
      }

      if (!mounted) return;
      setState(() => _isSubmitting = false);
      _showInstantVisaSuccessDialog();
    } on StripeException catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      if (e.error.code != FailureCode.Canceled) {
        setState(() {
          _cardErrorMessage = e.error.localizedMessage ??
              (widget.isArabic ? 'فشلت عملية الدفع' : 'Payment failed');
        });
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [Stripe Native Sheet Error]: $e');
      }
      if (!mounted) return;
      setState(() {
        _isSubmitting = false;
        _cardErrorMessage = widget.isArabic
            ? 'تعذر إكمال الدفع أو التحقق من حالته. لا تعاود الدفع قبل التأكد من سجل العملية.'
            : 'Payment or payment verification could not be completed. Do not retry before checking the payment status.';
      });
    }
  }

  void _handleRenewPackage() {
    if (_packages.isNotEmpty) {
      dynamic defaultPkg = _packages.firstWhere(
        (p) => (p['name'] ?? '').toString().contains(_activePackageName),
        orElse: () => _packages.first,
      );

      final int id = (defaultPkg['id'] is int)
          ? defaultPkg['id']
          : (int.tryParse(defaultPkg['id']?.toString() ?? '') ??
              (defaultPkg['package_id'] is int
                  ? defaultPkg['package_id']
                  : (int.tryParse(
                          defaultPkg['package_id']?.toString() ?? '0') ??
                      0)));

      final double price = (defaultPkg['price'] is num)
          ? (defaultPkg['price'] as num).toDouble()
          : (double.tryParse(defaultPkg['price']?.toString() ?? '0') ?? 0.0);

      final String rawName =
          defaultPkg['name']?.toString() ?? _activePackageName;
      final String name = _getTranslatedPackageName(rawName, widget.isArabic);

      setState(() {
        _selectedPackageId = id;
        _selectedAmount = price;
        _selectedPackageName = name;
        _showPaymentStep = true;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final double bottomInset = MediaQuery.viewInsetsOf(context).bottom;
    final double screenHeight = MediaQuery.sizeOf(context).height;

    return Directionality(
      textDirection: widget.isArabic ? TextDirection.rtl : TextDirection.ltr,
      child: GestureDetector(
        onTap: () => FocusScope.of(context).unfocus(),
        child: Padding(
          padding: EdgeInsets.only(bottom: bottomInset),
          child: Container(
            height: screenHeight * 0.88,
            decoration: BoxDecoration(
              color: const Color(0xFF0F172A).withValues(alpha: 0.96),
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(32)),
              border: Border.all(
                  color: const Color(0xFF38BDF8).withValues(alpha: 0.4)),
            ),
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                Container(
                  width: 42,
                  height: 4.5,
                  decoration: BoxDecoration(
                    color: Colors.white30,
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    if (_showPaymentStep)
                      IconButton(
                        icon: Icon(
                            widget.isArabic
                                ? Icons.arrow_forward_ios_rounded
                                : Icons.arrow_back_ios_rounded,
                            color: Colors.white,
                            size: 20),
                        onPressed: _isSubmitting
                            ? null
                            : () {
                                setState(() {
                                  _showPaymentStep = false;
                                  _cardErrorMessage = null;
                                });
                              },
                      ),
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.amber.withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.workspace_premium_rounded,
                          color: Colors.amber, size: 24),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      _showPaymentStep
                          ? (widget.isArabic
                              ? 'وسائل الدفع المتاحة'
                              : 'Payment Options')
                          : (widget.isArabic
                              ? 'باقات الاشتراك المميزة'
                              : 'Subscription Packages'),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Expanded(
                  child: _isLoading
                      ? const Center(
                          child: CircularProgressIndicator(color: Colors.white))
                      : _isFreeFemale
                          ? _buildFemaleFreeCard()
                          : _showPaymentStep
                              ? _buildPaymentMethodsStepView()
                              : _buildMalePackagesView(),
                ),
                if (_selectedPackageId != null &&
                    !_showPaymentStep &&
                    !_isFreeFemale &&
                    !_isLoading)
                  Padding(
                    padding: const EdgeInsets.only(top: 12.0),
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0288D1),
                        padding: const EdgeInsets.symmetric(vertical: 15),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(18)),
                        elevation: 8,
                        shadowColor:
                            const Color(0xFF0288D1).withValues(alpha: 0.5),
                      ),
                      onPressed: () {
                        setState(() {
                          _showPaymentStep = true;
                        });
                      },
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.flash_on_rounded,
                              color: Colors.amber, size: 22),
                          const SizedBox(width: 8),
                          Text(
                            widget.isArabic
                                ? 'الدفع الآن - $_currencySymbol$selectedAmountClean'
                                : 'Pay Now - $_currencySymbol$selectedAmountClean',
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo'),
                          ),
                          const SizedBox(width: 8),
                          Icon(
                            widget.isArabic
                                ? Icons.arrow_back_rounded
                                : Icons.arrow_forward_rounded,
                            color: Colors.white,
                            size: 20,
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String get selectedAmountClean {
    if (_selectedAmount % 1 == 0) {
      return _selectedAmount.toInt().toString();
    }
    return _selectedAmount.toStringAsFixed(2);
  }

  Widget _buildFemaleFreeCard() {
    return Center(
      child: Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
              colors: [Color(0xFFC2185B), Color(0xFFF48FB1)]),
          borderRadius: BorderRadius.circular(28),
          boxShadow: [
            BoxShadow(
                color: const Color(0xFFC2185B).withValues(alpha: 0.3),
                blurRadius: 20,
                offset: const Offset(0, 8))
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.stars_rounded, color: Colors.white, size: 64),
            const SizedBox(height: 16),
            Text(
              widget.isArabic
                  ? 'حسابكِ مفعل مجاناً بالكامل!'
                  : 'Your account is completely FREE!',
              textAlign: TextAlign.center,
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Cairo'),
            ),
            const SizedBox(height: 10),
            Text(
              widget.isArabic
                  ? 'تقديراً منا لدور المرأة، يمكنكِ المراسلة واستخدام كافة الميزات مجاناً ودائماً وبدون أي رسوم.'
                  : 'You can message and use all features for free forever.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                  color: Color(0xE6FFFFFF),
                  fontSize: 13,
                  fontFamily: 'Cairo',
                  height: 1.4),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActiveSubscriptionCard() {
    final bool isExpiringSoon = _remainingDays <= 3;
    final double progressRatio =
        (_remainingDays / _totalValidityDays).clamp(0.0, 1.0);

    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isExpiringSoon
              ? [
                  const Color(0xFFD32F2F).withValues(alpha: 0.25),
                  const Color(0xFFF57C00).withValues(alpha: 0.18)
                ]
              : [
                  const Color(0xFF00897B).withValues(alpha: 0.28),
                  const Color(0xFF0288D1).withValues(alpha: 0.18)
                ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: isExpiringSoon
              ? const Color(0xFFFF7043)
              : const Color(0xFF26A69A),
          width: 1.5,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: isExpiringSoon
                      ? const Color(0xFFE65100)
                      : const Color(0xFF00695C),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 7,
                      height: 7,
                      decoration: const BoxDecoration(
                          color: Colors.greenAccent, shape: BoxShape.circle),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      widget.isArabic
                          ? 'اشتراكك الحالي نشط 🟢'
                          : 'Active Subscription 🟢',
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo'),
                    ),
                  ],
                ),
              ),
              const Spacer(),
              if (isExpiringSoon)
                const Icon(Icons.warning_amber_rounded,
                    color: Color(0xFFFFB74D), size: 20),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _getTranslatedPackageName(
                        _activePackageName, widget.isArabic),
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo'),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    widget.isArabic
                        ? 'ينتهي بتاريخ: $_expiryDateStr'
                        : 'Expires on: $_expiryDateStr',
                    style: const TextStyle(
                        color: Colors.white60,
                        fontSize: 11.5,
                        fontFamily: 'Cairo'),
                  ),
                ],
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.35),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: isExpiringSoon
                        ? Colors.redAccent.withValues(alpha: 0.6)
                        : const Color(0xFF4FC3F7).withValues(alpha: 0.5),
                  ),
                ),
                child: Column(
                  children: [
                    Text(
                      '$_remainingDays',
                      style: TextStyle(
                        color: isExpiringSoon
                            ? const Color(0xFFFF7043)
                            : const Color(0xFF81D4FA),
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                        height: 1.0,
                      ),
                    ),
                    Text(
                      widget.isArabic ? 'أيام متبقية' : 'Days Left',
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 10,
                        fontFamily: 'Cairo',
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: progressRatio,
              minHeight: 6,
              backgroundColor: Colors.white12,
              valueColor: AlwaysStoppedAnimation<Color>(
                isExpiringSoon
                    ? const Color(0xFFFF5252)
                    : const Color(0xFF26A69A),
              ),
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              if (_remainingViewersQuota > 0)
                Expanded(
                  child: Row(
                    children: [
                      const Icon(Icons.visibility_rounded,
                          color: Color(0xFF81D4FA), size: 16),
                      const SizedBox(width: 6),
                      Text(
                        widget.isArabic
                            ? '$_remainingViewersQuota مشاهدات زوار'
                            : '$_remainingViewersQuota profile visitors',
                        style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 11.5,
                            fontFamily: 'Cairo'),
                      ),
                    ],
                  ),
                ),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: isExpiringSoon
                      ? const Color(0xFFE65100)
                      : const Color(0xFF0288D1),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                  elevation: 4,
                ),
                icon: const Icon(Icons.autorenew_rounded,
                    color: Colors.white, size: 18),
                label: Text(
                  widget.isArabic ? 'تجديد الاشتراك 🔄' : 'Renew Subscription',
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12.5,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo'),
                ),
                onPressed: _isSubmitting ? null : _handleRenewPackage,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMalePackagesView() {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_hasActiveSubscription) _buildActiveSubscriptionCard(),
          Text(
              widget.isArabic
                  ? 'انقر على الباقة المناسبة لاختيارها أو التجديد:'
                  : 'Tap to select or renew a subscription package:',
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 13,
                  fontFamily: 'Cairo',
                  fontWeight: FontWeight.bold)),
          const SizedBox(height: 14),
          ..._packages.map((pkg) {
            final int id = (pkg['id'] is int)
                ? pkg['id']
                : (int.tryParse(pkg['id']?.toString() ?? '') ??
                    (pkg['package_id'] is int
                        ? pkg['package_id']
                        : (int.tryParse(pkg['package_id']?.toString() ?? '0') ??
                            0)));

            final String rawPkgName = pkg['name']?.toString() ??
                (widget.isArabic ? 'باقة' : 'Package');
            final String name =
                _getTranslatedPackageName(rawPkgName, widget.isArabic);

            final double price = (pkg['price'] is num)
                ? (pkg['price'] as num).toDouble()
                : (double.tryParse(pkg['price']?.toString() ?? '0') ?? 0.0);

            final int validity = (pkg['validity'] is int)
                ? pkg['validity']
                : (int.tryParse(pkg['validity']?.toString() ?? '30') ?? 30);

            final int viewersView = (pkg['profile_viewers_view'] is int)
                ? pkg['profile_viewers_view']
                : (int.tryParse(
                        pkg['profile_viewers_view']?.toString() ?? '0') ??
                    0);

            final bool isSelected = _selectedPackageId == id;

            Color themeColor;
            List<Color> gradientColors;
            String badgeTag;
            IconData badgeIcon;

            if (validity >= 90 ||
                rawPkgName.contains('3') ||
                rawPkgName.contains('VIP') ||
                rawPkgName.contains('ملكي')) {
              themeColor = const Color(0xFFAB47BC);
              gradientColors = [
                const Color(0xFF8E24AA),
                const Color(0xFF4A148C)
              ];
              badgeTag =
                  widget.isArabic ? 'باقة VIP الملكية 👑' : 'Royal VIP Package';
              badgeIcon = Icons.workspace_premium_rounded;
            } else if (validity >= 30 || rawPkgName.contains('شهري')) {
              themeColor = Colors.amber;
              gradientColors = [
                const Color(0xFFFFB300),
                const Color(0xFFF57C00)
              ];
              badgeTag = widget.isArabic ? 'الباقة الذهبية 🥇' : 'Gold Package';
              badgeIcon = Icons.stars_rounded;
            } else {
              themeColor = const Color(0xFF38BDF8);
              gradientColors = [
                const Color(0xFF0288D1),
                const Color(0xFF005691)
              ];
              badgeTag =
                  widget.isArabic ? 'الباقة الفضية 🥈' : 'Silver Package';
              badgeIcon = Icons.verified_rounded;
            }

            return GestureDetector(
              onTap: () {
                setState(() {
                  _selectedPackageId = id;
                  _selectedAmount = price;
                  _selectedPackageName = name;
                });
              },
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                margin: const EdgeInsets.only(bottom: 14),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: isSelected
                      ? themeColor.withValues(alpha: 0.18)
                      : Colors.white.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(22),
                  border: Border.all(
                    color: isSelected ? themeColor : Colors.white12,
                    width: isSelected ? 2.2 : 1,
                  ),
                  boxShadow: isSelected
                      ? [
                          BoxShadow(
                              color: themeColor.withValues(alpha: 0.28),
                              blurRadius: 14,
                              offset: const Offset(0, 4))
                        ]
                      : [],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(colors: gradientColors),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            children: [
                              Icon(badgeIcon, color: Colors.white, size: 14),
                              const SizedBox(width: 6),
                              Text(
                                badgeTag,
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo'),
                              ),
                            ],
                          ),
                        ),
                        const Spacer(),
                        Text(
                          '$_currencySymbol$price',
                          style: TextStyle(
                              color: themeColor,
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Icon(
                            isSelected
                                ? Icons.check_circle_rounded
                                : Icons.radio_button_off_rounded,
                            color: isSelected
                                ? themeColor
                                : const Color.fromARGB(236, 255, 250, 250),
                            size: 22),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(name,
                                  style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                      fontFamily: 'Cairo')),
                              Text(
                                widget.isArabic
                                    ? 'صلاحية $validity يوم من تاريخ التفعيل'
                                    : 'Valid for $validity days',
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 11.5,
                                    fontFamily: 'Cairo'),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const Divider(
                        color: Color.fromARGB(85, 255, 254, 254), height: 20),
                    if ((int.tryParse(pkg['message_profile_quota']?.toString() ?? '0') ?? 0) != 0)
                      _buildPackageFeatureRow(
                        (int.tryParse(pkg['message_profile_quota']?.toString() ?? '0') ?? 0) == -1
                            ? (widget.isArabic ? 'مراسلة غير محدودة' : 'Unlimited messaging')
                            : (widget.isArabic
                                ? 'مراسلة حتى ${pkg['message_profile_quota']} أعضاء'
                                : 'Message up to ${pkg['message_profile_quota']} members'),
                      ),
                    if ((int.tryParse(pkg['express_interest']?.toString() ?? '0') ?? 0) != 0)
                      _buildPackageFeatureRow(
                        (int.tryParse(pkg['express_interest']?.toString() ?? '0') ?? 0) == -1
                            ? (widget.isArabic ? 'إبداء اهتمام غير محدود' : 'Unlimited interests')
                            : (widget.isArabic
                                ? '${pkg['express_interest']} طلبات اهتمام'
                                : '${pkg['express_interest']} interest requests'),
                      ),
                    if ((int.tryParse(pkg['photo_gallery']?.toString() ?? '0') ?? 0) != 0)
                      _buildPackageFeatureRow(
                        (int.tryParse(pkg['photo_gallery']?.toString() ?? '0') ?? 0) == -1
                            ? (widget.isArabic ? 'رفع صور للمعرض دون حد' : 'Unlimited gallery uploads')
                            : (widget.isArabic
                                ? 'رفع ${pkg['photo_gallery']} صور إلى المعرض'
                                : 'Upload ${pkg['photo_gallery']} gallery photos'),
                      ),
                    if ((int.tryParse(pkg['contact']?.toString() ?? '0') ?? 0) != 0)
                      _buildPackageFeatureRow(
                        (int.tryParse(pkg['contact']?.toString() ?? '0') ?? 0) == -1
                            ? (widget.isArabic ? 'عرض بيانات الاتصال دون حد' : 'Unlimited contact views')
                            : (widget.isArabic
                                ? 'عرض ${pkg['contact']} بيانات اتصال'
                                : 'View ${pkg['contact']} contact details'),
                      ),
                    if (viewersView != 0)
                      _buildPackageFeatureRow(
                        viewersView == -1
                            ? (widget.isArabic ? 'رؤية زوار الملف دون حد' : 'Unlimited profile visitors')
                            : (widget.isArabic
                                ? 'إمكانية رؤية حتى $viewersView زائر لملفك'
                                : 'View up to $viewersView profile visitors'),
                      ),
                    if ((int.tryParse(pkg['profile_image_view']?.toString() ?? '0') ?? 0) != 0)
                      _buildPackageFeatureRow(
                        (int.tryParse(pkg['profile_image_view']?.toString() ?? '0') ?? 0) == -1
                            ? (widget.isArabic ? 'عرض الصور الشخصية الخاصة دون حد' : 'Unlimited private profile photos')
                            : (widget.isArabic
                                ? 'عرض ${pkg['profile_image_view']} صور شخصية خاصة'
                                : 'View ${pkg['profile_image_view']} private profile photos'),
                      ),
                    if ((int.tryParse(pkg['gallery_image_view']?.toString() ?? '0') ?? 0) != 0)
                      _buildPackageFeatureRow(
                        (int.tryParse(pkg['gallery_image_view']?.toString() ?? '0') ?? 0) == -1
                            ? (widget.isArabic ? 'عرض صور المعرض الخاصة دون حد' : 'Unlimited private gallery photos')
                            : (widget.isArabic
                                ? 'عرض ${pkg['gallery_image_view']} صور معرض خاصة'
                                : 'View ${pkg['gallery_image_view']} private gallery photos'),
                      ),
                    if (pkg['auto_profile_match'] == true ||
                        pkg['auto_profile_match'] == 1 ||
                        pkg['auto_profile_match']?.toString() == '1')
                      _buildPackageFeatureRow(
                        widget.isArabic
                            ? 'مطابقة تلقائية ذكية مع الشريك'
                            : 'Advanced auto-profile matching',
                      ),
                  ],
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildPaymentMethodsStepView() {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                  color: const Color(0xFF38BDF8).withValues(alpha: 0.3)),
            ),
            child: Row(
              children: [
                const Icon(Icons.check_circle,
                    color: Color(0xFF38BDF8), size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    widget.isArabic
                        ? 'الباقة المختارة للتجديد/الدفع: $_selectedPackageName ($_currencySymbol$selectedAmountClean)'
                        : 'Selected: $_selectedPackageName ($_currencySymbol$selectedAmountClean)',
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13.5,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo'),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          Text(
              widget.isArabic
                  ? 'اختر طريقة الدفع المناسبة لك:'
                  : 'Select payment method:',
              style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 13,
                  fontFamily: 'Cairo',
                  fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: _isSubmitting
                      ? null
                      : () => setState(() {
                            _selectedPaymentCategory = 2;
                            _cardErrorMessage = null;
                          }),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    decoration: BoxDecoration(
                      color: _selectedPaymentCategory == 2
                          ? const Color(0xFF0288D1).withValues(alpha: 0.2)
                          : Colors.white.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: _selectedPaymentCategory == 2
                            ? const Color(0xFF38BDF8)
                            : Colors.white12,
                      ),
                    ),
                    child: Column(
                      children: [
                        Icon(Icons.credit_card_rounded,
                            color: _selectedPaymentCategory == 2
                                ? const Color(0xFF38BDF8)
                                : Colors.white54,
                            size: 24),
                        const SizedBox(height: 4),
                        Text(
                          widget.isArabic
                              ? 'فيزا / بطاقة ائتمان'
                              : 'Visa / Credit Card',
                          style: TextStyle(
                              color: _selectedPaymentCategory == 2
                                  ? const Color(0xFF38BDF8)
                                  : Colors.white70,
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: GestureDetector(
                  onTap: _isSubmitting
                      ? null
                      : () => setState(() {
                            _selectedPaymentCategory = 1;
                            _cardErrorMessage = null;
                          }),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    decoration: BoxDecoration(
                      color: _selectedPaymentCategory == 1
                          ? Colors.amber.withValues(alpha: 0.2)
                          : Colors.white.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: _selectedPaymentCategory == 1
                            ? Colors.amber
                            : Colors.white12,
                      ),
                    ),
                    child: Column(
                      children: [
                        Icon(Icons.account_balance_wallet_rounded,
                            color: _selectedPaymentCategory == 1
                                ? Colors.amber
                                : Colors.white54,
                            size: 24),
                        const SizedBox(height: 4),
                        Text(
                          widget.isArabic ? 'شام كاش' : 'Sham Cash',
                          style: TextStyle(
                              color: _selectedPaymentCategory == 1
                                  ? Colors.amber
                                  : Colors.white70,
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          if (_selectedPaymentCategory == 2) ...[
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(22),
                border: Border.all(
                    color: const Color(0xFF38BDF8).withValues(alpha: 0.35)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color:
                              const Color(0xFF38BDF8).withValues(alpha: 0.18),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.shield_outlined,
                            color: Color(0xFF38BDF8), size: 26),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.isArabic
                                  ? 'الدفع الإلكتروني المباشر عبر Stripe'
                                  : 'Secure Stripe Native Payment',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo'),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              widget.isArabic
                                  ? 'ستفتح نافذة أصلية سلسة ومباشرة من أسفل الشاشة لإدخال بيانات بطاقتك وتأكيد الدفع.'
                                  : 'A native bottom sheet will pop up directly in-app.',
                              style: const TextStyle(
                                  color: Colors.white60,
                                  fontSize: 11.5,
                                  fontFamily: 'Cairo',
                                  height: 1.3),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  if (_cardErrorMessage != null) ...[
                    Container(
                      margin: const EdgeInsets.only(bottom: 14),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.redAccent.withValues(alpha: 0.18),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.redAccent, width: 1.2),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline_rounded,
                              color: Colors.redAccent, size: 22),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _cardErrorMessage!,
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0288D1),
                        padding: const EdgeInsets.symmetric(vertical: 15),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16)),
                        elevation: 6,
                      ),
                      icon: _isSubmitting
                          ? const SizedBox.shrink()
                          : const Icon(Icons.payment_rounded,
                              color: Colors.white, size: 20),
                      label: _isSubmitting
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(
                                  color: Colors.white, strokeWidth: 2),
                            )
                          : Text(
                              widget.isArabic
                                  ? 'الانتقال للدفع السريع ($_currencySymbol$selectedAmountClean)'
                                  : 'Proceed to Payment ($_currencySymbol$selectedAmountClean)',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo'),
                            ),
                      onPressed:
                          _isSubmitting ? null : _startStripeNativePaymentSheet,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.verified_user_rounded,
                          color: Color(0xFF10B981), size: 14),
                      const SizedBox(width: 6),
                      Text(
                        widget.isArabic
                            ? 'حماية أصلية 256-bit SSL بدون مغادرة التطبيق'
                            : '256-bit SSL In-App Payment Protection',
                        style: const TextStyle(
                            color: Colors.white54,
                            fontSize: 11,
                            fontFamily: 'Cairo'),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
          if (_selectedPaymentCategory == 1) ...[
            Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: Colors.amber.withValues(alpha: 0.4)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.payments_rounded,
                          color: Colors.amber, size: 24),
                      const SizedBox(width: 10),
                      Text(
                        widget.isArabic
                            ? 'الدفع عبر شام كاش'
                            : 'Sham Cash Payment',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontFamily: 'Cairo',
                            fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    widget.isArabic
                        ? 'يرجى إرسال قيمة الباقة تحويلاً عبر شام كاش إلى الكود المخصص أدناه وارفاق صورة اشعار التحويل في الحقل المخصص:'
                        : 'Please transfer the package amount via Sham Cash to the code below:',
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontFamily: 'Cairo',
                        fontWeight: FontWeight.w600,
                        height: 1.45),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.45),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                          color: Colors.amber.withValues(alpha: 0.5)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.vpn_key_rounded,
                            color: Colors.amber, size: 18),
                        const SizedBox(width: 8),
                        const Expanded(
                          child: SelectableText(
                            _officialShamCashCode,
                            style: TextStyle(
                                color: Colors.amber,
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 0.8),
                          ),
                        ),
                        InkWell(
                          onTap: () {
                            Clipboard.setData(const ClipboardData(
                                text: _officialShamCashCode));
                            AppSnackBar.show(
                              context: context,
                              type: NotificationType.success,
                              isArabic: widget.isArabic,
                              messageAr: 'تم نسخ كود شام كاش بنجاح',
                              messageEn: 'Sham Cash Code copied',
                            );
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: Colors.amber,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              widget.isArabic ? 'نسخ الكود' : 'Copy',
                              style: const TextStyle(
                                  color: Colors.black,
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo'),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _accountNameController,
              enabled: !_isSubmitting,
              style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
              decoration: InputDecoration(
                hintText: widget.isArabic
                    ? 'اسم حسابك على تطبيق وصال...'
                    : 'Your account name on Wesal app...',
                hintStyle: const TextStyle(
                    color: Colors.white54, fontFamily: 'Cairo', fontSize: 13.5),
                filled: true,
                fillColor: Colors.white.withValues(alpha: 0.08),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide:
                        BorderSide(color: Colors.white.withValues(alpha: 0.2))),
                focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: const BorderSide(color: Color(0xFF38BDF8))),
              ),
            ),
            const SizedBox(height: 12),
            GestureDetector(
              onTap: _isSubmitting ? null : _pickProofImage,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.white24),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.cloud_upload_rounded,
                        color: Color(0xFF38BDF8)),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        _proofFile != null
                            ? _proofFile!.path.split('/').last
                            : (widget.isArabic
                                ? 'إرفاق صورة إشعار التحويل (اختياري)'
                                : 'Upload payment receipt (Optional)'),
                        style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 12,
                            fontFamily: 'Cairo'),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0288D1),
                  padding: const EdgeInsets.symmetric(vertical: 15),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16)),
                ),
                onPressed: _isSubmitting ? null : _submitShamCashPayment,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                            color: Colors.white, strokeWidth: 2))
                    : Text(
                        widget.isArabic
                            ? 'تأكيد وإرسال الإشعار'
                            : 'Confirm Sham Cash Payment',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo')),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildPackageFeatureRow(String featureText) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          const Icon(Icons.check_circle_outline_rounded,
              color: Color(0xFF34D399), size: 16),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              featureText,
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontFamily: 'Cairo',
                  fontWeight: FontWeight.w600,
                  height: 1.3),
            ),
          ),
        ],
      ),
    );
  }
}

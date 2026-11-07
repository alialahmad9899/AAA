// ============================================================================
// اسم الملف: lib/screens/member_profile_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة عرض بروفايل العضو مع دعم الشارات الموحدة للباقات
//        وإمكانية مشاركة رابط بطاقة العضو عبر Deep Linking.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import '../constants/colors.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:share_plus/share_plus.dart';

import '../models/chat_model.dart';
import '../models/member_profile_model.dart';
import '../services/api_service.dart';
import '../services/profile_cache_service.dart';
import '../widgets/app_snackbar.dart';
import '../widgets/bottom_system_inset.dart';
import '../widgets/package_badge_widget.dart';
import 'chat_detail_screen.dart';
import 'home/tabs/account_tab.dart';

class MemberProfileScreen extends StatefulWidget {
  final int userId;
  final String currentLanguage;
  final VoidCallback? onUpgradeRequested;

  const MemberProfileScreen({
    super.key,
    required this.userId,
    this.currentLanguage = 'ar',
    this.onUpgradeRequested,
  });

  @override
  State<MemberProfileScreen> createState() => _MemberProfileScreenState();
}

class _MemberProfileScreenState extends State<MemberProfileScreen> {
  MemberProfileModel? _profile;
  bool _isLoading = true;
  bool _hasError = false;
  bool _isInterestSent = false;

  bool _isSendingInterest = false;
  bool _currentUserIsSubscribedOrFemale = false;
  bool _currentUserPermissionsReady = false;

  bool _isModalActive = false;

  bool get _isArabic => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _fetchProfileData();
  }

  /// دالة توليد ومشاركة رابط بطاقة العضو بالنطاق الرسمي
  void _shareProfileLink() {
    if (_profile == null) return;

    final String memberName = _profile!.basicInfo.fullName;
    final String profileUrl =
        'https://www.wesalsyria.com/profile?id=${widget.userId}';

    final String shareMessage = _isArabic
        ? 'شاهد ملف العضو ($memberName) على تطبيق وصال :\n$profileUrl'
        : 'Check out ($memberName)\'s profile on Wesal App:\n$profileUrl';

    Share.share(shareMessage, subject: memberName);
  }

  Future<void> _fetchProfileData() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _hasError = false;
      _currentUserPermissionsReady = false;
    });

    try {
      // Fetch permissions in parallel, but do not block rendering the public profile.
      final currentUserFuture =
          ApiService.getCurrentUserProfile(lang: widget.currentLanguage);
      final data = await ApiService.getPublicProfile(
        widget.userId,
        lang: widget.currentLanguage,
      );

      if (!mounted) return;
      if (data == null) {
        setState(() {
          _hasError = true;
          _isLoading = false;
        });
        return;
      }

      // Show the requested profile as soon as its own response is ready.
      setState(() {
        _profile = data;
        _isInterestSent = data.isInterested || data.isInterestSent;
        _isLoading = false;
      });

      // Permission-dependent controls update when the secondary request completes.
      try {
        final currentUser = await currentUserFuture;
        if (mounted) {
          setState(() {
            if (currentUser != null) {
              _currentUserIsSubscribedOrFemale =
                  currentUser.isFemale || currentUser.isSubscribedActive;
            }
            _currentUserPermissionsReady = true;
          });
        }
      } catch (e) {
        debugPrint('تعذر تحميل صلاحيات المستخدم الحالي: $e');
        if (mounted) {
          setState(() => _currentUserPermissionsReady = true);
        }
      }
    } catch (error, stackTrace) {
      debugPrint(
        '[MemberProfileScreen] Failed to load profile user=${widget.userId}: $error',
      );
      debugPrintStack(stackTrace: stackTrace);

      if (mounted) {
        setState(() {
          _hasError = _profile == null;
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _openSubscriptionAndRefresh() async {
    if (_isModalActive || !mounted) return;
    _isModalActive = true;

    try {
      await AccountTab.showSubscriptionsModalDirectly(
        context,
        isArabic: _isArabic,
        onSubscriptionUpdated: () async {
          await ProfileCacheService.clearCache();
          if (mounted) {
            await _fetchProfileData();
          }
        },
      );
    } finally {
      _isModalActive = false;
    }
  }

  void _showLikesQuotaModal([String? customMessage]) async {
    if (_isModalActive || !mounted) return;
    _isModalActive = true;

    try {
      await AccountTab.showQuotaExceededModal(
        context,
        isArabic: _isArabic,
        onSubscriptionUpdated: () async {
          await ProfileCacheService.clearCache();
          if (mounted) {
            await _fetchProfileData();
          }
        },
      );
    } finally {
      _isModalActive = false;
    }
  }

  void _showMessageFeatureModal() async {
    if (_isModalActive || !mounted) return;
    _isModalActive = true;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (modalContext) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          padding: EdgeInsets.fromLTRB(
            24, 24, 24, 24 + WesalBottomInsets.of(modalContext),
          ),
          decoration: BoxDecoration(
            color: AppColors.cardColor.withValues(alpha: 0.96),
            borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
            border: Border.all(
              color: AppColors.warning.withValues(alpha: 0.5),
              width: 1.5,
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.lineColor,
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              const SizedBox(height: 20),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.warning.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                  border:
                      Border.all(color: AppColors.warning, width: 1.5),
                ),
                child: const Icon(Icons.mark_chat_unread_rounded,
                    color: AppColors.warning, size: 48),
              ),
              const SizedBox(height: 16),
              Text(
                _isArabic
                    ? 'المراسلة المباشرة ميزة مدفوعة 💬'
                    : 'Direct Messaging is Premium 💬',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: AppColors.textDark,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Cairo',
                ),
              ),
              const SizedBox(height: 10),
              Text(
                _isArabic
                    ? 'لتتمكن من فتح المحادثة الفورية والدردشة المباشرة مع هذا العضو، يرجى الاشتراك في إحدى باقات وصال المميزة.'
                    : 'To unlock direct chat and message this member, please subscribe to one of Wesal\'s premium packages.',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: AppColors.textMuted,
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
                    backgroundColor: AppColors.primaryTeal,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16)),
                    elevation: 6,
                  ),
                  icon: const Icon(Icons.workspace_premium_rounded,
                      color: AppColors.warning, size: 22),
                  label: Text(
                    _isArabic
                        ? 'فتح المراسلة والاشتراك الآن 🚀'
                        : 'Unlock Messaging & Subscribe 🚀',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  onPressed: () {
                    Navigator.of(modalContext).pop();
                    if (widget.onUpgradeRequested != null) {
                      widget.onUpgradeRequested!();
                    } else {
                      _openSubscriptionAndRefresh();
                    }
                  },
                ),
              ),
              const SizedBox(height: 10),
            ],
          ),
        ),
      ),
    );

    _isModalActive = false;
  }

  Future<void> _sendExpressInterest() async {
    if (_isInterestSent || _isSendingInterest) return;

    final bool previousState = _isInterestSent;

    setState(() {
      _isInterestSent = true;
      _isSendingInterest = true;
    });

    try {
      final result = await ApiService.expressInterest(widget.userId);

      if (!mounted) return;

      final bool success = result['success'] == true;
      final String status = result['status']?.toString() ?? '';
      final String serverMessage = result['message']?.toString() ?? '';

      final String localizedServerMsg = AttributeTranslator.translate(
        serverMessage,
        isArabic: _isArabic,
      );

      if (success || status == 'success') {
        AppSnackBar.show(
          context: context,
          type: NotificationType.success,
          isArabic: _isArabic,
          messageAr: localizedServerMsg.isNotEmpty
              ? localizedServerMsg
              : 'تم إرسال طلب الاهتمام بنجاح!',
          messageEn: localizedServerMsg.isNotEmpty
              ? localizedServerMsg
              : 'Express interest request sent successfully!',
        );
      } else if (status == 'already_expressed') {
        // حالة تم إرسال الاهتمام مسبقاً
      } else {
        setState(() {
          _isInterestSent = previousState;
        });

        final int code = result['code'] is int
            ? result['code']
            : int.tryParse(result['code']?.toString() ?? '0') ?? 0;

        if (status == 'package_required' ||
            status == 'subscription_expired' ||
            status == 'forbidden' ||
            status == 'quota_exceeded' ||
            code == 403) {
          _showLikesQuotaModal(
              localizedServerMsg.isNotEmpty ? localizedServerMsg : null);
        } else {
          AppSnackBar.show(
            context: context,
            type: NotificationType.error,
            isArabic: _isArabic,
            messageAr: localizedServerMsg.isNotEmpty
                ? localizedServerMsg
                : (_isArabic
                    ? 'تعذر إرسال طلب الاهتمام، يرجى المحاولة لاحقاً'
                    : 'Failed to express interest, please try again'),
            messageEn: localizedServerMsg.isNotEmpty
                ? localizedServerMsg
                : 'Failed to express interest, please try again',
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isInterestSent = previousState;
        });
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isArabic,
          messageAr: 'حدث خطأ أثناء الاتصال بالسيرفر، يرجى المحاولة لاحقاً',
          messageEn: 'Network error occurred, please try again later',
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSendingInterest = false;
        });
      }
    }
  }

  void _startChat() {
    if (_profile == null) return;
    // The profile is rendered before this secondary lookup completes.
    // Avoid treating the temporary default as a final subscription decision.
    if (!_currentUserPermissionsReady) return;

    if (!_currentUserIsSubscribedOrFemale) {
      _showMessageFeatureModal();
      return;
    }

    if (!_profile!.canChat) {
      AppSnackBar.show(
        context: context,
        type: NotificationType.warning,
        isArabic: _isArabic,
        messageAr: 'عذراً، هذا العضو لا يستقبل الرسائل المباشرة حالياً.',
        messageEn:
            'Sorry, this member is not accepting direct messages currently.',
      );
      return;
    }

    final thread = ChatThreadModel(
      id: _profile!.userId,
      userId: _profile!.userId,
      name: _profile!.basicInfo.fullName,
      image: _profile!.basicInfo.photo,
      gender: _profile!.basicInfo.gender,
      lastMessage: '',
      time: _isArabic ? 'الآن' : 'Now',
      unreadCount: 0,
      isOnline: false,
      lastSeen: '',
      packageName: _profile!.basicInfo.packageName,
    );

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ChatDetailScreen(
          thread: thread,
          currentLanguage: widget.currentLanguage,
        ),
      ),
    );
  }

  String _normalizeText(String input) {
    return input
        .toLowerCase()
        .replaceAll(RegExp(r'[أإآآ]'), 'ا')
        .replaceAll('ة', 'ه')
        .replaceAll('ى', 'ي')
        .trim();
  }

  Color _getVisualColor(String text) {
    final t = _normalizeText(text);

    if (t.contains('ابيض') ||
        t.contains('white') ||
        t.contains('fair') ||
        t.contains('بيضاء')) {
      return const Color(0xFFFFFFFF);
    }
    if (t.contains('ازرق') || t.contains('blue')) {
      return const Color(0xFF2563EB);
    }
    if (t.contains('اخضر') || t.contains('green')) {
      return const Color(0xFF16A34A);
    }
    if (t.contains('عسلي') || t.contains('hazel')) {
      return const Color(0xFFB45309);
    }
    if (t.contains('بني') || t.contains('brown')) {
      return const Color(0xFF78350F);
    }
    if (t.contains('اسود') || t.contains('black')) {
      return const Color(0xFF18181B);
    }
    if (t.contains('اشقر') || t.contains('blonde') || t.contains('blond')) {
      return const Color(0xFFEAB308);
    }
    if (t.contains('احمر') || t.contains('red')) return const Color(0xFFDC2626);
    if (t.contains('رمادي') || t.contains('grey') || t.contains('gray')) {
      return const Color(0xFF9CA3AF);
    }
    if (t.contains('حنطي') || t.contains('قمحي') || t.contains('wheat')) {
      return const Color(0xFFFDBA74);
    }
    if (t.contains('اسمر') || t.contains('tan') || t.contains('olive')) {
      return const Color(0xFFD97706);
    }
    if (t.contains('داكن') || t.contains('dark')) {
      return const Color(0xFF522504);
    }

    return const Color(0xFF38BDF8);
  }

  void _showReportDialog() async {
    if (_isModalActive || !mounted) return;
    _isModalActive = true;

    final List<Map<String, String>> reportReasons = [
      {
        'ar': 'صورة غير لائقة أو مسيئة',
        'en': 'Inappropriate or offensive photo',
        'type': 'photo',
      },
      {
        'ar': 'حساب مزيف / انتحال شخصية',
        'en': 'Fake profile / Impersonation',
        'type': 'person',
      },
      {
        'ar': 'معلومات مضللة أو وصف غير لائق',
        'en': 'Misleading info or inappropriate bio',
        'type': 'bio',
      },
      {
        'ar': 'سلوك مشبوه أو محاولة احتيال',
        'en': 'Suspicious behavior or fraud attempt',
        'type': 'warning',
      },
      {
        'ar': 'سبب آخر',
        'en': 'Other reason',
        'type': 'other',
      },
    ];

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
            child: Container(
              padding: EdgeInsets.fromLTRB(
                22, 22, 22, 22 + WesalBottomInsets.of(context),
              ),
              decoration: BoxDecoration(
                color: AppColors.cardColor.withValues(alpha: 0.95),
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(32)),
                border: Border.all(color: AppColors.warning.withValues(alpha: 0.35)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 42,
                      height: 4.5,
                      decoration: BoxDecoration(
                        color: AppColors.lineColor,
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: AppColors.warning.withValues(alpha: 0.15),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.report_problem_rounded,
                            color: AppColors.warning, size: 24),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _isArabic
                                  ? 'الإبلاغ عن هذا البروفايل'
                                  : 'Report this profile',
                              style: const TextStyle(
                                color: AppColors.textDark,
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                              ),
                            ),
                            Text(
                              _isArabic
                                  ? 'اختر سبب الإبلاغ لمراجعته من قبل الإدارة'
                                  : 'Select reason for admin review',
                              style: const TextStyle(
                                color: AppColors.textMuted,
                                fontSize: 12,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  ...reportReasons.map((item) {
                    final String reasonTitle =
                        _isArabic ? item['ar']! : item['en']!;
                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      decoration: BoxDecoration(
                        color: AppColors.sectionBackground,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                            color: AppColors.lineColor),
                      ),
                      child: Material(
                        color: Colors.transparent,
                        borderRadius: BorderRadius.circular(16),
                        child: ListTile(
                          leading: _getReasonIcon(item['type']!),
                          title: Text(
                            reasonTitle,
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          trailing: Icon(
                            _isArabic
                                ? Icons.arrow_back_ios_rounded
                                : Icons.arrow_forward_ios_rounded,
                            color: AppColors.textMuted,
                            size: 16,
                          ),
                          onTap: () {
                            Navigator.pop(context);
                            if (item['type'] == 'other') {
                              _showCustomProfileReasonDialog();
                            } else {
                              _submitReport(reasonTitle);
                            }
                          },
                        ),
                      ),
                    );
                  }),
                ],
              ),
            ),
          ),
        ),
      ),
    );

    _isModalActive = false;
  }

  Widget _getReasonIcon(String type) {
    IconData iconData;
    Color iconColor;

    switch (type) {
      case 'photo':
        iconData = Icons.no_photography_rounded;
        iconColor = AppColors.warning;
        break;
      case 'person':
        iconData = Icons.person_off_rounded;
        iconColor = AppColors.error;
        break;
      case 'bio':
        iconData = Icons.article_rounded;
        iconColor = AppColors.primaryTeal;
        break;
      case 'warning':
        iconData = Icons.warning_amber_rounded;
        iconColor = AppColors.warning;
        break;
      default:
        iconData = Icons.edit_note_rounded;
        iconColor = AppColors.accentWarm;
    }

    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: iconColor.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Icon(iconData, color: iconColor, size: 20),
    );
  }

  void _showCustomProfileReasonDialog() async {
    if (_isModalActive || !mounted) return;
    _isModalActive = true;

    await showDialog(
      context: context,
      builder: (context) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: _CustomProfileReportDialog(
          isArabic: _isArabic,
          onSubmit: (reasonText) => _submitReport(reasonText),
        ),
      ),
    );

    _isModalActive = false;
  }

  Future<void> _submitReport(String reason) async {
    try {
      final result = await ApiService.reportMember(widget.userId, reason);

      if (!mounted) return;

      final String responseMessage = result['message'] ??
          (_isArabic
              ? 'لقد سبق وأن بلغت على هذا الشخص من قبل.'
              : 'You have already reported this member.');

      if (result['success'] == true) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.success,
          isArabic: _isArabic,
          messageAr: responseMessage,
          messageEn: 'Report submitted successfully for review',
        );
      } else {
        AppSnackBar.show(
          context: context,
          type: NotificationType.warning,
          isArabic: _isArabic,
          messageAr: responseMessage,
          messageEn: responseMessage,
        );
      }
    } catch (e) {
      if (mounted) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isArabic,
          messageAr: 'تعذر إرسال البلاغ، يرجى التأكد من الاتصال بالإنترنت',
          messageEn:
              'Failed to submit report, please check your internet connection',
        );
      }
    }
  }

  bool _shouldShowChildrenCount(BasicInfoModel info) {
    if (info.noOfChildren.trim().isEmpty) return false;
    final status = info.maritalStatus.trim().toLowerCase();
    if (const {'2', '3', '4'}.contains(status)) return true;
    final localized =
        info.getLocalizedMaritalStatus(isArabic: _isArabic).toLowerCase();
    return localized.contains('متزوج') ||
        localized.contains('مطلق') ||
        localized.contains('أرمل') ||
        localized.contains('ارمل') ||
        localized.contains('married') ||
        localized.contains('divorced') ||
        localized.contains('widowed');
  }

  bool _hasPhysicalData() {
    if (_profile?.physicalAttributes == null) return false;
    final phys = _profile!.physicalAttributes!;
    return phys.height.isNotEmpty ||
        phys.weight.isNotEmpty ||
        phys.eyeColor.isNotEmpty ||
        phys.hairColor.isNotEmpty ||
        phys.complexion.isNotEmpty ||
        phys.bodyType.isNotEmpty ||
        phys.bodyArt.isNotEmpty;
  }

  bool _hasResidenceAndFamilyData() {
    final res = _profile?.residenceInfo;
    return res != null &&
        (res.birthCountry.isNotEmpty ||
            res.residencyCountry.isNotEmpty ||
            res.residencyState.isNotEmpty ||
            res.residencyCity.isNotEmpty ||
            res.immigrationStatus.isNotEmpty);
  }

  bool _hasSpiritualData() {
    final sp = _profile?.spiritualBackground;
    final info = _profile?.basicInfo;
    if (info == null) return false;

    final religionText = (sp != null && sp.religion.isNotEmpty)
        ? sp.getLocalizedReligion(isArabic: _isArabic)
        : info.getLocalizedReligion(isArabic: _isArabic);

    final familyValueText =
        sp?.getLocalizedFamilyValue(isArabic: _isArabic) ?? '';

    return religionText.isNotEmpty || familyValueText.isNotEmpty;
  }

  bool _hasLifestyleAndHobbiesData() {
    final ls = _profile?.lifestyle;
    final hb = _profile?.hobbies;
    final hasLs = ls != null &&
        (ls.smoke.isNotEmpty ||
            ls.drink.isNotEmpty ||
            ls.livingWith.isNotEmpty);
    final hasHb = hb != null &&
        (hb.hobbies.isNotEmpty ||
            hb.interests.isNotEmpty ||
            hb.books.isNotEmpty ||
            hb.music.isNotEmpty ||
            hb.movies.isNotEmpty ||
            hb.sports.isNotEmpty ||
            hb.dressStyles.isNotEmpty);
    final attitude = _profile?.attitudeBehavior;
    final astro = _profile?.astronomicInformation;
    final hasAttitude = attitude != null &&
        (attitude.affection.isNotEmpty || attitude.humor.isNotEmpty);
    final hasAstro = astro != null && astro.sunSign.isNotEmpty;
    return hasLs || hasHb || hasAttitude || hasAstro;
  }

  bool _hasPartnerExpectationsData() {
    if (_profile?.partnerExpectation == null) return false;
    final pe = _profile!.partnerExpectation!;
    return pe.general.isNotEmpty ||
        pe.getFormattedHeight(isArabic: _isArabic).isNotEmpty ||
        pe.getFormattedWeight(isArabic: _isArabic).isNotEmpty ||
        pe.maritalStatus.isNotEmpty ||
        pe.education.isNotEmpty ||
        pe.profession.isNotEmpty ||
        pe.religion.isNotEmpty ||
        pe.language.isNotEmpty ||
        pe.preferredCountry.isNotEmpty ||
        pe.preferredState.isNotEmpty ||
        pe.eyeColor.isNotEmpty ||
        pe.hairColor.isNotEmpty ||
        pe.complexion.isNotEmpty ||
        pe.childrenAcceptable.isNotEmpty ||
        pe.smokingAcceptable.isNotEmpty ||
        pe.drinkingAcceptable.isNotEmpty;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundColor,
      body: Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          width: double.infinity,
          height: double.infinity,
          decoration: const BoxDecoration(
            color: AppColors.backgroundColor,
          ),
          child: SafeArea(
            bottom: false,
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(
                      color: AppColors.primaryTeal,
                      strokeWidth: 2.5,
                    ),
                  )
                : _hasError || _profile == null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: AppColors.sectionBackground,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.error_outline_rounded,
                                  color: AppColors.error, size: 54),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _isArabic
                                  ? 'تعذر تحميل بيانات العضو'
                                  : 'Failed to load member profile',
                              style: const TextStyle(
                                  color: AppColors.textDark,
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                  fontFamily: 'Cairo'),
                            ),
                            const SizedBox(height: 20),
                            ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primaryTeal,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 28, vertical: 12),
                                shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(16)),
                              ),
                              onPressed: _fetchProfileData,
                              child: Text(
                                  _isArabic ? 'إعادة المحاولة' : 'Retry',
                                  style: const TextStyle(
                                      fontFamily: 'Cairo',
                                      fontWeight: FontWeight.bold)),
                            ),
                          ],
                        ),
                      )
                    : Column(
                        children: [
                          Expanded(
                            child: CustomScrollView(
                              physics: const BouncingScrollPhysics(),
                              slivers: [
                                _buildSliverHeader(),
                                SliverToBoxAdapter(
                                  child: Padding(
                                    padding: const EdgeInsets.fromLTRB(
                                        16, 16, 16, 24),
                                    child: Column(
                                      children: [
                                        _buildVerifiedIdentityBadge(),
                                        const SizedBox(height: 16),
                                        if (_profile!
                                            .introduction.isNotEmpty) ...[
                                          _buildSectionCard(
                                            title: _isArabic
                                                ? 'مقدمة عني'
                                                : 'About Me',
                                            icon: Icons.format_quote_rounded,
                                            accentColor:
                                                AppColors.primaryTeal,
                                            content: Text(
                                              _profile!.introduction,
                                              style: TextStyle(
                                                  color: AppColors.textDark,
                                                  fontSize: 14,
                                                  height: 1.6,
                                                  fontFamily: 'Cairo'),
                                            ),
                                          ),
                                          const SizedBox(height: 16),
                                        ],
                                        _buildBasicDetailsGrid(),
                                        const SizedBox(height: 16),
                                        if (_profile!.education != null ||
                                            _profile!.career != null) ...[
                                          _buildEducationAndCareerSection(),
                                          const SizedBox(height: 16),
                                        ],
                                        if (_hasPhysicalData()) ...[
                                          _buildPhysicalAttributesSection(),
                                          const SizedBox(height: 16),
                                        ],
                                        if (_hasResidenceAndFamilyData()) ...[
                                          _buildResidenceAndFamilySection(),
                                          const SizedBox(height: 16),
                                        ],
                                        if (_hasSpiritualData()) ...[
                                          _buildSpiritualSection(),
                                          const SizedBox(height: 16),
                                        ],
                                        if (_hasLifestyleAndHobbiesData()) ...[
                                          _buildLifestyleAndHobbiesSection(),
                                          const SizedBox(height: 16),
                                        ],
                                        if (_hasPartnerExpectationsData()) ...[
                                          _buildPartnerExpectationsSection(),
                                          const SizedBox(height: 16),
                                        ],
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          _buildBottomActionBar(),
                        ],
                      ),
          ),
        ),
      ),
    );
  }

  Widget _buildVerifiedIdentityBadge() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.primaryTeal.withValues(alpha: 0.18),
            AppColors.accentLight.withValues(alpha: 0.12),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(22),
        border:
            Border.all(color: AppColors.primaryTeal.withValues(alpha: 0.35)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: AppColors.primaryTeal.withValues(alpha: 0.2),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.verified_rounded,
                color: AppColors.primaryTeal, size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _isArabic
                      ? 'بطاقة عضوية مكتملة وموثقة'
                      : 'Verified Member Profile',
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  _isArabic
                      ? 'تمت مراجعة واستيفاء البيانات الأساسية والشخصية بالكامل'
                      : 'All personal specifications have been verified',
                  style: TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 11.5,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSliverHeader() {
    final info = _profile!.basicInfo;
    final bool isFemale = info.gender.toLowerCase().contains('female') ||
        info.gender.contains('أنثى');

    final String localizedMarital =
        info.getLocalizedMaritalStatus(isArabic: _isArabic).trim();
    final String localizedReligion =
        info.getLocalizedReligion(isArabic: _isArabic).trim();
    final String? managedAccountLabel =
        info.getManagedAccountLabel(isArabic: _isArabic);

    final List<String> subtitleParts = [];
    if (localizedMarital.isNotEmpty &&
        localizedMarital != '_' &&
        localizedMarital != '___') {
      subtitleParts.add(localizedMarital);
    }
    if (localizedReligion.isNotEmpty &&
        localizedReligion != '_' &&
        localizedReligion != '___') {
      subtitleParts.add(localizedReligion);
    }
    if (info.code.isNotEmpty) {
      subtitleParts.add('(ID: ${info.code})');
    }
    final String subtitleText = subtitleParts.join(' • ');

    return SliverAppBar(
      expandedHeight: 320.0,
      floating: false,
      pinned: true,
      elevation: 0,
      backgroundColor: AppColors.backgroundColor,
      leading: Padding(
        padding: const EdgeInsets.all(8.0),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(14),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
            child: Container(
              color: Colors.black.withValues(alpha: 0.3),
              child: IconButton(
                icon: Icon(
                    _isArabic
                        ? Icons.arrow_back_ios_new_rounded
                        : Icons.arrow_back_rounded,
                    color: Colors.white,
                    size: 18),
                onPressed: () => Navigator.pop(context),
              ),
            ),
          ),
        ),
      ),
      actions: [
        // زر مشاركة رابط العضو
        Padding(
          padding: const EdgeInsets.all(8.0),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
              child: Container(
                color: Colors.black.withValues(alpha: 0.3),
                child: IconButton(
                  icon: const Icon(Icons.share_rounded,
                      color: AppColors.primaryTeal, size: 20),
                  onPressed: _shareProfileLink,
                ),
              ),
            ),
          ),
        ),
        // زر الإبلاغ
        Padding(
          padding: const EdgeInsets.all(8.0),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
              child: Container(
                color: Colors.black.withValues(alpha: 0.3),
                child: IconButton(
                  icon: const Icon(Icons.report_problem_outlined,
                      color: AppColors.warning, size: 20),
                  onPressed: _showReportDialog,
                ),
              ),
            ),
          ),
        ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: Stack(
          fit: StackFit.expand,
          children: [
            if (ChatThreadModel.hasCustomPhoto(info.photo))
              CachedNetworkImage(
                imageUrl: info.photo,
                fit: BoxFit.cover,
                placeholder: (context, url) =>
                    Container(color: AppColors.sectionBackground),
                errorWidget: (context, url, error) =>
                    Container(color: AppColors.sectionBackground),
              )
            else
              Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: isFemale
                        ? [AppColors.accentWarm, AppColors.primaryTeal]
                        : [AppColors.primaryTeal, AppColors.secondaryTeal],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                child: Icon(
                    isFemale ? Icons.female_rounded : Icons.male_rounded,
                    color: Colors.white24,
                    size: 110),
              ),
            Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Colors.black.withValues(alpha: 0.4),
                    Colors.transparent,
                    const Color(0xFF070A11).withValues(alpha: 0.85),
                    const Color(0xFF070A11),
                  ],
                  stops: const [0.0, 0.35, 0.8, 1.0],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
              ),
            ),
            Positioned(
              bottom: 16,
              left: 16,
              right: 16,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Flexible(
                              child: Text(
                                info.fullName,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 23,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo'),
                              ),
                            ),
                            // 🟢 استبدال الشارات المحلية باستدعاء الودجت الموحد
                            if (info.packageName.isNotEmpty) ...[
                              const SizedBox(width: 8),
                              PackageBadgeWidget(
                                packageName: info.packageName,
                                compact: true,
                                isArabic: _isArabic,
                              ),
                            ],
                            const SizedBox(width: 8),
                            if (info.age > 0)
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 10, vertical: 3),
                                decoration: BoxDecoration(
                                    color: AppColors.primaryTeal
                                        .withValues(alpha: 0.2),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                        color: AppColors.primaryTeal
                                            .withValues(alpha: 0.6))),
                                child: Text(
                                    '${info.age} ${_isArabic ? 'سنة' : 'yrs'}',
                                    style: const TextStyle(
                                        color: AppColors.primaryTeal,
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        fontFamily: 'Cairo')),
                              ),
                          ],
                        ),
                        if (subtitleText.isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            subtitleText,
                            style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.75),
                                fontSize: 13,
                                fontFamily: 'Cairo'),
                          ),
                        ],
                        if (managedAccountLabel != null) ...[
                          const SizedBox(height: 8),
                          Container(
                            constraints: const BoxConstraints(maxWidth: 280),
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 6),
                            decoration: BoxDecoration(
                              color: AppColors.warning
                                  .withValues(alpha: 0.16),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(
                                color: AppColors.accentLight
                                    .withValues(alpha: 0.65),
                              ),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(
                                  Icons.admin_panel_settings_rounded,
                                  color: AppColors.warning,
                                  size: 16,
                                ),
                                const SizedBox(width: 6),
                                Flexible(
                                  child: Text(
                                    managedAccountLabel,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      color: AppColors.textDark,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w700,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  if (_profile!.profileMatchStatus != 'not_eligible')
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 8),
                      decoration: BoxDecoration(
                        color: _profile!.profileMatchPercentage == null
                            ? AppColors.textMuted
                            : null,
                        gradient: _profile!.profileMatchPercentage == null
                            ? null
                            : const LinearGradient(
                                colors: [AppColors.success, AppColors.success]),
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                            color: Colors.white.withValues(alpha: 0.2)),
                        boxShadow: [
                          BoxShadow(
                              color: (_profile!.profileMatchPercentage == null
                                      ? Colors.black
                                      : AppColors.success)
                                  .withValues(alpha: 0.25),
                              blurRadius: 12)
                        ],
                      ),
                      child: Column(
                        children: [
                          Text(
                            _profile!.profileMatchPercentage != null
                                ? '${_profile!.profileMatchPercentage}%'
                                : (_profile!.profileMatchStatus == 'no_preferences'
                                    ? (_isArabic
                                        ? 'أكمل تفضيلاتك'
                                        : 'Complete preferences')
                                    : (_isArabic
                                        ? 'غير محسوبة'
                                        : 'Not calculated')),
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo'),
                          ),
                          Text(_isArabic ? 'توافق' : 'Match',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 10,
                                  fontFamily: 'Cairo')),
                          if (_profile!.profileMatchCriteriaCount > 0)
                            Text(
                              _isArabic
                                  ? 'المعايير ${_profile!.profileMatchEvaluatedCriteriaCount}/${_profile!.profileMatchCriteriaCount}'
                                  : 'Criteria ${_profile!.profileMatchEvaluatedCriteriaCount}/${_profile!.profileMatchCriteriaCount}',
                              style: const TextStyle(
                                  color: Colors.white70,
                                  fontSize: 9,
                                  fontFamily: 'Cairo'),
                            ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBasicDetailsGrid() {
    final info = _profile!.basicInfo;
    return _buildSectionCard(
      title: _isArabic ? 'المعلومات الأساسية' : 'Basic Details',
      icon: Icons.person_outline_rounded,
      accentColor: AppColors.primaryTeal,
      content: Wrap(
        spacing: 10,
        runSpacing: 10,
        children: [
          _buildInfoChip(Icons.wc_rounded, _isArabic ? 'الجنس' : 'Gender',
              info.getGenderText(isArabic: _isArabic)),
          _buildInfoChip(Icons.cake_rounded,
              _isArabic ? 'تاريخ الميلاد' : 'DOB', info.dateOfBirth),
          if (_shouldShowChildrenCount(info))
            _buildInfoChip(
                Icons.family_restroom_rounded,
                _isArabic ? 'عدد الأطفال' : 'Number of Children',
                info.noOfChildren),
          _buildInfoChip(
              Icons.volunteer_activism_rounded,
              _isArabic ? 'الحالة الاجتماعية' : 'Marital Status',
              info.getLocalizedMaritalStatus(isArabic: _isArabic)),
        ],
      ),
    );
  }

  Widget _buildEducationAndCareerSection() {
    final edu = _profile!.education;
    final car = _profile!.career;

    return _buildSectionCard(
      title: _isArabic ? 'التعليم والعمل' : 'Education & Career',
      icon: Icons.school_outlined,
      accentColor: AppColors.secondaryTeal,
      content: Wrap(
        spacing: 10,
        runSpacing: 10,
        children: [
          if (edu != null) ...[
            _buildInfoChip(
                Icons.school_rounded,
                _isArabic ? 'المؤهل العلمي' : 'Degree',
                edu.getLocalizedDegree(isArabic: _isArabic)),
            _buildInfoChip(
                Icons.account_balance_rounded,
                _isArabic ? 'الجامعة / التخصص' : 'Institution',
                edu.getLocalizedInstitution(isArabic: _isArabic)),
          ],
          if (car != null) ...[
            _buildInfoChip(
                Icons.work_rounded,
                _isArabic ? 'المهنة / الوظيفة' : 'Designation',
                car.getLocalizedDesignation(isArabic: _isArabic)),
            _buildInfoChip(
                Icons.business_center_rounded,
                _isArabic ? 'جهة العمل' : 'Company',
                car.getLocalizedCompany(isArabic: _isArabic)),
          ],
        ],
      ),
    );
  }

  Widget _buildPhysicalAttributesSection() {
    final phys = _profile!.physicalAttributes!;
    return _buildSectionCard(
      title: _isArabic ? 'المواصفات الجسدية' : 'Physical Attributes',
      icon: Icons.accessibility_new_rounded,
      accentColor: AppColors.accentWarm,
      content: Wrap(
        spacing: 10,
        runSpacing: 10,
        children: [
          _buildInfoChip(Icons.height_rounded, _isArabic ? 'الطول' : 'Height',
              phys.height),
          _buildInfoChip(Icons.monitor_weight_outlined,
              _isArabic ? 'الوزن' : 'Weight', phys.weight),
          _buildVisualColorChip(
              Icons.remove_red_eye_rounded,
              _isArabic ? 'لون العين' : 'Eye Color',
              phys.getLocalizedEyeColor(isArabic: _isArabic)),
          _buildVisualColorChip(
              Icons.face_rounded,
              _isArabic ? 'لون الشعر' : 'Hair Color',
              phys.getLocalizedHairColor(isArabic: _isArabic)),
          _buildVisualColorChip(
              Icons.color_lens_rounded,
              _isArabic ? 'البشرة' : 'Complexion',
              phys.getLocalizedComplexion(isArabic: _isArabic)),
          _buildInfoChip(
              Icons.accessibility_rounded,
              _isArabic ? 'بنية الجسد' : 'Body Type',
              phys.getLocalizedBodyType(isArabic: _isArabic)),
          _buildInfoChip(
              Icons.palette_outlined,
              _isArabic ? 'الوشم / الرسومات' : 'Body Art',
              phys.getLocalizedBodyArt(isArabic: _isArabic)),
        ],
      ),
    );
  }

  Widget _buildSpiritualSection() {
    final sp = _profile!.spiritualBackground;
    final info = _profile!.basicInfo;

    final String religionText = (sp != null && sp.religion.isNotEmpty)
        ? sp.getLocalizedReligion(isArabic: _isArabic)
        : info.getLocalizedReligion(isArabic: _isArabic);

    final String familyValueText =
        sp?.getLocalizedFamilyValue(isArabic: _isArabic) ?? '';

    return _buildSectionCard(
      title: _isArabic ? 'الخلفية الدينية والاجتماعية' : 'Spiritual & Social',
      icon: Icons.mosque_rounded,
      accentColor: AppColors.warning,
      content: Wrap(
        spacing: 10,
        runSpacing: 10,
        children: [
          _buildInfoChip(Icons.auto_awesome_rounded,
              _isArabic ? 'الديانة' : 'Religion', religionText),
          if (familyValueText.isNotEmpty)
            _buildInfoChip(
                Icons.star_outline_rounded,
                _isArabic ? 'القيم العائلية' : 'Family Values',
                familyValueText),
        ],
      ),
    );
  }

  Widget _buildLifestyleAndHobbiesSection() {
    final ls = _profile!.lifestyle;
    final hb = _profile!.hobbies;
    final attitude = _profile!.attitudeBehavior;
    final astro = _profile!.astronomicInformation;

    return _buildSectionCard(
      title: _isArabic ? 'أسلوب الحياة والاهتمامات' : 'Lifestyle & Interests',
      icon: Icons.local_activity_rounded,
      accentColor: AppColors.success,
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (ls != null)
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                if (ls.smoke.isNotEmpty)
                  _buildInfoChip(
                    Icons.smoking_rooms_rounded,
                    _isArabic ? 'التدخين' : 'Smoking',
                    ls.getLocalizedSmoke(isArabic: _isArabic),
                  ),
                if (ls.drink.isNotEmpty)
                  _buildInfoChip(
                    Icons.local_bar_rounded,
                    _isArabic ? 'المشروبات' : 'Drinks',
                    ls.getLocalizedDrink(isArabic: _isArabic),
                  ),
                if (ls.livingWith.isNotEmpty)
                  _buildInfoChip(
                    Icons.home_rounded,
                    _isArabic ? 'السكن الحالي' : 'Living With',
                    ls.getLocalizedLivingWith(isArabic: _isArabic),
                  ),
              ],
            ),
          if (attitude != null || astro != null) ...[
            if (ls != null) const SizedBox(height: 12),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                if (attitude?.affection.isNotEmpty == true)
                  _buildInfoChip(
                    Icons.favorite_border_rounded,
                    _isArabic ? 'طريقة التعبير عن المشاعر' : 'Affection Style',
                    attitude!.getLocalizedAffection(isArabic: _isArabic),
                  ),
                if (attitude?.humor.isNotEmpty == true)
                  _buildInfoChip(
                    Icons.sentiment_satisfied_alt_rounded,
                    _isArabic ? 'حس الفكاهة' : 'Sense of Humor',
                    attitude!.getLocalizedHumor(isArabic: _isArabic),
                  ),
                if (astro?.sunSign.isNotEmpty == true)
                  _buildInfoChip(
                    Icons.stars_rounded,
                    _isArabic ? 'البرج الشمسي' : 'Zodiac Sign',
                    astro!.getLocalizedSunSign(isArabic: _isArabic),
                  ),
              ],
            ),
          ],
          if (hb != null) ...[
            const SizedBox(height: 12),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                if (hb.hobbies.isNotEmpty)
                  _buildInfoChip(Icons.sports_esports_rounded,
                    _isArabic ? 'الهوايات' : 'Hobbies',
                    hb.getLocalizedHobbies(isArabic: _isArabic)),
                if (hb.interests.isNotEmpty)
                  _buildInfoChip(Icons.stars_rounded,
                    _isArabic ? 'الاهتمامات' : 'Interests',
                    hb.getLocalizedInterests(isArabic: _isArabic)),
                if (hb.books.isNotEmpty)
                  _buildInfoChip(Icons.menu_book_rounded,
                    _isArabic ? 'الكتب' : 'Books',
                    hb.getLocalizedBooks(isArabic: _isArabic)),
                if (hb.music.isNotEmpty)
                  _buildInfoChip(Icons.music_note_rounded,
                    _isArabic ? 'الموسيقى' : 'Music',
                    hb.getLocalizedMusic(isArabic: _isArabic)),
                if (hb.movies.isNotEmpty)
                  _buildInfoChip(Icons.movie_rounded,
                    _isArabic ? 'الأفلام' : 'Movies',
                    hb.getLocalizedMovies(isArabic: _isArabic)),
                if (hb.sports.isNotEmpty)
                  _buildInfoChip(Icons.fitness_center_rounded,
                    _isArabic ? 'الرياضة' : 'Sports',
                    hb.getLocalizedSports(isArabic: _isArabic)),
                if (hb.dressStyles.isNotEmpty)
                  _buildInfoChip(Icons.checkroom_rounded,
                    _isArabic ? 'أسلوب اللباس' : 'Dress Style',
                    hb.getLocalizedDressStyles(isArabic: _isArabic)),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildResidenceAndFamilySection() {
    final res = _profile!.residenceInfo!;
    return _buildSectionCard(
      title: _isArabic ? 'الإقامة' : 'Residence',
      icon: Icons.location_on_rounded,
      accentColor: AppColors.accentWarm,
      content: Wrap(
        spacing: 10,
        runSpacing: 10,
        children: [
          _buildInfoChip(
            Icons.flag_rounded,
            _isArabic ? 'بلد الميلاد' : 'Birth Country',
            res.getLocalizedBirthCountry(isArabic: _isArabic),
          ),
          _buildInfoChip(
            Icons.public_rounded,
            _isArabic ? 'بلد الإقامة' : 'Residency',
            res.getLocalizedResidencyCountry(isArabic: _isArabic),
          ),
          _buildInfoChip(
            Icons.location_city_rounded,
            _isArabic ? 'المحافظة' : 'State',
            res.getLocalizedResidencyState(isArabic: _isArabic),
          ),
          _buildInfoChip(
            Icons.holiday_village_rounded,
            _isArabic ? 'المدينة' : 'City',
            res.getLocalizedResidencyCity(isArabic: _isArabic),
          ),
          _buildInfoChip(
            Icons.card_membership_rounded,
            _isArabic ? 'حالة الإقامة' : 'Immigration Status',
            res.getLocalizedImmigrationStatus(isArabic: _isArabic),
          ),
        ],
      ),
    );
  }

  Widget _buildPartnerExpectationsSection() {
    final pe = _profile!.partnerExpectation!;
    final religion = pe.getLocalizedReligion(isArabic: _isArabic);
    final language = pe.getLocalizedLanguage(isArabic: _isArabic);
    final preferredCountry =
        pe.getLocalizedPreferredCountry(isArabic: _isArabic);
    final preferredState = pe.getLocalizedPreferredState(isArabic: _isArabic);
    final eyeColor = pe.getLocalizedEyeColor(isArabic: _isArabic);
    final hairColor = pe.getLocalizedHairColor(isArabic: _isArabic);

    return _buildSectionCard(
      title: _isArabic ? 'توقعات شريك الحياة' : 'Partner Expectations',
      icon: Icons.favorite_rounded,
      accentColor: AppColors.secondaryTeal,
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (pe.getLocalizedGeneral(isArabic: _isArabic).isNotEmpty) ...[
            Text(
              pe.getLocalizedGeneral(isArabic: _isArabic),
              style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 13.5,
                height: 1.5,
                fontFamily: 'Cairo',
              ),
            ),
            const SizedBox(height: 12),
          ],
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              if (pe.getFormattedHeight(isArabic: _isArabic).isNotEmpty)
                _buildInfoChip(
                  Icons.height_rounded,
                  _isArabic ? 'طول الشريك' : 'Preferred Height',
                  pe.getFormattedHeight(isArabic: _isArabic),
                ),
              if (pe.getFormattedWeight(isArabic: _isArabic).isNotEmpty)
                _buildInfoChip(
                  Icons.monitor_weight_outlined,
                  _isArabic ? 'وزن الشريك' : 'Preferred Weight',
                  pe.getFormattedWeight(isArabic: _isArabic),
                ),
              if (pe.getLocalizedMaritalStatus(isArabic: _isArabic).isNotEmpty)
                _buildInfoChip(
                  Icons.volunteer_activism_rounded,
                  _isArabic ? 'الحالة الاجتماعية للشريك' : 'Partner Marital Status',
                  pe.getLocalizedMaritalStatus(isArabic: _isArabic),
                ),
              if (pe.getLocalizedEducation(isArabic: _isArabic).isNotEmpty)
                _buildInfoChip(
                  Icons.school_rounded,
                  _isArabic ? 'التعليم المفضل' : 'Preferred Education',
                  pe.getLocalizedEducation(isArabic: _isArabic),
                ),
              if (pe.getLocalizedProfession(isArabic: _isArabic).isNotEmpty)
                _buildInfoChip(
                  Icons.work_rounded,
                  _isArabic ? 'المهنة المفضلة' : 'Preferred Profession',
                  pe.getLocalizedProfession(isArabic: _isArabic),
                ),
              if (religion.isNotEmpty)
                _buildInfoChip(
                  Icons.auto_awesome_rounded,
                  _isArabic ? 'ديانة الشريك' : 'Partner Religion',
                  religion,
                ),
              if (language.isNotEmpty)
                _buildInfoChip(
                  Icons.translate_rounded,
                  _isArabic ? 'لغة الشريك المفضلة' : 'Preferred Partner Language',
                  language,
                ),
              if (preferredCountry.isNotEmpty)
                _buildInfoChip(
                  Icons.public_rounded,
                  _isArabic ? 'بلد الشريك المفضل' : 'Preferred Partner Country',
                  preferredCountry,
                ),
              if (preferredState.isNotEmpty)
                _buildInfoChip(
                  Icons.location_city_rounded,
                  _isArabic ? 'محافظة الشريك المفضلة' : 'Preferred Partner Governorate',
                  preferredState,
                ),
              if (eyeColor.isNotEmpty)
                _buildVisualColorChip(
                  Icons.remove_red_eye_rounded,
                  _isArabic ? 'لون عيون الشريك' : 'Partner Eye Color',
                  eyeColor,
                ),
              if (hairColor.isNotEmpty)
                _buildVisualColorChip(
                  Icons.face_rounded,
                  _isArabic ? 'لون شعر الشريك' : 'Partner Hair Color',
                  hairColor,
                ),
              if (pe.getLocalizedComplexion(isArabic: _isArabic).isNotEmpty)
                _buildVisualColorChip(
                  Icons.color_lens_outlined,
                  _isArabic ? 'لون البشرة المفضل' : 'Preferred Complexion',
                  pe.getLocalizedComplexion(isArabic: _isArabic),
                ),
              if (pe.getLocalizedChildrenAcceptable(isArabic: _isArabic).isNotEmpty)
                _buildInfoChip(
                  Icons.child_care_rounded,
                  _isArabic ? 'قبول الأطفال' : 'Children Acceptance',
                  pe.getLocalizedChildrenAcceptable(isArabic: _isArabic),
                ),
              if (pe.getLocalizedSmokingAcceptable(isArabic: _isArabic).isNotEmpty)
                _buildInfoChip(
                  Icons.smoking_rooms_rounded,
                  _isArabic ? 'موقف التدخين' : 'Smoking Acceptance',
                  pe.getLocalizedSmokingAcceptable(isArabic: _isArabic),
                ),
              if (pe.getLocalizedDrinkingAcceptable(isArabic: _isArabic).isNotEmpty)
                _buildInfoChip(
                  Icons.local_bar_rounded,
                  _isArabic ? 'موقف المشروبات' : 'Drinking Acceptance',
                  pe.getLocalizedDrinkingAcceptable(isArabic: _isArabic),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSectionCard(
      {required String title,
      required IconData icon,
      required Color accentColor,
      required Widget content}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.cardColor.withValues(alpha: 0.65),
        borderRadius: BorderRadius.circular(24),
        border:
            Border.all(color: accentColor.withValues(alpha: 0.25), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: accentColor.withValues(alpha: 0.06),
            blurRadius: 18,
            offset: const Offset(0, 6),
          )
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(9),
                decoration: BoxDecoration(
                  color: accentColor.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: accentColor.withValues(alpha: 0.4)),
                ),
                child: Icon(icon, color: accentColor, size: 21),
              ),
              const SizedBox(width: 12),
              Text(
                title,
                style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo'),
              ),
            ],
          ),
          const SizedBox(height: 14),
          content,
        ],
      ),
    );
  }

  Widget _buildVisualColorChip(IconData icon, String label, String value) {
    if (value.isEmpty) return const SizedBox.shrink();

    final Color sampleColor = _getVisualColor(value);
    final bool isWhiteColor = sampleColor.toARGB32() == 0xFFFFFFFF ||
        sampleColor.toARGB32() == 0xFFFFF7ED;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.sectionBackground,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isWhiteColor
              ? AppColors.lineColor
              : sampleColor.withValues(alpha: 0.5),
          width: 1.2,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 15,
            height: 15,
            decoration: BoxDecoration(
              color: sampleColor,
              shape: BoxShape.circle,
              border: Border.all(
                color: isWhiteColor
                    ? const Color(0xFF94A3B8)
                    : AppColors.lineColor,
                width: 1.5,
              ),
              boxShadow: [
                BoxShadow(
                  color:
                      sampleColor.withValues(alpha: isWhiteColor ? 0.3 : 0.7),
                  blurRadius: 6,
                  spreadRadius: 1,
                )
              ],
            ),
          ),
          const SizedBox(width: 8),
          Flexible(
            child: Text(
              '$label: $value',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 12.5,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoChip(IconData icon, String label, String value) {
    if (value.isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.sectionBackground,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.lineColor),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: AppColors.primaryTeal, size: 15),
          const SizedBox(width: 6),
          Flexible(
            child: Text(
              '$label: $value',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 12.5,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomActionBar() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.cardColor.withValues(alpha: 0.85),
        border: Border(
          top: BorderSide(
            color: AppColors.lineColor,
            width: 1,
          ),
        ),
      ),
      child: ClipRRect(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
          child: SafeArea(
            top: false,
            bottom: true,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      height: 50,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(18),
                        boxShadow: [
                          if (!_isInterestSent)
                            BoxShadow(
                              color: AppColors.primaryTeal
                                  .withValues(alpha: 0.35),
                              blurRadius: 12,
                              offset: const Offset(0, 4),
                            ),
                        ],
                      ),
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _isInterestSent
                              ? AppColors.success
                              : AppColors.primaryTeal,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(18),
                          ),
                          elevation: 0,
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                        ),
                        icon: Icon(
                          _isInterestSent
                              ? Icons.check_circle_rounded
                              : Icons.favorite_rounded,
                          color: Colors.white,
                          size: 20,
                        ),
                        label: Text(
                          _isInterestSent
                              ? (_isArabic ? 'تم الإرسال' : 'Interest Sent')
                              : (_isArabic
                                  ? 'إرسال اهتمام'
                                  : 'Express Interest'),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                            fontSize: 14,
                          ),
                        ),
                        onPressed: _sendExpressInterest,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Container(
                      height: 50,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(18),
                        boxShadow: [
                          BoxShadow(
                            color:
                                AppColors.primaryTeal.withValues(alpha: 0.2),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.sectionBackground,
                          foregroundColor: AppColors.primaryTeal,
                          side: const BorderSide(
                            color: AppColors.primaryTeal,
                            width: 1.2,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(18),
                          ),
                          elevation: 0,
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                        ),
                        icon: const Icon(
                          Icons.chat_bubble_rounded,
                          color: AppColors.primaryTeal,
                          size: 20,
                        ),
                        label: Text(
                          _isArabic ? 'المراسلة' : 'Message',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: AppColors.primaryTeal,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                            fontSize: 14,
                          ),
                        ),
                        onPressed: _startChat,
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
}

class _CustomProfileReportDialog extends StatefulWidget {
  final bool isArabic;
  final Function(String) onSubmit;

  const _CustomProfileReportDialog({
    required this.isArabic,
    required this.onSubmit,
  });

  @override
  State<_CustomProfileReportDialog> createState() =>
      __CustomProfileReportDialogState();
}

class __CustomProfileReportDialogState
    extends State<_CustomProfileReportDialog> {
  late final TextEditingController _customController;

  @override
  void initState() {
    super.initState();
    _customController = TextEditingController();
  }

  @override
  void dispose() {
    _customController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      elevation: 0,
      insetPadding: const EdgeInsets.symmetric(horizontal: 24),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(28),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
          child: Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              color: AppColors.cardColor.withValues(alpha: 0.95),
              borderRadius: BorderRadius.circular(28),
              border: Border.all(
                color: AppColors.accentWarm.withValues(alpha: 0.4),
                width: 1.5,
              ),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  widget.isArabic
                      ? 'كتابة سبب الإبلاغ'
                      : 'Specify Report Reason',
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _customController,
                  maxLines: 3,
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontFamily: 'Cairo',
                    fontSize: 14,
                  ),
                  decoration: InputDecoration(
                    hintText: widget.isArabic
                        ? 'اكتب التفاصيل هنا لمساعدة الإدارة في التحقق...'
                        : 'Type details here to help review...',
                    hintStyle: const TextStyle(
                      color: AppColors.textMuted,
                      fontFamily: 'Cairo',
                      fontSize: 13,
                    ),
                    filled: true,
                    fillColor: AppColors.sectionBackground,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide(
                          color: AppColors.lineColor),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide(
                          color: AppColors.lineColor),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: const BorderSide(color: AppColors.primaryTeal),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(
                      child: TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: Text(
                          widget.isArabic ? 'إلغاء' : 'Cancel',
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
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                        ),
                        onPressed: () {
                          final text = _customController.text.trim();
                          if (text.isNotEmpty) {
                            Navigator.pop(context);
                            widget.onSubmit(text);
                          }
                        },
                        child: Text(
                          widget.isArabic ? 'إرسال البلاغ' : 'Submit Report',
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
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
    );
  }
}

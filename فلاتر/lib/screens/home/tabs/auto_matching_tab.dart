// ============================================================================
// اسم الملف: lib/screens/home/tabs/auto_matching_tab.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: تبويب المطابقات التلقائية والبطاقات الخارجية مع دعم شارات الباقات الموحدة والترجمة الديناميكية الكاملة لكافة الحقول.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../constants/colors.dart';
import '../../../widgets/app_snackbar.dart';
import '../../../widgets/bottom_system_inset.dart';

import '../../../models/member_model.dart';
import '../../../models/chat_model.dart';
import '../../../services/api_service.dart';
import '../../../widgets/package_badge_widget.dart';
import '../../member_profile_screen.dart';
import '../../chat_detail_screen.dart';
import 'account_tab.dart';

class AutoMatchingTab extends StatefulWidget {
  final String currentLanguage;
  final bool isActive;
  final String Function(String) txt;
  final int unreadNotificationCount;
  final VoidCallback onNotificationPressed;
  final VoidCallback onUpgradeRequested;

  const AutoMatchingTab({
    super.key,
    required this.currentLanguage,
    this.isActive = true,
    required this.txt,
    required this.unreadNotificationCount,
    required this.onNotificationPressed,
    required this.onUpgradeRequested,
  });

  @override
  State<AutoMatchingTab> createState() => _AutoMatchingTabState();
}

class _AutoMatchingTabState extends State<AutoMatchingTab>
    with TickerProviderStateMixin {
  List<MemberModel> _matchedMembers = [];
  int _currentProfileIndex = 0;
  bool _isLoading = true;
  String? _errorMessage;

  bool _isSubscribedOrFemale = true;
  bool _isModalActive = false;
  bool _isMatchFilterEnabled = false;

  int? _likingUserId;
  final Set<int> _likedUserIds = {};
  int _fetchCounter = 0;

  late PageController _pageController;
  late AnimationController _heartAnimationController;
  late Animation<double> _heartScaleAnimation;
  late Animation<double> _heartOpacityAnimation;
  bool _showHeartOverlay = false;

  bool get _isAr => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _pageController = PageController(viewportFraction: 0.90);

    _heartAnimationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 750),
    );

    _heartScaleAnimation = Tween<double>(begin: 0.1, end: 1.25).animate(
      CurvedAnimation(
        parent: _heartAnimationController,
        curve: Curves.elasticOut,
      ),
    );

    _heartOpacityAnimation = Tween<double>(begin: 1.0, end: 0.0).animate(
      CurvedAnimation(
        parent: _heartAnimationController,
        curve: const Interval(0.65, 1.0, curve: Curves.easeOut),
      ),
    );

    _heartAnimationController.addStatusListener((status) {
      if (status == AnimationStatus.completed) {
        if (mounted) {
          setState(() {
            _showHeartOverlay = false;
          });
        }
        _heartAnimationController.reset();
      }
    });

    _checkSubscriptionAndFetch();
  }

  @override
  void didUpdateWidget(AutoMatchingTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    final languageChanged =
        oldWidget.currentLanguage != widget.currentLanguage;
    final becameActive = !oldWidget.isActive && widget.isActive;
    if (languageChanged || becameActive) {
      _checkSubscriptionAndFetch();
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    _heartAnimationController.dispose();
    super.dispose();
  }

  void _showActionModal({
    required String titleAr,
    required String titleEn,
    required String messageAr,
    required String messageEn,
    required IconData icon,
    Color iconColor = Colors.amber,
    required VoidCallback onUpgradePressed,
  }) async {
    if (_isModalActive || !mounted) return;
    _isModalActive = true;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (modalContext) => Directionality(
        textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          padding: EdgeInsets.fromLTRB(24, 24, 24, 24 + WesalBottomInsets.of(modalContext)),
          decoration: BoxDecoration(
            color: AppColors.cardColor,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
            border: Border.all(
                color: AppColors.lineColor),
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
                  color: iconColor.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                  border: Border.all(color: iconColor, width: 1.5),
                ),
                child: Icon(icon, color: iconColor, size: 48),
              ),
              const SizedBox(height: 16),
              Text(
                _isAr ? titleAr : titleEn,
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
                _isAr ? messageAr : messageEn,
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
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16)),
                  ),
                  icon: const Icon(Icons.workspace_premium_rounded,
                      color: Colors.amber, size: 22),
                  label: Text(
                    _isAr
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
                    onUpgradePressed();
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

  void _showMatchFilterModal() {
    _showActionModal(
      titleAr: 'تفعيل خوارزمية التطابق الذكي 🎯',
      titleEn: 'Unlock Smart Auto-Matching 🎯',
      messageAr:
          'ميزة تصفية الأعضاء بناءً على نسبة التطابق مخصصة للمشتركين. اشترك الآن في باقات وصال المميزة للوصول لنتائج توافق دقيقة!',
      messageEn:
          'Filtering profiles by match percentage is a premium feature. Subscribe now to get accurate matching results!',
      icon: Icons.auto_awesome_rounded,
      iconColor: AppColors.accentWarm,
      onUpgradePressed: widget.onUpgradeRequested,
    );
  }

  void _showLikeQuotaModal() async {
    if (_isModalActive || !mounted) return;
    _isModalActive = true;

    try {
      await AccountTab.showQuotaExceededModal(
        context,
        isArabic: _isAr,
      );
    } finally {
      _isModalActive = false;
    }
  }

  void _showMessageModal() {
    _showActionModal(
      titleAr: 'بدء المحادثات المباشرة 💬',
      titleEn: 'Start Direct Messaging 💬',
      messageAr:
          'المراسلة المباشرة ميزة حصريّة لأصحاب الحسابات المفعّلة. اشترك الآن لفتح الدردشة المباشرة والتواصل الفوري مع شريك حياتك!',
      messageEn:
          'Direct messaging is exclusive to subscribed accounts. Upgrade now to start direct messaging with your partner!',
      icon: Icons.chat_bubble_outline_rounded,
      iconColor: AppColors.accentWarm,
      onUpgradePressed: widget.onUpgradeRequested,
    );
  }

  Future<void> _checkSubscriptionAndFetch() async {
    if (!mounted) return;

    try {
      final profile = await ApiService.getCurrentUserProfile();
      if (mounted && profile != null) {
        bool isFemale = false;
        bool isSubscribed = false;

        try {
          final dynamic p = profile;
          if (p.isFemale == true) {
            isFemale = true;
          } else if (p.basicInfo != null && p.basicInfo.gender != null) {
            final String g = p.basicInfo.gender.toString().toLowerCase();
            isFemale = g.contains('female') ||
                g.contains('أنثى') ||
                g.contains('انثى');
          }
          if (p.isSubscribedActive == true) {
            isSubscribed = true;
          }
        } catch (_) {}

        setState(() {
          _isSubscribedOrFemale = isFemale || isSubscribed;
        });
      }
    } catch (_) {}

    if (mounted) {
      _fetchMatches();
    }
  }

  Future<void> _fetchMatches() async {
    if (!mounted) return;

    _fetchCounter++;
    final int currentRequestToken = _fetchCounter;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final members = await ApiService.getMatchedProfiles(
        matchEnabled: _isMatchFilterEnabled,
        lang: widget.currentLanguage,
      );

      if (mounted && currentRequestToken == _fetchCounter) {
        setState(() {
          _matchedMembers = members;
          _currentProfileIndex = 0;
          _isLoading = false;
        });

        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted && _pageController.hasClients) {
            _pageController.jumpToPage(0);
          }
        });
      }
    } catch (e) {
      if (mounted && currentRequestToken == _fetchCounter) {
        final matchingLocked = e.toString().contains('smart_matching_locked');
        setState(() {
          if (matchingLocked) {
            // The server is authoritative: a paid package may still lack the
            // smart-sort entitlement. Revert the switch instead of leaving it on.
            _isSubscribedOrFemale = false;
            _isMatchFilterEnabled = false;
            _matchedMembers = [];
            _errorMessage = _isAr
                ? 'ترتيب الأعضاء حسب التوافق غير مفعّل ضمن صلاحية حسابك الحالية.'
                : 'Compatibility sorting is not enabled for your current account.';
          } else {
            _errorMessage = widget.txt('matches_fetch_failed');
          }
          _isLoading = false;
        });

        if (matchingLocked && mounted && currentRequestToken == _fetchCounter) {
          // Keep the ordinary member list available when the account can browse
          // profiles but does not have the separate smart-sort entitlement.
          try {
            final members = await ApiService.getMatchedProfiles(
              matchEnabled: false,
              lang: widget.currentLanguage,
            );
            if (mounted && currentRequestToken == _fetchCounter) {
              setState(() {
                _matchedMembers = members;
                _isLoading = false;
              });
            }
          } catch (_) {
            // Preserve the localized entitlement notice and let Retry handle
            // transient list-loading errors.
          }
        }
      }
    }
  }

  void _navigateToProfile(MemberModel member) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => MemberProfileScreen(
          userId: member.id,
          currentLanguage: widget.currentLanguage,
          onUpgradeRequested: widget.onUpgradeRequested,
        ),
      ),
    );
  }

  void _startDirectChat(MemberModel member) {
    final thread = ChatThreadModel(
      id: member.id,
      userId: member.id,
      name: member.name,
      image: member.image,
      gender: '',
      lastMessage: '',
      time: _isAr ? 'الآن' : 'Now',
      unreadCount: 0,
      isOnline: false,
      lastSeen: '',
      packageName: member.packageName,
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

  void _triggerLikeAnimation(MemberModel member) async {
    if (_likingUserId != null || _likedUserIds.contains(member.id)) return;

    if (!_isSubscribedOrFemale && _likedUserIds.length >= 5) {
      _showLikeQuotaModal();
      return;
    }

    setState(() {
      _likingUserId = member.id;
      _likedUserIds.add(member.id);
      _showHeartOverlay = true;
    });
    _heartAnimationController.forward(from: 0.0);

    try {
      final Map<String, dynamic> response =
          await ApiService.expressInterest(member.id);
      final bool isSuccess = response['success'] == true;

      if (!mounted) return;

      if (!isSuccess) {
        final String status = response['status']?.toString() ?? '';
        final int code = response['code'] is int
            ? response['code']
            : int.tryParse(response['code']?.toString() ?? '0') ?? 0;

        if (status == 'already_expressed') {
          return;
        }

        setState(() {
          _likedUserIds.remove(member.id);
          _showHeartOverlay = false;
        });

        if (status == 'package_required' ||
            status == 'subscription_expired' ||
            status == 'forbidden' ||
            status == 'quota_exceeded' ||
            code == 403) {
          _showLikeQuotaModal();
        } else {
          final String errorMsg =
              response['message']?.toString() ?? widget.txt('like_failed');
          if (mounted) {
            AppSnackBar.show(
              context: context,
              type: NotificationType.error,
              isArabic: _isAr,
              titleAr: 'تعذّر إرسال طلب الاهتمام',
              titleEn: 'Could not send interest',
              messageAr: errorMsg,
              messageEn: 'Your interest request was not sent. Please try again.',
              detailsAr: 'لم يتم تسجيل الإعجاب على هذا الملف.',
              detailsEn: 'No interest was recorded for this profile.',
            );
          }
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _likedUserIds.remove(member.id);
          _showHeartOverlay = false;
        });
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isAr,
          titleAr: 'تعذّر الاتصال',
          titleEn: 'Connection problem',
          messageAr: 'لم نتمكن من إرسال طلب الاهتمام حالياً.',
          messageEn: 'We could not send your interest request right now.',
          detailsAr: 'تحقق من اتصال الإنترنت ثم حاول مجدداً.',
          detailsEn: 'Check your internet connection and try again.',
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _likingUserId = null;
        });
      }
    }
  }

  Widget _buildLockOverlayView() {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 20.0),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
            child: Container(
              padding: const EdgeInsets.all(28.0),
              decoration: BoxDecoration(
                color: AppColors.cardColor,
                borderRadius: BorderRadius.circular(28),
                border: Border.all(
                  color: AppColors.lineColor,
                  width: 1.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primaryTeal.withValues(alpha: 0.08),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: AppColors.accentLight.withValues(alpha: 0.22),
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: AppColors.accentWarm,
                        width: 1.5,
                      ),
                    ),
                    child: const Icon(
                      Icons.auto_awesome_rounded,
                      color: AppColors.accentWarm,
                      size: 48,
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    _isAr
                        ? 'ميزة التطابق الذكي مقفلة للحسابات المجانية 💎'
                        : 'Auto-Matching Feature Locked 💎',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: AppColors.textDark,
                      fontSize: 19,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    _isAr
                        ? 'اشترك في إحدى باقات وصال المميزة للحصول على خوارزميات التوافق المتقدمة واكتشاف أنسب شريك لك!'
                        : 'Subscribe to a premium plan to unlock advanced profile matching algorithms!',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 14,
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
                          borderRadius: BorderRadius.circular(16),
                        ),
                        elevation: 6,
                        shadowColor:
                            AppColors.primaryTeal.withValues(alpha: 0.18),
                      ),
                      icon: const Icon(Icons.workspace_premium_rounded,
                          color: Colors.amber, size: 22),
                      label: Text(
                        _isAr ? 'تفعيل الباقة الآن 🚀' : 'Activate Plan Now 🚀',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 15,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                      ),
                      onPressed: _showMatchFilterModal,
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

  Widget _buildCompactHeader(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [AppColors.primaryTeal, AppColors.secondaryTeal],
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                  ),
                  borderRadius: BorderRadius.circular(15),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primaryTeal.withValues(alpha: 0.15),
                      blurRadius: 12,
                      offset: const Offset(0, 5),
                    ),
                  ],
                ),
                child: const Icon(Icons.favorite_rounded,
                    color: AppColors.accentLight, size: 23),
              ),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _isAr ? 'اكتشف من يشبهك' : 'Discover your connection',
                      style: const TextStyle(
                        color: AppColors.textDark,
                        fontSize: 17,
                        fontWeight: FontWeight.w900,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    Text(
                      _isAr
                          ? 'مساحة للتعارف الجاد والتوافق الحقيقي'
                          : 'Meaningful connections and real compatibility',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 10.5,
                        fontFamily: 'Cairo',
                      ),
                    ),
                  ],
                ),
              ),
              Material(
                color: AppColors.cardColor,
                shape: const CircleBorder(),
                child: InkWell(
                  onTap: widget.onNotificationPressed,
                  customBorder: const CircleBorder(),
                  child: SizedBox(
                    width: 43,
                    height: 43,
                    child: Stack(
                      alignment: Alignment.center,
                      children: [
                        const Icon(Icons.notifications_none_rounded,
                            color: AppColors.primaryTeal, size: 23),
                        if (widget.unreadNotificationCount > 0)
                          Positioned(
                            top: 5,
                            right: 5,
                            child: Container(
                              padding: const EdgeInsets.all(3),
                              decoration: const BoxDecoration(
                                color: AppColors.error,
                                shape: BoxShape.circle,
                              ),
                              child: Text(
                                '${widget.unreadNotificationCount}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 8,
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
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
            decoration: BoxDecoration(
              color: AppColors.cardColor,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: AppColors.lineColor),
            ),
            child: Row(
              children: [
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: AppColors.accentLight.withValues(alpha: 0.30),
                    borderRadius: BorderRadius.circular(11),
                  ),
                  child: const Icon(Icons.auto_awesome_rounded,
                      color: AppColors.primaryTeal, size: 18),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          color: AppColors.textDark,
                          fontSize: 12.5,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                      ),
                      Text(
                        _isAr
                            ? 'تعرّف على ملفات جديدة تم اختيارها لك'
                            : 'Explore new profiles selected for you',
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 10,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.favorite_border_rounded,
                    color: AppColors.accentWarm, size: 20),
              ],
            ),
          ),
          const SizedBox(height: 9),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 5),
            decoration: BoxDecoration(
              color: _isMatchFilterEnabled
                  ? AppColors.accentLight.withValues(alpha: 0.24)
                  : AppColors.cardColor,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: _isMatchFilterEnabled
                    ? AppColors.accentWarm
                    : AppColors.lineColor,
              ),
            ),
            child: Row(
              children: [
                const Icon(Icons.auto_awesome_rounded,
                    color: AppColors.primaryTeal, size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    _isAr
                        ? 'ترتيب حسب نسبة التوافق'
                        : 'Sort by compatibility score',
                    style: const TextStyle(
                      color: AppColors.textDark,
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      fontFamily: 'Cairo',
                    ),
                  ),
                ),
                Transform.scale(
                  scale: 0.85,
                  child: Switch(
                    value: _isMatchFilterEnabled,
                    activeThumbColor: Colors.white,
                    activeTrackColor: AppColors.primaryTeal,
                    inactiveThumbColor: AppColors.textLight,
                    inactiveTrackColor: AppColors.lineColor,
                    onChanged: (value) {
                      if (!_isSubscribedOrFemale && value) {
                        _showMatchFilterModal();
                        return;
                      }
                      setState(() => _isMatchFilterEnabled = value);
                      _fetchMatches();
                    },
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSwipeableProfileCard(MemberModel member) {
    final String locationText = member.getFormattedLocation(isArabic: _isAr);
    final String maritalStatusText =
        member.getLocalizedMaritalStatus(isArabic: _isAr);
    final String occupationText =
        member.getLocalizedOccupation(isArabic: _isAr);

    final bool hasMatchBadge = member.matchStatus != 'not_eligible';
    final String matchLabel = member.matchPercentage != null
        ? '${member.matchPercentage}% ${_isAr ? 'توافق' : 'Match'}'
        : (member.matchStatus == 'no_preferences'
            ? (_isAr ? 'أكمل تفضيلاتك' : 'Complete preferences')
            : (_isAr ? 'غير محسوبة' : 'Not calculated'));

    return GestureDetector(
      onTap: () => _navigateToProfile(member),
      onDoubleTap: () => _triggerLikeAnimation(member),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 5),
        child: Container(
          decoration: BoxDecoration(
            color: AppColors.cardColor,
            borderRadius: BorderRadius.circular(30),
            border: Border.all(color: AppColors.lineColor, width: 1),
            boxShadow: [
              BoxShadow(
                color: AppColors.primaryTeal.withValues(alpha: 0.10),
                blurRadius: 22,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            children: [
              Expanded(
                flex: 7,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    CachedNetworkImage(
                      imageUrl: member.image,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(
                        color: AppColors.sectionBackground,
                        child: const Center(
                          child: CircularProgressIndicator(
                            color: AppColors.primaryTeal,
                            strokeWidth: 2,
                          ),
                        ),
                      ),
                      errorWidget: (context, url, error) => Container(
                        color: AppColors.sectionBackground,
                        child: const Icon(
                          Icons.person_rounded,
                          size: 100,
                          color: AppColors.primaryTeal,
                        ),
                      ),
                    ),
                    Positioned(
                      top: 0,
                      left: 0,
                      right: 0,
                      height: 90,
                      child: IgnorePointer(
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                AppColors.primaryTeal.withValues(alpha: 0.25),
                                Colors.transparent,
                              ],
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                            ),
                          ),
                        ),
                      ),
                    ),
                    if (hasMatchBadge)
                      Positioned(
                        top: 14,
                        right: _isAr ? 14 : null,
                        left: _isAr ? null : 14,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.cardColor.withValues(alpha: 0.97),
                            borderRadius: BorderRadius.circular(18),
                            border: Border.all(color: AppColors.lineColor),
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.textDark.withValues(alpha: 0.12),
                                blurRadius: 12,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                member.matchPercentage == null
                                    ? Icons.info_outline_rounded
                                    : Icons.auto_awesome_rounded,
                                color: AppColors.primaryTeal,
                                size: 15,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                matchLabel,
                                style: const TextStyle(
                                  color: AppColors.textDark,
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w900,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    if (_showHeartOverlay)
                      Center(
                        child: FadeTransition(
                          opacity: _heartOpacityAnimation,
                          child: ScaleTransition(
                            scale: _heartScaleAnimation,
                            child: Container(
                              padding: const EdgeInsets.all(25),
                              decoration: BoxDecoration(
                                color: AppColors.cardColor.withValues(alpha: 0.90),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.favorite_rounded,
                                color: AppColors.accentWarm,
                                size: 82,
                              ),
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(16, 13, 16, 14),
                decoration: const BoxDecoration(
                  color: AppColors.cardColor,
                  border: Border(
                    top: BorderSide(color: AppColors.lineColor),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            member.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 22,
                              fontWeight: FontWeight.w900,
                              fontFamily: 'Cairo',
                            ),
                          ),
                        ),
                        if (member.packageName.isNotEmpty) ...[
                          const SizedBox(width: 7),
                          PackageBadgeWidget(
                            packageName: member.packageName,
                            compact: true,
                            isArabic: _isAr,
                          ),
                        ],
                      ],
                    ),
                    if (member.matchCriteriaCount > 0 &&
                        member.matchPercentage != null) ...[
                      const SizedBox(height: 3),
                      Text(
                        _isAr
                            ? 'تم تقييم ${member.matchEvaluatedCriteriaCount} من ${member.matchCriteriaCount} معياراً'
                            : '${member.matchEvaluatedCriteriaCount} of ${member.matchCriteriaCount} criteria evaluated',
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 10,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ],
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 7,
                      runSpacing: 7,
                      children: [
                        if (member.age > 0)
                          _buildInfoPillChip(
                            icon: Icons.cake_rounded,
                            label: '${member.age} ${_isAr ? 'سنة' : 'yrs'}',
                          ),
                        if (maritalStatusText.isNotEmpty)
                          _buildInfoPillChip(
                            icon: Icons.favorite_rounded,
                            label: maritalStatusText,
                            iconColor: AppColors.accentWarm,
                          ),
                        if (locationText.isNotEmpty)
                          _buildInfoPillChip(
                            icon: Icons.location_on_rounded,
                            label: locationText,
                          ),
                        if (occupationText.isNotEmpty &&
                            occupationText != 'غير محدد' &&
                            occupationText != 'Unspecified' &&
                            occupationText != 'Not Specified')
                          _buildInfoPillChip(
                            icon: Icons.work_rounded,
                            label: occupationText,
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildActionItem({
    required IconData icon,
    required String label,
    required Color backgroundColor,
    required Color iconColor,
    required VoidCallback? onTap,
    Color borderColor = AppColors.lineColor,
    bool prominent = false,
  }) {
    final double size = prominent ? 62 : 56;

    return Expanded(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(size),
              child: Ink(
                width: size,
                height: size,
                decoration: BoxDecoration(
                  color: backgroundColor,
                  shape: BoxShape.circle,
                  border: Border.all(color: borderColor, width: 1.2),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.textDark.withValues(alpha: 0.07),
                      blurRadius: 12,
                      offset: const Offset(0, 5),
                    ),
                  ],
                ),
                child: Icon(icon, color: iconColor, size: prominent ? 25 : 22),
              ),
            ),
          ),
          const SizedBox(height: 7),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 3),
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 11.5,
                fontWeight: FontWeight.w700,
                fontFamily: 'Cairo',
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDirectActionButtons() {
    if (_matchedMembers.isEmpty ||
        _currentProfileIndex < 0 ||
        _currentProfileIndex >= _matchedMembers.length) {
      return const SizedBox.shrink();
    }

    final member = _matchedMembers[_currentProfileIndex];
    final bool isLiked = _likedUserIds.contains(member.id);

    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 4, 18, 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildActionItem(
            icon: _isAr
                ? Icons.arrow_back_ios_new_rounded
                : Icons.arrow_forward_ios_rounded,
            label: widget.txt('next'),
            backgroundColor: AppColors.sectionBackground,
            iconColor: AppColors.primaryTeal,
            borderColor: AppColors.lineColor,
            onTap: () {
              if (!_pageController.hasClients) return;
              if (_currentProfileIndex < _matchedMembers.length - 1) {
                _pageController.nextPage(
                  duration: const Duration(milliseconds: 400),
                  curve: Curves.easeOutCubic,
                );
              } else {
                _pageController.animateToPage(
                  0,
                  duration: const Duration(milliseconds: 600),
                  curve: Curves.easeInOutCubic,
                );
              }
            },
          ),
          _buildActionItem(
            icon: isLiked
                ? Icons.check_circle_rounded
                : Icons.favorite_rounded,
            label: isLiked
                ? widget.txt('liked_already')
                : widget.txt('express_interest'),
            backgroundColor: isLiked
                ? AppColors.sectionBackground
                : AppColors.accentLight.withValues(alpha: 0.42),
            iconColor: isLiked ? AppColors.textMuted : AppColors.primaryTeal,
            borderColor: isLiked ? AppColors.lineColor : AppColors.accentWarm,
            prominent: true,
            onTap: isLiked ? null : () => _triggerLikeAnimation(member),
          ),
          _buildActionItem(
            icon: Icons.chat_bubble_rounded,
            label: widget.txt('message_btn'),
            backgroundColor: AppColors.primaryTeal,
            iconColor: Colors.white,
            borderColor: AppColors.primaryTeal,
            onTap: () {
              if (!_isSubscribedOrFemale) {
                _showMessageModal();
              } else {
                _startDirectChat(member);
              }
            },
          ),
        ],
      ),
    );
  }

  Widget _buildInfoPillChip({
    required IconData icon,
    required String label,
    Color? iconColor,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.sectionBackground,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.lineColor),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: iconColor ?? AppColors.primaryTeal, size: 14),
          const SizedBox(width: 5),
          Text(
            label,
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 11.5,
              fontWeight: FontWeight.w700,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final double screenHeight = MediaQuery.sizeOf(context).height;

    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: Column(
        children: [
          _buildCompactHeader(widget.txt('title')),
          const SizedBox(height: 6),
          Expanded(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(color: AppColors.accentWarm))
                : !_isSubscribedOrFemale && _isMatchFilterEnabled
                    ? _buildLockOverlayView()
                    : _errorMessage != null
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  _errorMessage!,
                                  style: const TextStyle(color: AppColors.textDark, fontSize: 15, fontFamily: 'Cairo'),
                                  textAlign: TextAlign.center,
                                ),
                                const SizedBox(height: 12),
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.primaryTeal,
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(
                                        borderRadius:
                                            BorderRadius.circular(16)),
                                  ),
                                  onPressed: _checkSubscriptionAndFetch,
                                  child: Text(widget.txt('retry'),
                                      style:
                                          const TextStyle(fontFamily: 'Cairo')),
                                )
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _checkSubscriptionAndFetch,
                            color: AppColors.accentWarm,
                            child: _matchedMembers.isEmpty
                                ? ListView(
                                    physics:
                                        const AlwaysScrollableScrollPhysics(),
                                    children: [
                                      SizedBox(
                                        height: screenHeight * 0.5,
                                        child: Center(
                                          child: Text(
                                            widget.txt(_isMatchFilterEnabled ? 'no_smart_matches' : 'no_data'),
                                            style: const TextStyle(color: AppColors.textMuted, fontSize: 16, fontFamily: 'Cairo'),
                                          ),
                                        ),
                                      ),
                                    ],
                                  )
                                : PageView.builder(
                                    controller: _pageController,
                                    physics: const BouncingScrollPhysics(),
                                    onPageChanged: (index) {
                                      setState(() {
                                        _currentProfileIndex = index;
                                      });
                                    },
                                    itemCount: _matchedMembers.length,
                                    itemBuilder: (context, index) {
                                      final member = _matchedMembers[index];
                                      return _buildSwipeableProfileCard(member);
                                    },
                                  ),
                          ),
          ),
          const SizedBox(height: 10),
          if (!_isLoading && _matchedMembers.isNotEmpty)
            _buildDirectActionButtons(),
          const SizedBox(height: 4),
        ],
      ),
    );
  }
}

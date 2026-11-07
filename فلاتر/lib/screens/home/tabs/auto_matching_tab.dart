// ============================================================================
// اسم الملف: lib/screens/home/tabs/auto_matching_tab.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: تبويب المطابقات التلقائية والبطاقات الخارجية مع دعم شارات الباقات الموحدة والترجمة الديناميكية الكاملة لكافة الحقول.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../constants/colors.dart';

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
      backgroundColor: Colors.transparent,
      builder: (modalContext) => Directionality(
        textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          padding: const EdgeInsets.all(24),
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
                      color: AppColors.textDark,
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
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(errorMsg),
                backgroundColor: Colors.redAccent,
              ),
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(widget.txt('like_failed')),
            backgroundColor: Colors.redAccent,
          ),
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
                            const Color(0xFF0288D1).withValues(alpha: 0.5),
                      ),
                      icon: const Icon(Icons.workspace_premium_rounded,
                          color: Colors.amber, size: 22),
                      label: Text(
                        _isAr ? 'تفعيل الباقة الآن 🚀' : 'Activate Plan Now 🚀',
                        style: const TextStyle(
                          color: AppColors.textDark,
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
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 4.0),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                decoration: BoxDecoration(
                  color: AppColors.cardColor,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: AppColors.lineColor),
                ),
                child: Row(children: [
                  Container(width: 8, height: 8, decoration: const BoxDecoration(
                    color: AppColors.accentWarm, shape: BoxShape.circle)),
                  const SizedBox(width: 8),
                  Text(title, style: const TextStyle(color: AppColors.primaryTeal,
                    fontSize: 15, fontWeight: FontWeight.bold, fontFamily: 'Cairo')),
                ]),
              ),
              Container(
                decoration: BoxDecoration(color: AppColors.cardColor, shape: BoxShape.circle,
                  border: Border.all(color: AppColors.lineColor)),
                child: Stack(alignment: Alignment.center, children: [
                  IconButton(
                    constraints: const BoxConstraints(minWidth: 40, minHeight: 40),
                    padding: EdgeInsets.zero,
                    icon: const Icon(Icons.notifications_none_rounded,
                      color: AppColors.primaryTeal, size: 22),
                    onPressed: widget.onNotificationPressed,
                  ),
                  if (widget.unreadNotificationCount > 0)
                    Positioned(top: 6, right: 6, child: Container(
                      padding: const EdgeInsets.all(3),
                      decoration: const BoxDecoration(color: AppColors.error, shape: BoxShape.circle),
                      child: Text('${widget.unreadNotificationCount}',
                        style: const TextStyle(color: Colors.white, fontSize: 9,
                          fontWeight: FontWeight.bold, fontFamily: 'Cairo')),
                    )),
                ]),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 5),
            decoration: BoxDecoration(
              color: _isMatchFilterEnabled
                  ? AppColors.accentLight.withValues(alpha: 0.24)
                  : AppColors.cardColor,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: _isMatchFilterEnabled
                  ? AppColors.accentWarm : AppColors.lineColor),
            ),
            child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Row(children: [
                Icon(_isMatchFilterEnabled ? Icons.auto_awesome_rounded : Icons.people_alt_rounded,
                  color: _isMatchFilterEnabled ? AppColors.accentWarm : AppColors.primaryTeal, size: 18),
                const SizedBox(width: 8),
                Text(_isAr ? 'ترتيب حسب نسبة التوافق' : 'Sort by compatibility score',
                  style: TextStyle(color: AppColors.textDark, fontSize: 13,
                    fontWeight: _isMatchFilterEnabled ? FontWeight.bold : FontWeight.normal,
                    fontFamily: 'Cairo')),
              ]),
              Transform.scale(scale: 0.85, child: Switch(
                value: _isMatchFilterEnabled,
                activeThumbColor: Colors.white,
                activeTrackColor: AppColors.primaryTeal,
                inactiveThumbColor: AppColors.textLight,
                inactiveTrackColor: AppColors.lineColor,
                onChanged: (val) {
                  if (!_isSubscribedOrFemale && val) {
                    _showMatchFilterModal();
                    return;
                  }
                  setState(() => _isMatchFilterEnabled = val);
                  _fetchMatches();
                },
              )),
            ]),
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

    return GestureDetector(
      onTap: () => _navigateToProfile(member),
      onDoubleTap: () => _triggerLikeAnimation(member),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 6.0, vertical: 4.0),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(32),
            border: Border.all(
              color: AppColors.lineColor,
              width: 1,
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primaryTeal.withValues(alpha: 0.09),
                blurRadius: 18,
                spreadRadius: 0,
              ),
              BoxShadow(
                color: AppColors.accentWarm.withValues(alpha: 0.07),
                blurRadius: 22,
                spreadRadius: 0,
              ),
              BoxShadow(
                color: AppColors.textDark.withValues(alpha: 0.12),
                blurRadius: 22,
                offset: const Offset(0, 7),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(30),
            child: Stack(
              children: [
                Positioned.fill(
                  child: CachedNetworkImage(
                    imageUrl: member.image,
                    fit: BoxFit.cover,
                    placeholder: (context, url) => Container(
                      color: AppColors.sectionBackground,
                      child: const Center(
                        child: CircularProgressIndicator(
                          color: AppColors.accentWarm,
                          strokeWidth: 2,
                        ),
                      ),
                    ),
                    errorWidget: (context, url, error) => Container(
                      color: AppColors.sectionBackground,
                      child: const Icon(Icons.person,
                          size: 110, color: AppColors.primaryTeal),
                    ),
                  ),
                ),
                Positioned.fill(
                  child: Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Colors.transparent,
                          Colors.black.withValues(alpha: 0.15),
                          Colors.black.withValues(alpha: 0.90),
                        ],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        stops: const [0.35, 0.65, 1.0],
                      ),
                    ),
                  ),
                ),
                if (member.matchStatus != 'not_eligible')
                  Positioned(
                    top: 16,
                    left: _isAr ? null : 16,
                    right: _isAr ? 16 : null,
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(20),
                      child: BackdropFilter(
                        filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 7,
                          ),
                          decoration: BoxDecoration(
                            color: member.matchPercentage == null
                                ? AppColors.textDark.withValues(alpha: 0.90)
                                : null,
                            gradient: member.matchPercentage == null
                                ? null
                                : const LinearGradient(
                                    colors: [
                                      AppColors.accentWarm,
                                      AppColors.primaryTeal,
                                    ],
                                  ),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(
                              color: member.matchPercentage == null
                                  ? Colors.white24
                                  : Colors.white,
                              width: 1.2,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: (member.matchPercentage == null
                                        ? Colors.black
                                        : AppColors.accentWarm)
                                    .withValues(alpha: 0.35),
                                blurRadius: 10,
                              ),
                            ],
                          ),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    member.matchPercentage == null
                                        ? Icons.info_outline_rounded
                                        : Icons.auto_awesome_rounded,
                                    color: member.matchPercentage == null
                                        ? Colors.white70
                                         : AppColors.accentLight,
                                    size: 15,
                                  ),
                                  const SizedBox(width: 5),
                                  Text(
                                    member.matchPercentage != null
                                        ? '${member.matchPercentage}% ${_isAr ? 'توافق' : 'Match'}'
                                        : (member.matchStatus == 'no_preferences'
                                            ? (_isAr
                                                ? 'أكمل تفضيلاتك'
                                                : 'Complete preferences')
                                            : (_isAr
                                                ? 'غير محسوبة'
                                                : 'Not calculated')),
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                                ],
                              ),
                              if (member.matchCriteriaCount > 0) ...[
                                const SizedBox(height: 2),
                                Text(
                                  _isAr
                                      ? 'المعايير ${member.matchEvaluatedCriteriaCount}/${member.matchCriteriaCount}'
                                      : 'Criteria ${member.matchEvaluatedCriteriaCount}/${member.matchCriteriaCount}',
                                  style: const TextStyle(
                                    color: Colors.white70,
                                    fontSize: 9,
                                    fontWeight: FontWeight.w600,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                Positioned(
                  bottom: 20,
                  right: 18,
                  left: 18,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              member.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 26,
                                fontWeight: FontWeight.w900,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ),
                          // 🟢 استبدال الدالة المحلية باستدعاء الودجت الموحد PackageBadgeWidget
                          if (member.packageName.isNotEmpty) ...[
                            const SizedBox(width: 8),
                            PackageBadgeWidget(
                              packageName: member.packageName,
                              compact: true,
                              isArabic: _isAr,
                            ),
                          ],
                        ],
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 6,
                        runSpacing: 6,
                        children: [
                          if (member.age > 0)
                            _buildInfoPillChip(
                              icon: Icons.cake_rounded,
                              label: '${member.age} ${_isAr ? 'سنة' : 'yrs'}',
                              iconColor: AppColors.accentLight,
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
                              iconColor: AppColors.secondaryTeal,
                            ),
                          if (occupationText.isNotEmpty &&
                              occupationText != 'غير محدد' &&
                              occupationText != 'Unspecified' &&
                              occupationText != 'Not Specified')
                            _buildInfoPillChip(
                              icon: Icons.work_rounded,
                              label: occupationText,
                              iconColor: AppColors.accentLight,
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
                if (_showHeartOverlay)
                  Center(
                    child: FadeTransition(
                      opacity: _heartOpacityAnimation,
                      child: ScaleTransition(
                        scale: _heartScaleAnimation,
                        child: Container(
                          padding: const EdgeInsets.all(28),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: RadialGradient(
                              colors: [
                                Colors.redAccent.withValues(alpha: 0.7),
                                Colors.transparent,
                              ],
                            ),
                          ),
                          child: const Icon(
                            Icons.favorite_rounded,
                            color: Colors.redAccent,
                            size: 110,
                          ),
                        ),
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

  Widget _buildDirectActionButtons() {
    if (_matchedMembers.isEmpty ||
        _currentProfileIndex < 0 ||
        _currentProfileIndex >= _matchedMembers.length) {
      return const SizedBox.shrink();
    }

    final member = _matchedMembers[_currentProfileIndex];
    final bool isLiked = _likedUserIds.contains(member.id);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          InkWell(
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
            borderRadius: BorderRadius.circular(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.18),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.45),
                      width: 1.2,
                    ),
                  ),
                  child: Icon(
                    _isAr
                        ? Icons.arrow_back_ios_new_rounded
                        : Icons.arrow_forward_ios_rounded,
                    color: Colors.white,
                    size: 20,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  widget.txt('next'),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
          InkWell(
            onTap: isLiked
                ? null
                : () {
                    if (_matchedMembers.isNotEmpty) {
                      _triggerLikeAnimation(member);
                    }
                  },
            borderRadius: BorderRadius.circular(26),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 22, vertical: 12),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: isLiked
                          ? [Colors.grey.shade600, Colors.grey.shade800]
                          : const [AppColors.accentWarm, AppColors.accentLight],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(26),
                    border: Border.all(
                      color:
                          Colors.white.withValues(alpha: isLiked ? 0.3 : 0.85),
                      width: 1.2,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        isLiked
                            ? Icons.how_to_reg_rounded
                            : Icons.favorite_rounded,
                        color: isLiked ? Colors.white70 : Colors.redAccent,
                        size: 22,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        isLiked
                            ? widget.txt('liked_already')
                            : widget.txt('express_interest'),
                        style: TextStyle(
                          color: isLiked ? Colors.white70 : Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  widget.txt('like_btn'),
                  style: TextStyle(
                    color: isLiked ? Colors.transparent : Colors.white70,
                    fontSize: 11,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
          InkWell(
            onTap: () {
              if (!_isSubscribedOrFemale) {
                _showMessageModal();
              } else {
                _startDirectChat(member);
              }
            },
            borderRadius: BorderRadius.circular(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.18),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: AppColors.accentLight.withValues(alpha: 0.85),
                      width: 1.2,
                    ),
                  ),
                  child: const Icon(
                    Icons.chat_bubble_rounded,
                    color: AppColors.accentLight,
                    size: 20,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  widget.txt('message_btn'),
                  style: const TextStyle(
                    color: Colors.amberAccent,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
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

  Widget _buildInfoPillChip({
    required IconData icon,
    required String label,
    Color? iconColor,
  }) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(14),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 8, sigmaY: 8),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: Colors.black.withValues(alpha: 0.35),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
                color: Colors.white.withValues(alpha: 0.35), width: 1),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, color: iconColor ?? const Color(0xFF4FC3F7), size: 14),
              const SizedBox(width: 5),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  fontFamily: 'Cairo',
                ),
              ),
            ],
          ),
        ),
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
                                  style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 15,
                                      fontFamily: 'Cairo'),
                                  textAlign: TextAlign.center,
                                ),
                                const SizedBox(height: 12),
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.white,
                                    foregroundColor: const Color(0xFF0288D1),
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
                                            style: const TextStyle(
                                                color: Colors.white,
                                                fontSize: 16,
                                                fontFamily: 'Cairo'),
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

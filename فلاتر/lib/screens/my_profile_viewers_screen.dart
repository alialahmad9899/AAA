// ============================================================================
// اسم الملف: lib/screens/my_profile_viewers_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة عرض زوار الملف الشخصي مع تخصيص الصلاحيات (3 مشاهدات مجانية للشباب،
//        ومفتوح بالكامل مجاناً للبنات) مع الربط المباشر بباقات الترقية.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import '../constants/colors.dart';
import 'package:cached_network_image/cached_network_image.dart';

import '../models/member_profile_model.dart';
import '../services/api_service.dart';
import 'member_profile_screen.dart';
import 'home/tabs/account_tab.dart';

class MyProfileViewersScreen extends StatefulWidget {
  final String currentLanguage;

  const MyProfileViewersScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<MyProfileViewersScreen> createState() => _MyProfileViewersScreenState();
}

class _MyProfileViewersScreenState extends State<MyProfileViewersScreen> {
  bool _isLoading = true;
  bool _hasQuotaError = false;
  String _quotaErrorMessage = '';
  bool _hasLoadError = false;
  String _loadErrorMessage = '';
  List<dynamic> _viewers = [];
  bool _isFemale = false;
  bool _hasActivePackage = false;

  bool get _isAr => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _fetchViewers();
  }

  /// 🟢 جلب بيانات الزوار مع التحقق من جنس المستخدم وحالة باقة الاشتراك
  Future<void> _fetchViewers() async {
    setState(() {
      _isLoading = true;
      _hasQuotaError = false;
      _hasLoadError = false;
    });

    try {
      // 1. جلب ملف المستخدم الحالي لمعرفة الجنس (ذكر/أنثى) وحالة الاشتراك
      final MemberProfileModel? currentUser =
          await ApiService.getCurrentUserProfile(
        lang: widget.currentLanguage,
      );

      if (currentUser != null) {
        _isFemale = currentUser.isFemale;
        // 🟢 تم التصحيح لاستخدام isSubscribedActive من الموديل الرئيسي
        _hasActivePackage = currentUser.isSubscribedActive;
      }

      // 2. طلب قائمة زوار البروفايل من السيرفر
      final res = await ApiService.getProfileViewers();

      if (!mounted) return;

      // الإناث لديهن وصول مجاني مفتوح دائماً
      if (res['status'] == 'quota_exceeded') {
        setState(() {
          _isLoading = false;
          _hasQuotaError = true;
          _hasLoadError = false;
          _quotaErrorMessage = res['message'] ??
              (_isAr
                  ? 'استنفدت حصة مشاهدة الزوار المجانية (3 مشاهدات للشباب). اشترك الآن في إحدى باقات وصال المميزة لمشاهدة كافة الزوار.'
                  : 'You have used your 3 free profile views. Upgrade to Wesal Premium to see all visitors.');
        });
      } else if (res['result'] != true || res['data'] is! List) {
        setState(() {
          _isLoading = false;
          _hasQuotaError = false;
          _hasLoadError = true;
          _loadErrorMessage = res['message']?.toString() ??
              (_isAr ? 'تعذر تحميل زوار الملف الشخصي' : 'Could not load profile visitors');
        });
      } else {
        setState(() {
          _viewers = res['data'];
          _isLoading = false;
          _hasQuotaError = false;
          _hasLoadError = false;
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _hasQuotaError = false;
        _hasLoadError = true;
        _loadErrorMessage = _isAr
            ? 'تعذر الاتصال بالسيرفر لتحميل زوار الملف الشخصي'
            : 'Could not connect to load profile visitors';
      });
    }
  }

  /// 🟢 فتح نافذة باقات الاشتراك المباشرة عند الضغط على الترقية
  void _showUpgradeModal() {
    AccountTab.showSubscriptionsModalDirectly(
      context,
      isArabic: _isAr,
      onSubscriptionUpdated: () {
        _fetchViewers();
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    // تحديد الصلاحيات: مفتوح بالكامل للبنات أو الحسابات المفعّلة، ومحدد بـ 3 للشباب المجاني
    final bool isUnlimited = _isFemale || _hasActivePackage;
    const int maxFreeViews = 3;
    final bool hasMoreLocked = !isUnlimited && _viewers.length > maxFreeViews;
    final int visibleCount = isUnlimited
        ? _viewers.length
        : (_viewers.length > maxFreeViews ? maxFreeViews : _viewers.length);

    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: Scaffold(
        backgroundColor: AppColors.backgroundColor,
        appBar: AppBar(
          backgroundColor: AppColors.cardColor,
          elevation: 0,
          leading: IconButton(
            icon: Icon(
              _isAr
                  ? Icons.arrow_back_ios_new_rounded
                  : Icons.arrow_back_rounded,
              color: AppColors.textDark,
            ),
            onPressed: () => Navigator.pop(context),
          ),
          title: Text(
            _isAr ? 'من زار ملفي الشخصي 👁️' : 'Profile Visitors 👁️',
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 18,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
          centerTitle: true,
        ),
        body: Container(
          width: double.infinity,
          height: double.infinity,
          decoration: const BoxDecoration(
            color: AppColors.backgroundColor,
          ),
          child: SafeArea(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(color: AppColors.primaryTeal),
                  )
                : _hasQuotaError
                    ? _buildQuotaErrorView()
                    : _hasLoadError
                        ? _buildLoadErrorView()
                        : _viewers.isEmpty
                        ? _buildEmptyStateView()
                        : RefreshIndicator(
                            onRefresh: _fetchViewers,
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: visibleCount + (hasMoreLocked ? 1 : 0),
                              itemBuilder: (context, index) {
                                if (hasMoreLocked && index == visibleCount) {
                                  return _buildLockedMoreCard(
                                      _viewers.length - maxFreeViews);
                                }
                                final item = _viewers[index];
                                return _buildViewerCard(item);
                              },
                            ),
                          ),
          ),
        ),
      ),
    );
  }

  /// 🔒 كارت الترقية الزجاجي للشباب في حال وجود زوار أكثر من المسموح (3 زوار)
  Widget _buildLockedMoreCard(int lockedCount) {
    return Container(
      margin: const EdgeInsets.only(top: 8, bottom: 20),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.cardColor,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(
                color: AppColors.warning.withValues(alpha: 0.5),
                width: 1.5,
              ),
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppColors.warning.withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.lock_rounded,
                        color: AppColors.warning,
                        size: 28,
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _isAr
                                ? 'هناك +$lockedCount زوار آخرين! 👁️'
                                : '+$lockedCount More Visitors! 👁️',
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _isAr
                                ? 'استنفدت المشاهدات الـ3 المجانية للشباب. اشترك الآن لرؤية جميع الزوار بدون قيود.'
                                : 'You have reached the 3 free views limit for male accounts. Upgrade to see all visitors.',
                            style: const TextStyle(
                              color: AppColors.textMuted,
                              fontSize: 12.5,
                              fontFamily: 'Cairo',
                              height: 1.35,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primaryTeal,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      elevation: 4,
                    ),
                    icon: const Icon(Icons.stars_rounded,
                        color: AppColors.warning, size: 20),
                    label: Text(
                      _isAr
                          ? 'ترقية الحساب وكشف كافة الزوار 🚀'
                          : 'Upgrade Now to See All 🚀',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    onPressed: _showUpgradeModal,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// 🛑 واجهة الخطأ عند تجاوز الحصة من السيرفر
  Widget _buildQuotaErrorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
            child: Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: AppColors.cardColor,
                borderRadius: BorderRadius.circular(28),
                border: Border.all(
                  color: AppColors.warning.withValues(alpha: 0.5),
                  width: 1.5,
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.warning.withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.workspace_premium_rounded,
                      color: AppColors.warning,
                      size: 48,
                    ),
                  ),
                  const SizedBox(height: 18),
                  Text(
                    _isAr
                        ? 'ميزة حصرية بالباقات المميزة 💎'
                        : 'Premium Feature 💎',
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
                    _quotaErrorMessage,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 13.5,
                      fontFamily: 'Cairo',
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 22),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryTeal,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      icon: const Icon(Icons.flash_on_rounded,
                          color: AppColors.warning),
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
                      onPressed: _showUpgradeModal,
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

  Widget _buildLoadErrorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_rounded,
                color: AppColors.textMuted, size: 48),
            const SizedBox(height: 14),
            Text(
              _loadErrorMessage,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 14,
                fontFamily: 'Cairo',
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _fetchViewers,
              icon: const Icon(Icons.refresh_rounded),
              label: Text(_isAr ? 'إعادة المحاولة' : 'Retry'),
            ),
          ],
        ),
      ),
    );
  }

  /// 📭 واجهة عدم وجود زوار
  Widget _buildEmptyStateView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.sectionBackground,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.visibility_off_rounded,
              color: AppColors.textMuted,
              size: 54,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            _isAr
                ? 'لا يوجد زوار لملفك الشخصي مؤخراً'
                : 'No recent profile visitors',
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 16,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _isAr
                ? 'عندما يقوم أي عضو بزيارة ملفك، سيظهر هنا فوراً'
                : 'Visitors will appear here when they view your profile',
            style: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }

  /// 👤 كارت عرض بيانات الزائر
  Widget _buildViewerCard(dynamic item) {
    final String name =
        item['name'] ?? item['user_name'] ?? (_isAr ? 'عضو' : 'Member');
    final String photo = item['photo'] ?? item['user_photo'] ?? '';
    final int userId = item['user_id'] ?? item['viewed_by'] ?? 0;
    final int age = item['age'] ?? 0;
    final String timeStr = item['date'] ?? item['time'] ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.cardColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: AppColors.lineColor,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(20),
        child: ListTile(
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          leading: CircleAvatar(
            radius: 26,
            backgroundColor: AppColors.sectionBackground,
            backgroundImage:
                photo.isNotEmpty ? CachedNetworkImageProvider(photo) : null,
            child: photo.isEmpty
                ? const Icon(Icons.person, color: AppColors.textMuted, size: 28)
                : null,
          ),
          title: Row(
            children: [
              Expanded(
                child: Text(
                  name,
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
              ),
              if (age > 0)
                Text(
                  '$age ${_isAr ? 'سنة' : 'yrs'}',
                  style: const TextStyle(
                    color: AppColors.primaryTeal,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
            ],
          ),
          subtitle: Row(
            children: [
              const Icon(Icons.access_time_rounded,
                  color: AppColors.textMuted, size: 14),
              const SizedBox(width: 4),
              Text(
                timeStr,
                style: const TextStyle(
                  color: AppColors.textMuted,
                  fontSize: 11.5,
                  fontFamily: 'Cairo',
                ),
              ),
            ],
          ),
          trailing: Icon(
            _isAr
                ? Icons.arrow_back_ios_new_rounded
                : Icons.arrow_forward_ios_rounded,
            color: AppColors.textMuted,
            size: 16,
          ),
          onTap: () {
            if (userId > 0) {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => MemberProfileScreen(
                    userId: userId,
                    currentLanguage: widget.currentLanguage,
                  ),
                ),
              );
            }
          },
        ),
      ),
    );
  }
}

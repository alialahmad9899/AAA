// ============================================================================
// اسم الملف: lib/screens/my_interests_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة عرض واستجابة لطلبات الاهتمام الواردة والصادرة مع دعم كامل
//        للقراءة التلقائية للغة من SharedPreferences وتمريرها للـ API.
// ============================================================================

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../widgets/app_snackbar.dart';
import 'member_profile_screen.dart';

class MyInterestsScreen extends StatefulWidget {
  final String currentLanguage;

  const MyInterestsScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<MyInterestsScreen> createState() => _MyInterestsScreenState();
}

class _MyInterestsScreenState extends State<MyInterestsScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  bool _isLoadingReceived = true;
  bool _isLoadingSent = true;
  String? _receivedLoadError;
  String? _sentLoadError;
  late String _activeLang;

  List<dynamic> _receivedInterests = [];
  List<dynamic> _sentInterests = [];

  @override
  void initState() {
    super.initState();
    _activeLang = widget.currentLanguage;
    _tabController = TabController(length: 2, vsync: this);
    _checkLanguageAndLoad();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  /// فحص لغة التطبيق الحقيقية المحفوظة في الجهاز ثم جلب البيانات
  Future<void> _checkLanguageAndLoad() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final String? savedLang = prefs.getString('app_language');
      if (savedLang != null && savedLang.isNotEmpty) {
        if (mounted) {
          setState(() {
            _activeLang = savedLang;
          });
        }
      }
    } catch (e) {
      debugPrint('خطأ في قراءة اللغة المحفوظة: $e');
    }
    _loadAllInterests();
  }

  /// تحميل جميع الطلبات (الواردة والصادرة)
  Future<void> _loadAllInterests() async {
    await Future.wait([
      _fetchReceivedInterests(),
      _fetchSentInterests(),
    ]);
  }

  /// جلب الطلبات الواردة من السيرفر بمرجعية اللغة النشطة
  Future<void> _fetchReceivedInterests() async {
    if (!mounted) return;
    setState(() => _isLoadingReceived = true);
    try {
      final res = await ApiService.getReceivedInterests(lang: _activeLang);
      if (mounted) {
        setState(() {
          if (res['success'] == true && res['data'] is List) {
            _receivedInterests = res['data'];
            _receivedLoadError = null;
          } else {
            _receivedLoadError = res['message']?.toString() ??
                (_activeLang == 'ar'
                    ? 'تعذر تحميل الطلبات الواردة'
                    : 'Could not load received requests');
          }
          _isLoadingReceived = false;
        });
      }
    } catch (e) {
      debugPrint('خطأ في جلب الطلبات الواردة: $e');
      if (mounted) {
        setState(() {
          _receivedLoadError = _activeLang == 'ar'
              ? 'تعذر الاتصال بالسيرفر لتحميل الطلبات الواردة'
              : 'Could not connect to load received requests';
          _isLoadingReceived = false;
        });
      }
    }
  }

  /// جلب الطلبات المرسلة من السيرفر بمرجعية اللغة النشطة
  Future<void> _fetchSentInterests() async {
    if (!mounted) return;
    setState(() => _isLoadingSent = true);
    try {
      final res = await ApiService.getSentInterests(lang: _activeLang);
      if (mounted) {
        setState(() {
          if (res['success'] == true && res['data'] is List) {
            _sentInterests = res['data'];
            _sentLoadError = null;
          } else {
            _sentLoadError = res['message']?.toString() ??
                (_activeLang == 'ar'
                    ? 'تعذر تحميل الطلبات المرسلة'
                    : 'Could not load sent requests');
          }
          _isLoadingSent = false;
        });
      }
    } catch (e) {
      debugPrint('خطأ في جلب الطلبات المرسلة: $e');
      if (mounted) {
        setState(() {
          _sentLoadError = _activeLang == 'ar'
              ? 'تعذر الاتصال بالسيرفر لتحميل الطلبات المرسلة'
              : 'Could not connect to load sent requests';
          _isLoadingSent = false;
        });
      }
    }
  }

  /// معالجة قبول طلب الاهتمام الوارد
  Future<void> _handleAccept(int interestId, int index) async {
    final bool isAr = _activeLang == 'ar';
    try {
      final success = await ApiService.acceptInterestRequest(interestId);

      if (!mounted) return;

      if (success) {
        setState(() {
          _receivedInterests.removeWhere((item) {
            if (item is Map) {
              final id = int.tryParse(
                      (item['id'] ?? item['interest_id'] ?? '').toString()) ??
                  0;
              return id == interestId;
            }
            return false;
          });
        });
        AppSnackBar.show(
          context: context,
          type: NotificationType.success,
          isArabic: isAr,
          messageAr: 'تم قبول طلب الاهتمام بنجاح 🎉',
          messageEn: 'Interest request accepted successfully 🎉',
        );
      } else {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: isAr,
          messageAr: 'فشل قبول الطلب، يرجى المحاولة لاحقاً',
          messageEn: 'Failed to accept request, try again later',
        );
      }
    } catch (e) {
      if (mounted) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: isAr,
          messageAr: 'حدث خطأ في الاتصال بالسيرفر',
          messageEn: 'Network connection error',
        );
      }
    }
  }

  /// معالجة رفض أو إزالة طلب الاهتمام
  Future<void> _handleReject(int interestId, int index, bool isReceived) async {
    final bool isAr = _activeLang == 'ar';
    try {
      final success = await ApiService.rejectInterestRequest(interestId);

      if (!mounted) return;

      if (success) {
        setState(() {
          final targetList = isReceived ? _receivedInterests : _sentInterests;
          targetList.removeWhere((item) {
            if (item is Map) {
              final id = int.tryParse(
                      (item['id'] ?? item['interest_id'] ?? '').toString()) ??
                  0;
              return id == interestId;
            }
            return false;
          });
        });
        AppSnackBar.show(
          context: context,
          type: NotificationType.info,
          isArabic: isAr,
          messageAr: 'تم إزالة طلب الاهتمام بنجاح',
          messageEn: 'Interest request removed successfully',
        );
      } else {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: isAr,
          messageAr: 'حدث خطأ، يرجى إعادة المحاولة',
          messageEn: 'An error occurred, please try again',
        );
      }
    } catch (e) {
      if (mounted) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: isAr,
          messageAr: 'حدث خطأ في الاتصال بالسيرفر',
          messageEn: 'Network connection error',
        );
      }
    }
  }

  /// فتح الملف الشخصي للعضو مع تمرير اللغة النشطة
  void _navigateToProfile(int userId) {
    if (userId <= 0) return;
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => MemberProfileScreen(
          userId: userId,
          currentLanguage: _activeLang,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool isAr = _activeLang == 'ar';
    const Color darkBgColor = Color(0xFF0F172A);

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: darkBgColor,
        systemNavigationBarIconBrightness: Brightness.light,
        systemNavigationBarDividerColor: Colors.transparent,
      ),
      child: Directionality(
        textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Scaffold(
          backgroundColor: darkBgColor,
          appBar: AppBar(
            backgroundColor: darkBgColor,
            elevation: 0,
            centerTitle: true,
            leading: IconButton(
              icon: Icon(
                isAr
                    ? Icons.arrow_back_ios_rounded
                    : Icons.arrow_back_ios_new_rounded,
                color: Colors.white,
                size: 20,
              ),
              onPressed: () => Navigator.pop(context),
            ),
            title: Text(
              isAr ? 'طلبات الاهتمام' : 'Interest Requests',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
            bottom: PreferredSize(
              preferredSize: const Size.fromHeight(48),
              child: Container(
                margin: const EdgeInsets.symmetric(horizontal: 16),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(14),
                  border:
                      Border.all(color: Colors.white.withValues(alpha: 0.1)),
                ),
                child: TabBar(
                  controller: _tabController,
                  indicator: BoxDecoration(
                    color: const Color(0xFFE91E63),
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFFE91E63).withValues(alpha: 0.4),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  labelColor: Colors.white,
                  unselectedLabelColor: Colors.white60,
                  labelStyle: const TextStyle(
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                  ),
                  tabs: [
                    Tab(
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.call_received_rounded, size: 16),
                          const SizedBox(width: 6),
                          Text(isAr ? 'الطلبات الواردة' : 'Received'),
                        ],
                      ),
                    ),
                    Tab(
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.call_made_rounded, size: 16),
                          const SizedBox(width: 6),
                          Text(isAr ? 'الطلبات المرسلة' : 'Sent'),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          body: SafeArea(
            top: false,
            bottom: true,
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildInterestsList(
                  isLoading: _isLoadingReceived,
                  items: _receivedInterests,
                  isReceived: true,
                  isAr: isAr,
                  errorMessage: _receivedLoadError,
                  onRetry: _fetchReceivedInterests,
                  emptyMessage: isAr
                      ? 'لا توجد طلبات اهتمام واردة حالياً'
                      : 'No received interest requests yet',
                ),
                _buildInterestsList(
                  isLoading: _isLoadingSent,
                  items: _sentInterests,
                  isReceived: false,
                  isAr: isAr,
                  errorMessage: _sentLoadError,
                  onRetry: _fetchSentInterests,
                  emptyMessage: isAr
                      ? 'لم تقم بإرسال أي طلب اهتمام حتى الآن'
                      : 'You have not sent any interest requests',
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildInterestsList({
    required bool isLoading,
    required List<dynamic> items,
    required bool isReceived,
    required bool isAr,
    required String emptyMessage,
    required String? errorMessage,
    required VoidCallback onRetry,
  }) {
    if (isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFFE91E63)),
      );
    }

    if (errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.cloud_off_rounded,
                  color: Colors.white60, size: 48),
              const SizedBox(height: 14),
              Text(
                errorMessage,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 14,
                  fontFamily: 'Cairo',
                ),
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded),
                label: Text(isAr ? 'إعادة المحاولة' : 'Retry'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFE91E63),
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (items.isEmpty) {
      return Center(
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: const Color(0xFFE91E63).withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: const Color(0xFFE91E63).withValues(alpha: 0.2),
                    width: 1.5,
                  ),
                ),
                child: const Icon(
                  Icons.favorite_border_rounded,
                  size: 56,
                  color: Color(0xFFF48FB1),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                emptyMessage,
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 14,
                  fontFamily: 'Cairo',
                ),
              ),
            ],
          ),
        ),
      );
    }

    final double bottomPadding = MediaQuery.of(context).padding.bottom + 24;

    return RefreshIndicator(
      color: const Color(0xFFE91E63),
      backgroundColor: const Color(0xFF1E293B),
      onRefresh: _loadAllInterests,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(
          parent: BouncingScrollPhysics(),
        ),
        padding: EdgeInsets.fromLTRB(16, 16, 16, bottomPadding),
        itemCount: items.length,
        itemBuilder: (context, index) {
          final item = items[index];
          if (item is! Map) return const SizedBox.shrink();

          final int interestId = int.tryParse(
                  (item['id'] ?? item['interest_id'] ?? '').toString()) ??
              0;

          final dynamic userObj = item['user'] ?? item['sender'] ?? item;
          final Map userMap = userObj is Map ? userObj : item;

          final int userId = int.tryParse(
                  (userMap['user_id'] ?? userMap['id'] ?? '').toString()) ??
              0;

          final String name = (userMap['name'] ?? userMap['full_name'] ?? '')
                  .toString()
                  .trim()
                  .isEmpty
              ? (isAr ? 'عضو وصال' : 'Wesal Member')
              : (userMap['name'] ?? userMap['full_name']).toString();

          final String photo =
              (userMap['photo'] ?? userMap['avatar'] ?? '').toString().trim();
          final dynamic ageVal = userMap['age'] ?? 0;
          final String country =
              (userMap['country'] ?? userMap['residency_country'] ?? '')
                  .toString();
          final String statusStr = (item['status'] ?? '').toString();
          final bool isApproved =
              statusStr.toLowerCase() == 'approved' || item['status'] == 1;

          final bool hasValidPhoto = photo.isNotEmpty &&
              photo != 'null' &&
              (photo.startsWith('http://') || photo.startsWith('https://'));

          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(20),
              clipBehavior: Clip.antiAlias,
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: () => _navigateToProfile(userId),
                  splashColor: const Color(0xFFE91E63).withValues(alpha: 0.12),
                  highlightColor: Colors.white.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(20),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B).withValues(alpha: 0.7),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.12),
                        width: 1,
                      ),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(2),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: LinearGradient(
                              colors: [
                                const Color(0xFFE91E63).withValues(alpha: 0.6),
                                Colors.white.withValues(alpha: 0.2),
                              ],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(50),
                            child: hasValidPhoto
                                ? CachedNetworkImage(
                                    imageUrl: photo,
                                    width: 58,
                                    height: 58,
                                    fit: BoxFit.cover,
                                    placeholder: (context, url) => Container(
                                      width: 58,
                                      height: 58,
                                      color: const Color(0xFF0F172A),
                                      child: const Center(
                                        child: SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(
                                            color: Color(0xFFE91E63),
                                            strokeWidth: 2,
                                          ),
                                        ),
                                      ),
                                    ),
                                    errorWidget: (context, url, error) =>
                                        Container(
                                      width: 58,
                                      height: 58,
                                      color: const Color(0xFF0F172A),
                                      child: const Icon(
                                        Icons.person_rounded,
                                        color: Colors.white54,
                                        size: 32,
                                      ),
                                    ),
                                  )
                                : Container(
                                    width: 58,
                                    height: 58,
                                    color: const Color(0xFF0F172A),
                                    child: const Icon(
                                      Icons.person_rounded,
                                      color: Colors.white54,
                                      size: 32,
                                    ),
                                  ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                name,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  if (ageVal.toString() != '0' &&
                                      ageVal.toString().isNotEmpty) ...[
                                    Icon(
                                      Icons.cake_outlined,
                                      size: 13,
                                      color: Colors.pink.shade200,
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      '$ageVal ${isAr ? 'سنة' : 'yrs'}',
                                      style: const TextStyle(
                                        color: Colors.white70,
                                        fontSize: 12,
                                        fontFamily: 'Cairo',
                                      ),
                                    ),
                                  ],
                                  if (ageVal.toString() != '0' &&
                                      ageVal.toString().isNotEmpty &&
                                      country.isNotEmpty)
                                    const Text(
                                      '  •  ',
                                      style: TextStyle(color: Colors.white38),
                                    ),
                                  if (country.isNotEmpty) ...[
                                    Icon(
                                      Icons.location_on_outlined,
                                      size: 13,
                                      color: Colors.pink.shade200,
                                    ),
                                    const SizedBox(width: 3),
                                    Expanded(
                                      child: Text(
                                        country,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(
                                          color: Colors.white70,
                                          fontSize: 12,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        if (isReceived) ...[
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              InkWell(
                                onTap: () => _handleAccept(interestId, index),
                                borderRadius: BorderRadius.circular(12),
                                child: Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF10B981)
                                        .withValues(alpha: 0.15),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: const Color(0xFF10B981)
                                          .withValues(alpha: 0.4),
                                    ),
                                  ),
                                  child: const Icon(
                                    Icons.check_rounded,
                                    color: Color(0xFF10B981),
                                    size: 20,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 6),
                              InkWell(
                                onTap: () =>
                                    _handleReject(interestId, index, true),
                                borderRadius: BorderRadius.circular(12),
                                child: Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFEF4444)
                                        .withValues(alpha: 0.15),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: const Color(0xFFEF4444)
                                          .withValues(alpha: 0.4),
                                    ),
                                  ),
                                  child: const Icon(
                                    Icons.close_rounded,
                                    color: Color(0xFFEF4444),
                                    size: 20,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ] else ...[
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: isApproved
                                  ? const Color(0xFF10B981)
                                      .withValues(alpha: 0.15)
                                  : Colors.amber.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(
                                color: isApproved
                                    ? const Color(0xFF10B981)
                                        .withValues(alpha: 0.5)
                                    : Colors.amber.withValues(alpha: 0.5),
                              ),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  isApproved
                                      ? Icons.check_circle_outline_rounded
                                      : Icons.hourglass_empty_rounded,
                                  size: 13,
                                  color: isApproved
                                      ? const Color(0xFF10B981)
                                      : Colors.amber,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  isApproved
                                      ? (isAr ? 'مقبول' : 'Accepted')
                                      : (isAr ? 'قيد الانتظار' : 'Pending'),
                                  style: TextStyle(
                                    color: isApproved
                                        ? const Color(0xFF10B981)
                                        : Colors.amber,
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

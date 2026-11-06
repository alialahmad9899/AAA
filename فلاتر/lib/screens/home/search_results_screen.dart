// ============================================================================
// اسم الملف: lib/screens/home/search_results_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: واجهة نتائج البحث المحدثة والمنظمة مع دعم كامل للترجمة الديناميكية المزدوجة
//        (الحالة الاجتماعية، الديانة، المهن، الإقامة) وعرض الشارات بأسلوب Modern Premium.
// ============================================================================

import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../services/api_service.dart';
import '../../models/member_model.dart';
import '../member_profile_screen.dart';

class SearchResultsScreen extends StatefulWidget {
  final int? ageFrom;
  final int? ageTo;
  final int? countryId;
  final int? cityId;
  final int? maritalStatus;
  final int? religionId;
  final String currentLanguage;
  final String Function(String) txt;
  final String countryName;
  final String cityName;

  const SearchResultsScreen({
    super.key,
    this.ageFrom,
    this.ageTo,
    this.countryId,
    this.cityId,
    this.maritalStatus,
    this.religionId,
    required this.currentLanguage,
    required this.txt,
    required this.countryName,
    required this.cityName,
  });

  @override
  State<SearchResultsScreen> createState() => _SearchResultsScreenState();
}

class _SearchResultsScreenState extends State<SearchResultsScreen> {
  bool _isLoading = true;
  List<MemberModel> _results = [];
  String? _errorMessage;
  int _searchFetchCounter = 0;

  bool get _isAr => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _fetchSearchResults();
  }

  /// 🟢 ترجمة ديناميكية للحالة الاجتماعية عند تحويل اللغة للإنجليزي
  String _translateMaritalStatus(String rawStatus) {
    if (rawStatus.trim().isEmpty) return '';
    if (_isAr) return rawStatus;

    final String clean = rawStatus.trim().toLowerCase();
    if (clean.contains('أعزب') ||
        clean.contains('اعزب') ||
        clean.contains('عزباء') ||
        clean == 'single') {
      return 'Single';
    }
    if (clean.contains('مطلق') ||
        clean.contains('مطلقة') ||
        clean == 'divorced') {
      return 'Divorced';
    }
    if (clean.contains('أرمل') ||
        clean.contains('ارمل') ||
        clean.contains('أرملة') ||
        clean.contains('ارملة') ||
        clean == 'widowed') {
      return 'Widowed';
    }
    if (clean.contains('متزوج') ||
        clean.contains('متزوجة') ||
        clean == 'married') {
      return 'Married';
    }
    return rawStatus;
  }

  /// 🟢 ترجمة ديناميكية للديانة المحددة عند تحويل اللغة للإنجليزي
  String _translateReligion(String rawReligion) {
    if (rawReligion.trim().isEmpty) return '';
    if (_isAr) return rawReligion;

    final String clean = rawReligion.trim().toLowerCase();
    if (clean.contains('مسلم') ||
        clean.contains('إسلام') ||
        clean.contains('اسلام') ||
        clean == 'muslim') {
      if (clean.contains('سنة') || clean.contains('سني')) {
        return 'Muslim (Sunni)';
      }
      if (clean.contains('شيعة') || clean.contains('شيعي')) {
        return 'Muslim (Shia)';
      }
      return 'Muslim';
    }
    if (clean.contains('مسيحي') ||
        clean.contains('مسيحية') ||
        clean == 'christian') {
      return 'Christian';
    }
    if (clean.contains('غير محدد') || clean == 'unspecified') {
      return 'Unspecified';
    }
    return rawReligion;
  }

  /// 🟢 ترجمة ديناميكية للمهنة والوظيفة
  String _translateOccupation(String rawOccupation) {
    if (rawOccupation.trim().isEmpty) return '';
    final String clean = rawOccupation.trim().toLowerCase();

    if (clean == 'غير محدد' || clean == 'unspecified') {
      return _isAr ? 'غير محدد' : 'Unspecified';
    }
    if (_isAr) return rawOccupation;

    if (clean.contains('طبيب') || clean.contains('طبيبة')) return 'Doctor';
    if (clean.contains('مهندس') || clean.contains('مهندسة')) return 'Engineer';
    if (clean.contains('معلم') ||
        clean.contains('معلمة') ||
        clean.contains('مدرس')) {
      return 'Teacher';
    }
    if (clean.contains('طالب') || clean.contains('طالبة')) return 'Student';
    if (clean.contains('اعمال حرة') ||
        clean.contains('أعمال حرة') ||
        clean.contains('عمل حر')) {
      return 'Business / Freelancer';
    }
    if (clean.contains('موظف') || clean.contains('موظفة')) return 'Employee';

    return rawOccupation;
  }

  /// 🟢 ترجمة ديناميكية لمكان الإقامة والدولة
  String _translateResidence(String rawResidence) {
    if (rawResidence.trim().isEmpty) return '';
    if (_isAr) return rawResidence;

    String clean = rawResidence;
    clean = clean.replaceAll('سوريا', 'Syria').replaceAll('سورية', 'Syria');
    return clean;
  }

  /// جلب نتائج البحث من API السيرفر مع حماية من تضارب الطلبات (Race Conditions)
  Future<void> _fetchSearchResults() async {
    _searchFetchCounter++;
    final int currentFetch = _searchFetchCounter;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final members = await ApiService.searchMembers(
        ageFrom: widget.ageFrom,
        ageTo: widget.ageTo,
        countryId: widget.countryId,
        cityId: widget.cityId,
        maritalStatus: widget.maritalStatus,
        religionId: widget.religionId,
        lang: widget.currentLanguage,
      );

      if (mounted && currentFetch == _searchFetchCounter) {
        setState(() {
          _results = members;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted && currentFetch == _searchFetchCounter) {
        setState(() {
          _errorMessage = _isAr
              ? 'حدث خطأ أثناء جلب النتائج، يرجى المحاولة لاحقاً.'
              : 'Failed to fetch search results. Please try again later.';
          _isLoading = false;
        });
      }
    }
  }

  /// فتح شاشة الملف الشخصي للعضو عند النقر
  void _openMemberProfile(int userId) {
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

  /// ودجت بناء شارة الباقة المخصصة للعضو داخل الكروت المصغرة
  Widget _buildPackageBadge(String rawPackageName) {
    final String cleanName = rawPackageName.trim().toLowerCase();
    if (cleanName.isEmpty) return const SizedBox.shrink();

    Color primaryColor;
    List<Color> gradientColors;
    IconData badgeIcon;
    String badgeTitle;

    if (cleanName.contains('ملكي') ||
        cleanName.contains('royal') ||
        cleanName.contains('3')) {
      primaryColor = const Color(0xFFAB47BC);
      gradientColors = [const Color(0xFF8E24AA), const Color(0xFF4A148C)];
      badgeIcon = Icons.workspace_premium_rounded;
      badgeTitle = _isAr ? 'ملكية 👑' : 'Royal 👑';
    } else if (cleanName.contains('ذهبي') ||
        cleanName.contains('gold') ||
        cleanName.contains('شهر') ||
        cleanName.contains('month')) {
      primaryColor = const Color(0xFFFFB300);
      gradientColors = [const Color(0xFFFFB300), const Color(0xFFF57C00)];
      badgeIcon = Icons.stars_rounded;
      badgeTitle = _isAr ? 'ذهبية 🥇' : 'Gold 🥇';
    } else {
      primaryColor = const Color(0xFF38BDF8);
      gradientColors = [const Color(0xFF0288D1), const Color(0xFF005691)];
      badgeIcon = Icons.verified_rounded;
      badgeTitle = _isAr ? 'مميزة 🥈' : 'Silver 🥈';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: gradientColors),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.35),
          width: 0.8,
        ),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withValues(alpha: 0.3),
            blurRadius: 4,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(badgeIcon, color: Colors.white, size: 10),
          const SizedBox(width: 3),
          Text(
            badgeTitle,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 9.5,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }

  /// ودجت بناء شارة المعلومات المصغرة (Info Badge)
  Widget _buildInfoBadge({
    required IconData icon,
    required String text,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.3), width: 1),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: color, size: 12),
          const SizedBox(width: 4),
          Text(
            text,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.9),
              fontSize: 11,
              fontWeight: FontWeight.w600,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }

  /// بطاقة عرض العضو الخارجية (2026 Modern Premium Member Card)
  Widget _buildMemberCard(MemberModel member) {
    // 🟢 تطبيق المترجمات الديناميكية على تفاصيل العضو
    final String maritalStatusText =
        _translateMaritalStatus(member.maritalStatus);
    final String religionText = _translateReligion(member.religion);
    final String residenceText = _translateResidence(member.residence);
    final String occupationText = _translateOccupation(member.occupation);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(22),
        child: InkWell(
          onTap: () => _openMemberProfile(member.id),
          borderRadius: BorderRadius.circular(22),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.20),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // الصورة الشخصية مع إطار ملون ومعالجة أخطاء التحميل
                Container(
                  padding: const EdgeInsets.all(2),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: const Color(0xFF4FC3F7).withValues(alpha: 0.6),
                      width: 1.5,
                    ),
                  ),
                  child: Container(
                    width: 60,
                    height: 60,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white12,
                    ),
                    child: ClipOval(
                      child: member.image.isNotEmpty
                          ? CachedNetworkImage(
                              imageUrl: member.image,
                              width: 60,
                              height: 60,
                              fit: BoxFit.cover,
                              placeholder: (context, url) => Container(
                                color: Colors.white12,
                              ),
                              errorWidget: (context, url, error) => const Icon(
                                Icons.person_rounded,
                                color: Colors.white,
                                size: 30,
                              ),
                            )
                          : const Icon(
                              Icons.person_rounded,
                              color: Colors.white,
                              size: 30,
                            ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),

                // تفاصيل العضو والشارات
                Expanded(
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
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ),
                          // شارة الباقة المخصصة
                          if (member.packageName.isNotEmpty) ...[
                            const SizedBox(width: 6),
                            _buildPackageBadge(member.packageName),
                          ],
                        ],
                      ),
                      const SizedBox(height: 8),

                      // شارات المعلومات: العمر - الحالة الاجتماعية - الديانة - الإقامة
                      Wrap(
                        spacing: 6,
                        runSpacing: 6,
                        children: [
                          _buildInfoBadge(
                            icon: Icons.cake_rounded,
                            text: _isAr
                                ? '${member.age} سنة'
                                : '${member.age} yrs',
                            color: const Color(0xFF4FC3F7),
                          ),
                          if (maritalStatusText.isNotEmpty)
                            _buildInfoBadge(
                              icon: Icons.favorite_rounded,
                              text: maritalStatusText,
                              color: const Color(0xFFFF80AB),
                            ),
                          if (religionText.isNotEmpty)
                            _buildInfoBadge(
                              icon: Icons.auto_awesome_rounded,
                              text: religionText,
                              color: const Color(0xFFFFD54F),
                            ),
                          if (residenceText.isNotEmpty)
                            _buildInfoBadge(
                              icon: Icons.location_on_rounded,
                              text: residenceText,
                              color: const Color(0xFF81D4FA),
                            ),
                        ],
                      ),

                      if (occupationText.isNotEmpty &&
                          occupationText != 'غير محدد' &&
                          occupationText != 'Unspecified') ...[
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Icon(
                              Icons.work_rounded,
                              color: const Color(0xFF4FC3F7)
                                  .withValues(alpha: 0.8),
                              size: 13,
                            ),
                            const SizedBox(width: 4),
                            Expanded(
                              child: Text(
                                occupationText,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.65),
                                  fontSize: 11,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(width: 6),

                // أيقونة سهم الانتقال
                Padding(
                  padding: const EdgeInsets.only(top: 4.0),
                  child: Container(
                    padding: const EdgeInsets.all(7),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.10),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      _isAr
                          ? Icons.arrow_back_ios_new_rounded
                          : Icons.arrow_forward_ios_rounded,
                      color: Colors.white70,
                      size: 12,
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

  /// تنسيق ملخص الفلاتر المحددة للبحث
  String _buildFilterSummaryText() {
    final int minAge = widget.ageFrom ?? 18;
    final int maxAge = widget.ageTo ?? 70;
    final String ageStr =
        _isAr ? 'العمر: $minAge-$maxAge' : 'Age: $minAge-$maxAge';

    final List<String> parts = [ageStr];

    if (widget.countryName.isNotEmpty) {
      parts.add(_translateResidence(widget.countryName));
    }
    if (widget.cityName.isNotEmpty) {
      parts.add(_translateResidence(widget.cityName));
    }

    return parts.join(' | ');
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: Scaffold(
        backgroundColor: const Color(0xFF12121C),
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          leading: IconButton(
            icon: Icon(
              _isAr
                  ? Icons.arrow_back_ios_new_rounded
                  : Icons.arrow_back_ios_rounded,
              color: Colors.white,
            ),
            onPressed: () => Navigator.pop(context),
          ),
          title: Text(
            _isAr ? 'نتائج البحث' : 'Search Results',
            style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo'),
          ),
          centerTitle: true,
        ),
        body: SafeArea(top: false, bottom: true, child: Column(
          children: [
            // شريط ملخص الفلاتر
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.filter_alt_rounded,
                      color: Color(0xFF4FC3F7), size: 18),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _buildFilterSummaryText(),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.85),
                          fontSize: 12,
                          fontFamily: 'Cairo'),
                    ),
                  ),
                  if (!_isLoading)
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFF4FC3F7).withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        _isAr
                            ? '${_results.length} عضو'
                            : '${_results.length} members',
                        style: const TextStyle(
                          color: Color(0xFF4FC3F7),
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                ],
              ),
            ),

            // عرض نتائج الأعضاء
            Expanded(
              child: _isLoading
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const CircularProgressIndicator(
                              color: Color(0xFF4FC3F7)),
                          const SizedBox(height: 14),
                          Text(
                            _isAr
                                ? 'جاري البحث عن الأعضاء المطابقين...'
                                : 'Searching for matching members...',
                            style: const TextStyle(
                                color: Colors.white70,
                                fontSize: 13,
                                fontFamily: 'Cairo'),
                          ),
                        ],
                      ),
                    )
                  : _errorMessage != null
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.error_outline_rounded,
                                  color: Colors.redAccent, size: 48),
                              const SizedBox(height: 10),
                              Text(
                                _errorMessage!,
                                style: const TextStyle(
                                    color: Colors.white70, fontFamily: 'Cairo'),
                              ),
                              const SizedBox(height: 14),
                              ElevatedButton(
                                onPressed: _fetchSearchResults,
                                style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF0288D1)),
                                child: Text(
                                  _isAr ? 'إعادة المحاولة' : 'Retry',
                                  style: const TextStyle(
                                      color: Colors.white, fontFamily: 'Cairo'),
                                ),
                              ),
                            ],
                          ),
                        )
                      : RefreshIndicator(
                          onRefresh: _fetchSearchResults,
                          color: const Color(0xFF4FC3F7),
                          backgroundColor: const Color(0xFF1E1E2C),
                          child: _results.isEmpty
                              ? ListView(
                                  physics:
                                      const AlwaysScrollableScrollPhysics(),
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 16, vertical: 40),
                                  children: [
                                    SizedBox(
                                      height:
                                          MediaQuery.of(context).size.height *
                                              0.35,
                                      child: Center(
                                        child: Column(
                                          mainAxisAlignment:
                                              MainAxisAlignment.center,
                                          children: [
                                            Icon(Icons.search_off_rounded,
                                                color: Colors.white
                                                    .withValues(alpha: 0.4),
                                                size: 56),
                                            const SizedBox(height: 12),
                                            Text(
                                              _isAr
                                                  ? 'لا توجد نتائج تطابق خيارات الفلترة المحددة'
                                                  : 'No members match the selected filters',
                                              textAlign: TextAlign.center,
                                              style: const TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 14,
                                                  fontWeight: FontWeight.bold,
                                                  fontFamily: 'Cairo'),
                                            ),
                                            const SizedBox(height: 6),
                                            Text(
                                              _isAr
                                                  ? 'جرب توسيع نطاق البحث أو تغيير الدولة/المدينة'
                                                  : 'Try expanding search range or changing country/city',
                                              textAlign: TextAlign.center,
                                              style: TextStyle(
                                                  color: Colors.white
                                                      .withValues(alpha: 0.6),
                                                  fontSize: 12,
                                                  fontFamily: 'Cairo'),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                )
                              : ListView.builder(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 16, vertical: 8),
                                  itemCount: _results.length,
                                  itemBuilder: (context, index) {
                                    return _buildMemberCard(_results[index]);
                                  },
                                ),
                        ),
            ),
          ],
        )),
      ),
    );
  }
}

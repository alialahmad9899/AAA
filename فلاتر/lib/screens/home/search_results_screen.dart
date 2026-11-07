// ============================================================================
// اسم الملف: lib/screens/home/search_results_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: واجهة نتائج البحث المحدثة والمنظمة مع دعم كامل للترجمة الديناميكية المزدوجة
//        (الحالة الاجتماعية، الديانة، المهن، الإقامة) وعرض الشارات بأسلوب Modern Premium.
// ============================================================================

import 'package:flutter/material.dart';
import '../../constants/colors.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../services/api_service.dart';
import '../../models/member_model.dart';
import '../../widgets/package_badge_widget.dart';
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

  /// شارة موحدة تعرض اسم الباقة الحقيقي الذي وصل من API.
  Widget _buildPackageBadge(String rawPackageName) {
    if (rawPackageName.trim().isEmpty) return const SizedBox.shrink();
    return PackageBadgeWidget(
      packageName: rawPackageName,
      compact: true,
      isArabic: _isAr,
    );
  }

  /// شارة بيانات صغيرة بنص داكن واضح على سطح فاتح.
  Widget _buildInfoBadge({
    required IconData icon,
    required String text,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.22)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: color, size: 12),
          const SizedBox(width: 4),
          Text(text, style: const TextStyle(
            color: AppColors.textDark,
            fontSize: 10.5,
            fontWeight: FontWeight.w600,
            fontFamily: 'Cairo',
          )),
        ],
      ),
    );
  }

  /// بطاقة عضو واضحة ومتناسقة مع شاشة البحث.
  Widget _buildMemberCard(MemberModel member) {
    final String maritalStatusText = _translateMaritalStatus(member.maritalStatus);
    final String religionText = _translateReligion(member.religion);
    final String residenceText = _translateResidence(member.residence);
    final String occupationText = _translateOccupation(member.occupation);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: AppColors.cardColor,
        borderRadius: BorderRadius.circular(20),
        child: InkWell(
          onTap: () => _openMemberProfile(member.id),
          borderRadius: BorderRadius.circular(20),
          child: Container(
            padding: const EdgeInsets.all(13),
            decoration: BoxDecoration(
              color: AppColors.cardColor,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: AppColors.lineColor),
              boxShadow: [
                BoxShadow(
                  color: AppColors.textDark.withValues(alpha: 0.045),
                  blurRadius: 14,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.all(2),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: AppColors.accentLight, width: 1.5),
                  ),
                  child: Container(
                    width: 66,
                    height: 66,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      color: AppColors.sectionBackground,
                    ),
                    child: ClipOval(
                      child: member.image.isNotEmpty
                          ? CachedNetworkImage(
                              imageUrl: member.image,
                              width: 66,
                              height: 66,
                              fit: BoxFit.cover,
                              placeholder: (context, url) => Container(
                                color: AppColors.sectionBackground,
                              ),
                              errorWidget: (context, url, error) =>
                                  const Icon(
                                Icons.person_rounded,
                                color: AppColors.textLight,
                                size: 31,
                              ),
                            )
                          : const Icon(
                              Icons.person_rounded,
                              color: AppColors.textLight,
                              size: 31,
                            ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
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
                                color: AppColors.textDark,
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ),
                          if (member.packageName.isNotEmpty) ...[
                            const SizedBox(width: 6),
                            _buildPackageBadge(member.packageName),
                          ],
                        ],
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 6,
                        runSpacing: 6,
                        children: [
                          _buildInfoBadge(
                            icon: Icons.cake_rounded,
                            text: _isAr ? '${member.age} سنة' : '${member.age} yrs',
                            color: AppColors.primaryTeal,
                          ),
                          if (maritalStatusText.isNotEmpty)
                            _buildInfoBadge(
                              icon: Icons.favorite_rounded,
                              text: maritalStatusText,
                              color: const Color(0xFF9C4A62),
                            ),
                          if (religionText.isNotEmpty)
                            _buildInfoBadge(
                              icon: Icons.auto_awesome_rounded,
                              text: religionText,
                              color: const Color(0xFF8A641D),
                            ),
                          if (residenceText.isNotEmpty)
                            _buildInfoBadge(
                              icon: Icons.location_on_rounded,
                              text: residenceText,
                              color: AppColors.secondaryTeal,
                            ),
                        ],
                      ),
                      if (occupationText.isNotEmpty &&
                          occupationText != 'غير محدد' &&
                          occupationText != 'Unspecified') ...[
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            const Icon(
                              Icons.work_outline_rounded,
                              color: AppColors.primaryTeal,
                              size: 14,
                            ),
                            const SizedBox(width: 5),
                            Expanded(
                              child: Text(
                                occupationText,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  color: AppColors.textMuted,
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
                Container(
                  margin: const EdgeInsets.only(top: 2),
                  padding: const EdgeInsets.all(7),
                  decoration: const BoxDecoration(
                    color: AppColors.sectionBackground,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    _isAr
                        ? Icons.arrow_back_ios_new_rounded
                        : Icons.arrow_forward_ios_rounded,
                    color: AppColors.textMuted,
                    size: 12,
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
        backgroundColor: AppColors.backgroundColor,
        appBar: AppBar(
          backgroundColor: AppColors.backgroundColor,
          surfaceTintColor: Colors.transparent,
          elevation: 0,
          leading: IconButton(
            icon: Icon(
              _isAr
                  ? Icons.arrow_back_ios_new_rounded
                  : Icons.arrow_back_ios_rounded,
              color: AppColors.textDark,
            ),
            onPressed: () => Navigator.pop(context),
          ),
          title: Text(
            _isAr ? 'نتائج البحث' : 'Search Results',
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 18,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
          centerTitle: true,
        ),
        body: SafeArea(
          top: false,
          bottom: true,
          child: Column(
            children: [
              Container(
                margin: const EdgeInsets.fromLTRB(16, 8, 16, 10),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                decoration: BoxDecoration(
                  color: AppColors.cardColor,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.lineColor),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.filter_alt_rounded,
                      color: AppColors.primaryTeal,
                      size: 18,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _buildFilterSummaryText(),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textDark,
                          fontSize: 11.5,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                    if (!_isLoading)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.accentLight.withValues(alpha: 0.28),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          _isAr ? '${_results.length} عضو' : '${_results.length} members',
                          style: const TextStyle(
                            color: AppColors.primaryTeal,
                            fontSize: 10.5,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                      ),
                  ],
                ),
              ),
              Expanded(
                child: _isLoading
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const CircularProgressIndicator(color: AppColors.primaryTeal),
                            const SizedBox(height: 14),
                            Text(
                              _isAr
                                  ? 'جاري البحث عن الأعضاء المطابقين...'
                                  : 'Searching for matching members...',
                              style: const TextStyle(
                                color: AppColors.textMuted,
                                fontSize: 13,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ],
                        ),
                      )
                    : _errorMessage != null
                        ? Center(
                            child: Padding(
                              padding: const EdgeInsets.all(24),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.error_outline_rounded, color: AppColors.error, size: 44),
                                  const SizedBox(height: 10),
                                  Text(
                                    _errorMessage!,
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(
                                      color: AppColors.textDark,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                                  const SizedBox(height: 14),
                                  ElevatedButton(
                                    onPressed: _fetchSearchResults,
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppColors.primaryTeal,
                                      foregroundColor: Colors.white,
                                    ),
                                    child: Text(
                                      _isAr ? 'إعادة المحاولة' : 'Retry',
                                      style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _fetchSearchResults,
                            color: AppColors.primaryTeal,
                            backgroundColor: AppColors.cardColor,
                            child: _results.isEmpty
                                ? ListView(
                                    physics: const AlwaysScrollableScrollPhysics(),
                                    padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 36),
                                    children: [
                                      SizedBox(
                                        height: MediaQuery.of(context).size.height * 0.35,
                                        child: Center(
                                          child: Column(
                                            mainAxisAlignment: MainAxisAlignment.center,
                                            children: [
                                              const Icon(Icons.search_off_rounded, color: AppColors.textLight, size: 54),
                                              const SizedBox(height: 12),
                                              Text(
                                                _isAr
                                                    ? 'لا توجد نتائج تطابق خيارات الفلترة المحددة'
                                                    : 'No members match the selected filters',
                                                textAlign: TextAlign.center,
                                                style: const TextStyle(
                                                  color: AppColors.textDark,
                                                  fontSize: 14,
                                                  fontWeight: FontWeight.bold,
                                                  fontFamily: 'Cairo',
                                                ),
                                              ),
                                              const SizedBox(height: 6),
                                              Text(
                                                _isAr
                                                    ? 'جرّب توسيع نطاق البحث أو تغيير الدولة والمحافظة'
                                                    : 'Try expanding your age range or changing the location',
                                                textAlign: TextAlign.center,
                                                style: const TextStyle(
                                                  color: AppColors.textMuted,
                                                  fontSize: 12,
                                                  fontFamily: 'Cairo',
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                  )
                                : ListView.builder(
                                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 18),
                                    itemCount: _results.length,
                                    itemBuilder: (context, index) =>
                                        _buildMemberCard(_results[index]),
                                  ),
                          ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

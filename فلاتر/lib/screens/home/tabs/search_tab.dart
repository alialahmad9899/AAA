// ============================================================================
// اسم الملف: lib/screens/home/tabs/search_tab.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: واجهة الفلترة المتقدمة بتصميمها الزجاجي الأصلي الشفاف مع الدعم
//        الكامل للغتين (عربي / إنجليزي) ووضوح محسّن للخطوط والقوائم.
// ============================================================================
import 'package:flutter/material.dart';
import '../../../constants/colors.dart';
import 'package:wesal_app/widgets/bottom_system_inset.dart';
import '../../../services/api_service.dart';
import '../search_results_screen.dart';

class SearchTab extends StatefulWidget {
  final String currentLanguage;
  final String Function(String) txt;
  final int unreadNotificationCount;
  final VoidCallback onNotificationPressed;

  const SearchTab({
    super.key,
    required this.currentLanguage,
    required this.txt,
    required this.unreadNotificationCount,
    required this.onNotificationPressed,
  });

  @override
  State<SearchTab> createState() => _SearchTabState();
}

class _SearchTabState extends State<SearchTab> {
  // --------------------------------------------------------------------------
  // حالات الخيارات المحددة (Selected Filter States)
  // --------------------------------------------------------------------------
  RangeValues _ageRange = const RangeValues(18, 50);
  Map<String, dynamic>? _selectedCountry;
  Map<String, dynamic>? _selectedCity;
  Map<String, dynamic>? _selectedStatus;
  Map<String, dynamic>? _selectedReligion;

  bool _isLoadingDropdowns = false;
  bool _isLoadingCities = false;

  // عداد لتتبع تسلسل طلبات المدن ومنع السباق (Race Condition)
  int _cityFetchSequence = 0;

  bool get _isAr => widget.currentLanguage == 'ar';

  // --------------------------------------------------------------------------
  // القوائم المنسدلة الديناميكية (Dynamic API Dropdown Lists)
  // --------------------------------------------------------------------------
  List<Map<String, dynamic>> _countries = [];
  List<Map<String, dynamic>> _cities = [];
  List<Map<String, dynamic>> _statuses = [];
  List<Map<String, dynamic>> _religions = [];

  static final Set<String> _arabCountryCodes = {
    'sy',
    'sa',
    'ae',
    'eg',
    'iq',
    'jo',
    'lb',
    'kw',
    'qa',
    'om',
    'bh',
    'ye',
    'ps',
    'dz',
    'ma',
    'tn',
    'ly',
    'sd',
    'mr',
    'so',
    'dj',
    'km'
  };

  static final List<String> _arabCountryKeywords = [
    'سوريا',
    'سورية',
    'السعودية',
    'الإمارات',
    'مصر',
    'العراق',
    'الأردن',
    'لبنان',
    'الكويت',
    'قطر',
    'عمان',
    'البحرين',
    'اليمن',
    'فلسطين',
    'الجزائر',
    'المغرب',
    'تونس',
    'ليبيا',
    'السودان',
    'موريتانيا',
    'الصومال',
    'جيبوتي',
    'جزر القمر'
  ];

  @override
  void initState() {
    super.initState();
    _fetchDropdownData();
  }

  // 💡 الاستجابة اللحظية عند تغيير لغة التطبيق مع الحفاظ على الاختيارات الحالية
  @override
  void didUpdateWidget(SearchTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.currentLanguage != widget.currentLanguage) {
      _fetchDropdownData();
    }
  }

  bool _isArabCountry(Map<String, dynamic> country) {
    if (country['id'] == null) return false;

    final String code =
        (country['code'] ?? country['iso'] ?? country['iso2'] ?? '')
            .toString()
            .toLowerCase();
    if (_arabCountryCodes.contains(code)) return true;

    final String nameAr =
        (country['name_ar'] ?? country['name'] ?? '').toString();
    final String nameEn =
        (country['name_en'] ?? country['name'] ?? '').toString().toLowerCase();

    for (var keyword in _arabCountryKeywords) {
      if (nameAr.contains(keyword)) return true;
    }

    if (nameEn.contains('syria') ||
        nameEn.contains('saudi') ||
        nameEn.contains('emirates') ||
        nameEn.contains('egypt') ||
        nameEn.contains('iraq') ||
        nameEn.contains('jordan') ||
        nameEn.contains('lebanon') ||
        nameEn.contains('kuwait') ||
        nameEn.contains('qatar') ||
        nameEn.contains('oman') ||
        nameEn.contains('bahrain') ||
        nameEn.contains('yemen') ||
        nameEn.contains('palestine') ||
        nameEn.contains('algeria') ||
        nameEn.contains('morocco') ||
        nameEn.contains('tunisia') ||
        nameEn.contains('libya') ||
        nameEn.contains('sudan')) {
      return true;
    }

    return false;
  }

  List<Map<String, dynamic>> _sortCountriesWithArabPriority(
      List<Map<String, dynamic>> rawCountries) {
    if (rawCountries.isEmpty) return [];

    final Map<String, dynamic> allOption = rawCountries.firstWhere(
      (c) => c['id'] == null,
      orElse: () =>
          {'id': null, 'name_ar': 'جميع الدول', 'name_en': 'All Countries'},
    );

    List<Map<String, dynamic>> arabCountries = [];
    List<Map<String, dynamic>> otherCountries = [];

    for (var c in rawCountries) {
      if (c['id'] == null) continue;
      if (_isArabCountry(c)) {
        arabCountries.add(c);
      } else {
        otherCountries.add(c);
      }
    }

    arabCountries.sort((a, b) => _getItemName(a).compareTo(_getItemName(b)));
    otherCountries.sort((a, b) => _getItemName(a).compareTo(_getItemName(b)));

    return [allOption, ...arabCountries, ...otherCountries];
  }

  Map<String, dynamic> _findMatchingOption(
    List<Map<String, dynamic>> list,
    Map<String, dynamic>? currentSelection,
    Map<String, dynamic> defaultOption,
  ) {
    if (currentSelection == null || list.isEmpty) return defaultOption;
    if (currentSelection['id'] == null) {
      return list.firstWhere((item) => item['id'] == null,
          orElse: () => defaultOption);
    }

    final String targetId = currentSelection['id'].toString();
    return list.firstWhere(
      (item) => item['id'] != null && item['id'].toString() == targetId,
      orElse: () => defaultOption,
    );
  }

  Future<void> _fetchDropdownData() async {
    if (!mounted) return;
    setState(() => _isLoadingDropdowns = true);

    try {
      final dropdowns = await ApiService.getProfileDropdowns(
        lang: widget.currentLanguage,
      );

      final Map<String, dynamic> allOption = {
        'id': null,
        'name': _isAr ? 'الكل' : 'All',
        'name_ar': 'الكل',
        'name_en': 'All',
      };

      if (dropdowns != null) {
        List<Map<String, dynamic>> fetchedCountries = [allOption];
        if (dropdowns['countries'] != null && dropdowns['countries'] is List) {
          for (var item in dropdowns['countries']) {
            if (item is Map) {
              fetchedCountries.add(Map<String, dynamic>.from(item));
            }
          }
        }
        fetchedCountries = _sortCountriesWithArabPriority(fetchedCountries);

        List<Map<String, dynamic>> fetchedStatuses = [allOption];
        final rawStatuses =
            dropdowns['maritial_status'] ?? dropdowns['marital_statuses'];
        if (rawStatuses != null && rawStatuses is List) {
          for (var item in rawStatuses) {
            if (item is Map) {
              fetchedStatuses.add(Map<String, dynamic>.from(item));
            }
          }
        }

        List<Map<String, dynamic>> fetchedReligions = [allOption];
        if (dropdowns['religions'] != null && dropdowns['religions'] is List) {
          for (var item in dropdowns['religions']) {
            if (item is Map) {
              fetchedReligions.add(Map<String, dynamic>.from(item));
            }
          }
        }

        if (mounted) {
          setState(() {
            _countries = fetchedCountries;
            _statuses = fetchedStatuses;
            _religions = fetchedReligions;

            _selectedCountry = _findMatchingOption(_countries, _selectedCountry,
                _countries.isNotEmpty ? _countries[0] : allOption);
            _selectedStatus = _findMatchingOption(_statuses, _selectedStatus,
                _statuses.isNotEmpty ? _statuses[0] : allOption);
            _selectedReligion = _findMatchingOption(
                _religions,
                _selectedReligion,
                _religions.isNotEmpty ? _religions[0] : allOption);

            _isLoadingDropdowns = false;
          });

          if (_selectedCountry != null && _selectedCountry!['id'] != null) {
            _fetchCitiesForCountry(_selectedCountry!);
          } else {
            if (mounted) {
              setState(() {
                _cities = [allOption];
                _selectedCity = allOption;
              });
            }
          }
        }
      } else {
        _setFallbackDropdowns();
      }
    } catch (e) {
      debugPrint('⚠️ [SearchTab] Error fetching dropdowns: $e');
      _setFallbackDropdowns();
    }
  }

  void _setFallbackDropdowns() {
    final Map<String, dynamic> allOption = {
      'id': null,
      'name': _isAr ? 'الكل' : 'All',
      'name_ar': 'الكل',
      'name_en': 'All',
    };

    if (mounted) {
      setState(() {
        _countries = _sortCountriesWithArabPriority([
          allOption,
          {
            'id': 1,
            'name_ar': 'الجمهورية العربية السورية',
            'name_en': 'Syria',
            'code': 'sy'
          },
          {
            'id': 2,
            'name_ar': 'المملكة العربية السعودية',
            'name_en': 'Saudi Arabia',
            'code': 'sa'
          },
          {
            'id': 3,
            'name_ar': 'الإمارات العربية المتحدة',
            'name_en': 'UAE',
            'code': 'ae'
          },
          {
            'id': 4,
            'name_ar': 'جمهورية مصر العربية',
            'name_en': 'Egypt',
            'code': 'eg'
          },
          {'id': 5, 'name_ar': 'ألمانيا', 'name_en': 'Germany', 'code': 'de'},
          {'id': 6, 'name_ar': 'السويد', 'name_en': 'Sweden', 'code': 'se'},
          {'id': 7, 'name_ar': 'تركيا', 'name_en': 'Turkey', 'code': 'tr'},
        ]);
        _cities = [allOption];
        _statuses = [
          allOption,
          {'id': 1, 'name_ar': 'أعزب / عزباء', 'name_en': 'Single'},
          {'id': 2, 'name_ar': 'متزوج / ة', 'name_en': 'Married'},
          {'id': 3, 'name_ar': 'مطلق / ة', 'name_en': 'Divorced'},
          {'id': 4, 'name_ar': 'أرمل / ة', 'name_en': 'Widowed'},
        ];
        _religions = [
          allOption,
          {'id': 1, 'name_ar': 'الإسلام', 'name_en': 'Islam'},
          {'id': 2, 'name_ar': 'المسيحية', 'name_en': 'Christianity'},
        ];

        _selectedCountry =
            _findMatchingOption(_countries, _selectedCountry, _countries[0]);
        _selectedCity = allOption;
        _selectedStatus =
            _findMatchingOption(_statuses, _selectedStatus, _statuses[0]);
        _selectedReligion =
            _findMatchingOption(_religions, _selectedReligion, _religions[0]);
        _isLoadingDropdowns = false;
      });
    }
  }

  Future<void> _fetchCitiesForCountry(Map<String, dynamic> country) async {
    _cityFetchSequence++;
    final int currentSequence = _cityFetchSequence;

    final Map<String, dynamic> allCitiesOption = {
      'id': null,
      'name': _isAr ? 'جميع المدن' : 'All Cities',
      'name_ar': 'جميع المدن',
      'name_en': 'All Cities',
    };

    final dynamic countryId = country['id'];
    if (countryId == null) {
      if (mounted) {
        setState(() {
          _selectedCity = allCitiesOption;
          _cities = [allCitiesOption];
          _isLoadingCities = false;
        });
      }
      return;
    }

    if (mounted) {
      setState(() => _isLoadingCities = true);
    }

    try {
      final statesData = await ApiService.getStatesByCountry(
        countryId,
        lang: widget.currentLanguage,
      );

      List<Map<String, dynamic>> fetchedCities = [allCitiesOption];
      for (var item in statesData) {
        if (item is Map) {
          fetchedCities.add(Map<String, dynamic>.from(item));
        }
      }

      if (mounted && currentSequence == _cityFetchSequence) {
        setState(() {
          _cities = fetchedCities;
          _selectedCity =
              _findMatchingOption(_cities, _selectedCity, allCitiesOption);
          _isLoadingCities = false;
        });
      }
    } catch (e) {
      debugPrint('⚠️ [SearchTab] Error fetching states/cities: $e');
      if (mounted && currentSequence == _cityFetchSequence) {
        setState(() => _isLoadingCities = false);
      }
    }
  }

  Future<void> _onCountrySelected(Map<String, dynamic> country) async {
    final Map<String, dynamic> allCitiesOption = {
      'id': null,
      'name': _isAr ? 'جميع المدن' : 'All Cities',
      'name_ar': 'جميع المدن',
      'name_en': 'All Cities',
    };

    setState(() {
      _selectedCountry = country;
      _selectedCity = allCitiesOption;
      _cities = [allCitiesOption];
    });

    await _fetchCitiesForCountry(country);
  }

  void _resetFilters() {
    final Map<String, dynamic> allOption = {
      'id': null,
      'name': _isAr ? 'الكل' : 'All',
      'name_ar': 'الكل',
      'name_en': 'All',
    };

    setState(() {
      _ageRange = const RangeValues(18, 50);
      _selectedCountry = _countries.isNotEmpty ? _countries[0] : allOption;
      _selectedCity = allOption;
      _cities = [allOption];
      _selectedStatus = _statuses.isNotEmpty ? _statuses[0] : allOption;
      _selectedReligion = _religions.isNotEmpty ? _religions[0] : allOption;
    });
  }

  void _navigateToResults() {
    if (!mounted) return;

    int? parseId(dynamic val) {
      if (val == null) return null;
      if (val is int) return val > 0 ? val : null;
      final parsed = int.tryParse(val.toString());
      return (parsed != null && parsed > 0) ? parsed : null;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => SearchResultsScreen(
          ageFrom: _ageRange.start.round(),
          ageTo: _ageRange.end.round(),
          countryId: parseId(_selectedCountry?['id']),
          cityId: parseId(_selectedCity?['id']),
          maritalStatus: parseId(_selectedStatus?['id']),
          religionId: parseId(_selectedReligion?['id']),
          currentLanguage: widget.currentLanguage,
          txt: widget.txt,
          countryName: _getItemName(_selectedCountry),
          cityName: _getItemName(_selectedCity),
        ),
      ),
    );
  }

  /// 🟢 دالة إرجاع اسم العنصر المترجم ديناميكياً مع التغطية الشاملة
  String _getItemName(Map<String, dynamic>? item) {
    if (item == null) return '';
    if (item['label'] != null) return item['label'].toString();

    // 1️⃣ إذا كانت اللغة الحالية هي العربية
    if (_isAr) {
      return (item['name_ar'] ?? item['name'] ?? item['name_en'] ?? '')
          .toString();
    }

    // 2️⃣ إذا كانت اللغة الحالية هي الإنجليزية
    if (item['name_en'] != null &&
        item['name_en'].toString().trim().isNotEmpty) {
      return item['name_en'].toString();
    }

    final String rawName = (item['name'] ?? item['name_ar'] ?? '').toString();
    return _translateItemToEnglish(item['id'], rawName);
  }

  /// 🌐 دالة الترجمة الاحتياطية للغة الإنجليزية بناءً على المعرف والاسم العربي
  String _translateItemToEnglish(dynamic id, String arabicName) {
    final String trimmed = arabicName.trim();

    if (trimmed == 'الكل' ||
        trimmed == 'جميع الدول' ||
        trimmed == 'جميع المدن') {
      return 'All';
    }

    // قاموس ترجمة الحالة الاجتماعية
    final Map<String, String> statusTranslations = {
      'أعزب': 'Single',
      'عزباء': 'Single',
      'أعزب / عزباء': 'Single',
      'متزوج': 'Married',
      'متزوجة': 'Married',
      'متزوج / ة': 'Married',
      'مطلق': 'Divorced',
      'مطلقة': 'Divorced',
      'مطلق / ة': 'Divorced',
      'أرمل': 'Widowed',
      'أرملة': 'Widowed',
      'أرمل / ة': 'Widowed',
    };

    if (statusTranslations.containsKey(trimmed)) {
      return statusTranslations[trimmed]!;
    }

    // قاموس ترجمة الديانة
    final Map<String, String> religionTranslations = {
      'الإسلام': 'Islam',
      'مسلم': 'Muslim',
      'مسلمة': 'Muslim',
      'المسيحية': 'Christianity',
      'مسيحي': 'Christian',
      'مسيحية': 'Christian',
    };

    if (religionTranslations.containsKey(trimmed)) {
      return religionTranslations[trimmed]!;
    }

    return trimmed;
  }

  void _showSelectionBottomSheet({
    required String title,
    required List<Map<String, dynamic>> items,
    required Map<String, dynamic>? currentSelection,
    required Function(Map<String, dynamic>) onSelect,
    bool enableSearch = false,
    bool isCountryList = false,
  }) {
    final TextEditingController searchController = TextEditingController();
    final String currentSelectionName =
        currentSelection != null ? _getItemName(currentSelection) : '';

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) {
        String searchQuery = '';

        return Directionality(
          textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
          child: StatefulBuilder(
            builder: (context, setModalState) {
              final filteredItems = items.where((item) {
                if (searchQuery.trim().isEmpty) return true;
                final String name = _getItemName(item).toLowerCase();
                return name.contains(searchQuery.trim().toLowerCase());
              }).toList();

              final double screenHeight = MediaQuery.sizeOf(context).height;
              final double bottomInset = WesalBottomInsets.of(context);

              return Container(
                constraints: BoxConstraints(
                  maxHeight: screenHeight * 0.75,
                ),
                padding: EdgeInsets.only(
                  left: 20,
                  right: 20,
                  top: 18,
                  bottom: bottomInset + 18,
                ),
                decoration: BoxDecoration(
                  color: AppColors.cardColor,
                  borderRadius:
                      const BorderRadius.vertical(top: Radius.circular(28)),
                  border:
                      Border.all(color: AppColors.lineColor),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(
                      child: Container(
                        width: 38,
                        height: 4,
                        decoration: BoxDecoration(
                          color: AppColors.textLight,
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      title,
                      style: const TextStyle(
                        color: AppColors.textDark,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    const SizedBox(height: 12),
                    if (enableSearch) ...[
                      Container(
                        height: 46,
                        decoration: BoxDecoration(
                          color: AppColors.sectionBackground,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                              color: AppColors.lineColor),
                        ),
                        child: TextField(
                          controller: searchController,
                          style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              fontFamily: 'Cairo'),
                          onChanged: (val) {
                            setModalState(() {
                              searchQuery = val;
                            });
                          },
                          decoration: InputDecoration(
                            hintText:
                                _isAr ? 'ابحث في القائمة...' : 'Search list...',
                            hintStyle: TextStyle(
                                color: AppColors.textDark.withValues(alpha: 0.55),
                                fontSize: 13.5,
                                fontFamily: 'Cairo'),
                            prefixIcon: const Icon(Icons.search_rounded,
                                color: AppColors.secondaryTeal, size: 22),
                            suffixIcon: searchQuery.isNotEmpty
                                ? IconButton(
                                    icon: const Icon(Icons.clear_rounded,
                                        color: AppColors.textMuted, size: 20),
                                    onPressed: () {
                                      searchController.clear();
                                      setModalState(() {
                                        searchQuery = '';
                                      });
                                    },
                                  )
                                : null,
                            border: InputBorder.none,
                            contentPadding: const EdgeInsets.symmetric(
                                vertical: 10, horizontal: 12),
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),
                    ],
                    Expanded(
                      child: filteredItems.isEmpty
                          ? Center(
                              child: Text(
                                _isAr
                                    ? 'لا توجد نتائج تطابق بحثك'
                                    : 'No matching results found',
                                style: const TextStyle(
                                    color: AppColors.textMuted,
                                    fontSize: 14,
                                    fontFamily: 'Cairo'),
                              ),
                            )
                          : ListView.builder(
                              itemCount: filteredItems.length,
                              itemBuilder: (context, index) {
                                final item = filteredItems[index];
                                final String displayText = _getItemName(item);

                                final bool isSelected = currentSelection !=
                                        null &&
                                    ((item['id'] == null &&
                                            currentSelection['id'] == null) ||
                                        (item['id'] != null &&
                                            currentSelection['id'] != null &&
                                            item['id'].toString() ==
                                                currentSelection['id']
                                                    .toString())) &&
                                    displayText == currentSelectionName;

                                final bool isArab =
                                    isCountryList && _isArabCountry(item);

                                bool showArabHeader = isCountryList &&
                                    searchQuery.isEmpty &&
                                    index == 1 &&
                                    isArab;
                                bool showOthersHeader = isCountryList &&
                                    searchQuery.isEmpty &&
                                    !isArab &&
                                    item['id'] != null &&
                                    (index > 0 &&
                                        _isArabCountry(
                                            filteredItems[index - 1]));

                                return Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    if (showArabHeader) ...[
                                      Padding(
                                        padding: const EdgeInsets.only(
                                            top: 6,
                                            bottom: 8,
                                            right: 4,
                                            left: 4),
                                        child: Row(
                                          children: [
                                            const Icon(Icons.star_rounded,
                                                color: AppColors.secondaryTeal,
                                                size: 16),
                                            const SizedBox(width: 6),
                                            Text(
                                              _isAr
                                                  ? 'الدول العربية'
                                                  : 'Arab Countries',
                                              style: const TextStyle(
                                                color: AppColors.secondaryTeal,
                                                fontSize: 13,
                                                fontWeight: FontWeight.bold,
                                                fontFamily: 'Cairo',
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                    if (showOthersHeader) ...[
                                      Padding(
                                        padding: const EdgeInsets.only(
                                            top: 12,
                                            bottom: 8,
                                            right: 4,
                                            left: 4),
                                        child: Row(
                                          children: [
                                            const Icon(Icons.public_rounded,
                                                color: AppColors.textMuted,
                                                size: 16),
                                            const SizedBox(width: 6),
                                            Text(
                                              _isAr
                                                  ? 'باقي دول العالم'
                                                  : 'Other Countries',
                                              style: const TextStyle(
                                                color: AppColors.textMuted,
                                                fontSize: 13,
                                                fontWeight: FontWeight.bold,
                                                fontFamily: 'Cairo',
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                    Padding(
                                      padding:
                                          const EdgeInsets.only(bottom: 6.0),
                                      child: Material(
                                        color: Colors.transparent,
                                        child: InkWell(
                                          onTap: () {
                                            onSelect(item);
                                            Navigator.pop(context);
                                          },
                                          borderRadius:
                                              BorderRadius.circular(14),
                                          child: Container(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: 14, vertical: 13),
                                            decoration: BoxDecoration(
                                              color: isSelected
                                                  ? AppColors.primaryTeal.withValues(alpha: 0.08)
                                                  : AppColors.sectionBackground,
                                              borderRadius:
                                                  BorderRadius.circular(14),
                                              border: Border.all(
                                                color: isSelected ? AppColors.primaryTeal : AppColors.lineColor,
                                              ),
                                            ),
                                            child: Row(
                                              mainAxisAlignment:
                                                  MainAxisAlignment
                                                      .spaceBetween,
                                              children: [
                                                Row(
                                                  children: [
                                                    Text(
                                                      displayText,
                                                      style: TextStyle(
                                                        color: isSelected ? AppColors.primaryTeal : AppColors.textDark,
                                                        fontWeight: isSelected
                                                            ? FontWeight.bold
                                                            : FontWeight.w600,
                                                        fontSize: 14.5,
                                                        fontFamily: 'Cairo',
                                                      ),
                                                    ),
                                                    if (isArab) ...[
                                                      const SizedBox(width: 8),
                                                      Container(
                                                        padding:
                                                            const EdgeInsets
                                                                .symmetric(
                                                                horizontal: 7,
                                                                vertical: 2.5),
                                                        decoration:
                                                            BoxDecoration(
                                                          color: AppColors.primaryTeal.withValues(alpha: 0.08),
                                                          borderRadius:
                                                              BorderRadius
                                                                  .circular(6),
                                                          border: Border.all(color: AppColors.primaryTeal.withValues(alpha: 0.18)),
                                                        ),
                                                        child: Text(
                                                          _isAr
                                                              ? 'عربية'
                                                              : 'Arab',
                                                          style:
                                                              const TextStyle(
                                                            color: AppColors.primaryTeal,
                                                            fontSize: 10,
                                                            fontWeight:
                                                                FontWeight.bold,
                                                            fontFamily: 'Cairo',
                                                          ),
                                                        ),
                                                      ),
                                                    ],
                                                  ],
                                                ),
                                                if (isSelected)
                                                  const Icon(
                                                    Icons.check_circle_rounded,
                                                    color: AppColors.primaryTeal,
                                                    size: 20,
                                                  ),
                                              ],
                                            ),
                                          ),
                                        ),
                                      ),
                                    ),
                                  ],
                                );
                              },
                            ),
                    ),
                  ],
                ),
              );
            },
          ),
        );
      },
    ).then((_) {
      searchController.dispose();
    });
  }

  /// 🟢 بناء أزرار الخيارات (الحالة الاجتماعية، الديانة، إلخ) مع نص أبيض ناصع وواضح
  Widget _buildChoiceChipGroup({
    required String title,
    required IconData icon,
    required List<Map<String, dynamic>> items,
    required Map<String, dynamic>? selectedItem,
    required Function(Map<String, dynamic>) onSelected,
  }) {
    final String selectedItemName =
        selectedItem != null ? _getItemName(selectedItem) : '';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, color: AppColors.primaryTeal, size: 18),
            const SizedBox(width: 6),
            Expanded(
              child: Text(title, style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 13.5,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              )),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Wrap(
          spacing: 8,
          runSpacing: 9,
          children: items.map((item) {
            final String text = _getItemName(item);
            final bool isSelected = selectedItem != null &&
                ((item['id'] == null && selectedItem['id'] == null) ||
                    (item['id'] != null &&
                        selectedItem['id'] != null &&
                        item['id'].toString() == selectedItem['id'].toString())) &&
                text == selectedItemName;
            return Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: () => onSelected(item),
                borderRadius: BorderRadius.circular(13),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                  decoration: BoxDecoration(
                    color: isSelected ? AppColors.primaryTeal : AppColors.sectionBackground,
                    borderRadius: BorderRadius.circular(13),
                    border: Border.all(
                      color: isSelected ? AppColors.primaryTeal : AppColors.lineColor,
                    ),
                  ),
                  child: Text(text, style: TextStyle(
                    color: isSelected ? Colors.white : AppColors.textDark,
                    fontSize: 12.5,
                    fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                    fontFamily: 'Cairo',
                  )),
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }

  Widget _buildFilterSelectorTile({
    required String label,
    required String value,
    required IconData icon,
    required VoidCallback onTap,
    bool isLoading = false,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: isLoading ? null : onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
          decoration: BoxDecoration(
            color: AppColors.cardColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.lineColor),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: const BoxDecoration(
                  color: AppColors.sectionBackground,
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: AppColors.primaryTeal, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(label, style: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                      fontFamily: 'Cairo',
                    )),
                    const SizedBox(height: 2),
                    Text(value, maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.textDark,
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      )),
                  ],
                ),
              ),
              if (isLoading)
                const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: AppColors.primaryTeal,
                  ),
                )
              else
                const Icon(Icons.keyboard_arrow_down_rounded,
                  color: AppColors.textMuted, size: 22),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 12, 16, 8),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.txt('search'), style: const TextStyle(
                  color: AppColors.textDark,
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  fontFamily: 'Cairo',
                )),
                const SizedBox(height: 2),
                Text(
                  _isAr
                      ? 'حدّد المواصفات الأقرب لما تبحث عنه'
                      : 'Choose the preferences that matter to you',
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 12,
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
              customBorder: const CircleBorder(),
              onTap: widget.onNotificationPressed,
              child: SizedBox(
                width: 42,
                height: 42,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    const Icon(Icons.notifications_none_rounded,
                      color: AppColors.primaryTeal, size: 21),
                    if (widget.unreadNotificationCount > 0)
                      Positioned(
                        top: 5,
                        right: 5,
                        child: Container(
                          constraints: const BoxConstraints(minWidth: 15),
                          padding: const EdgeInsets.all(3),
                          decoration: const BoxDecoration(
                            color: AppColors.accentWarm,
                            shape: BoxShape.circle,
                          ),
                          child: Text(
                            '${widget.unreadNotificationCount}',
                            textAlign: TextAlign.center,
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
    );
  }

  @override
  Widget build(BuildContext context) {
    final String currentCountryText = _getItemName(_selectedCountry);
    final String currentCityText = _getItemName(_selectedCity);

    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: Column(
        children: [
          _buildHeader(),
          const SizedBox(height: 4),
          Expanded(
            child: _isLoadingDropdowns
                ? const Center(
                    child: CircularProgressIndicator(
                      color: AppColors.primaryTeal,
                    ),
                  )
                : SingleChildScrollView(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: AppColors.cardColor,
                            borderRadius: BorderRadius.circular(24),
                            border: Border.all(
                                color: AppColors.lineColor),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Row(
                                      children: [
                                        const Icon(Icons.tune_rounded,
                                            color: AppColors.primaryTeal, size: 20),
                                        const SizedBox(width: 8),
                                        Expanded(
                                          child: Text(
                                            widget.txt('advanced_search'),
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(
                                              color: AppColors.textDark,
                                              fontSize: 16,
                                              fontWeight: FontWeight.bold,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  InkWell(
                                    onTap: _resetFilters,
                                    borderRadius: BorderRadius.circular(10),
                                    child: Padding(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 8, vertical: 5),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(Icons.refresh_rounded,
                                              color: AppColors.primaryTeal, size: 16),
                                          const SizedBox(width: 4),
                                          Text(
                                            _isAr ? 'إعادة ضبط' : 'Reset',
                                            style: const TextStyle(
                                                color: AppColors.textDark,
                                                fontSize: 12.5,
                                                fontWeight: FontWeight.w600,
                                                fontFamily: 'Cairo'),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 16),

                              // 1. شريط المدى العمري
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      Row(
                                        children: [
                                          const Icon(Icons.cake_rounded,
                                              color: AppColors.secondaryTeal,
                                              size: 18),
                                          const SizedBox(width: 6),
                                          Text(
                                            _isAr
                                                ? 'المدى العمري'
                                                : 'Age Range',
                                            style: const TextStyle(
                                              color: AppColors.textDark,
                                              fontSize: 13.5,
                                              fontWeight: FontWeight.bold,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                        ],
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 11, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: AppColors.secondaryTeal
                                              .withValues(alpha: 0.20),
                                          borderRadius:
                                              BorderRadius.circular(10),
                                          border: Border.all(
                                              color: AppColors.secondaryTeal
                                                  .withValues(alpha: 0.4)),
                                        ),
                                        child: Text(
                                          '${_ageRange.start.round()} - ${_ageRange.end.round()} ${_isAr ? 'سنة' : 'yrs'}',
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
                                  const SizedBox(height: 6),
                                  SliderTheme(
                                    data: SliderTheme.of(context).copyWith(
                                      activeTrackColor: AppColors.primaryTeal,
                                      inactiveTrackColor: AppColors.lineColor,
                                      thumbColor: AppColors.accentWarm,
                                      overlayColor: AppColors.secondaryTeal
                                          .withValues(alpha: 0.25),
                                      trackHeight: 5,
                                    ),
                                    child: RangeSlider(
                                      values: _ageRange,
                                      min: 18,
                                      max: 70,
                                      divisions: 52,
                                      onChanged: (RangeValues values) {
                                        setState(() {
                                          _ageRange = values;
                                        });
                                      },
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),

                              // 2. دولة الإقامة
                              _buildFilterSelectorTile(
                                label: _isAr
                                    ? 'دولة الإقامة'
                                    : 'Country of Residence',
                                value: currentCountryText,
                                icon: Icons.public_rounded,
                                onTap: () {
                                  _showSelectionBottomSheet(
                                    title: _isAr
                                        ? 'اختر دولة الإقامة'
                                        : 'Select Country',
                                    items: _countries,
                                    currentSelection: _selectedCountry,
                                    onSelect: _onCountrySelected,
                                    enableSearch: true,
                                    isCountryList: true,
                                  );
                                },
                              ),
                              const SizedBox(height: 10),

                              // 3. المدينة / المحافظة
                              _buildFilterSelectorTile(
                                label: widget.txt('residence_filter'),
                                value: currentCityText,
                                icon: Icons.location_on_rounded,
                                isLoading: _isLoadingCities,
                                onTap: () {
                                  _showSelectionBottomSheet(
                                    title: widget.txt('residence_filter'),
                                    items: _cities,
                                    currentSelection: _selectedCity,
                                    onSelect: (item) =>
                                        setState(() => _selectedCity = item),
                                    enableSearch: true,
                                  );
                                },
                              ),
                              const SizedBox(height: 16),

                              // 4. الحالة الاجتماعية
                              _buildChoiceChipGroup(
                                title: widget.txt('status_filter'),
                                icon: Icons.favorite_rounded,
                                items: _statuses,
                                selectedItem: _selectedStatus,
                                onSelected: (item) =>
                                    setState(() => _selectedStatus = item),
                              ),
                              const SizedBox(height: 16),

                              // 5. الديانة
                              _buildChoiceChipGroup(
                                title: _isAr ? 'الديانة' : 'Religion',
                                icon: Icons.auto_awesome_rounded,
                                items: _religions,
                                selectedItem: _selectedReligion,
                                onSelected: (item) =>
                                    setState(() => _selectedReligion = item),
                              ),
                              const SizedBox(height: 22),

                              // زر تطبيق البحث والانتقال للواجهة المنفصلة
                              SizedBox(
                                width: double.infinity,
                                height: 50,
                                child: ElevatedButton.icon(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.primaryTeal,
                                    foregroundColor: Colors.white,
                                    elevation: 4,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                  ),
                                  onPressed: _navigateToResults,
                                  icon: const Icon(Icons.search_rounded,
                                      size: 22),
                                  label: Text(
                                    widget.txt('apply_search'),
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
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
                  ),
          ),
        ],
      ),
    );
  }
}

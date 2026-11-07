// ============================================================================
// الملف: lib/screens/widgets/step3_residency_religion.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: المرحلة 3 - المنشأ والإقامة مع الدعم الكامل للغتين والتأقلم مع الاتجاه.
// ============================================================================
import 'package:flutter/material.dart';
import '../../constants/colors.dart';
import 'package:wesal_app/widgets/bottom_system_inset.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../services/api_service.dart';

class Step3ResidencyReligion extends StatefulWidget {
  final GlobalKey<FormState> formKey;
  final String? birthCountryId;
  final String? residenceCountryId;
  final String? stateId;
  final String? cityId;
  final String? religionId;
  final String? maritalStatusId;
  final TextEditingController childrenCountController;
  final String lang;

  final ValueChanged<String> onBirthCountryChanged;
  final ValueChanged<String> onResidenceCountryChanged;
  final ValueChanged<String?> onStateChanged;
  final ValueChanged<String?> onCityChanged;
  final ValueChanged<String> onReligionChanged;
  final ValueChanged<String> onMaritalStatusChanged;

  const Step3ResidencyReligion({
    super.key,
    required this.formKey,
    required this.birthCountryId,
    required this.residenceCountryId,
    required this.stateId,
    required this.cityId,
    required this.religionId,
    required this.maritalStatusId,
    required this.childrenCountController,
    this.lang = 'ar',
    required this.onBirthCountryChanged,
    required this.onResidenceCountryChanged,
    required this.onStateChanged,
    required this.onCityChanged,
    required this.onReligionChanged,
    required this.onMaritalStatusChanged,
  });

  @override
  State<Step3ResidencyReligion> createState() => _Step3ResidencyReligionState();
}

class _Step3ResidencyReligionState extends State<Step3ResidencyReligion> {
  List<dynamic> _countries = [];
  List<dynamic> _states = [];
  List<dynamic> _cities = [];
  List<dynamic> _religions = [];
  List<dynamic> _maritalStatuses = [];

  bool _isLoadingCountries = true;
  bool _isLoadingStates = false;
  bool _isLoadingCities = false;

  // عدادات تتبع تسلسل الطلبات لمنع تضارب الاستجابات (Race Condition)
  int _statesRequestToken = 0;
  int _citiesRequestToken = 0;

  bool get _isAr => widget.lang == 'ar';
  bool get _requiresChildrenCount => const {'2', '3', '4'}.contains(widget.maritalStatusId);

  @override
  void initState() {
    super.initState();
    _loadInitialDropdowns();
  }

  @override
  void didUpdateWidget(covariant Step3ResidencyReligion oldWidget) {
    super.didUpdateWidget(oldWidget);

    if (widget.residenceCountryId != oldWidget.residenceCountryId) {
      if (widget.residenceCountryId != null &&
          widget.residenceCountryId!.isNotEmpty) {
        _loadStatesForCountry(widget.residenceCountryId!);
      } else {
        setState(() {
          _states = [];
          _cities = [];
          _isLoadingStates = false;
          _isLoadingCities = false;
        });
      }
    } else if (widget.stateId != oldWidget.stateId) {
      if (widget.stateId != null && widget.stateId!.isNotEmpty) {
        _loadCitiesForState(widget.stateId!);
      } else {
        setState(() {
          _cities = [];
          _isLoadingCities = false;
        });
      }
    }
  }

  Future<void> _loadInitialDropdowns() async {
    try {
      final dropdowns = await ApiService.getProfileDropdowns(lang: widget.lang);
      if (mounted) {
        if (dropdowns != null) {
          setState(() {
            _countries = _sortCountriesWithArabFirst(
                dropdowns['countries'] ?? dropdowns['country'] ?? []);
            _religions = dropdowns['religions'] ?? [];
            _maritalStatuses = dropdowns['maritial_status'] ??
                dropdowns['marital_statuses'] ??
                [];
            _isLoadingCountries = false;
          });

          if (widget.residenceCountryId != null &&
              widget.residenceCountryId!.isNotEmpty) {
            _loadStatesForCountry(widget.residenceCountryId!);
          }
        } else {
          setState(() => _isLoadingCountries = false);
        }
      }
    } catch (e) {
      debugPrint('⚠️ [Step3 Dropdown Error]: $e');
      if (mounted) setState(() => _isLoadingCountries = false);
    }
  }

  Future<void> _loadStatesForCountry(String countryId) async {
    if (!mounted) return;

    _statesRequestToken++;
    final int currentToken = _statesRequestToken;

    setState(() {
      _isLoadingStates = true;
      _states = [];
      _cities = [];
    });

    try {
      final parsedId = int.tryParse(countryId) ?? 0;
      final resStates =
          await ApiService.getStatesByCountry(parsedId, lang: widget.lang);

      if (mounted && currentToken == _statesRequestToken) {
        setState(() {
          _states = resStates;
          _isLoadingStates = false;
        });
        if (widget.stateId != null && widget.stateId!.isNotEmpty) {
          _loadCitiesForState(widget.stateId!);
        }
      }
    } catch (e) {
      debugPrint('⚠️ [Step3 States Error]: $e');
      if (mounted && currentToken == _statesRequestToken) {
        setState(() => _isLoadingStates = false);
      }
    }
  }

  Future<void> _loadCitiesForState(String stateId) async {
    if (!mounted) return;

    _citiesRequestToken++;
    final int currentToken = _citiesRequestToken;

    setState(() {
      _isLoadingCities = true;
      _cities = [];
    });

    try {
      final parsedId = int.tryParse(stateId) ?? 0;
      List<dynamic> resCities = [];
      try {
        resCities =
            await ApiService.getCitiesByState(parsedId, lang: widget.lang);
      } catch (_) {
        resCities = [];
      }
      if (mounted && currentToken == _citiesRequestToken) {
        setState(() {
          _cities = resCities;
          _isLoadingCities = false;
        });
      }
    } catch (e) {
      debugPrint('⚠️ [Step3 Cities Error]: $e');
      if (mounted && currentToken == _citiesRequestToken) {
        setState(() => _isLoadingCities = false);
      }
    }
  }

  List<dynamic> _sortCountriesWithArabFirst(List<dynamic> countriesList) {
    if (countriesList.isEmpty) return [];
    final Set<String> arabCodes = const {
      'SY',
      'SA',
      'AE',
      'EG',
      'JO',
      'LB',
      'IQ',
      'KW',
      'QA',
      'BH',
      'OM',
      'YE',
      'PS',
      'DZ',
      'MA',
      'TN',
      'LY',
      'SD',
      'SO',
      'MR',
      'DJ',
      'KM'
    };
    final List<dynamic> arab = [];
    final List<dynamic> other = [];

    for (var c in countriesList) {
      if (c is Map) {
        final code =
            (c['code'] ?? c['iso2'] ?? '').toString().toUpperCase().trim();
        final name = (c['name'] ?? '').toString().toLowerCase();
        final nameAr = (c['name_ar'] ?? c['name_arabic'] ?? '').toString();

        if (arabCodes.contains(code) ||
            name.contains('syria') ||
            nameAr.contains('سوريا')) {
          arab.add(c);
        } else {
          other.add(c);
        }
      }
    }

    arab.sort((a, b) {
      final codeA = (a['code'] ?? '').toString().toUpperCase();
      final codeB = (b['code'] ?? '').toString().toUpperCase();
      if (codeA == 'SY' && codeB == 'SY') return 0;
      if (codeA == 'SY') return -1;
      if (codeB == 'SY') return 1;
      return 0;
    });

    return [...arab, ...other];
  }

  String _getLocalizedName(Map item) {
    if (_isAr) {
      return (item['name_ar'] ?? item['name_arabic'] ?? item['name'] ?? '')
          .toString();
    }
    return (item['name_en'] ?? item['name'] ?? '').toString();
  }

  String _getCountryNameById(String? id) {
    if (id == null || _countries.isEmpty) {
      return _isAr ? 'حدد البلد *' : 'Select Country *';
    }
    for (var c in _countries) {
      if (c is Map && c['id'].toString() == id) {
        final name = _getLocalizedName(c);
        return name.isNotEmpty ? name : (c['name'] ?? '').toString();
      }
    }
    return _isAr ? 'حدد البلد *' : 'Select Country *';
  }

  String _getStateNameById(String? id) {
    if (id == null || _states.isEmpty) {
      return _isAr ? 'حدد المحافظة *' : 'Select State *';
    }
    for (var s in _states) {
      if (s is Map && s['id'].toString() == id) {
        final name = _getLocalizedName(s);
        return name.isNotEmpty ? name : (s['name'] ?? '').toString();
      }
    }
    return _isAr ? 'حدد المحافظة *' : 'Select State *';
  }

  String _getCityNameById(String? id) {
    if (id == null || _cities.isEmpty) {
      return _isAr ? 'حدد المدينة (اختر المحافظة أولاً) *' : 'Select City *';
    }
    for (var c in _cities) {
      if (c is Map && c['id'].toString() == id) {
        final name = _getLocalizedName(c);
        return name.isNotEmpty ? name : (c['name'] ?? '').toString();
      }
    }
    return _isAr ? 'حدد المدينة *' : 'Select City *';
  }

  void _showCountryPicker(String title, ValueChanged<String> onSelected) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.cardColor,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) {
        String query = '';
        return StatefulBuilder(
          builder: (context, setModalState) {
            final filtered = _countries.where((c) {
              if (c is! Map) return false;
              final name = (c['name'] ?? '').toString().toLowerCase();
              final nameAr = (c['name_ar'] ?? c['name_arabic'] ?? '')
                  .toString()
                  .toLowerCase();
              final q = query.toLowerCase().trim();
              return name.contains(q) || nameAr.contains(q);
            }).toList();

            return Directionality(
              textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
              child: AnimatedPadding(
                duration: const Duration(milliseconds: 150),
                padding: EdgeInsets.only(
                    bottom: WesalBottomInsets.of(context)),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  height: MediaQuery.of(context).size.height * 0.7,
                  child: Column(
                    children: [
                      Text(title,
                          style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 17,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo')),
                      const SizedBox(height: 12),
                      TextField(
                        style: const TextStyle(
                            color: AppColors.textDark,
                            fontFamily: 'Cairo',
                            fontSize: 13.5),
                        decoration: InputDecoration(
                          hintText: _isAr
                              ? 'ابحث باسم الدولة...'
                              : 'Search country...',
                          hintStyle: const TextStyle(
                              color: AppColors.textMuted, fontFamily: 'Cairo'),
                          prefixIcon: const Icon(Icons.search,
                              color: AppColors.accentWarm),
                          filled: true,
                          fillColor: AppColors.sectionBackground,
                          contentPadding: const EdgeInsets.symmetric(
                              horizontal: 12, vertical: 10),
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide.none),
                        ),
                        onChanged: (val) => setModalState(() => query = val),
                      ),
                      const SizedBox(height: 12),
                      Expanded(
                        child: ListView.builder(
                          itemCount: filtered.length,
                          itemBuilder: (context, index) {
                            final country = filtered[index];
                            if (country is! Map) return const SizedBox.shrink();
                            final flagUrl = ApiService.getOptimizedFlagUrl(
                                Map<String, dynamic>.from(country));
                            final countryName = _getLocalizedName(country);
                            return ListTile(
                              leading: ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: CachedNetworkImage(
                                    imageUrl: flagUrl,
                                    width: 32,
                                    height: 22,
                                    fit: BoxFit.cover,
                                    errorWidget: (c, u, e) => const Icon(
                                        Icons.flag,
                                        color: AppColors.textMuted)),
                              ),
                              title: Text(
                                  countryName.isNotEmpty
                                      ? countryName
                                      : (country['name'] ?? '').toString(),
                                  style: const TextStyle(
                                      color: AppColors.textDark,
                                      fontFamily: 'Cairo',
                                      fontWeight: FontWeight.bold)),
                              onTap: () {
                                onSelected(country['id'].toString());
                                Navigator.pop(context);
                              },
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _showItemPicker(String title, List<Map<String, String>> items,
      ValueChanged<String> onSelected) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.cardColor,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) => Directionality(
        textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + WesalBottomInsets.of(context)),
          height: MediaQuery.of(context).size.height * 0.55,
          child: Column(
            children: [
              Text(title,
                  style: const TextStyle(
                      color: AppColors.textDark,
                      fontSize: 17,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo')),
              const SizedBox(height: 12),
              Expanded(
                child: items.isEmpty
                    ? Center(
                        child: Text(
                          _isAr
                              ? 'لا توجد بيانات متاحة'
                              : 'No options available',
                          style: const TextStyle(
                              color: AppColors.textMuted, fontFamily: 'Cairo'),
                        ),
                      )
                    : ListView.builder(
                        itemCount: items.length,
                        itemBuilder: (context, index) {
                          final item = items[index];
                          return ListTile(
                            title: Text(
                              item['name']!,
                              textAlign: _isAr ? TextAlign.right : TextAlign.left,
                              textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
                              style: const TextStyle(
                                    color: AppColors.textDark,
                                    fontFamily: 'Cairo',
                                    fontWeight: FontWeight.bold)),
                            onTap: () {
                              onSelected(item['id']!);
                              Navigator.pop(context);
                            },
                          );
                        },
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildQuestionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(top: 14, bottom: 8),
      child: Text(
        title,
        style: const TextStyle(
          color: AppColors.primaryTeal,
          fontSize: 15,
          fontWeight: FontWeight.bold,
          fontFamily: 'Cairo',
        ),
      ),
    );
  }

  Widget _buildCustomChip({
    required String label,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.accentLight.withValues(alpha: 0.42)
              : AppColors.sectionBackground,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? AppColors.accentWarm : AppColors.lineColor,
            width: 1.5,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (isSelected) ...[
              const Icon(Icons.check_circle_rounded,
                  size: 15, color: AppColors.primaryTeal),
              const SizedBox(width: 5),
            ],
            Text(
              label,
              style: TextStyle(
                color: isSelected ? AppColors.primaryTeal : AppColors.textDark,
                fontWeight: FontWeight.bold,
                fontSize: 13,
                fontFamily: 'Cairo',
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPickerTile({
    required String label,
    required String value,
    required IconData icon,
    required VoidCallback onTap,
    bool isSelected = false,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: AppColors.sectionBackground,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? AppColors.accentWarm : AppColors.lineColor,
            width: 1.2,
          ),
        ),
        child: Row(
          children: [
            Icon(icon, color: AppColors.primaryTeal, size: 20),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                label,
                textAlign: TextAlign.start,
                textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
                style: const TextStyle(
                  color: AppColors.textMuted,
                  fontSize: 13,
                  fontFamily: 'Cairo',
                ),
              ),
            ),
            Text(
              value,
              textAlign: TextAlign.start,
              textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
              style: TextStyle(
                color: isSelected ? AppColors.primaryTeal : AppColors.textDark,
                fontWeight: FontWeight.bold,
                fontSize: 13.5,
                fontFamily: 'Cairo',
              ),
            ),
            const SizedBox(width: 6),
            const Icon(Icons.keyboard_arrow_down_rounded,
                color: AppColors.textMuted, size: 20),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
        child: Form(
          key: widget.formKey,
          child: Container(
            padding: const EdgeInsets.all(18.0),
            decoration: BoxDecoration(
              color: AppColors.cardColor,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(
                color: AppColors.lineColor,
                width: 1.2,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Text(
                    _isAr
                        ? 'الخطوة الثالثة: من أين أنت وأين تعيش؟ 🏠🌍'
                        : 'Step 3: Residency & Background 🏠🌍',
                    style: const TextStyle(
                      fontSize: 19,
                      color: AppColors.textDark,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // 1. بلد المنشأ
                _buildQuestionTitle(
                  _isAr
                      ? '1. في أي بلد وُلدت ونشأت؟'
                      : '1. What is your country of birth?',
                ),
                if (_isLoadingCountries)
                  const Center(
                      child:
                          CircularProgressIndicator(color: AppColors.accentWarm))
                else
                  _buildPickerTile(
                    label: _isAr ? 'بلد الميلاد والمنشأ' : 'Birth Country',
                    value: _getCountryNameById(widget.birthCountryId),
                    icon: Icons.flag_rounded,
                    isSelected: widget.birthCountryId != null,
                    onTap: () {
                      _showCountryPicker(
                        _isAr ? 'اختر بلد المنشأ' : 'Select Birth Country',
                        widget.onBirthCountryChanged,
                      );
                    },
                  ),
                const SizedBox(height: 14),

                // 2. بلد الإقامة والمحافظة والمدينة
                _buildQuestionTitle(
                  _isAr
                      ? '2. ما البلد الذي تقيم فيه حالياً؟'
                      : '2. Where do you currently live?',
                ),
                if (_isLoadingCountries)
                  const Center(
                      child:
                          CircularProgressIndicator(color: AppColors.accentWarm))
                else
                  _buildPickerTile(
                    label: _isAr ? 'بلد الإقامة' : 'Residence Country',
                    value: _getCountryNameById(widget.residenceCountryId),
                    icon: Icons.public_rounded,
                    isSelected: widget.residenceCountryId != null,
                    onTap: () {
                      _showCountryPicker(
                        _isAr ? 'اختر بلد الإقامة' : 'Select Residence Country',
                        (v) {
                          widget.onResidenceCountryChanged(v);
                          widget.onStateChanged(null);
                          widget.onCityChanged(null);
                          _loadStatesForCountry(v);
                        },
                      );
                    },
                  ),
                const SizedBox(height: 10),

                if (_isLoadingStates)
                  const Center(
                      child: Padding(
                          padding: EdgeInsets.all(8.0),
                          child: CircularProgressIndicator(
                              color: AppColors.accentWarm)))
                else
                  _buildPickerTile(
                    label:
                        _isAr ? 'المحافظة / المقاطعة' : 'Governorate / State',
                    value: _getStateNameById(widget.stateId),
                    icon: Icons.location_city_rounded,
                    isSelected: widget.stateId != null,
                    onTap: () {
                      if (widget.residenceCountryId == null) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                          content: Text(
                              _isAr
                                  ? 'يرجى اختيار بلد الإقامة أولاً'
                                  : 'Select Country first',
                              style: const TextStyle(fontFamily: 'Cairo')),
                          backgroundColor: AppColors.warning,
                        ));
                        return;
                      }
                      final items = _states
                          .whereType<Map>()
                          .map((s) => {
                                'id': (s['id'] ?? '').toString(),
                                'name': _getLocalizedName(s)
                              })
                          .toList();
                      _showItemPicker(
                        _isAr ? 'اختر المحافظة' : 'Select State',
                        items,
                        (stId) {
                          widget.onStateChanged(stId);
                          widget.onCityChanged(null);
                          _loadCitiesForState(stId);
                        },
                      );
                    },
                  ),
                const SizedBox(height: 10),

                if (_isLoadingCities)
                  const Center(
                      child: Padding(
                          padding: EdgeInsets.all(8.0),
                          child: CircularProgressIndicator(
                              color: AppColors.accentWarm)))
                else
                  _buildPickerTile(
                    label: _isAr ? 'المدينة' : 'City',
                    value: _getCityNameById(widget.cityId),
                    icon: Icons.holiday_village_rounded,
                    isSelected: widget.cityId != null,
                    onTap: () {
                      if (widget.stateId == null) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                          content: Text(
                              _isAr
                                  ? 'يرجى اختيار المحافظة أولاً'
                                  : 'Select State first',
                              style: const TextStyle(fontFamily: 'Cairo')),
                          backgroundColor: AppColors.warning,
                        ));
                        return;
                      }
                      final items = _cities
                          .whereType<Map>()
                          .map((c) => {
                                'id': (c['id'] ?? '').toString(),
                                'name': _getLocalizedName(c)
                              })
                          .toList();
                      _showItemPicker(
                        _isAr ? 'اختر المدينة' : 'Select City',
                        items,
                        widget.onCityChanged,
                      );
                    },
                  ),
                const SizedBox(height: 16),

                // 3. الحالة الاجتماعية
                _buildQuestionTitle(
                  _isAr
                      ? '3. كيف تصف حالتك الاجتماعية اليوم؟'
                      : '3. What is your marital status?',
                ),
                if (_maritalStatuses.isNotEmpty)
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _maritalStatuses.whereType<Map>().map((ms) {
                      final id = (ms['id'] ?? '').toString();
                      final name = _getLocalizedName(ms);
                      return _buildCustomChip(
                        label: name.isNotEmpty
                            ? name
                            : (ms['name'] ?? '').toString(),
                        isSelected: widget.maritalStatusId == id,
                        onTap: () => widget.onMaritalStatusChanged(id),
                      );
                    }).toList(),
                  )
                else
                  const CircularProgressIndicator(color: AppColors.accentWarm),
                const SizedBox(height: 16),

                                const SizedBox(height: 16),
                if (_requiresChildrenCount) ...[
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: widget.childrenCountController,
                    keyboardType: TextInputType.number,
                    textDirection: TextDirection.ltr,
                    decoration: InputDecoration(
                      labelText: _isAr ? 'كم طفلاً لديك؟ 👶' : 'How many children do you have? 👶',
                      hintText: _isAr ? 'اكتب العدد، أو 0 إن لم يكن لديك أطفال' : 'Enter a number, or 0 if none',
                      filled: true,
                      fillColor: AppColors.cardColor,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    validator: (value) {
                      if (!_requiresChildrenCount) return null;
                      final count = int.tryParse((value ?? '').trim());
                      if (count == null || count < 0) {
                        return _isAr ? 'يرجى إدخال عدد صحيح (0 أو أكثر)' : 'Enter a whole number (0 or more)';
                      }
                      return null;
                    },
                  ),
                ],
                const SizedBox(height: 16),

                // 4. الديانة
                _buildQuestionTitle(
                  _isAr
                      ? '4. ما الديانة التي تود إضافتها إلى ملفك؟'
                      : '4. What is your religion?',
                ),
                if (_religions.isNotEmpty)
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _religions.whereType<Map>().map((rel) {
                      final id = (rel['id'] ?? '').toString();
                      final name = _getLocalizedName(rel);
                      return _buildCustomChip(
                        label: name.isNotEmpty
                            ? name
                            : (rel['name'] ?? '').toString(),
                        isSelected: widget.religionId == id,
                        onTap: () => widget.onReligionChanged(id),
                      );
                    }).toList(),
                  )
                else
                  const CircularProgressIndicator(color: AppColors.accentWarm),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

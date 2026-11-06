// ============================================================================
// اسم الملف: lib/screens/partner_expectations_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: واجهة توقعات ومواصفات الشريك المحصنة ضد أخطاء Null ومعالجة
//        البيانات والارتباط المباشر مع خدمات API وتنبيهات AppSnackBar.
// ============================================================================

import 'dart:async';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../services/api_service.dart';
import '../widgets/app_snackbar.dart';

/// 1️⃣ كلاس مخصص للحد من تكرار استدعاءات البحث أثناء الكتابة (Debouncer)
class Debouncer {
  final int milliseconds;
  Timer? _timer;

  Debouncer({this.milliseconds = 200});

  void run(VoidCallback action) {
    _timer?.cancel();
    _timer = Timer(Duration(milliseconds: milliseconds), action);
  }

  void dispose() {
    _timer?.cancel();
  }
}

class PartnerExpectationsScreen extends StatefulWidget {
  final String currentLanguage;

  const PartnerExpectationsScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<PartnerExpectationsScreen> createState() =>
      _PartnerExpectationsScreenState();
}

class _PartnerExpectationsScreenState extends State<PartnerExpectationsScreen> {
  // حالات التحميل والحفظ
  bool _isLoading = true;
  bool _isSaving = false;
  bool _isLoadingStates = false;
  int _statesRequestGeneration = 0;

  // متحكمات النصوص
  final _generalCtrl = TextEditingController();
  final _professionCtrl = TextEditingController();

  // النطاقات المحددة بالطول والوزن
  RangeValues _partnerAgeRange = const RangeValues(22, 45);
  RangeValues _partnerHeightRange = const RangeValues(155, 180);
  RangeValues _partnerWeightRange = const RangeValues(50, 80);
  bool _ageRangeEdited = false;
  bool _ageRangeCleared = false;
  bool _locationPreferenceEdited = false;
  bool _heightRangeEdited = false;
  bool _weightRangeEdited = false;
  bool _generalWasLoaded = false;
  bool _professionWasLoaded = false;

  // المتغيرات المحددة للخيارات
  String? _selectedEducation;
  String? _selectedMaritalStatusId;
  String _selectedChildrenAcceptable = 'does_not_matter';
  String? _selectedReligionId;
  String? _selectedLanguageId;
  String _selectedSmokingAcceptable = 'does_not_matter';
  String _selectedDrinkingAcceptable = 'does_not_matter';
  String? _selectedComplexion;
  String? _selectedEyeColor;
  String? _selectedHairColor;
  String? _selectedPartnerCountryId;
  String? _selectedStateId;

  // القوائم القادمة من السيرفر
  List<dynamic> _countries = [];
  List<dynamic> _states = [];
  List<dynamic> _maritalStatuses = [];
  List<dynamic> _religions = [];
  List<dynamic> _languages = [];

  // القوائم الثابتة للخيارات المحلية
  final List<String> _educationDegrees = [
    'إعدادية',
    'ثانوية',
    'دبلوم',
    'بكالوريوس',
    'ماجستير',
    'دكتوراه',
    'لا يهم'
  ];

  final List<String> _complexionOptions = [
    'أبيض',
    'حنطي فاتح',
    'حنطي',
    'أسمر',
    'أسمر داكن',
    'لا يهم'
  ];
  final List<String> _eyeColorOptions = [
    'بني',
    'أسود',
    'أزرق',
    'أخضر',
    'عسلي',
    'رمادي',
    'لا يهم',
  ];
  final List<String> _hairColorOptions = [
    'أسود',
    'بني داكن',
    'بني فاتح',
    'أشقر',
    'أحمر',
    'رمادي / شيب',
    'لا يهم',
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadDataOptimized();
    });
  }

  @override
  void dispose() {
    _generalCtrl.dispose();
    _professionCtrl.dispose();
    super.dispose();
  }

  /// تنظيف النصوص للمقارنة ومطابقة الخيارات
  String _cleanText(String text) {
    return text
        .replaceAll(RegExp(r'[^\w\s\u0600-\u06FF]'), '')
        .trim()
        .toLowerCase();
  }

  /// توحيد القيمة القديمة مع القيمة المعتمدة التي يعيدها الـAPI.
  String _normalizeAcceptability(dynamic raw) {
    final value = (raw ?? '').toString().trim().toLowerCase();
    if (value == 'dose_not_matter' || value == 'does_not_matter' || value.isEmpty) {
      return 'does_not_matter';
    }
    return value == 'yes' || value == 'no' ? value : 'does_not_matter';
  }

  /// 2️⃣ جلب البيانات والقوائم المنسدلة من السيرفر بشكل محسن
  Future<void> _loadDataOptimized() async {
    final lang = widget.currentLanguage;
    try {
      await Future.delayed(const Duration(milliseconds: 150));

      final results = await Future.wait([
        ApiService.getProfileDropdowns(lang: lang),
        ApiService.getPartnerExpectations(lang: lang),
        ApiService.get('/member/languages', lang: lang),
      ]);

      final dropdowns = results[0];
      final expectations = results[1];
      final languageResponse = results[2];

      if (!mounted) return;

      if (dropdowns != null) {
        _maritalStatuses = (dropdowns['maritial_status'] ??
            dropdowns['marital_statuses'] ??
            []) as List<dynamic>;
        _countries = (dropdowns['countries'] ??
            dropdowns['country'] ??
            dropdowns['country_list'] ??
            []) as List<dynamic>;
        _religions = (dropdowns['religions'] ?? []) as List<dynamic>;
      }

      final languageMap = languageResponse is Map ? languageResponse : null;
      final nestedLanguageData = languageMap?['data'];
      final rawLanguages =
          languageMap?['languages'] ?? nestedLanguageData?['languages'];
      _languages = rawLanguages is List
          ? rawLanguages.whereType<Map>().toList()
          : <dynamic>[];

      if (expectations != null) {
        _generalWasLoaded = expectations.containsKey('general');
        _professionWasLoaded = expectations.containsKey('profession') ||
            expectations.containsKey('partner_profession');
        _generalCtrl.text = expectations['general']?.toString() ?? '';
        _professionCtrl.text = expectations['profession']?.toString() ??
            expectations['partner_profession']?.toString() ??
            '';

        final rawMinAge = int.tryParse(
            (expectations['min_age'] ?? expectations['partner_age'])?.toString() ?? '');
        final rawMaxAge = int.tryParse(
            (expectations['max_age'] ?? expectations['partner_max_age'])?.toString() ?? '');
        if (rawMinAge != null || rawMaxAge != null) {
          final minAge = (rawMinAge ?? 18).clamp(18, 80).toDouble();
          final maxAge = (rawMaxAge ?? 80).clamp(18, 80).toDouble();
          if (minAge <= maxAge) {
            _partnerAgeRange = RangeValues(minAge, maxAge);
          }
        }

        final eduVal = expectations['education'] ??
            expectations['partner_education'] ??
            expectations['pertner_education'];
        if (eduVal != null && eduVal.toString().isNotEmpty) {
          _selectedEducation = eduVal.toString().trim();
        }

        final compVal =
            expectations['complexion'] ?? expectations['pertner_complexion'];
        if (compVal != null && compVal.toString().isNotEmpty) {
          _selectedComplexion = compVal.toString().trim();
        }
        final eyeVal = expectations['eye_color'] ?? expectations['partner_eye_color'];
        final hairVal = expectations['hair_color'] ?? expectations['partner_hair_color'];
        _selectedEyeColor = eyeVal?.toString().trim();
        _selectedHairColor = hairVal?.toString().trim();

        // Restore exact partner height range when it is available.
        final rawMinHeight = double.tryParse(
            expectations['min_height']?.toString() ?? '');
        final rawMaxHeight = double.tryParse(
            expectations['max_height']?.toString() ?? '');
        if (rawMinHeight != null && rawMaxHeight != null) {
          final minCm = (rawMinHeight > 0 && rawMinHeight < 10)
              ? rawMinHeight * 100
              : rawMinHeight;
          final maxCm = (rawMaxHeight > 0 && rawMaxHeight < 10)
              ? rawMaxHeight * 100
              : rawMaxHeight;
          if (minCm >= 140 && maxCm <= 210 && minCm <= maxCm) {
            _partnerHeightRange = RangeValues(minCm, maxCm);
          }
        } else {
          final rawVal = expectations['partner_height'] ?? expectations['height'];
          final rawH = double.tryParse(rawVal?.toString() ?? '');
          if (rawH != null) {
            final parsedCm = (rawH > 0 && rawH < 10) ? rawH * 100 : rawH;
            if (parsedCm >= 140 && parsedCm <= 210) {
              _partnerHeightRange = RangeValues(
                (parsedCm - 10).clamp(140.0, 210.0),
                parsedCm.clamp(140.0, 210.0),
              );
            }
          }
        }

        // Restore exact partner weight range when it is available.
        final rawMinWeight = double.tryParse(
            expectations['min_weight']?.toString() ?? '');
        final rawMaxWeight = double.tryParse(
            expectations['max_weight']?.toString() ?? '');
        if (rawMinWeight != null && rawMaxWeight != null &&
            rawMinWeight >= 40 && rawMaxWeight <= 140 &&
            rawMinWeight <= rawMaxWeight) {
          _partnerWeightRange = RangeValues(rawMinWeight, rawMaxWeight);
        } else {
          final rawVal = expectations['partner_weight'] ?? expectations['weight'];
          final rawW = double.tryParse(rawVal?.toString() ?? '');
          if (rawW != null && rawW >= 40 && rawW <= 140) {
            _partnerWeightRange = RangeValues(
              (rawW - 15).clamp(40.0, 140.0),
              rawW.clamp(40.0, 140.0),
            );
          }
        }

        if (expectations['children_acceptable'] != null) {
          _selectedChildrenAcceptable =
              _normalizeAcceptability(expectations['children_acceptable']);
        }
        if (expectations['smoking_acceptable'] != null) {
          _selectedSmokingAcceptable =
              _normalizeAcceptability(expectations['smoking_acceptable']);
        }
        if (expectations['drinking_acceptable'] != null) {
          _selectedDrinkingAcceptable =
              _normalizeAcceptability(expectations['drinking_acceptable']);
        }

        // مطابقة الحالة الاجتماعية
        final rawMarital = expectations['raw_marital_status_id'] ??
            expectations['marital_status_id'] ??
            expectations['marital_status'];
        if (rawMarital != null && _maritalStatuses.isNotEmpty) {
          final strVal = rawMarital.toString().trim();
          final cleanVal = _cleanText(strVal);
          final match = _maritalStatuses.firstWhere(
            (m) =>
                m['id'].toString() == strVal ||
                _cleanText((m['name'] ?? '').toString()) == cleanVal,
            orElse: () => null,
          );
          if (match != null) {
            _selectedMaritalStatusId = match['id'].toString();
          }
        }

        // مطابقة الديانة
        final rawReligion =
            expectations['religion_id'] ?? expectations['partner_religion_id'];
        if (rawReligion != null && _religions.isNotEmpty) {
          final strVal = rawReligion.toString().trim();
          final match = _religions.firstWhere(
            (r) =>
                r['id'].toString() == strVal ||
                _cleanText((r['name'] ?? '').toString()) == _cleanText(strVal),
            orElse: () => null,
          );
          if (match != null) {
            _selectedReligionId = match['id'].toString();
          }
        }

        // مطابقة اللغة
        final rawLang = expectations['language_id'];
        if (rawLang != null && _languages.isNotEmpty) {
          final strVal = rawLang.toString().trim();
          final match = _languages.firstWhere(
            (l) => l['id'].toString() == strVal,
            orElse: () => null,
          );
          if (match != null) {
            _selectedLanguageId = match['id'].toString();
          }
        }

        // مطابقة الدولة والمحافظة
        // This screen edits the preferred partner country, not the user's
        // or partner's separate residence-country field.
        final rawCountry =
            ApiService.preferredPartnerCountryId(expectations);
        if (rawCountry != null && _countries.isNotEmpty) {
          final strVal = rawCountry;
          final match = _countries.firstWhere(
            (c) =>
                c['id'].toString() == strVal ||
                _cleanText((c['name'] ?? '').toString()) == _cleanText(strVal),
            orElse: () => null,
          );
          if (match != null) {
            _selectedPartnerCountryId = match['id'].toString();
            final rawStateVal = expectations['preferred_state_id'] ??
                expectations['partner_state_id'];
            _loadStatesForCountry(_selectedPartnerCountryId!,
                rawStateTarget: rawStateVal);
          }
        }
      }
    } catch (e) {
      debugPrint('❌ خطأ في تحميل بيانات توقعات الشريك: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  /// 3️⃣ جلب المحافظات التابعة للدولة المختارة
  Future<void> _loadStatesForCountry(String countryId,
      {dynamic rawStateTarget}) async {
    final int requestGeneration = ++_statesRequestGeneration;
    final parsedCountryId = int.tryParse(countryId);
    if (countryId.isEmpty || parsedCountryId == null) {
      if (mounted) {
        setState(() {
          _states = [];
          _selectedStateId = null;
          _isLoadingStates = false;
        });
      }
      return;
    }

    setState(() {
      _isLoadingStates = true;
      _states = [];
      _selectedStateId = null;
    });

    try {
      final resStates = await ApiService.getStatesByCountry(
        parsedCountryId,
        lang: widget.currentLanguage,
      );
      if (!mounted || requestGeneration != _statesRequestGeneration) return;

      setState(() {
        _states = resStates;
        _isLoadingStates = false;

        if (rawStateTarget != null && _states.isNotEmpty) {
          final strVal = rawStateTarget.toString().trim();
          final match = _states.firstWhere(
            (s) =>
                s['id'].toString() == strVal ||
                _cleanText((s['name'] ?? '').toString()) == _cleanText(strVal),
            orElse: () => null,
          );
          if (match != null) _selectedStateId = match['id'].toString();
        }
      });
    } catch (e) {
      if (mounted && requestGeneration == _statesRequestGeneration) {
        setState(() => _isLoadingStates = false);
      }
    }
  }

  /// 4️⃣ إرسال وحفظ بيانات توقعات الشريك للسيرفر
  Future<void> _saveExpectations() async {
    FocusScope.of(context).unfocus();
    if (_isSaving) return;
    setState(() => _isSaving = true);

    try {
      final Map<String, dynamic> payload = <String, dynamic>{};
      final String formattedHeight =
          (_partnerHeightRange.start / 100).toStringAsFixed(2);
      final String formattedWeight = _partnerWeightRange.end.round().toString();

      if (_generalCtrl.text.trim().isNotEmpty || _generalWasLoaded) {
        payload['general'] = _generalCtrl.text.trim();
      }
      if (_professionCtrl.text.trim().isNotEmpty || _professionWasLoaded) {
        payload['partner_profession'] = _professionCtrl.text.trim();
      }

      // Persist the preferred partner location only. Never overwrite the
      // independent residence_country_id field from this screen.
      if (_locationPreferenceEdited) {
        payload['partner_country_id'] = _selectedPartnerCountryId;
        payload['partner_state_id'] = _selectedStateId;
      } else {
        payload.addAll(ApiService.buildPreferredPartnerLocationPayload(
          countryId: _selectedPartnerCountryId,
          stateId: _selectedStateId,
        ));
      }
      if (_ageRangeEdited) {
        payload['min_age'] =
            _ageRangeCleared ? null : _partnerAgeRange.start.round();
        payload['max_age'] =
            _ageRangeCleared ? null : _partnerAgeRange.end.round();
      }
      if (_heightRangeEdited) {
        payload['min_height'] =
            (_partnerHeightRange.start / 100).toStringAsFixed(2);
        payload['max_height'] =
            (_partnerHeightRange.end / 100).toStringAsFixed(2);
        payload['partner_height'] = formattedHeight;
      }
      if (_weightRangeEdited) {
        payload['min_weight'] = _partnerWeightRange.start.toStringAsFixed(2);
        payload['max_weight'] = _partnerWeightRange.end.toStringAsFixed(2);
        payload['partner_weight'] = formattedWeight;
      }
      if (_selectedMaritalStatusId != null &&
          _selectedMaritalStatusId!.isNotEmpty) {
        payload['partner_marital_status'] = _selectedMaritalStatusId;
      }
      if (_selectedReligionId != null && _selectedReligionId!.isNotEmpty) {
        payload['partner_religion_id'] = _selectedReligionId;
      }
      if (_selectedLanguageId != null && _selectedLanguageId!.isNotEmpty) {
        payload['language_id'] = _selectedLanguageId;
      }
      if (_selectedEducation != null && _selectedEducation!.isNotEmpty) {
        payload['partner_education'] = _selectedEducation;
        payload['pertner_education'] = _selectedEducation;
      }
      if (_selectedComplexion != null && _selectedComplexion!.isNotEmpty) {
        payload['partner_complexion'] = _selectedComplexion;
        payload['pertner_complexion'] = _selectedComplexion;
      }
      if (_selectedEyeColor != null && _selectedEyeColor!.isNotEmpty) {
        payload['partner_eye_color'] = _selectedEyeColor;
      }
      if (_selectedHairColor != null && _selectedHairColor!.isNotEmpty) {
        payload['partner_hair_color'] = _selectedHairColor;
      }

      payload['partner_children_acceptable'] = _selectedChildrenAcceptable;
      payload['smoking_acceptable'] = _selectedSmokingAcceptable;
      payload['drinking_acceptable'] = _selectedDrinkingAcceptable;

      final result = await ApiService.updatePartnerExpectations(
        payload,
        lang: widget.currentLanguage,
      );

      if (!mounted) return;
      final bool success = result['success'] == true;
      final bool isAr = widget.currentLanguage == 'ar';
      final String message = result['message'] != null
          ? result['message'].toString()
          : (success
              ? (isAr
                  ? 'تم حفظ توقعات الشريك بنجاح ✨'
                  : 'Partner expectations saved successfully ✨')
              : (isAr
                  ? 'حدث خطأ أثناء حفظ التوقعات'
                  : 'Failed to save expectations'));

      if (success) {
        _generalWasLoaded = true;
        _professionWasLoaded = true;
        _ageRangeEdited = false;
        _ageRangeCleared = false;
        _locationPreferenceEdited = false;
        _heightRangeEdited = false;
        _weightRangeEdited = false;
      }

      AppSnackBar.show(
        context: context,
        type: success ? NotificationType.success : NotificationType.error,
        isArabic: isAr,
        messageAr: message,
        messageEn: message,
      );
    } catch (e) {
      if (mounted) {
        final bool isAr = widget.currentLanguage == 'ar';
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: isAr,
          messageAr: e.toString().contains('لا توجد') 
              ? e.toString()
              : 'حدث خطأ في الاتصال بالسيرفر، يرجى المحاولة لاحقاً',
          messageEn: 'Network connection error, please try again later',
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  /// 5️⃣ القاع المنبثق المخصص لاختيار العناصر مع محرك البحث
  void _showCustomBottomSheet({
    required String title,
    required List<Map<String, dynamic>> items,
    required String? currentValue,
    required ValueChanged<String> onSelected,
    bool isAr = true,
    bool enableSearch = true,
  }) {
    FocusScope.of(context).unfocus();
    final debouncer = Debouncer(milliseconds: 200);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        String searchQuery = '';
        return Directionality(
          textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
          child: StatefulBuilder(
            builder: (BuildContext context, StateSetter setModalState) {
            final filteredItems = items.where((item) {
              final name = (item['name'] ?? '').toString().toLowerCase();
              return name.contains(searchQuery.toLowerCase());
            }).toList();

            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              child: ClipRRect(
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(32)),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
                  child: Container(
                    height: MediaQuery.of(context).size.height * 0.65,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 20, vertical: 16),
                    decoration: BoxDecoration(
                      color: const Color(0xFF01579B).withValues(alpha: 0.96),
                      borderRadius:
                          const BorderRadius.vertical(top: Radius.circular(32)),
                      border: Border.all(
                          color: Colors.white.withValues(alpha: 0.2),
                          width: 1.5),
                    ),
                    child: Column(
                      children: [
                        Container(
                          width: 48,
                          height: 5,
                          decoration: BoxDecoration(
                            color: Colors.white38,
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        const SizedBox(height: 14),
                        Text(
                          title,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        const SizedBox(height: 14),
                        if (enableSearch && items.length > 5) ...[
                          TextFormField(
                            textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
                            textAlign: isAr ? TextAlign.right : TextAlign.left,
                            onChanged: (val) {
                              debouncer.run(() {
                                setModalState(() {
                                  searchQuery = val;
                                });
                              });
                            },
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 14,
                                fontFamily: 'Cairo'),
                            decoration: InputDecoration(
                              hintText: isAr ? 'ابحث هنا...' : 'Search...',
                              hintStyle: const TextStyle(
                                  color: Colors.white60,
                                  fontSize: 14,
                                  fontFamily: 'Cairo'),
                              prefixIcon: const Icon(Icons.search_rounded,
                                  color: Colors.white70, size: 20),
                              filled: true,
                              fillColor: Colors.white.withValues(alpha: 0.12),
                              contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 14, vertical: 12),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(16),
                                borderSide: BorderSide.none,
                              ),
                            ),
                          ),
                          const SizedBox(height: 14),
                        ],
                        Expanded(
                          child: filteredItems.isEmpty
                              ? Center(
                                  child: Text(
                                    isAr ? 'لا توجد نتائج' : 'No results found',
                                    style: const TextStyle(
                                      color: Colors.white70,
                                      fontFamily: 'Cairo',
                                      fontSize: 14,
                                    ),
                                  ),
                                )
                              : ListView.builder(
                                  physics: const BouncingScrollPhysics(),
                                  itemCount: filteredItems.length,
                                  itemBuilder: (context, index) {
                                    final item = filteredItems[index];
                                    final String itemId =
                                        (item['id'] ?? '').toString();
                                    final String itemName =
                                        (item['name'] ?? '').toString();
                                    final bool isSelected =
                                        currentValue == itemId;

                                    return Column(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Material(
                                          color: Colors.transparent,
                                          child: InkWell(
                                            borderRadius:
                                                BorderRadius.circular(14),
                                            onTap: () {
                                              debouncer.dispose();
                                              FocusScope.of(context).unfocus();
                                              onSelected(itemId);
                                              Navigator.pop(context);
                                            },
                                            child: Container(
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                      horizontal: 14,
                                                      vertical: 14),
                                              decoration: BoxDecoration(
                                                color: isSelected
                                                    ? Colors.white
                                                        .withValues(alpha: 0.2)
                                                    : Colors.transparent,
                                                borderRadius:
                                                    BorderRadius.circular(14),
                                              ),
                                              child: Row(
                                                children: [
                                                  Expanded(
                                                    child: Text(
                                                      itemName,
                                                      textAlign: isAr
                                                          ? TextAlign.right
                                                          : TextAlign.left,
                                                      textDirection: isAr
                                                          ? TextDirection.rtl
                                                          : TextDirection.ltr,
                                                      style: TextStyle(
                                                        color: Colors.white,
                                                        fontSize: 15,
                                                        fontFamily: 'Cairo',
                                                        fontWeight: isSelected
                                                            ? FontWeight.bold
                                                            : FontWeight.normal,
                                                      ),
                                                    ),
                                                  ),
                                                  if (isSelected)
                                                    const Icon(
                                                        Icons
                                                            .check_circle_rounded,
                                                        color:
                                                            Colors.amberAccent,
                                                        size: 22),
                                                ],
                                              ),
                                            ),
                                          ),
                                        ),
                                        Divider(
                                            color: Colors.white
                                                .withValues(alpha: 0.12),
                                            height: 1),
                                      ],
                                    );
                                  },
                                ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
            },
          ),
        );
      },
    ).whenComplete(() => debouncer.dispose());
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(top: 22, bottom: 12),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: Colors.white, size: 20),
          ),
          const SizedBox(width: 10),
          Text(
            title,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 17,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChoiceChips({
    required String title,
    required IconData icon,
    required List<Map<String, String>> options,
    required String? selectedValue,
    required ValueChanged<String> onSelected,
    required bool isAr,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader(title, icon),
        Wrap(
          spacing: 8.0,
          runSpacing: 10.0,
          children: options.map((opt) {
            final bool isSelected = selectedValue == opt['id'];
            return ChoiceChip(
              label: Text(
                opt['name']!,
                style: TextStyle(
                  color: isSelected ? const Color(0xFF01579B) : Colors.white,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                  fontFamily: 'Cairo',
                  fontSize: 14,
                ),
              ),
              selected: isSelected,
              selectedColor: Colors.white,
              backgroundColor: const Color(0xFF013966).withValues(alpha: 0.7),
              showCheckmark: isSelected,
              checkmarkColor: const Color(0xFF01579B),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
                side: BorderSide.none,
              ),
              onSelected: (bool sel) {
                FocusScope.of(context).unfocus();
                if (sel) onSelected(opt['id']!);
              },
            );
          }).toList(),
        ),
      ],
    );
  }

  Widget _buildSheetPickerTile({
    required String label,
    required String? displayValue,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: () {
        FocusScope.of(context).unfocus();
        onTap();
      },
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(16),
          border:
              Border.all(color: Colors.white.withValues(alpha: 0.15), width: 1),
        ),
        child: Row(
          children: [
            Icon(icon, color: Colors.white70, size: 22),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                displayValue != null && displayValue.isNotEmpty
                    ? displayValue
                    : label,
                textAlign: TextAlign.start,
                textDirection: widget.currentLanguage == 'ar'
                    ? TextDirection.rtl
                    : TextDirection.ltr,
                style: TextStyle(
                  color: displayValue != null && displayValue.isNotEmpty
                      ? Colors.white
                      : Colors.white70,
                  fontSize: 15,
                  fontFamily: 'Cairo',
                  fontWeight: displayValue != null && displayValue.isNotEmpty
                      ? FontWeight.bold
                      : FontWeight.normal,
                ),
              ),
            ),
            const Icon(Icons.keyboard_arrow_down_rounded,
                color: Colors.white70, size: 24),
          ],
        ),
      ),
    );
  }

  InputDecoration _buildInputDecoration(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      labelStyle: const TextStyle(
        color: Colors.white70,
        fontSize: 14,
        fontFamily: 'Cairo',
      ),
      prefixIcon: Icon(icon, color: Colors.white70, size: 22),
      filled: true,
      fillColor: Colors.white.withValues(alpha: 0.12),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide:
            BorderSide(color: Colors.white.withValues(alpha: 0.15), width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.white, width: 1.5),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isAr = widget.currentLanguage == 'ar';
    const primaryColor = Color(0xFF0288D1);
    const darkBgColor = Color(0xFF01579B);

    final double bottomInset = MediaQuery.of(context).padding.bottom;
    final double saveButtonBottom = bottomInset > 0 ? bottomInset + 12 : 16;
    final double scrollBottomPadding = 90 + bottomInset;

    String? maritalName;
    if (_selectedMaritalStatusId != null && _maritalStatuses.isNotEmpty) {
      final match = _maritalStatuses.firstWhere(
        (m) => m['id'].toString() == _selectedMaritalStatusId,
        orElse: () => null,
      );
      if (match != null) maritalName = match['name']?.toString();
    }

    String? religionName;
    if (_selectedReligionId != null && _religions.isNotEmpty) {
      final match = _religions.firstWhere(
        (r) => r['id'].toString() == _selectedReligionId,
        orElse: () => null,
      );
      if (match != null) religionName = match['name']?.toString();
    }

    String? languageName;
    if (_selectedLanguageId != null && _languages.isNotEmpty) {
      final match = _languages.firstWhere(
        (l) => l['id'].toString() == _selectedLanguageId,
        orElse: () => null,
      );
      if (match != null) languageName = match['name']?.toString();
    }

    String? countryName;
    if (_selectedPartnerCountryId != null && _countries.isNotEmpty) {
      final match = _countries.firstWhere(
        (c) => c['id'].toString() == _selectedPartnerCountryId,
        orElse: () => null,
      );
      if (match != null) countryName = match['name']?.toString();
    }

    String? stateName;
    if (_selectedStateId != null && _states.isNotEmpty) {
      final match = _states.firstWhere(
        (s) => s['id'].toString() == _selectedStateId,
        orElse: () => null,
      );
      if (match != null) stateName = match['name']?.toString();
    }

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
        child: GestureDetector(
          onTap: () => FocusScope.of(context).unfocus(),
          child: Scaffold(
            body: Container(
              width: double.infinity,
              height: double.infinity,
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Color(0xFF01579B),
                    Color(0xFF0288D1),
                    Color(0xFF4FC3F7)
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: SafeArea(
                child: Stack(
                  children: [
                    Column(
                      children: [
                        Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 10),
                          child: Row(
                            children: [
                              IconButton(
                                icon: Icon(
                                  isAr
                                      ? Icons.arrow_back_ios_rounded
                                      : Icons.arrow_back_ios_new_rounded,
                                  color: Colors.white,
                                  size: 20,
                                ),
                                onPressed: () => Navigator.pop(context),
                              ),
                              Expanded(
                                child: Text(
                                  isAr
                                      ? 'ما الذي تبحث عنه في شريك حياتك؟'
                                      : 'Partner Expectations',
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                              ),
                              const SizedBox(width: 48),
                            ],
                          ),
                        ),
                        Expanded(
                          child: _isLoading
                              ? const Center(
                                  child: CircularProgressIndicator(
                                      color: Colors.white))
                              : SingleChildScrollView(
                                  physics: const BouncingScrollPhysics(),
                                  padding: const EdgeInsets.all(18),
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(28),
                                    child: BackdropFilter(
                                      filter: ImageFilter.blur(
                                          sigmaX: 16, sigmaY: 16),
                                      child: Container(
                                        padding: EdgeInsets.only(
                                            left: 18,
                                            right: 18,
                                            top: 18,
                                            bottom: scrollBottomPadding),
                                        decoration: BoxDecoration(
                                          color: Colors.white
                                              .withValues(alpha: 0.18),
                                          borderRadius:
                                              BorderRadius.circular(28),
                                          border: Border.all(
                                              color: Colors.white
                                                  .withValues(alpha: 0.2),
                                              width: 1.5),
                                        ),
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            _buildSectionHeader(
                                                isAr
                                                    ? 'الصفات التي تتمنى وجودها ✍️'
                                                    : 'General Expectations ✍',
                                                Icons.description_rounded),
                                            TextFormField(
                                              controller: _generalCtrl,
                                              maxLines: 3,
                                              style: const TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 15,
                                                  fontFamily: 'Cairo'),
                                              decoration: _buildInputDecoration(
                                                  isAr
                                                      ? 'مثال: حنون، مثقف، طموح، يقدر الحياة الزوجية...'
                                                      : 'Describe your ideal partner...',
                                                  Icons.edit_note_rounded),
                                            ),
                                            _buildSectionHeader(
                                              isAr ? 'النطاق العمري المفضل 🎂' : 'Preferred Age Range 🎂',
                                              Icons.cake_rounded,
                                            ),
                                            Container(
                                              padding: const EdgeInsets.all(12),
                                              decoration: BoxDecoration(
                                                color: Colors.black.withValues(alpha: 0.2),
                                                borderRadius: BorderRadius.circular(16),
                                                border: Border.all(color: Colors.white24),
                                              ),
                                              child: Column(
                                                children: [
                                                  Row(
                                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                    children: [
                                                      Text(
                                                        isAr
                                                            ? 'من: ${_partnerAgeRange.start.round()} سنة'
                                                            : 'From: ${_partnerAgeRange.start.round()} yrs',
                                                        style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontFamily: 'Cairo'),
                                                      ),
                                                      Text(
                                                        isAr
                                                            ? 'إلى: ${_partnerAgeRange.end.round()} سنة'
                                                            : 'To: ${_partnerAgeRange.end.round()} yrs',
                                                        style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontFamily: 'Cairo'),
                                                      ),
                                                    ],
                                                  ),
                                                  RangeSlider(
                                                    values: _partnerAgeRange,
                                                    min: 18,
                                                    max: 80,
                                                    divisions: 62,
                                                    activeColor: Colors.amberAccent,
                                                    inactiveColor: Colors.white24,
                                                    onChanged: (newRange) {
                                                      setState(() {
                                                        _partnerAgeRange = newRange;
                                                        _ageRangeEdited = true;
                                                        _ageRangeCleared = false;
                                                      });
                                                    },
                                                  ),
                                                ],
                                              ),
                                            ),
                                            const SizedBox(height: 12),
                                            Align(
                                              alignment: AlignmentDirectional.centerStart,
                                              child: TextButton.icon(
                                                onPressed: () {
                                                  setState(() {
                                                    _ageRangeEdited = true;
                                                    _ageRangeCleared = true;
                                                  });
                                                },
                                                icon: const Icon(Icons.clear_rounded, color: Colors.white70),
                                                label: Text(
                                                  isAr ? 'لا يهم العمر / إلغاء التفضيل' : 'No age preference',
                                                  style: const TextStyle(color: Colors.white70, fontFamily: 'Cairo'),
                                                ),
                                              ),
                                            ),
                                            _buildSectionHeader(
                                                isAr
                                                    ? 'مكان الإقامة واللغة 🌍'
                                                    : 'Location & Language 🌍',
                                                Icons.map_rounded),
                                            _buildSheetPickerTile(
                                              label: isAr
                                                  ? 'بلد الشريك المفضل'
                                                  : 'Preferred Partner Country',
                                              displayValue: countryName,
                                              icon: Icons.public_rounded,
                                              onTap: () {
                                                final items = _countries
                                                    .map((c) => {
                                                          'id': c['id']
                                                              .toString(),
                                                          'name':
                                                              (c['name'] ?? '')
                                                                  .toString()
                                                        })
                                                    .toList();
                                                _showCustomBottomSheet(
                                                  title: isAr
                                                      ? 'اختر بلد الشريك المفضل'
                                                      : 'Preferred Partner Country',
                                                  items: items,
                                                  currentValue:
                                                      _selectedPartnerCountryId,
                                                  isAr: isAr,
                                                  enableSearch: true,
                                                  onSelected: (v) {
                                                    setState(() {
                                                      _selectedPartnerCountryId =
                                                          v.isEmpty ? null : v;
                                                      _selectedStateId = null;
                                                      _locationPreferenceEdited = true;
                                                    });
                                                    _loadStatesForCountry(v);
                                                  },
                                                );
                                              },
                                            ),
                                            const SizedBox(height: 12),
                                            Align(
                                              alignment: AlignmentDirectional.centerStart,
                                              child: TextButton.icon(
                                                onPressed: _selectedPartnerCountryId == null
                                                    ? null
                                                    : () {
                                                        setState(() {
                                                          _selectedPartnerCountryId = null;
                                                          _selectedStateId = null;
                                                          _states = [];
                                                          _locationPreferenceEdited = true;
                                                        });
                                                      },
                                                icon: const Icon(Icons.clear_rounded, color: Colors.white70),
                                                label: Text(
                                                  isAr ? 'إلغاء تفضيل البلد والمحافظة' : 'Clear country and governorate preference',
                                                  style: const TextStyle(color: Colors.white70, fontFamily: 'Cairo'),
                                                ),
                                              ),
                                            ),
                                            if (_isLoadingStates) ...[
                                              Container(
                                                padding:
                                                    const EdgeInsets.symmetric(
                                                        horizontal: 16,
                                                        vertical: 14),
                                                decoration: BoxDecoration(
                                                  color: Colors.white
                                                      .withValues(alpha: 0.12),
                                                  borderRadius:
                                                      BorderRadius.circular(16),
                                                  border: Border.all(
                                                      color: Colors.white
                                                          .withValues(
                                                              alpha: 0.15),
                                                      width: 1),
                                                ),
                                                child: Row(
                                                  children: [
                                                    const SizedBox(
                                                      width: 18,
                                                      height: 18,
                                                      child:
                                                          CircularProgressIndicator(
                                                              strokeWidth: 2,
                                                              color:
                                                                  Colors.white),
                                                    ),
                                                    const SizedBox(width: 12),
                                                    Text(
                                                      isAr
                                                          ? 'جاري تحميل المحافظات...'
                                                          : 'Loading governorates...',
                                                      style: const TextStyle(
                                                        color: Colors.white70,
                                                        fontSize: 14,
                                                        fontFamily: 'Cairo',
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                              const SizedBox(height: 12),
                                            ] else if (_selectedPartnerCountryId !=
                                                null) ...[
                                              _buildSheetPickerTile(
                                                label: isAr
                                                    ? 'المحافظة / المقاطعة المفضلة'
                                                    : 'Preferred Governorate / State',
                                                displayValue: stateName,
                                                icon:
                                                    Icons.location_city_rounded,
                                                onTap: () {
                                                  if (_states.isEmpty) return;
                                                  final items = _states
                                                      .map((s) => {
                                                            'id': s['id']
                                                                .toString(),
                                                            'name':
                                                                (s['name'] ??
                                                                        '')
                                                                    .toString()
                                                          })
                                                      .toList();
                                                  _showCustomBottomSheet(
                                                    title: isAr
                                                        ? 'اختر المحافظة / المقاطعة'
                                                        : 'Select Governorate',
                                                    items: items,
                                                    currentValue:
                                                        _selectedStateId,
                                                    isAr: isAr,
                                                    enableSearch: true,
                                                    onSelected: (v) => setState(() {
                                                      _selectedStateId = v.isEmpty ? null : v;
                                                      _locationPreferenceEdited = true;
                                                    }),
                                                  );
                                                },
                                              ),
                                              const SizedBox(height: 12),
                                            ],
                                            if (_languages.isNotEmpty) ...[
                                              _buildSheetPickerTile(
                                                label: isAr
                                                    ? 'اللغة الأساسية'
                                                    : 'Language',
                                                displayValue: languageName,
                                                icon: Icons.translate_rounded,
                                                onTap: () {
                                                  final items = _languages
                                                      .map((l) => {
                                                            'id': l['id']
                                                                .toString(),
                                                            'name':
                                                                (l['name'] ??
                                                                        '')
                                                                    .toString()
                                                          })
                                                      .toList();
                                                  _showCustomBottomSheet(
                                                    title: isAr
                                                        ? 'اختر اللغة'
                                                        : 'Select Language',
                                                    items: items,
                                                    currentValue:
                                                        _selectedLanguageId,
                                                    isAr: isAr,
                                                    onSelected: (v) => setState(
                                                        () =>
                                                            _selectedLanguageId =
                                                                v),
                                                  );
                                                },
                                              ),
                                              const SizedBox(height: 12),
                                            ],
                                            _buildSectionHeader(
                                                isAr
                                                    ? 'المظهر والسمات البدنية 📏'
                                                    : 'Physical Appearance 📏',
                                                Icons.straighten_rounded),
                                            const Text(
                                              'نطاق الطول المطلوب للشريك (سم):',
                                              style: TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 14,
                                                  fontWeight: FontWeight.bold,
                                                  fontFamily: 'Cairo'),
                                            ),
                                            const SizedBox(height: 6),
                                            Container(
                                              padding: const EdgeInsets.all(12),
                                              decoration: BoxDecoration(
                                                  color: Colors.black
                                                      .withValues(alpha: 0.2),
                                                  borderRadius:
                                                      BorderRadius.circular(16),
                                                  border: Border.all(
                                                      color: Colors.white24)),
                                              child: Column(
                                                children: [
                                                  Row(
                                                    mainAxisAlignment:
                                                        MainAxisAlignment
                                                            .spaceBetween,
                                                    children: [
                                                      Text(
                                                          'من: ${_partnerHeightRange.start.round()} سم',
                                                          style: const TextStyle(
                                                              color: Colors
                                                                  .amberAccent,
                                                              fontSize: 14,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .bold,
                                                              fontFamily:
                                                                  'Cairo')),
                                                      Text(
                                                          'إلى: ${_partnerHeightRange.end.round()} سم',
                                                          style: const TextStyle(
                                                              color: Colors
                                                                  .amberAccent,
                                                              fontSize: 14,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .bold,
                                                              fontFamily:
                                                                  'Cairo')),
                                                    ],
                                                  ),
                                                  RangeSlider(
                                                      values:
                                                          _partnerHeightRange,
                                                      min: 140,
                                                      max: 210,
                                                      divisions: 70,
                                                      activeColor:
                                                          Colors.amberAccent,
                                                      inactiveColor:
                                                          Colors.white24,
                                                      onChanged: (newRange) {
                                                        setState(() {
                                                          _partnerHeightRange = newRange;
                                                          _heightRangeEdited = true;
                                                        });
                                                      }),
                                                ],
                                              ),
                                            ),
                                            const SizedBox(height: 14),
                                            const Text(
                                              'نطاق الوزن المطلوب للشريك (كغ):',
                                              style: TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 14,
                                                  fontWeight: FontWeight.bold,
                                                  fontFamily: 'Cairo'),
                                            ),
                                            const SizedBox(height: 6),
                                            Container(
                                              padding: const EdgeInsets.all(12),
                                              decoration: BoxDecoration(
                                                  color: Colors.black
                                                      .withValues(alpha: 0.2),
                                                  borderRadius:
                                                      BorderRadius.circular(16),
                                                  border: Border.all(
                                                      color: Colors.white24)),
                                              child: Column(
                                                children: [
                                                  Row(
                                                    mainAxisAlignment:
                                                        MainAxisAlignment
                                                            .spaceBetween,
                                                    children: [
                                                      Text(
                                                          'من: ${_partnerWeightRange.start.round()} كغ',
                                                          style: const TextStyle(
                                                              color: Colors
                                                                  .amberAccent,
                                                              fontSize: 14,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .bold,
                                                              fontFamily:
                                                                  'Cairo')),
                                                      Text(
                                                          'إلى: ${_partnerWeightRange.end.round()} كغ',
                                                          style: const TextStyle(
                                                              color: Colors
                                                                  .amberAccent,
                                                              fontSize: 14,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .bold,
                                                              fontFamily:
                                                                  'Cairo')),
                                                    ],
                                                  ),
                                                  RangeSlider(
                                                      values:
                                                          _partnerWeightRange,
                                                      min: 40,
                                                      max: 140,
                                                      divisions: 100,
                                                      activeColor:
                                                          Colors.amberAccent,
                                                      inactiveColor:
                                                          Colors.white24,
                                                      onChanged: (newRange) {
                                                        setState(() {
                                                          _partnerWeightRange = newRange;
                                                          _weightRangeEdited = true;
                                                        });
                                                      }),
                                                ],
                                              ),
                                            ),
                                            const SizedBox(height: 14),
                                            _buildSheetPickerTile(
                                              label: isAr
                                                  ? 'لون البشرة المفضل'
                                                  : 'Complexion',
                                              displayValue: _selectedComplexion,
                                              icon: Icons.color_lens_rounded,
                                              onTap: () {
                                                final items = _complexionOptions
                                                    .map((c) =>
                                                        {'id': c, 'name': c})
                                                    .toList();
                                                _showCustomBottomSheet(
                                                  title: isAr
                                                      ? 'اختر لون البشرة'
                                                      : 'Select Complexion',
                                                  items: items,
                                                  currentValue:
                                                      _selectedComplexion,
                                                  isAr: isAr,
                                                  onSelected: (v) => setState(
                                                      () =>
                                                          _selectedComplexion =
                                                              v),
                                                );
                                              },
                                            ),
                                                                                        _buildSheetPickerTile(
                                              label: isAr ? 'لون العين المفضل' : 'Preferred Eye Color',
                                              displayValue: _selectedEyeColor,
                                              icon: Icons.remove_red_eye_rounded,
                                              onTap: () {
                                                _showCustomBottomSheet(
                                                  title: isAr ? 'اختر لون العين' : 'Select Eye Color',
                                                  items: _eyeColorOptions.map((v) => {'id': v, 'name': v}).toList(),
                                                  currentValue: _selectedEyeColor,
                                                  isAr: isAr,
                                                  onSelected: (v) => setState(() => _selectedEyeColor = v),
                                                );
                                              },
                                            ),
                                            const SizedBox(height: 10),
                                            _buildSheetPickerTile(
                                              label: isAr ? 'لون الشعر المفضل' : 'Preferred Hair Color',
                                              displayValue: _selectedHairColor,
                                              icon: Icons.face_rounded,
                                              onTap: () {
                                                _showCustomBottomSheet(
                                                  title: isAr ? 'اختر لون الشعر' : 'Select Hair Color',
                                                  items: _hairColorOptions.map((v) => {'id': v, 'name': v}).toList(),
                                                  currentValue: _selectedHairColor,
                                                  isAr: isAr,
                                                  onSelected: (v) => setState(() => _selectedHairColor = v),
                                                );
                                              },
                                            ),
                                            const SizedBox(height: 12),
_buildSectionHeader(
                                                isAr
                                                    ? 'الحالة الاجتماعية والدين 💍'
                                                    : 'Marital & Religion 💍',
                                                Icons.favorite_border_rounded),
                                            _buildSheetPickerTile(
                                              label: isAr
                                                  ? 'الحالة الاجتماعية المفضلة'
                                                  : 'Marital Status',
                                              displayValue: maritalName,
                                              icon: Icons.favorite_rounded,
                                              onTap: () {
                                                final items = _maritalStatuses
                                                    .map((m) => {
                                                          'id': m['id']
                                                              .toString(),
                                                          'name':
                                                              (m['name'] ?? '')
                                                                  .toString()
                                                        })
                                                    .toList();
                                                _showCustomBottomSheet(
                                                  title: isAr
                                                      ? 'الحالة الاجتماعية'
                                                      : 'Marital Status',
                                                  items: items,
                                                  currentValue:
                                                      _selectedMaritalStatusId,
                                                  isAr: isAr,
                                                  onSelected: (v) => setState(() =>
                                                      _selectedMaritalStatusId =
                                                          v),
                                                );
                                              },
                                            ),
                                            const SizedBox(height: 12),
                                            if (_religions.isNotEmpty) ...[
                                              _buildSheetPickerTile(
                                                label: isAr
                                                    ? 'الديانة'
                                                    : 'Religion',
                                                displayValue: religionName,
                                                icon:
                                                    Icons.auto_awesome_rounded,
                                                onTap: () {
                                                  final items = _religions
                                                      .map((r) => {
                                                            'id': r['id']
                                                                .toString(),
                                                            'name':
                                                                (r['name'] ??
                                                                        '')
                                                                    .toString()
                                                          })
                                                      .toList();
                                                  _showCustomBottomSheet(
                                                    title: isAr
                                                        ? 'اختر الديانة'
                                                        : 'Select Religion',
                                                    items: items,
                                                    currentValue:
                                                        _selectedReligionId,
                                                    isAr: isAr,
                                                    onSelected: (v) => setState(
                                                        () =>
                                                            _selectedReligionId =
                                                                v),
                                                  );
                                                },
                                              ),
                                              const SizedBox(height: 12),
                                            ],
                                            _buildChoiceChips(
                                              title: isAr
                                                  ? 'هل يناسبك شريك لديه أطفال؟ 👶'
                                                  : 'Accept Children? 👶',
                                              icon: Icons.child_care_rounded,
                                              options: [
                                                {
                                                  'id': 'does_not_matter',
                                                  'name': isAr
                                                      ? 'لا يهم'
                                                      : 'Does not matter'
                                                },
                                                {
                                                  'id': 'yes',
                                                  'name': isAr
                                                      ? 'نعم، أتقبل ذلك 👍'
                                                      : 'Yes'
                                                },
                                                {
                                                  'id': 'no',
                                                  'name': isAr
                                                      ? 'لا، أُفضل بدون أطفال ✋'
                                                      : 'No'
                                                },
                                              ],
                                              selectedValue:
                                                  _selectedChildrenAcceptable,
                                              isAr: isAr,
                                              onSelected: (v) => setState(() =>
                                                  _selectedChildrenAcceptable =
                                                      v),
                                            ),
                                            _buildSectionHeader(
                                                isAr
                                                    ? 'التعليم والعمل 🎓💼'
                                                    : 'Education & Career 🎓💼',
                                                Icons.school_rounded),
                                            _buildSheetPickerTile(
                                              label: isAr
                                                  ? 'الحد الأدنى للمستوى التعليمي'
                                                  : 'Education Level',
                                              displayValue: _selectedEducation,
                                              icon: Icons
                                                  .workspace_premium_rounded,
                                              onTap: () {
                                                final items = _educationDegrees
                                                    .map((e) =>
                                                        {'id': e, 'name': e})
                                                    .toList();
                                                _showCustomBottomSheet(
                                                  title: isAr
                                                      ? 'اختر المستوى التعليمي'
                                                      : 'Select Education Level',
                                                  items: items,
                                                  currentValue:
                                                      _selectedEducation,
                                                  isAr: isAr,
                                                  onSelected: (v) => setState(
                                                      () => _selectedEducation =
                                                          v),
                                                );
                                              },
                                            ),
                                            const SizedBox(height: 12),
                                            TextFormField(
                                              controller: _professionCtrl,
                                              style: const TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 15,
                                                  fontFamily: 'Cairo'),
                                              decoration: _buildInputDecoration(
                                                  isAr
                                                      ? 'ما المجال المهني الذي تفضّله؟ مثال: طبيب أو معلّم...'
                                                      : 'Preferred Profession',
                                                  Icons.work_outline_rounded),
                                            ),
                                            _buildChoiceChips(
                                              title: isAr
                                                  ? 'ما موقفك من التدخين لدى الشريك؟ 🚬'
                                                  : 'Accept Smoking? 🚬',
                                              icon: Icons.smoking_rooms_rounded,
                                              options: [
                                                {
                                                  'id': 'does_not_matter',
                                                  'name': isAr
                                                      ? 'لا يهم'
                                                      : 'Does not matter'
                                                },
                                                {
                                                  'id': 'no',
                                                  'name': isAr
                                                      ? 'لا (أشترط عدم التدخين) ✋'
                                                      : 'No'
                                                },
                                                {
                                                  'id': 'yes',
                                                  'name': isAr
                                                      ? 'نعم (لا مانع من التدخين) 👍'
                                                      : 'Yes'
                                                },
                                              ],
                                              selectedValue:
                                                  _selectedSmokingAcceptable,
                                              isAr: isAr,
                                              onSelected: (v) => setState(() =>
                                                  _selectedSmokingAcceptable =
                                                      v),
                                            ),
                                            _buildChoiceChips(
                                              title: isAr
                                                  ? 'ما موقفك من تناول الكحول لدى الشريك؟ 🍷'
                                                  : 'Accept Alcohol? 🍷',
                                              icon: Icons.local_bar_rounded,
                                              options: [
                                                {
                                                  'id': 'no',
                                                  'name': isAr
                                                      ? 'مرفوض تماماً ✋'
                                                      : 'No'
                                                },
                                                {
                                                  'id': 'does_not_matter',
                                                  'name': isAr
                                                      ? 'لا يهم'
                                                      : 'Does not matter'
                                                },
                                                {
                                                  'id': 'yes',
                                                  'name':
                                                      isAr ? 'مقبول 👍' : 'Yes'
                                                },
                                              ],
                                              selectedValue:
                                                  _selectedDrinkingAcceptable,
                                              isAr: isAr,
                                              onSelected: (v) => setState(() =>
                                                  _selectedDrinkingAcceptable =
                                                      v),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                        ),
                      ],
                    ),
                    Positioned(
                      bottom: saveButtonBottom,
                      left: 20,
                      right: 20,
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(30),
                        child: BackdropFilter(
                          filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                          child: Container(
                            height: 54,
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.25),
                              borderRadius: BorderRadius.circular(30),
                              border: Border.all(
                                  color: Colors.white.withValues(alpha: 0.4),
                                  width: 1.5),
                            ),
                            child: ElevatedButton(
                              onPressed: _isSaving ? null : _saveExpectations,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.white,
                                foregroundColor: primaryColor,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(30),
                                ),
                                elevation: 0,
                              ),
                              child: _isSaving
                                  ? const SizedBox(
                                      width: 22,
                                      height: 22,
                                      child: CircularProgressIndicator(
                                        color: primaryColor,
                                        strokeWidth: 2.5,
                                      ),
                                    )
                                  : Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      children: [
                                        const Icon(Icons.check_circle_rounded,
                                            color: primaryColor, size: 22),
                                        const SizedBox(width: 8),
                                        Text(
                                          isAr ? 'حفظ وإنهاء' : 'Save & Finish',
                                          style: const TextStyle(
                                            color: primaryColor,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 16,
                                            fontFamily: 'Cairo',
                                          ),
                                        ),
                                      ],
                                    ),
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
        ),
      ),
    );
  }
}

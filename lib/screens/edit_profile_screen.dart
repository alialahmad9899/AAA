// ============================================================================
// اسم الملف: lib/screens/edit_profile_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: الشاشة الرئيسية لتعديل الملف الشخصي مع الدعم الكامل للترجمة
//        الديناميكية المزدوجة (العربية والإنجليزية) والتحقق القبلي من المدخلات.
// ============================================================================

import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:image_cropper/image_cropper.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../services/api_service.dart';
import '../models/member_profile_model.dart';
import '../services/profile_cache_service.dart';

import 'edit_profile/widgets/profile_pickers.dart';
import 'edit_profile/widgets/step1_basic_physical_tab.dart';
import 'edit_profile/widgets/step2_residency_spiritual_tab.dart';
import 'edit_profile/widgets/step3_lifestyle_hobbies_tab.dart';

class EditProfileScreen extends StatefulWidget {
  final String currentLanguage;

  const EditProfileScreen({super.key, this.currentLanguage = 'ar'});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  bool _isLoading = true;
  bool _isSaving = false;

  bool get _isArabic => widget.currentLanguage == 'ar';

  final _firstNameCtrl = TextEditingController();
  final _lastNameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _dobCtrl = TextEditingController();
  final _childrenCtrl = TextEditingController();
  final _introCtrl = TextEditingController();

  String? _educationId;
  String? _careerId;

  final _institutionCtrl = TextEditingController();
  final _designationCtrl = TextEditingController();
  final _companyCtrl = TextEditingController();
  String? _selectedEducationDegree;

  // الحقول التي استلمناها فعلياً. نستخدمها للسماح بمسح قيمة موجودة
  // مع منع إرسال فراغات/قيم افتراضية لحقول لم تُحمّل من الخادم.
  final Map<String, Set<String>> _loadedProfileFields =
      <String, Set<String>>{};

  List<String> get _educationDegreeOptions => _isArabic
      ? [
          'إعدادية',
          'ثانوية',
          'دبلوم',
          'بكالوريوس',
          'ماجستير',
          'دكتوراه',
          'أخرى'
        ]
      : [
          'Middle School',
          'High School',
          'Diploma',
          'Bachelor',
          'Master',
          'PhD',
          'Other'
        ];

  String _selectedGender = '1';
  bool _genderWasLoaded = false;
  String? _onBehalfValue;
  String? _selectedMaritalStatusId;
  String _currentPhotoUrl = '';
  File? _pickedImageFile;

  String? _selectedHeight;
  String? _selectedWeight;
  String? _selectedEyeColor;
  String? _selectedHairColor;
  String? _selectedComplexion;
  String? _selectedBloodGroup;
  String? _selectedBodyType;
  String? _selectedBodyArt;

  String? _selectedDiet;
  String? _selectedLivingWith;
  String? _selectedSmoke;
  String? _selectedDrink;

  String? _selectedHobby;
  String? _selectedInterest;
  String? _selectedMusic;
  String? _selectedBook;
  String? _selectedMovie;
  String? _selectedTvShow;
  String? _selectedSport;
  String? _selectedFitness;
  String? _selectedCuisine;
  String? _selectedDressStyle;

  String? _selectedAffection;
  String? _selectedHumor;
  String? _selectedReligiousService;
  String? _selectedPoliticalView;

  String? _selectedBirthCountryId;
  String? _selectedResidencyCountryId;
  String? _selectedGrowupCountryId;
  String? _selectedResidencyStateId;
  String? _selectedResidencyCityId;

  List<dynamic> _residencyStates = [];
  bool _isLoadingResidencyStates = false;

  List<dynamic> _residencyCities = [];
  bool _isLoadingResidencyCities = false;

  final _immigrationStatusCtrl = TextEditingController();

  String? _selectedReligionId;
  String? _selectedFamilyValueId;

  String? _selectedSunSign;

  List<dynamic> _maritalStatuses = [];
  List<dynamic> _countries = [];
  List<dynamic> _religions = [];
  List<dynamic> _familyValues = [];

  // 🟢 مصفوفات الألوان المترجمة المحدثة لضمان الدعم المزدوج للغات
  static const List<Map<String, dynamic>> _eyeColors = [
    {'key': 'بني', 'ar': 'بني', 'en': 'Brown', 'color': Color(0xFF5D4037)},
    {'key': 'أسود', 'ar': 'أسود', 'en': 'Black', 'color': Color(0xFF212121)},
    {'key': 'أزرق', 'ar': 'أزرق', 'en': 'Blue', 'color': Color(0xFF1976D2)},
    {'key': 'أخضر', 'ar': 'أخضر', 'en': 'Green', 'color': Color(0xFF388E3C)},
    {'key': 'عسلي', 'ar': 'عسلي', 'en': 'Hazel', 'color': Color(0xFFFBC02D)},
    {'key': 'رمادي', 'ar': 'رمادي', 'en': 'Grey', 'color': Color(0xFF78909C)},
  ];

  static const List<Map<String, dynamic>> _hairColors = [
    {'key': 'أسود', 'ar': 'أسود', 'en': 'Black', 'color': Color(0xFF111111)},
    {
      'key': 'بني داكن',
      'ar': 'بني داكن',
      'en': 'Dark Brown',
      'color': Color(0xFF3E2723)
    },
    {
      'key': 'بني فاتح',
      'ar': 'بني فاتح',
      'en': 'Light Brown',
      'color': Color(0xFF8D6E63)
    },
    {'key': 'أشقر', 'ar': 'أشقر', 'en': 'Blonde', 'color': Color(0xFFFBC02D)},
    {'key': 'أحمر', 'ar': 'أحمر', 'en': 'Red', 'color': Color(0xFFD32F2F)},
    {
      'key': 'رمادي / شيب',
      'ar': 'رمادي / شيب',
      'en': 'Grey / White',
      'color': Color(0xFFB0BEC5)
    },
  ];

  static const List<Map<String, dynamic>> _complexions = [
    {
      'key': 'أبيض',
      'ar': 'أبيض',
      'en': 'Fair / White',
      'color': Color(0xFFFFF3E0)
    },
    {
      'key': 'حنطي فاتح',
      'ar': 'حنطي فاتح',
      'en': 'Light Tan',
      'color': Color(0xFFFFE0B2)
    },
    {
      'key': 'حنطي',
      'ar': 'حنطي',
      'en': 'Wheat / Tan',
      'color': Color(0xFFFFCC80)
    },
    {'key': 'أسمر', 'ar': 'أسمر', 'en': 'Dark Tan', 'color': Color(0xFFA1887F)},
    {
      'key': 'أسمر داكن',
      'ar': 'أسمر داكن',
      'en': 'Dark',
      'color': Color(0xFF5D4037)
    },
  ];

  final Map<String, Color> _eyeColorMap = {
    'بني': const Color(0xFF5D4037),
    'أسود': const Color(0xFF212121),
    'أزرق': const Color(0xFF1976D2),
    'أخضر': const Color(0xFF388E3C),
    'عسلي': const Color(0xFFFBC02D),
    'رمادي': const Color(0xFF78909C),
    'Brown': const Color(0xFF5D4037),
    'Black': const Color(0xFF212121),
    'Blue': const Color(0xFF1976D2),
    'Green': const Color(0xFF388E3C),
    'Hazel': const Color(0xFFFBC02D),
    'Grey': const Color(0xFF78909C),
    'Gray': const Color(0xFF78909C),
  };

  final Map<String, Color> _hairColorMap = {
    'أسود': const Color(0xFF111111),
    'بني داكن': const Color(0xFF3E2723),
    'بني فاتح': const Color(0xFF8D6E63),
    'أشقر': const Color(0xFFFBC02D),
    'أحمر': const Color(0xFFD32F2F),
    'رمادي / شيب': const Color(0xFFB0BEC5),
    'Black': const Color(0xFF111111),
    'Dark Brown': const Color(0xFF3E2723),
    'Light Brown': const Color(0xFF8D6E63),
    'Blonde': const Color(0xFFFBC02D),
    'Red': const Color(0xFFD32F2F),
    'Grey / White': const Color(0xFFB0BEC5),
    'White / Grey': const Color(0xFFB0BEC5),
  };

  final Map<String, Color> _complexionMap = {
    'أبيض': const Color(0xFFFFF3E0),
    'حنطي فاتح': const Color(0xFFFFE0B2),
    'حنطي': const Color(0xFFFFCC80),
    'أسمر': const Color(0xFFA1887F),
    'أسمر داكن': const Color(0xFF5D4037),
    'Fair / White': const Color(0xFFFFF3E0),
    'Fair': const Color(0xFFFFF3E0),
    'White': const Color(0xFFFFF3E0),
    'Light Tan': const Color(0xFFFFE0B2),
    'Wheat / Tan': const Color(0xFFFFCC80),
    'Wheatish': const Color(0xFFFFCC80),
    'Dark Tan': const Color(0xFFA1887F),
    'Dark': const Color(0xFF5D4037),
  };

  List<String> get _livingWithOptions => _isArabic
      ? [
          'بمفردي في مسكني الخاص 🏠',
          'مع العائلة الكريمة 👨‍👩‍👧',
          'أعيش برفقة أطفالي 👶',
          'في سكن مشترك / مع أصدقاء 🤝'
        ]
      : [
          'Living alone in my own place 🏠',
          'With family 👨‍👩‍👧',
          'Living with my children 👶',
          'Shared housing / with friends 🤝'
        ];

  List<String> get _hobbiesOptions => _isArabic
      ? [
          'القراءة والاطلاع 📚',
          'السفر واستكشاف أماكن جديدة ✈',
          'الرياضة والأنشطة البدنية 🏃‍♂️',
          'الرسم والتصميم والفنون 🎨',
          'الطهي وإعداد الوصفات 👨‍🍳',
          'عالم التقنية والبرمجة 💻',
          'الموسيقى والاسترخاء 🎧'
        ]
      : [
          'Reading 📚',
          'Traveling & Exploring ✈',
          'Sports & Physical Activities 🏃‍♂️',
          'Drawing, Design & Arts 🎨',
          'Cooking & Recipes 👨‍🍳',
          'Tech & Programming 💻',
          'Music & Relaxation 🎧'
        ];

  List<String> get _interestsOptions => _isArabic
      ? [
          'التطوير الذاتي والوعي 🌟',
          'عالم الأعمال والاستثمار 📈',
          'الثقافة والأدب والفلسفة 📖',
          'التكنولوجيا والابتكار 🤖',
          'الموضة والأناقة 👔',
          'الطبيعة والتخييم 🌿'
        ]
      : [
          'Self Development & Awareness 🌟',
          'Business & Investment 📈',
          'Culture, Literature & Philosophy 📖',
          'Technology & Innovation 🤖',
          'Fashion & Elegance 👔',
          'Nature & Camping 🌿'
        ];

  List<String> get _musicOptions => _isArabic
      ? [
          'الموسيقى الهادئة والكلاسيكية 🎻',
          'الطرب والأغاني الشرقية 🎤',
          'البوب والموسيقى العصرية 🎵',
          'لا أستمع للموسيقى 🔇'
        ]
      : [
          'Calm & Classical Music 🎻',
          'Oriental Music 🎤',
          'Pop & Modern Music 🎵',
          'Don\'t listen to music 🔇'
        ];

  List<String> get _booksOptions => _isArabic
      ? [
          'الكتب الدينية والروحية 🌙',
          'الروايات والقصص الأدبية 📖',
          'كتب التنمية وتطوير الذات 💡',
          'التاريخ والسير الذاتية 🏛️',
          'الكتب العلمية والمعرفية 🔬'
        ]
      : [
          'Religious & Spiritual Books 🌙',
          'Novels & Literature 📖',
          'Self-Development Books 💡',
          'History & Biographies 🏛️',
          'Scientific & Knowledge Books 🔬'
        ];

  List<String> get _moviesOptions => _isArabic
      ? [
          'الدراما والقصص الواقعية 🎬',
          'الأفلام الوثائقية والمعرفية 🎥',
          'الأفلام الكوميدية والمرحة 🍿',
          'الأفلام والتحديات 💥',
          'الأفلام التاريخية والملحمية ⚔️'
        ]
      : [
          'Drama & True Stories 🎬',
          'Documentaries 🎥',
          'Comedy & Fun Movies 🍿',
          'Action & Adventure 💥',
          'Historical & Epic Movies ⚔️'
        ];

  List<String> get _sportsOptions => _isArabic
      ? [
          'كرة القدم والرياضات الجماعية ⚽',
          'المشي والركض في الهواء الطلق 🏃',
          'اللياقة وبناء الأجسام 🏋️‍♂',
          'السباحة والأنشطة المائية 🏊',
          'التنس والرياضات المضنيّة 🎾'
        ]
      : [
          'Football & Team Sports ⚽',
          'Walking & Outdoor Running 🏃',
          'Fitness & Bodybuilding 🏋️‍♂️',
          'Swimming & Water Sports 🏊',
          'Tennis & Racket Sports 🎾'
        ];

  List<String> get _dressStylesOptions => _isArabic
      ? [
          'أنيق وكاجوال عصري 👟',
          'رسمي وكلاسيكي فاخر 👔',
          'محافظ ومحتشم 🧕',
          'عصري ومتميز 🌟'
        ]
      : [
          'Smart Casual 👟',
          'Formal & Classic 👔',
          'Modest & Conservative 🧕',
          'Trendy & Distinctive 🌟'
        ];

  List<String> get _affectionOptions => _isArabic
      ? [
          'عاطفي جداً وأعبر بدفء 💖',
          'متوازن وأعبر في الأوقات المناسبة ✨',
          'هادئ وحذر في إظهار المشاعر 🌿'
        ]
      : [
          'Very affectionate & warm 💖',
          'Balanced & express at right times ✨',
          'Quiet & reserved with feelings 🌿'
        ];

  List<String> get _humorOptions => _isArabic
      ? [
          'مرح جداً ومحب للضحك والبهجة 😄',
          'متوازن ومرح في الأوقات اللطيفة 😊',
          'جاد معظم الوقت وقليل المزاح 😐'
        ]
      : [
          'Very cheerful & loves laughter 😄',
          'Balanced & fun in nice moments 😊',
          'Serious most of the time 😐'
        ];

  List<String> get _sunSignsKeys => _isArabic
      ? [
          'الحمل ♈',
          'الثور ♉',
          'الجوزاء ♊',
          'السرطان ♋',
          'الأسد ♌',
          'العذراء ♍',
          'الميزان ♎',
          'العقرب ♏',
          'القوس ♐',
          'الجدي ♑',
          'الدلو ♒',
          'الحوت ♓'
        ]
      : [
          'Aries ♈',
          'Taurus ♉',
          'Gemini ♊',
          'Cancer ♋',
          'Leo ♌',
          'Virgo ♍',
          'Libra ♎',
          'Scorpio ♏',
          'Sagittarius ♐',
          'Capricorn ♑',
          'Aquarius ♒',
          'Pisces ♓'
        ];

  /// 🟢 دالة تنقية واستخراج معرف الحالة الاجتماعية بأسلوب آمن ودقيق
  String? _resolveDropdownId(dynamic raw, List<dynamic> options) {
    if (raw == null) return null;
    if (raw is Map) {
      final directId = raw['id'] ??
          raw['value'] ??
          raw['religion_id'] ??
          raw['marital_status_id'] ??
          raw['maritial_status_id'];
      if (directId != null) return _resolveDropdownId(directId, options);
      raw = raw['name'] ?? raw['name_ar'] ?? raw['name_en'] ?? raw['title'];
    }

    final value = raw?.toString().trim() ?? '';
    if (value.isEmpty || value == 'null' || value == 'undefined') return null;
    if (RegExp(r'^\d+$').hasMatch(value)) return value;

    String normalize(String input) => input
        .toLowerCase()
        .replaceAll(RegExp(r'[\s_-]+'), '')
        .trim();

    final target = normalize(value);
    for (final option in options) {
      if (option is! Map) continue;
      final id = option['id']?.toString();
      if (id == null || id.isEmpty) continue;
      final candidates = [
        option['name'],
        option['name_ar'],
        option['name_en'],
        option['title'],
        option['value'],
      ];
      if (candidates.any((candidate) =>
          candidate != null && normalize(candidate.toString()) == target)) {
        return id;
      }
    }

    // Never guess a foreign key from a translated display label.
    return null;
  }

  String? _sanitizeReligionId(dynamic raw) =>
      _resolveDropdownId(raw, _religions);

  String _getLocalizedCityName(dynamic cityData, bool isAr) {
    if (cityData == null) return '';
    if (cityData is Map) {
      String nameAr = (cityData['name_ar'] ?? cityData['name_arabic'] ?? '')
          .toString()
          .trim();
      String nameEn =
          (cityData['name'] ?? cityData['city_name'] ?? '').toString().trim();
      return isAr
          ? (nameAr.isNotEmpty ? nameAr : nameEn)
          : (nameEn.isNotEmpty ? nameEn : nameAr);
    }
    return cityData.toString().trim();
  }

  List<dynamic> _sortCountriesWithArabFirst(List<dynamic> countriesList) {
    if (countriesList.isEmpty) return [];
    final Set<String> arabCodes = {
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

    final List<dynamic> arabCountries = [];
    final List<dynamic> otherCountries = [];

    for (var c in countriesList) {
      if (c is Map) {
        final code =
            (c['code'] ?? c['iso2'] ?? '').toString().toUpperCase().trim();
        final name = (c['name'] ?? '').toString().toLowerCase();
        final nameAr = (c['name_ar'] ?? c['name_arabic'] ?? '').toString();

        bool isArab = arabCodes.contains(code) ||
            name.contains('syria') ||
            nameAr.contains('سوريا');

        if (isArab) {
          arabCountries.add(c);
        } else {
          otherCountries.add(c);
        }
      } else {
        otherCountries.add(c);
      }
    }
    return [...arabCountries, ...otherCountries];
  }

  String _mapSmokeToBackend(String? val) {
    if (val == null || val.isEmpty) return 'no';
    if (val == 'no' || val == 'yes') return val;
    if (val.contains('لا أدخن') || val.contains('Non-smoker')) return 'no';
    if (val.contains('نعم') || val.contains('Smoker')) return 'yes';
    return 'no';
  }

  String? _mapSmokeFromBackend(String? raw, bool isAr) {
    if (raw == null || raw.isEmpty) return null;
    String lower = raw.trim().toLowerCase();
    if (lower == 'no') return isAr ? 'لا أدخن أبداً 🌿' : 'Non-smoker 🌿';
    if (lower == 'yes') return isAr ? 'نعم، أدخن 🚬' : 'Smoker 🚬';
    return raw;
  }

  String _sanitizeForApi(String? input) {
    if (input == null || input.isEmpty) return '';
    return input
        .replaceAll(
            RegExp(
                r'[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F700}-\u{1F77F}\u{1F780}-\u{1F8FF}\u{1FA00}-\u{1FA6F}\u{1FA70}-\u{1FAFF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}]',
                unicode: true),
            '')
        .trim();
  }

  Map<String, dynamic>? _asProfileMap(dynamic raw) {
    return MemberProfileModel.safeMapOrNull(raw);
  }

  Map<String, dynamic>? _unwrapSection(
    dynamic raw, {
    required List<String> fieldHints,
    List<String> nestedKeys = const [],
  }) {
    Map<String, dynamic>? current = _asProfileMap(raw);
    if (current == null) return null;

    bool looksPopulated(Map<String, dynamic> map) {
      for (final key in fieldHints) {
        if (map[key] != null && map[key].toString().trim().isNotEmpty) {
          return true;
        }
      }
      return false;
    }

    if (looksPopulated(current)) return current;

    final keysToTry = <String>[...nestedKeys, 'data', 'item', 'record'];
    for (final key in keysToTry) {
      if (current[key] == null) continue;
      final inner = _asProfileMap(current[key]);
      if (inner == null) continue;
      if (looksPopulated(inner)) return inner;
      for (final nestedKey in keysToTry) {
        if (inner[nestedKey] == null) continue;
        final deeper = _asProfileMap(inner[nestedKey]);
        if (deeper != null && looksPopulated(deeper)) return deeper;
      }
    }
    return current;
  }

  String? _fieldString(dynamic value) {
    if (value == null) return null;
    if (value is Map) {
      final nested = value['name'] ??
          value['name_ar'] ??
          value['name_en'] ??
          value['title'] ??
          value['label'] ??
          value['value'] ??
          value['text'] ??
          value['introduction'] ??
          value['about'];
      return _fieldString(nested);
    }
    if (value is List && value.isNotEmpty) {
      return _fieldString(value.first);
    }
    final String text = value.toString().trim();
    if (text.isEmpty ||
        text == 'null' ||
        text == 'undefined' ||
        text == '{}' ||
        text == '[]') {
      return null;
    }
    return text;
  }

  String _normalizeCompare(String input) {
    return AttributeTranslator.cleanEmojis(input)
        .toLowerCase()
        .replaceAll(RegExp(r'\s+'), ' ')
        .trim();
  }

  String? _matchListedOption(
      String? raw, List<Map<String, dynamic>> options) {
    final String? value = _fieldString(raw);
    if (value == null) return null;

    final String clean = _normalizeCompare(value);
    final String translatedAr = _normalizeCompare(
        AttributeTranslator.translate(value, isArabic: true));
    final String translatedEn = _normalizeCompare(
        AttributeTranslator.translate(value, isArabic: false));

    for (final opt in options) {
      final candidates = <String>[
        opt['key']?.toString() ?? '',
        opt['ar']?.toString() ?? '',
        opt['en']?.toString() ?? '',
        opt['id']?.toString() ?? '',
        opt['name']?.toString() ?? '',
      ];
      for (final candidate in candidates) {
        if (candidate.isEmpty) continue;
        if (_normalizeCompare(candidate) == clean ||
            _normalizeCompare(candidate) == translatedAr ||
            _normalizeCompare(candidate) == translatedEn) {
          return (opt['key'] ?? opt['id'] ?? opt['ar'])?.toString();
        }
      }
    }
    return value;
  }

  String? _matchStringOption(String? raw, List<String> options) {
    final String? value = _fieldString(raw);
    if (value == null) return null;
    if (options.contains(value)) return value;

    final String clean = _normalizeCompare(value);
    final String translatedAr = _normalizeCompare(
        AttributeTranslator.translate(value, isArabic: true));
    final String translatedEn = _normalizeCompare(
        AttributeTranslator.translate(value, isArabic: false));

    for (final opt in options) {
      final String oc = _normalizeCompare(opt);
      if (oc == clean || oc == translatedAr || oc == translatedEn) {
        return opt;
      }
    }
    return value;
  }

  String _parseGenderId(dynamic raw, {dynamic isFemaleFlag}) {
    if (isFemaleFlag == true || isFemaleFlag == 1 || isFemaleFlag == '1') {
      return '2';
    }
    if (raw is Map) {
      return _parseGenderId(
        raw['id'] ?? raw['gender'] ?? raw['name'] ?? raw['value'],
        isFemaleFlag: raw['is_female'] ?? raw['female'],
      );
    }
    final String s = (raw ?? '').toString().trim().toLowerCase();
    if (s == '2' ||
        s.contains('female') ||
        s.contains('أنثى') ||
        s.contains('انثى') ||
        s.contains('woman') ||
        s.contains('girl')) {
      return '2';
    }
    return '1';
  }

  String _extractIntroductionText(
    Map<String, dynamic> fullData,
    MemberProfileModel fullProfile,
    Map<String, dynamic>? basic,
  ) {
    if (fullProfile.introduction.trim().isNotEmpty) {
      return fullProfile.introduction.trim();
    }

    final dynamic introRaw = fullData['introduction'] ??
        fullData['intoduction'] ??
        fullData['about'] ??
        fullData['bio'];
    final String? fromSection = introRaw is Map
        ? _fieldString(introRaw['introduction'] ??
            introRaw['intoduction'] ??
            introRaw['about'] ??
            introRaw['bio'] ??
            introRaw['text'] ??
            introRaw)
        : _fieldString(introRaw);
    if (fromSection != null && fromSection.isNotEmpty) return fromSection;

    if (basic != null) {
      final String? fromBasic = _fieldString(basic['introduction'] ??
          basic['intoduction'] ??
          basic['about'] ??
          basic['bio']);
      if (fromBasic != null && fromBasic.isNotEmpty) return fromBasic;
    }
    return '';
  }

  String? _localizedOption(
      String? key, List<Map<String, dynamic>> options) {
    if (key == null || key.trim().isEmpty) return null;
    for (final opt in options) {
      final String optKey = (opt['key'] ?? opt['id'] ?? '').toString();
      if (optKey == key ||
          opt['ar'] == key ||
          opt['en'] == key) {
        return _isArabic
            ? (opt['ar'] ?? optKey).toString()
            : (opt['en'] ?? optKey).toString();
      }
    }
    return AttributeTranslator.translate(key, isArabic: _isArabic);
  }

  /// 🟢 دالة محسنة لاستخراج أخطاء الـ API من الاستجابة حتى عند تعارض مفاتيح الأخطاء
  String _extractErrorMessage(dynamic failedResult, String defaultMsg) {
    if (failedResult == null) return defaultMsg;

    if (failedResult is Map) {
      if (failedResult['errors'] != null && failedResult['errors'] is Map) {
        final errorsMap = failedResult['errors'] as Map;
        final List<String> errList = [];
        errorsMap.forEach((key, value) {
          if (value is List && value.isNotEmpty) {
            errList.add(value.first.toString());
          } else if (value != null) {
            errList.add(value.toString());
          }
        });
        if (errList.isNotEmpty) {
          return errList.join('\n');
        }
      }
      if (failedResult['message'] != null &&
          failedResult['message'].toString().isNotEmpty) {
        return failedResult['message'].toString();
      }
    }

    if (failedResult is String && failedResult.isNotEmpty) {
      return failedResult;
    }

    if (failedResult is List && failedResult.isNotEmpty) {
      return failedResult.map((e) => e.toString()).join('\n');
    }

    return defaultMsg;
  }

  String _getCountryFlagUrl(dynamic country) {
    if (country is Map<String, dynamic>) {
      return ApiService.getOptimizedFlagUrl(country);
    } else if (country is Map) {
      return ApiService.getOptimizedFlagUrl(Map<String, dynamic>.from(country));
    }
    return 'https://flagcdn.com/w80/un.png';
  }

  void _showNotification(String message, bool isSuccess) {
    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        elevation: 0,
        behavior: SnackBarBehavior.floating,
        backgroundColor: Colors.transparent,
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
        padding: EdgeInsets.zero,
        duration: const Duration(seconds: 4),
        content: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
            color:
                isSuccess ? const Color(0xFF0F382C) : const Color(0xFF4A1015),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Row(
            children: [
              Icon(
                isSuccess
                    ? Icons.check_circle_outline_rounded
                    : Icons.error_outline_rounded,
                color: isSuccess
                    ? const Color(0xFF2EC4B6)
                    : const Color(0xFFFF5252),
                size: 24,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  message,
                  style: const TextStyle(
                      color: Colors.white, fontSize: 14, fontFamily: 'Cairo'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _saveAppLanguage();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadAllProfileDataFast();
    });
  }

  Future<void> _saveAppLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('app_language', widget.currentLanguage);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _firstNameCtrl.dispose();
    _lastNameCtrl.dispose();
    _phoneCtrl.dispose();
    _dobCtrl.dispose();
    _childrenCtrl.dispose();
    _introCtrl.dispose();
    _immigrationStatusCtrl.dispose();
    _institutionCtrl.dispose();
    _designationCtrl.dispose();
    _companyCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadResidencyStates(String countryId,
      {bool keepSelected = false}) async {
    if (countryId.isEmpty) return;
    if (mounted) {
      setState(() {
        _isLoadingResidencyStates = true;
        _residencyStates = [];
        if (!keepSelected) {
          _selectedResidencyStateId = null;
          _selectedResidencyCityId = null;
          _residencyCities = [];
        }
      });
    }

    try {
      final parsedId = int.tryParse(countryId) ?? 0;
      final dynamic response = await ApiService.getStatesByCountry(parsedId,
          lang: widget.currentLanguage);

      List<dynamic> fetchedStates = [];
      if (response is List) {
        fetchedStates = response;
      } else if (response is Map && response['states'] is List) {
        fetchedStates = response['states'];
      }

      if (mounted) {
        setState(() {
          _residencyStates = fetchedStates;
          _isLoadingResidencyStates = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingResidencyStates = false);
      }
    }
  }

  Future<void> _loadResidencyCities(String stateId,
      {bool keepSelected = false}) async {
    if (stateId.isEmpty) return;
    if (mounted) {
      setState(() {
        _isLoadingResidencyCities = true;
        _residencyCities = [];
        if (!keepSelected) {
          _selectedResidencyCityId = null;
        }
      });
    }

    try {
      final parsedId = int.tryParse(stateId) ?? 0;
      final dynamic response = await ApiService.getCitiesByState(parsedId,
          lang: widget.currentLanguage);

      List<dynamic> fetchedCities = [];
      if (response is List) {
        fetchedCities = response;
      } else if (response is Map && response['cities'] is List) {
        fetchedCities = response['cities'];
      }

      if (mounted) {
        setState(() {
          _residencyCities = fetchedCities;
          _isLoadingResidencyCities = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingResidencyCities = false);
      }
    }
  }

  double _calculateProfileCompletion() {
    int total = 10;
    int filled = 0;
    if (_firstNameCtrl.text.isNotEmpty) filled++;
    if (_lastNameCtrl.text.isNotEmpty) filled++;
    if (_introCtrl.text.isNotEmpty) filled++;
    if (_selectedBirthCountryId != null) filled++;
    if (_selectedResidencyCountryId != null) filled++;
    if (_selectedReligionId != null) filled++;
    if (_selectedHeight != null) filled++;
    if (_selectedWeight != null) filled++;
    if (_selectedHobby != null) filled++;
    if (_selectedSunSign != null) filled++;
    return filled / total;
  }

  void _recordLoadedFields(String section, Map<String, dynamic>? data) {
    if (data == null || data.isEmpty) return;
    final loadedFields =
        _loadedProfileFields.putIfAbsent(section, () => <String>{});
    loadedFields.addAll(data.keys.map((key) => key.toString()));
  }

  bool _wasFieldLoaded(String section, List<String> aliases) {
    final fields = _loadedProfileFields[section];
    if (fields == null) return false;
    return aliases.any(fields.contains);
  }

  bool _shouldSendProfileField(
    String section,
    List<String> aliases,
    dynamic value,
  ) {
    if (value != null && value.toString().trim().isNotEmpty) return true;
    return _wasFieldLoaded(section, aliases);
  }

  Map<String, dynamic> _mergeProfileResponses(
    Map<String, dynamic>? cached,
    Map<String, dynamic> incoming,
  ) {
    final merged = <String, dynamic>{...?cached};
    for (final entry in incoming.entries) {
      final value = entry.value;
      if (value == null) continue;
      if (value is Map) {
        final previous = merged[entry.key];
        final section = <String, dynamic>{};
        if (previous is Map) {
          section.addAll(Map<String, dynamic>.from(previous));
        }
        for (final field in value.entries) {
          if (field.value != null) {
            section[field.key.toString()] = field.value;
          }
        }
        if (section.isNotEmpty) merged[entry.key] = section;
      } else if (value is List) {
        if (value.isNotEmpty || !merged.containsKey(entry.key)) {
          merged[entry.key] = value;
        }
      } else {
        merged[entry.key] = value;
      }
    }
    return merged;
  }

  bool _hasProfileData(Map<String, dynamic> profile) {
    return profile.values.any((value) =>
        (value is Map && value.isNotEmpty) ||
        (value is List && value.isNotEmpty));
  }

  void _setControllerIfPresent(
    TextEditingController controller,
    Map<String, dynamic> data,
    List<String> keys,
  ) {
    for (final key in keys) {
      if (!data.containsKey(key)) continue;
      final value = data[key];
      controller.text = value == null ? '' : (_fieldString(value) ?? '');
      return;
    }
  }

  Future<void> _loadAllProfileDataFast() async {
    final lang = widget.currentLanguage;
    Map<String, dynamic>? cachedData;

    try {
      cachedData = await ProfileCacheService.getCachedProfile();
      if (cachedData != null && _hasProfileData(cachedData) && mounted) {
        await _applyProfileDataToState(cachedData, dropdowns: null);
        if (mounted) setState(() => _isLoading = false);
      }

      final results = await Future.wait<dynamic>(
        [
          ApiService.getFullProfile(lang: lang),
          ApiService.getProfileDropdowns(lang: lang),
        ],
        eagerError: false,
      );

      final rawFullProfile = results[0];
      final dropdowns = results[1] is Map
          ? Map<String, dynamic>.from(results[1] as Map)
          : null;

      if (rawFullProfile is Map) {
        final parsed = await compute(
          parseProfileData,
          Map<String, dynamic>.from(rawFullProfile),
        );
        final merged = _mergeProfileResponses(cachedData, parsed);

        // لا نحفظ استجابة فارغة/ناقصة عندما فشل أحد طلبات جلب الأقسام.
        if (_hasProfileData(merged)) {
          await ProfileCacheService.saveProfileToCache(merged);
          if (mounted) {
            await _applyProfileDataToState(merged, dropdowns: dropdowns);
            if (mounted) setState(() => _isLoading = false);
          }
        }
      }
    } catch (e) {
      debugPrint('خطأ في تحديث البيانات: $e');
    } finally {
      if (mounted && _isLoading) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _applyProfileDataToState(
      Map<String, dynamic> fullProfileResponse,
      {Map<String, dynamic>? dropdowns}) async {
    final lang = widget.currentLanguage;
    String? residencyCountryToFetch;
    String? residencyStateToFetch;

    Map<String, dynamic>? fullData = fullProfileResponse.containsKey('data') &&
            fullProfileResponse['data'] is Map &&
            (fullProfileResponse['basic_info'] == null &&
                fullProfileResponse['physical_attributes'] == null)
        ? Map<String, dynamic>.from(fullProfileResponse['data'] as Map)
        : fullProfileResponse;

    final basic = _unwrapSection(
      fullData['basic_info'] ?? fullData['basic'],
      fieldHints: const [
        'first_name',
        'firs_name',
        'last_name',
        'gender',
        'phone',
        'date_of_birth'
      ],
      nestedKeys: const ['basic_info', 'basic'],
    );
    final MemberProfileModel fullProfile =
        MemberProfileModel.fromJson(fullData, 0);

    final Map<String, dynamic>? educationData =
        ApiService.extractFirstResourceMap(fullData['education']);
    final Map<String, dynamic>? careerData =
        ApiService.extractFirstResourceMap(fullData['career']);

    final residency = _unwrapSection(
      fullData['residency_information'] ??
          fullData['residency'] ??
          fullData['present_address'] ??
          fullData['residence_info'],
      fieldHints: const [
        'birth_country_id',
        'residency_country_id',
        'recidency_country_id',
        'immigration_status'
      ],
      nestedKeys: const [
        'residency_information',
        'residency',
        'present_address',
        'residence_info'
      ],
    );

    final spiritual = _unwrapSection(
      fullData['spiritual_background'] ??
          fullData['spiritual'] ??
          fullData['spiritual_backgrounds'],
      fieldHints: const ['religion_id', 'member_religion_id', 'family_value_id'],
      nestedKeys: const [
        'spiritual_background',
        'spiritual',
        'spiritual_backgrounds'
      ],
    );

    final physical = _unwrapSection(
      fullData['physical_attributes'] ?? fullData['physical'],
      fieldHints: const [
        'eye_color',
        'hair_color',
        'complexion',
        'height',
        'weight',
        'eye_colour',
        'hair_colour'
      ],
      nestedKeys: const ['physical_attributes', 'physical'],
    );

    final lifestyle = _unwrapSection(
      fullData['lifestyle'] ?? fullData['life_style'] ?? fullData['lifestyles'],
      fieldHints: const ['diet', 'living_with', 'smoke', 'drink'],
      nestedKeys: const ['lifestyle', 'life_style', 'lifestyles'],
    );

    final hobbies = _unwrapSection(
      fullData['hobbies_interest'] ?? fullData['hobbies'],
      fieldHints: const ['hobbies', 'interests', 'music', 'books'],
      nestedKeys: const ['hobbies_interest', 'hobbies'],
    );

    final attitude = _unwrapSection(
      fullData['attitude_behavior'] ?? fullData['attitude'],
      fieldHints: const [
        'affection',
        'humor',
        'religious_service',
        'political_views'
      ],
      nestedKeys: const ['attitude_behavior', 'attitude'],
    );

    final astro = _unwrapSection(
      fullData['astronomic_information'] ?? fullData['astro'],
      fieldHints: const ['sun_sign'],
      nestedKeys: const ['astronomic_information', 'astro'],
    );

    _recordLoadedFields('basic_info', basic);
    _recordLoadedFields('education', educationData);
    _recordLoadedFields('career', careerData);
    _recordLoadedFields('residency_information', residency);
    _recordLoadedFields('spiritual_background', spiritual);
    _recordLoadedFields('physical_attributes', physical);
    _recordLoadedFields('lifestyle', lifestyle);
    _recordLoadedFields('hobbies_interest', hobbies);
    _recordLoadedFields('attitude_behavior', attitude);
    _recordLoadedFields('astronomic_information', astro);

    if (dropdowns != null) {
      _maritalStatuses = dropdowns['marital_status'] ??
          dropdowns['maritial_status'] ??
          dropdowns['marital_statuses'] ??
          dropdowns['maritial_statuses'] ??
          dropdowns['marital_status_list'] ??
          [];
      _countries = _sortCountriesWithArabFirst(
          dropdowns['countries'] ?? dropdowns['country'] ?? []);
      _religions = dropdowns['religions'] ?? [];
      _familyValues = dropdowns['family_values'] ?? [];
    }

    final introText = _extractIntroductionText(fullData, fullProfile, basic);
    if (introText.isNotEmpty ||
        _loadedProfileFields['basic_info']?.contains('introduction') == true ||
        _loadedProfileFields['basic_info']?.contains('intoduction') == true) {
      _introCtrl.text = introText;
    }

    if (basic != null) {
      _setControllerIfPresent(
          _firstNameCtrl, basic, const ['first_name', 'firs_name']);
      _setControllerIfPresent(_lastNameCtrl, basic, const ['last_name']);
      _setControllerIfPresent(_phoneCtrl, basic, const ['phone']);
      _setControllerIfPresent(
          _dobCtrl, basic, const ['date_of_birth', 'birthday']);
      _setControllerIfPresent(
          _childrenCtrl, basic, const ['children', 'no_of_children']);

      if (basic.containsKey('photo') || basic.containsKey('avatar')) {
        _currentPhotoUrl =
            _fieldString(basic['photo'] ?? basic['avatar']) ?? '';
      }
      if (basic.containsKey('gender_id') ||
          basic.containsKey('gender') ||
          fullData.containsKey('gender_id') ||
          fullData.containsKey('gender') ||
          basic.containsKey('is_female') ||
          fullData.containsKey('is_female')) {
        _genderWasLoaded = true;
        _selectedGender = _parseGenderId(
          basic['gender_id'] ?? basic['gender'] ??
              fullData['gender_id'] ?? fullData['gender'],
          isFemaleFlag: basic['is_female'] ?? fullData['is_female'],
        );
      }

      final rawOnBehalf = basic['on_behalf_id'] ??
          basic['onbehalf_id'] ??
          basic['on_behalf'] ??
          basic['onbehalf'];
      final resolvedOnBehalf = rawOnBehalf is Map
          ? (rawOnBehalf['id'] ?? rawOnBehalf['value'])
          : rawOnBehalf;
      final onBehalfId = resolvedOnBehalf?.toString().trim();
      if (onBehalfId != null && RegExp(r'^\d+$').hasMatch(onBehalfId)) {
        _onBehalfValue = onBehalfId;
      }
    }

    if (educationData != null) {
      _educationId = educationData['id']?.toString() ??
          educationData['education_id']?.toString();
      if (educationData.containsKey('degree')) {
        _selectedEducationDegree = _matchStringOption(
            educationData['degree']?.toString(), _educationDegreeOptions);
      }
      _setControllerIfPresent(
          _institutionCtrl, educationData, const ['institution']);
    }

    if (careerData != null) {
      _careerId =
          careerData['id']?.toString() ?? careerData['career_id']?.toString();
      _setControllerIfPresent(
          _designationCtrl, careerData, const ['designation']);
      _setControllerIfPresent(_companyCtrl, careerData, const ['company']);
    }

    if (residency != null) {
      if (residency.containsKey('birth_country_id')) {
        _selectedBirthCountryId = residency['birth_country_id']?.toString();
      }
      if (residency.containsKey('growup_country_id') ||
          residency.containsKey('birth_country_id')) {
        _selectedGrowupCountryId = residency['growup_country_id']?.toString() ??
            residency['birth_country_id']?.toString();
      }
      if (residency.containsKey('residency_country_id') ||
          residency.containsKey('recidency_country_id')) {
        _selectedResidencyCountryId =
            residency['residency_country_id']?.toString() ??
                residency['recidency_country_id']?.toString();
      }
      if (residency.containsKey('residency_state_id') ||
          residency.containsKey('recidency_state_id')) {
        _selectedResidencyStateId =
            residency['residency_state_id']?.toString() ??
                residency['recidency_state_id']?.toString();
      }
      if (residency.containsKey('residency_city_id') ||
          residency.containsKey('recidency_city_id')) {
        _selectedResidencyCityId =
            residency['residency_city_id']?.toString() ??
                residency['recidency_city_id']?.toString();
      }

      residencyCountryToFetch = _selectedResidencyCountryId;
      residencyStateToFetch = _selectedResidencyStateId;
      _setControllerIfPresent(
          _immigrationStatusCtrl, residency, const ['immigration_status']);
    }

    if (spiritual != null) {
      final religionValue = spiritual['raw_religion_id'] ??
          spiritual['member_religion_id'] ??
          spiritual['religion_id'];
      _selectedReligionId = _sanitizeReligionId(religionValue);
      if (spiritual.containsKey('family_value_id')) {
        _selectedFamilyValueId = spiritual['family_value_id']?.toString();
      }
    }

    if (physical != null) {
      if (physical.containsKey('height')) {
        _selectedHeight = _fieldString(physical['height']);
      }
      if (physical.containsKey('weight')) {
        _selectedWeight = _fieldString(physical['weight']);
      }
      if (physical.containsKey('eye_color') || physical.containsKey('eye_colour')) {
        _selectedEyeColor = _matchListedOption(
            physical['eye_color'] ?? physical['eye_colour'], _eyeColors);
      }
      if (physical.containsKey('hair_color') || physical.containsKey('hair_colour')) {
        _selectedHairColor = _matchListedOption(
            physical['hair_color'] ?? physical['hair_colour'], _hairColors);
      }
      if (physical.containsKey('complexion')) {
        _selectedComplexion =
            _matchListedOption(physical['complexion'], _complexions);
      }
      if (physical.containsKey('blood_group')) {
        _selectedBloodGroup = _fieldString(physical['blood_group']);
      }
      if (physical.containsKey('body_type')) {
        _selectedBodyType = _fieldString(physical['body_type']);
      }
      if (physical.containsKey('body_art')) {
        _selectedBodyArt = _fieldString(physical['body_art']);
      }
    } else if (fullProfile.physicalAttributes != null) {
      final phys = fullProfile.physicalAttributes!;
      if (phys.height.isNotEmpty) _selectedHeight = phys.height;
      if (phys.weight.isNotEmpty) _selectedWeight = phys.weight;
      _selectedEyeColor = _matchListedOption(phys.eyeColor, _eyeColors);
      _selectedHairColor = _matchListedOption(phys.hairColor, _hairColors);
      _selectedComplexion =
          _matchListedOption(phys.complexion, _complexions);
      if (phys.bloodGroup.isNotEmpty) _selectedBloodGroup = phys.bloodGroup;
      if (phys.bodyType.isNotEmpty) _selectedBodyType = phys.bodyType;
      if (phys.bodyArt.isNotEmpty) _selectedBodyArt = phys.bodyArt;
    }

    if (lifestyle != null) {
      _selectedDiet = _fieldString(lifestyle['diet']);
      _selectedLivingWith = _matchStringOption(
          lifestyle['living_with'], _livingWithOptions);
      _selectedSmoke = _mapSmokeFromBackend(
          _fieldString(lifestyle['smoke']), lang == 'ar');
      _selectedDrink = _fieldString(lifestyle['drink']);
    }

    if (hobbies != null) {
      _selectedHobby =
          _matchStringOption(hobbies['hobbies'], _hobbiesOptions);
      _selectedInterest =
          _matchStringOption(hobbies['interests'], _interestsOptions);
      _selectedMusic = _matchStringOption(hobbies['music'], _musicOptions);
      _selectedBook = _matchStringOption(hobbies['books'], _booksOptions);
      _selectedMovie = _matchStringOption(hobbies['movies'], _moviesOptions);
      _selectedTvShow = _fieldString(hobbies['tv_shows']);
      _selectedSport = _matchStringOption(hobbies['sports'], _sportsOptions);
      _selectedFitness = _fieldString(hobbies['fitness_activities']);
      _selectedCuisine = _fieldString(hobbies['cuisines']);
      _selectedDressStyle =
          _matchStringOption(hobbies['dress_styles'], _dressStylesOptions);
    }

    if (attitude != null) {
      _selectedAffection =
          _matchStringOption(attitude['affection'], _affectionOptions);
      _selectedHumor = _matchStringOption(attitude['humor'], _humorOptions);
      _selectedReligiousService = _fieldString(attitude['religious_service']);
      _selectedPoliticalView = _fieldString(attitude['political_views']);
    }

    if (astro != null) {
      _selectedSunSign =
          _matchStringOption(astro['sun_sign']?.toString(), _sunSignsKeys);
    }

    if (residencyCountryToFetch != null) {
      await _loadResidencyStates(residencyCountryToFetch, keepSelected: true);
      if (residencyStateToFetch != null) {
        await _loadResidencyCities(residencyStateToFetch, keepSelected: true);
      }
    }
  }

  void _showCustomBottomSheetPicker({
    required String title,
    required List<Map<String, dynamic>> items,
    required String? currentValue,
    required ValueChanged<String> onSelected,
    bool isAr = true,
    bool enableSearch = false,
  }) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return GenericPickerBottomSheetContent(
          title: title,
          items: items,
          currentValue: currentValue,
          onSelected: onSelected,
          isAr: isAr,
          enableSearch: enableSearch || items.length > 5,
        );
      },
    );
  }

  void _showCountryPickerBottomSheet(bool isAr,
      {required bool isBirthCountry}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return CountryPickerBottomSheetContent(
          countries: _countries,
          selectedCountryId: isBirthCountry
              ? _selectedBirthCountryId
              : _selectedResidencyCountryId,
          isBirthCountry: isBirthCountry,
          isAr: isAr,
          onCountrySelected: (id) {
            if (isBirthCountry) {
              setState(() {
                _selectedBirthCountryId = id;
                _selectedGrowupCountryId = id;
              });
            } else {
              setState(() => _selectedResidencyCountryId = id);
              _loadResidencyStates(id);
            }
          },
        );
      },
    );
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
    );

    if (pickedFile != null) {
      final croppedFile = await ImageCropper().cropImage(
        sourcePath: pickedFile.path,
        aspectRatio: const CropAspectRatio(ratioX: 4, ratioY: 5),
        uiSettings: [
          AndroidUiSettings(
            toolbarTitle:
                _isArabic ? 'اقتصاص الصورة الشخصية' : 'Crop Profile Photo',
            toolbarColor: const Color(0xFF01579B),
            toolbarWidgetColor: Colors.white,
            activeControlsWidgetColor: Colors.amberAccent,
            initAspectRatio: CropAspectRatioPreset.original,
            lockAspectRatio: true,
          ),
          IOSUiSettings(
            title: _isArabic ? 'اقتصاص الصورة الشخصية' : 'Crop Profile Photo',
            aspectRatioLockEnabled: true,
            resetAspectRatioEnabled: false,
          ),
        ],
      );

      if (croppedFile != null) {
        setState(() => _pickedImageFile = File(croppedFile.path));
      }
    }
  }

  Future<void> _selectDateOfBirth() async {
    DateTime initial = DateTime.tryParse(_dobCtrl.text) ?? DateTime(1998, 1, 1);

    if (initial.isBefore(DateTime(1940))) initial = DateTime(1940);
    if (initial.isAfter(DateTime(2008))) initial = DateTime(2008);

    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(1940),
      lastDate: DateTime(2008),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.dark(
              primary: Color(0xFF0288D1),
              onPrimary: Colors.white,
              surface: Color(0xFF01579B),
              onSurface: Colors.white,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() {
        _dobCtrl.text =
            "${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}";
      });
    }
  }

  Future<void> _saveCurrentSection() async {
    if (_isSaving || !mounted) return;
    setState(() => _isSaving = true);

    bool allSuccess = true;
    bool anySuccess = false;
    String lastMessage = '';
    final int index = _tabController.index;
    final lang = widget.currentLanguage;
    final List<Future<Map<String, dynamic>>> requests = [];

    try {
      if (index == 0) {
        if (_firstNameCtrl.text.trim().isEmpty ||
            _lastNameCtrl.text.trim().isEmpty ||
            _dobCtrl.text.trim().isEmpty) {
          allSuccess = false;
          lastMessage = _isArabic
              ? 'يرجى التأكد من إدخال الاسم وتاريخ الميلاد'
              : 'Please complete the name and date of birth';
        } else {
          final bool sendIntroduction = _introCtrl.text.trim().isNotEmpty ||
              _wasFieldLoaded('basic_info', const ['introduction', 'intoduction', 'about']);
          requests.add(ApiService.updateBasicInfo(
            firstName: _firstNameCtrl.text.trim(),
            lastName: _lastNameCtrl.text.trim(),
            phone: _phoneCtrl.text.trim(),
            gender: _genderWasLoaded ? _selectedGender : null,
            dateOfBirth: _dobCtrl.text.trim(),
            maritalStatus: _selectedMaritalStatusId,
            onBehalf: _onBehalfValue,
            children: _wasFieldLoaded('basic_info', const ['no_of_children', 'children'])
                ? _childrenCtrl.text.trim()
                : null,
            introduction: sendIntroduction ? _introCtrl.text.trim() : null,
            photoFile: _pickedImageFile,
            lang: lang,
          ));

          final physicalPayload = <String, dynamic>{};
          void addPhysical(String key, List<String> aliases, dynamic value) {
            if (_shouldSendProfileField('physical_attributes', aliases, value)) {
              physicalPayload[key] = value ?? '';
            }
          }
          addPhysical('height', const ['height'], _selectedHeight);
          addPhysical('weight', const ['weight'], _selectedWeight);
          addPhysical('eye_color', const ['eye_color', 'eye_colour'], _selectedEyeColor);
          addPhysical('hair_color', const ['hair_color', 'hair_colour'], _selectedHairColor);
          addPhysical('complexion', const ['complexion'], _selectedComplexion);
          addPhysical('blood_group', const ['blood_group'], _selectedBloodGroup);
          addPhysical('body_type', const ['body_type'], _selectedBodyType);
          addPhysical('body_art', const ['body_art'], _selectedBodyArt);
          if (physicalPayload.isNotEmpty) {
            requests.add(ApiService.updatePhysicalAttributes(physicalPayload, lang: lang));
          }

          final educationPayload = <String, dynamic>{};
          if (_educationId != null && _educationId!.isNotEmpty) {
            educationPayload['id'] = _educationId;
            educationPayload['education_id'] = _educationId;
          }
          if (_shouldSendProfileField('education', const ['degree'], _selectedEducationDegree)) {
            educationPayload['degree'] = _selectedEducationDegree ?? '';
          }
          if (_shouldSendProfileField('education', const ['institution'], _institutionCtrl.text)) {
            educationPayload['institution'] = _institutionCtrl.text.trim();
          }
          if (educationPayload.keys.any((key) => key != 'id' && key != 'education_id')) {
            requests.add(ApiService.updateEducation(educationPayload, lang: lang));
          }

          final careerPayload = <String, dynamic>{};
          if (_careerId != null && _careerId!.isNotEmpty) {
            careerPayload['id'] = _careerId;
            careerPayload['career_id'] = _careerId;
          }
          if (_shouldSendProfileField('career', const ['designation'], _designationCtrl.text)) {
            careerPayload['designation'] = _designationCtrl.text.trim();
          }
          if (_shouldSendProfileField('career', const ['company'], _companyCtrl.text)) {
            careerPayload['company'] = _companyCtrl.text.trim();
          }
          if (careerPayload.keys.any((key) => key != 'id' && key != 'career_id')) {
            requests.add(ApiService.updateCareer(careerPayload, lang: lang));
          }
        }
      } else if (index == 1) {
        final residencyPayload = <String, dynamic>{};
        void addResidency(String key, List<String> aliases, dynamic value) {
          if (_shouldSendProfileField('residency_information', aliases, value)) {
            residencyPayload[key] = value ?? '';
          }
        }
        addResidency('birth_country_id', const ['birth_country_id'], _selectedBirthCountryId);
        addResidency('growup_country_id', const ['growup_country_id'], _selectedGrowupCountryId);
        addResidency('recidency_country_id', const ['residency_country_id', 'recidency_country_id'], _selectedResidencyCountryId);
        addResidency('recidency_state_id', const ['residency_state_id', 'recidency_state_id'], _selectedResidencyStateId);
        addResidency('recidency_city_id', const ['residency_city_id', 'recidency_city_id'], _selectedResidencyCityId);
        addResidency('immigration_status', const ['immigration_status'], _immigrationStatusCtrl.text);
        if (residencyPayload.isNotEmpty) {
          requests.add(ApiService.updateResidencyInfo(residencyPayload, lang: lang));
        }

        final spiritualPayload = <String, dynamic>{};
        if (_shouldSendProfileField('spiritual_background', const ['religion_id', 'member_religion_id'], _selectedReligionId)) {
          spiritualPayload['member_religion_id'] = _selectedReligionId == null
              ? ''
              : _sanitizeReligionId(_selectedReligionId);
        }
        if (_shouldSendProfileField('spiritual_background', const ['family_value_id'], _selectedFamilyValueId)) {
          spiritualPayload['family_value_id'] = _selectedFamilyValueId ?? '';
        }
        if (spiritualPayload.isNotEmpty) {
          requests.add(ApiService.updateSpiritualBackground(spiritualPayload, lang: lang));
        }
      } else if (index == 2) {
        final lifestylePayload = <String, dynamic>{};
        void addLifestyle(String key, dynamic value, [List<String>? aliases]) {
          if (_shouldSendProfileField('lifestyle', aliases ?? [key], value)) {
            lifestylePayload[key] = value ?? '';
          }
        }
        addLifestyle('diet', _selectedDiet);
        addLifestyle('living_with', _sanitizeForApi(_selectedLivingWith));
        addLifestyle('smoke', _selectedSmoke == null ? null : _mapSmokeToBackend(_selectedSmoke));
        addLifestyle('drink', _selectedDrink);
        if (lifestylePayload.isNotEmpty) {
          requests.add(ApiService.updateLifeStyle(lifestylePayload, lang: lang));
        }

        final hobbiesPayload = <String, dynamic>{};
        void addHobby(String key, dynamic value) {
          if (_shouldSendProfileField('hobbies_interest', [key], value)) {
            hobbiesPayload[key] = value ?? '';
          }
        }
        addHobby('hobbies', _sanitizeForApi(_selectedHobby));
        addHobby('interests', _sanitizeForApi(_selectedInterest));
        addHobby('music', _sanitizeForApi(_selectedMusic));
        addHobby('books', _sanitizeForApi(_selectedBook));
        addHobby('movies', _sanitizeForApi(_selectedMovie));
        addHobby('tv_shows', _sanitizeForApi(_selectedTvShow));
        addHobby('sports', _sanitizeForApi(_selectedSport));
        addHobby('fitness_activities', _sanitizeForApi(_selectedFitness));
        addHobby('cuisines', _sanitizeForApi(_selectedCuisine));
        addHobby('dress_styles', _sanitizeForApi(_selectedDressStyle));
        if (hobbiesPayload.isNotEmpty) {
          requests.add(ApiService.updateHobbies(hobbiesPayload, lang: lang));
        }

        final attitudePayload = <String, dynamic>{};
        void addAttitude(String key, dynamic value) {
          if (_shouldSendProfileField('attitude_behavior', [key], value)) {
            attitudePayload[key] = value ?? '';
          }
        }
        addAttitude('affection', _sanitizeForApi(_selectedAffection));
        addAttitude('humor', _sanitizeForApi(_selectedHumor));
        addAttitude('religious_service', _sanitizeForApi(_selectedReligiousService));
        addAttitude('political_views', _sanitizeForApi(_selectedPoliticalView));
        if (attitudePayload.isNotEmpty) {
          requests.add(ApiService.updateAttitudeBehavior(attitudePayload, lang: lang));
        }

        final astroPayload = <String, dynamic>{};
        if (_shouldSendProfileField('astronomic_information', const ['sun_sign'], _selectedSunSign)) {
          astroPayload['sun_sign'] = _selectedSunSign ?? '';
        }
        if (astroPayload.isNotEmpty) {
          requests.add(ApiService.updateAstronomicInfo(astroPayload, lang: lang));
        }
      }

      if (allSuccess && requests.isEmpty) {
        lastMessage = _isArabic ? 'لا توجد تغييرات جديدة لحفظها' : 'There are no changes to save';
      } else if (requests.isNotEmpty) {
        final results = await Future.wait(requests, eagerError: false);
        for (final result in results) {
          if (ApiService.isSuccessfulResult(result)) {
            anySuccess = true;
          } else {
            allSuccess = false;
            if (lastMessage.isEmpty) {
              lastMessage = _extractErrorMessage(
                result,
                _isArabic ? 'فشل حفظ بعض البيانات' : 'Some profile changes could not be saved',
              );
            }
          }
        }

        // Basic-info is the first request in this section. Confirm the stored
        // photo URL from the server before discarding the locally picked file.
        if (index == 0 &&
            _pickedImageFile != null &&
            results.isNotEmpty &&
            ApiService.isSuccessfulResult(results.first)) {
          final refreshedBasic = await ApiService.getBasicInfo(lang: lang);
          final refreshedRecord =
              ApiService.extractFirstResourceMap(refreshedBasic) ??
                  (refreshedBasic is Map<String, dynamic> ? refreshedBasic : null);
          final refreshedPhoto = refreshedRecord?['photo'] ?? refreshedRecord?['avatar'];
          final photoUrl = _fieldString(refreshedPhoto);
          if (mounted && photoUrl != null && photoUrl.isNotEmpty) {
            setState(() {
              _currentPhotoUrl = photoUrl;
              _pickedImageFile = null;
            });
          }
        }
        if (allSuccess) {
          lastMessage = _isArabic ? 'تمت عملية الحفظ بنجاح ✨' : 'Saved successfully ✨';
        }
      }
    } catch (e) {
      allSuccess = false;
      lastMessage = _isArabic
          ? 'حدث خطأ غير متوقع أثناء الحفظ'
          : 'Unexpected error during save';
      debugPrint('Edit profile save error: $e');
    }

    // عند نجاح جزء من الحفظ وفشل جزء آخر، لا نترك نسخة كاش قديمة توحي
    // بأن كل شيء تم أو لم يتم. نجبر الشاشة على جلب الحالة من الخادم لاحقاً.
    if (anySuccess) {
      await ProfileCacheService.clearCache();
    }

    if (!mounted) return;
    setState(() => _isSaving = false);
    _showNotification(
      lastMessage.isEmpty
          ? (_isArabic ? 'تعذر حفظ البيانات' : 'Unable to save profile data')
          : lastMessage,
      allSuccess,
    );
  }

  @override
  Widget build(BuildContext context) {
    const Color primarySkyBlue = Color(0xFF0288D1);
    final double completionRatio = _calculateProfileCompletion();

    return GestureDetector(
      onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
      child: Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
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
              child: Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 10),
                    child: Row(
                      children: [
                        IconButton(
                          icon: Icon(
                              _isArabic
                                  ? Icons.arrow_back_ios_new_rounded
                                  : Icons.arrow_back_rounded,
                              color: Colors.white,
                              size: 20),
                          onPressed: () => Navigator.pop(context, true),
                        ),
                        Expanded(
                          child: Text(
                            _isArabic ? 'تعديل الملف الشخصي' : 'Edit Profile',
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo'),
                          ),
                        ),
                        const SizedBox(width: 48),
                      ],
                    ),
                  ),
                  Padding(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(18)),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                _isArabic
                                    ? 'اكتمال الملف الشخصي ✨'
                                    : 'Profile Completion ✨',
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo'),
                              ),
                              Text(
                                '${(completionRatio * 100).toInt()}%',
                                style: const TextStyle(
                                    color: Colors.amberAccent,
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo'),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(10),
                            child: LinearProgressIndicator(
                              value: completionRatio,
                              minHeight: 6,
                              backgroundColor: Colors.white24,
                              valueColor: const AlwaysStoppedAnimation<Color>(
                                  Colors.amberAccent),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    margin:
                        const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(24),
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(
                              color: Colors.white.withValues(alpha: 0.3),
                              width: 1.1),
                        ),
                        child: TabBar(
                          controller: _tabController,
                          isScrollable: true,
                          tabAlignment: TabAlignment.start,
                          dividerColor: Colors.transparent,
                          indicatorPadding: const EdgeInsets.all(2),
                          indicator: BoxDecoration(
                            borderRadius: BorderRadius.circular(20),
                            color: Colors.white,
                            boxShadow: [
                              BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.12),
                                  blurRadius: 8,
                                  offset: const Offset(0, 3)),
                            ],
                          ),
                          labelColor: primarySkyBlue,
                          unselectedLabelColor:
                              Colors.white.withValues(alpha: 0.85),
                          labelStyle: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo'),
                          unselectedLabelStyle: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              fontFamily: 'Cairo'),
                          labelPadding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 4),
                          tabs: [
                            Tab(
                                child: Row(children: [
                              const Text('👤 ', style: TextStyle(fontSize: 15)),
                              Text(_isArabic
                                  ? 'البيانات الشخصية'
                                  : 'Personal Info')
                            ])),
                            Tab(
                                child: Row(children: [
                              const Text('🌍 ', style: TextStyle(fontSize: 15)),
                              Text(_isArabic
                                  ? 'الإقامة والدين'
                                  : 'Residency & Religion')
                            ])),
                            Tab(
                                child: Row(children: [
                              const Text('🎨 ', style: TextStyle(fontSize: 15)),
                              Text(_isArabic ? 'نمط الحياة' : 'Lifestyle')
                            ])),
                          ],
                        ),
                      ),
                    ),
                  ),
                  Expanded(
                    child: _isLoading
                        ? const Center(
                            child:
                                CircularProgressIndicator(color: Colors.white))
                        : TabBarView(
                            controller: _tabController,
                            children: [
                              Step1BasicPhysicalTab(
                                isAr: _isArabic,
                                firstNameCtrl: _firstNameCtrl,
                                lastNameCtrl: _lastNameCtrl,
                                phoneCtrl: _phoneCtrl,
                                dobCtrl: _dobCtrl,
                                childrenCtrl: _childrenCtrl,
                                introCtrl: _introCtrl,
                                institutionCtrl: _institutionCtrl,
                                designationCtrl: _designationCtrl,
                                companyCtrl: _companyCtrl,
                                selectedGender: _selectedGender,
                                currentPhotoUrl: _currentPhotoUrl,
                                pickedImageFile: _pickedImageFile,
                                selectedMaritalStatusId:
                                    _selectedMaritalStatusId,
                                selectedEducationDegree:
                                    _selectedEducationDegree,
                                selectedHeight: _selectedHeight,
                                selectedWeight: _selectedWeight,
                                selectedEyeColor: _localizedOption(
                                    _selectedEyeColor, _eyeColors),
                                selectedHairColor: _localizedOption(
                                    _selectedHairColor, _hairColors),
                                selectedComplexion: _localizedOption(
                                    _selectedComplexion, _complexions),
                                eyeColorMap: _eyeColorMap,
                                hairColorMap: _hairColorMap,
                                complexionMap: _complexionMap,
                                onPickImage: _pickImage,
                                onSelectDateOfBirth: _selectDateOfBirth,
                                onSelectMaritalStatus: () {
                                  final items = _maritalStatuses.isNotEmpty
                                      ? _maritalStatuses.map((ms) {
                                          if (ms is Map) {
                                            final id = ms['id'] ??
                                                ms['marital_status_id'] ??
                                                ms['maritial_status_id'] ??
                                                ms['value'] ??
                                                '1';
                                            final name = ms['name'] ??
                                                ms['title'] ??
                                                ms['label'] ??
                                                '';
                                            return {
                                              'id': id.toString(),
                                              'name': name.toString().isNotEmpty
                                                  ? name.toString()
                                                  : id.toString()
                                            };
                                          }
                                          return {
                                            'id': ms.toString(),
                                            'name': ms.toString()
                                          };
                                        }).toList()
                                      : [
                                          {
                                            'id': '1',
                                            'name': _isArabic
                                                ? 'أعزب / عزباء 💍'
                                                : 'Single 💍'
                                          },
                                          {
                                            'id': '2',
                                            'name': _isArabic
                                                ? 'متزوج / متزوجة 💍'
                                                : 'Married 💍'
                                          },
                                          {
                                            'id': '3',
                                            'name': _isArabic
                                                ? 'مطلق / مطلقة 💍'
                                                : 'Divorced 💍'
                                          },
                                          {
                                            'id': '4',
                                            'name': _isArabic
                                                ? 'أرمل / أرملة 💍'
                                                : 'Widowed 💍'
                                          },
                                        ];
                                  _showCustomBottomSheetPicker(
                                      title: _isArabic
                                          ? 'الحالة الاجتماعية 💍'
                                          : 'Marital Status 💍',
                                      items: items,
                                      currentValue: _selectedMaritalStatusId,
                                      isAr: _isArabic,
                                      onSelected: (val) => setState(() =>
                                          _selectedMaritalStatusId = val));
                                },
                                onSelectEducationDegree: () {
                                  final items = _educationDegreeOptions
                                      .map((d) => {'id': d, 'name': d})
                                      .toList();
                                  _showCustomBottomSheetPicker(
                                      title: _isArabic
                                          ? 'المؤهل العلمي 🎓'
                                          : 'Education Degree 🎓',
                                      items: items,
                                      currentValue: _selectedEducationDegree,
                                      isAr: _isArabic,
                                      onSelected: (val) => setState(() =>
                                          _selectedEducationDegree = val));
                                },
                                onHeightChanged: (val) =>
                                    setState(() => _selectedHeight = val),
                                onWeightChanged: (val) =>
                                    setState(() => _selectedWeight = val),
                                onSelectEyeColor: () {
                                  final items = _eyeColors
                                      .map((e) => {
                                            'id': e['key'],
                                            'name':
                                                _isArabic ? e['ar'] : e['en'],
                                            'color': e['color']
                                          })
                                      .toList();
                                  _showCustomBottomSheetPicker(
                                      title: _isArabic
                                          ? 'اختر لون العينين 👁️'
                                          : 'Select Eye Color 👁️',
                                      items: items,
                                      currentValue: _selectedEyeColor,
                                      isAr: _isArabic,
                                      onSelected: (val) => setState(
                                          () => _selectedEyeColor = val));
                                },
                                onSelectHairColor: () {
                                  final items = _hairColors
                                      .map((e) => {
                                            'id': e['key'],
                                            'name':
                                                _isArabic ? e['ar'] : e['en'],
                                            'color': e['color']
                                          })
                                      .toList();
                                  _showCustomBottomSheetPicker(
                                      title: _isArabic
                                          ? 'اختر لون الشعر 💈'
                                          : 'Select Hair Color 💈',
                                      items: items,
                                      currentValue: _selectedHairColor,
                                      isAr: _isArabic,
                                      onSelected: (val) => setState(
                                          () => _selectedHairColor = val));
                                },
                                onSelectComplexion: () {
                                  final items = _complexions
                                      .map((e) => {
                                            'id': e['key'],
                                            'name':
                                                _isArabic ? e['ar'] : e['en'],
                                            'color': e['color']
                                          })
                                      .toList();
                                  _showCustomBottomSheetPicker(
                                      title: _isArabic
                                          ? 'اختر لون البشرة 🎨'
                                          : 'Select Complexion 🎨',
                                      items: items,
                                      currentValue: _selectedComplexion,
                                      isAr: _isArabic,
                                      onSelected: (val) => setState(
                                          () => _selectedComplexion = val));
                                },
                              ),
                              Step2ResidencySpiritualTab(
                                isAr: _isArabic,
                                selectedBirthCountryId: _selectedBirthCountryId,
                                selectedResidencyCountryId:
                                    _selectedResidencyCountryId,
                                selectedResidencyStateId:
                                    _selectedResidencyStateId,
                                selectedResidencyCityId:
                                    _selectedResidencyCityId,
                                selectedReligionId: _selectedReligionId,
                                selectedFamilyValueId: _selectedFamilyValueId,
                                immigrationStatusCtrl: _immigrationStatusCtrl,
                                countries: _countries,
                                residencyStates: _residencyStates,
                                residencyCities: _residencyCities,
                                familyValues: _familyValues,
                                isLoadingResidencyStates:
                                    _isLoadingResidencyStates,
                                isLoadingResidencyCities:
                                    _isLoadingResidencyCities,
                                onSelectBirthCountry: () =>
                                    _showCountryPickerBottomSheet(_isArabic,
                                        isBirthCountry: true),
                                onSelectResidencyCountry: () =>
                                    _showCountryPickerBottomSheet(_isArabic,
                                        isBirthCountry: false),
                                onSelectState: () {
                                  final items = _residencyStates
                                      .map((st) => {
                                            'id': (st as Map)['id'].toString(),
                                            'name': _getLocalizedCityName(
                                                st, _isArabic)
                                          })
                                      .toList();
                                  _showCustomBottomSheetPicker(
                                      title: _isArabic
                                          ? 'المحافظة 🏛️'
                                          : 'Governorate / State 🏛️',
                                      items: items,
                                      currentValue: _selectedResidencyStateId,
                                      isAr: _isArabic,
                                      enableSearch: true,
                                      onSelected: (val) {
                                        setState(() =>
                                            _selectedResidencyStateId = val);
                                        _loadResidencyCities(val);
                                      });
                                },
                                onSelectCity: () {
                                  final items = _residencyCities
                                      .map((ct) => {
                                            'id': (ct as Map)['id'].toString(),
                                            'name': _getLocalizedCityName(
                                                ct, _isArabic)
                                          })
                                      .toList();
                                  _showCustomBottomSheetPicker(
                                      title:
                                          _isArabic ? 'المدينة 🏙' : 'City 🏙️',
                                      items: items,
                                      currentValue: _selectedResidencyCityId,
                                      isAr: _isArabic,
                                      enableSearch: true,
                                      onSelected: (val) => setState(() =>
                                          _selectedResidencyCityId = val));
                                },
                                onSelectReligion: () {
                                  final items = _religions.isNotEmpty
                                      ? _religions
                                          .map((r) => {
                                                'id':
                                                    (r as Map)['id'].toString(),
                                                'name':
                                                    (r['name'] ?? '').toString()
                                              })
                                          .toList()
                                      : [
                                          {
                                            'id': '1',
                                            'name': _isArabic
                                                ? 'إسلام ☪️'
                                                : 'Islam ☪️'
                                          },
                                          {
                                            'id': '2',
                                            'name': _isArabic
                                                ? 'مسيحية ✝️'
                                                : 'Christianity ✝️'
                                          },
                                        ];
                                  _showCustomBottomSheetPicker(
                                      title: _isArabic
                                          ? 'الديانة *'
                                          : 'Religion *',
                                      items: items,
                                      currentValue: _selectedReligionId,
                                      isAr: _isArabic,
                                      onSelected: (val) => setState(
                                          () => _selectedReligionId = val));
                                },
                                onSelectFamilyValue: () {
                                  final items = _familyValues
                                      .map((fv) => {
                                            'id': (fv as Map)['id'].toString(),
                                            'name':
                                                (fv['name'] ?? '').toString()
                                          })
                                      .toList();
                                  _showCustomBottomSheetPicker(
                                      title: _isArabic
                                          ? 'القيم العائلية'
                                          : 'Family Values',
                                      items: items,
                                      currentValue: _selectedFamilyValueId,
                                      isAr: _isArabic,
                                      enableSearch: true,
                                      onSelected: (val) => setState(
                                          () => _selectedFamilyValueId = val));
                                },
                                onImmigrationStatusChanged: (val) => setState(
                                    () => _immigrationStatusCtrl.text = val),
                                getLocalizedCityName: _getLocalizedCityName,
                                getCountryFlagUrl: _getCountryFlagUrl,
                              ),
                              Step3LifestyleHobbiesTab(
                                isAr: _isArabic,
                                selectedSunSign: _selectedSunSign,
                                selectedLivingWith: _selectedLivingWith,
                                selectedSmoke: _selectedSmoke,
                                selectedHobby: _selectedHobby,
                                selectedInterest: _selectedInterest,
                                selectedMusic: _selectedMusic,
                                selectedBook: _selectedBook,
                                selectedMovie: _selectedMovie,
                                selectedSport: _selectedSport,
                                selectedDressStyle: _selectedDressStyle,
                                selectedAffection: _selectedAffection,
                                selectedHumor: _selectedHumor,
                                sunSignsKeys: _sunSignsKeys,
                                livingWithOptions: _livingWithOptions,
                                hobbiesOptions: _hobbiesOptions,
                                interestsOptions: _interestsOptions,
                                musicOptions: _musicOptions,
                                booksOptions: _booksOptions,
                                moviesOptions: _moviesOptions,
                                sportsOptions: _sportsOptions,
                                dressStylesOptions: _dressStylesOptions,
                                affectionOptions: _affectionOptions,
                                humorOptions: _humorOptions,
                                onSelectSunSign: (val) =>
                                    setState(() => _selectedSunSign = val),
                                onSelectLivingWith: (val) =>
                                    setState(() => _selectedLivingWith = val),
                                onSelectSmoke: (val) =>
                                    setState(() => _selectedSmoke = val),
                                onSelectHobby: (val) =>
                                    setState(() => _selectedHobby = val),
                                onSelectInterest: (val) =>
                                    setState(() => _selectedInterest = val),
                                onSelectMusic: (val) =>
                                    setState(() => _selectedMusic = val),
                                onSelectBook: (val) =>
                                    setState(() => _selectedBook = val),
                                onSelectMovie: (val) =>
                                    setState(() => _selectedMovie = val),
                                onSelectSport: (val) =>
                                    setState(() => _selectedSport = val),
                                onSelectDressStyle: (val) =>
                                    setState(() => _selectedDressStyle = val),
                                onSelectAffection: (val) =>
                                    setState(() => _selectedAffection = val),
                                onSelectHumor: (val) =>
                                    setState(() => _selectedHumor = val),
                              ),
                            ],
                          ),
                  ),
                ],
              ),
            ),
          ),
          floatingActionButtonLocation:
              FloatingActionButtonLocation.centerFloat,
          floatingActionButton: SafeArea(
            child: Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Container(
                margin: const EdgeInsets.symmetric(horizontal: 24),
                height: 52,
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isSaving ? null : _saveCurrentSection,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: primarySkyBlue,
                    elevation: 10,
                    shadowColor: Colors.black45,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30),
                    ),
                  ),
                  child: _isSaving
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                              color: primarySkyBlue, strokeWidth: 2.5))
                      : Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.save_rounded,
                                color: primarySkyBlue, size: 22),
                            const SizedBox(width: 8),
                            Text(
                              _isArabic ? 'حفظ هذا القسم' : 'Save Section',
                              style: const TextStyle(
                                  color: primarySkyBlue,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16.5,
                                  fontFamily: 'Cairo'),
                            ),
                          ],
                        ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

Map<String, dynamic> parseProfileData(Map<String, dynamic> jsonResponse) {
  return jsonResponse;
}

// ============================================================================
// اسم الملف: lib/screens/signup_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: الشاشة الرئيسية لعملية التسجيل متعددة الخطوات (4 خطوات)
//        مع دعم استقبال بيانات Google التلقائية وإشتراط رفع الصورة الشخصية.
// ============================================================================

import 'dart:convert';
import 'dart:io';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../services/profile_cache_service.dart';
import '../widgets/app_snackbar.dart';
import 'main_home_screen.dart';

import 'widgets/step1_basic_info.dart';
import 'widgets/step2_personal_details.dart';
import 'widgets/step3_residency_religion.dart';
import 'widgets/step4_partner_expectations.dart';

class _SignupStepSaveException implements Exception {
  final String message;
  const _SignupStepSaveException(this.message);

  @override
  String toString() => message;
}

class SignupScreen extends StatefulWidget {
  final String currentLanguage;
  final String? initialEmail;
  final String? initialName;

  const SignupScreen({
    super.key,
    this.currentLanguage = 'ar',
    this.initialEmail,
    this.initialName,
  });

  @override
  State<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen> {
  // متحكم التنقل بين الصفحات
  final PageController _pageController = PageController();
  int _currentStep = 0;
  bool _isLoading = false;
  bool _isAccountCreated = false;
  bool _isSignupRequestInFlight = false;

  String? _educationSavedFingerprint;
  String? _careerSavedFingerprint;
  int? _educationRecordId;
  int? _careerRecordId;

  // مفاتيح التحقق من صحة حقول النماذج لكل خطوة
  final _step1Key = GlobalKey<FormState>();
  final _step2Key = GlobalKey<FormState>();
  final _step3Key = GlobalKey<FormState>();
  final _step4Key = GlobalKey<FormState>();

  // 1️⃣ متغيّرات ومتحكمات الخطوة الأولى (المعلومات الأساسية)
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _dobController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  String _gender = '';
  String _onBehalf = '';
  bool _agreedToTerms = false;
  File? _profilePhoto; // متغير الصورة الشخصية الإجبارية

  // 2️⃣ متغيّرات ومتحكمات الخطوة الثانية (التفاصيل الشخصية)
  String? _educationDegree;
  final _institutionController = TextEditingController();
  final _designationController = TextEditingController();
  final _companyController = TextEditingController();
  String? _personalDrink;
  String? _personalSunSign;
  String? _personalAffection;
  String? _personalHumor;
  double _height = 140.0;
  double _weight = 40.0;
  bool _heightWasSelected = false;
  bool _weightWasSelected = false;
  String _eyeColor = '';
  String _hairColor = '';
  String _complexion = '';
  String _bodyType = '';
  String _bodyArt = '';
  bool _eyeColorWasSelected = false;
  bool _hairColorWasSelected = false;
  bool _complexionWasSelected = false;
  bool _bodyTypeWasSelected = false;
  bool _bodyArtWasSelected = false;

  // 3️⃣ متغيّرات الخطوة الثالثة (الإقامة والدين)
  String? _birthCountryId;
  String? _residenceCountryId;
  String? _stateId;
  String? _cityId;
  String? _religionId;
  String? _maritalStatusId;
  final _childrenCountController = TextEditingController();
  bool get _requiresChildrenCount => const <String>{'2', '3', '4'}.contains(_maritalStatusId);

  // 4️⃣ متغيّرات الخطوة الرابعة (توقعات الشريك)
  final _partnerGeneralController = TextEditingController();
  final _partnerProfessionController = TextEditingController();
  RangeValues _partnerAgeRange = const RangeValues(22, 45);
  RangeValues _partnerHeightRange = const RangeValues(155, 180);
  RangeValues _partnerWeightRange = const RangeValues(50, 80);
  bool _partnerAgeWasSelected = false;
  bool _partnerHeightWasSelected = false;
  bool _partnerWeightWasSelected = false;
  String? _partnerEducation;
  String? _partnerComplexion;
  String? _partnerEyeColor;
  String? _partnerHairColor;
  String? _partnerLanguageId;
  String? _partnerReligionId;
  String? _partnerCountryId;
  String? _partnerStateId;
  List<dynamic> _partnerReligions = [];
  List<dynamic> _partnerCountries = [];
  List<dynamic> _partnerStates = [];
  String _partnerChildrenAcceptable = '';
  String _partnerSmokingAcceptable = '';
  String _partnerDrinkingAcceptable = '';
  List<dynamic> _partnerLanguages = [];
  bool _isLoadingPartnerLanguages = true;

  @override
  void initState() {
    super.initState();
    _initializeFieldsFromGoogle();
  }

  Future<void> _loadPartnerOptions() async {
    if (mounted) setState(() => _isLoadingPartnerLanguages = true);
    try {
      final results = await Future.wait([
        ApiService.get('/member/languages', lang: widget.currentLanguage),
        ApiService.getProfileDropdowns(lang: widget.currentLanguage),
      ]);
      if (!mounted) return;
      final languageRaw = results[0];
      final dropdownRaw = results[1];
      final languageResponse = languageRaw is Map ? languageRaw : null;
      final dropdowns = dropdownRaw is Map ? dropdownRaw : null;
      final nestedLanguageData = languageResponse?['data'];
      final rawLanguages =
          languageResponse?['languages'] ?? nestedLanguageData?['languages'];
      final loadedLanguages = rawLanguages is List
          ? rawLanguages.whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .where((item) => item['id'] != null &&
                  (item['name'] ?? '').toString().trim().isNotEmpty)
              .toList()
          : <Map<String, dynamic>>[];
      setState(() {
        _partnerLanguages = loadedLanguages;
        _partnerReligions = dropdowns?['religions'] is List ? dropdowns!['religions'] : [];
        _partnerCountries = dropdowns?['countries'] is List ? dropdowns!['countries'] :
            (dropdowns?['country'] is List ? dropdowns!['country'] : []);
        _isLoadingPartnerLanguages = false;
      });
      if (loadedLanguages.isEmpty) debugPrint('⚠️ [Signup] partner-languages endpoint returned an empty list');
    } catch (e) {
      debugPrint('Partner options could not be loaded: $e');
      if (mounted) setState(() => _isLoadingPartnerLanguages = false);
    }
  }

  Future<void> _loadPartnerStates(String? countryId) async {
    final id = int.tryParse(countryId ?? '');
    if (id == null || id <= 0) {
      if (mounted) setState(() { _partnerStates = []; _partnerStateId = null; });
      return;
    }
    try {
      final states = await ApiService.getStatesByCountry(id, lang: widget.currentLanguage);
      if (!mounted) return;
      setState(() { _partnerStates = states; _partnerStateId = null; });
    } catch (e) {
      debugPrint('Partner governorates could not be loaded: $e');
      if (mounted) setState(() => _partnerStates = []);
    }
  }

  /// 🟢 دالة ملء بيانات الاسم والبريد تلقائياً إذا كانت قادمة من Google Sign-In
  void _initializeFieldsFromGoogle() {
    if (widget.initialEmail != null && widget.initialEmail!.trim().isNotEmpty) {
      _emailController.text = widget.initialEmail!.trim();
    }

    if (widget.initialName != null && widget.initialName!.trim().isNotEmpty) {
      final String fullName = widget.initialName!.trim();
      final List<String> nameParts = fullName.split(RegExp(r'\s+'));

      if (nameParts.isNotEmpty) {
        _firstNameController.text = nameParts.first;
        if (nameParts.length > 1) {
          _lastNameController.text = nameParts.sublist(1).join(' ');
        }
      }
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    _firstNameController.dispose();
    _lastNameController.dispose();
    _dobController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _institutionController.dispose();
    _designationController.dispose();
    _companyController.dispose();
    _childrenCountController.dispose();
    _partnerGeneralController.dispose();
    _partnerProfessionController.dispose();
    super.dispose();
  }

  Future<Map<String, dynamic>> _requireSignupSave(
    Future<Map<String, dynamic>> request,
    String fallbackMessage,
  ) async {
    final result = await request;
    if (result['success'] != true) {
      final message = result['message']?.toString().trim();
      throw _SignupStepSaveException(
        message != null && message.isNotEmpty ? message : fallbackMessage,
      );
    }
    return result;
  }

  int? _extractRecordId(dynamic value, Set<String> idKeys) {
    if (value is Map) {
      for (final key in idKeys) {
        final rawId = value[key];
        final parsedId = rawId is int
            ? rawId
            : int.tryParse(rawId?.toString() ?? '');
        if (parsedId != null && parsedId > 0) return parsedId;
      }
      for (final key in const ['data', 'education', 'career', 'record']) {
        final found = _extractRecordId(value[key], idKeys);
        if (found != null) return found;
      }
    } else if (value is List && value.isNotEmpty) {
      return _extractRecordId(value.first, idKeys);
    }
    return null;
  }

  String _stepErrorMessage(Object error, String fallback) {
    return error is _SignupStepSaveException ? error.message : fallback;
  }

  /// 🔄 معالجة الانتقال والتثبت من صحة البيانات في كل خطوة
  Future<void> _handleNextStep() async {
    FocusScope.of(context).unfocus();
    final bool isAr = widget.currentLanguage == 'ar';

    // ------------------------------------------------------------------------
    // 🟢 الخطوة الأولى: إنشاء الحساب ورفع الصورة الشخصية
    // ------------------------------------------------------------------------
    if (_currentStep == 0) {
      final profilePhoto = _profilePhoto;
      if (profilePhoto == null || !await profilePhoto.exists()) {
        _showNotification(
          isAr
              ? 'يرجى اختيار صورة شخصية موجودة لإكمال إنشاء الحساب'
              : 'Please select an existing profile photo',
          NotificationType.warning,
        );
        return;
      }
      final photoSize = await profilePhoto.length();
      if (photoSize <= 0 || photoSize > 10 * 1024 * 1024) {
        _showNotification(
          isAr
              ? 'يجب أن يكون حجم الصورة أكبر من صفر وألا يتجاوز 10 ميغابايت'
              : 'The profile photo must be larger than zero and no more than 10 MB',
          NotificationType.warning,
        );
        return;
      }

      if (!_step1Key.currentState!.validate()) return;

      if (_gender.isEmpty || _onBehalf.isEmpty) {
        _showNotification(
          isAr
              ? 'يرجى تحديد الجنس ولمن يتم إنشاء الحساب'
              : 'Please select gender and who this profile is for',
          NotificationType.warning,
        );
        return;
      }

      if (_dobController.text.isEmpty) {
        _showNotification(
          isAr
              ? 'يرجى تحديد تاريخ الميلاد بالكامل'
              : 'Please select your date of birth',
          NotificationType.warning,
        );
        return;
      }

      if (!_agreedToTerms) {
        _showNotification(
          isAr
              ? 'يجب الموافقة على الشروط والأحكام'
              : 'Terms agreement is required',
          NotificationType.warning,
        );
        return;
      }

      if (!_isAccountCreated) {
        // Prevent multiple multipart signup requests when the user taps
        // the create-account button repeatedly before the first response.
        if (_isSignupRequestInFlight) return;
        _isSignupRequestInFlight = true;
        setState(() => _isLoading = true);
        try {
          await ApiService.clearToken();

          final result = await ApiService.signup(
            firstName: _firstNameController.text.trim(),
            lastName: _lastNameController.text.trim(),
            gender: _gender,
            dateOfBirth: _dobController.text.trim(),
            email: _emailController.text.trim(),
            password: _passwordController.text.trim(),
            passwordConfirmation: _confirmPasswordController.text.trim(),
            onBehalf: _onBehalf,
            photoFile: profilePhoto,
          );

          if (!mounted) return;

          if (result['success'] == true) {
            final token = await ApiService.getToken();
            if (token == null || token.isEmpty) {
              _showNotification(
                isAr
                    ? 'تم إنشاء الحساب لكن تعذر حفظ جلسة الدخول. يرجى تسجيل الدخول مجدداً'
                    : 'The account was created, but the login session could not be saved. Please sign in again.',
                NotificationType.error,
              );
              return;
            }
            _isAccountCreated = true;

            // ربط رمز الإشعارات عبر الخدمة المركزية بعد حفظ جلسة الحساب.
            try {
              await NotificationService.syncCurrentToken(force: true);
            } catch (e) {
              debugPrint('⚠️ تعذرت مزامنة رمز الإشعارات بعد إنشاء الحساب: $e');
            }

            _goToNextPage();
          } else {
            final String errorMsg = result['message'] != null
                ? result['message'].toString()
                : (isAr ? 'فشلت عملية إنشاء الحساب' : 'Signup failed');
            _showNotification(errorMsg, NotificationType.error);
          }
        } catch (e) {
          if (mounted) {
            _showNotification(
              isAr
                  ? 'حدث خطأ في الاتصال بالسيرفر، يرجى المحاولة لاحقاً'
                  : 'Network error, please try again later',
              NotificationType.error,
            );
          }
        } finally {
          _isSignupRequestInFlight = false;
          if (mounted) setState(() => _isLoading = false);
        }
      } else {
        _goToNextPage();
      }
    }
    // ------------------------------------------------------------------------
    // 🟡 الخطوة الثانية: التفاصيل الجسدية والتعليم والعمل
    // ------------------------------------------------------------------------
    else if (_currentStep == 1) {
      if (!_step2Key.currentState!.validate()) return;
      if (_educationDegree == null || _educationDegree!.trim().isEmpty) {
        _showNotification(
          isAr ? 'يرجى تحديد المستوى التعليمي' : 'Please select your education level',
          NotificationType.warning,
        );
        return;
      }
      if (!_heightWasSelected ||
          !_weightWasSelected ||
          !_eyeColorWasSelected ||
          !_hairColorWasSelected ||
          !_complexionWasSelected ||
          !_bodyTypeWasSelected ||
          !_bodyArtWasSelected) {
        _showNotification(
          isAr
              ? 'يرجى تحديد الطول والوزن ولون العينين والشعر والبشرة وبنية الجسد والوشم'
              : 'Please select height, weight, eye color, hair color, complexion, body type, and body art',
          NotificationType.warning,
        );
        return;
      }

      setState(() => _isLoading = true);
      try {
        final physicalPayload = <String, dynamic>{
          'height': (_height / 100).toStringAsFixed(2),
          'weight': _weight.round().toString(),
          'eye_color': _eyeColor,
          'hair_color': _hairColor,
          'complexion': _complexion,
          'body_type': _bodyType,
          'body_art': _bodyArt,
        };
        await _requireSignupSave(
          ApiService.updatePhysicalAttributes(
            physicalPayload,
            lang: widget.currentLanguage,
          ),
          isAr ? 'تعذر حفظ المواصفات الجسدية' : 'Failed to save physical details',
        );

        // Confirm the body-type and body-art values were actually persisted.
        final savedPhysicalResponse =
            await ApiService.getPhysicalAttributes(lang: widget.currentLanguage);
        final savedPhysical =
            ApiService.extractFirstResourceMap(savedPhysicalResponse);
        final savedBodyType =
            savedPhysical?['raw_body_type'] ?? savedPhysical?['body_type'];
        final savedBodyArt =
            savedPhysical?['raw_body_art'] ?? savedPhysical?['body_art'];
        if (!_hasPersistedValue(savedBodyType) ||
            !_hasPersistedValue(savedBodyArt)) {
          throw _SignupStepSaveException(
            isAr
                ? 'لم يؤكد الخادم حفظ بنية الجسم والوشم. أوقفنا التسجيل حتى لا تضيع هذه البيانات.'
                : 'The server did not confirm saving body type and body art. Signup was paused to avoid losing these details.',
          );
        }

        // Read the current user's rows first. This makes retries safe even after
        // the app is closed or the previous create response was interrupted.
        final educationResponse =
            await ApiService.getEducation(lang: widget.currentLanguage);
        if (educationResponse == null || educationResponse['result'] == false) {
          throw _SignupStepSaveException(
            isAr
                ? 'تعذر التحقق من السجل التعليمي الحالي. لم نرسل طلب إنشاء جديد لتجنب التكرار.'
                : 'Could not verify existing education records. No create request was sent to avoid duplicates.',
          );
        }
        _educationRecordId ??= _extractRecordId(
          educationResponse,
          const {'id', 'education_id'},
        );

        final educationPayload = <String, dynamic>{
          'degree': _educationDegree!,
          'institution': _institutionController.text.trim(),
        };
        final educationFingerprint = jsonEncode(educationPayload);
        if (_educationSavedFingerprint != educationFingerprint) {
          if (_educationSavedFingerprint != null && _educationRecordId == null) {
            throw _SignupStepSaveException(
              isAr
                  ? 'تم حفظ التعليم سابقاً، لكن الخادم لم يُرجع معرّف السجل اللازم لتعديله. لم نكرر الإرسال حتى لا ينشأ سجل مكرر.'
                  : 'Education was saved already, but the server did not return its record ID. It was not resent to avoid a duplicate row.',
            );
          }
          final educationRequest = <String, dynamic>{
            ...educationPayload,
            if (_educationRecordId != null) 'id': _educationRecordId,
          };
          final result = await _requireSignupSave(
            ApiService.updateEducation(
              educationRequest,
              lang: widget.currentLanguage,
            ),
            isAr ? 'تعذر حفظ البيانات التعليمية' : 'Failed to save education details',
          );
          _educationRecordId ??=
              _extractRecordId(result, const {'id', 'education_id'});
          _educationSavedFingerprint = educationFingerprint;
        }

        final careerResponse =
            await ApiService.getCareer(lang: widget.currentLanguage);
        if (careerResponse == null || careerResponse['result'] == false) {
          throw _SignupStepSaveException(
            isAr
                ? 'تعذر التحقق من سجل العمل الحالي. لم نرسل طلب إنشاء جديد لتجنب التكرار.'
                : 'Could not verify existing career records. No create request was sent to avoid duplicates.',
          );
        }
        _careerRecordId ??= _extractRecordId(
          careerResponse,
          const {'id', 'career_id'},
        );

        final careerPayload = <String, dynamic>{
          'designation': _designationController.text.trim(),
          if (_companyController.text.trim().isNotEmpty)
            'company': _companyController.text.trim(),
        };
        final careerFingerprint = jsonEncode(careerPayload);
        if (_careerSavedFingerprint != careerFingerprint) {
          if (_careerSavedFingerprint != null && _careerRecordId == null) {
            throw _SignupStepSaveException(
              isAr
                  ? 'تم حفظ العمل سابقاً، لكن الخادم لم يُرجع معرّف السجل اللازم لتعديله. لم نكرر الإرسال حتى لا ينشأ سجل مكرر.'
                  : 'Career was saved already, but the server did not return its record ID. It was not resent to avoid a duplicate row.',
            );
          }
          final careerRequest = <String, dynamic>{
            ...careerPayload,
            if (_careerRecordId != null) 'id': _careerRecordId,
          };
          final result = await _requireSignupSave(
            ApiService.updateCareer(
              careerRequest,
              lang: widget.currentLanguage,
            ),
            isAr ? 'تعذر حفظ بيانات العمل' : 'Failed to save career details',
          );
          _careerRecordId ??=
              _extractRecordId(result, const {'id', 'career_id'});
          _careerSavedFingerprint = careerFingerprint;
        }

        if (_personalDrink != null && _personalDrink!.isNotEmpty) {
          await _requireSignupSave(
            ApiService.updateLifeStyle({'drink': _personalDrink}, lang: widget.currentLanguage),
            isAr ? 'تعذر حفظ تفضيل المشروبات' : 'Failed saving drink preference',
          );
        }
        final attitudePayload = <String, dynamic>{
          if (_personalAffection != null && _personalAffection!.isNotEmpty) 'affection': _personalAffection,
          if (_personalHumor != null && _personalHumor!.isNotEmpty) 'humor': _personalHumor,
        };
        if (attitudePayload.isNotEmpty) {
          await _requireSignupSave(
            ApiService.updateAttitudeBehavior(attitudePayload, lang: widget.currentLanguage),
            isAr ? 'تعذر حفظ طريقة التعبير وحس الفكاهة' : 'Failed saving attitude preferences',
          );
        }
        if (_personalSunSign != null && _personalSunSign!.isNotEmpty) {
          await _requireSignupSave(
            ApiService.updateAstronomicInfo({'sun_sign': _personalSunSign}, lang: widget.currentLanguage),
            isAr ? 'تعذر حفظ البرج الشمسي' : 'Failed saving zodiac sign',
          );
        }
        if (!mounted) return;
        _goToNextPage();
      } catch (e) {
        if (mounted) {
          _showNotification(
            _stepErrorMessage(
              e,
              isAr
                  ? 'تعذر حفظ بيانات الخطوة الثانية'
                  : 'Failed saving step two',
            ),
            NotificationType.error,
          );
        }
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    }
    // ------------------------------------------------------------------------
    // 🔵 الخطوة الثالثة: بيانات الإقامة والدين
    // ------------------------------------------------------------------------
    else if (_currentStep == 2) {
      if (_birthCountryId == null) {
        _showNotification(
          isAr ? 'يرجى تحديد بلد المنشأ' : 'Select Birth Country',
          NotificationType.warning,
        );
        return;
      }
      if (_residenceCountryId == null) {
        _showNotification(
          isAr ? 'يرجى تحديد بلد الإقامة' : 'Select Residence Country',
          NotificationType.warning,
        );
        return;
      }
      if (_stateId == null || _stateId!.isEmpty) {
        _showNotification(
          isAr ? 'يرجى تحديد المحافظة' : 'Select Governorate / State',
          NotificationType.warning,
        );
        return;
      }
      if (_cityId == null || _cityId!.isEmpty) {
        _showNotification(
          isAr ? 'يرجى تحديد المدينة' : 'Select City',
          NotificationType.warning,
        );
        return;
      }
      if (_maritalStatusId == null || _maritalStatusId!.isEmpty) {
        _showNotification(
          isAr ? 'يرجى تحديد الحالة الاجتماعية' : 'Select marital status',
          NotificationType.warning,
        );
        return;
      }
      if (_religionId == null || _religionId!.isEmpty) {
        _showNotification(
          isAr ? 'يرجى تحديد الديانة' : 'Select religion',
          NotificationType.warning,
        );
        return;
      }

      if (_requiresChildrenCount && _childrenCountController.text.trim().isEmpty) {
        _showNotification(isAr ? 'يرجى إدخال عدد الأطفال (اكتب 0 إذا لم يكن لديك أطفال)' : 'Enter the number of children (use 0 if none)', NotificationType.warning);
        return;
      }

      setState(() => _isLoading = true);
      try {
        await _requireSignupSave(
          ApiService.updateResidencyInfo({
            'birth_country_id': _birthCountryId!,
            'recidency_country_id': _residenceCountryId!,
            'recidency_state_id': _stateId!,
            'recidency_city_id': _cityId!,
          }, lang: widget.currentLanguage),
          isAr ? 'تعذر حفظ بيانات الإقامة' : 'Failed to save residency details',
        );

        final spiritualPayload = <String, dynamic>{
          'member_religion_id': _religionId!,
          'marital_status': _maritalStatusId!,
        };
        await _requireSignupSave(
          ApiService.updateSpiritualBackground(
            spiritualPayload,
            lang: widget.currentLanguage,
          ),
          isAr ? 'تعذر حفظ البيانات الدينية' : 'Failed to save religious details',
        );

        if (_requiresChildrenCount) {
          final childCount = int.tryParse(_childrenCountController.text.trim());
          if (childCount == null || childCount < 0) {
            throw const _SignupStepSaveException('عدد الأطفال يجب أن يكون رقماً صحيحاً غير سالب.');
          }
          await _requireSignupSave(
            ApiService.updateChildrenCount(childCount, lang: widget.currentLanguage),
            isAr ? 'تعذر حفظ عدد الأطفال' : 'Failed to save children count',
          );
        }

        // Profile-dropdown endpoints require an authenticated member account.
        // Load the mandatory partner language options only after signup/login.
        await _loadPartnerOptions();

        if (!mounted) return;
        _goToNextPage();
      } catch (e) {
        if (mounted) {
          _showNotification(
            _stepErrorMessage(
              e,
              isAr
                  ? 'تعذر حفظ بيانات الإقامة والدين'
                  : 'Failed saving residency and religion details',
            ),
            NotificationType.error,
          );
        }
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    }
    // ------------------------------------------------------------------------
    // 🟣 الخطوة الرابعة: حفظ توقعات الشريك وإنهاء الحساب
    // ------------------------------------------------------------------------
    else if (_currentStep == 3) {
      if (!_step4Key.currentState!.validate()) return;
      if (!_partnerHeightWasSelected ||
          !_partnerWeightWasSelected ||
          _partnerEducation == null ||
          _partnerComplexion == null ||
          _partnerEyeColor == null ||
          _partnerHairColor == null ||
          _partnerLanguageId == null ||
          _partnerChildrenAcceptable.isEmpty ||
          _partnerSmokingAcceptable.isEmpty ||
          _partnerDrinkingAcceptable.isEmpty) {
        _showNotification(
          isAr
              ? 'يرجى اختيار جميع تفضيلات الشريك، بما فيها نطاقات الطول والوزن والألوان واللغة'
              : 'Please complete all partner preferences, including height, weight, colors, and language',
          NotificationType.warning,
        );
        return;
      }

      setState(() => _isLoading = true);
      try {
        final partnerPayload = <String, dynamic>{
          if (_partnerGeneralController.text.trim().isNotEmpty)
            'general': _partnerGeneralController.text.trim(),
          if (_partnerAgeWasSelected) ...{
            'min_age': _partnerAgeRange.start.round().toString(),
            'max_age': _partnerAgeRange.end.round().toString(),
          },
          if (_partnerHeightWasSelected) ...{
            'min_height':
                (_partnerHeightRange.start / 100).toStringAsFixed(2),
            'max_height':
                (_partnerHeightRange.end / 100).toStringAsFixed(2),
            // Legacy scalar retained for older matching code / app versions.
            'partner_height':
                (_partnerHeightRange.start / 100).toStringAsFixed(2),
          },
          if (_partnerWeightWasSelected) ...{
            'min_weight': _partnerWeightRange.start.toStringAsFixed(2),
            'max_weight': _partnerWeightRange.end.toStringAsFixed(2),
            // Legacy scalar retained for older matching code / app versions.
            'partner_weight': _partnerWeightRange.end.round().toString(),
          },
          if (_partnerEducation != null && _partnerEducation!.isNotEmpty) ...{
            'partner_education': _partnerEducation,
            'pertner_education': _partnerEducation,
          },
          if (_partnerProfessionController.text.trim().isNotEmpty)
            'partner_profession': _partnerProfessionController.text.trim(),
          'partner_children_acceptable': _partnerChildrenAcceptable,
          'smoking_acceptable': _partnerSmokingAcceptable,
          'drinking_acceptable': _partnerDrinkingAcceptable,
          if (_partnerComplexion != null && _partnerComplexion!.isNotEmpty) ...{
            'partner_complexion': _partnerComplexion,
            'pertner_complexion': _partnerComplexion,
          },
          'partner_eye_color': _partnerEyeColor,
          'partner_hair_color': _partnerHairColor,
          'language_id': _partnerLanguageId,
          if (_partnerReligionId != null) 'partner_religion_id': _partnerReligionId,
          if (_partnerCountryId != null) 'partner_country_id': _partnerCountryId,
          if (_partnerStateId != null) 'partner_state_id': _partnerStateId,
        };
        if (partnerPayload.isEmpty) {
          throw const _SignupStepSaveException(
            'يرجى تحديد تفضيل واحد على الأقل للشريك قبل إنهاء التسجيل.',
          );
        }
        await _requireSignupSave(
          ApiService.updatePartnerExpectations(
            partnerPayload,
            lang: widget.currentLanguage,
          ),
          isAr ? 'تعذر حفظ توقعات الشريك' : 'Failed to save partner expectations',
        );

        await ProfileCacheService.clearCache();

        if (!mounted) return;
        _showSuccessDialog();
      } catch (e) {
        if (mounted) {
          _showNotification(
            _stepErrorMessage(
              e,
              isAr ? 'تعذر حفظ توقعات الشريك' : 'Failed saving expectations',
            ),
            NotificationType.error,
          );
        }
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    }
  }

  void _goToNextPage() {
    if (_currentStep < 3) {
      setState(() => _currentStep++);
      _pageController.animateToPage(
        _currentStep,
        duration: const Duration(milliseconds: 350),
        curve: Curves.easeInOut,
      );
    }
  }

  void _goToPreviousPage() {
    if (_currentStep > 0) {
      setState(() => _currentStep--);
      _pageController.animateToPage(
        _currentStep,
        duration: const Duration(milliseconds: 350),
        curve: Curves.easeInOut,
      );
    } else {
      Navigator.pop(context);
    }
  }

  /// إظهار التنبيهات باستخدام AppSnackBar الزجاجي الفاخر
  void _showNotification(String message, NotificationType type) {
    AppSnackBar.show(
      context: context,
      type: type,
      isArabic: widget.currentLanguage == 'ar',
      messageAr: message,
      messageEn: message,
    );
  }

  /// نافذة منبثقة زجاجية فور إتمام الحساب بنجاح
  void _showSuccessDialog() {
    final bool isAr = widget.currentLanguage == 'ar';
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => Directionality(
        textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(28),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A).withValues(alpha: 0.95),
                  borderRadius: BorderRadius.circular(28),
                  border: Border.all(
                    color: const Color(0xFF38BDF8).withValues(alpha: 0.5),
                    width: 1.5,
                  ),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981).withValues(alpha: 0.15),
                        shape: BoxShape.circle,
                        border: Border.all(
                            color: const Color(0xFF10B981), width: 1.5),
                      ),
                      child: const Icon(
                        Icons.verified_rounded,
                        color: Color(0xFF10B981),
                        size: 48,
                      ),
                    ),
                    const SizedBox(height: 18),
                    Text(
                      isAr
                          ? 'مبارك! تم إنشاء الحساب بنجاح ✨'
                          : 'Account Created Successfully! ✨',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontFamily: 'Cairo',
                        fontWeight: FontWeight.bold,
                        fontSize: 18,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      isAr
                          ? 'تم حفظ الملف الشخصي وتوقعات الشريك بنجاح وربطها بالكامل. يمكنك الآن البدء بتصفح التطبيق.'
                          : 'Profile and partner expectations saved successfully. You can now browse the app.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white70,
                        fontFamily: 'Cairo',
                        fontSize: 13.5,
                        height: 1.5,
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF0288D1),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          elevation: 4,
                        ),
                        onPressed: () {
                          Navigator.pop(ctx);
                          Navigator.pushAndRemoveUntil(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const MainHomeScreen(),
                            ),
                            (route) => false,
                          );
                        },
                        child: Text(
                          isAr ? 'الانتقال للتطبيق 🚀' : 'Start Exploring 🚀',
                          style: const TextStyle(
                            fontFamily: 'Cairo',
                            fontWeight: FontWeight.bold,
                            fontSize: 15,
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

  @override
  Widget build(BuildContext context) {
    final bool isAr = widget.currentLanguage == 'ar';
    const primaryColor = Color(0xFF0288D1);
    const darkBgColor = Color(0xFF01579B);

    final double bottomInset = MediaQuery.of(context).padding.bottom;
    final double bottomButtonPadding =
        bottomInset > 0 ? bottomInset + 10.0 : 16.0;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: darkBgColor,
        systemNavigationBarIconBrightness: Brightness.light,
        systemNavigationBarDividerColor: Colors.transparent,
      ),
      child: Scaffold(
        body: Directionality(
          textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
          child: Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  Color(0xFF01579B),
                  Color(0xFF0288D1),
                  Color(0xFF00838F)
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: SafeArea(
              child: Column(
                children: [
                  // 1️⃣ الشريط العلوي (شريط العنوان والتراجع)
                  Padding(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16.0, vertical: 8.0),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        IconButton(
                          icon: Icon(
                            isAr
                                ? Icons.arrow_back_ios_rounded
                                : Icons.arrow_back_ios_new_rounded,
                            color: Colors.white,
                            size: 20,
                          ),
                          onPressed: _goToPreviousPage,
                        ),
                        Text(
                          isAr
                              ? 'خطوة ${_currentStep + 1} من 4'
                              : 'Step ${_currentStep + 1} of 4',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        const SizedBox(width: 48),
                      ],
                    ),
                  ),

                  // 2️⃣ مؤشر التقدم الخطوي (Step Indicator)
                  Padding(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 24.0, vertical: 4.0),
                    child: Row(
                      children: List.generate(4, (index) {
                        return Expanded(
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 300),
                            margin: const EdgeInsets.symmetric(horizontal: 3.0),
                            height: 6,
                            decoration: BoxDecoration(
                              color: index <= _currentStep
                                  ? Colors.amberAccent
                                  : Colors.white24,
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                        );
                      }),
                    ),
                  ),
                  const SizedBox(height: 10),

                  // 3️⃣ منطقة عرض محتوى الخطوات (PageView)
                  Expanded(
                    child: PageView(
                      controller: _pageController,
                      physics: const NeverScrollableScrollPhysics(),
                      children: [
                        Step1BasicInfo(
                          formKey: _step1Key,
                          firstNameController: _firstNameController,
                          lastNameController: _lastNameController,
                          dobController: _dobController,
                          emailController: _emailController,
                          passwordController: _passwordController,
                          confirmPasswordController: _confirmPasswordController,
                          gender: _gender,
                          onBehalf: _onBehalf,
                          agreedToTerms: _agreedToTerms,
                          profilePhoto: _profilePhoto,
                          lang: widget.currentLanguage,
                          onGenderChanged: (v) => setState(() => _gender = v),
                          onBehalfChanged: (v) => setState(() => _onBehalf = v),
                          onTermsChanged: (v) =>
                              setState(() => _agreedToTerms = v),
                          onPhotoChanged: (f) =>
                              setState(() => _profilePhoto = f),
                        ),
                        Step2PersonalDetails(
                          formKey: _step2Key,
                          institutionController: _institutionController,
                          designationController: _designationController,
                          companyController: _companyController,
                          educationDegree: _educationDegree,
                          height: _height,
                          weight: _weight,
                          heightWasSelected: _heightWasSelected,
                          weightWasSelected: _weightWasSelected,
                          eyeColor: _eyeColor,
                          hairColor: _hairColor,
                          complexion: _complexion,
                          bodyType: _bodyType,
                          bodyArt: _bodyArt,
                          drink: _personalDrink,
                          sunSign: _personalSunSign,
                          affection: _personalAffection,
                          humor: _personalHumor,
                          onDrinkChanged: (v) => setState(() => _personalDrink = v),
                          onSunSignChanged: (v) => setState(() => _personalSunSign = v),
                          onAffectionChanged: (v) => setState(() => _personalAffection = v),
                          onHumorChanged: (v) => setState(() => _personalHumor = v),
                          lang: widget.currentLanguage,
                          onEducationChanged: (v) =>
                              setState(() => _educationDegree = v),
                          onHeightChanged: (v) => setState(() {
                            _height = v;
                            _heightWasSelected = true;
                          }),
                          onWeightChanged: (v) => setState(() {
                            _weight = v;
                            _weightWasSelected = true;
                          }),
                          onEyeColorChanged: (v) => setState(() {
                            _eyeColor = v;
                            _eyeColorWasSelected = true;
                          }),
                          onHairColorChanged: (v) => setState(() {
                            _hairColor = v;
                            _hairColorWasSelected = true;
                          }),
                          onComplexionChanged: (v) => setState(() {
                            _complexion = v;
                            _complexionWasSelected = true;
                          }),
                          onBodyTypeChanged: (v) => setState(() {
                            _bodyType = v;
                            _bodyTypeWasSelected = true;
                          }),
                          onBodyArtChanged: (v) => setState(() {
                            _bodyArt = v;
                            _bodyArtWasSelected = true;
                          }),
                        ),
                        Step3ResidencyReligion(
                          formKey: _step3Key,
                          birthCountryId: _birthCountryId,
                          residenceCountryId: _residenceCountryId,
                          stateId: _stateId,
                          cityId: _cityId,
                          religionId: _religionId,
                          maritalStatusId: _maritalStatusId,
                          childrenCountController: _childrenCountController,
                          lang: widget.currentLanguage,
                          onBirthCountryChanged: (v) =>
                              setState(() => _birthCountryId = v),
                                          onResidenceCountryChanged: (v) => setState(() {
                            if (_residenceCountryId != v) {
                              _stateId = null;
                              _cityId = null;
                            }
                            _residenceCountryId = v;
                          }),
                          onStateChanged: (v) => setState(() {
                            if (_stateId != v) _cityId = null;
                            _stateId = v;
                          }),
                          onCityChanged: (v) => setState(() => _cityId = v),
                          onReligionChanged: (v) => setState(() => _religionId = v),
                          onMaritalStatusChanged: (v) => setState(() {
                            _maritalStatusId = v;
                            if (v == '1') _childrenCountController.clear();
                          }),
                        ),
                        Step4PartnerExpectations(
                          formKey: _step4Key,
                          ageRange: _partnerAgeRange,
                          ageRangeWasSelected: _partnerAgeWasSelected,
                          generalController: _partnerGeneralController,
                          professionController: _partnerProfessionController,
                          heightRange: _partnerHeightRange,
                          weightRange: _partnerWeightRange,
                          heightRangeWasSelected: _partnerHeightWasSelected,
                          weightRangeWasSelected: _partnerWeightWasSelected,
                          education: _partnerEducation,
                          complexion: _partnerComplexion,
                          eyeColor: _partnerEyeColor,
                          hairColor: _partnerHairColor,
                          languages: _partnerLanguages,
                          religions: _partnerReligions,
                          countries: _partnerCountries,
                          states: _partnerStates,
                          preferredReligionId: _partnerReligionId,
                          preferredCountryId: _partnerCountryId,
                          preferredStateId: _partnerStateId,
                          isLoadingLanguages: _isLoadingPartnerLanguages,
                          languageId: _partnerLanguageId,
                          childrenAcceptable: _partnerChildrenAcceptable,
                          smokingAcceptable: _partnerSmokingAcceptable,
                          drinkingAcceptable: _partnerDrinkingAcceptable,
                          lang: widget.currentLanguage,
                          onAgeRangeChanged: (v) => setState(() {
                            _partnerAgeRange = v;
                            _partnerAgeWasSelected = true;
                          }),
                          onHeightRangeChanged: (v) => setState(() {
                            _partnerHeightRange = v;
                            _partnerHeightWasSelected = true;
                          }),
                          onWeightRangeChanged: (v) => setState(() {
                            _partnerWeightRange = v;
                            _partnerWeightWasSelected = true;
                          }),
                          onEducationChanged: (v) =>
                              setState(() => _partnerEducation = v),
                          onComplexionChanged: (v) =>
                              setState(() => _partnerComplexion = v),
                          onEyeColorChanged: (v) =>
                              setState(() => _partnerEyeColor = v),
                          onHairColorChanged: (v) =>
                              setState(() => _partnerHairColor = v),
                          onLanguageChanged: (v) =>
                              setState(() => _partnerLanguageId = v),
                          onRetryLanguages: _loadPartnerOptions,
                          onPreferredReligionChanged: (v) => setState(() => _partnerReligionId = v),
                          onPreferredCountryChanged: (v) {
                            setState(() => _partnerCountryId = v);
                            _loadPartnerStates(v);
                          },
                          onPreferredStateChanged: (v) => setState(() => _partnerStateId = v),
                          onChildrenChanged: (v) =>
                              setState(() => _partnerChildrenAcceptable = v),
                          onSmokingChanged: (v) =>
                              setState(() => _partnerSmokingAcceptable = v),
                          onDrinkingChanged: (v) =>
                              setState(() => _partnerDrinkingAcceptable = v),
                        ),
                      ],
                    ),
                  ),

                  // 4️⃣ الزر السفلي للانتقال للخطوة التالية أو الإنهاء
                  Padding(
                    padding: EdgeInsets.fromLTRB(
                        16.0, 8.0, 16.0, bottomButtonPadding),
                    child: SizedBox(
                      width: double.infinity,
                      height: 54,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _handleNextStep,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: primaryColor,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(28),
                          ),
                          elevation: 6,
                        ),
                        child: _isLoading
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(
                                  color: primaryColor,
                                  strokeWidth: 3,
                                ),
                              )
                            : Text(
                                _currentStep == 3
                                    ? (isAr
                                        ? 'إتمام وحفظ الحساب النهائي ✨'
                                        : 'Complete Registration ✨')
                                    : (isAr
                                        ? 'المتابعة للخطوة التالية 👈'
                                        : 'Continue to Next Step 👈'),
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo',
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
    );
  }
}

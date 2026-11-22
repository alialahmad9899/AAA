// ============================================================================
// اسم الملف: lib/screens/signup_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: الشاشة الرئيسية لعملية التسجيل متعددة الخطوات (4 خطوات)
//        مع دعم استقبال بيانات Google التلقائية وإشتراط رفع الصورة الشخصية.
// ============================================================================

import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/colors.dart';

import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../services/profile_cache_service.dart';
import '../widgets/app_snackbar.dart';
import '../widgets/wesal_logo.dart';
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
  final bool resumeExistingDraft;

  const SignupScreen({
    super.key,
    this.currentLanguage = 'ar',
    this.initialEmail,
    this.initialName,
    this.resumeExistingDraft = false,
  });

  @override
  State<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen> with WidgetsBindingObserver {
  // متحكم التنقل بين الصفحات
  final PageController _pageController = PageController();
  int _currentStep = 0;
  bool _isLoading = false;
  bool _showValidationErrors = false;
  String? _profilePhotoValidationMessage;
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
  int _partnerStatesRequestSequence = 0;
  bool _isRestoringSignupDraft = false;
  Timer? _signupDraftTimer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    for (final controller in <TextEditingController>[
      _institutionController,
      _designationController,
      _companyController,
      _childrenCountController,
      _partnerGeneralController,
      _partnerProfessionController,
    ]) {
      controller.addListener(_schedulePersistSignupDraft);
    }

    if (widget.resumeExistingDraft) {
      unawaited(_restoreSignupDraft());
    } else {
      _initializeFieldsFromGoogle();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.inactive ||
        state == AppLifecycleState.paused ||
        state == AppLifecycleState.detached) {
      _signupDraftTimer?.cancel();
      unawaited(_persistSignupDraft());
    }
  }

  @override
  void setState(VoidCallback fn) {
    super.setState(fn);
    _schedulePersistSignupDraft();
  }

  void _schedulePersistSignupDraft() {
    if (!_isAccountCreated || _isRestoringSignupDraft || !mounted) return;
    _signupDraftTimer?.cancel();
    _signupDraftTimer = Timer(const Duration(milliseconds: 350), () {
      unawaited(_persistSignupDraft());
    });
  }

  Future<void> _persistSignupDraft() async {
    if (!_isAccountCreated || _isRestoringSignupDraft) return;
    final draft = <String, dynamic>{
      'current_step': _currentStep,
      'is_account_created': true,
      'first_name': _firstNameController.text,
      'last_name': _lastNameController.text,
      'date_of_birth': _dobController.text,
      'email': _emailController.text,
      'gender': _gender,
      'on_behalf': _onBehalf,
      'education_degree': _educationDegree,
      'institution': _institutionController.text,
      'designation': _designationController.text,
      'company': _companyController.text,
      'drink': _personalDrink,
      'sun_sign': _personalSunSign,
      'affection': _personalAffection,
      'humor': _personalHumor,
      'height': _height,
      'weight': _weight,
      'height_was_selected': _heightWasSelected,
      'weight_was_selected': _weightWasSelected,
      'eye_color': _eyeColor,
      'hair_color': _hairColor,
      'complexion': _complexion,
      'body_type': _bodyType,
      'body_art': _bodyArt,
      'eye_color_was_selected': _eyeColorWasSelected,
      'hair_color_was_selected': _hairColorWasSelected,
      'complexion_was_selected': _complexionWasSelected,
      'body_type_was_selected': _bodyTypeWasSelected,
      'body_art_was_selected': _bodyArtWasSelected,
      'birth_country_id': _birthCountryId,
      'residence_country_id': _residenceCountryId,
      'state_id': _stateId,
      'city_id': _cityId,
      'religion_id': _religionId,
      'marital_status_id': _maritalStatusId,
      'children_count': _childrenCountController.text,
      'partner_general': _partnerGeneralController.text,
      'partner_profession': _partnerProfessionController.text,
      'partner_age_range': [_partnerAgeRange.start, _partnerAgeRange.end],
      'partner_height_range': [_partnerHeightRange.start, _partnerHeightRange.end],
      'partner_weight_range': [_partnerWeightRange.start, _partnerWeightRange.end],
      'partner_age_was_selected': _partnerAgeWasSelected,
      'partner_height_was_selected': _partnerHeightWasSelected,
      'partner_weight_was_selected': _partnerWeightWasSelected,
      'partner_education': _partnerEducation,
      'partner_complexion': _partnerComplexion,
      'partner_eye_color': _partnerEyeColor,
      'partner_hair_color': _partnerHairColor,
      'partner_language_id': _partnerLanguageId,
      'partner_religion_id': _partnerReligionId,
      'partner_country_id': _partnerCountryId,
      'partner_state_id': _partnerStateId,
      'partner_children_acceptable': _partnerChildrenAcceptable,
      'partner_smoking_acceptable': _partnerSmokingAcceptable,
      'partner_drinking_acceptable': _partnerDrinkingAcceptable,
      'education_record_id': _educationRecordId,
      'career_record_id': _careerRecordId,
      'education_saved_fingerprint': _educationSavedFingerprint,
      'career_saved_fingerprint': _careerSavedFingerprint,
    };
    await ApiService.saveSignupDraft(draft);
  }

  Future<void> _restoreSignupDraft() async {
    final draft = await ApiService.getSignupDraft();
    if (!mounted) return;
    if (draft == null || draft['is_account_created'] != true) {
      _initializeFieldsFromGoogle();
      return;
    }

    dynamic value(String key) => draft[key];
    String stringValue(String key, [String fallback = '']) =>
        (value(key) ?? fallback).toString();
    String? nullableString(String key) {
      final raw = value(key);
      if (raw == null || raw.toString().trim().isEmpty) return null;
      return raw.toString();
    }
    bool boolValue(String key) => value(key) == true || value(key) == 1;
    double doubleValue(String key, double fallback) =>
        double.tryParse(stringValue(key)) ?? fallback;
    RangeValues rangeValue(String key, RangeValues fallback) {
      final raw = value(key);
      if (raw is List && raw.length >= 2) {
        final start = double.tryParse(raw[0].toString());
        final end = double.tryParse(raw[1].toString());
        if (start != null && end != null && start <= end) {
          return RangeValues(start, end);
        }
      }
      return fallback;
    }

    _isRestoringSignupDraft = true;
    setState(() {
      _currentStep = (int.tryParse(stringValue('current_step')) ?? 1).clamp(1, 3).toInt();
      _isAccountCreated = true;
      _firstNameController.text = stringValue('first_name');
      _lastNameController.text = stringValue('last_name');
      _dobController.text = stringValue('date_of_birth');
      _emailController.text = stringValue('email');
      _gender = stringValue('gender');
      _onBehalf = stringValue('on_behalf');
      _educationDegree = nullableString('education_degree');
      _institutionController.text = stringValue('institution');
      _designationController.text = stringValue('designation');
      _companyController.text = stringValue('company');
      _personalDrink = nullableString('drink');
      _personalSunSign = nullableString('sun_sign');
      _personalAffection = nullableString('affection');
      _personalHumor = nullableString('humor');
      _height = doubleValue('height', 140);
      _weight = doubleValue('weight', 40);
      _heightWasSelected = boolValue('height_was_selected');
      _weightWasSelected = boolValue('weight_was_selected');
      _eyeColor = stringValue('eye_color');
      _hairColor = stringValue('hair_color');
      _complexion = stringValue('complexion');
      _bodyType = stringValue('body_type');
      _bodyArt = stringValue('body_art');
      _eyeColorWasSelected = boolValue('eye_color_was_selected');
      _hairColorWasSelected = boolValue('hair_color_was_selected');
      _complexionWasSelected = boolValue('complexion_was_selected');
      _bodyTypeWasSelected = boolValue('body_type_was_selected');
      _bodyArtWasSelected = boolValue('body_art_was_selected');
      _birthCountryId = nullableString('birth_country_id');
      _residenceCountryId = nullableString('residence_country_id');
      _stateId = nullableString('state_id');
      _cityId = nullableString('city_id');
      _religionId = nullableString('religion_id');
      _maritalStatusId = nullableString('marital_status_id');
      _childrenCountController.text = stringValue('children_count');
      _partnerGeneralController.text = stringValue('partner_general');
      _partnerProfessionController.text = stringValue('partner_profession');
      _partnerAgeRange = rangeValue('partner_age_range', _partnerAgeRange);
      _partnerHeightRange = rangeValue('partner_height_range', _partnerHeightRange);
      _partnerWeightRange = rangeValue('partner_weight_range', _partnerWeightRange);
      _partnerAgeWasSelected = boolValue('partner_age_was_selected');
      _partnerHeightWasSelected = boolValue('partner_height_was_selected');
      _partnerWeightWasSelected = boolValue('partner_weight_was_selected');
      _partnerEducation = nullableString('partner_education');
      _partnerComplexion = nullableString('partner_complexion');
      _partnerEyeColor = nullableString('partner_eye_color');
      _partnerHairColor = nullableString('partner_hair_color');
      _partnerLanguageId = nullableString('partner_language_id');
      _partnerReligionId = nullableString('partner_religion_id');
      _partnerCountryId = nullableString('partner_country_id');
      _partnerStateId = nullableString('partner_state_id');
      _partnerChildrenAcceptable = stringValue('partner_children_acceptable');
      _partnerSmokingAcceptable = stringValue('partner_smoking_acceptable');
      _partnerDrinkingAcceptable = stringValue('partner_drinking_acceptable');
      _educationRecordId = int.tryParse(stringValue('education_record_id'));
      _careerRecordId = int.tryParse(stringValue('career_record_id'));
      _educationSavedFingerprint = nullableString('education_saved_fingerprint');
      _careerSavedFingerprint = nullableString('career_saved_fingerprint');
    });
    _isRestoringSignupDraft = false;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && _pageController.hasClients) {
        _pageController.jumpToPage(_currentStep);
      }
    });

    await _loadPartnerOptions();
    if (_partnerCountryId != null) {
      await _loadPartnerStates(_partnerCountryId, keepSelected: true);
    }
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
        _partnerLanguages = Step4PartnerExpectations.normalizePartnerLanguages(loadedLanguages);
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

  Future<void> _loadPartnerStates(String? countryId, {bool keepSelected = false}) async {
    final int requestSequence = ++_partnerStatesRequestSequence;
    final id = int.tryParse(countryId ?? '');
    if (id == null || id <= 0) {
      if (mounted) setState(() { _partnerStates = []; _partnerStateId = null; });
      return;
    }
    try {
      final states = await ApiService.getStatesByCountry(id, lang: widget.currentLanguage);
      if (!mounted || requestSequence != _partnerStatesRequestSequence) return;
      setState(() {
        _partnerStates = states;
        _partnerStateId = keepSelected &&
                states.any((state) => state is Map &&
                    state['id'].toString() == _partnerStateId)
            ? _partnerStateId
            : null;
      });
    } catch (e) {
      debugPrint('Partner governorates could not be loaded: $e');
      if (mounted && requestSequence == _partnerStatesRequestSequence) setState(() => _partnerStates = []);
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
    WidgetsBinding.instance.removeObserver(this);
    _signupDraftTimer?.cancel();
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

  bool _hasPersistedValue(dynamic value) {
    if (value == null) return false;
    if (value is String) {
      final normalized = value.trim().toLowerCase();
      return normalized.isNotEmpty &&
          normalized != 'null' &&
          normalized != 'undefined' &&
          normalized != '0';
    }
    if (value is num) return value > 0;
    if (value is bool) return value;
    if (value is Map) return value.isNotEmpty;
    if (value is List) return value.isNotEmpty;
    return true;
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
    final bool isAr = widget.currentLanguage.trim().toLowerCase().startsWith('ar');

    // ------------------------------------------------------------------------
    // 🟢 الخطوة الأولى: إنشاء الحساب ورفع الصورة الشخصية
    // ------------------------------------------------------------------------
    if (_currentStep == 0) {
      // Validate every field first so all missing controls are highlighted in
      // the same pass, including the required profile-photo frame.
      setState(() => _showValidationErrors = true);
      final bool formIsValid = _step1Key.currentState!.validate();
      final bool selectionsAreValid = _gender.isNotEmpty &&
          _onBehalf.isNotEmpty &&
          _dobController.text.trim().isNotEmpty &&
          _agreedToTerms;

      final profilePhoto = _profilePhoto;
      final bool photoExists =
          profilePhoto != null && await profilePhoto.exists();
      if (!mounted) return;
      var photoIsValid = photoExists;
      if (photoExists) {
        final photoSize = await profilePhoto.length();
        if (!mounted) return;
        photoIsValid = photoSize > 0 && photoSize <= 10 * 1024 * 1024;
        if (!photoIsValid) {
          setState(() {
            _profilePhoto = null;
            _profilePhotoValidationMessage = isAr
                ? 'حجم الصورة يجب أن يكون أكبر من صفر وألا يتجاوز 10 ميغابايت. اختر صورة أخرى.'
                : 'Choose another photo larger than zero and no more than 10 MB.';
          });
        }
      }

      if (!formIsValid || !selectionsAreValid || !photoIsValid) return;
      setState(() {
        _showValidationErrors = false;
        _profilePhotoValidationMessage = null;
      });

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
            await ApiService.setRememberMe(true);

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
      setState(() => _showValidationErrors = true);
      final bool formIsValid = _step2Key.currentState!.validate();
      final bool selectionsAreValid =
          _educationDegree != null && _educationDegree!.trim().isNotEmpty &&
          _heightWasSelected && _weightWasSelected &&
          _eyeColorWasSelected && _hairColorWasSelected &&
          _complexionWasSelected && _bodyTypeWasSelected && _bodyArtWasSelected;
      if (!formIsValid || !selectionsAreValid) return;
      setState(() => _showValidationErrors = false);

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
      setState(() => _showValidationErrors = true);
      final bool formIsValid = _step3Key.currentState!.validate();
      final bool selectionsAreValid =
          _birthCountryId != null && _birthCountryId!.isNotEmpty &&
          _residenceCountryId != null && _residenceCountryId!.isNotEmpty &&
          _stateId != null && _stateId!.isNotEmpty &&
          _cityId != null && _cityId!.isNotEmpty &&
          _maritalStatusId != null && _maritalStatusId!.isNotEmpty &&
          _religionId != null && _religionId!.isNotEmpty;
      final int? childCount =
          int.tryParse(_childrenCountController.text.trim());
      final bool childrenAreValid = !_requiresChildrenCount ||
          (childCount != null && childCount >= 0);
      if (!formIsValid || !selectionsAreValid || !childrenAreValid) return;
      setState(() => _showValidationErrors = false);

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
      setState(() => _showValidationErrors = true);
      final bool formIsValid = _step4Key.currentState!.validate();
      final bool selectionsAreValid =
          _partnerHeightWasSelected && _partnerWeightWasSelected &&
          _partnerEducation != null && _partnerComplexion != null &&
          _partnerEyeColor != null && _partnerHairColor != null &&
          _partnerLanguageId != null &&
          _partnerChildrenAcceptable.isNotEmpty &&
          _partnerSmokingAcceptable.isNotEmpty &&
          _partnerDrinkingAcceptable.isNotEmpty;
      if (!formIsValid || !selectionsAreValid) return;
      setState(() => _showValidationErrors = false);

      setState(() => _isLoading = true);
      try {
        final partnerPayload = <String, dynamic>{
          if (_partnerGeneralController.text.trim().isNotEmpty)
            'general': _partnerGeneralController.text.trim(),
          if (_partnerAgeWasSelected)
            ...ApiService.buildPartnerAgeRangePayload(
              minAge: _partnerAgeRange.start.round(),
              maxAge: _partnerAgeRange.end.round(),
            ),
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
      setState(() {
        _showValidationErrors = false;
        _currentStep++;
      });
      unawaited(_persistSignupDraft());
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
      isArabic: widget.currentLanguage.trim().toLowerCase().startsWith('ar'),
      messageAr: message,
      messageEn: message,
    );
  }

  /// نافذة منبثقة زجاجية فور إتمام الحساب بنجاح
  void _showSuccessDialog() {
    final bool isAr = widget.currentLanguage.trim().toLowerCase().startsWith('ar');
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
                  color: AppColors.cardColor,
                  borderRadius: BorderRadius.circular(28),
                  border: Border.all(
                    color: AppColors.lineColor,
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
                            color: AppColors.success, width: 1.5),
                      ),
                      child: const Icon(
                        Icons.verified_rounded,
                        color: AppColors.success,
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
                        color: AppColors.textDark,
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
                        color: AppColors.textMuted,
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
                          backgroundColor: AppColors.primaryTeal,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          elevation: 4,
                        ),
                        onPressed: () async {
                          final rootNavigator = Navigator.of(context);
                          final dialogNavigator = Navigator.of(ctx);
                          await ApiService.clearSignupDraft();
                          if (!mounted) return;
                          dialogNavigator.pop();
                          rootNavigator.pushAndRemoveUntil(
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
    final bool isAr = widget.currentLanguage.trim().toLowerCase().startsWith('ar');
    const darkBgColor = AppColors.backgroundColor;

    final double bottomInset = MediaQuery.of(context).padding.bottom;
    final double bottomButtonPadding =
        bottomInset > 0 ? bottomInset + 10.0 : 16.0;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.dark,
        systemNavigationBarColor: darkBgColor,
        systemNavigationBarIconBrightness: Brightness.dark,
        systemNavigationBarDividerColor: Colors.transparent,
      ),
      child: Scaffold(
        body: Directionality(
          textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
          child: Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  AppColors.backgroundColor,
                  AppColors.sectionBackground
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: SafeArea(
              child: Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(12, 2, 12, 2),
                    child: Container(
                      padding: const EdgeInsets.fromLTRB(10, 2, 10, 6),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [
                            AppColors.primaryTeal,
                            AppColors.secondaryTeal,
                          ],
                          begin: Alignment.topRight,
                          end: Alignment.bottomLeft,
                        ),
                        borderRadius: BorderRadius.circular(28),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.primaryTeal
                                .withValues(alpha: 0.16),
                            blurRadius: 20,
                            offset: const Offset(0, 9),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Row(
                            textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
                            children: [
                              Material(
                                color: Colors.white.withValues(alpha: 0.13),
                                shape: const CircleBorder(),
                                child: IconButton(
                                  tooltip: isAr ? 'العودة' : 'Back',
                                  onPressed: _goToPreviousPage,
                                  visualDensity: VisualDensity.compact,
                                  constraints: const BoxConstraints.tightFor(width: 40, height: 40),
                                  padding: const EdgeInsets.all(8),
                                  icon: Icon(
                                    Icons.arrow_back_rounded,
                                    textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
                                    color: Colors.white,
                                    size: 20,
                                  ),
                                ),
                              ),
                              const Spacer(),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 7,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.14),
                                  borderRadius: BorderRadius.circular(30),
                                  border: Border.all(
                                    color: Colors.white.withValues(alpha: 0.20),
                                  ),
                                ),
                                child: Text(
                                  isAr
                                      ? 'الخطوة ${_currentStep + 1} من 4'
                                      : 'Step ${_currentStep + 1} of 4',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 11.5,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          const Center(
                            child: WesalLogo(
                              width: 104,
                              height: 54,
                              fit: BoxFit.contain,
                              excludeSemantics: true,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            isAr ? 'لنبدأ رحلتك مع وصال' : 'Let’s begin with Wesal',
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 16,
                              fontWeight: FontWeight.w900,
                              fontFamily: 'Cairo',
                              height: 1.35,
                            ),
                          ),
                          const SizedBox(height: 1),
                          Text(
                            isAr
                                ? 'أكمل بياناتك بهدوء، وكل خطوة تقرّبنا من ملف متكامل يعكسك.'
                                : 'Complete your details at your pace. Each step helps build a profile that represents you.',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.86),
                              fontSize: 10.5,
                              height: 1.25,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          const SizedBox(height: 6),
                          Row(
                            children: List.generate(4, (index) {
                              final bool completed = index < _currentStep;
                              final bool active = index == _currentStep;
                              return Expanded(
                                child: Row(
                                  children: [
                                    AnimatedContainer(
                                      duration:
                                          const Duration(milliseconds: 250),
                                      width: active ? 26 : 20,
                                      height: active ? 26 : 20,
                                      decoration: BoxDecoration(
                                        color: completed || active
                                            ? AppColors.accentLight
                                            : Colors.white.withValues(alpha: 0.13),
                                        shape: BoxShape.circle,
                                        border: Border.all(
                                          color: completed || active
                                              ? AppColors.accentLight
                                              : Colors.white.withValues(alpha: 0.34),
                                          width: 1.2,
                                        ),
                                      ),
                                      child: Center(
                                        child: completed
                                            ? const Icon(
                                                Icons.check_rounded,
                                                color: AppColors.primaryTeal,
                                                size: 18,
                                              )
                                            : Text(
                                                '${index + 1}',
                                                style: TextStyle(
                                                  color: active
                                                      ? AppColors.primaryTeal
                                                      : Colors.white,
                                                  fontSize: 10.5,
                                                  fontWeight: FontWeight.w900,
                                                  fontFamily: 'Cairo',
                                                ),
                                              ),
                                      ),
                                    ),
                                    if (index < 3)
                                      Expanded(
                                        child: Container(
                                          height: 2,
                                          margin: const EdgeInsets.symmetric(
                                              horizontal: 3),
                                          decoration: BoxDecoration(
                                            color: index < _currentStep
                                                ? AppColors.accentLight
                                                : Colors.white.withValues(
                                                    alpha: 0.22,
                                                  ),
                                            borderRadius:
                                                BorderRadius.circular(3),
                                          ),
                                        ),
                                      ),
                                  ],
                                ),
                              );
                            }),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 4),

                  // 3️⃣ منطقة عرض محتوى الخطوات (PageView)
                  Expanded(
                    child: PageView(
                      controller: _pageController,
                      physics: const NeverScrollableScrollPhysics(),
                      children: [
                        Step1BasicInfo(
                          formKey: _step1Key,
                          showValidationErrors: _showValidationErrors,
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
                          onPhotoChanged: (f) => setState(() {
                            _profilePhoto = f;
                            _profilePhotoValidationMessage = null;
                          }),
                        ),
                        Step2PersonalDetails(
                          formKey: _step2Key,
                          showValidationErrors: _showValidationErrors,
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
                          showValidationErrors: _showValidationErrors,
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
                          showValidationErrors: _showValidationErrors,
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

                  Padding(
                    padding: EdgeInsets.fromLTRB(
                      16,
                      8,
                      16,
                      bottomButtonPadding,
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (_showValidationErrors || _profilePhotoValidationMessage != null)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 6),
                            child: Text(
                              _profilePhotoValidationMessage ??
                                  (isAr
                                      ? 'أكمل الحقول المحددة بالأحمر، ثم اضغط متابعة.'
                                      : 'Complete the fields outlined in red, then continue.'),
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                color: Colors.redAccent,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ),
                        Text(
                          isAr
                              ? 'الخطوة ${_currentStep + 1} من 4 • بياناتك محفوظة أثناء التنقل'
                              : 'Step ${_currentStep + 1} of 4 • Your details stay with you',
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: AppColors.textMuted,
                            fontSize: 10.5,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        const SizedBox(height: 8),
                        SizedBox(
                          width: double.infinity,
                          height: 56,
                          child: ElevatedButton(
                            onPressed: _isLoading ? null : _handleNextStep,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primaryTeal,
                              foregroundColor: Colors.white,
                              disabledBackgroundColor: AppColors.lineColor,
                              disabledForegroundColor: AppColors.textMuted,
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(18),
                              ),
                            ),
                            child: _isLoading
                                ? const SizedBox(
                                    width: 23,
                                    height: 23,
                                    child: CircularProgressIndicator(
                                      color: Colors.white,
                                      strokeWidth: 2.4,
                                    ),
                                  )
                                : Padding(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                    ),
                                    child: Center(
                                      child: Text(
                                        _currentStep == 3
                                            ? (isAr
                                                ? 'إتمام إنشاء الحساب'
                                                : 'Complete registration')
                                            : (isAr
                                                ? 'متابعة إلى الخطوة التالية'
                                                : 'Continue to next step'),
                                        textAlign: TextAlign.center,
                                        maxLines: 2,
                                        softWrap: true,
                                        style: const TextStyle(
                                          fontSize: 14.5,
                                          fontWeight: FontWeight.w900,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
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
        ),
      ),
    );
  }
}

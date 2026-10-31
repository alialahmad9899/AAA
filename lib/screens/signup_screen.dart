// ============================================================================
// اسم الملف: lib/screens/signup_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: الشاشة الرئيسية لعملية التسجيل متعددة الخطوات (4 خطوات)
//        مع دعم استقبال بيانات Google التلقائية وإشتراط رفع الصورة الشخصية.
// ============================================================================

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
  String _gender = '1';
  String _onBehalf = '1';
  bool _agreedToTerms = true;
  File? _profilePhoto; // متغير الصورة الشخصية الإجبارية

  // 2️⃣ متغيّرات ومتحكمات الخطوة الثانية (التفاصيل الشخصية)
  String? _educationDegree = 'بكالوريوس';
  final _institutionController = TextEditingController();
  final _designationController = TextEditingController();
  double _height = 170.0;
  double _weight = 70.0;
  String _eyeColor = 'بني';
  String _hairColor = 'أسود';
  String _complexion = 'حنطي';
  String _bodyType = 'عادي';
  String _bodyArt = 'لا يوجد';

  // 3️⃣ متغيّرات الخطوة الثالثة (الإقامة والدين)
  String? _birthCountryId;
  String? _residenceCountryId;
  String? _stateId;
  String? _cityId;
  String? _religionId;
  String? _maritalStatusId;

  // 4️⃣ متغيّرات الخطوة الرابعة (توقعات الشريك)
  final _partnerGeneralController = TextEditingController();
  final _partnerProfessionController = TextEditingController();
  RangeValues _partnerHeightRange = const RangeValues(155, 180);
  RangeValues _partnerWeightRange = const RangeValues(50, 80);
  String? _partnerEducation = 'بكالوريوس';
  String? _partnerComplexion = 'حنطي';
  String? _partnerLanguageId;
  String? _partnerReligionId;
  String? _partnerMaritalStatusId;
  String _partnerChildrenAcceptable = 'dose_not_matter';
  String _partnerSmokingAcceptable = 'dose_not_matter';
  String _partnerDrinkingAcceptable = 'no';

  @override
  void initState() {
    super.initState();
    _initializeFieldsFromGoogle();
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
    _partnerGeneralController.dispose();
    _partnerProfessionController.dispose();
    super.dispose();
  }

  /// 🔄 معالجة الانتقال والتثبت من صحة البيانات في كل خطوة
  Future<void> _handleNextStep() async {
    FocusScope.of(context).unfocus();
    final bool isAr = widget.currentLanguage == 'ar';

    // ------------------------------------------------------------------------
    // 🟢 الخطوة الأولى: إنشاء الحساب ورفع الصورة الشخصية
    // ------------------------------------------------------------------------
    if (_currentStep == 0) {
      if (_profilePhoto == null) {
        _showNotification(
          isAr
              ? 'يرجى اختيار صورة شخصية لإكمال إنشاء الحساب'
              : 'Profile photo is required to complete registration',
          NotificationType.warning,
        );
        return;
      }

      if (!_step1Key.currentState!.validate()) return;

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
          );

          if (!mounted) return;

          if (result['success'] == true) {
            _isAccountCreated = true;
            final token =
                result['data']?['access_token'] ?? result['data']?['token'];
            if (token != null && token.toString().isNotEmpty) {
              await ApiService.saveToken(token.toString());
            }

            // 🟢 رفع الصورة الشخصية المحددة فور نجاح إنشاء الحساب
            try {
              await ApiService.updateBasicInfo(
                firstName: _firstNameController.text.trim(),
                lastName: _lastNameController.text.trim(),
                phone: '',
                gender: _gender,
                dateOfBirth: _dobController.text.trim(),
                maritalStatus: '1',
                onBehalf: _onBehalf,
                children: '0',
                photoFile: _profilePhoto,
                lang: widget.currentLanguage,
              );
            } catch (e) {
              debugPrint('⚠️ [Upload Signup Photo Error]: $e');
            }

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

      setState(() => _isLoading = true);
      try {
        await Future.wait([
          ApiService.updatePhysicalAttributes({
            'height': (_height / 100).toStringAsFixed(2),
            'weight': _weight.round().toString(),
            'eye_color': _eyeColor,
            'hair_color': _hairColor,
            'complexion': _complexion,
            'blood_group': 'O+',
            'body_type': _bodyType,
            'body_art': _bodyArt,
          }, lang: widget.currentLanguage),
          ApiService.updateEducation({
            'degree': _educationDegree ?? 'بكالوريوس',
            'institution': _institutionController.text.trim(),
            'education_start': '2026',
          }, lang: widget.currentLanguage),
          ApiService.updateCareer({
            'designation': _designationController.text.trim(),
            'company': 'مستقل',
            'career_start': '2026',
          }, lang: widget.currentLanguage),
        ]);

        if (!mounted) return;
        _goToNextPage();
      } catch (e) {
        if (mounted) {
          _showNotification(
            isAr
                ? 'تعذر حفظ البيانات البدنية'
                : 'Failed saving physical details',
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
      if (_stateId == null) {
        _showNotification(
          isAr ? 'يرجى تحديد المحافظة' : 'Select Governorate / State',
          NotificationType.warning,
        );
        return;
      }

      setState(() => _isLoading = true);
      try {
        await Future.wait([
          ApiService.updateResidencyInfo({
            'birth_country_id': _birthCountryId!,
            'recidency_country_id': _residenceCountryId!,
            'recidency_state_id': _stateId ?? '0',
            'recidency_city_id': _cityId ?? '0',
            'growup_country_id': _birthCountryId!,
          }, lang: widget.currentLanguage),
          ApiService.updateSpiritualBackground({
            'member_religion_id': _religionId ?? '1',
            'family_value_id': '1',
            'marital_status': _maritalStatusId ?? '1',
          }, lang: widget.currentLanguage),
        ]);

        if (!mounted) return;
        _goToNextPage();
      } catch (e) {
        if (mounted) {
          _showNotification(
            isAr
                ? 'تعذر حفظ بيانات الإقامة'
                : 'Failed saving residency details',
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

      setState(() => _isLoading = true);
      try {
        await ApiService.updatePartnerExpectations({
          'general': _partnerGeneralController.text.trim(),
          'residence_country_id': _residenceCountryId ?? '1',
          'partner_country_id': _residenceCountryId ?? '1',
          'partner_state_id': _stateId ?? '0',
          'partner_height':
              (_partnerHeightRange.start / 100).toStringAsFixed(2),
          'partner_weight': _partnerWeightRange.end.round().toString(),
          'partner_education': _partnerEducation ?? 'بكالوريوس',
          'pertner_education': _partnerEducation ?? 'بكالوريوس',
          'partner_profession': _partnerProfessionController.text.trim(),
          'partner_marital_status':
              _partnerMaritalStatusId ?? _maritalStatusId ?? '1',
          'partner_religion_id': _partnerReligionId ?? _religionId ?? '1',
          'language_id': _partnerLanguageId ?? '1',
          'partner_children_acceptable': _partnerChildrenAcceptable,
          'smoking_acceptable': _partnerSmokingAcceptable,
          'drinking_acceptable': _partnerDrinkingAcceptable,
          'pertner_complexion': _partnerComplexion ?? 'حنطي',
          'complexion': _partnerComplexion ?? 'حنطي',
          'partner_diet': 'dose_not_matter',
          'partner_manglik': 'dose_not_matter',
          'partner_caste_id': '0',
          'partner_sub_caste_id': '0',
          'partner_body_type': '0',
          'partner_personal_value': '0',
          'family_value_id': '0',
        }, lang: widget.currentLanguage);

        await ProfileCacheService.clearCache();

        if (!mounted) return;
        _showSuccessDialog();
      } catch (e) {
        if (mounted) {
          _showNotification(
            isAr ? 'تعذر حفظ توقعات الشريك' : 'Failed saving expectations',
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
                          educationDegree: _educationDegree,
                          height: _height,
                          weight: _weight,
                          eyeColor: _eyeColor,
                          hairColor: _hairColor,
                          complexion: _complexion,
                          bodyType: _bodyType,
                          bodyArt: _bodyArt,
                          lang: widget.currentLanguage,
                          onEducationChanged: (v) =>
                              setState(() => _educationDegree = v),
                          onHeightChanged: (v) => setState(() => _height = v),
                          onWeightChanged: (v) => setState(() => _weight = v),
                          onEyeColorChanged: (v) =>
                              setState(() => _eyeColor = v),
                          onHairColorChanged: (v) =>
                              setState(() => _hairColor = v),
                          onComplexionChanged: (v) =>
                              setState(() => _complexion = v),
                          onBodyTypeChanged: (v) =>
                              setState(() => _bodyType = v),
                          onBodyArtChanged: (v) => setState(() => _bodyArt = v),
                        ),
                        Step3ResidencyReligion(
                          formKey: _step3Key,
                          birthCountryId: _birthCountryId,
                          residenceCountryId: _residenceCountryId,
                          stateId: _stateId,
                          cityId: _cityId,
                          religionId: _religionId,
                          maritalStatusId: _maritalStatusId,
                          lang: widget.currentLanguage,
                          onBirthCountryChanged: (v) =>
                              setState(() => _birthCountryId = v),
                          onResidenceCountryChanged: (v) =>
                              setState(() => _residenceCountryId = v),
                          onStateChanged: (v) => setState(() => _stateId = v),
                          onCityChanged: (v) => setState(() => _cityId = v),
                          onReligionChanged: (v) => setState(() {
                            _religionId = v;
                            _partnerReligionId = v;
                          }),
                          onMaritalStatusChanged: (v) => setState(() {
                            _maritalStatusId = v;
                            _partnerMaritalStatusId = v;
                          }),
                        ),
                        Step4PartnerExpectations(
                          formKey: _step4Key,
                          generalController: _partnerGeneralController,
                          professionController: _partnerProfessionController,
                          heightRange: _partnerHeightRange,
                          weightRange: _partnerWeightRange,
                          education: _partnerEducation,
                          complexion: _partnerComplexion,
                          languageId: _partnerLanguageId,
                          childrenAcceptable: _partnerChildrenAcceptable,
                          smokingAcceptable: _partnerSmokingAcceptable,
                          drinkingAcceptable: _partnerDrinkingAcceptable,
                          lang: widget.currentLanguage,
                          onHeightRangeChanged: (v) =>
                              setState(() => _partnerHeightRange = v),
                          onWeightRangeChanged: (v) =>
                              setState(() => _partnerWeightRange = v),
                          onEducationChanged: (v) =>
                              setState(() => _partnerEducation = v),
                          onComplexionChanged: (v) =>
                              setState(() => _partnerComplexion = v),
                          onLanguageChanged: (v) =>
                              setState(() => _partnerLanguageId = v),
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

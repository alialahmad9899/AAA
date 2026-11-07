// ============================================================================
// الملف: lib/screens/widgets/step1_basic_info.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: المرحلة 1 - إضافة ميزة اقتصاص الصورة بنسبة 4:5 إجبارية قبل الحفظ
//        مع الدعم الكامل للغتين والتأقلم مع الاتجاه الديناميكي (RTL / LTR).
// ============================================================================

import 'dart:io';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:image_cropper/image_cropper.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../constants/colors.dart';
import '../../utils/arabic_number_formatter.dart';

class Step1BasicInfo extends StatefulWidget {
  final GlobalKey<FormState> formKey;
  final TextEditingController firstNameController;
  final TextEditingController lastNameController;
  final TextEditingController dobController;
  final TextEditingController emailController;
  final TextEditingController passwordController;
  final TextEditingController confirmPasswordController;
  final String gender;
  final String onBehalf;
  final bool agreedToTerms;
  final File? profilePhoto;
  final String lang;

  final ValueChanged<String> onGenderChanged;
  final ValueChanged<String> onBehalfChanged;
  final ValueChanged<bool> onTermsChanged;
  final ValueChanged<File?> onPhotoChanged;

  const Step1BasicInfo({
    super.key,
    required this.formKey,
    required this.firstNameController,
    required this.lastNameController,
    required this.dobController,
    required this.emailController,
    required this.passwordController,
    required this.confirmPasswordController,
    required this.gender,
    required this.onBehalf,
    required this.agreedToTerms,
    required this.profilePhoto,
    this.lang = 'ar',
    required this.onGenderChanged,
    required this.onBehalfChanged,
    required this.onTermsChanged,
    required this.onPhotoChanged,
  });

  @override
  State<Step1BasicInfo> createState() => _Step1BasicInfoState();
}

class _Step1BasicInfoState extends State<Step1BasicInfo> {
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  late final TapGestureRecognizer _termsToggleRecognizer;
  late final TapGestureRecognizer _termsLinkRecognizer;

  static final Uri _termsUri =
      Uri.parse('https://www.wesalsyria.com/terms-conditions');

  String? _selectedDay;
  String? _selectedMonth;
  String? _selectedYear;

  late final List<String> _days;
  late final List<String> _months;
  late final List<String> _years;

  bool get _isAr => widget.lang == 'ar';

  @override
  void initState() {
    super.initState();
    _termsToggleRecognizer = TapGestureRecognizer()
      ..onTap = () => widget.onTermsChanged(!widget.agreedToTerms);
    _termsLinkRecognizer = TapGestureRecognizer()
      ..onTap = _openTermsAndConditions;
    _days = List.generate(31, (i) => (i + 1).toString());
    _months = List.generate(12, (i) => (i + 1).toString());
    final currentYear = DateTime.now().year;
    _years = List.generate(68, (i) => (currentYear - 18 - i).toString());

    _parseDobFromController();
  }

  Future<void> _openTermsAndConditions() async {
    try {
      final opened = await launchUrl(
        _termsUri,
        mode: LaunchMode.externalApplication,
      );
      if (!opened && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _isAr
                  ? 'تعذر فتح صفحة الخصوصية وشروط التسجيل'
                  : 'Could not open the privacy policy and registration terms',
            ),
          ),
        );
      }
    } catch (error) {
      debugPrint('Failed to open Wesal terms and conditions: $error');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _isAr
                  ? 'تعذر فتح صفحة الخصوصية وشروط التسجيل'
                  : 'Could not open the privacy policy and registration terms',
            ),
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _termsToggleRecognizer.dispose();
    _termsLinkRecognizer.dispose();
    super.dispose();
  }

  void _parseDobFromController() {
    if (widget.dobController.text.isNotEmpty) {
      final parts = widget.dobController.text.split('-');
      if (parts.length == 3) {
        final rawYear = parts[0].trim();
        final rawMonth = int.tryParse(parts[1].trim())?.toString();
        final rawDay = int.tryParse(parts[2].trim())?.toString();

        if (rawYear.isNotEmpty && _years.contains(rawYear)) {
          _selectedYear = rawYear;
        }
        if (rawMonth != null && _months.contains(rawMonth)) {
          _selectedMonth = rawMonth;
        }
        if (rawDay != null && _days.contains(rawDay)) {
          _selectedDay = rawDay;
        }
      }
    }
  }

  void _updateDobController() {
    if (_selectedDay != null &&
        _selectedMonth != null &&
        _selectedYear != null) {
      final formattedDay = _selectedDay!.padLeft(2, '0');
      final formattedMonth = _selectedMonth!.padLeft(2, '0');
      widget.dobController.text =
          "$_selectedYear-$formattedMonth-$formattedDay";
    } else {
      widget.dobController.text = "";
    }
  }

  /// 🟢 دالة اختيار واقتصاص الصورة بنسبة 4:5
  Future<void> _pickAndCropPhoto() async {
    try {
      final picker = ImagePicker();
      final pickedFile = await picker.pickImage(
        source: ImageSource.gallery,
        maxWidth: 1440,
        maxHeight: 1800,
        imageQuality: 85,
      );

      if (pickedFile != null) {
        final croppedFile = await ImageCropper().cropImage(
          sourcePath: pickedFile.path,
          maxWidth: 1440,
          maxHeight: 1800,
          compressQuality: 85,
          aspectRatio: const CropAspectRatio(ratioX: 4, ratioY: 5),
          uiSettings: [
            AndroidUiSettings(
              toolbarTitle:
                  _isAr ? 'اقتصاص الصورة الشخصية' : 'Crop Profile Photo',
              toolbarColor: AppColors.primaryTeal,
              toolbarWidgetColor: Colors.white,
              activeControlsWidgetColor: AppColors.accentWarm,
              initAspectRatio: CropAspectRatioPreset.original,
              lockAspectRatio: true,
            ),
            IOSUiSettings(
              title: _isAr ? 'اقتصاص الصورة الشخصية' : 'Crop Profile Photo',
              aspectRatioLockEnabled: true,
              resetAspectRatioEnabled: false,
            ),
          ],
        );

        if (croppedFile != null && mounted) {
          widget.onPhotoChanged(File(croppedFile.path));
        }
      }
    } catch (e) {
      debugPrint('⚠️ [Step1BasicInfo] Error picking or cropping photo: $e');
    }
  }

  final Map<String, Map<String, String>> _onBehalfOptions = const {
    '1': {'ar': 'عن نفسي 🙋‍♂️', 'en': 'For Myself 🙋‍♂️'},
    '2': {'ar': 'عن ابني/ابنتي 👨‍👩‍👧', 'en': 'For My Child 👨‍👩‍👧'},
    '3': {'ar': 'عن أخي/أختي 👫', 'en': 'For My Sibling 👫'},
  };

  InputDecoration _buildInputDecoration({
    required String hintText,
    IconData? prefixIcon,
    Widget? suffixIcon,
    EdgeInsetsGeometry? contentPadding,
  }) {
    return InputDecoration(
      hintText: hintText,
      hintStyle: const TextStyle(
        color: AppColors.textMuted,
        fontSize: 13,
        fontFamily: 'Cairo',
        fontWeight: FontWeight.w500,
      ),
      prefixIcon: prefixIcon != null
          ? Icon(prefixIcon, color: AppColors.primaryTeal, size: 18)
          : null,
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: AppColors.cardColor,
      contentPadding: contentPadding ??
          const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.black26, width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.primaryTeal, width: 1.8),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.redAccent),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.redAccent, width: 1.8),
      ),
    );
  }

  Widget _buildQuestionTitle(String title, {String? subtitle}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            color: AppColors.primaryTeal,
            fontSize: 15,
            fontWeight: FontWeight.bold,
            fontFamily: 'Cairo',
          ),
        ),
        if (subtitle != null) ...[
          const SizedBox(height: 2),
          Text(
            subtitle,
            style: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 12,
              fontFamily: 'Cairo',
            ),
          ),
        ],
        const SizedBox(height: 8),
      ],
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
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected
              ? Colors.amberAccent
              : Colors.white.withValues(alpha: 0.18),
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
                  size: 16, color: Color(0xFF01579B)),
              const SizedBox(width: 6),
            ],
            Text(
              label,
              style: TextStyle(
                color: isSelected ? AppColors.primaryTeal : AppColors.textDark,
                fontWeight: FontWeight.bold,
                fontSize: 13.5,
                fontFamily: 'Cairo',
              ),
            ),
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
                        ? 'الخطوة الأولى: بداية حكايتك مع وصال ✨'
                        : 'Step 1: Let us know you ✨',
                    style: const TextStyle(
                      fontSize: 19,
                      color: AppColors.textDark,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                ),
                const SizedBox(height: 18),

                // 🟢 قسم رفع الصورة الشخصية الإجباري مع الاقتطاع
                Center(
                  child: Column(
                    children: [
                      GestureDetector(
                        onTap: _pickAndCropPhoto,
                        child: Stack(
                          children: [
                            Container(
                              width: 110,
                              height: 137.5,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(18),
                                border: Border.all(
                                  color: widget.profilePhoto != null
                                      ? Colors.amberAccent
                                      : Colors.white60,
                                  width: 2.5,
                                ),
                                boxShadow: [
                                  BoxShadow(
                                    color: AppColors.textDark.withValues(alpha: 0.08),
                                    blurRadius: 10,
                                    offset: const Offset(0, 4),
                                  ),
                                ],
                              ),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(15),
                                child: widget.profilePhoto != null
                                    ? Image.file(
                                        widget.profilePhoto!,
                                        fit: BoxFit.cover,
                                        cacheWidth: 480,
                                        cacheHeight: 600,
                                        filterQuality: FilterQuality.medium,
                                      )
                                    : Container(
                                        color: Colors.white
                                            .withValues(alpha: 0.15),
                                        child: const Icon(
                                          Icons.add_a_photo_rounded,
                                          size: 42,
                                          color: Colors.white,
                                        ),
                                      ),
                              ),
                            ),
                            Positioned(
                              bottom: 2,
                              right: _isAr ? 2 : null,
                              left: _isAr ? null : 2,
                              child: Container(
                                padding: const EdgeInsets.all(6),
                                decoration: const BoxDecoration(
                                  color: Colors.amberAccent,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.crop_rounded,
                                  size: 16,
                                  color: Color(0xFF01579B),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _isAr
                            ? 'الصورة الشخصية (إجبارية بنسبة 4:5) *'
                            : 'Profile Photo (Required 4:5) *',
                        style: const TextStyle(
                          color: Colors.amberAccent,
                          fontSize: 13.5,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 14, vertical: 10),
                        decoration: BoxDecoration(
                          color:
                              AppColors.sectionBackground,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color:
                                AppColors.lineColor,
                            width: 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.info_outline_rounded,
                              color: AppColors.accentWarm,
                              size: 20,
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                _isAr
                                    ? 'ملاحظة: اختيار الصورة إجباري لإتمام الحساب، ويمكنك اقتطاع وتحديد الأبعاد المناسبة قبل الرفع.'
                                    : 'Note: Profile photo is required and will be cropped to 4:5 ratio.',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  height: 1.4,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                _buildQuestionTitle(
                  _isAr
                      ? '1. لمن نجهّز حساب وصال اليوم؟'
                      : '1. Who are you creating this profile for?',
                ),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _onBehalfOptions.entries.map((e) {
                    final isSelected = widget.onBehalf == e.key;
                    final label = e.value[widget.lang] ?? e.value['ar']!;
                    return _buildCustomChip(
                      label: label,
                      isSelected: isSelected,
                      onTap: () => widget.onBehalfChanged(e.key),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 18),

                _buildQuestionTitle(
                  _isAr ? '2. بأي اسم تحب أن نناديك؟' : '2. What is your full name?',
                ),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: widget.firstNameController,
                        style: const TextStyle(
                          color: AppColors.textDark,
                          fontSize: 14,
                          fontFamily: 'Cairo',
                          fontWeight: FontWeight.bold,
                        ),
                        decoration: _buildInputDecoration(
                          hintText: _isAr ? 'الاسم الأول *' : 'First Name *',
                          prefixIcon: Icons.person_outline,
                        ),
                        validator: (val) => (val == null || val.trim().isEmpty)
                            ? (_isAr ? 'مطلوب' : 'Required')
                            : null,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: widget.lastNameController,
                        style: const TextStyle(
                          color: Colors.black87,
                          fontSize: 14,
                          fontFamily: 'Cairo',
                          fontWeight: FontWeight.bold,
                        ),
                        decoration: _buildInputDecoration(
                          hintText: _isAr ? 'اسم العائلة *' : 'Last Name *',
                          prefixIcon: Icons.person_outline,
                        ),
                        validator: (val) => (val == null || val.trim().isEmpty)
                            ? (_isAr ? 'مطلوب' : 'Required')
                            : null,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),

                _buildQuestionTitle(
                  _isAr ? '3. ما الجنس الذي ندوّنه في ملفك؟ *' : '3. Gender *',
                ),
                Row(
                  children: [
                    Expanded(
                      child: _buildGenderCard(
                        '1',
                        _isAr ? 'ذكر 👨' : 'Male 👨',
                        Icons.male_rounded,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _buildGenderCard(
                        '2',
                        _isAr ? 'أنثى 👩' : 'Female 👩',
                        Icons.female_rounded,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),

                _buildQuestionTitle(
                  _isAr
                      ? '4. متى يوافق يوم ميلادك؟ 🎂'
                      : '4. What is your date of birth? 🎂',
                  subtitle: _isAr
                      ? 'اختر تاريخ ميلادك بدقة'
                      : 'Select Day, Month, and Year',
                ),
                Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: DropdownButtonFormField<String>(
                        initialValue: _selectedDay,
                        isExpanded: true,
                        dropdownColor: Colors.white,
                        style: const TextStyle(
                          color: Colors.black87,
                          fontFamily: 'Cairo',
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                        ),
                        hint: Text(
                          _isAr ? 'اليوم' : 'Day',
                          style: const TextStyle(
                            color: Colors.black54,
                            fontFamily: 'Cairo',
                            fontSize: 13,
                          ),
                        ),
                        decoration: _buildInputDecoration(
                          hintText: '',
                          contentPadding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 10),
                        ),
                        items: _days
                            .map((d) => DropdownMenuItem(
                                  value: d,
                                  child: _buildDateDropdownLabel(d),
                                ))
                            .toList(),
                        onChanged: (val) {
                          setState(() => _selectedDay = val);
                          _updateDobController();
                        },
                      ),
                    ),
                    const SizedBox(width: 6),
                    Expanded(
                      flex: 2,
                      child: DropdownButtonFormField<String>(
                        initialValue: _selectedMonth,
                        isExpanded: true,
                        dropdownColor: Colors.white,
                        style: const TextStyle(
                          color: Colors.black87,
                          fontFamily: 'Cairo',
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                        ),
                        hint: Text(
                          _isAr ? 'الشهر' : 'Month',
                          style: const TextStyle(
                            color: Colors.black54,
                            fontFamily: 'Cairo',
                            fontSize: 13,
                          ),
                        ),
                        decoration: _buildInputDecoration(
                          hintText: '',
                          contentPadding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 10),
                        ),
                        items: _months
                            .map((m) => DropdownMenuItem(
                                  value: m,
                                  child: _buildDateDropdownLabel(m),
                                ))
                            .toList(),
                        onChanged: (val) {
                          setState(() => _selectedMonth = val);
                          _updateDobController();
                        },
                      ),
                    ),
                    const SizedBox(width: 6),
                    Expanded(
                      flex: 3,
                      child: DropdownButtonFormField<String>(
                        initialValue: _selectedYear,
                        isExpanded: true,
                        dropdownColor: Colors.white,
                        style: const TextStyle(
                          color: Colors.black87,
                          fontFamily: 'Cairo',
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                        ),
                        hint: Text(
                          _isAr ? 'السنة' : 'Year',
                          style: const TextStyle(
                            color: Colors.black54,
                            fontFamily: 'Cairo',
                            fontSize: 13,
                          ),
                        ),
                        decoration: _buildInputDecoration(
                          hintText: '',
                          contentPadding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 10),
                        ),
                        items: _years
                            .map((y) => DropdownMenuItem(
                                  value: y,
                                  child: _buildDateDropdownLabel(y),
                                ))
                            .toList(),
                        onChanged: (val) {
                          setState(() => _selectedYear = val);
                          _updateDobController();
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),

                _buildQuestionTitle(
                  _isAr
                      ? '5. لنجهّز بيانات دخولك بأمان 🔐'
                      : '5. Account Credentials 🔐',
                ),
                TextFormField(
                  controller: widget.emailController,
                  keyboardType: TextInputType.emailAddress,
                  style: const TextStyle(
                    color: Colors.black87,
                    fontSize: 14,
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                  ),
                  inputFormatters: [
                    ArabicToEnglishNumbersFormatter(),
                    FilteringTextInputFormatter.deny(RegExp(r'\s'))
                  ],
                  decoration: _buildInputDecoration(
                    hintText: _isAr ? 'البريد الإلكتروني *' : 'Email Address *',
                    prefixIcon: Icons.email_outlined,
                  ),
                  validator: (val) =>
                      (val == null || !val.contains('@') || !val.contains('.'))
                          ? (_isAr ? 'بريد إلكتروني غير صحيح' : 'Invalid Email')
                          : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: widget.passwordController,
                  obscureText: _obscurePassword,
                  style: const TextStyle(
                    color: Colors.black87,
                    fontSize: 14,
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                  ),
                  inputFormatters: [ArabicToEnglishNumbersFormatter()],
                  decoration: _buildInputDecoration(
                    hintText: _isAr
                        ? 'كلمة المرور (8 أرقام على الأقل) *'
                        : 'Password (min 8 chars) *',
                    prefixIcon: Icons.lock_outline,
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword
                            ? Icons.visibility_off_outlined
                            : Icons.visibility_outlined,
                        color: const Color(0xFF0288D1),
                        size: 20,
                      ),
                      onPressed: () =>
                          setState(() => _obscurePassword = !_obscurePassword),
                    ),
                  ),
                  validator: (val) => (val == null || val.length < 8)
                      ? (_isAr ? 'لا تقل عن 8 خانات' : 'At least 8 chars')
                      : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: widget.confirmPasswordController,
                  obscureText: _obscureConfirmPassword,
                  style: const TextStyle(
                    color: Colors.black87,
                    fontSize: 14,
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                  ),
                  inputFormatters: [ArabicToEnglishNumbersFormatter()],
                  decoration: _buildInputDecoration(
                    hintText:
                        _isAr ? 'تأكيد كلمة المرور *' : 'Confirm Password *',
                    prefixIcon: Icons.lock_reset_outlined,
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscureConfirmPassword
                            ? Icons.visibility_off_outlined
                            : Icons.visibility_outlined,
                        color: const Color(0xFF0288D1),
                        size: 20,
                      ),
                      onPressed: () => setState(() =>
                          _obscureConfirmPassword = !_obscureConfirmPassword),
                    ),
                  ),
                  validator: (val) => (val != widget.passwordController.text)
                      ? (_isAr ? 'غير متطابقة' : 'Passwords do not match')
                      : null,
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Theme(
                      data: ThemeData(unselectedWidgetColor: AppColors.textLight),
                      child: Checkbox(
                        value: widget.agreedToTerms,
                        activeColor: AppColors.primaryTeal,
                        checkColor: Colors.white,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(4)),
                        onChanged: (val) => widget.onTermsChanged(val ?? false),
                      ),
                    ),
                    Expanded(
                      child: Text.rich(
                        TextSpan(
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12.5,
                            fontFamily: 'Cairo',
                            fontWeight: FontWeight.w600,
                          ),
                          children: [
                            TextSpan(
                              text: _isAr ? 'أوافق على ' : 'I agree to ',
                              recognizer: _termsToggleRecognizer,
                            ),
                            TextSpan(
                              text: _isAr
                                  ? 'الخصوصية وشروط التسجيل'
                                  : 'Privacy Policy & Registration Terms',
                              style: const TextStyle(
                                color: AppColors.accentWarm,
                                decoration: TextDecoration.underline,
                                decorationColor: AppColors.accentWarm,
                                fontWeight: FontWeight.bold,
                              ),
                              recognizer: _termsLinkRecognizer,
                            ),
                            TextSpan(
                              text: ' *',
                              recognizer: _termsToggleRecognizer,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDateDropdownLabel(String value) {
    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: Align(
        alignment: AlignmentDirectional.centerStart,
        child: Text(
          value,
          textAlign: TextAlign.start,
          textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
          style: const TextStyle(
            color: Colors.black87,
            fontFamily: 'Cairo',
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _buildGenderCard(String value, String label, IconData icon) {
    final bool isSelected = widget.gender == value;
    return GestureDetector(
      onTap: () => widget.onGenderChanged(value),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: isSelected
              ? Colors.amberAccent
              : Colors.white.withValues(alpha: 0.15),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? Colors.amber : Colors.white30,
            width: 1.5,
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              color: isSelected ? const Color(0xFF01579B) : Colors.white,
              size: 20,
            ),
            const SizedBox(width: 8),
            Text(
              label,
              style: TextStyle(
                color: isSelected ? const Color(0xFF01579B) : Colors.white,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
                fontSize: 14,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

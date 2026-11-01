// ============================================================================
// اسم الملف: lib/screens/edit_profile/widgets/step1_basic_physical_tab.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: التبويب الأول المطور مع حماية متكاملة لعرض البيانات الأساسية والجسدية
//         ودعم اللغتين العربية والإنجليزية وخالٍ تماماً من التحذيرات.
// ============================================================================

import 'dart:io';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../utils/arabic_number_formatter.dart';

class Step1BasicPhysicalTab extends StatelessWidget {
  final bool isAr;
  final TextEditingController firstNameCtrl;
  final TextEditingController lastNameCtrl;
  final TextEditingController phoneCtrl;
  final TextEditingController dobCtrl;
  final TextEditingController childrenCtrl;
  final TextEditingController introCtrl;
  final TextEditingController institutionCtrl;
  final TextEditingController educationStartCtrl;
  final TextEditingController designationCtrl;
  final TextEditingController companyCtrl;
  final TextEditingController careerStartCtrl;
  final String selectedGender;
  final String currentPhotoUrl;
  final File? pickedImageFile;
  final String? selectedMaritalStatusId;
  final String? selectedEducationDegree;
  final String? selectedHeight;
  final String? selectedWeight;
  final String? selectedEyeColor;
  final String? selectedHairColor;
  final String? selectedComplexion;
  final Map<String, Color> eyeColorMap;
  final Map<String, Color> hairColorMap;
  final Map<String, Color> complexionMap;

  final VoidCallback onPickImage;
  final VoidCallback onSelectDateOfBirth;
  final VoidCallback onSelectMaritalStatus;
  final VoidCallback onSelectEducationDegree;
  final ValueChanged<String> onHeightChanged;
  final ValueChanged<String> onWeightChanged;
  final VoidCallback onSelectEyeColor;
  final VoidCallback onSelectHairColor;
  final VoidCallback onSelectComplexion;

  const Step1BasicPhysicalTab({
    super.key,
    required this.isAr,
    required this.firstNameCtrl,
    required this.lastNameCtrl,
    required this.phoneCtrl,
    required this.dobCtrl,
    required this.childrenCtrl,
    required this.introCtrl,
    required this.institutionCtrl,
    required this.educationStartCtrl,
    required this.designationCtrl,
    required this.companyCtrl,
    required this.careerStartCtrl,
    required this.selectedGender,
    required this.currentPhotoUrl,
    required this.pickedImageFile,
    required this.selectedMaritalStatusId,
    required this.selectedEducationDegree,
    required this.selectedHeight,
    required this.selectedWeight,
    required this.selectedEyeColor,
    required this.selectedHairColor,
    required this.selectedComplexion,
    required this.eyeColorMap,
    required this.hairColorMap,
    required this.complexionMap,
    required this.onPickImage,
    required this.onSelectDateOfBirth,
    required this.onSelectMaritalStatus,
    required this.onSelectEducationDegree,
    required this.onHeightChanged,
    required this.onWeightChanged,
    required this.onSelectEyeColor,
    required this.onSelectHairColor,
    required this.onSelectComplexion,
  });

  InputDecoration _buildInput(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      labelStyle: const TextStyle(
        color: Colors.white,
        fontSize: 15,
        fontWeight: FontWeight.w600,
        fontFamily: 'Cairo',
      ),
      prefixIcon: Icon(icon, color: Colors.white70, size: 22),
      filled: true,
      fillColor: Colors.white.withValues(alpha: 0.12),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: InputBorder.none,
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide.none,
      ),
    );
  }

  Widget _buildSheetPickerTile({
    required String label,
    required String? displayValue,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    final bool hasValue =
        displayValue != null && displayValue.trim().isNotEmpty;
    final String textToDisplay = hasValue ? displayValue : label;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Icon(icon, color: Colors.white70, size: 22),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                textToDisplay,
                style: TextStyle(
                  color: hasValue ? Colors.white : Colors.white70,
                  fontSize: 15,
                  fontFamily: 'Cairo',
                  fontWeight: hasValue ? FontWeight.bold : FontWeight.normal,
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

  Widget _buildColorPickerTile({
    required String label,
    required String? displayValue,
    required Map<String, Color> colorMap,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    final bool hasValue =
        displayValue != null && displayValue.trim().isNotEmpty;
    final String textToDisplay = hasValue ? displayValue : label;
    final Color? currentColor = hasValue ? colorMap[displayValue.trim()] : null;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Icon(icon, color: Colors.white70, size: 22),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                textToDisplay,
                style: TextStyle(
                  color: hasValue ? Colors.white : Colors.white70,
                  fontSize: 15,
                  fontFamily: 'Cairo',
                  fontWeight: hasValue ? FontWeight.bold : FontWeight.normal,
                ),
              ),
            ),
            if (currentColor != null) ...[
              Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  color: currentColor,
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 2),
                  boxShadow: [
                    BoxShadow(
                      color: currentColor.withValues(alpha: 0.6),
                      blurRadius: 6,
                    )
                  ],
                ),
              ),
              const SizedBox(width: 8),
            ],
            const Icon(Icons.keyboard_arrow_down_rounded,
                color: Colors.white70, size: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(top: 20, bottom: 14),
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
          Expanded(
            child: Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 16.5,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final String gLower = selectedGender.trim().toLowerCase();
    final bool isFemale = gLower == '2' ||
        gLower.contains('female') ||
        gLower.contains('أنثى') ||
        gLower.contains('انثى');
    final String genderText =
        isFemale ? (isAr ? 'أنثى' : 'Female') : (isAr ? 'ذكر' : 'Male');

    String cleanHeight = (selectedHeight ?? '1.70')
        .replaceAll('م', '')
        .replaceAll('m', '')
        .trim();
    double currentHeightVal = double.tryParse(cleanHeight) ?? 1.70;

    if (currentHeightVal > 100) {
      currentHeightVal = currentHeightVal / 100;
    }
    if (currentHeightVal < 1.40 || currentHeightVal > 2.20) {
      currentHeightVal = 1.70;
    }

    String cleanWeight = (selectedWeight ?? '70')
        .replaceAll('كغ', '')
        .replaceAll('kg', '')
        .trim();
    double currentWeightVal = double.tryParse(cleanWeight) ?? 70.0;
    if (currentWeightVal < 40 || currentWeightVal > 150) {
      currentWeightVal = 70.0;
    }

    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.all(20),
      child: Container(
        padding: const EdgeInsets.only(
            left: 18.0, right: 18.0, top: 18.0, bottom: 100.0),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.18),
          borderRadius: BorderRadius.circular(28),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. الصورة الشخصية
            Center(
              child: GestureDetector(
                onTap: onPickImage,
                child: Stack(
                  children: [
                    Container(
                      width: 115,
                      height: 115,
                      decoration: const BoxDecoration(
                          shape: BoxShape.circle, color: Color(0xFF01579B)),
                      child: CircleAvatar(
                        backgroundColor: Colors.white10,
                        child: pickedImageFile != null
                            ? ClipRRect(
                                borderRadius: BorderRadius.circular(55),
                                child: Image.file(pickedImageFile!,
                                    width: 110, height: 110, fit: BoxFit.cover))
                            : currentPhotoUrl.isNotEmpty
                                ? ClipRRect(
                                    borderRadius: BorderRadius.circular(55),
                                    child: CachedNetworkImage(
                                        imageUrl: currentPhotoUrl,
                                        width: 110,
                                        height: 110,
                                        fit: BoxFit.cover))
                                : const Icon(Icons.person_rounded,
                                    size: 60, color: Colors.white70),
                      ),
                    ),
                    Positioned(
                      bottom: 2,
                      right: 2,
                      child: Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                            border: Border.all(
                                color: const Color(0xFF0288D1), width: 2)),
                        child: const Icon(Icons.camera_alt_rounded,
                            color: Color(0xFF0288D1), size: 16),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // 2. البيانات الأساسية
            _buildSectionHeader(
                isAr ? 'البيانات الأساسية والنبذة' : 'Basic Details',
                Icons.badge_rounded),
            TextFormField(
                controller: firstNameCtrl,
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(isAr ? 'الاسم الأول *' : 'First Name *',
                    Icons.person_rounded)),
            const SizedBox(height: 14),
            TextFormField(
                controller: lastNameCtrl,
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(isAr ? 'اسم العائلة *' : 'Last Name *',
                    Icons.person_outline_rounded)),
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16)),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(children: [
                    const Icon(Icons.transgender_rounded,
                        color: Colors.white70, size: 22),
                    const SizedBox(width: 12),
                    Text(isAr ? 'الجنس:' : 'Gender:',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                            fontFamily: 'Cairo')),
                    const SizedBox(width: 10),
                    Text(genderText,
                        style: TextStyle(
                            color: isFemale
                                ? const Color(0xFFF48FB1)
                                : Colors.amberAccent,
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo'))
                  ]),
                  Row(children: [
                    Icon(Icons.lock_outline_rounded,
                        color: Colors.white.withValues(alpha: 0.6), size: 16),
                    const SizedBox(width: 4),
                    Text(isAr ? 'ثابت' : 'Locked',
                        style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.6),
                            fontSize: 12.5,
                            fontFamily: 'Cairo'))
                  ]),
                ],
              ),
            ),
            const SizedBox(height: 14),

            _buildSheetPickerTile(
              label: isAr ? 'الحالة الاجتماعية 💍' : 'Marital Status 💍',
              displayValue: selectedMaritalStatusId != null
                  ? (selectedMaritalStatusId == '1'
                      ? (isAr ? 'أعزب / عزباء 💍' : 'Single 💍')
                      : selectedMaritalStatusId == '2'
                          ? (isAr ? 'متزوج / متزوجة 💍' : 'Married 💍')
                          : selectedMaritalStatusId == '3'
                              ? (isAr ? 'مطلق / مطلقة 💍' : 'Divorced 💍')
                              : (isAr ? 'أرمل / أرملة 💍' : 'Widowed 💍'))
                  : null,
              icon: Icons.favorite_rounded,
              onTap: onSelectMaritalStatus,
            ),
            const SizedBox(height: 14),

            GestureDetector(
              onTap: onSelectDateOfBirth,
              child: AbsorbPointer(
                child: TextFormField(
                    controller: dobCtrl,
                    style: const TextStyle(
                        color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                    decoration: _buildInput(
                        isAr ? 'تاريخ الميلاد' : 'Date of Birth',
                        Icons.calendar_today_rounded)),
              ),
            ),
            const SizedBox(height: 14),

            TextFormField(
                controller: phoneCtrl,
                keyboardType: TextInputType.phone,
                inputFormatters: [ArabicToEnglishNumbersFormatter()],
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(
                    isAr ? 'رقم للتواصل (اختياري)' : 'Phone Number (Optional)',
                    Icons.phone_rounded)),
            const SizedBox(height: 8),

            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: const Color(0xFF0288D1).withValues(alpha: 0.18),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: const Color(0xFF38BDF8).withValues(alpha: 0.4),
                  width: 1,
                ),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.amberAccent.withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.shield_outlined,
                      color: Colors.amberAccent,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      isAr
                          ? 'تنويه هام: رقم التواصل مخصص لإدارة المنصة فقط، ولا يظهر نهائياً لأي عضو آخر 🔒'
                          : 'Important Note: Phone number is for admin contact only and will never be visible to other members 🔒',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        height: 1.4,
                        fontFamily: 'Cairo',
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            TextFormField(
                controller: introCtrl,
                maxLines: 3,
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(
                    isAr ? 'النبذة الشخصية ✍' : 'Introduction ✍️',
                    Icons.article_rounded)),

            // 3. التحصيل العلمي والعمل
            _buildSectionHeader(
                isAr ? 'التحصيل العلمي والعمل 🎓💼' : 'Education & Career 🎓💼',
                Icons.school_rounded),

            _buildSheetPickerTile(
              label: isAr ? 'المؤهل العلمي 🎓' : 'Education Degree 🎓',
              displayValue: selectedEducationDegree,
              icon: Icons.workspace_premium_rounded,
              onTap: onSelectEducationDegree,
            ),
            const SizedBox(height: 14),

            TextFormField(
                controller: institutionCtrl,
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(
                    isAr ? 'الجامعة / المؤسسة 🏛️' : 'Institution 🏛️',
                    Icons.account_balance_rounded)),
            const SizedBox(height: 10),
            TextFormField(
                controller: educationStartCtrl,
                keyboardType: TextInputType.number,
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(
                    isAr ? 'سنة بدء التعليم' : 'Education start year',
                    Icons.calendar_today_outlined)),
            const SizedBox(height: 14),
            TextFormField(
                controller: designationCtrl,
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(
                    isAr ? 'المسمى الوظيفي / المهنة 💼' : 'Designation 💼',
                    Icons.work_rounded)),
            const SizedBox(height: 10),
            TextFormField(
                controller: companyCtrl,
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(
                    isAr ? 'جهة العمل' : 'Company / Employer',
                    Icons.business_outlined)),
            const SizedBox(height: 10),
            TextFormField(
                controller: careerStartCtrl,
                keyboardType: TextInputType.number,
                style: const TextStyle(
                    color: Colors.white, fontSize: 15, fontFamily: 'Cairo'),
                decoration: _buildInput(
                    isAr ? 'سنة بدء العمل' : 'Career start year',
                    Icons.calendar_month_outlined)),

            // 4. السمات والمظهر الجسدي
            _buildSectionHeader(
                isAr ? 'السمات والمظهر الجسدي' : 'Physical Attributes',
                Icons.accessibility_new_rounded),

            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.height_rounded,
                              color: Colors.amberAccent, size: 22),
                          const SizedBox(width: 8),
                          Text(isAr ? 'الطول:' : 'Height:',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15,
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold)),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.amberAccent,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          isAr
                              ? '${currentHeightVal.toStringAsFixed(2)} م'
                              : '${currentHeightVal.toStringAsFixed(2)} m',
                          style: const TextStyle(
                              color: Colors.black,
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                              fontFamily: 'Cairo'),
                        ),
                      ),
                    ],
                  ),
                  SliderTheme(
                    data: SliderThemeData(
                      activeTrackColor: Colors.amberAccent,
                      inactiveTrackColor: Colors.white24,
                      thumbColor: Colors.white,
                      overlayColor: Colors.amberAccent.withValues(alpha: 0.2),
                      valueIndicatorShape:
                          const PaddleSliderValueIndicatorShape(),
                      valueIndicatorTextStyle:
                          const TextStyle(color: Colors.black),
                    ),
                    child: Slider(
                      value: currentHeightVal,
                      min: 1.40,
                      max: 2.20,
                      divisions: 80,
                      label: isAr
                          ? '${currentHeightVal.toStringAsFixed(2)} م'
                          : '${currentHeightVal.toStringAsFixed(2)} m',
                      onChanged: (val) =>
                          onHeightChanged(val.toStringAsFixed(2)),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.monitor_weight_rounded,
                              color: Colors.amberAccent, size: 22),
                          const SizedBox(width: 8),
                          Text(isAr ? 'الوزن:' : 'Weight:',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15,
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold)),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.amberAccent,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          isAr
                              ? '${currentWeightVal.toInt()} كغ'
                              : '${currentWeightVal.toInt()} kg',
                          style: const TextStyle(
                              color: Colors.black,
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                              fontFamily: 'Cairo'),
                        ),
                      ),
                    ],
                  ),
                  SliderTheme(
                    data: SliderThemeData(
                      activeTrackColor: Colors.amberAccent,
                      inactiveTrackColor: Colors.white24,
                      thumbColor: Colors.white,
                      overlayColor: Colors.amberAccent.withValues(alpha: 0.2),
                    ),
                    child: Slider(
                      value: currentWeightVal,
                      min: 40,
                      max: 150,
                      divisions: 110,
                      label: isAr
                          ? '${currentWeightVal.toInt()} كغ'
                          : '${currentWeightVal.toInt()} kg',
                      onChanged: (val) =>
                          onWeightChanged(val.toInt().toString()),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            _buildColorPickerTile(
                label: isAr ? 'لون العينين 👁️' : 'Eye Color 👁️',
                displayValue: selectedEyeColor,
                colorMap: eyeColorMap,
                icon: Icons.remove_red_eye_rounded,
                onTap: onSelectEyeColor),
            const SizedBox(height: 14),
            _buildColorPickerTile(
                label: isAr ? 'لون الشعر 💈' : 'Hair Color 💈',
                displayValue: selectedHairColor,
                colorMap: hairColorMap,
                icon: Icons.face_rounded,
                onTap: onSelectHairColor),
            const SizedBox(height: 14),
            _buildColorPickerTile(
                label: isAr ? 'لون البشرة 🎨' : 'Skin Complexion 🎨',
                displayValue: selectedComplexion,
                colorMap: complexionMap,
                icon: Icons.color_lens_rounded,
                onTap: onSelectComplexion),
          ],
        ),
      ),
    );
  }
}

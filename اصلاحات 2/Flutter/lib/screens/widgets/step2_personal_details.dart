// ============================================================================
// الملف: lib/screens/widgets/step2_personal_details.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: المرحلة 2 - التفاصيل الشخصية والمظهر الخارجي مع الدعم الكامل
//        للغتين (عربي / إنجليزي) والتحويل الديناميكي للاتجاه (RTL / LTR).
// ============================================================================

import 'package:flutter/material.dart';

class Step2PersonalDetails extends StatelessWidget {
  final GlobalKey<FormState> formKey;
  final TextEditingController institutionController;
  final TextEditingController designationController;
  final TextEditingController companyController;
  final TextEditingController educationStartYearController;
  final TextEditingController careerStartYearController;
  final String? educationDegree;
  final double height;
  final double weight;
  final String eyeColor;
  final String hairColor;
  final String complexion;
  final String bodyType;
  final String bodyArt;
  final String lang;

  final ValueChanged<String?> onEducationChanged;
  final ValueChanged<double> onHeightChanged;
  final ValueChanged<double> onWeightChanged;
  final ValueChanged<String> onEyeColorChanged;
  final ValueChanged<String> onHairColorChanged;
  final ValueChanged<String> onComplexionChanged;
  final ValueChanged<String> onBodyTypeChanged;
  final ValueChanged<String> onBodyArtChanged;

  const Step2PersonalDetails({
    super.key,
    required this.formKey,
    required this.institutionController,
    required this.designationController,
    required this.companyController,
    required this.educationStartYearController,
    required this.careerStartYearController,
    required this.educationDegree,
    required this.height,
    required this.weight,
    required this.eyeColor,
    required this.hairColor,
    required this.complexion,
    required this.bodyType,
    required this.bodyArt,
    this.lang = 'ar',
    required this.onEducationChanged,
    required this.onHeightChanged,
    required this.onWeightChanged,
    required this.onEyeColorChanged,
    required this.onHairColorChanged,
    required this.onComplexionChanged,
    required this.onBodyTypeChanged,
    required this.onBodyArtChanged,
  });

  bool get _isAr => lang == 'ar';

  static const List<Map<String, String>> _educationDegrees = [
    {'ar': 'إعدادية', 'en': 'Preparatory'},
    {'ar': 'ثانوية', 'en': 'Secondary'},
    {'ar': 'دبلوم', 'en': 'Diploma'},
    {'ar': 'بكالوريوس', 'en': 'Bachelor'},
    {'ar': 'ماجستير', 'en': 'Master'},
    {'ar': 'دكتوراه', 'en': 'Doctorate'},
  ];

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

  static const Map<String, Map<String, String>> _bodyTypeOptions = {
    'عادي': {'ar': 'عادي / متوسط 🚶‍♂️', 'en': 'Average / Medium 🚶‍♂️'},
    'رياضي': {'ar': 'رياضي / متناسق 🏋️‍♂️', 'en': 'Athletic / Fit 🏋️‍♂️'},
    'رشيق': {'ar': 'رشيق / نحيف 🏃‍♂️', 'en': 'Slim / Lean 🏃‍♂️'},
    'ممتلئ': {'ar': 'ممتلئ / قوام عريض 🧍‍♂️', 'en': 'Plus Size / Heavy 🧍‍♂️'},
  };

  static const Map<String, Map<String, String>> _bodyArtOptions = {
    'لا يوجد': {'ar': 'لا يوجد ❌', 'en': 'None ❌'},
    'وشم بسيط': {'ar': 'وشم بسيط 🎨', 'en': 'Simple Tattoo 🎨'},
    'أكثر من وشم': {'ar': 'أكثر من وشم ✨', 'en': 'Multiple Tattoos ✨'},
  };

  InputDecoration _buildInputDecoration({
    required String hintText,
    required IconData prefixIcon,
  }) {
    return InputDecoration(
      hintText: hintText,
      hintStyle: const TextStyle(
        color: Colors.black54,
        fontSize: 13,
        fontFamily: 'Cairo',
        fontWeight: FontWeight.w500,
      ),
      prefixIcon: Icon(prefixIcon, color: const Color(0xFF0288D1), size: 18),
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.black26, width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Color(0xFF0288D1), width: 1.8),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.redAccent),
      ),
    );
  }

  Widget _buildQuestionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(top: 14, bottom: 8),
      child: Text(
        title,
        style: const TextStyle(
          color: Colors.amberAccent,
          fontSize: 15,
          fontWeight: FontWeight.bold,
          fontFamily: 'Cairo',
        ),
      ),
    );
  }

  Widget _buildSubTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(top: 10, bottom: 6),
      child: Text(
        title,
        style: const TextStyle(
          color: Colors.white70,
          fontSize: 13,
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
              ? Colors.amberAccent
              : Colors.white.withValues(alpha: 0.18),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? Colors.amber : Colors.white30,
            width: 1.5,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (isSelected) ...[
              const Icon(Icons.check_circle_rounded,
                  size: 15, color: Color(0xFF01579B)),
              const SizedBox(width: 5),
            ],
            Text(
              label,
              style: TextStyle(
                color: isSelected ? const Color(0xFF01579B) : Colors.white,
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

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
        child: Form(
          key: formKey,
          child: Container(
            padding: const EdgeInsets.all(18.0),
            decoration: BoxDecoration(
              color: Colors.black.withValues(alpha: 0.35),
              borderRadius: BorderRadius.circular(24),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.2),
                width: 1.2,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Text(
                    _isAr
                        ? 'خطوة 2: تعليمك ومظهرك الخارجي 🎓📏'
                        : 'Step 2: Education & Physical Traits 🎓📏',
                    style: const TextStyle(
                      fontSize: 19,
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // 1. المستوى التعليمي
                _buildQuestionTitle(
                  _isAr
                      ? '1. ما هو مستواك التعليمي الحالي؟'
                      : '1. What is your education degree?',
                ),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _educationDegrees.map((item) {
                    final key = item['ar']!;
                    final label = _isAr ? item['ar']! : item['en']!;
                    final isSelected = educationDegree == key;
                    return _buildCustomChip(
                      label: label,
                      isSelected: isSelected,
                      onTap: () => onEducationChanged(key),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: educationStartYearController,
                  keyboardType: TextInputType.number,
                  decoration: _buildInputDecoration(
                    hintText: _isAr ? 'سنة بدء التعليم *' : 'Education start year *',
                    prefixIcon: Icons.calendar_today_outlined,
                  ),
                  validator: (value) {
                    final year = int.tryParse((value ?? '').trim());
                    if (year == null || year < 1900 || year > DateTime.now().year) {
                      return _isAr ? 'أدخل سنة تعليم صحيحة' : 'Enter a valid education year';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 14),

                // 2. التخصص والمهنة
                _buildQuestionTitle(
                  _isAr
                      ? '2. التخصص أو المهنة والحقل العلمي'
                      : '2. University & Career Details',
                ),
                TextFormField(
                  controller: institutionController,
                  style: const TextStyle(
                    color: Colors.black87,
                    fontSize: 14,
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                  ),
                  decoration: _buildInputDecoration(
                    hintText: _isAr
                        ? 'الجامعة / التخصص *'
                        : 'University / Specialization *',
                    prefixIcon: Icons.account_balance_outlined,
                  ),
                  validator: (val) => (val == null || val.trim().isEmpty)
                      ? (_isAr ? 'مطلوب إدخال التخصص أو الجامعة' : 'Required')
                      : null,
                ),
                const SizedBox(height: 10),
                TextFormField(
                  controller: designationController,
                  style: const TextStyle(
                    color: Colors.black87,
                    fontSize: 14,
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                  ),
                  decoration: _buildInputDecoration(
                    hintText: _isAr
                        ? 'المهنة / المسمى الوظيفي *'
                        : 'Job Title / Career *',
                    prefixIcon: Icons.work_outline,
                  ),
                  validator: (val) => (val == null || val.trim().isEmpty)
                      ? (_isAr ? 'مطلوب إدخال المهنة الحالية' : 'Required')
                      : null,
                ),
                const SizedBox(height: 10),
                TextFormField(
                  controller: companyController,
                  decoration: _buildInputDecoration(
                    hintText: _isAr ? 'جهة العمل (اختياري)' : 'Company / Employer (optional)',
                    prefixIcon: Icons.business_outlined,
                  ),
                ),
                const SizedBox(height: 10),
                TextFormField(
                  controller: careerStartYearController,
                  keyboardType: TextInputType.number,
                  decoration: _buildInputDecoration(
                    hintText: _isAr ? 'سنة بدء العمل *' : 'Career start year *',
                    prefixIcon: Icons.calendar_month_outlined,
                  ),
                  validator: (value) {
                    final year = int.tryParse((value ?? '').trim());
                    if (year == null || year < 1900 || year > DateTime.now().year) {
                      return _isAr ? 'أدخل سنة عمل صحيحة' : 'Enter a valid career year';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // 3. الطول والوزن
                _buildQuestionTitle(
                  _isAr
                      ? '3. حدد طولك ووزنك بلمسة واحدة'
                      : '3. Select your height & weight',
                ),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.white24),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            _isAr ? 'الطول:' : 'Height:',
                            style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 13,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          Text(
                            '${height.round()} ${_isAr ? 'سم' : 'cm'}',
                            style: const TextStyle(
                              color: Colors.amberAccent,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                            ),
                          ),
                        ],
                      ),
                      Slider(
                        value: height.clamp(140.0, 210.0),
                        min: 140,
                        max: 210,
                        divisions: 70,
                        activeColor: Colors.amberAccent,
                        inactiveColor: Colors.white24,
                        onChanged: onHeightChanged,
                      ),
                      const Divider(color: Colors.white24),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            _isAr ? 'الوزن:' : 'Weight:',
                            style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 13,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          Text(
                            '${weight.round()} ${_isAr ? 'كغ' : 'kg'}',
                            style: const TextStyle(
                              color: Colors.amberAccent,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                            ),
                          ),
                        ],
                      ),
                      Slider(
                        value: weight.clamp(40.0, 150.0),
                        min: 40,
                        max: 150,
                        divisions: 110,
                        activeColor: Colors.amberAccent,
                        inactiveColor: Colors.white24,
                        onChanged: onWeightChanged,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // 4. خيارات الألوان
                _buildQuestionTitle(
                  _isAr
                      ? '4. اختيارات الألوان المميزة لك'
                      : '4. Select your personal features',
                ),
                _buildVisualColorSelector(
                  _isAr ? 'لون العينين' : 'Eye Color',
                  Icons.remove_red_eye_rounded,
                  _eyeColors,
                  eyeColor,
                  onEyeColorChanged,
                ),
                const SizedBox(height: 12),
                _buildVisualColorSelector(
                  _isAr ? 'لون الشعر' : 'Hair Color',
                  Icons.face_rounded,
                  _hairColors,
                  hairColor,
                  onHairColorChanged,
                ),
                const SizedBox(height: 12),
                _buildVisualColorSelector(
                  _isAr ? 'لون البشرة' : 'Skin Complexion',
                  Icons.color_lens_rounded,
                  _complexions,
                  complexion,
                  onComplexionChanged,
                ),
                const SizedBox(height: 16),

                // 5. بنية الجسد والوشم
                _buildQuestionTitle(
                  _isAr ? '5. بنية الجسد والوشم' : '5. Body Structure & Art',
                ),

                _buildSubTitle(_isAr ? '• بنية الجسد:' : '• Body Type:'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _bodyTypeOptions.entries.map((e) {
                    final label = _isAr ? e.value['ar']! : e.value['en']!;
                    return _buildCustomChip(
                      label: label,
                      isSelected: bodyType == e.key,
                      onTap: () => onBodyTypeChanged(e.key),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 12),

                _buildSubTitle(_isAr
                    ? '• الوشم والرسومات على الجسد:'
                    : '• Body Art / Tattoo:'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _bodyArtOptions.entries.map((e) {
                    final label = _isAr ? e.value['ar']! : e.value['en']!;
                    return _buildCustomChip(
                      label: label,
                      isSelected: bodyArt == e.key,
                      onTap: () => onBodyArtChanged(e.key),
                    );
                  }).toList(),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildVisualColorSelector(
    String title,
    IconData icon,
    List<Map<String, dynamic>> items,
    String selectedValue,
    ValueChanged<String> onSelected,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, color: Colors.amberAccent, size: 16),
            const SizedBox(width: 6),
            Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 13,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: items.map((e) {
            final String key = e['key'].toString();
            final String label =
                _isAr ? e['ar'].toString() : e['en'].toString();
            final Color color = e['color'] as Color;
            final bool isSelected = key == selectedValue;

            return InkWell(
              onTap: () => onSelected(key),
              borderRadius: BorderRadius.circular(20),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: isSelected
                      ? Colors.amberAccent
                      : Colors.black.withValues(alpha: 0.25),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: isSelected ? Colors.amber : Colors.white24,
                    width: 1.5,
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 14,
                      height: 14,
                      decoration: BoxDecoration(
                        color: color,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 1),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      label,
                      style: TextStyle(
                        color:
                            isSelected ? const Color(0xFF01579B) : Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                        fontFamily: 'Cairo',
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}

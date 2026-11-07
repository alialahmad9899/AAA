// ============================================================================
// الملف: lib/screens/widgets/step2_personal_details.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: المرحلة 2 - التفاصيل الشخصية والمظهر الخارجي مع الدعم الكامل
//        للغتين (عربي / إنجليزي) والتحويل الديناميكي للاتجاه (RTL / LTR).
// ============================================================================

import 'package:flutter/material.dart';
import '../../constants/colors.dart';

class Step2PersonalDetails extends StatelessWidget {
  final GlobalKey<FormState> formKey;
  final TextEditingController institutionController;
  final TextEditingController designationController;
  final TextEditingController companyController;
  final String? educationDegree;
  final double height;
  final double weight;
  final bool heightWasSelected;
  final bool weightWasSelected;
  final String eyeColor;
  final String hairColor;
  final String complexion;
  final String bodyType;
  final String bodyArt;
  final String? drink;
  final String? sunSign;
  final String? affection;
  final String? humor;
  final String lang;

  final ValueChanged<String?> onEducationChanged;
  final ValueChanged<double> onHeightChanged;
  final ValueChanged<double> onWeightChanged;
  final ValueChanged<String> onEyeColorChanged;
  final ValueChanged<String> onHairColorChanged;
  final ValueChanged<String> onComplexionChanged;
  final ValueChanged<String> onBodyTypeChanged;
  final ValueChanged<String> onBodyArtChanged;
  final ValueChanged<String?> onDrinkChanged;
  final ValueChanged<String?> onSunSignChanged;
  final ValueChanged<String?> onAffectionChanged;
  final ValueChanged<String?> onHumorChanged;

  const Step2PersonalDetails({
    super.key,
    required this.formKey,
    required this.institutionController,
    required this.designationController,
    required this.companyController,
    required this.educationDegree,
    required this.height,
    required this.weight,
    required this.heightWasSelected,
    required this.weightWasSelected,
    required this.eyeColor,
    required this.hairColor,
    required this.complexion,
    required this.bodyType,
    required this.bodyArt,
    required this.drink,
    required this.sunSign,
    required this.affection,
    required this.humor,
    this.lang = 'ar',
    required this.onEducationChanged,
    required this.onHeightChanged,
    required this.onWeightChanged,
    required this.onEyeColorChanged,
    required this.onHairColorChanged,
    required this.onComplexionChanged,
    required this.onBodyTypeChanged,
    required this.onBodyArtChanged,
    required this.onDrinkChanged,
    required this.onSunSignChanged,
    required this.onAffectionChanged,
    required this.onHumorChanged,
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
        color: AppColors.textMuted,
        fontSize: 13,
        fontFamily: 'Cairo',
        fontWeight: FontWeight.w500,
      ),
      prefixIcon: Icon(prefixIcon, color: AppColors.primaryTeal, size: 18),
      filled: true,
      fillColor: AppColors.cardColor,
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
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

  Widget _buildSubTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(top: 10, bottom: 6),
      child: Text(
        title,
        style: const TextStyle(
          color: AppColors.textMuted,
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
                        ? 'الخطوة الثانية: لنتعرّف إلى تفاصيلك ✨'
                        : 'Step 2: Education & Physical Traits 🎓📏',
                    style: const TextStyle(
                      fontSize: 19,
                      color: AppColors.textDark,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // 1. المستوى التعليمي
                _buildQuestionTitle(
                  _isAr
                      ? '1. إلى أي مستوى تعليمي وصلت؟ *'
                      : '1. What is your education degree? *',
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
                // 2. التخصص والمهنة                // 2. التخصص والمهنة
                _buildQuestionTitle(
                  _isAr
                      ? '2. ما تخصصك الدراسي أو مجالك العلمي؟'
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
                        ? 'اسم الجامعة أو التخصص *'
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
                        ? 'ما مهنتك أو مسماك الوظيفي حالياً؟ *'
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
                    hintText: _isAr ? 'اسم جهة العمل (اختياري)' : 'Company / Employer (optional)',
                    prefixIcon: Icons.business_outlined,
                  ),
                ),
                const SizedBox(height: 10),
                const SizedBox(height: 16),

                // 3. الطول والوزن                const SizedBox(height: 16),

                // 3. الطول والوزن
                _buildQuestionTitle(
                  _isAr
                      ? '3. كيف تصف طولك ووزنك؟ 📏'
                      : '3. Select your height & weight',
                ),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppColors.sectionBackground,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.lineColor),
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
                              color: AppColors.textMuted,
                              fontSize: 13,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          Text(
                            heightWasSelected
                                ? '${height.round()} ${_isAr ? 'سم' : 'cm'}'
                                : (_isAr ? 'لم يتم التحديد بعد' : 'Not selected yet'),
                            style: const TextStyle(
                              color: AppColors.accentWarm,
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
                        activeColor:
                            heightWasSelected ? AppColors.accentWarm : AppColors.lineColor,
                        inactiveColor: AppColors.lineColor,
                        onChanged: onHeightChanged,
                      ),
                      const Divider(color: AppColors.lineColor),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            _isAr ? 'الوزن:' : 'Weight:',
                            style: const TextStyle(
                              color: AppColors.textMuted,
                              fontSize: 13,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          Text(
                            weightWasSelected
                                ? '${weight.round()} ${_isAr ? 'كغ' : 'kg'}'
                                : (_isAr ? 'لم يتم التحديد بعد' : 'Not selected yet'),
                            style: const TextStyle(
                              color: AppColors.primaryTeal,
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
                        activeColor:
                            weightWasSelected ? AppColors.accentWarm : AppColors.lineColor,
                        inactiveColor: AppColors.lineColor,
                        onChanged: onWeightChanged,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // 4. خيارات الألوان
                _buildQuestionTitle(
                  _isAr
                      ? '4. ما الألوان التي تميّز ملامحك؟ *'
                      : '4. Select your personal features *',
                ),
                _buildVisualColorSelector(
                  _isAr ? 'لون العينين *' : 'Eye Color *',
                  Icons.remove_red_eye_rounded,
                  _eyeColors,
                  eyeColor,
                  onEyeColorChanged,
                ),
                const SizedBox(height: 12),
                _buildVisualColorSelector(
                  _isAr ? 'لون الشعر *' : 'Hair Color *',
                  Icons.face_rounded,
                  _hairColors,
                  hairColor,
                  onHairColorChanged,
                ),
                const SizedBox(height: 12),
                _buildVisualColorSelector(
                  _isAr ? 'لون البشرة *' : 'Skin Complexion *',
                  Icons.color_lens_rounded,
                  _complexions,
                  complexion,
                  onComplexionChanged,
                ),
                const SizedBox(height: 16),

                // 5. بنية الجسد والوشم
                _buildQuestionTitle(
                  _isAr ? '5. كيف تصف قوامك، وهل لديك وشم؟' : '5. Body Structure & Art',
                ),

                _buildSubTitle(_isAr ? '• كيف تصف بنية جسمك؟ *' : '• Body Type *:'),
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
                    ? '• هل لديك وشم أو رسومات على الجسد؟ *'
                    : '• Body Art / Tattoo *:'),
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
                const SizedBox(height: 18),
                _buildQuestionTitle(_isAr ? '6. هل تتناول الكحول؟' : '6. Do you drink? 🥤'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _buildCustomChip(
                      label: _isAr ? 'لا' : 'No',
                      isSelected: drink == 'no',
                      onTap: () => onDrinkChanged('no'),
                    ),
                    _buildCustomChip(
                      label: _isAr ? 'نعم' : 'Yes',
                      isSelected: drink == 'yes',
                      onTap: () => onDrinkChanged('yes'),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                _buildQuestionTitle(_isAr ? '7. أي برج شمسي يرافق تاريخ ميلادك؟ ♈' : '7. Your zodiac sign ♈'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: (_isAr
                      ? const ['الحمل ♈', 'الثور ♉', 'الجوزاء ♊', 'السرطان ♋', 'الأسد ♌', 'العذراء ♍', 'الميزان ♎', 'العقرب ♏', 'القوس ♐', 'الجدي ♑', 'الدلو ♒', 'الحوت ♓']
                      : const ['Aries ♈', 'Taurus ♉', 'Gemini ♊', 'Cancer ♋', 'Leo ♌', 'Virgo ♍', 'Libra ♎', 'Scorpio ♏', 'Sagittarius ♐', 'Capricorn ♑', 'Aquarius ♒', 'Pisces ♓'])
                      .map((option) => _buildCustomChip(
                            label: option,
                            isSelected: sunSign == option,
                            onTap: () => onSunSignChanged(option),
                          ))
                      .toList(),
                ),
                const SizedBox(height: 18),
                _buildQuestionTitle(_isAr ? '8. كيف تحب أن تعبّر عن المودة؟ 💖' : '8. How do you express affection? 💖'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: (_isAr
                      ? const ['عاطفي جداً وأعبر بدفء 💖', 'متوازن وأعبر في الأوقات المناسبة ✨', 'هادئ وحذر في إظهار المشاعر 🌿']
                      : const ['Very affectionate & warm 💖', 'Balanced & express at right times ✨', 'Quiet & reserved with feelings 🌿'])
                      .map((option) => _buildCustomChip(
                            label: option,
                            isSelected: affection == option,
                            onTap: () => onAffectionChanged(option),
                          ))
                      .toList(),
                ),
                const SizedBox(height: 18),
                _buildQuestionTitle(_isAr ? '9. أي وصف أقرب إلى حسّك الفكاهي؟ 😄' : '9. Describe your sense of humor 😄'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: (_isAr
                      ? const ['مرح جداً ومحب للضحك والبهجة 😄', 'متوازن ومرح في الأوقات اللطيفة 😊', 'جاد معظم الوقت وقليل المزاح 😐']
                      : const ['Very cheerful & loves laughter 😄', 'Balanced & fun in nice moments 😊', 'Serious most of the time 😐'])
                      .map((option) => _buildCustomChip(
                            label: option,
                            isSelected: humor == option,
                            onTap: () => onHumorChanged(option),
                          ))
                      .toList(),
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
            Icon(icon, color: AppColors.accentWarm, size: 16),
            const SizedBox(width: 6),
            Text(
              title,
              style: const TextStyle(
                color: AppColors.primaryTeal,
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
                    Container(
                      width: 14,
                      height: 14,
                      decoration: BoxDecoration(
                        color: color,
                        shape: BoxShape.circle,
                        border: Border.all(color: AppColors.lineColor, width: 1),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      label,
                      style: TextStyle(
                        color:
                            isSelected ? AppColors.primaryTeal : AppColors.textDark,
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

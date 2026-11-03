// ============================================================================
// الملف: lib/screens/widgets/step4_partner_expectations.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: المرحلة 4 - مواصفات الشريك مع الدعم الكامل للغتين والتأقلم مع الاتجاه.
// ============================================================================

import 'package:flutter/material.dart';

class Step4PartnerExpectations extends StatelessWidget {
  final GlobalKey<FormState> formKey;
  final TextEditingController generalController;
  final TextEditingController professionController;
  final RangeValues heightRange;
  final RangeValues weightRange;
  final bool heightRangeWasSelected;
  final bool weightRangeWasSelected;
  final String? education;
  final String? complexion;
  final String? eyeColor;
  final String? hairColor;
  final List<dynamic> languages;
  final List<dynamic> religions;
  final List<dynamic> countries;
  final List<dynamic> states;
  final String? preferredReligionId;
  final String? preferredCountryId;
  final String? preferredStateId;
  final bool isLoadingLanguages;
  final String? languageId;
  final String childrenAcceptable;
  final String smokingAcceptable;
  final String drinkingAcceptable;
  final String lang;

  final ValueChanged<RangeValues> onHeightRangeChanged;
  final ValueChanged<RangeValues> onWeightRangeChanged;
  final ValueChanged<String?> onEducationChanged;
  final ValueChanged<String?> onComplexionChanged;
  final ValueChanged<String?> onEyeColorChanged;
  final ValueChanged<String?> onHairColorChanged;
  final ValueChanged<String?> onLanguageChanged;
  final ValueChanged<String?> onPreferredReligionChanged;
  final ValueChanged<String?> onPreferredCountryChanged;
  final ValueChanged<String?> onPreferredStateChanged;
  final VoidCallback onRetryLanguages;
  final ValueChanged<String> onChildrenChanged;
  final ValueChanged<String> onSmokingChanged;
  final ValueChanged<String> onDrinkingChanged;

  const Step4PartnerExpectations({
    super.key,
    required this.formKey,
    required this.generalController,
    required this.professionController,
    required this.heightRange,
    required this.weightRange,
    required this.heightRangeWasSelected,
    required this.weightRangeWasSelected,
    required this.education,
    required this.complexion,
    required this.eyeColor,
    required this.hairColor,
    required this.languages,
    required this.religions,
    required this.countries,
    required this.states,
    required this.preferredReligionId,
    required this.preferredCountryId,
    required this.preferredStateId,
    required this.isLoadingLanguages,
    required this.languageId,
    required this.childrenAcceptable,
    required this.smokingAcceptable,
    required this.drinkingAcceptable,
    this.lang = 'ar',
    required this.onHeightRangeChanged,
    required this.onWeightRangeChanged,
    required this.onEducationChanged,
    required this.onComplexionChanged,
    required this.onEyeColorChanged,
    required this.onHairColorChanged,
    required this.onLanguageChanged,
    required this.onPreferredReligionChanged,
    required this.onPreferredCountryChanged,
    required this.onPreferredStateChanged,
    required this.onRetryLanguages,
    required this.onChildrenChanged,
    required this.onSmokingChanged,
    required this.onDrinkingChanged,
  });

  // Labels are bilingual; the submitted value remains the real member_languages id.
  static const Map<String, String> _bilingualLanguageLabels = {
    'arabic': 'العربية / Arabic',
    'العربية': 'العربية / Arabic',
    'english': 'الإنجليزية / English',
    'الإنجليزية': 'الإنجليزية / English',
    'french': 'الفرنسية / French',
    'français': 'الفرنسية / French',
    'الفرنسية': 'الفرنسية / French',
    'turkish': 'التركية / Turkish',
    'türkçe': 'التركية / Turkish',
    'التركية': 'التركية / Turkish',
    'german': 'الألمانية / German',
    'deutsch': 'الألمانية / German',
    'الألمانية': 'الألمانية / German',
    'spanish': 'الإسبانية / Spanish',
    'español': 'الإسبانية / Spanish',
    'الإسبانية': 'الإسبانية / Spanish',
    'russian': 'الروسية / Russian',
    'русский': 'الروسية / Russian',
    'الروسية': 'الروسية / Russian',
    'kurdish': 'الكردية / Kurdish',
    'کوردی': 'الكردية / Kurdish',
    'الكردية': 'الكردية / Kurdish',
    'persian': 'الفارسية / Persian',
    'farsi': 'الفارسية / Persian',
    'فارسی': 'الفارسية / Persian',
    'الفارسية': 'الفارسية / Persian',
    'urdu': 'الأردية / Urdu',
    'اردو': 'الأردية / Urdu',
    'الأردية': 'الأردية / Urdu',
    'hindi': 'الهندية / Hindi',
    'हिन्दी': 'الهندية / Hindi',
    'الهندية': 'الهندية / Hindi',
    'italian': 'الإيطالية / Italian',
    'italiano': 'الإيطالية / Italian',
    'الإيطالية': 'الإيطالية / Italian',
    'chinese': 'الصينية / Chinese',
    '中文': 'الصينية / Chinese',
    'الصينية': 'الصينية / Chinese',
    'japanese': 'اليابانية / Japanese',
    '日本語': 'اليابانية / Japanese',
    'اليابانية': 'اليابانية / Japanese',
    'portuguese': 'البرتغالية / Portuguese',
    'português': 'البرتغالية / Portuguese',
    'البرتغالية': 'البرتغالية / Portuguese',
    'dutch': 'الهولندية / Dutch',
    'nederlands': 'الهولندية / Dutch',
    'الهولندية': 'الهولندية / Dutch',
    'greek': 'اليونانية / Greek',
    'ελληνικά': 'اليونانية / Greek',
    'اليونانية': 'اليونانية / Greek',
  };

  String _languageLabel(Map language) {
    final rawName = (language['name'] ??
            language['name_en'] ??
            language['name_ar'] ??
            '')
        .toString()
        .trim();
    if (rawName.isEmpty) return '';
    return _bilingualLanguageLabels[rawName.toLowerCase()] ?? rawName;
  }

  bool get _isAr => lang == 'ar';

  static const List<Map<String, String>> _educationDegrees = [
    {'ar': 'إعدادية', 'en': 'Preparatory'},
    {'ar': 'ثانوية', 'en': 'Secondary'},
    {'ar': 'دبلوم', 'en': 'Diploma'},
    {'ar': 'بكالوريوس', 'en': 'Bachelor'},
    {'ar': 'ماجستير', 'en': 'Master'},
    {'ar': 'دكتوراه', 'en': 'Doctorate'},
    {'ar': 'لا يهم', 'en': "Doesn't matter"},
  ];

  static const List<Map<String, String>> _complexions = [
    {'ar': 'أبيض', 'en': 'Fair / White'},
    {'ar': 'حنطي فاتح', 'en': 'Light Tan'},
    {'ar': 'حنطي', 'en': 'Wheat / Tan'},
    {'ar': 'أسمر', 'en': 'Dark Tan'},
    {'ar': 'أسمر داكن', 'en': 'Dark'},
    {'ar': 'لا يهم', 'en': "Doesn't matter"},
  ];

  static const List<Map<String, String>> _eyeColors = [
    {'ar': 'بني', 'en': 'Brown'},
    {'ar': 'أسود', 'en': 'Black'},
    {'ar': 'أزرق', 'en': 'Blue'},
    {'ar': 'أخضر', 'en': 'Green'},
    {'ar': 'عسلي', 'en': 'Hazel'},
    {'ar': 'رمادي', 'en': 'Grey'},
    {'ar': 'لا يهم', 'en': "Doesn't matter"},
  ];

  static const List<Map<String, String>> _hairColors = [
    {'ar': 'أسود', 'en': 'Black'},
    {'ar': 'بني داكن', 'en': 'Dark Brown'},
    {'ar': 'بني فاتح', 'en': 'Light Brown'},
    {'ar': 'أشقر', 'en': 'Blonde'},
    {'ar': 'أحمر', 'en': 'Red'},
    {'ar': 'رمادي / شيب', 'en': 'Grey / White'},
    {'ar': 'لا يهم', 'en': "Doesn't matter"},
  ];

  /// دالة حماية لتقييد قيم [RangeValues] بداخل النطاق المسموح وتجنب انهيار الواجهة
  RangeValues _clampRange(RangeValues range, double min, double max) {
    double start = range.start.clamp(min, max);
    double end = range.end.clamp(min, max);
    if (start > end) {
      final temp = start;
      start = end;
      end = temp;
    }
    return RangeValues(start, end);
  }

  InputDecoration _buildInputDecoration({
    required String hintText,
    required IconData icon,
  }) {
    return InputDecoration(
      hintText: hintText,
      hintStyle: const TextStyle(
        color: Colors.black54,
        fontSize: 13,
        fontFamily: 'Cairo',
        fontWeight: FontWeight.w500,
      ),
      prefixIcon: Icon(icon, color: const Color(0xFF0288D1), size: 18),
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
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Colors.redAccent, width: 1.8),
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Text(
        title,
        style: const TextStyle(
          color: Colors.amberAccent,
          fontSize: 14.5,
          fontWeight: FontWeight.bold,
          fontFamily: 'Cairo',
        ),
      ),
    );
  }

  Color _colorForOption(String value) {
    final normalized = value.toLowerCase();
    if (normalized.contains('أبيض') || normalized.contains('white') || normalized.contains('fair')) return const Color(0xFFF3D5C0);
    if (normalized.contains('حنطي فاتح') || normalized.contains('light tan')) return const Color(0xFFE5B98F);
    if (normalized.contains('حنطي') || normalized.contains('wheat') || normalized.contains('tan')) return const Color(0xFFC68A5B);
    if (normalized.contains('أسمر داكن') || normalized.contains('dark')) return const Color(0xFF70452F);
    if (normalized.contains('أسمر') || normalized.contains('olive')) return const Color(0xFF9A6344);
    if (normalized.contains('أزرق') || normalized.contains('blue')) return const Color(0xFF3B82F6);
    if (normalized.contains('أخضر') || normalized.contains('green')) return const Color(0xFF16A34A);
    if (normalized.contains('عسلي') || normalized.contains('hazel')) return const Color(0xFFB77936);
    if (normalized.contains('بني') || normalized.contains('brown')) return const Color(0xFF75452B);
    if (normalized.contains('أسود') || normalized.contains('black')) return const Color(0xFF18181B);
    if (normalized.contains('أشقر') || normalized.contains('blonde')) return const Color(0xFFE7C66B);
    if (normalized.contains('أحمر') || normalized.contains('red')) return const Color(0xFFB7473A);
    if (normalized.contains('رمادي') || normalized.contains('grey') || normalized.contains('gray') || normalized.contains('شيب')) return const Color(0xFF9CA3AF);
    return const Color(0xFF38BDF8);
  }

  Widget _buildColorDot(String value) {
    return Container(
      width: 13,
      height: 13,
      decoration: BoxDecoration(
        color: _colorForOption(value),
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white.withValues(alpha: 0.9), width: 1),
      ),
    );
  }

  Widget _buildCustomChip({
    required String label,
    required bool isSelected,
    required VoidCallback onTap,
    Widget? leading,
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
            if (leading != null) ...[
              leading,
              const SizedBox(width: 6),
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
    final safeHeightRange = _clampRange(heightRange, 140, 210);
    final safeWeightRange = _clampRange(weightRange, 40, 140);
    final languageItems = languages.whereType<Map>().map((language) {
      final id = (language['id'] ?? '').toString().trim();
      final label = _languageLabel(language);
      if (id.isEmpty || label.isEmpty) return null;
      return DropdownMenuItem<String>(
        value: id,
        child: Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          textDirection: TextDirection.rtl,
          style: const TextStyle(
            color: Colors.black87,
            fontFamily: 'Cairo',
            fontWeight: FontWeight.w600,
          ),
        ),
      );
    }).whereType<DropdownMenuItem<String>>().toList();
    final safeLanguageId =
        languageItems.any((item) => item.value == languageId) ? languageId : null;
    List<DropdownMenuItem<String>> makeItems(List<dynamic> values) =>
        values.whereType<Map>().map((item) {
          final id = (item['id'] ?? '').toString();
          final name = (item['name_ar'] ?? item['name'] ?? item['name_en'] ?? '').toString();
          if (id.isEmpty || name.trim().isEmpty) return null;
          return DropdownMenuItem<String>(
            value: id,
            child: Text(name, maxLines: 1, overflow: TextOverflow.ellipsis),
          );
        }).whereType<DropdownMenuItem<String>>().toList();
    final religionItems = makeItems(religions);
    final countryItems = makeItems(countries);
    final stateItems = makeItems(states);

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
                        ? 'خطوة 4: مواصفات وتوقعات الشريك ✨'
                        : 'Step 4: Partner Expectations ✨',
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                      fontFamily: 'Cairo',
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                Center(
                  child: Text(
                    _isAr
                        ? 'كيف تتخيل شريك حياتك القادم؟ حدد تفضيلاتك وجميع خياراتك'
                        : 'Describe your ideal match with simple options',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 12.5,
                      color: Colors.white70,
                      fontFamily: 'Cairo',
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                // 1. نبذة عامة
                _buildSectionTitle(_isAr
                    ? '1. نبذة عامة وصفات تتمنى توفرها ✍️'
                    : '1. General Qualities ✍️'),
                TextFormField(
                  controller: generalController,
                  maxLines: 3,
                  style: const TextStyle(
                    color: Colors.black87,
                    fontSize: 14,
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                  ),
                  decoration: _buildInputDecoration(
                    hintText: _isAr
                        ? 'اكتب نبذة عن الأخلاق والتطلعات التي تهمك في شريك حياتك...'
                        : 'Describe ideal traits, morals, and lifestyle...',
                    icon: Icons.edit_note_rounded,
                  ),
                ),
                const SizedBox(height: 20),

                // 2. نطاق الطول
                _buildSectionTitle(_isAr
                    ? '2. نطاق الطول المطلوب (سم) 📏 *'
                    : '2. Preferred Height Range (cm) 📏 *'),
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
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Text(
                              _isAr
                                  ? (heightRangeWasSelected
                                      ? 'من: ${safeHeightRange.start.round()} سم'
                                      : 'لم يتم تحديد نطاق الطول بعد')
                                  : (heightRangeWasSelected
                                      ? 'From: ${safeHeightRange.start.round()} cm'
                                      : 'Height range not selected yet'),
                              softWrap: true,
                              textAlign: TextAlign.start,
                              style: const TextStyle(
                                color: Colors.amberAccent,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                                fontSize: 12,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _isAr
                                  ? (heightRangeWasSelected
                                      ? 'إلى: ${safeHeightRange.end.round()} سم'
                                      : 'حرّك المؤشر لاختيار النطاق')
                                  : (heightRangeWasSelected
                                      ? 'To: ${safeHeightRange.end.round()} cm'
                                      : 'Move the slider to choose a range'),
                              softWrap: true,
                              textAlign: TextAlign.end,
                              style: const TextStyle(
                                color: Colors.amberAccent,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ],
                      ),
                      SliderTheme(
                        data: SliderTheme.of(context).copyWith(
                          trackHeight: 4,
                          thumbShape: const RoundSliderThumbShape(
                              enabledThumbRadius: 8),
                        ),
                        child: RangeSlider(
                          values: safeHeightRange,
                          min: 140,
                          max: 210,
                          divisions: 70,
                          activeColor: heightRangeWasSelected
                              ? Colors.amberAccent
                              : Colors.white24,
                          inactiveColor: Colors.white24,
                          onChanged: onHeightRangeChanged,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),

                // 3. نطاق الوزن
                _buildSectionTitle(_isAr
                    ? '3. نطاق الوزن المطلوب (كغ) ⚖️ *'
                    : '3. Preferred Weight Range (kg) ⚖️ *'),
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
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Text(
                              _isAr
                                  ? (weightRangeWasSelected
                                      ? 'من: ${safeWeightRange.start.round()} كغ'
                                      : 'لم يتم تحديد نطاق الوزن بعد')
                                  : (weightRangeWasSelected
                                      ? 'From: ${safeWeightRange.start.round()} kg'
                                      : 'Weight range not selected yet'),
                              softWrap: true,
                              textAlign: TextAlign.start,
                              style: const TextStyle(
                                color: Colors.amberAccent,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                                fontSize: 12,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _isAr
                                  ? (weightRangeWasSelected
                                      ? 'إلى: ${safeWeightRange.end.round()} كغ'
                                      : 'حرّك المؤشر لاختيار النطاق')
                                  : (weightRangeWasSelected
                                      ? 'To: ${safeWeightRange.end.round()} kg'
                                      : 'Move the slider to choose a range'),
                              softWrap: true,
                              textAlign: TextAlign.end,
                              style: const TextStyle(
                                color: Colors.amberAccent,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ],
                      ),
                      SliderTheme(
                        data: SliderTheme.of(context).copyWith(
                          trackHeight: 4,
                          thumbShape: const RoundSliderThumbShape(
                              enabledThumbRadius: 8),
                        ),
                        child: RangeSlider(
                          values: safeWeightRange,
                          min: 40,
                          max: 140,
                          divisions: 100,
                          activeColor: weightRangeWasSelected
                              ? Colors.amberAccent
                              : Colors.white24,
                          inactiveColor: Colors.white24,
                          onChanged: onWeightRangeChanged,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // 4. المستوى التعليمي المفضل
                _buildSectionTitle(_isAr
                    ? '4. المؤهل العلمي المفضل 🎓 *'
                    : '4. Education Level 🎓 *'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _educationDegrees.map((item) {
                    final key = item['ar']!;
                    final label = _isAr ? item['ar']! : item['en']!;
                    final isSelected = education == key;
                    return _buildCustomChip(
                      label: label,
                      isSelected: isSelected,
                      onTap: () => onEducationChanged(key),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 18),

                // 5. لون البشرة المفضل
                _buildSectionTitle(_isAr
                    ? '5. لون البشرة المفضل 🎨 *'
                    : '5. Preferred Complexion 🎨 *'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _complexions.map((item) {
                    final key = item['ar']!;
                    final label = _isAr ? item['ar']! : item['en']!;
                    final isSelected = complexion == key;
                    return _buildCustomChip(
                      label: label,
                      isSelected: isSelected,
                      leading: key == 'لا يهم' ? null : _buildColorDot(key),
                      onTap: () => onComplexionChanged(key),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 18),

                // 6. لون العينين المفضل
                _buildSectionTitle(_isAr
                    ? '6. لون العينين المفضل 👁️ *'
                    : '6. Preferred Eye Color 👁️ *'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _eyeColors.map((item) {
                    final key = item['ar']!;
                    final label = _isAr ? item['ar']! : item['en']!;
                    return _buildCustomChip(
                      label: label,
                      isSelected: eyeColor == key,
                      leading: key == 'لا يهم' ? null : _buildColorDot(key),
                      onTap: () => onEyeColorChanged(key),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 18),

                // 7. لون الشعر المفضل
                _buildSectionTitle(_isAr
                    ? '7. لون الشعر المفضل 💇 *'
                    : '7. Preferred Hair Color 💇 *'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _hairColors.map((item) {
                    final key = item['ar']!;
                    final label = _isAr ? item['ar']! : item['en']!;
                    return _buildCustomChip(
                      label: label,
                      isSelected: hairColor == key,
                      leading: key == 'لا يهم' ? null : _buildColorDot(key),
                      onTap: () => onHairColorChanged(key),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 18),

                // 8. اللغة المفضلة للشريك
                _buildSectionTitle(_isAr
                    ? '8. اللغة المفضلة للشريك *'
                    : '8. Preferred Partner Language *'),
                if (isLoadingLanguages)
                  const Center(
                    child: CircularProgressIndicator(color: Colors.amberAccent),
                  )
                else if (languageItems.isEmpty)
                  Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: TextButton.icon(
                      onPressed: onRetryLanguages,
                      icon: const Icon(Icons.refresh_rounded),
                      label: Text(
                        _isAr
                            ? 'تعذر تحميل قائمة اللغات. اضغط لإعادة المحاولة'
                            : 'Language list could not be loaded. Tap to retry',
                        style: const TextStyle(fontFamily: 'Cairo'),
                      ),
                    ),
                  )
                else
                  DropdownButtonFormField<String>(
                    key: ValueKey('partner-language-$safeLanguageId'),
                    initialValue: safeLanguageId,
                    isExpanded: true,
                    decoration: _buildInputDecoration(
                      hintText: _isAr ? 'اختر اللغة المفضلة' : 'Select preferred language',
                      icon: Icons.translate_rounded,
                    ),
                    items: languageItems,
                    onChanged: onLanguageChanged,
                    validator: (value) => value == null || value.isEmpty
                        ? (_isAr ? 'يرجى اختيار لغة الشريك' : 'Select a language')
                        : null,
                  ),
                const SizedBox(height: 18),

                                if (religionItems.isNotEmpty) ...[
                  _buildSectionTitle(_isAr ? 'الديانة المفضلة للشريك' : 'Preferred Partner Religion'),
                  DropdownButtonFormField<String>(
                    key: ValueKey('partner-religion-$preferredReligionId'),
                    initialValue: religionItems.any((e) => e.value == preferredReligionId) ? preferredReligionId : null,
                    isExpanded: true,
                    decoration: _buildInputDecoration(hintText: _isAr ? 'اختر الديانة' : 'Select religion', icon: Icons.auto_awesome_rounded),
                    items: religionItems,
                    onChanged: onPreferredReligionChanged,
                  ),
                  const SizedBox(height: 16),
                ],
                if (countryItems.isNotEmpty) ...[
                  _buildSectionTitle(_isAr ? 'بلد الشريك المفضل' : 'Preferred Partner Country'),
                  DropdownButtonFormField<String>(
                    key: ValueKey('partner-country-$preferredCountryId'),
                    initialValue: countryItems.any((e) => e.value == preferredCountryId) ? preferredCountryId : null,
                    isExpanded: true,
                    decoration: _buildInputDecoration(hintText: _isAr ? 'اختر البلد' : 'Select country', icon: Icons.public_rounded),
                    items: countryItems,
                    onChanged: onPreferredCountryChanged,
                  ),
                  const SizedBox(height: 16),
                ],
                if (stateItems.isNotEmpty) ...[
                  _buildSectionTitle(_isAr ? 'المحافظة المفضلة للشريك' : 'Preferred Partner Governorate'),
                  DropdownButtonFormField<String>(
                    key: ValueKey('partner-state-$preferredStateId'),
                    initialValue: stateItems.any((e) => e.value == preferredStateId) ? preferredStateId : null,
                    isExpanded: true,
                    decoration: _buildInputDecoration(hintText: _isAr ? 'اختر المحافظة' : 'Select governorate', icon: Icons.location_city_rounded),
                    items: stateItems,
                    onChanged: onPreferredStateChanged,
                  ),
                  const SizedBox(height: 16),
                ],
// 9. مهنة الشريك
                _buildSectionTitle(_isAr
                    ? '6. مجال العمل أو المهنة المفضلة 💼'
                    : '6. Preferred Career 💼'),
                TextFormField(
                  controller: professionController,
                  style: const TextStyle(
                    color: Colors.black87,
                    fontSize: 14,
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                  ),
                  decoration: _buildInputDecoration(
                    hintText: _isAr
                        ? 'مثال: مهندس، طبيب، أستاذ، لا يهم...'
                        : 'e.g. Engineer, Doctor, Teacher...',
                    icon: Icons.work_outline,
                  ),
                ),
                const SizedBox(height: 20),

                // 7. قبول وجود أطفال
                _buildSectionTitle(_isAr
                    ? '10. قبول وجود أطفال من زواج سابق 👶 *'
                    : '10. Accept Children? 👶 *'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _buildCustomChip(
                      label: _isAr ? 'لا يهم' : 'Does not matter',
                      isSelected: childrenAcceptable == 'dose_not_matter',
                      onTap: () => onChildrenChanged('dose_not_matter'),
                    ),
                    _buildCustomChip(
                      label: _isAr ? 'نعم، أقبل 👍' : 'Yes',
                      isSelected: childrenAcceptable == 'yes',
                      onTap: () => onChildrenChanged('yes'),
                    ),
                    _buildCustomChip(
                      label: _isAr ? 'لا أقبل ✋' : 'No',
                      isSelected: childrenAcceptable == 'no',
                      onTap: () => onChildrenChanged('no'),
                    ),
                  ],
                ),
                const SizedBox(height: 18),

                // 8. موقف التدخين
                _buildSectionTitle(
                    _isAr ? '11. موقف التدخين 🚬 *' : '11. Accept Smoking? 🚬 *'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _buildCustomChip(
                      label: _isAr ? 'لا يهم' : 'Does not matter',
                      isSelected: smokingAcceptable == 'dose_not_matter',
                      onTap: () => onSmokingChanged('dose_not_matter'),
                    ),
                    _buildCustomChip(
                      label: _isAr ? 'لا أقبل التدخين ✋' : 'No Smoking',
                      isSelected: smokingAcceptable == 'no',
                      onTap: () => onSmokingChanged('no'),
                    ),
                    _buildCustomChip(
                      label: _isAr ? 'أقبل التدخين 👍' : 'Accept Smoking',
                      isSelected: smokingAcceptable == 'yes',
                      onTap: () => onSmokingChanged('yes'),
                    ),
                  ],
                ),
                const SizedBox(height: 18),

                // 9. موقف الكحول / المشروبات
                _buildSectionTitle(_isAr
                    ? '12. موقف تناول الكحول والمشروبات 🍷 *'
                    : '12. Accept Alcohol? 🍷 *'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _buildCustomChip(
                      label: _isAr ? 'لا يهم' : 'Does not matter',
                      isSelected: drinkingAcceptable == 'dose_not_matter',
                      onTap: () => onDrinkingChanged('dose_not_matter'),
                    ),
                    _buildCustomChip(
                      label: _isAr ? 'لا أقبل ✋' : 'No Alcohol',
                      isSelected: drinkingAcceptable == 'no',
                      onTap: () => onDrinkingChanged('no'),
                    ),
                    _buildCustomChip(
                      label: _isAr ? 'أقبل 👍' : 'Accept',
                      isSelected: drinkingAcceptable == 'yes',
                      onTap: () => onDrinkingChanged('yes'),
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
}

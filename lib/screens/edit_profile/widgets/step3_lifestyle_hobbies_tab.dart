// ============================================================================
// اسم الملف: lib/screens/edit_profile/widgets/step3_lifestyle_hobbies_tab.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: التبويب الثالث لتعديل نمط الحياة، الاهتمامات، الهوايات، الأبراج، والسلوك
//        مع دعم كامل ثنائي اللغة (عربي / إنجليزي) ومطابقة خيارات ذكية.
// ============================================================================

import 'package:flutter/material.dart';

class Step3LifestyleHobbiesTab extends StatelessWidget {
  final bool isAr;
  final String? selectedSunSign;
  final String? selectedLivingWith;
  final String? selectedSmoke;
  final String? selectedDrink;
  final String? selectedHobby;
  final String? selectedInterest;
  final String? selectedMusic;
  final String? selectedBook;
  final String? selectedMovie;
  final String? selectedSport;
  final String? selectedDressStyle;
  final String? selectedAffection;
  final String? selectedHumor;

  final List<String> sunSignsKeys;
  final List<String> livingWithOptions;
  final List<String> hobbiesOptions;
  final List<String> interestsOptions;
  final List<String> musicOptions;
  final List<String> booksOptions;
  final List<String> moviesOptions;
  final List<String> sportsOptions;
  final List<String> dressStylesOptions;
  final List<String> affectionOptions;
  final List<String> humorOptions;

  final ValueChanged<String> onSelectSunSign;
  final ValueChanged<String> onSelectLivingWith;
  final ValueChanged<String> onSelectSmoke;
  final ValueChanged<String> onSelectDrink;
  final ValueChanged<String> onSelectHobby;
  final ValueChanged<String> onSelectInterest;
  final ValueChanged<String> onSelectMusic;
  final ValueChanged<String> onSelectBook;
  final ValueChanged<String> onSelectMovie;
  final ValueChanged<String> onSelectSport;
  final ValueChanged<String> onSelectDressStyle;
  final ValueChanged<String> onSelectAffection;
  final ValueChanged<String> onSelectHumor;

  const Step3LifestyleHobbiesTab({
    super.key,
    required this.isAr,
    required this.selectedSunSign,
    required this.selectedLivingWith,
    required this.selectedSmoke,
    required this.selectedDrink,
    required this.selectedHobby,
    required this.selectedInterest,
    required this.selectedMusic,
    required this.selectedBook,
    required this.selectedMovie,
    required this.selectedSport,
    required this.selectedDressStyle,
    required this.selectedAffection,
    required this.selectedHumor,
    required this.sunSignsKeys,
    required this.livingWithOptions,
    required this.hobbiesOptions,
    required this.interestsOptions,
    required this.musicOptions,
    required this.booksOptions,
    required this.moviesOptions,
    required this.sportsOptions,
    required this.dressStylesOptions,
    required this.affectionOptions,
    required this.humorOptions,
    required this.onSelectSunSign,
    required this.onSelectLivingWith,
    required this.onSelectSmoke,
    required this.onSelectDrink,
    required this.onSelectHobby,
    required this.onSelectInterest,
    required this.onSelectMusic,
    required this.onSelectBook,
    required this.onSelectMovie,
    required this.onSelectSport,
    required this.onSelectDressStyle,
    required this.onSelectAffection,
    required this.onSelectHumor,
  });

  /// 🟢 خوارزمية ذكية لمطابقة القيمة المختارة مع زر الاختيار بصرف النظر عن لغة العرض
  bool _isOptionSelected(String option, String? selectedValue) {
    if (selectedValue == null || selectedValue.trim().isEmpty) return false;
    if (selectedValue == option) return true;

    final String cleanSelected = selectedValue
        .replaceAll(RegExp(r'[^\u0600-\u06FFa-zA-Z0-9]'), '')
        .toLowerCase()
        .trim();
    final String cleanOption = option
        .replaceAll(RegExp(r'[^\u0600-\u06FFa-zA-Z0-9]'), '')
        .toLowerCase()
        .trim();

    if (cleanSelected == cleanOption) return true;

    // قاموس المترادفات للخيارات الثابتة مثل التدخين لضمان التطابق عبر اللغتين
    const Map<String, List<String>> equivalents = {
      'non_smoker': ['لاأدخنأبداً', 'nonsmoker', 'no', 'لاأدخن'],
      'smoker': ['نعمدخن', 'أدخن', 'smoker', 'yes'],
      'occasional': [
        'أحياناًأرجيلةفيب',
        'أحياناً',
        'occasionally',
        'sometimes'
      ],
    };

    for (final entry in equivalents.entries) {
      final list = entry.value;
      final bool selectedMatches = list.any((item) =>
          cleanSelected.contains(item) || item.contains(cleanSelected));
      final bool optionMatches = list.any(
          (item) => cleanOption.contains(item) || item.contains(cleanOption));
      if (selectedMatches && optionMatches) return true;
    }

    return false;
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
            child: Icon(icon, color: Colors.white, size: 22),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 17,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChoiceChips({
    required String title,
    required IconData icon,
    required List<String> options,
    required String? selectedValue,
    required ValueChanged<String> onSelected,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader(title, icon),
        Directionality(
          textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
          child: Wrap(
            spacing: 8.0,
            runSpacing: 10.0,
            children: options.map((option) {
              final bool isSelected = _isOptionSelected(option, selectedValue);

              return ChoiceChip(
                label: Text(
                  option,
                  style: TextStyle(
                    color: isSelected ? const Color(0xFF01579B) : Colors.white,
                    fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                    fontFamily: 'Cairo',
                    fontSize: 14,
                  ),
                ),
                selected: isSelected,
                color: WidgetStateProperty.resolveWith<Color?>((states) {
                  if (states.contains(WidgetState.selected)) {
                    return Colors.white;
                  }
                  return const Color(0xFF013966).withValues(alpha: 0.7);
                }),
                elevation: isSelected ? 2 : 0,
                pressElevation: 1,
                shadowColor: Colors.black26,
                showCheckmark: isSelected,
                checkmarkColor: const Color(0xFF01579B),
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                  side: BorderSide.none,
                ),
                onSelected: (bool selected) {
                  if (selected) {
                    onSelected(option);
                  }
                },
              );
            }).toList(),
          ),
        ),
        const SizedBox(height: 16),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Container(
        padding: const EdgeInsets.only(
            left: 20.0, right: 20.0, top: 20.0, bottom: 80.0),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.18),
          borderRadius: BorderRadius.circular(28),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildChoiceChips(
              title: isAr ? 'البرج الشمسي ♈' : 'Zodiac Sign ♈',
              icon: Icons.bedtime_rounded,
              options: sunSignsKeys,
              selectedValue: selectedSunSign,
              onSelected: onSelectSunSign,
            ),
            _buildChoiceChips(
              title: isAr ? 'مع من تتشارك سكنك حالياً؟ 🏡' : 'Living With 🏡',
              icon: Icons.groups_rounded,
              options: livingWithOptions,
              selectedValue: selectedLivingWith,
              onSelected: onSelectLivingWith,
            ),
            _buildChoiceChips(
              title: isAr ? 'ما هي علاقتك بالتدخين؟ 🚬' : 'Smoking Habits 🚬',
              icon: Icons.smoking_rooms_rounded,
              options: [
                isAr ? 'لا أدخن أبداً 🌿' : 'Non-smoker 🌿',
                isAr ? 'نعم، أدخن 🚬' : 'Smoker 🚬',
                isAr ? 'أحياناً (أرجيلة / فيب) 💨' : 'Occasionally 💨',
              ],
              selectedValue: selectedSmoke,
              onSelected: onSelectSmoke,
            ),
            _buildChoiceChips(
              title: isAr ? 'هل تتناول المشروبات؟ 🥤' : 'Do you drink? 🥤',
              icon: Icons.local_drink_rounded,
              options: [isAr ? 'لا' : 'No', isAr ? 'نعم' : 'Yes'],
              selectedValue: selectedDrink == 'no'
                  ? (isAr ? 'لا' : 'No')
                  : selectedDrink == 'yes'
                      ? (isAr ? 'نعم' : 'Yes')
                      : selectedDrink,
              onSelected: (value) => onSelectDrink(
                value == (isAr ? 'لا' : 'No') ? 'no' : 'yes',
              ),
            ),
            _buildChoiceChips(
              title: isAr ? 'كيف تحب قضاء وقت فراغك؟ 🎨' : 'Hobbies 🎨',
              icon: Icons.favorite_rounded,
              options: hobbiesOptions,
              selectedValue: selectedHobby,
              onSelected: onSelectHobby,
            ),
            _buildChoiceChips(
              title: isAr ? 'ما هو مجال اهتماماتك وشغفك؟ 💡' : 'Interests 💡',
              icon: Icons.lightbulb_outline_rounded,
              options: interestsOptions,
              selectedValue: selectedInterest,
              onSelected: onSelectInterest,
            ),
            _buildChoiceChips(
              title: isAr ? 'الموسيقى المفضلة 🎧' : 'Preferred Music 🎧',
              icon: Icons.music_note_rounded,
              options: musicOptions,
              selectedValue: selectedMusic,
              onSelected: onSelectMusic,
            ),
            _buildChoiceChips(
              title: isAr ? 'الكتب والقراءة 📖' : 'Books & Reading 📖',
              icon: Icons.menu_book_rounded,
              options: booksOptions,
              selectedValue: selectedBook,
              onSelected: onSelectBook,
            ),
            _buildChoiceChips(
              title: isAr ? 'الأفلام المفضلة 🎬' : 'Movies 🎬',
              icon: Icons.movie_rounded,
              options: moviesOptions,
              selectedValue: selectedMovie,
              onSelected: onSelectMovie,
            ),
            _buildChoiceChips(
              title: isAr ? 'الرياضة المفضلة ⚽' : 'Sports ⚽',
              icon: Icons.sports_soccer_rounded,
              options: sportsOptions,
              selectedValue: selectedSport,
              onSelected: onSelectSport,
            ),
            _buildChoiceChips(
              title: isAr ? 'أسلوب اللباس المفضل 👔' : 'Dress Style 👔',
              icon: Icons.checkroom_rounded,
              options: dressStylesOptions,
              selectedValue: selectedDressStyle,
              onSelected: onSelectDressStyle,
            ),
            _buildChoiceChips(
              title: isAr
                  ? 'كيف تعبر عن مشاعرك عادةً؟ 💖'
                  : 'Expressing Affection 💖',
              icon: Icons.sentiment_satisfied_rounded,
              options: affectionOptions,
              selectedValue: selectedAffection,
              onSelected: onSelectAffection,
            ),
            _buildChoiceChips(
              title: isAr ? 'طبيعة حس الفكاهة لديك 😄' : 'Sense of Humor 😄',
              icon: Icons.insert_emoticon_rounded,
              options: humorOptions,
              selectedValue: selectedHumor,
              onSelected: onSelectHumor,
            ),
          ],
        ),
      ),
    );
  }
}

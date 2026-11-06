// ============================================================================
// اسم الملف: lib/screens/edit_profile/widgets/step2_residency_spiritual_tab.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: التبويب الثاني لتعديل الإقامة، بلد المنشأ، المحافظات، والديانة
//        مع دعم كامل ومزدوج للغتين العربية والإنجليزية والجلب الديناميكي.
// ============================================================================

import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';

class Step2ResidencySpiritualTab extends StatelessWidget {
  final bool isAr;
  final String? selectedBirthCountryId;
  final String? selectedResidencyCountryId;
  final String? selectedResidencyStateId;
  final String? selectedResidencyCityId;
  final String? selectedReligionId;
  final String? selectedFamilyValueId;
  final TextEditingController immigrationStatusCtrl;
  final List<dynamic> countries;
  final List<dynamic> residencyStates;
  final List<dynamic> residencyCities;
  final List<dynamic> familyValues;
  final bool isLoadingResidencyStates;
  final bool isLoadingResidencyCities;
  final VoidCallback onSelectBirthCountry;
  final VoidCallback onSelectResidencyCountry;
  final VoidCallback onSelectState;
  final VoidCallback onSelectCity;
  final VoidCallback onSelectReligion;
  final VoidCallback onSelectFamilyValue;
  final ValueChanged<String> onImmigrationStatusChanged;
  final String Function(dynamic data, bool isAr) getLocalizedCityName;
  final String Function(dynamic country) getCountryFlagUrl;

  const Step2ResidencySpiritualTab({
    super.key,
    required this.isAr,
    required this.selectedBirthCountryId,
    required this.selectedResidencyCountryId,
    required this.selectedResidencyStateId,
    required this.selectedResidencyCityId,
    required this.selectedReligionId,
    required this.selectedFamilyValueId,
    required this.immigrationStatusCtrl,
    required this.countries,
    required this.residencyStates,
    required this.residencyCities,
    required this.familyValues,
    required this.isLoadingResidencyStates,
    required this.isLoadingResidencyCities,
    required this.onSelectBirthCountry,
    required this.onSelectResidencyCountry,
    required this.onSelectState,
    required this.onSelectCity,
    required this.onSelectReligion,
    required this.onSelectFamilyValue,
    required this.onImmigrationStatusChanged,
    required this.getLocalizedCityName,
    required this.getCountryFlagUrl,
  });

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

  Widget _buildSheetPickerTile({
    required String label,
    required String? displayValue,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
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
                displayValue != null && displayValue.isNotEmpty
                    ? displayValue
                    : label,
                style: TextStyle(
                  color: displayValue != null && displayValue.isNotEmpty
                      ? Colors.white
                      : Colors.white70,
                  fontSize: 16,
                  fontFamily: 'Cairo',
                  fontWeight: displayValue != null && displayValue.isNotEmpty
                      ? FontWeight.bold
                      : FontWeight.normal,
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

  Widget _buildFlagWidget(String flagUrl,
      {double width = 38, double height = 26}) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(7),
        child: CachedNetworkImage(
          imageUrl: flagUrl,
          width: width,
          height: height,
          fit: BoxFit.cover,
          memCacheWidth: 80,
          memCacheHeight: 60,
          placeholder: (context, url) => Container(color: Colors.white12),
          errorWidget: (context, url, error) =>
              Container(color: Colors.white24),
        ),
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
              final bool isSelected = selectedValue != null &&
                  (selectedValue == option ||
                      selectedValue
                              .replaceAll(
                                  RegExp(r'[^\u0600-\u06FFa-zA-Z0-9]'), '')
                              .toLowerCase()
                              .trim() ==
                          option
                              .replaceAll(
                                  RegExp(r'[^\u0600-\u06FFa-zA-Z0-9]'), '')
                              .toLowerCase()
                              .trim());

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
    String selectedBirthCountryName =
        isAr ? 'اختر بلد المنشأ 🌍' : 'Select birth country 🌍';
    String? selectedBirthFlagUrl;

    if (selectedBirthCountryId != null && countries.isNotEmpty) {
      final match = countries.firstWhere(
        (dynamic c) => (c as Map)['id'].toString() == selectedBirthCountryId,
        orElse: () => null,
      );
      if (match != null && match is Map) {
        selectedBirthCountryName = isAr
            ? (match['name_ar'] ?? match['name'] ?? '')
            : (match['name_en'] ?? match['name'] ?? '');
        selectedBirthFlagUrl = getCountryFlagUrl(match);
      }
    }

    String selectedResidencyCountryName =
        isAr ? 'اختر بلد الإقامة 🏠' : 'Select residence country 🏠';
    String? selectedResidencyFlagUrl;

    if (selectedResidencyCountryId != null && countries.isNotEmpty) {
      final match = countries.firstWhere(
        (dynamic c) =>
            (c as Map)['id'].toString() == selectedResidencyCountryId,
        orElse: () => null,
      );
      if (match != null && match is Map) {
        selectedResidencyCountryName = isAr
            ? (match['name_ar'] ?? match['name'] ?? '')
            : (match['name_en'] ?? match['name'] ?? '');
        selectedResidencyFlagUrl = getCountryFlagUrl(match);
      }
    }

    String? selectedStateName;
    if (selectedResidencyStateId != null && residencyStates.isNotEmpty) {
      final match = residencyStates.firstWhere(
        (dynamic st) =>
            (st as Map)['id'].toString() == selectedResidencyStateId,
        orElse: () => null,
      );
      if (match != null) {
        selectedStateName = getLocalizedCityName(match, isAr);
      }
    }

    String? selectedCityName;
    if (selectedResidencyCityId != null && residencyCities.isNotEmpty) {
      final match = residencyCities.firstWhere(
        (dynamic ct) => (ct as Map)['id'].toString() == selectedResidencyCityId,
        orElse: () => null,
      );
      if (match != null) {
        selectedCityName = getLocalizedCityName(match, isAr);
      }
    }

    final List<Map<String, String>> religionOptions = [
      {'id': '1', 'name': isAr ? 'إسلام ☪️' : 'Islam ☪️'},
      {'id': '2', 'name': isAr ? 'مسيحية ✝️' : 'Christianity ✝️'},
      {'id': '3', 'name': isAr ? 'غير ذلك' : 'Other'},
    ];

    String? currentReligionName;
    if (selectedReligionId != null) {
      final match = religionOptions.firstWhere(
        (r) => r['id'] == selectedReligionId,
        orElse: () => {'id': '1', 'name': isAr ? 'إسلام ☪️' : 'Islam ☪️️'},
      );
      currentReligionName = match['name'];
    }

    String? currentFamilyValueName;
    if (selectedFamilyValueId != null && familyValues.isNotEmpty) {
      final match = familyValues.firstWhere(
        (dynamic fv) => (fv as Map)['id'].toString() == selectedFamilyValueId,
        orElse: () => null,
      );
      if (match != null && match is Map) {
        currentFamilyValueName = isAr
            ? (match['name_ar'] ?? match['name'] ?? '')
            : (match['name_en'] ?? match['name'] ?? '');
      }
    }

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
            _buildSectionHeader(
                isAr ? 'بلد المنشأ والولادة 🌍' : 'Place of Birth 🌍',
                Icons.public_rounded),
            InkWell(
              onTap: onSelectBirthCountry,
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    selectedBirthFlagUrl != null
                        ? _buildFlagWidget(selectedBirthFlagUrl)
                        : const Icon(Icons.flag_rounded,
                            color: Colors.white70, size: 22),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        selectedBirthCountryName,
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontFamily: 'Cairo',
                          fontWeight: selectedBirthCountryId != null
                              ? FontWeight.bold
                              : FontWeight.normal,
                        ),
                      ),
                    ),
                    const Icon(Icons.search_rounded,
                        color: Colors.white70, size: 22),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
            _buildSectionHeader(
                isAr ? 'مقر الإقامة والوضع الحالي 🏠' : 'Current Residence 🏠',
                Icons.home_work_rounded),
            InkWell(
              onTap: onSelectResidencyCountry,
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    selectedResidencyFlagUrl != null
                        ? _buildFlagWidget(selectedResidencyFlagUrl)
                        : const Icon(Icons.language_rounded,
                            color: Colors.white70, size: 22),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        selectedResidencyCountryName,
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontFamily: 'Cairo',
                          fontWeight: selectedResidencyCountryId != null
                              ? FontWeight.bold
                              : FontWeight.normal,
                        ),
                      ),
                    ),
                    const Icon(Icons.search_rounded,
                        color: Colors.white70, size: 22),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            if (isLoadingResidencyStates)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      isAr
                          ? 'جاري جلب المحافظات...'
                          : 'Loading governorates...',
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontFamily: 'Cairo'),
                    ),
                  ],
                ),
              )
            else if (residencyStates.isNotEmpty)
              _buildSheetPickerTile(
                label:
                    isAr ? 'المحافظة / المقاطعة 🏛' : 'Governorate / State 🏛️',
                displayValue: selectedStateName,
                icon: Icons.location_city_rounded,
                onTap: onSelectState,
              ),
            const SizedBox(height: 16),
            if (isLoadingResidencyCities)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      isAr ? 'جاري جلب المدن...' : 'Loading cities...',
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontFamily: 'Cairo'),
                    ),
                  ],
                ),
              )
            else if (selectedResidencyStateId != null) ...[
              _buildSheetPickerTile(
                label: residencyCities.isNotEmpty
                    ? (isAr
                        ? 'المدينة / المنطقة السكنية 🏙️'
                        : 'City / District 🏙️')
                    : (isAr
                        ? 'لا توجد مدن مسجلة لهذه المحافظة 🏙️'
                        : 'No cities found 🏙️'),
                displayValue: selectedCityName,
                icon: Icons.holiday_village_rounded,
                onTap: () {
                  if (residencyCities.isNotEmpty) {
                    onSelectCity();
                  }
                },
              ),
              const SizedBox(height: 16),
            ],
            _buildChoiceChips(
              title: isAr ? 'وضع الإقامة والسفر ✈️' : 'Travel Status ✈️',
              icon: Icons.card_travel_rounded,
              options: [
                isAr ? 'مقيم في بلدي الأصلي (غير مسافر) 🏡' : 'Home Country 🏡',
                isAr ? 'مغترب - مقيم 🏠' : 'Expat - Resident 🏠',
                isAr ? 'مغترب - إقامة دائمة ✈️' : 'Permanent Resident ✈️️',
                isAr ? 'مغترب - إقامة عمل 💼' : 'Work Visa 💼',
                isAr ? 'حاصل على الجنسية 🌐' : 'Citizen 🌐',
              ],
              selectedValue: immigrationStatusCtrl.text,
              onSelected: onImmigrationStatusChanged,
            ),
            _buildSectionHeader(
                isAr ? 'الخلفية الدينية والاجتماعية ✨' : 'Spiritual & Social ✨',
                Icons.auto_awesome_rounded),
            _buildSheetPickerTile(
              label: isAr ? 'الديانة *' : 'Religion *',
              displayValue: currentReligionName,
              icon: Icons.stars_rounded,
              onTap: onSelectReligion,
            ),
            const SizedBox(height: 16),
            if (familyValues.isNotEmpty)
              _buildSheetPickerTile(
                label: isAr
                    ? 'القيم العائلية 👨‍👩‍👧‍👦'
                    : 'Family Values 👨‍👩‍👧‍👦',
                displayValue: currentFamilyValueName,
                icon: Icons.groups_rounded,
                onTap: onSelectFamilyValue,
              ),
          ],
        ),
      ),
    );
  }
}

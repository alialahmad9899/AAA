// ============================================================================
// اسم الملف: lib/screens/edit_profile/widgets/profile_pickers.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: النوافذ المنبثقة القابلة لإعادة الاستخدام والمحصنة ضد مشاكل الكيبورد،
//         مع قاموس ترجمة ثنائي اللغة (عربي / إنجليزي)، وبحث سرييع، ومعاينة للألوان.
// ============================================================================

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../services/api_service.dart';

// ============================================================================
// 🌐 1. قاموس الترجمة الشامل للقوائم والخيارات الثابتة (Translation Dictionary)
// ============================================================================

class ProfilePickerTranslations {
  static const Map<String, Map<String, String>> _translations = {
    // الحالة الاجتماعية
    'single': {'ar': 'أعزب / عزباء', 'en': 'Single'},
    'married': {'ar': 'متزوج / متزوجة', 'en': 'Married'},
    'divorced': {'ar': 'مطلق / مطلقة', 'en': 'Divorced'},
    'widowed': {'ar': 'أرمل / أرملة', 'en': 'Widowed'},

    // بنية الجسم
    'slim': {'ar': 'نحيف / رشيق', 'en': 'Slim'},
    'average': {'ar': 'متوسط / متناسق', 'en': 'Average'},
    'athletic': {'ar': 'رياضي / متناسق', 'en': 'Athletic'},
    'heavy': {'ar': 'ممتلئ / ضخم', 'en': 'Heavy'},

    // لون العيون
    'black': {'ar': 'أسود', 'en': 'Black'},
    'brown': {'ar': 'بني', 'en': 'Brown'},
    'blue': {'ar': 'أزرق', 'en': 'Blue'},
    'green': {'ar': 'أخضر', 'en': 'Green'},
    'hazel': {'ar': 'عسلي', 'en': 'Hazel'},
    'grey': {'ar': 'رمادي', 'en': 'Grey'},

    // لون الشعر
    'dark_brown': {'ar': 'بني غامق', 'en': 'Dark Brown'},
    'blonde': {'ar': 'أشقر', 'en': 'Blonde'},
    'red': {'ar': 'أحمر', 'en': 'Red'},
    'white': {'ar': 'أبيض / شيب', 'en': 'White / Grey'},

    // لون البشرة
    'fair': {'ar': 'بيضاء', 'en': 'Fair / Light'},
    'wheat': {'ar': 'حنطية / حنطي', 'en': 'Wheatish / Medium'},
    'bronze': {'ar': 'برونزية / أسمر فاتح', 'en': 'Bronze / Olive'},
    'dark': {'ar': 'سمراء / داكنة', 'en': 'Dark / Black'},

    // المستوى التعليمي
    'high_school': {'ar': 'ثانوي', 'en': 'High School'},
    'diploma': {'ar': 'دبلوم تقني / معهد', 'en': 'Diploma'},
    'bachelor': {'ar': 'بكالوريوس / جامعي', 'en': 'Bachelor Degree'},
    'master': {'ar': 'ماجستير', 'en': 'Master Degree'},
    'phd': {'ar': 'دكتوراه', 'en': 'PhD / Doctorate'},

    // الوضع المالي والمهني
    'employed': {'ar': 'موظف / يعمل', 'en': 'Employed'},
    'business_owner': {'ar': 'صاحب عمل / أعمال حرة', 'en': 'Business Owner'},
    'student': {'ar': 'طالب / طالبة', 'en': 'Student'},
    'unemployed': {'ar': 'لا يعمل حالياً', 'en': 'Unemployed'},

    // التدين والتعديل الشرعي
    'very_religious': {'ar': 'ملتزم جداً', 'en': 'Very Religious'},
    'religious': {'ar': 'ملتزم', 'en': 'Religious'},
    'moderate': {'ar': 'متوسط / معتدل', 'en': 'Moderate'},
    'not_practicing': {'ar': 'غير ملتزم', 'en': 'Not Practicing'},

    // خيارات السكن والاقامة
    'own_house': {'ar': 'ملك خاص', 'en': 'Own House'},
    'rented_house': {'ar': 'إيجار', 'en': 'Rented House'},
    'family_house': {'ar': 'مع العائلة', 'en': 'With Family'},

    // إجابات نعم / لا / أحياناً
    'yes': {'ar': 'نعم', 'en': 'Yes'},
    'no': {'ar': 'لا', 'en': 'No'},
    'sometimes': {'ar': 'أحياناً', 'en': 'Sometimes'},
  };

  /// ترجمة النص أو المفتاح تلقائياً بحسب لغة التطبيق الحالية
  static String translate(String? keyOrValue, bool isAr) {
    if (keyOrValue == null || keyOrValue.trim().isEmpty) return '';

    final cleanKey = keyOrValue.trim().toLowerCase();

    if (_translations.containsKey(cleanKey)) {
      return _translations[cleanKey]![isAr ? 'ar' : 'en']!;
    }

    // إذا لم يتوفر المفتاح بالقاموس يتم إرجاع النص الأصلي
    return keyOrValue;
  }
}

// ============================================================================
// ⚡ 2. مؤخر الاستجابة (Debouncer) لمنع تعليق الشاشة أثناء البحث
// ============================================================================

class Debouncer {
  final int milliseconds;
  Timer? _timer;

  Debouncer({this.milliseconds = 150});

  void run(VoidCallback action) {
    _timer?.cancel();
    _timer = Timer(Duration(milliseconds: milliseconds), action);
  }

  void dispose() {
    _timer?.cancel();
  }
}

// ============================================================================
// 🛠️ 3. فئة المساعدة لفتح النوافذ المنبثقة بسهولة (ProfilePickers Helper)
// ============================================================================

class ProfilePickers {
  /// فتح النافذة المنبثقة العامة للاختيار من قائمة
  static Future<void> showGenericPicker({
    required BuildContext context,
    required String title,
    required List<Map<String, dynamic>> items,
    required String? currentValue,
    required ValueChanged<String> onSelected,
    required bool isAr,
    bool enableSearch = false,
  }) async {
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      barrierColor: Colors.black.withValues(alpha: 0.65),
      builder: (context) => GenericPickerBottomSheetContent(
        title: title,
        items: items,
        currentValue: currentValue,
        onSelected: onSelected,
        isAr: isAr,
        enableSearch: enableSearch,
      ),
    );
  }

  /// فتح النافذة المنبثقة لاختيار الدولة مع الأعلام والبحث
  static Future<void> showCountryPicker({
    required BuildContext context,
    required List<dynamic> countries,
    required String? selectedCountryId,
    required bool isBirthCountry,
    required bool isAr,
    required Function(String id) onCountrySelected,
  }) async {
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      barrierColor: Colors.black.withValues(alpha: 0.65),
      builder: (context) => CountryPickerBottomSheetContent(
        countries: countries,
        selectedCountryId: selectedCountryId,
        isBirthCountry: isBirthCountry,
        isAr: isAr,
        onCountrySelected: onCountrySelected,
      ),
    );
  }
}

// ============================================================================
// 📱 4. ودجت المحتوى للنوافذ المنبثقة العامة (Generic Picker Bottom Sheet)
// ============================================================================

class GenericPickerBottomSheetContent extends StatefulWidget {
  final String title;
  final List<Map<String, dynamic>> items;
  final String? currentValue;
  final ValueChanged<String> onSelected;
  final bool isAr;
  final bool enableSearch;

  const GenericPickerBottomSheetContent({
    super.key,
    required this.title,
    required this.items,
    required this.currentValue,
    required this.onSelected,
    required this.isAr,
    this.enableSearch = false,
  });

  @override
  State<GenericPickerBottomSheetContent> createState() =>
      _GenericPickerBottomSheetContentState();
}

class _GenericPickerBottomSheetContentState
    extends State<GenericPickerBottomSheetContent> {
  late TextEditingController _searchCtrl;
  late List<Map<String, dynamic>> _filteredItems;
  late Debouncer _debouncer;

  @override
  void initState() {
    super.initState();
    _searchCtrl = TextEditingController();
    _filteredItems = List.from(widget.items);
    _debouncer = Debouncer(milliseconds: 150);
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _debouncer.dispose();
    super.dispose();
  }

  /// استخراج اسم العنصر باللغة المحددة مع استخدام القاموس التلقائي كخيار بديل
  String _getItemName(Map<String, dynamic> item) {
    final String? rawAr = item['name_ar'] ?? item['title_ar'];
    final String? rawEn = item['name_en'] ?? item['title_en'];
    final String? generic =
        item['name'] ?? item['title'] ?? item['id']?.toString();

    if (widget.isAr) {
      if (rawAr != null && rawAr.isNotEmpty) return rawAr;
      return ProfilePickerTranslations.translate(generic, true);
    } else {
      if (rawEn != null && rawEn.isNotEmpty) return rawEn;
      return ProfilePickerTranslations.translate(generic, false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final double bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Directionality(
      textDirection: widget.isAr ? TextDirection.rtl : TextDirection.ltr,
      child: AnimatedPadding(
        duration: const Duration(milliseconds: 100),
        padding: EdgeInsets.only(bottom: bottomInset),
        child: Container(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.75,
          ),
          padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 18),
          decoration: BoxDecoration(
            color: const Color(0xFF0F172A).withValues(alpha: 0.96),
            borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
            border: Border.all(
              color: const Color(0xFF38BDF8).withValues(alpha: 0.3),
              width: 1.2,
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // مقبض النافذة السفلية
              Container(
                width: 48,
                height: 5,
                decoration: BoxDecoration(
                  color: Colors.white38,
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              const SizedBox(height: 18),

              // عنوان النافذة
              Text(
                widget.title,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 19,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Cairo',
                ),
              ),

              // حقل البحث السريع عند تفعيله
              if (widget.enableSearch) ...[
                const SizedBox(height: 16),
                TextField(
                  controller: _searchCtrl,
                  style: const TextStyle(
                      color: Colors.white, fontSize: 16, fontFamily: 'Cairo'),
                  onChanged: (query) {
                    _debouncer.run(() {
                      final q = query.toLowerCase().trim();
                      final results = widget.items.where((item) {
                        final String nameAr =
                            _getItemName({...item, 'isAr': true}).toLowerCase();
                        final String nameEn =
                            _getItemName({...item, 'isAr': false})
                                .toLowerCase();
                        final String idStr =
                            (item['id'] ?? '').toString().toLowerCase();
                        return nameAr.contains(q) ||
                            nameEn.contains(q) ||
                            idStr.contains(q);
                      }).toList();
                      if (mounted) setState(() => _filteredItems = results);
                    });
                  },
                  decoration: InputDecoration(
                    hintText: widget.isAr ? 'بحث سريع...' : 'Quick search...',
                    hintStyle: TextStyle(
                        color: Colors.white.withValues(alpha: 0.65),
                        fontSize: 15,
                        fontFamily: 'Cairo'),
                    prefixIcon:
                        const Icon(Icons.search_rounded, color: Colors.white70),
                    filled: true,
                    fillColor: Colors.white.withValues(alpha: 0.12),
                    border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(20),
                        borderSide: BorderSide.none),
                  ),
                ),
              ],
              const SizedBox(height: 16),

              // قائمة العناصر المتاحة للاختيار
              Flexible(
                child: _filteredItems.isEmpty
                    ? Padding(
                        padding: const EdgeInsets.symmetric(vertical: 30),
                        child: Text(
                          widget.isAr
                              ? 'لا توجد نتائج مطابقة'
                              : 'No results found',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.6),
                            fontSize: 15,
                            fontFamily: 'Cairo',
                          ),
                        ),
                      )
                    : ListView.separated(
                        physics: const BouncingScrollPhysics(),
                        shrinkWrap: true,
                        itemCount: _filteredItems.length,
                        separatorBuilder: (context, index) => Divider(
                            color: Colors.white.withValues(alpha: 0.12)),
                        itemBuilder: (context, index) {
                          final item = _filteredItems[index];
                          final String itemName = _getItemName(item);
                          final String itemId = item['id']?.toString() ?? '';
                          final isSelected = widget.currentValue == itemId;
                          final Color? colorValue = item['color'] as Color?;

                          return Material(
                            color: Colors.transparent,
                            child: ListTile(
                              leading: colorValue != null
                                  ? Container(
                                      width: 26,
                                      height: 26,
                                      decoration: BoxDecoration(
                                        color: colorValue,
                                        shape: BoxShape.circle,
                                        border: Border.all(
                                            color: Colors.white, width: 2),
                                        boxShadow: [
                                          BoxShadow(
                                            color: colorValue.withValues(
                                                alpha: 0.5),
                                            blurRadius: 6,
                                          )
                                        ],
                                      ),
                                    )
                                  : null,
                              title: Text(
                                itemName,
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 16,
                                  fontFamily: 'Cairo',
                                  fontWeight: isSelected
                                      ? FontWeight.bold
                                      : FontWeight.normal,
                                ),
                              ),
                              trailing: isSelected
                                  ? const Icon(Icons.check_circle_rounded,
                                      color: Colors.amberAccent, size: 22)
                                  : null,
                              onTap: () {
                                if (itemId.isNotEmpty) {
                                  widget.onSelected(itemId);
                                }
                                Navigator.pop(context);
                              },
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// 🌍 5. ودجت المحتوى للنافذة المنبثقة لاختيار الدول (Country Picker)
// ============================================================================

class CountryPickerBottomSheetContent extends StatefulWidget {
  final List<dynamic> countries;
  final String? selectedCountryId;
  final bool isBirthCountry;
  final bool isAr;
  final Function(String id) onCountrySelected;

  const CountryPickerBottomSheetContent({
    super.key,
    required this.countries,
    required this.selectedCountryId,
    required this.isBirthCountry,
    required this.isAr,
    required this.onCountrySelected,
  });

  @override
  State<CountryPickerBottomSheetContent> createState() =>
      _CountryPickerBottomSheetContentState();
}

class _CountryPickerBottomSheetContentState
    extends State<CountryPickerBottomSheetContent> {
  late TextEditingController _searchCtrl;
  late List<dynamic> _filteredCountries;
  late Debouncer _debouncer;

  @override
  void initState() {
    super.initState();
    _searchCtrl = TextEditingController();
    _filteredCountries = List.from(widget.countries);
    _debouncer = Debouncer(milliseconds: 150);
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _debouncer.dispose();
    super.dispose();
  }

  /// إرجاع اسم الدولة المترجم بذكاء
  String _getCountryName(Map country) {
    if (widget.isAr) {
      return country['name_ar'] ??
          country['name_arabic'] ??
          country['ar_name'] ??
          country['name'] ??
          '';
    } else {
      return country['name_en'] ??
          country['en_name'] ??
          country['name'] ??
          country['name_ar'] ??
          '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final double bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Directionality(
      textDirection: widget.isAr ? TextDirection.rtl : TextDirection.ltr,
      child: AnimatedPadding(
        duration: const Duration(milliseconds: 100),
        padding: EdgeInsets.only(bottom: bottomInset),
        child: Container(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.75,
          ),
          padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 18),
          decoration: BoxDecoration(
            color: const Color(0xFF0F172A).withValues(alpha: 0.96),
            borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
            border: Border.all(
              color: const Color(0xFF38BDF8).withValues(alpha: 0.3),
              width: 1.2,
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // مقبض النافذة المنبثقة
              Container(
                width: 48,
                height: 5,
                decoration: BoxDecoration(
                  color: Colors.white38,
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              const SizedBox(height: 20),

              // عنوان النافذة بحسب نوع الدولة (منشأ / إقامة)
              Text(
                widget.isBirthCountry
                    ? (widget.isAr
                        ? 'اختر بلد المنشأ 🌍'
                        : 'Select Birth Country 🌍')
                    : (widget.isAr
                        ? 'اختر بلد الإقامة 🏠'
                        : 'Select Residence Country 🏠'),
                textAlign: TextAlign.center,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 19,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo'),
              ),
              const SizedBox(height: 18),

              // حقل البحث في قائمة الدول
              TextField(
                controller: _searchCtrl,
                style: const TextStyle(
                    color: Colors.white, fontSize: 16, fontFamily: 'Cairo'),
                onChanged: (query) {
                  _debouncer.run(() {
                    final q = query.toLowerCase().trim();
                    final results = widget.countries.where((dynamic c) {
                      if (c is! Map) return false;
                      final name = (c['name'] ?? '').toString().toLowerCase();
                      final nameAr = (c['name_ar'] ??
                              c['name_arabic'] ??
                              c['ar_name'] ??
                              '')
                          .toString()
                          .toLowerCase();
                      final nameEn = (c['name_en'] ?? c['en_name'] ?? '')
                          .toString()
                          .toLowerCase();
                      final code = (c['code'] ?? c['iso2'] ?? '')
                          .toString()
                          .toLowerCase();
                      return name.contains(q) ||
                          nameAr.contains(q) ||
                          nameEn.contains(q) ||
                          code.contains(q);
                    }).toList();
                    if (mounted) setState(() => _filteredCountries = results);
                  });
                },
                decoration: InputDecoration(
                  hintText: widget.isAr
                      ? 'ابحث باسم الدولة...'
                      : 'Search country name...',
                  hintStyle: TextStyle(
                      color: Colors.white.withValues(alpha: 0.65),
                      fontSize: 14,
                      fontFamily: 'Cairo'),
                  prefixIcon:
                      const Icon(Icons.search_rounded, color: Colors.white70),
                  filled: true,
                  fillColor: Colors.white.withValues(alpha: 0.12),
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(20),
                      borderSide: BorderSide.none),
                ),
              ),
              const SizedBox(height: 18),

              // قائمة الدول المفلترة
              Flexible(
                child: _filteredCountries.isEmpty
                    ? Padding(
                        padding: const EdgeInsets.symmetric(vertical: 30),
                        child: Text(
                          widget.isAr
                              ? 'لم يتم العثور على أي دولة'
                              : 'No country found',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.6),
                            fontSize: 15,
                            fontFamily: 'Cairo',
                          ),
                        ),
                      )
                    : ListView.separated(
                        physics: const BouncingScrollPhysics(),
                        shrinkWrap: true,
                        itemCount: _filteredCountries.length,
                        separatorBuilder: (context, index) => Divider(
                            color: Colors.white.withValues(alpha: 0.12)),
                        itemBuilder: (context, index) {
                          final country = _filteredCountries[index] as Map;
                          final String id = country['id'].toString();
                          final String name = _getCountryName(country);
                          final String flagUrl = ApiService.getOptimizedFlagUrl(
                              Map<String, dynamic>.from(country));
                          final bool isSelected =
                              widget.selectedCountryId == id;

                          return Material(
                            color: Colors.transparent,
                            child: ListTile(
                              key: ValueKey('country_$id'),
                              leading: CachedNetworkImage(
                                imageUrl: flagUrl,
                                width: 38,
                                height: 26,
                                fit: BoxFit.cover,
                                placeholder: (context, url) => Container(
                                  width: 38,
                                  height: 26,
                                  color: Colors.white12,
                                ),
                                errorWidget: (context, url, error) =>
                                    const Icon(
                                  Icons.flag_rounded,
                                  color: Colors.white54,
                                  size: 26,
                                ),
                              ),
                              title: Text(
                                name,
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 16,
                                  fontFamily: 'Cairo',
                                  fontWeight: isSelected
                                      ? FontWeight.bold
                                      : FontWeight.normal,
                                ),
                              ),
                              trailing: isSelected
                                  ? const Icon(Icons.check_circle_rounded,
                                      color: Colors.amberAccent, size: 22)
                                  : null,
                              onTap: () {
                                Navigator.pop(context);
                                widget.onCountrySelected(id);
                              },
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

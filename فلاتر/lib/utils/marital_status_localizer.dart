/// Shared, defensive translation for marital-status labels returned by APIs.
class MaritalStatusLocalizer {
  MaritalStatusLocalizer._();

  static String? toEnglish(String rawValue) {
    final raw = rawValue.trim();
    if (raw.isEmpty) return null;

    final normalized = raw
        .replaceAll(RegExp(r'[💍💔✨]'), '')
        .replaceAll(RegExp(r'\s+'), ' ')
        .replaceAll(RegExp(r'\s*/\s*'), '/')
        .trim()
        .toLowerCase();

    const translations = <String, String>{
      'أعزب': 'Single',
      'اعزب': 'Single',
      'عزباء': 'Single',
      'أعزب/عزباء': 'Single',
      'single': 'Single',
      'متزوج': 'Married',
      'متزوج/متزوجة': 'Married',
      'متزوجة': 'Married',
      'متزوج/ة': 'Married',
      'married': 'Married',
      'مطلق': 'Divorced',
      'مطلقة': 'Divorced',
      'مطلق/ة': 'Divorced',
      'divorced': 'Divorced',
      'مطلق/مطلقة': 'Divorced',
      'أرمل': 'Widowed',
      'ارمل': 'Widowed',
      'أرملة': 'Widowed',
      'ارملة': 'Widowed',
      'أرمل/ة': 'Widowed',
      'widowed': 'Widowed',
      'أرمل/أرملة': 'Widowed',
      'ارمل/ارملة': 'Widowed',
    };

    final exact = translations[normalized];
    if (exact != null) return exact;
    if (normalized.contains('عزب') || normalized.contains('اعزب')) {
      return 'Single';
    }
    if (normalized.contains('مطلق')) return 'Divorced';
    if (normalized.contains('ارمل') || normalized.contains('أرمل')) {
      return 'Widowed';
    }
    if (normalized.contains('متزوج')) return 'Married';
    return null;
  }
}

/// Utilities for displaying the server-managed subscription package catalogue.
class PackageCatalogUtils {
  static int _integer(dynamic value, int fallback) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? fallback;
  }

  static bool _boolean(dynamic value) {
    if (value is bool) return value;
    if (value is num) return value != 0;
    final text = value?.toString().trim().toLowerCase();
    return text == '1' || text == 'true' || text == 'yes';
  }

  /// Returns a grammatically correct Arabic label for a package duration.
  static String arabicValidityDaysLabel(int days) {
    final count = days < 0 ? 0 : days;
    if (count == 0) return '0 يوم';
    if (count == 1) return 'يوم واحد';
    if (count == 2) return 'يومين';
    if (count >= 3 && count <= 10) return '$count أيام';
    return '$count يوماً';
  }

  static List<Map<String, dynamic>> sortPackages(Iterable<dynamic> source) {
    final packages = source
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();

    packages.sort((left, right) {
      final leftOrder = _integer(
        left['sort_order'],
        _integer(left['id'] ?? left['package_id'], 0) * 10,
      );
      final rightOrder = _integer(
        right['sort_order'],
        _integer(right['id'] ?? right['package_id'], 0) * 10,
      );
      final orderComparison = leftOrder.compareTo(rightOrder);
      if (orderComparison != 0) return orderComparison;

      final leftId = _integer(left['id'] ?? left['package_id'], 0);
      final rightId = _integer(right['id'] ?? right['package_id'], 0);
      return leftId.compareTo(rightId);
    });

    return packages;
  }

  static String displayName(Map<String, dynamic> package, {required bool isArabic}) {
    final preferred = isArabic ? package['name_ar'] : package['name_en'];
    final preferredText = preferred?.toString().trim() ?? '';
    if (preferredText.isNotEmpty) return preferredText;
    return package['name']?.toString().trim() ?? '';
  }

  static String displayDescription(Map<String, dynamic> package, {required bool isArabic}) {
    final preferred = isArabic ? package['description_ar'] : package['description_en'];
    final preferredText = preferred?.toString().trim() ?? '';
    if (preferredText.isNotEmpty) return preferredText;
    return package['description']?.toString().trim() ?? '';
  }

  /// Prefer icon-bearing API rows; gracefully read older plain-string arrays.
  static List<Map<String, String>> displayFeatureItems(
    Map<String, dynamic> package, {
    required bool isArabic,
  }) {
    final preferredItems = isArabic
        ? package['feature_items_ar']
        : package['feature_items_en'];
    final sharedItems = package['feature_items'];
    final localizedStrings =
        isArabic ? package['features_ar'] : package['features_en'];

    final dynamic source = preferredItems is List
        ? preferredItems
        : (sharedItems is List ? sharedItems : localizedStrings);
    final fallback = package['features'] is List
        ? package['features'] as List
        : const <dynamic>[];
    final values = source is List && source.isNotEmpty ? source : fallback;

    final items = <Map<String, String>>[];
    for (final value in values.take(30)) {
      if (value is Map) {
        final title = (value['title'] ??
                (isArabic ? value['title_ar'] : value['title_en']) ??
                '')
            .toString()
            .trim();
        if (title.isEmpty) continue;
        final icon = value['icon_key']?.toString().trim() ?? '';
        items.add({
          'title': title,
          'icon_key': icon.isEmpty ? 'check' : icon,
        });
      } else {
        final title = value?.toString().trim() ?? '';
        if (title.isEmpty) continue;
        items.add({'title': title, 'icon_key': 'check'});
      }
    }
    return items;
  }

  static List<String> displayFeatures(
    Map<String, dynamic> package, {
    required bool isArabic,
  }) {
    return displayFeatureItems(package, isArabic: isArabic)
        .map((item) => item['title']!)
        .toList(growable: false);
  }

  static String displayBadgeText(Map<String, dynamic> package, {required bool isArabic}) {
    final preferred = isArabic ? package['badge_text_ar'] : package['badge_text_en'];
    final preferredText = preferred?.toString().trim() ?? '';
    if (preferredText.isNotEmpty) return preferredText;
    final localizedFallback = package['badge_text']?.toString().trim() ?? '';
    if (localizedFallback.isNotEmpty) return localizedFallback;
    return isArabic ? 'اشتراك مميز' : 'Premium Membership';
  }

  static String badgeIconKey(Map<String, dynamic> package) {
    final value = package['badge_icon_key']?.toString().trim() ?? '';
    return value.isEmpty ? 'workspace_premium' : value;
  }

  static String badgeStyle(Map<String, dynamic> package) {
    final value = package['badge_style']?.toString().trim().toLowerCase();
    return const ['silver', 'gold', 'teal'].contains(value) ? value! : 'teal';
  }

  static bool isFeatured(Map<String, dynamic> package) {
    return _boolean(package['is_featured']);
  }

  static String displayDurationLabel(Map<String, dynamic> package, {required bool isArabic}) {
    final preferred = isArabic
        ? package['duration_label_ar']
        : package['duration_label_en'];
    final preferredText = preferred?.toString().trim() ?? '';
    if (preferredText.isNotEmpty) return preferredText;
    return package['duration_label']?.toString().trim() ?? '';
  }
}

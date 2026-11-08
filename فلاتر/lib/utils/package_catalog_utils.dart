/// Utilities for displaying the server-managed subscription package catalogue.
class PackageCatalogUtils {
  static int _integer(dynamic value, int fallback) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? fallback;
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

  static String displayName(
    Map<String, dynamic> package, {
    required bool isArabic,
  }) {
    final preferred = isArabic ? package['name_ar'] : package['name_en'];
    final preferredText = preferred?.toString().trim() ?? '';
    if (preferredText.isNotEmpty) return preferredText;
    return package['name']?.toString().trim() ?? '';
  }

  static String displayDescription(
    Map<String, dynamic> package, {
    required bool isArabic,
  }) {
    final preferred =
        isArabic ? package['description_ar'] : package['description_en'];
    final preferredText = preferred?.toString().trim() ?? '';
    if (preferredText.isNotEmpty) return preferredText;
    return package['description']?.toString().trim() ?? '';
  }

  static List<String> displayFeatures(
    Map<String, dynamic> package, {
    required bool isArabic,
  }) {
    final preferred = isArabic ? package['features_ar'] : package['features_en'];
    final source = preferred is List
        ? preferred
        : (package['features'] is List ? package['features'] as List : const []);
    return source
        .map((item) => item?.toString().trim() ?? '')
        .where((item) => item.isNotEmpty)
        .take(30)
        .toList(growable: false);
  }
}

/// Safe model for a single profile visitor returned by the Laravel API.
class ProfileViewerModel {
  final int userId;
  final String name;
  final String photo;
  final int age;
  final String visitedAt;

  const ProfileViewerModel({
    required this.userId,
    required this.name,
    required this.photo,
    required this.age,
    required this.visitedAt,
  });

  /// Returns null for malformed collection entries instead of letting the UI
  /// crash while indexing a null or non-map JSON value.
  static ProfileViewerModel? tryParse(
    dynamic value, {
    required bool isArabic,
  }) {
    if (value is! Map) return null;

    late final Map<String, dynamic> json;
    try {
      json = Map<String, dynamic>.from(value);
    } catch (_) {
      return null;
    }

    final int userId = _readPositiveInt(
      json['user_id'],
      json['viewed_by'],
      json['id'],
    );
    if (userId <= 0) return null;

    final String name = _firstText(json, const ['name', 'user_name']) ??
        _joinName(json) ??
        (isArabic ? 'عضو' : 'Member');

    final String photo = _firstHttpUrl(
      json['photo'],
      json['user_photo'],
    );

    final int age = _readAge(json['age'] ?? json['user_age']);
    final String visitedAt =
        _firstText(json, const ['date', 'time']) ?? '';

    return ProfileViewerModel(
      userId: userId,
      name: name,
      photo: photo,
      age: age,
      visitedAt: visitedAt,
    );
  }

  static int _readPositiveInt(dynamic first, dynamic second, dynamic third) {
    for (final value in [first, second, third]) {
      if (value is int && value > 0) return value;
      if (value is num && value.isFinite && value > 0) {
        return value.toInt();
      }
      final parsed = int.tryParse(value?.toString().trim() ?? '');
      if (parsed != null && parsed > 0) return parsed;
    }
    return 0;
  }

  static int _readAge(dynamic value) {
    final num? parsed = value is num
        ? value
        : num.tryParse(value?.toString().trim() ?? '');
    if (parsed == null || !parsed.isFinite) return 0;

    final int age = parsed.round();
    return age >= 0 && age <= 120 ? age : 0;
  }

  static String? _firstText(
    Map<String, dynamic> json,
    List<String> keys,
  ) {
    for (final key in keys) {
      final value = json[key];
      if (value is String && value.trim().isNotEmpty) {
        return value.trim();
      }
    }
    return null;
  }

  static String? _joinName(Map<String, dynamic> json) {
    final firstName = json['first_name'];
    final lastName = json['last_name'];
    final parts = [
      if (firstName is String && firstName.trim().isNotEmpty)
        firstName.trim(),
      if (lastName is String && lastName.trim().isNotEmpty)
        lastName.trim(),
    ];
    if (parts.isEmpty) return null;
    return parts.join(' ');
  }

  static String _firstHttpUrl(dynamic first, dynamic second) {
    for (final value in [first, second]) {
      if (value is! String || value.trim().isEmpty) continue;
      final candidate = value.trim();
      final uri = Uri.tryParse(candidate);
      if (uri != null &&
          (uri.scheme == 'https' || uri.scheme == 'http') &&
          uri.host.isNotEmpty) {
        return candidate;
      }
    }
    return '';
  }
}

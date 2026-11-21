/// Builds and parses links to Wesal member profiles.
class MemberProfileLink {
  MemberProfileLink._();

  /// Must match android/app/build.gradle.kts -> defaultConfig.applicationId.
  static const String androidPackage = 'com.example.wesal_app';

  static String webUrl(Object memberReference) {
    return Uri.https(
      'www.wesalsyria.com',
      '/profile',
      {'id': memberReference.toString()},
    ).toString();
  }

  /// Chrome uses browser_fallback_url when the native app is not installed.
  static String androidIntentUrl(Object memberReference) {
    final reference = memberReference.toString().trim();
    final fallback = Uri.encodeComponent(webUrl(reference));
    return 'intent://profile?id=${Uri.encodeQueryComponent(reference)}'
        '#Intent;scheme=wesal;package=$androidPackage;'
        'S.browser_fallback_url=$fallback;end';
  }

  /// Accept HTTPS App Links, wesal custom-scheme links and Android intent links.
  /// The incoming numeric value can be an internal user ID or public member code.
  static int? parseProfileUri(Uri uri) {
    final scheme = uri.scheme.toLowerCase();
    final host = uri.host.toLowerCase();
    final segments = uri.pathSegments;
    final profileIndex = segments.indexWhere(
      (segment) => segment.toLowerCase() == 'profile',
    );

    final customProfile = scheme == 'wesal' && host == 'profile';
    final androidIntentProfile = scheme == 'intent' && host == 'profile';
    if (!customProfile && !androidIntentProfile && profileIndex < 0) {
      return null;
    }

    String? reference;
    for (final key in const [
      'id',
      'member_id',
      'member_code',
      'member_number',
      'code',
    ]) {
      final candidate = uri.queryParameters[key]?.trim();
      if (candidate != null && candidate.isNotEmpty) {
        reference = candidate;
        break;
      }
    }

    if ((reference == null || reference.isEmpty) &&
        profileIndex >= 0 &&
        profileIndex + 1 < segments.length) {
      reference = segments[profileIndex + 1];
    }

    if (reference == null || reference.isEmpty) return null;
    final normalized = _normalizeDigits(reference);
    final parsed = int.tryParse(normalized);
    return parsed != null && parsed > 0 ? parsed : null;
  }

  static String _normalizeDigits(String value) {
    const arabicIndic = '٠١٢٣٤٥٦٧٨٩';
    const persian = '۰۱۲۳۴۵۶۷۸۹';
    var result = value;
    for (var index = 0; index < 10; index++) {
      result = result
          .replaceAll(arabicIndic[index], '$index')
          .replaceAll(persian[index], '$index');
    }
    return result;
  }
}

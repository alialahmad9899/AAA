/// Builds safe profile links for web fallback and the installed Android app.
class MemberProfileLink {
  MemberProfileLink._();

  /// Must match android/app/build.gradle.kts -> defaultConfig.applicationId.
  static const String androidPackage = 'com.example.wesal_app';

  static String webUrl(int memberReference) {
    return Uri.https(
      'www.wesalsyria.com',
      '/profile',
      {'id': memberReference.toString()},
    ).toString();
  }

  /// Chrome uses browser_fallback_url when the target package is not installed.
  static String androidIntentUrl(int memberReference) {
    final fallback = Uri.encodeComponent(webUrl(memberReference));
    return 'intent://profile?id=$memberReference'
        '#Intent;scheme=wesal;package=$androidPackage;'
        'S.browser_fallback_url=$fallback;end';
  }
}

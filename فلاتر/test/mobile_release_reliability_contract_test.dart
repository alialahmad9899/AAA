import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  test('restored sessions are server-validated and invalid auth clears local state', () async {
    final api = await File('lib/services/api_service.dart').readAsString();
    final intro = await File('lib/screens/brand_intro_screen.dart').readAsString();

    expect(api, contains(r"Uri.parse('$baseUrl/user-by-token')"));
    expect(api, contains("status == 401 || status == 403"));
    expect(api, contains("await clearToken()"));
    expect(intro, contains('ApiService.validateCurrentSession()'));
  });

  test('profile deep links remain pending until authenticated home is ready', () async {
    final main = await File('lib/main.dart').readAsString();
    final pending = await File('lib/services/pending_profile_link_service.dart').readAsString();
    final home = await File('lib/screens/main_home_screen.dart').readAsString();

    expect(main, contains('PendingProfileLinkService.save(memberReference)'));
    expect(pending, contains("pending_profile_deep_link_id"));
    expect(pending, contains('Navigator.of(context, rootNavigator: true).push'));
    expect(home, contains('PendingProfileLinkService.openPending(context)'));
    expect(home, contains('PendingProfileLinkService.markAuthenticatedHomeReady()'));
    expect(main, contains('isAuthenticatedHomeReady'));
  });

  test('signup resume stores profile progress securely but never serializes passwords', () async {
    final api = await File('lib/services/api_service.dart').readAsString();
    final signup = await File('lib/screens/signup_screen.dart').readAsString();
    final intro = await File('lib/screens/brand_intro_screen.dart').readAsString();

    expect(api, contains('_signupDraftSecureKey'));
    expect(api, contains('_secureStorage.write'));
    expect(signup, contains('resumeExistingDraft'));
    expect(signup, contains("'current_step': _currentStep"));
    expect(signup, contains('await ApiService.saveSignupDraft(draft)'));
    expect(signup, contains('await ApiService.getSignupDraft()'));
    expect(signup, contains('WidgetsBindingObserver'));
    expect(signup, isNot(contains("'password': _passwordController.text")));
    expect(intro, contains('resumeExistingDraft: true'));
  });

  test('local chat payloads use authenticated encryption and encrypted key storage', () async {
    final store = await File('lib/services/chat_local_store.dart').readAsString();
    final manifest = await File('android/app/src/main/AndroidManifest.xml').readAsString();

    expect(store, contains('AesGcm.with256bits()'));
    expect(store, contains('FlutterSecureStorage'));
    expect(store, contains('_payloadCipher.encrypt'));
    expect(store, contains('_payloadCipher.decrypt'));
    expect(store, contains('_encryptLegacyPayloads'));
    expect(store, contains('version: 4'));
    expect(manifest, contains('android:allowBackup="false"'));
  });

  test('reauthentication respects Remember Me and restores an interrupted signup', () async {
    final api = await File('lib/services/api_service.dart').readAsString();
    final login = await File('lib/screens/login_screen.dart').readAsString();

    expect(api, contains('rememberMe = true'));
    expect(api, contains("await prefs.setBool('remember_me', rememberMe)"));
    expect(login, contains('rememberMe: _rememberMe'));
    expect(login, contains('ApiService.getSignupDraft()'));
    expect(login, contains('resumeExistingDraft: true'));
    expect(login, contains('await _openAuthenticatedDestination()'));
  });

  test('logout preserves a pending FCM detach until the server accepts it', () async {
    final api = await File('lib/services/api_service.dart').readAsString();
    final auth = await File('../المشروع/WESAL/app/Http/Controllers/Api/AuthController.php').readAsString();
    final routes = await File('../المشروع/WESAL/routes/api.php').readAsString();

    expect(api, contains('_pendingLogoutSessionKey'));
    expect(api, contains(r"Uri.parse('$baseUrl/logout')"));
    expect(api, contains(r"headers['Authorization'] = 'Bearer $oldAccessToken'"));
    expect(auth, contains(r'public function logout(Request $request)'));
    expect(auth, contains(r"->where('fcm_token', trim($deviceToken))"));
    expect(routes, isNot(contains('device-token/detach')));
  });
}

import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/services/api_service.dart';

void main() {
  group('Sanctum restored-session response contract', () {
    test('accepts Laravel user-by-token top-level user payload', () {
      expect(
        ApiService.isValidUserByTokenPayload({
          'id': 58,
          'type': 'member',
          'name': 'Wesal Member',
        }),
        isTrue,
      );
    });

    test('accepts backward-compatible wrapped user payload', () {
      expect(
        ApiService.isValidUserByTokenPayload({
          'result': true,
          'data': {'id': 58, 'type': 'member'},
        }),
        isTrue,
      );
      expect(
        ApiService.isValidUserByTokenPayload({
          'user': {'id': 58, 'type': 'member'},
        }),
        isTrue,
      );
    });

    test('rejects missing users and failed authentication envelopes', () {
      expect(
        ApiService.isValidUserByTokenPayload({
          'message': 'Unauthenticated.',
        }),
        isFalse,
      );
      expect(
        ApiService.isValidUserByTokenPayload({
          'result': false,
          'data': {'id': 58},
        }),
        isFalse,
      );
      expect(ApiService.isValidUserByTokenPayload(null), isFalse);
    });
  });

  group('Auth token lifecycle safeguards', () {
    late String apiService;
    late String loginScreen;

    setUpAll(() {
      apiService = File('lib/services/api_service.dart').readAsStringSync();
      loginScreen = File('lib/screens/login_screen.dart').readAsStringSync();
    });

    test('generic API 401 does not delete the token or falsely report expiry', () {
      final start = apiService.indexOf('static String _parseErrorMessage');
      final end = apiService.indexOf(
        'static Future<Map<String, dynamic>?> get(',
        start,
      );
      expect(start, greaterThanOrEqualTo(0));
      expect(end, greaterThan(start));
      final method = apiService.substring(start, end);

      expect(method, isNot(contains('clearToken(')));
      expect(method, contains('لم يتم إنهاء جلسة تسجيل الدخول'));
      expect(method, isNot(contains('انتهت جلسة تسجيل الدخول')));
      expect(apiService, contains("reason: 'user_by_token_401'"));
      expect(apiService, contains('expectedToken: token'));
    });

    test('session validation protects a newer token from a stale 401', () {
      final clearStart = apiService.indexOf(
        'static Future<void> clearToken({',
      );
      final clearEnd = apiService.indexOf(
        '/// Laravel',
        clearStart,
      );
      expect(clearStart, greaterThanOrEqualTo(0));
      expect(clearEnd, greaterThan(clearStart));
      final clearMethod = apiService.substring(clearStart, clearEnd);

      expect(clearMethod, contains('expectedToken'));
      expect(clearMethod, contains('storedToken != expectedToken'));
      expect(clearMethod, contains('Skipped stale token clear'));
    });

    test('login persists the Remember Me choice without overriding it', () {
      expect(apiService, contains('await prefs.setBool(\'remember_me\', rememberMe);'));
      expect(loginScreen, contains('rememberMe: _rememberMe'));
      expect(loginScreen, isNot(contains('await ApiService.setRememberMe(true);')));
    });
  });

  group('Profile recovery safeguards', () {
    late String apiService;
    late String profileEditor;
    late String partnerEditor;

    setUpAll(() {
      apiService = File('lib/services/api_service.dart').readAsStringSync();
      profileEditor =
          File('lib/screens/edit_profile_screen.dart').readAsStringSync();
      partnerEditor =
          File('lib/screens/partner_expectations_screen.dart')
              .readAsStringSync();
    });

    test('full profile does not fan out after an authentication rejection', () {
      final start = apiService.indexOf(
        'static Future<Map<String, dynamic>?> getFullProfile',
      );
      final end = apiService.indexOf(
        'static Future<Map<String, dynamic>?> getIntroduction',
        start,
      );
      expect(start, greaterThanOrEqualTo(0));
      expect(end, greaterThan(start));
      final method = apiService.substring(start, end);
      expect(method, contains('statusCode == 401 || statusCode == 403'));
      expect(method, contains('throw ApiUnauthorizedException'));
      expect(method, contains('statusCode != 404 && statusCode != 405'));
    });

    test('partner expectations propagate authorization failures', () {
      final start = apiService.indexOf(
        'static Future<Map<String, dynamic>?> getPartnerExpectations',
      );
      final end = apiService.indexOf(
        '/// Returns only the preferred partner country ID.',
        start,
      );
      expect(start, greaterThanOrEqualTo(0));
      expect(end, greaterThan(start));
      final method = apiService.substring(start, end);
      expect(method, contains('statusCode == 401 || response.statusCode == 403'));
      expect(method, contains('on ApiUnauthorizedException'));
    });

    test('failed profile loading blocks the edit form and save path', () {
      expect(profileEditor, contains('on ApiUnauthorizedException'));
      expect(profileEditor, contains('_profileLoadError != null'));
      expect(
        profileEditor,
        contains('لم نغيّر أو نمسح أي بيانات محفوظة'),
      );
      expect(profileEditor, contains('_isLoading || _profileLoadError != null'));
    });

    test('failed partner loading blocks editing and saving', () {
      expect(partnerEditor, contains('on ApiUnauthorizedException'));
      expect(partnerEditor, contains('_loadErrorMessage != null'));
      expect(
        partnerEditor,
        contains('if (_isSaving || _loadErrorMessage != null || _isLoading) return;'),
      );
    });
  });

  group('Mobile search request contract', () {
    test('Flutter filter keys are accepted by Laravel', () {
      final api = File('lib/services/api_service.dart').readAsStringSync();
      final backend = File(
        '../المشروع/WESAL/app/Http/Controllers/Api/MemberController.php',
      ).readAsStringSync();

      for (final key in [
        'age_from',
        'age_to',
        'country_id',
        'state_id',
        'city_id',
        'marital_status',
        'religion_id',
        'page',
        'per_page',
      ]) {
        expect(api, contains("'$key':"));
        expect(backend, contains("'$key' =>"));
      }
    });

    test('search results display the actual API error message', () {
      final screen =
          File('lib/screens/home/search_results_screen.dart').readAsStringSync();
      expect(screen, contains('error is SearchMembersException'));
      expect(screen, contains('error is SearchMembersException'));
      expect(screen, contains('? error.message'));
    });
  });

  group('Member profile UI and debug privacy', () {
    test('profile expansion cards use Material as their ink surface', () {
      final screen =
          File('lib/screens/member_profile_screen.dart').readAsStringSync();
      final start = screen.indexOf('Widget _buildSectionCard({');
      final end = screen.indexOf('Widget _buildVisualColorChip(', start);
      expect(start, greaterThanOrEqualTo(0));
      expect(end, greaterThan(start));
      final method = screen.substring(start, end);

      expect(method, contains('child: Material('));
      expect(method, contains('child: ExpansionTile('));
      expect(method, contains('clipBehavior: Clip.antiAlias'));
      expect(method, isNot(contains('color: AppColors.cardColor.withValues(alpha: 0.72),\n        borderRadius:')));
    });

    test('member cards do not log raw religion or member identifiers', () {
      final model = File('lib/models/member_model.dart').readAsStringSync();
      expect(model, isNot(contains('[MemberModel] ID:')));
      expect(model, isNot(contains('RawReligion:')));
    });
  });

  group('Unified secure-storage configuration', () {
    late String storageService;
    late String apiService;
    late String profileCache;
    late String chatStore;
    late String mainEntry;

    setUpAll(() {
      storageService =
          File('lib/services/wesal_secure_storage.dart').readAsStringSync();
      apiService = File('lib/services/api_service.dart').readAsStringSync();
      profileCache =
          File('lib/services/profile_cache_service.dart').readAsStringSync();
      chatStore = File('lib/services/chat_local_store.dart').readAsStringSync();
      mainEntry = File('lib/main.dart').readAsStringSync();
    });

    test('auth, profile cache, and chat cache share one storage service', () {
      for (final source in [apiService, profileCache, chatStore]) {
        expect(source, contains("import 'wesal_secure_storage.dart';"));
        expect(source, contains('WesalSecureStorage.'));
        expect(source, isNot(contains('FlutterSecureStorage')));
      }
      expect(
        storageService,
        contains('AndroidOptions(encryptedSharedPreferences: true)'),
      );
      expect(storageService, contains('KeychainAccessibility.first_unlock_this_device'));
    });

    test('the package migrates prior secure values before app services start', () {
      expect(storageService, contains('await _storage.readAll();'));
      expect(storageService, contains("final savedUserId = prefs.getInt('user_id') ?? 0;"));
      expect(storageService, contains("final savedToken = values['user_token'];"));
      expect(storageService, contains('The saved member token is not available'));
      expect(storageService, contains('built-in Android migration'));
      expect(storageService, isNot(contains('_legacyDefaultStorage')));
      expect(storageService, isNot(contains('FlutterSecureStorage();')));
      expect(
        mainEntry.indexOf('await WesalSecureStorage.initialize();'),
        lessThan(mainEntry.indexOf('runApp(')),
      );
      expect(mainEntry, contains('Future<void> main() async'));
    });

    test('chat encryption key name is unchanged under the unified storage config', () {
      expect(chatStore, contains("_encryptionKeyStorageName = 'wesal_chat_cache_aes_key_v1'"));
      expect(
        storageService,
        contains('AndroidOptions(encryptedSharedPreferences: true)'),
      );
      expect(
        chatStore,
        contains('WesalSecureStorage.read(key: _encryptionKeyStorageName)'),
      );
      expect(
        storageService.split('FlutterSecureStorage(').length - 1,
        1,
      );
    });
  });

}

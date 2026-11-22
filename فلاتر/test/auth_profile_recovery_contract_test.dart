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

  group('Mobile search request contract', () {
    test('Flutter sends pagination and filter keys accepted by Laravel', () {
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

    test('generic error parsing does not delete the stored bearer token', () {
      final start = apiService.indexOf('static String _parseErrorMessage');
      final end = apiService.indexOf(
        'static Future<Map<String, dynamic>?> get(',
        start,
      );
      expect(start, greaterThanOrEqualTo(0));
      expect(end, greaterThan(start));
      final method = apiService.substring(start, end);
      expect(method, isNot(contains('clearToken()')));
    });

    test('failed profile loading blocks the edit form and save path', () {
      expect(profileEditor, contains('ApiUnauthorizedException'));
      expect(profileEditor, contains('_profileLoadError != null'));
      expect(
        profileEditor,
        contains('لم نغيّر أو نمسح أي بيانات محفوظة'),
      );
    });

    test('failed partner-expectation loading blocks editing and saving', () {
      expect(partnerEditor, contains('on ApiUnauthorizedException'));
      expect(partnerEditor, contains('_loadErrorMessage != null'));
      expect(
        partnerEditor,
        contains('if (_isSaving || _loadErrorMessage != null || _isLoading) return;'),
      );
    });
  });
}

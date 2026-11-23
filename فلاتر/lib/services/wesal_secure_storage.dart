import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// One Android/iOS secure-storage configuration shared by every Wesal service.
///
/// Android must not mix the default storage mode with
/// encryptedSharedPreferences in the same app preference namespace. The
/// existing chat-cache key already uses encryptedSharedPreferences, so this
/// keeps that format and migrates the app's older default-mode keys once,
/// before the app starts making authenticated requests.
class WesalSecureStorage {
  WesalSecureStorage._();

  static const FlutterSecureStorage _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
    ),
  );

  /// Used only during the one-time upgrade from the previous default config.
  static const FlutterSecureStorage _legacyDefaultStorage =
      FlutterSecureStorage();

  static const String _migrationMarker =
      'wesal_secure_storage_unified_v1';
  static const String _primaryWarmUpKey = 'wesal_chat_cache_aes_key_v1';

  // Every key previously written by the default-config ApiService and
  // ProfileCacheService. The chat-cache encryption key is already stored with
  // the target encryptedSharedPreferences configuration and is left in place.
  static const List<String> _legacyKeys = <String>[
    'user_token',
    'wesal_signup_draft_v1',
    'pending_logout_session_v1',
    'cached_user_profile_data',
    'cached_user_identity_summary',
    'cached_fcm_token',
  ];

  static Future<void>? _initialization;

  /// Must complete before the app starts auth, profile-cache, or chat-cache IO.
  /// The first run migrates old values; later starts only read a SharedPrefs
  /// marker and do not switch secure-storage configurations.
  static Future<void> initialize() {
    return _initialization ??= _initializeOnce();
  }

  static Future<void> _initializeOnce() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (prefs.getBool(_migrationMarker) == true) return;

      // Phase 1: read every legacy value while the legacy configuration is
      // active. Do not interleave legacy and target-config operations.
      final legacyValues = <String, String>{};
      for (final key in _legacyKeys) {
        final value = await _legacyDefaultStorage.read(key: key);
        if (value != null && value.isNotEmpty) {
          legacyValues[key] = value;
        }
      }

      // Phase 2: copy and verify each value using the single target config.
      // Never overwrite a value already written by this version of the app:
      // a new sign-in may have completed after an earlier migration attempt
      // was deferred, and that newer token must win over the stale legacy one.
      for (final entry in legacyValues.entries) {
        final current = await _storage.read(key: entry.key);
        if (current != null && current.isNotEmpty) continue;

        await _storage.write(key: entry.key, value: entry.value);
        final copied = await _storage.read(key: entry.key);
        if (copied != entry.value) {
          throw StateError('Secure-storage migration verification failed.');
        }
      }

      // Phase 3: remove the old Android entries only after verified copies.
      // Keychain items on iOS are retained because accessibility attributes
      // are part of their item metadata and should not be deleted by a second
      // query configuration.
      if (Platform.isAndroid) {
        for (final key in legacyValues.keys) {
          await _legacyDefaultStorage.delete(key: key);
        }
      }

      // Re-select the target Android preferences after the one-time migration.
      await _storage.read(key: _primaryWarmUpKey);
      await prefs.setBool(_migrationMarker, true);

      if (kDebugMode) {
        debugPrint(
          '[SecureStorage] Unified configuration ready; '
          'migratedEntries=${legacyValues.length}.',
        );
      }
    } catch (error) {
      // Keep the app launchable. New sign-ins can still write to the target
      // store; a later process start retries migration because the marker is
      // written only after all copy/verification steps succeed.
      if (kDebugMode) {
        debugPrint(
          '[SecureStorage] One-time migration deferred: ${error.runtimeType}.',
        );
      }
      try {
        await _storage.read(key: _primaryWarmUpKey);
      } catch (_) {
        // The calling operation reports its own secure-storage failure.
      }
    }
  }

  static Future<String?> read({required String key}) async {
    await initialize();
    return _storage.read(key: key);
  }

  static Future<void> write({
    required String key,
    required String value,
  }) async {
    await initialize();
    await _storage.write(key: key, value: value);
  }

  static Future<void> delete({required String key}) async {
    await initialize();
    await _storage.delete(key: key);
  }
}

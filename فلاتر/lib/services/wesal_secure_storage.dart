import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// One Android/iOS secure-storage configuration shared by every Wesal service.
///
/// Android must not mix the default storage mode with
/// encryptedSharedPreferences in the same app. On first use, flutter_secure_storage
/// 9.2.4 migrates values from its previous default SharedPreferences storage
/// into EncryptedSharedPreferences when this configured instance is initialized.
class WesalSecureStorage {
  WesalSecureStorage._();

  static const FlutterSecureStorage _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
    ),
  );

  static const String _migrationMarker =
      'wesal_secure_storage_unified_v1';

  static Future<void>? _initialization;

  /// Runs once before auth, profile-cache, or chat-cache IO starts.
  ///
  /// readAll() initializes this single configured native storage instance and
  /// invokes the package's built-in Android migration from the previous
  /// default storage mode. We deliberately do not instantiate a second legacy
  /// FlutterSecureStorage here: mixing option sets on the same MethodChannel
  /// is the condition that caused the original token visibility failure.
  static Future<void> initialize() {
    return _initialization ??= _initializeOnce();
  }

  static Future<void> _initializeOnce() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (prefs.getBool(_migrationMarker) == true) return;

      // On Android 9.2.4, the first ESP initialization copies each legacy
      // default-mode value to EncryptedSharedPreferences and removes the old
      // entry. On iOS, readAll simply reads the existing Keychain records.
      final values = await _storage.readAll();

      await prefs.setBool(_migrationMarker, true);
      if (kDebugMode) {
        debugPrint(
          '[SecureStorage] Unified configuration ready; '
          'availableEntries=${values.length}.',
        );
      }
    } catch (error) {
      // No marker is written after failure. A later app launch retries the
      // package migration. Never delete or reset secure data as a fallback.
      if (kDebugMode) {
        debugPrint(
          '[SecureStorage] One-time migration deferred: ${error.runtimeType}.',
        );
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

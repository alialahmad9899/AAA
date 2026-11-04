// ============================================================================
// اسم الملف: lib/services/profile_cache_service.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: خدمة التخزين المحلي السريع لحفظ واسترجاع وتفريغ بيانات البروفايل
// ============================================================================

import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ProfileCacheService {
  static const String _profileKey = 'cached_user_profile_data';
  static const String _fcmTokenKey = 'cached_fcm_token';
  static const FlutterSecureStorage _secureStorage = FlutterSecureStorage();

  /// حفظ بيانات البروفايل الكاملة محلياً بتنسيق JSON
  static Future<void> saveProfileToCache(
      Map<String, dynamic> jsonResponse) async {
    try {
      if (jsonResponse.isEmpty) return;
      final String rawJson = jsonEncode(jsonResponse);
      await _secureStorage.write(key: _profileKey, value: rawJson);
      // إزالة النسخة القديمة غير المشفرة بعد نجاح الكتابة الآمنة.
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_profileKey);
    } catch (e) {
      if (kDebugMode) {
        debugPrint('⚠️ [ProfileCache Error] Failed to save profile: $e');
      }
    }
  }

  /// استرجاع بيانات البروفايل المحفوظة محلياً بسرعة البرق (خلال ملي ثوانٍ)
  static Future<Map<String, dynamic>?> getCachedProfile() async {
    try {
      String? rawJson = await _secureStorage.read(key: _profileKey);
      if (rawJson == null || rawJson.trim().isEmpty) {
        // ترحيل تلقائي للبيانات القديمة من SharedPreferences إلى التخزين الآمن.
        final prefs = await SharedPreferences.getInstance();
        rawJson = prefs.getString(_profileKey);
        if (rawJson != null && rawJson.trim().isNotEmpty) {
          await _secureStorage.write(key: _profileKey, value: rawJson);
          await prefs.remove(_profileKey);
        }
      }
      if (rawJson != null && rawJson.trim().isNotEmpty) {
        final dynamic decoded = jsonDecode(rawJson);
        if (decoded is Map<String, dynamic>) {
          return decoded;
        } else if (decoded is Map) {
          return Map<String, dynamic>.from(decoded);
        }
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('⚠️ [ProfileCache Error] Failed to read cached profile: $e');
      }
      return null;
    }
    return null;
  }

  /// مسح بيانات التخزين المؤقت بالكامل لمنع ظهور بيانات المستخدم السابق
  static Future<void> clearCache() async {
    try {
      await _secureStorage.delete(key: _profileKey);
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_profileKey);
      await prefs.remove(_fcmTokenKey);
    } catch (e) {
      if (kDebugMode) {
        debugPrint('⚠️ [ProfileCache Error] Failed to clear cache: $e');
      }
    }
  }

  /// التحقق مما إذا كان FCM Token محليًا هو نفسه لمنع تكرار إرسال الطلب للسيرفر
  static Future<bool> shouldUpdateFcmToken(String newToken) async {
    try {
      final String cleanToken = newToken.trim();
      if (cleanToken.isEmpty) return false;

      final prefs = await SharedPreferences.getInstance();
      final String? oldToken = prefs.getString(_fcmTokenKey);

      if (oldToken == cleanToken) {
        return false; // لا داعي لإعادة الإرسال، التوكن مطابق
      }

      await prefs.setString(_fcmTokenKey, cleanToken);
      return true; // التوكن جديد، يجب التحديث
    } catch (e) {
      if (kDebugMode) {
        debugPrint(
            '⚠️ [ProfileCache Error] Failed to check/update FCM token: $e');
      }
      return true; // في حال حدوث خطأ في التخزين المحلي، نعود بـ true للضمان
    }
  }
}

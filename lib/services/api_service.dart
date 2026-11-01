// ============================================================================
// اسم الملف: lib/services/api_service.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: كلاس إداري شامل لجميع الاتصالات بسيرفر Laravel API محصن ضد مهلة الاتصال
//        وانقطاع الشبكة وانتهائها (401) ومعالج بكفاءة في الخلفية عبر Isolates،
//        مع دعم كامل ومضمون لتمرير متغيرات اللغة وتسجيل الدخول عبر Google ورسم الصورة الشخصية مباشرة أثناء التسجيل.
// ============================================================================

import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/member_model.dart';
import '../models/chat_model.dart';
import '../models/notification_model.dart';
import '../models/member_profile_model.dart';
import '../models/support_ticket_model.dart';
import 'profile_cache_service.dart';

// ============================================================================
// دوال معالجة الـ JSON في خلفية النظام (Top-Level Isolates Parsers)
// ============================================================================

Map<String, dynamic>? _extractMapDataFromDynamic(dynamic responseBody) {
  if (responseBody == null) return null;
  dynamic data = responseBody;

  if (responseBody is Map && responseBody.containsKey('data')) {
    data = responseBody['data'];
  }

  if (data is List && data.isNotEmpty) {
    final first = data.first;
    if (first is Map<String, dynamic>) return first;
    if (first is Map) return Map<String, dynamic>.from(first);
  } else if (data is Map<String, dynamic>) {
    return data;
  } else if (data is Map) {
    return Map<String, dynamic>.from(data);
  }

  return null;
}

Map<String, dynamic>? _parseGenericMapIsolate(String responseBody) {
  try {
    final decoded = jsonDecode(responseBody);
    return _extractMapDataFromDynamic(decoded);
  } catch (_) {
    return null;
  }
}

List<dynamic> _parseStatesIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    if (data is List) return data;
    if (data is Map) {
      final states = data['states'] ?? data['data'] ?? data['result'];
      if (states is List) return states;
    }
  } catch (_) {}
  return [];
}

List<dynamic> _parseCitiesIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    if (data is List) return data;
    if (data is Map) {
      final cities = data['cities'] ?? data['data'] ?? data['result'];
      if (cities is List) return cities;
    }
  } catch (_) {}
  return [];
}

Map<String, dynamic>? _parseDropdownsIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    if (data == null) return null;
    if (data is Map && data.containsKey('data')) {
      final inner = data['data'];
      if (inner is Map<String, dynamic>) return inner;
      if (inner is Map) return Map<String, dynamic>.from(inner);
    }
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return Map<String, dynamic>.from(data);
  } catch (_) {}
  return null;
}

List<MemberModel> _parseMatchedProfilesIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    List list = [];
    if (data is List) {
      list = data;
    } else if (data is Map) {
      if (data['data'] is List) {
        list = data['data'];
      } else if (data['members'] is List) {
        list = data['members'];
      }
    }
    return list
        .whereType<Map>()
        .map((item) => MemberModel.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  } catch (_) {
    return [];
  }
}

List<ChatThreadModel> _parseChatThreadsIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    List list = [];
    if (data is List) {
      list = data;
    } else if (data is Map) {
      if (data['data'] is List) {
        list = data['data'];
      } else if (data['chat_threads'] is List) {
        list = data['chat_threads'];
      } else if (data['chats'] is List) {
        list = data['chats'];
      } else if (data['threads'] is List) {
        list = data['threads'];
      }
    }

    List<ChatThreadModel> threads = [];
    for (var item in list) {
      try {
        if (item is Map<String, dynamic>) {
          threads.add(ChatThreadModel.fromJson(item));
        } else if (item is Map) {
          threads
              .add(ChatThreadModel.fromJson(Map<String, dynamic>.from(item)));
        }
      } catch (_) {}
    }
    return threads;
  } catch (_) {
    return [];
  }
}

List<NotificationModel> _parseNotificationsIsolate(String responseBody) {
  try {
    final dynamic body = jsonDecode(responseBody);
    List list = [];
    if (body is List) {
      list = body;
    } else if (body is Map) {
      if (body['data'] is List) {
        list = body['data'];
      } else if (body['notifications'] is List) {
        list = body['notifications'];
      }
    }

    List<NotificationModel> notifications = [];
    for (var item in list) {
      try {
        if (item is Map<String, dynamic>) {
          final notif = NotificationModel.fromJson(item);
          if (notif.check) notifications.add(notif);
        } else if (item is Map) {
          final notif =
              NotificationModel.fromJson(Map<String, dynamic>.from(item));
          if (notif.check) notifications.add(notif);
        }
      } catch (_) {}
    }
    return notifications;
  } catch (_) {
    return [];
  }
}

List<MemberModel> _parseIgnoredUsersIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    List list = [];
    if (data is List) {
      list = data;
    } else if (data is Map) {
      if (data['data'] is List) {
        list = data['data'];
      } else if (data['ignored_users'] is List) {
        list = data['ignored_users'];
      } else if (data['result'] is List) {
        list = data['result'];
      }
    }
    return list
        .whereType<Map>()
        .map((item) => MemberModel.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  } catch (_) {
    return [];
  }
}

List<MemberModel> _parseSearchMembersIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    List list = [];
    if (data is Map && data['data'] != null) {
      if (data['data'] is Map && data['data']['members'] is List) {
        list = data['data']['members'];
      } else if (data['data'] is List) {
        list = data['data'];
      }
    } else if (data is Map && data['members'] is List) {
      list = data['members'];
    } else if (data is List) {
      list = data;
    }
    return list
        .whereType<Map>()
        .map((item) => MemberModel.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  } catch (_) {
    return [];
  }
}

List<SupportCategoryModel> _parseSupportCategoriesIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    List list = [];
    if (data is List) {
      list = data;
    } else if (data is Map) {
      if (data['data'] is List) {
        list = data['data'];
      } else if (data['result'] is List) {
        list = data['result'];
      }
    }
    return list
        .whereType<Map>()
        .map((item) =>
            SupportCategoryModel.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  } catch (_) {
    return [];
  }
}

List<SupportTicketModel> _parseSupportTicketsIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    List list = [];
    if (data is List) {
      list = data;
    } else if (data is Map) {
      if (data['data'] is List) {
        list = data['data'];
      } else if (data['result'] is List) {
        list = data['result'];
      }
    }
    return list
        .whereType<Map>()
        .map((item) =>
            SupportTicketModel.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  } catch (_) {
    return [];
  }
}

SupportTicketModel? _parseSupportTicketDetailsIsolate(String responseBody) {
  try {
    final data = jsonDecode(responseBody);
    Map<String, dynamic>? ticketMap;
    if (data is Map) {
      final converted = Map<String, dynamic>.from(data);
      if (converted['data'] is Map) {
        ticketMap = Map<String, dynamic>.from(converted['data']);
      } else {
        ticketMap = converted;
      }
    }
    if (ticketMap != null) {
      return SupportTicketModel.fromJson(ticketMap);
    }
  } catch (_) {}
  return null;
}

// ============================================================================
// كلاس ApiService الرئيسي
// ============================================================================

class ApiService {
  static const String baseUrl = 'https://www.wesalsyria.com/api';
  static const _secureStorage = FlutterSecureStorage();
  static const Duration _timeoutDuration = Duration(seconds: 20);

  /// توحيد تفسير استجابات API في جميع الشاشات.
  /// HTTP 2xx لا يكفي وحده إذا صرّح الخادم أن العملية فشلت.
  static bool isSuccessfulResult(Map<String, dynamic>? response) {
    // لا نعتبر جسم استجابة فارغاً تأكيداً لعملية كتابة أو دفع؛ يجب أن
    // يحتوي الخادم على الأقل على رسالة/بيانات أو علامة نتيجة قابلة للفحص.
    if (response == null || response.isEmpty) return false;

    bool isExplicitFalse(dynamic value) {
      if (value == false) return true;
      if (value is num && value == 0) return true;
      if (value is String) {
        final normalized = value.trim().toLowerCase();
        return normalized == 'false' ||
            normalized == '0' ||
            normalized == 'failed' ||
            normalized == 'failure' ||
            normalized == 'error';
      }
      return false;
    }

    for (final key in const ['success', 'result']) {
      if (response.containsKey(key) && isExplicitFalse(response[key])) {
        return false;
      }
    }

    final status = response['status'];
    if (isExplicitFalse(status)) return false;
    if (status is num && status >= 400) return false;
    if (status is String) {
      final normalized = status.trim().toLowerCase();
      final numericStatus = int.tryParse(normalized);
      if (normalized == 'failed' ||
          normalized == 'failure' ||
          normalized == 'error' ||
          (numericStatus != null && numericStatus >= 400)) {
        return false;
      }
    }

    final errors = response['errors'];
    if (errors is Map && errors.isNotEmpty) return false;
    if (errors is List && errors.isNotEmpty) return false;
    if (errors is String && errors.trim().isNotEmpty) return false;

    final error = response['error'];
    if (error != null) {
      if (error == true) return false;
      if (error is String && error.trim().isNotEmpty) return false;
      if (error is Map && error.isNotEmpty) return false;
      if (error is List && error.isNotEmpty) return false;
    }

    final nestedData = response['data'];
    // لا نفسر حقول status الخاصة بكيان المستخدم على أنها حالة طلب API.
    // نفحص data بشكل متداخل فقط إذا بدا أنها غلاف استجابة مستقل.
    if (nestedData is Map) {
      final nested = Map<String, dynamic>.from(nestedData);
      final looksLikeResponseEnvelope = nested.containsKey('success') ||
          nested.containsKey('result') ||
          nested.containsKey('errors') ||
          nested.containsKey('error');
      if (looksLikeResponseEnvelope && !isSuccessfulResult(nested)) {
        return false;
      }
    }

    return true;
  }

  /// Returns true only when a profile field should be sent to the API.
  /// Existing records are updated only when a value differs from the value
  /// loaded from the server; new non-empty values are submitted.
  static bool shouldSendChangedProfileField({
    required bool wasLoaded,
    dynamic originalValue,
    dynamic currentValue,
  }) {
    final current = currentValue?.toString().trim() ?? '';
    if (!wasLoaded) return current.isNotEmpty;
    final original = originalValue?.toString().trim() ?? '';
    // An intentionally cleared optional field is also a real change.
    return current != original;
  }

  /// Extract the first actual model record from Laravel resource responses.
  /// Supports a bare map, a JsonResource {data: {...}}, a collection
  /// {data: [...]}, or a nested response envelope.
  static Map<String, dynamic>? extractFirstResourceMap(dynamic value) {
    if (value is List) {
      for (final item in value) {
        final found = extractFirstResourceMap(item);
        if (found != null) return found;
      }
      return null;
    }
    if (value is! Map) return null;

    final map = Map<String, dynamic>.from(value);
    if (map['data'] is Map || map['data'] is List) {
      return extractFirstResourceMap(map['data']);
    }

    const envelopeKeys = {
      'result', 'success', 'message', 'status', 'errors', 'error'
    };
    final hasModelFields = map.keys.any((key) => !envelopeKeys.contains(key));
    return hasModelFields ? map : null;
  }

  static bool _isHttpResponseSuccessful(int statusCode, dynamic responseData) {
    if (statusCode < 200 || statusCode >= 300) return false;
    if (statusCode == 204) return true;
    if (responseData is Map) {
      return isSuccessfulResult(Map<String, dynamic>.from(responseData));
    }
    if (responseData is bool) return responseData;
    if (responseData is num) return responseData != 0;
    if (responseData is String) {
      final normalized = responseData.trim().toLowerCase();
      return normalized == 'success' ||
          normalized == 'ok' ||
          normalized == 'true' ||
          normalized == '1';
    }
    return false;
  }

  // تخزين مؤقت مفهرس باللغة لمنع تعارض اللغات عند التحويل
  static final Map<String, Map<String, dynamic>> _cachedDropdowns = {};

  static void clearDropdownCache() {
    _cachedDropdowns.clear();
  }

  /// جلب التوكن المشفّر بآمان
  static Future<String?> getToken() async {
    try {
      final token = await _secureStorage.read(key: 'user_token');
      if (token == null || token.isEmpty) return null;
      String cleaned = token.trim();
      if (cleaned.startsWith('Bearer ')) {
        cleaned = cleaned.substring(7).trim();
      }
      return cleaned.isEmpty ? null : cleaned;
    } catch (e) {
      if (kDebugMode) {
        debugPrint('⚠ [SecureStorage Read Error]: $e');
      }
      return null;
    }
  }

  /// حفظ التوكن مشفراً
  static Future<void> saveToken(String token) async {
    String cleaned = token.trim();
    if (cleaned.startsWith('Bearer ')) {
      cleaned = cleaned.substring(7).trim();
    }
    if (cleaned.isNotEmpty) {
      await _secureStorage.write(key: 'user_token', value: cleaned);
    }
  }

  /// مسح التوكن والكاش عند تسجيل الخروج أو انتهاء الجلسة
  static Future<void> clearToken() async {
    try {
      await _secureStorage.delete(key: 'user_token');
      final SharedPreferences prefs = await SharedPreferences.getInstance();
      await prefs.remove('user_id');
      await ProfileCacheService.clearCache();
      clearDropdownCache();
    } catch (e) {
      if (kDebugMode) debugPrint('⚠ [Error Clearing Token]: $e');
    }
  }

  /// 🟢 دالة تسجيل الخروج المباشرة وإبلاغ سيرفر Laravel بتصفير الجلسة وتوكن FCM
  static Future<Map<String, dynamic>?> logout() async {
    try {
      String? deviceToken;
      try {
        deviceToken = await FirebaseMessaging.instance.getToken();
      } catch (e) {
        if (kDebugMode) debugPrint('⚠️ تعذر قراءة FCM token عند الخروج: $e');
      }
      if (kDebugMode) debugPrint('🌐 [API POST] Logout -> $baseUrl/logout');
      final response = await post('/logout', {
        if (deviceToken != null && deviceToken.isNotEmpty)
          'device_token': deviceToken,
      });
      await clearToken();
      return response;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR Logout]: $e');
      await clearToken();
      return {
        'result': false,
        'message': 'حدث خطأ في الاتصال بالسيرفر أثناء تسجيل الخروج',
      };
    }
  }

  /// 🟢 دالة حذف الحساب نهائياً من سيرفر Laravel
  static Future<Map<String, dynamic>> deleteAccount() async {
    try {
      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] DeleteAccount -> $baseUrl/member/account/delete');
      }
      final res = await post('/member/account/delete', {});
      if (res != null &&
          (res['result'] == true ||
              res['status'] == 200 ||
              res['success'] == true)) {
        await clearToken();
        return {
          'success': true,
          'message': res['message'] ?? 'تم حذف حسابك بنجاح',
        };
      }
      return {
        'success': false,
        'message': res?['message'] ?? 'فشل حذف الحساب من السيرفر',
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR deleteAccount]: $e');
      return {
        'success': false,
        'message': 'حدث خطأ في الاتصال بالسيرفر أثناء حذف الحساب',
      };
    }
  }

  static Future<Map<String, String>> _getHeaders({String? lang}) async {
    final token = await getToken();
    final prefs = await SharedPreferences.getInstance();
    final String currentLang = lang ?? prefs.getString('app_language') ?? 'ar';

    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Accept-Language': currentLang,
      'app_language': currentLang,
      'lang': currentLang,
      'X-Requested-With': 'XMLHttpRequest',
      'Cache-Control': 'no-cache, no-store, must-revalidate',
      'Pragma': 'no-cache',
      'Expires': '0',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
  }

  static Map<String, dynamic>? _extractMapData(dynamic responseBody) {
    return _extractMapDataFromDynamic(responseBody);
  }

  static String getOptimizedFlagUrl(Map<String, dynamic> country) {
    final String code =
        (country['code'] ?? country['iso'] ?? country['iso2'] ?? '')
            .toString()
            .toLowerCase();
    final String name = (country['name'] ?? country['country_name'] ?? '')
        .toString()
        .toLowerCase();

    if (code == 'sy' ||
        code == 'syr' ||
        name.contains('سوريا') ||
        name.contains('سورية') ||
        name.contains('syria')) {
      return 'https://flagcdn.com/w80/sy.png';
    }
    if (code.isNotEmpty) {
      return 'https://flagcdn.com/w80/$code.png';
    }
    return 'https://flagcdn.com/w80/un.png';
  }

  static String _parseErrorMessage(dynamic responseData, int statusCode) {
    if (statusCode == 401) {
      unawaited(clearToken());
      return 'انتهت جلسة تسجيل الدخول، يرجى تسجيل الدخول مجدداً';
    }
    if (responseData == null) return 'حدث خطأ في السيرفر (رمز: $statusCode)';
    if (responseData is Map && responseData['message'] != null) {
      return responseData['message'].toString();
    }
    return 'حدث خطأ غير متوقع (رمز: $statusCode)';
  }

  /// 🟢 دالة إرسال طلبات GET العامة
  static Future<Map<String, dynamic>?> get(
    String endpoint, {
    String? lang,
  }) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final prefs = await SharedPreferences.getInstance();
      final String currentLang =
          lang ?? prefs.getString('app_language') ?? 'ar';

      String cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/$endpoint';
      if (!cleanEndpoint.contains('lang=')) {
        final String separator = cleanEndpoint.contains('?') ? '&' : '?';
        cleanEndpoint =
            '$cleanEndpoint${separator}lang=$currentLang&app_language=$currentLang';
      }

      final Uri url = Uri.parse('$baseUrl$cleanEndpoint');

      if (kDebugMode) {
        debugPrint('🌐 [API GET Generic] -> $url');
      }

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic decoded;
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        decoded = null;
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        if (decoded is Map<String, dynamic>) {
          return decoded;
        } else if (decoded is Map) {
          return Map<String, dynamic>.from(decoded);
        }
        return {'result': true, 'data': decoded};
      } else {
        return {
          'result': false,
          'status': response.statusCode,
          'message': _parseErrorMessage(decoded, response.statusCode),
        };
      }
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR Generic GET]: $e');
      return {
        'result': false,
        'message': 'حدث خطأ في الاتصال بالسيرفر: $e',
      };
    }
  }

  /// 🟢 دالة إرسال طلبات POST العامة
  static Future<Map<String, dynamic>?> post(
    String endpoint,
    Map<String, dynamic> body, {
    String? lang,
  }) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final prefs = await SharedPreferences.getInstance();
      final String currentLang =
          lang ?? prefs.getString('app_language') ?? 'ar';

      String cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/$endpoint';
      if (!cleanEndpoint.contains('lang=')) {
        final String separator = cleanEndpoint.contains('?') ? '&' : '?';
        cleanEndpoint =
            '$cleanEndpoint${separator}lang=$currentLang&app_language=$currentLang';
      }

      final Uri url = Uri.parse('$baseUrl$cleanEndpoint');

      if (kDebugMode) {
        debugPrint('🌐 [API POST Generic] -> $url | Body: $body');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(body),
          )
          .timeout(_timeoutDuration);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic decoded;
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        decoded = null;
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        if (decoded is Map<String, dynamic>) {
          return decoded;
        } else if (decoded is Map) {
          return Map<String, dynamic>.from(decoded);
        }
        return {'result': true, 'data': decoded};
      } else {
        return {
          'result': false,
          'status': response.statusCode,
          'message': _parseErrorMessage(decoded, response.statusCode),
        };
      }
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR Generic POST]: $e');
      return {
        'result': false,
        'message': 'حدث خطأ في الاتصال بالسيرفر: $e',
      };
    }
  }

  // 1. قسم المصادقة وتسجيل الدخول
  static Future<Map<String, dynamic>> signin(
      String email, String password) async {
    final Uri url = Uri.parse('$baseUrl/signin');
    final String cleanEmail = email.trim().toLowerCase();
    final String cleanPassword = password.trim();

    try {
      final headers = await _getHeaders();
      if (kDebugMode) debugPrint('🌐 [API POST] Signin -> $url');
      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'email': cleanEmail,
              'email_or_phone': cleanEmail,
              'password': cleanPassword,
              'login_by': 'email',
            }),
          )
          .timeout(_timeoutDuration);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic decoded;
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        decoded = null;
      }

      final Map<String, dynamic> responseData = decoded is Map
          ? Map<String, dynamic>.from(decoded)
          : <String, dynamic>{};

      if ((response.statusCode == 200 || response.statusCode == 201) &&
          (responseData['result'] == true ||
              responseData['access_token'] != null ||
              responseData['token'] != null)) {
        await ProfileCacheService.clearCache();
        clearDropdownCache();

        SharedPreferences prefs = await SharedPreferences.getInstance();
        String? token = responseData['access_token'] ?? responseData['token'];
        if (token != null) {
          await saveToken(token);
        }
        if (responseData['user'] != null &&
            responseData['user']['id'] != null) {
          await prefs.setInt('user_id', responseData['user']['id']);
        }
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': _parseErrorMessage(responseData, response.statusCode),
        };
      }
    } on TimeoutException {
      return {
        'success': false,
        'message': 'انتهت مهلة الاتصال بالسيرفر، حاول مرة أخرى'
      };
    } on SocketException {
      return {
        'success': false,
        'message': 'تعذر الاتصال بالشبكة، يرجى التحقق من الإنترنت'
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR Signin]: $e');
      return {
        'success': false,
        'message': 'تعذر الاتصال بالسيرفر، تأكد من اتصالك بالإنترنت'
      };
    }
  }

  /// 🟢 دالة تسجيل الدخول عبر Google المباشرة مع السيرفر
  static Future<Map<String, dynamic>> googleSignIn({
    required String email,
    required String name,
    required String googleId,
    String? idToken,
    String? accessToken,
    String? photoUrl,
  }) async {
    final Uri url = Uri.parse('$baseUrl/auth/google');
    try {
      final headers = await _getHeaders();
      if (kDebugMode) debugPrint('🌐 [API POST] GoogleSignIn -> $url');

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'email': email.trim().toLowerCase(),
              'name': name.trim(),
              'google_id': googleId,
              'id_token': idToken,
              'access_token': accessToken,
              'photo_url': photoUrl,
            }),
          )
          .timeout(_timeoutDuration);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic decoded;
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        decoded = null;
      }

      final Map<String, dynamic> responseData = decoded is Map
          ? Map<String, dynamic>.from(decoded)
          : <String, dynamic>{};

      if ((response.statusCode == 200 || response.statusCode == 201) &&
          (responseData['result'] == true ||
              responseData['access_token'] != null ||
              responseData['token'] != null)) {
        await ProfileCacheService.clearCache();
        clearDropdownCache();

        SharedPreferences prefs = await SharedPreferences.getInstance();
        String? token = responseData['access_token'] ?? responseData['token'];
        if (token != null) {
          await saveToken(token);
        }
        if (responseData['user'] != null &&
            responseData['user']['id'] != null) {
          await prefs.setInt('user_id', responseData['user']['id']);
        }
        return {'success': true, 'is_registered': true, 'data': responseData};
      } else if (responseData['is_registered'] == false) {
        final googleData = responseData['google_data'] ?? {};
        return {
          'success': false,
          'is_registered': false,
          'email': googleData['email'] ?? email,
          'name': googleData['name'] ?? name,
          'google_id': googleData['google_id'] ?? googleId,
          'message': responseData['message'] ?? 'يرجى إكمال إنشاء الحساب',
        };
      } else {
        return {
          'success': false,
          'is_registered': true,
          'message': _parseErrorMessage(responseData, response.statusCode),
        };
      }
    } on TimeoutException {
      return {
        'success': false,
        'message': 'انتهت مهلة الاتصال بالسيرفر، حاول مرة أخرى'
      };
    } on SocketException {
      return {
        'success': false,
        'message': 'تعذر الاتصال بالشبكة، يرجى التحقق من الإنترنت'
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR GoogleSignIn]: $e');
      return {
        'success': false,
        'message': 'تعذر الاتصال بالسيرفر، تأكد من اتصالك بالإنترنت'
      };
    }
  }

  /// 🟢 دالة إنشاء الحساب المعدّلة لإرفاق الصورة الشخصية مباشرة
  static Future<Map<String, dynamic>> signup({
    required String firstName,
    required String lastName,
    required String gender,
    required String dateOfBirth,
    required String email,
    required String password,
    required String passwordConfirmation,
    String onBehalf = '1',
    File? photoFile,
  }) async {
    final Uri url = Uri.parse('$baseUrl/signup');
    final String cleanEmail = email.trim().toLowerCase();

    try {
      final headers = await _getHeaders();
      if (kDebugMode) debugPrint('🌐 [API POST Multipart] Signup -> $url');

      var request = http.MultipartRequest('POST', url);

      // نقل الهيدرز باستثناء Content-Type لتتيح للمكتبة ضبط boundary تلقائياً
      headers.forEach((key, value) {
        if (key.toLowerCase() != 'content-type') {
          request.headers[key] = value;
        }
      });
      request.headers['Accept'] = 'application/json';

      request.fields['on_behalf'] = onBehalf;
      request.fields['first_name'] = firstName.trim();
      request.fields['last_name'] = lastName.trim();
      request.fields['gender'] = gender;
      request.fields['date_of_birth'] = dateOfBirth;
      request.fields['email'] = cleanEmail;
      request.fields['password'] = password.trim();
      request.fields['password_confirmation'] = passwordConfirmation.trim();
      request.fields['checkbox_example_1'] = '1';

      if (photoFile != null) {
        if (!await photoFile.exists()) {
          return {
            'success': false,
            'message': 'ملف الصورة المحدد غير موجود. يرجى اختيار الصورة مرة أخرى.',
          };
        }
        final photoSize = await photoFile.length();
        if (photoSize <= 0 || photoSize > 10 * 1024 * 1024) {
          return {
            'success': false,
            'message': 'يجب أن يكون حجم الصورة أكبر من صفر وألا يتجاوز 10 ميغابايت.',
          };
        }
        request.files.add(
          await http.MultipartFile.fromPath('photo', photoFile.path),
        );
      }

      var streamedResponse = await request.send().timeout(_timeoutDuration);
      var response = await http.Response.fromStream(streamedResponse);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic decoded;
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        decoded = null;
      }

      final Map<String, dynamic> responseData = decoded is Map
          ? Map<String, dynamic>.from(decoded)
          : <String, dynamic>{};

      final bool responseSucceeded =
          _isHttpResponseSuccessful(response.statusCode, responseData);
      final dynamic authData =
          responseData['data'] is Map ? responseData['data'] : const {};
      final dynamic rawToken = responseData['access_token'] ??
          responseData['token'] ??
          (authData is Map
              ? authData['access_token'] ?? authData['token']
              : null);
      final String token = rawToken?.toString().trim() ?? '';

      if (responseSucceeded && token.isNotEmpty) {
        await ProfileCacheService.clearCache();
        clearDropdownCache();

        await saveToken(token);
        final savedToken = await getToken();
        if (savedToken == null || savedToken.isEmpty) {
          return {
            'success': false,
            'message':
                'تم إنشاء الحساب لكن تعذر حفظ جلسة الدخول. يرجى تسجيل الدخول.',
          };
        }

        final SharedPreferences prefs = await SharedPreferences.getInstance();
        final dynamic userData = responseData['user'] ??
            (authData is Map ? authData['user'] : null);
        final dynamic rawUserId = userData is Map ? userData['id'] : null;
        final int? userId = rawUserId is int
            ? rawUserId
            : int.tryParse(rawUserId?.toString() ?? '');
        if (userId != null) {
          await prefs.setInt('user_id', userId);
        }
        return {'success': true, 'data': responseData};
      }

      final message = responseSucceeded && token.isEmpty
          ? 'استجاب الخادم لإنشاء الحساب دون إرجاع رمز جلسة صالح. يرجى تسجيل الدخول أو التواصل مع الدعم.'
          : _parseErrorMessage(responseData, response.statusCode);
      return {'success': false, 'message': message};
    } on TimeoutException {
      return {'success': false, 'message': 'انتهت مهلة الاتصال بالسيرفر'};
    } on SocketException {
      return {'success': false, 'message': 'تعذر الاتصال بالشبكة'};
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR Signup]: $e');
      return {
        'success': false,
        'message': 'تعذر الاتصال بالسيرفر، تأكد من اتصالك بالإنترنت'
      };
    }
  }

  // 2. قسم جلب بيانات الملف الشخصي والقوائم المنسدلة

  static Future<Map<String, dynamic>?> getFullProfile({String? lang}) async {
    try {
      // Prefer the consolidated Laravel endpoint (one authenticated request).
      // Fall back to the established per-section requests on older hosting builds.
      final consolidatedResponse =
          await get('/member/full-my-profile', lang: lang);
      final consolidatedData = _extractMapData(consolidatedResponse);
      if (consolidatedResponse != null &&
          consolidatedResponse['result'] != false &&
          consolidatedData != null &&
          consolidatedData.containsKey('basic_info')) {
        return consolidatedData;
      }

      final results = await Future.wait([
        getBasicInfo(lang: lang),
        getEducation(lang: lang),
        getCareer(lang: lang),
        getPhysicalAttributes(lang: lang),
        getSpiritualBackground(lang: lang),
        getResidencyInfo(lang: lang),
        getLifeStyle(lang: lang),
        getHobbies(lang: lang),
        getAttitudeBehavior(lang: lang),
        getAstronomicInfo(lang: lang),
        getIntroduction(lang: lang),
      ]);

      return {
        'basic_info': results[0],
        'education': results[1],
        'career': results[2],
        'physical_attributes': results[3],
        'spiritual_background': results[4],
        'residency_information': results[5],
        'lifestyle': results[6],
        'hobbies_interest': results[7],
        'attitude_behavior': results[8],
        'astronomic_information': results[9],
        'intoduction': results[10],
        'introduction': results[10],
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getFullProfile]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getIntroduction({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/introduction?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] Introduction -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        try {
          final decoded = jsonDecode(response.body);
          if (decoded is String && decoded.trim().isNotEmpty) {
            return {'introduction': decoded.trim()};
          }
          if (decoded is Map) {
            final dynamic inner = decoded['data'] ?? decoded['introduction'];
            if (inner is String && inner.trim().isNotEmpty) {
              return {'introduction': inner.trim()};
            }
            final parsed = _extractMapDataFromDynamic(decoded);
            if (parsed != null) return parsed;
          }
        } catch (_) {}
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getIntroduction]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateIntroduction(
    String introduction, {
    String? lang,
  }) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/introduction/update');
      final String cleanIntro = introduction.trim();
      final Map<String, dynamic> payload = {
        'introduction': cleanIntro,
        'intoduction': cleanIntro,
        'about': cleanIntro,
      };

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] UpdateIntroduction -> $url | Payload: $payload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(payload),
          )
          .timeout(_timeoutDuration);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      final bool isSuccess =
          (response.statusCode == 200 || response.statusCode == 201) &&
              !(data is Map && data['result'] == false);

      return {
        'success': isSuccess,
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : (isSuccess
                ? 'تم تحديث النبذة التعريفية بنجاح'
                : 'فشل تحديث النبذة التعريفية'),
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateIntroduction]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getProfileDropdowns(
      {String? lang}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final String currentLang =
          lang ?? prefs.getString('app_language') ?? 'ar';

      if (_cachedDropdowns.containsKey(currentLang)) {
        if (kDebugMode) {
          debugPrint(
              '⚡ [API CACHE HIT] ProfileDropdowns ($currentLang) returned from memory');
        }
        return _cachedDropdowns[currentLang];
      }

      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse(
          '$baseUrl/member/profile-dropdown?lang=$currentLang&app_language=$currentLang&_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] ProfileDropdowns -> $url');
      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) debugPrint('📥 [API RESPONSE ${response.statusCode}]');

      if (response.statusCode == 200) {
        final parsed = await compute(_parseDropdownsIsolate, response.body);
        if (parsed != null) {
          _cachedDropdowns[currentLang] = parsed;
        }
        return parsed;
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR Dropdowns]: $e');
      return null;
    }
  }

  static Future<List<dynamic>> getStatesByCountry(dynamic countryId,
      {String? lang}) async {
    try {
      final int parsedId = countryId is int
          ? countryId
          : (int.tryParse(countryId.toString()) ?? 0);
      if (parsedId == 0) return [];

      final headers = await _getHeaders(lang: lang);
      final prefs = await SharedPreferences.getInstance();
      final String currentLang =
          lang ?? prefs.getString('app_language') ?? 'ar';
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse(
          '$baseUrl/member/states/$parsedId?lang=$currentLang&app_language=$currentLang&_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] States -> $url');
      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) debugPrint('📥 [API RESPONSE ${response.statusCode}]');

      if (response.statusCode == 200) {
        return await compute(_parseStatesIsolate, response.body);
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR States]: $e');
      return [];
    }
  }

  static Future<List<dynamic>> getCitiesByState(dynamic stateId,
      {String? lang}) async {
    try {
      final int parsedId =
          stateId is int ? stateId : (int.tryParse(stateId.toString()) ?? 0);
      if (parsedId == 0) return [];

      final headers = await _getHeaders(lang: lang);
      final prefs = await SharedPreferences.getInstance();
      final String currentLang =
          lang ?? prefs.getString('app_language') ?? 'ar';
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse(
          '$baseUrl/cities/$parsedId?lang=$currentLang&app_language=$currentLang&_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] CitiesByState -> $url');
      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) debugPrint('📥 [API RESPONSE ${response.statusCode}]');

      if (response.statusCode == 200) {
        return await compute(_parseCitiesIsolate, response.body);
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR Cities]: $e');
      return [];
    }
  }

  static Future<MemberProfileModel?> getPublicProfile(int userId,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url =
          Uri.parse('$baseUrl/member/public-profile/$userId?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] PublicProfile -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        dynamic decoded;
        try {
          decoded = jsonDecode(response.body);
        } catch (_) {
          decoded = null;
        }

        if (decoded is Map) {
          final Map<String, dynamic> body = Map<String, dynamic>.from(decoded);
          if (body['result'] == true || body['data'] != null) {
            final profileData = _extractMapData(body) ?? body;
            return MemberProfileModel.fromJson(profileData, userId);
          }
        }
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR PublicProfile]: $e');
      return null;
    }
  }

  static Future<MemberProfileModel?> getCurrentUserProfile(
      {String? lang}) async {
    try {
      final SharedPreferences prefs = await SharedPreferences.getInstance();
      final int currentUserId = prefs.getInt('user_id') ?? 0;
      if (currentUserId == 0) return null;

      return await getPublicProfile(currentUserId, lang: lang);
    } catch (e) {
      return null;
    }
  }

  // 3. قسم الباقات والدفع اليدوي (شام كاش)

  static Future<Map<String, dynamic>> getActivePackages({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final prefs = await SharedPreferences.getInstance();
      final String currentLang =
          lang ?? prefs.getString('app_language') ?? 'ar';
      final Uri url = Uri.parse(
          '$baseUrl/packages?lang=$currentLang&app_language=$currentLang');
      if (kDebugMode) debugPrint('🌐 [API GET] ActivePackages -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);
        if (decoded is Map &&
            decoded['result'] != false &&
            decoded['data'] is List) {
          return {
            'success': true,
            'is_free': decoded['is_free'] == true,
            'message': decoded['message'] ?? '',
            'data': decoded['data'],
          };
        }
        return {
          'success': false,
          'status': response.statusCode,
          'message': decoded is Map
              ? (decoded['message'] ?? 'استجابة الباقات غير صالحة')
              : 'استجابة الباقات غير صالحة',
        };
      }
      return {
        'success': false,
        'status': response.statusCode,
        'message': 'تعذر جلب الباقات (HTTP ' + response.statusCode.toString() + ')',
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getActivePackages]: $e');
      return {'success': false, 'message': 'خطأ في الاتصال بالشبكة'};
    }
  }

  static Future<List<dynamic>> getManualPaymentMethods({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final prefs = await SharedPreferences.getInstance();
      final String currentLang =
          lang ?? prefs.getString('app_language') ?? 'ar';
      final Uri url = Uri.parse(
          '$baseUrl/manual-payment-methods?lang=$currentLang&app_language=$currentLang');
      if (kDebugMode) debugPrint('🌐 [API GET] ManualPaymentMethods -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['result'] == true && data['data'] is List) {
          return data['data'];
        }
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getManualPaymentMethods]: $e');
      return [];
    }
  }

  static Future<Map<String, dynamic>> purchasePackageManual({
    required int packageId,
    required double amount,
    required int manualPaymentId,
    required String transactionId,
    File? paymentProofFile,
    String? paymentDetails,
  }) async {
    try {
      final String? token = await getToken();
      final Uri url = Uri.parse('$baseUrl/member/package-purchase');

      var request = http.MultipartRequest('POST', url);
      if (token != null) request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';

      request.fields['package_id'] = packageId.toString();
      request.fields['amount'] = amount.toString();
      request.fields['payment_method'] = 'manual_payment';
      request.fields['manual_payment_id'] = manualPaymentId.toString();
      request.fields['transaction_id'] = transactionId;
      if (paymentDetails != null) {
        request.fields['payment_details'] = paymentDetails;
      }

      if (paymentProofFile != null && await paymentProofFile.exists()) {
        request.files.add(
          await http.MultipartFile.fromPath(
              'payment_proof', paymentProofFile.path),
        );
      }

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST Multipart] PurchasePackageManual -> $url | Fields: ${request.fields}');
      }

      var streamedResponse = await request.send().timeout(_timeoutDuration);
      var response = await http.Response.fromStream(streamedResponse);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      return {
        'success':
            response.statusCode == 200 && data is Map && data['result'] == true,
        'message': data is Map && data['message'] != null
            ? data['message']
            : 'تم إرسال طلب الدفع بنجاح',
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR purchasePackageManual]: $e');
      return {'success': false, 'message': 'حدث خطأ في الاتصال بالسيرفر'};
    }
  }

  // 4. قسم جلب وتحديث أجزاء الملف الشخصي
  static Future<Map<String, dynamic>?> getBasicInfo({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/basic-info?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] BasicInfo -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getBasicInfo]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateBasicInfo({
    required String firstName,
    required String lastName,
    String? phone,
    String? gender,
    required String dateOfBirth,
    String? maritalStatus,
    String? onBehalf,
    String? children,
    String? introduction,
    File? photoFile,
    String? lang,
  }) async {
    try {
      final token = await getToken();
      if (token == null || token.trim().isEmpty) {
        return {'success': false, 'message': 'انتهت جلسة الدخول. يرجى تسجيل الدخول مجدداً.'};
      }

      final Uri url = Uri.parse('$baseUrl/member/basic-info/update');
      final String currentLang = lang ??
          (await SharedPreferences.getInstance()).getString('app_language') ??
          'ar';

      final request = http.MultipartRequest('POST', url);
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';
      request.headers['Accept-Language'] = currentLang;
      request.headers['app_language'] = currentLang;

      request.fields['first_name'] = firstName.trim();
      request.fields['last_name'] = lastName.trim();
      if (phone != null) request.fields['phone'] = phone.trim();
      if (gender != null && gender.trim().isNotEmpty) {
        request.fields['gender'] = gender.trim();
      }
      if (dateOfBirth.trim().isNotEmpty) {
        request.fields['date_of_birth'] = dateOfBirth.trim();
      }
      if (maritalStatus != null && maritalStatus.trim().isNotEmpty) {
        request.fields['marital_status'] = maritalStatus.trim();
      }
      if (onBehalf != null && onBehalf.trim().isNotEmpty) {
        request.fields['on_behalf'] = onBehalf.trim();
      }
      if (children != null) {
        request.fields['children'] = children.trim();
      }
      if (introduction != null) {
        request.fields['introduction'] = introduction;
      }

      if (photoFile != null) {
        if (!await photoFile.exists()) {
          return {'success': false, 'message': 'ملف الصورة المحدد غير موجود. اختر الصورة مرة أخرى.'};
        }
        final photoSize = await photoFile.length();
        if (photoSize <= 0 || photoSize > 10 * 1024 * 1024) {
          return {'success': false, 'message': 'يجب أن يكون حجم الصورة أكبر من صفر وألا يتجاوز 10 ميغابايت.'};
        }
        request.files.add(
          await http.MultipartFile.fromPath('photo', photoFile.path),
        );
      }

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST Multipart] UpdateBasicInfo -> $url | Fields: ${request.fields.keys.toList()} | HasPhoto: ${photoFile != null}');
      }

      final streamedResponse = await request.send().timeout(_timeoutDuration);
      final response = await http.Response.fromStream(streamedResponse);
      if (kDebugMode) {
        debugPrint('📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      final bool confirmed =
          _isHttpResponseSuccessful(response.statusCode, data);
      if (confirmed) {
        return {
          'success': true,
          'message': data is Map && data['message'] != null
              ? data['message']
              : 'تم تحديث البيانات الأساسية بنجاح',
          'data': data,
        };
      }
      return {
        'success': false,
        'message': _parseErrorMessage(data, response.statusCode),
        'data': data,
      };
    } on TimeoutException {
      return {'success': false, 'message': 'انتهت مهلة حفظ البيانات'};
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateBasicInfo]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getEducation({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/education?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] Education -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getEducation]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateEducation(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final dynamic rawId = payload['id'] ?? payload['education_id'];
      final int? eduId = rawId == null
          ? null
          : (rawId is int ? rawId : int.tryParse(rawId.toString()));
      final bool isUpdate = eduId != null && eduId > 0;
      final Uri url = Uri.parse(isUpdate
          ? '$baseUrl/member/education/$eduId'
          : '$baseUrl/member/education');

      if (isUpdate) {
        headers['X-HTTP-Method-Override'] = 'PUT';
      }

      final Map<String, dynamic> bodyData = {
        if (isUpdate) '_method': 'PUT',
      };
      final degree = payload['degree']?.toString().trim();
      final institution = payload['institution']?.toString().trim();
      final rawEnd = payload['education_end'] ?? payload['end'];
      final int? endYear = rawEnd == null || rawEnd.toString().trim().isEmpty
          ? null
          : int.tryParse(rawEnd.toString().trim());

      if (degree != null && degree.isNotEmpty) bodyData['degree'] = degree;
      if (institution != null && institution.isNotEmpty) {
        bodyData['institution'] = institution;
      }
      if (payload.containsKey('education_end') || payload.containsKey('end')) {
        bodyData['education_end'] = endYear;
      }

      if (!isUpdate &&
          (!bodyData.containsKey('degree') ||
              !bodyData.containsKey('institution'))) {
        return {
          'success': false,
          'message': 'أدخل الشهادة والجامعة/التخصص قبل الحفظ.',
        };
      }
      if (isUpdate &&
          bodyData.keys.every((key) => key == '_method')) {
        return {'success': false, 'message': 'لا توجد تغييرات تعليمية لحفظها'};
      }
      if (endYear != null &&
          (endYear < 1900 || endYear > DateTime.now().year)) {
        return {'success': false, 'message': 'سنة انتهاء التعليم غير صحيحة'};
      }

      if (kDebugMode) {
        debugPrint('🌐 [API POST] UpdateEducation -> $url | Method: ${isUpdate ? 'PUT' : 'POST'} | Keys: ${bodyData.keys.toList()}');
      }
      final response = await http
          .post(url, headers: headers, body: jsonEncode(bodyData))
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint('📥 [API RESPONSE UpdateEducation ${response.statusCode}]: ${response.body}');
      }
      dynamic responseData;
      try {
        responseData = jsonDecode(response.body);
      } catch (_) {
        responseData = null;
      }

      final bool isSuccess =
          _isHttpResponseSuccessful(response.statusCode, responseData);
      return {
        'success': isSuccess,
        'message': (responseData is Map && responseData['message'] != null)
            ? responseData['message']
            : (isSuccess
                ? 'تم تحديث البيانات التعليمية بنجاح'
                : 'فشل تحديث البيانات التعليمية'),
        'data': responseData,
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateEducation]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getCareer({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/career?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] Career -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getCareer]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateCareer(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final dynamic rawId = payload['id'] ?? payload['career_id'];
      final int? careerId = rawId == null
          ? null
          : (rawId is int ? rawId : int.tryParse(rawId.toString()));
      final bool isUpdate = careerId != null && careerId > 0;
      final Uri url = Uri.parse(isUpdate
          ? '$baseUrl/member/career/$careerId'
          : '$baseUrl/member/career');

      if (isUpdate) {
        headers['X-HTTP-Method-Override'] = 'PUT';
      }

      final Map<String, dynamic> bodyData = {
        if (isUpdate) '_method': 'PUT',
      };
      final designation = payload['designation']?.toString().trim();
      final company = payload['company']?.toString().trim();
      final rawEnd = payload['end'] ?? payload['career_end'];
      final int? endYear = rawEnd == null || rawEnd.toString().trim().isEmpty
          ? null
          : int.tryParse(rawEnd.toString().trim());

      if (designation != null && designation.isNotEmpty) {
        bodyData['designation'] = designation;
      }
      if (company != null && company.isNotEmpty) bodyData['company'] = company;
      if (payload.containsKey('end') || payload.containsKey('career_end')) {
        bodyData['end'] = endYear;
      }

      if (!isUpdate && !bodyData.containsKey('designation')) {
        return {
          'success': false,
          'message': 'أدخل المسمى الوظيفي قبل الحفظ.',
        };
      }
      if (isUpdate && bodyData.keys.every((key) => key == '_method')) {
        return {'success': false, 'message': 'لا توجد تغييرات مهنية لحفظها'};
      }
      if (endYear != null &&
          (endYear < 1900 || endYear > DateTime.now().year)) {
        return {'success': false, 'message': 'سنة انتهاء العمل غير صحيحة'};
      }

      if (kDebugMode) {
        debugPrint('🌐 [API POST] UpdateCareer -> $url | Method: ${isUpdate ? 'PUT' : 'POST'} | Keys: ${bodyData.keys.toList()}');
      }
      final response = await http
          .post(url, headers: headers, body: jsonEncode(bodyData))
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint('📥 [API RESPONSE UpdateCareer ${response.statusCode}]: ${response.body}');
      }
      dynamic responseData;
      try {
        responseData = jsonDecode(response.body);
      } catch (_) {
        responseData = null;
      }

      final bool isSuccess =
          _isHttpResponseSuccessful(response.statusCode, responseData);
      return {
        'success': isSuccess,
        'message': (responseData is Map && responseData['message'] != null)
            ? responseData['message']
            : (isSuccess
                ? 'تم تحديث البيانات المهنية بنجاح'
                : 'فشل تحديث البيانات المهنية'),
        'data': responseData,
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateCareer]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getPhysicalAttributes(
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/physical-attributes?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] PhysicalAttributes -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getPhysicalAttributes]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updatePhysicalAttributes(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/physical-attributes/update');

      // Send only fields actually present in the form. The server performs
      // a partial update, so missing fields never become fabricated defaults.
      final Map<String, dynamic> enrichedPayload = Map.from(payload);
      enrichedPayload.remove('_method');
      if (enrichedPayload.containsKey('eye_color')) {
        enrichedPayload['eye_colour'] = enrichedPayload['eye_color'];
      }
      if (enrichedPayload.containsKey('hair_color')) {
        enrichedPayload['hair_colour'] = enrichedPayload['hair_color'];
      }

      if (enrichedPayload.isEmpty) {
        return {'success': false, 'message': 'لا توجد مواصفات جسدية لتحديثها'};
      }

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] UpdatePhysicalAttributes -> $url | Keys: ${enrichedPayload.keys.toList()}');
      }

      final response = await http
          .post(url, headers: headers, body: jsonEncode(enrichedPayload))
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint('📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      final bool isSuccess =
          _isHttpResponseSuccessful(response.statusCode, data);
      return {
        'success': isSuccess,
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : (isSuccess
                ? 'تم تحديث المواصفات الجسدية بنجاح'
                : 'فشل تحديث المواصفات الجسدية'),
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updatePhysicalAttributes]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getSpiritualBackground(
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/spiritual-background?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] SpiritualBackground -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getSpiritualBackground]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateSpiritualBackground(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/spiritual-background/update');

      final Map<String, dynamic> enrichedPayload = Map.from(payload);
      enrichedPayload.remove('_method');

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] UpdateSpiritualBackground -> $url | Payload: $enrichedPayload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(enrichedPayload),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      final bool isSuccess =
          _isHttpResponseSuccessful(response.statusCode, data);
      return {
        'success': isSuccess,
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : (isSuccess
                ? 'تم تحديث البيانات الدينية بنجاح'
                : 'فشل تحديث البيانات الدينية'),
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateSpiritualBackground]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getResidencyInfo({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/residency-info?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] ResidencyInfo -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getResidencyInfo]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateResidencyInfo(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/residency-info/update');

      final Map<String, dynamic> enrichedPayload = Map.from(payload);
      enrichedPayload.remove('_method');

      if (enrichedPayload.containsKey('recidency_country_id')) {
        enrichedPayload['residency_country_id'] =
            enrichedPayload['recidency_country_id'];
      }
      if (enrichedPayload.containsKey('recidency_state_id')) {
        enrichedPayload['residency_state_id'] =
            enrichedPayload['recidency_state_id'];
      }
      if (enrichedPayload.containsKey('recidency_city_id')) {
        enrichedPayload['residency_city_id'] =
            enrichedPayload['recidency_city_id'];
        enrichedPayload['city_id'] = enrichedPayload['recidency_city_id'];
      }

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] UpdateResidencyInfo -> $url | Payload: $enrichedPayload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(enrichedPayload),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      final bool isSuccess =
          _isHttpResponseSuccessful(response.statusCode, data);
      return {
        'success': isSuccess,
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : (isSuccess
                ? 'تم تحديث معلومات الإقامة بنجاح'
                : 'فشل تحديث معلومات الإقامة'),
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateResidencyInfo]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getLifeStyle({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/life-style?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] LifeStyle -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getLifeStyle]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateLifeStyle(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/life-style/update');
      if (kDebugMode) {
        debugPrint('🌐 [API POST] UpdateLifeStyle -> $url | Payload: $payload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(payload),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      return {
        'success': _isHttpResponseSuccessful(response.statusCode, data),
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : 'تم تحديث نمط الحياة بنجاح',
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateLifeStyle]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getHobbies({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/hobbies-interests?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] Hobbies -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getHobbies]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateHobbies(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/hobbies/update');
      if (kDebugMode) {
        debugPrint('🌐 [API POST] UpdateHobbies -> $url | Payload: $payload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(payload),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      return {
        'success': _isHttpResponseSuccessful(response.statusCode, data),
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : 'تم تحديث الهوايات بنجاح',
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateHobbies]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getAttitudeBehavior(
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/attitude-behavior?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] AttitudeBehavior -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getAttitudeBehavior]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateAttitudeBehavior(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/attitude-behavior/update');
      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] UpdateAttitudeBehavior -> $url | Payload: $payload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(payload),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      return {
        'success': _isHttpResponseSuccessful(response.statusCode, data),
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : 'تم تحديث بيانات الطباع والسلوك بنجاح',
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateAttitudeBehavior]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getAstronomicInfo({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/astronomic?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] AstronomicInfo -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getAstronomicInfo]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateAstronomicInfo(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/astronomic/update');
      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] UpdateAstronomicInfo -> $url | Payload: $payload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'sun_sign': payload['sun_sign'] ?? '',
              'moon_sign': '',
              'time_of_birth': '',
              'city_of_birth': '',
            }),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      return {
        'success': _isHttpResponseSuccessful(response.statusCode, data),
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : 'تم تحديث البرج الشمسي بنجاح',
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateAstronomicInfo]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>?> getFamilyInfo({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/family-info?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] FamilyInfo -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        return await compute(_parseGenericMapIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getFamilyInfo]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateFamilyInfo(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/family-info/update');
      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] UpdateFamilyInfo -> $url | Payload: $payload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(payload),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      return {
        'success': response.statusCode == 200 || response.statusCode == 201,
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : 'تم تحديث البيانات العائلية بنجاح',
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateFamilyInfo]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  // 5. توقعات الشريك والتفاعلات
  static Future<Map<String, dynamic>?> getPartnerExpectations(
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/partner-expectation?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] PartnerExpectations -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint('📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode != 200) return null;
      final parsed = await compute(_parseGenericMapIsolate, response.body);
      if (parsed == null || !isSuccessfulResult(parsed)) return null;

      final record = extractFirstResourceMap(parsed);
      if (record == null) return null;
      return {...record, 'result': true};
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getPartnerExpectations]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> updatePartnerExpectations(
      Map<String, dynamic> payload,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/partner-expectation/update');

      final Map<String, dynamic> enrichedPayload = Map.from(payload);
      enrichedPayload.remove('_method');

      if (enrichedPayload.containsKey('partner_education')) {
        enrichedPayload['pertner_education'] =
            enrichedPayload['partner_education'];
      }
      if (enrichedPayload.containsKey('partner_complexion')) {
        enrichedPayload['pertner_complexion'] =
            enrichedPayload['partner_complexion'];
        enrichedPayload['complexion'] = enrichedPayload['partner_complexion'];
      }

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] UpdatePartnerExpectations -> $url | Payload: $enrichedPayload');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(enrichedPayload),
          )
          .timeout(_timeoutDuration);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic jsonResponse;
      try {
        jsonResponse = jsonDecode(response.body);
      } catch (_) {
        jsonResponse = null;
      }

      if (_isHttpResponseSuccessful(response.statusCode, jsonResponse)) {
        return {
          'success': true,
          'message': jsonResponse['message'] ??
              ((lang == 'ar' || lang == null)
                  ? 'تم حفظ مواصفات الشريك بنجاح'
                  : 'Partner expectations saved successfully'),
        };
      } else {
        return {
          'success': false,
          'message': (jsonResponse is Map && jsonResponse['message'] != null)
              ? jsonResponse['message']
              : ((lang == 'ar' || lang == null)
                  ? 'فشل حفظ مواصفات الشريك'
                  : 'Failed to save expectations'),
        };
      }
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updatePartnerExpectations]: $e');
      return {
        'success': false,
        'message': (lang == 'ar' || lang == null)
            ? 'حدث خطأ أثناء الاتصال بالسيرفر: $e'
            : 'Connection error: $e',
      };
    }
  }

  static Future<List<MemberModel>> getMatchedProfiles({
    bool matchEnabled = false,
    String? lang,
  }) async {
    final headers = await _getHeaders(lang: lang);
    final int ts = DateTime.now().millisecondsSinceEpoch;
    final int matchParam = matchEnabled ? 1 : 0;
    final Uri url = Uri.parse(
        '$baseUrl/member/matched-profile?match_enabled=$matchParam&_t=$ts');
    if (kDebugMode) debugPrint('🌐 [API GET] MatchedProfiles -> $url');

    final response =
        await http.get(url, headers: headers).timeout(_timeoutDuration);
    if (kDebugMode) debugPrint('📥 [API RESPONSE ${response.statusCode}]');

    if (response.statusCode == 200) {
      return await compute(_parseMatchedProfilesIsolate, response.body);
    } else {
      throw Exception('فشل في جلب قائمة الأعضاء من السيرفر');
    }
  }

  /// 🟢 دالة إرسال الاهتمام المصححة مع استخراج الستاتوس والرسالة بدقة
  static Future<Map<String, dynamic>> expressInterest(int userId) async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/member/express-interest');
      if (kDebugMode) {
        debugPrint('🌐 [API POST] ExpressInterest -> $url | UserId: $userId');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'id': userId,
              'user_id': userId,
            }),
          )
          .timeout(_timeoutDuration);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      if (response.statusCode == 403) {
        return {
          'success': false,
          'status': data is Map
              ? (data['status'] ?? 'package_required')
              : 'package_required',
          'code': 403,
          'message': data is Map && data['message'] != null
              ? data['message'].toString()
              : 'يلزم الاشتراك في إحدى الباقات لإرسال طلبات الاهتمام.',
        };
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        final bool isSuccess = data is Map &&
            (data['result'] == true ||
                data['status'] == true ||
                data['status'] == 200 ||
                data['status'] == 'success');

        final String status = data is Map && data['status'] != null
            ? data['status'].toString()
            : (isSuccess ? 'success' : 'failed');

        final String message = data is Map && data['message'] != null
            ? data['message'].toString()
            : (isSuccess
                ? 'تم إرسال طلب الاهتمام بنجاح!'
                : 'تعذر إرسال طلب الاهتمام');

        return {
          'success': isSuccess,
          'status': status,
          'code': response.statusCode,
          'message': message,
        };
      }

      return {
        'success': false,
        'status': data is Map && data['status'] != null
            ? data['status'].toString()
            : 'error',
        'code': response.statusCode,
        'message': _parseErrorMessage(data, response.statusCode),
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR expressInterest]: $e');
      return {
        'success': false,
        'status': 'error',
        'code': 500,
        'message': 'حدث خطأ في الاتصال بالسيرفر',
      };
    }
  }

  // ============================================================================
  // 🟢 قسم طلبات الاهتمام
  // ============================================================================

  static Map<String, dynamic> _interestListResult(
      Map<String, dynamic>? response, String failureMessage) {
    if (response == null ||
        response['result'] == false ||
        response['success'] == false) {
      return {
        'success': false,
        'data': <dynamic>[],
        'status': response?['status'] ?? 'request_failed',
        'message': response?['message'] ?? failureMessage,
      };
    }
    final dynamic rawData = response['data'];
    final List<dynamic>? items = rawData is List
        ? rawData.cast<dynamic>()
        : (rawData is Map && rawData['data'] is List)
            ? (rawData['data'] as List).cast<dynamic>()
            : null;
    if (items == null) {
      return {
        'success': false,
        'data': <dynamic>[],
        'status': 'invalid_response',
        'message': failureMessage,
      };
    }
    return {'success': true, 'data': items, 'status': 'success'};
  }

  static Future<Map<String, dynamic>> getReceivedInterests(
      {String? lang}) async {
    final res = await get('/member/interest-requests', lang: lang);
    return _interestListResult(
      res,
      'تعذر تحميل طلبات الاهتمام الواردة. اسحب لإعادة المحاولة.',
    );
  }

  static Future<Map<String, dynamic>> getSentInterests({String? lang}) async {
    final res = await get('/member/my-interests', lang: lang);
    return _interestListResult(
      res,
      'تعذر تحميل طلبات الاهتمام المرسلة. اسحب لإعادة المحاولة.',
    );
  }

  static Future<bool> acceptInterestRequest(int interestId) async {
    try {
      final res = await post('/member/interest-accept', {
        'interest_id': interestId,
        'id': interestId,
      });
      return res != null &&
          res['result'] != false &&
          (res['result'] == true || res['success'] == true);
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR acceptInterestRequest]: $e');
      return false;
    }
  }

  static Future<bool> rejectInterestRequest(int interestId) async {
    try {
      final res = await post('/member/interest-reject', {
        'interest_id': interestId,
        'id': interestId,
      });
      return res != null &&
          res['result'] != false &&
          (res['result'] == true || res['success'] == true);
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR rejectInterestRequest]: $e');
      return false;
    }
  }

  // 6. المحادثات والدردشة (Chat API)
  static Future<List<ChatThreadModel>> getChatThreads({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/chat-list?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] ChatThreads -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) debugPrint('📥 [API RESPONSE ${response.statusCode}]');

      if (response.statusCode != 200 && response.statusCode != 201) {
        throw HttpException(
          'تعذر تحميل المحادثات (HTTP ' + response.statusCode.toString() + ')',
          uri: url,
        );
      }
      dynamic decoded;
      try {
        decoded = jsonDecode(response.body);
      } on FormatException {
        throw const FormatException('استجابة المحادثات ليست JSON صالحاً');
      }
      if (decoded is Map && decoded['result'] == false) {
        throw HttpException(
          decoded['message']?.toString() ?? 'رفض الخادم جلب المحادثات',
          uri: url,
        );
      }
      final bool containsThreadList = decoded is List ||
          (decoded is Map &&
              ['data', 'chat_threads', 'chats', 'threads']
                  .any((key) => decoded[key] is List));
      if (!containsThreadList) {
        throw const FormatException('هيكل استجابة المحادثات غير متوقع');
      }
      return await compute(_parseChatThreadsIsolate, response.body);
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getChatThreads]: $e');
      rethrow;
    }
  }

  static Future<Map<String, dynamic>> getChatMessages(
      int threadId, int partnerUserId,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final SharedPreferences prefs = await SharedPreferences.getInstance();
      final int currentUserId = prefs.getInt('user_id') ?? 0;
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/chat-view/$threadId?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] ChatMessages -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        dynamic decoded;
        try {
          decoded = jsonDecode(response.body);
        } catch (_) {
          decoded = null;
        }

        if (decoded is! Map) {
          return {
            'success': false,
            'status': 'invalid_response',
            'message': 'استجابة المحادثة غير صالحة',
            'messages': <ChatMessageModel>[],
            'chat_rules': null,
          };
        }
        if (decoded['result'] == false) {
          return {
            'success': false,
            'status': decoded['status'] ?? 'request_failed',
            'message': decoded['message'] ?? 'تعذر تحميل الرسائل',
            'messages': <ChatMessageModel>[],
            'chat_rules': null,
          };
        }
        final Map data = decoded;
        Map<String, dynamic> threadData = {};
        if (data['data'] is Map) {
          threadData = Map<String, dynamic>.from(data['data']);
        } else {
          threadData = Map<String, dynamic>.from(decoded);
        }

        final dynamic rawMessages =
            threadData['messages'] ?? threadData['chats'] ?? [];
        if (rawMessages is! List) {
          return {
            'success': false,
            'status': 'invalid_response',
            'message': 'قائمة رسائل المحادثة غير صالحة',
            'messages': <ChatMessageModel>[],
            'chat_rules': null,
          };
        }
        final List messagesList = rawMessages;

        List<ChatMessageModel> parsedMessages = [];
        for (var item in messagesList) {
          try {
            if (item is Map<String, dynamic>) {
              parsedMessages.add(ChatMessageModel.fromJson(
                item,
                partnerUserId,
                currentUserId: currentUserId,
              ));
            } else if (item is Map) {
              parsedMessages.add(ChatMessageModel.fromJson(
                Map<String, dynamic>.from(item),
                partnerUserId,
                currentUserId: currentUserId,
              ));
            }
          } catch (e) {
            if (kDebugMode) debugPrint('⚠ [Error Parsing Message Item]: $e');
          }
        }

        ChatRulesModel? chatRules;
        if (data['chat_rules'] != null && data['chat_rules'] is Map) {
          chatRules = ChatRulesModel.fromJson(
              Map<String, dynamic>.from(data['chat_rules']));
        }

        return {
          'success': true,
          'status': 'success',
          'messages': parsedMessages.reversed.toList(),
          'chat_rules': chatRules,
        };
      }
      return {
        'success': false,
        'status': response.statusCode,
        'message': 'تعذر تحميل الرسائل (HTTP ' + response.statusCode.toString() + ')',
        'messages': <ChatMessageModel>[],
        'chat_rules': null,
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getChatMessages]: $e');
      return {
        'success': false,
        'status': 'network_error',
        'message': 'تعذر الاتصال بالسيرفر أثناء تحميل الرسائل',
        'messages': <ChatMessageModel>[],
        'chat_rules': null,
      };
    }
  }

  static Future<List<ChatMessageModel>> getOldMessages(
      int firstMessageId, int partnerUserId,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final SharedPreferences prefs = await SharedPreferences.getInstance();
      final int currentUserId = prefs.getInt('user_id') ?? 0;
      final Uri url = Uri.parse('$baseUrl/member/chat/old-messages');
      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] OldMessages -> $url | FirstMessageId: $firstMessageId');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({'first_message_id': firstMessageId}),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200) {
        dynamic decoded;
        try {
          decoded = jsonDecode(response.body);
        } catch (_) {
          decoded = null;
        }

        if (decoded is Map &&
            decoded['result'] == true &&
            decoded['messages'] != null &&
            decoded['messages'] is List) {
          final List list = decoded['messages'];
          final parsed = list
              .map((item) => ChatMessageModel.fromJson(
                    Map<String, dynamic>.from(item),
                    partnerUserId,
                    currentUserId: currentUserId,
                  ))
              .toList();
          return parsed.reversed.toList();
        }
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getOldMessages]: $e');
      return [];
    }
  }

  static Future<Map<String, dynamic>> sendMessage(int threadId, String message,
      {int? partnerUserId, String? lang}) async {
    return sendChatMessage(
        chatThreadId: threadId,
        message: message,
        partnerUserId: partnerUserId,
        lang: lang);
  }

  static Future<Map<String, dynamic>> sendChatMessage({
    required int chatThreadId,
    required String message,
    File? attachmentFile,
    int? partnerUserId,
    String? lang,
  }) async {
    try {
      final String? token = await getToken();
      final Uri url = Uri.parse('$baseUrl/member/chat-reply');
      final String currentLang = lang ??
          (await SharedPreferences.getInstance()).getString('app_language') ??
          'ar';

      var request = http.MultipartRequest('POST', url);
      if (token != null) {
        request.headers['Authorization'] =
            token.startsWith('Bearer ') ? token : 'Bearer $token';
      }
      request.headers['Accept'] = 'application/json';
      request.headers['Accept-Language'] = currentLang;
      request.headers['app_language'] = currentLang;

      if (chatThreadId > 0 && chatThreadId != partnerUserId) {
        request.fields['chat_thread_id'] = chatThreadId.toString();
      }

      if (partnerUserId != null && partnerUserId != 0) {
        request.fields['user_id'] = partnerUserId.toString();
        request.fields['receiver_id'] = partnerUserId.toString();
        request.fields['partner_id'] = partnerUserId.toString();
      }

      final String cleanMessage = message.trim();
      if (cleanMessage.isNotEmpty) {
        request.fields['message'] = cleanMessage;
      } else if (attachmentFile != null) {
        request.fields['message'] = 'أرسل صورة';
      } else {
        request.fields['message'] = '';
      }

      if (attachmentFile != null && await attachmentFile.exists()) {
        request.files.add(
          await http.MultipartFile.fromPath('attachment', attachmentFile.path),
        );
      }

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST Multipart] SendMessage -> $url | Fields: ${request.fields}');
      }
      var streamedResponse = await request.send().timeout(_timeoutDuration);
      var response = await http.Response.fromStream(streamedResponse);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      if (response.statusCode == 403) {
        return {
          'success': false,
          'status': data is Map
              ? (data['status'] ?? 'package_required')
              : 'package_required',
          'message': data is Map && data['message'] != null
              ? data['message']
              : 'يلزم الاشتراك في إحدى الباقات لإرسال الرسائل',
        };
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        if (data is Map && data['result'] == false) {
          return {
            'success': false,
            'status': data['status'] ?? 'failed',
            'message': data['message'] ?? 'عذراً، تعذر إرسال الرسالة.',
          };
        }
        return {
          'success': true,
          'message': 'تم إرسال الرسالة بنجاح',
          'data': data
        };
      } else {
        String errStr = 'حدث خطأ أثناء حفظ الرسالة.';
        if (data is Map && data['message'] != null) {
          errStr = data['message'].toString();
        }
        return {
          'success': false,
          'status': data is Map ? (data['status'] ?? 'error') : 'error',
          'message': errStr
        };
      }
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR sendChatMessage]: $e');
      return {
        'success': false,
        'status': 'error',
        'message': 'تعذر الاتصال بالسيرفر'
      };
    }
  }

  static Future<bool> toggleBlockUser(int chatThreadId) async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/member/chat/block-status');
      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST] ToggleBlock -> $url | ThreadId: $chatThreadId');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({'chat_thread_id': chatThreadId}),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }
      return response.statusCode == 200;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR toggleBlockUser]: $e');
      return false;
    }
  }

  static Future<Map<String, dynamic>> deleteChatThread(int threadId) async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/member/chat-delete');
      if (kDebugMode) {
        debugPrint('🌐 [API POST] DeleteChat -> $url | ThreadId: $threadId');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({'chat_thread_id': threadId}),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        return {
          'success':
              (data is Map && data['result'] != null) ? data['result'] : true,
          'message': (data is Map && data['message'] != null)
              ? data['message']
              : 'تم حذف المحادثة بنجاح',
        };
      } else {
        return {
          'success': false,
          'message': (data is Map && data['message'] != null)
              ? data['message']
              : 'تعذر حذف المحادثة من السيرفر',
        };
      }
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR deleteChatThread]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  // 7. التجاهل والإبلاغ والإشعارات

  static Future<List<MemberModel>> getIgnoredUserList({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/ignored-user-list?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] IgnoredUserList -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        return await compute(_parseIgnoredUsersIsolate, response.body);
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getIgnoredUserList]: $e');
      return [];
    }
  }

  static Future<Map<String, dynamic>> addToIgnoreList(int userId) async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/member/add-to-ignore-list');
      if (kDebugMode) {
        debugPrint('🌐 [API POST] AddToIgnore -> $url | UserId: $userId');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({'user_id': userId, 'id': userId}),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      if (response.statusCode == 200 &&
          data is Map &&
          (data['result'] == true || data['success'] == true)) {
        return {
          'success': true,
          'message': data['message'] ?? 'تم حظر المستخدم بنجاح'
        };
      }
      return {
        'success': false,
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : 'تعذر حظر المستخدم'
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR addToIgnoreList]: $e');
      return {'success': false, 'message': 'حدث خطأ في الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>> removeFromIgnoreList(int userId) async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/member/remove-from-ignored-list');
      if (kDebugMode) {
        debugPrint('🌐 [API POST] RemoveFromIgnore -> $url | UserId: $userId');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({'user_id': userId, 'id': userId}),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      if (response.statusCode == 200 &&
          data is Map &&
          (data['result'] == true || data['success'] == true)) {
        return {
          'success': true,
          'message': data['message'] ?? 'تم إلغاء الحظر بنجاح'
        };
      }
      return {
        'success': false,
        'message': (data is Map && data['message'] != null)
            ? data['message']
            : 'تعذر إلغاء الحظر'
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR removeFromIgnoreList]: $e');
      return {'success': false, 'message': 'حدث خطأ في الاتصال بالسيرفر'};
    }
  }

  static Future<Map<String, dynamic>> reportMember(
      int userId, String reason) async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/member/report-member');
      if (kDebugMode) {
        debugPrint('🌐 [API POST] ReportMember -> $url | UserId: $userId');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'user_id': userId,
              'id': userId,
              'reason': reason,
              'report_reason': reason,
            }),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      String serverMessage = 'تعذر إرسال البلاغ حالياً';
      if (data is Map &&
          data['message'] != null &&
          data['message'].toString().trim().isNotEmpty) {
        serverMessage = data['message'].toString();
      }

      final bool isSuccess =
          (response.statusCode == 200 || response.statusCode == 201) &&
              data is Map &&
              (data['result'] == true || data['success'] == true);

      return {'success': isSuccess, 'message': serverMessage};
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR reportMember]: $e');
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  static Future<bool> updateDeviceToken(String fcmToken) async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/update-device-token');
      if (kDebugMode) debugPrint('🌐 [API POST] UpdateDeviceToken -> $url');

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode({
              'fcm_token': fcmToken,
              'device_token': fcmToken,
            }),
          )
          .timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      return response.statusCode == 200 || response.statusCode == 201;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR updateDeviceToken]: $e');
      return false;
    }
  }

  static Future<List<NotificationModel>> getNotifications(
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/notifications?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] Notifications -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) debugPrint('📥 [API RESPONSE ${response.statusCode}]');

      if (response.statusCode == 200 || response.statusCode == 201) {
        return await compute(_parseNotificationsIsolate, response.body);
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getNotifications]: $e');
      return [];
    }
  }

  static Future<bool> markNotificationAsRead(String notificationId) async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/member/notification/$notificationId');
      if (kDebugMode) debugPrint('🌐 [API GET] MarkNotificationRead -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      return response.statusCode == 200;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR markNotificationAsRead]: $e');
      return false;
    }
  }

  static Future<bool> markAllNotificationsAsRead() async {
    try {
      final headers = await _getHeaders();
      final Uri url = Uri.parse('$baseUrl/member/mark-all-as-read');
      if (kDebugMode) {
        debugPrint('🌐 [API GET] MarkAllNotificationsRead -> $url');
      }

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      return response.statusCode == 200;
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [API ERROR markAllNotificationsAsRead]: $e');
      }
      return false;
    }
  }

  // ============================================================================
  // 🟢 8. قسم زوار الملف الشخصي (Profile Viewers)
  // ============================================================================

  /// جلب قائمة الأعضاء الذين زاروا البروفايل
  static Future<Map<String, dynamic>> getProfileViewers({int page = 1}) async {
    try {
      final res = await get('/member/my-profile-viewers?page=$page');
      if (res != null && res['result'] == true && res['data'] is List) {
        return res;
      }
      return {
        'result': false,
        'status': res?['status'] ?? 'invalid_response',
        'message': res?['message'] ?? 'تعذر تحميل زوار الملف الشخصي',
        'data': <dynamic>[],
      };
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getProfileViewers]: $e');
      return {
        'result': false,
        'status': 'network_error',
        'message': 'تعذر الاتصال بالسيرفر أثناء تحميل الزوار',
        'data': <dynamic>[],
      };
    }
  }

  /// تسجيل زيارة ملف شخصي في السيرفر
  static Future<void> recordProfileView(int userId) async {
    try {
      await post('/member/record-profile-view', {'user_id': userId});
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR recordProfileView]: $e');
    }
  }

  // 9. البحث والفلترة للأعضاء
  static Future<List<MemberModel>> searchMembers({
    int? ageFrom,
    int? ageTo,
    int? countryId,
    int? cityId,
    int? maritalStatus,
    int? religionId,
    String? profession,
    String? memberCode,
    String? lang,
  }) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('$baseUrl/member/member-listing');

      final Map<String, dynamic> body = {
        if (ageFrom != null) 'age_from': ageFrom,
        if (ageTo != null) 'age_to': ageTo,
        if (countryId != null && countryId > 0) 'country_id': countryId,
        if (cityId != null && cityId > 0) 'city_id': cityId,
        if (maritalStatus != null && maritalStatus > 0)
          'marital_status': maritalStatus,
        if (religionId != null && religionId > 0) 'religion_id': religionId,
        if (profession != null && profession.trim().isNotEmpty)
          'profession': profession.trim(),
        if (memberCode != null && memberCode.trim().isNotEmpty)
          'member_code': memberCode.trim(),
      };

      if (kDebugMode) {
        debugPrint('🌐 [API POST] SearchMembers -> $url | Body: $body');
      }

      final response = await http
          .post(
            url,
            headers: headers,
            body: jsonEncode(body),
          )
          .timeout(_timeoutDuration);

      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        return await compute(_parseSearchMembersIsolate, response.body);
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR searchMembers]: $e');
      return [];
    }
  }

  // 10. قسم تذاكر الدعم الفني (Support Tickets API)

  static Future<List<SupportCategoryModel>> getSupportCategories(
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url =
          Uri.parse('$baseUrl/member/support-ticket/categories?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] SupportCategories -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        dynamic decoded;
        try {
          decoded = jsonDecode(response.body);
        } catch (_) {
          decoded = null;
        }

        if (decoded is Map && decoded['result'] == false) {
          if (kDebugMode) {
            debugPrint(
                '⚠️ [SupportCategories Rejected]: ${decoded['message']}');
          }
          return [];
        }
        return await compute(_parseSupportCategoriesIsolate, response.body);
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getSupportCategories]: $e');
      return [];
    }
  }

  static Future<List<SupportTicketModel>> getMySupportTickets(
      {String? lang}) async {
    try {
      final token = await getToken();

      if (token == null || token.trim().isEmpty) {
        if (kDebugMode) {
          debugPrint(
              '⚠️ [MySupportTickets Error]: Token is empty in SecureStorage');
        }
        return [];
      }

      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url = Uri.parse('$baseUrl/member/my-tickets?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] MySupportTickets -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        dynamic decoded;
        try {
          decoded = jsonDecode(response.body);
        } catch (_) {
          decoded = null;
        }

        if (decoded is Map && decoded['result'] == false) {
          if (kDebugMode) {
            debugPrint('⚠️ [MySupportTickets Rejected]: ${decoded['message']}');
          }
          return [];
        }
        return await compute(_parseSupportTicketsIsolate, response.body);
      }
      return [];
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getMySupportTickets]: $e');
      return [];
    }
  }

  static Future<Map<String, dynamic>> createSupportTicket({
    required String subject,
    required int categoryId,
    required String description,
    File? attachment,
    String? lang,
  }) async {
    try {
      final token = await getToken();

      if (token == null || token.trim().isEmpty) {
        return {
          'success': false,
          'message': 'انتهت جلسة تسجيل الدخول، يرجى إعادة تسجيل الدخول مجدداً.',
        };
      }

      final Uri url = Uri.parse('$baseUrl/member/support-ticket/store');
      final String currentLang = lang ??
          (await SharedPreferences.getInstance()).getString('app_language') ??
          'ar';

      var request = http.MultipartRequest('POST', url);
      final String formattedToken =
          token.startsWith('Bearer ') ? token.trim() : 'Bearer ${token.trim()}';
      request.headers['Authorization'] = formattedToken;
      request.headers['Accept'] = 'application/json';
      request.headers['X-Requested-With'] = 'XMLHttpRequest';
      request.headers['Accept-Language'] = currentLang;
      request.headers['app_language'] = currentLang;

      request.fields['subject'] = subject;
      request.fields['support_category_id'] = categoryId.toString();
      request.fields['description'] = description;

      if (attachment != null && await attachment.exists()) {
        request.files.add(
          await http.MultipartFile.fromPath('attachment', attachment.path),
        );
      }

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST Multipart] CreateSupportTicket -> $url | Headers: ${request.headers} | Fields: ${request.fields}');
      }
      var streamedResponse = await request.send().timeout(_timeoutDuration);
      var response = await http.Response.fromStream(streamedResponse);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      final bool isSuccess =
          (response.statusCode == 200 || response.statusCode == 201) &&
              data is Map &&
              data['result'] != false &&
              data['result'] != 'false';

      if (isSuccess) {
        return {
          'success': true,
          'message': data['message'] ?? 'تم إنشاء تذكرة الدعم بنجاح',
          'data': data,
        };
      } else {
        String errorMsg = 'تعذر التوثيق مع السيرفر.';
        if (data is Map && data['message'] != null) {
          errorMsg = data['message'].toString();
        }
        return {
          'success': false,
          'message': errorMsg,
        };
      }
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR createSupportTicket]: $e');
      return {
        'success': false,
        'message': 'تعذر الاتصال بالسيرفر، تأكد من اتصالك بالإنترنت',
      };
    }
  }

  static Future<SupportTicketModel?> getSupportTicketDetails(int ticketId,
      {String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final int ts = DateTime.now().millisecondsSinceEpoch;
      final Uri url =
          Uri.parse('$baseUrl/member/support-ticket/$ticketId?_t=$ts');
      if (kDebugMode) debugPrint('🌐 [API GET] SupportTicketDetails -> $url');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        dynamic decoded;
        try {
          decoded = jsonDecode(response.body);
        } catch (_) {
          decoded = null;
        }

        if (decoded is Map && decoded['result'] == false) {
          return null;
        }
        return await compute(_parseSupportTicketDetailsIsolate, response.body);
      }
      return null;
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR getSupportTicketDetails]: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>> sendTicketReply({
    required int ticketId,
    required String replyText,
    File? attachment,
    String? lang,
  }) async {
    try {
      final token = await getToken();
      if (token == null || token.trim().isEmpty) {
        return {
          'success': false,
          'message': 'انتهت جلسة تسجيل الدخول، يرجى إعادة تسجيل الدخول مجدداً.',
        };
      }

      final Uri url = Uri.parse('$baseUrl/member/ticket-reply');
      final String currentLang = lang ??
          (await SharedPreferences.getInstance()).getString('app_language') ??
          'ar';

      var request = http.MultipartRequest('POST', url);
      final String formattedToken =
          token.startsWith('Bearer ') ? token.trim() : 'Bearer ${token.trim()}';
      request.headers['Authorization'] = formattedToken;
      request.headers['Accept'] = 'application/json';
      request.headers['X-Requested-With'] = 'XMLHttpRequest';
      request.headers['Accept-Language'] = currentLang;
      request.headers['app_language'] = currentLang;

      request.fields['support_ticket_id'] = ticketId.toString();
      request.fields['reply'] = replyText;

      if (attachment != null && await attachment.exists()) {
        request.files.add(
          await http.MultipartFile.fromPath('attachment', attachment.path),
        );
      }

      if (kDebugMode) {
        debugPrint(
            '🌐 [API POST Multipart] SendTicketReply -> $url | Fields: ${request.fields}');
      }
      var streamedResponse = await request.send().timeout(_timeoutDuration);
      var response = await http.Response.fromStream(streamedResponse);
      if (kDebugMode) {
        debugPrint(
            '📥 [API RESPONSE ${response.statusCode}]: ${response.body}');
      }

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      final bool isSuccess =
          (response.statusCode == 200 || response.statusCode == 201) &&
              data is Map &&
              data['result'] != false &&
              data['result'] != 'false';

      if (isSuccess) {
        return {
          'success': true,
          'message': data['message'] ?? 'تم إرسال الرد بنجاح',
          'data': data,
        };
      } else {
        return {
          'success': false,
          'message': _parseErrorMessage(data, response.statusCode),
        };
      }
    } catch (e) {
      if (kDebugMode) debugPrint('❌ [API ERROR sendTicketReply]: $e');
      return {
        'success': false,
        'message': 'تعذر الاتصال بالسيرفر، تأكد من اتصالك بالإنترنت',
      };
    }
  }
}

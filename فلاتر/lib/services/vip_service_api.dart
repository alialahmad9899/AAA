// ============================================================================
// اسم الملف: lib/services/vip_service_api.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: كلاس إدارة طلبات الخدمات VIP - التركيز على المزايا المتاحة فقط
// ============================================================================

import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

class VipServiceApi {
  static const Duration _timeoutDuration = Duration(seconds: 20);

  static bool _isSuccessfulResponse(int statusCode, dynamic decoded) {
    if (statusCode < 200 || statusCode >= 300 || decoded is! Map) {
      return false;
    }
    return ApiService.isSuccessfulResult(Map<String, dynamic>.from(decoded));
  }

  static Map<String, dynamic> _payloadMap(Map<String, dynamic> response) {
    final nested = response['data'];
    if (nested is Map) return Map<String, dynamic>.from(nested);
    return response;
  }

  static Future<Map<String, String>> _getHeaders({String? lang}) async {
    final token = await ApiService.getToken();
    final prefs = await SharedPreferences.getInstance();
    final String currentLang = lang ?? prefs.getString('app_language') ?? 'ar';

    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Accept-Language': currentLang,
      'app_language': currentLang,
      'X-Requested-With': 'XMLHttpRequest',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
  }

  /// تحليل استجابة كتالوج VIP دون إدخال بيانات محلية بديلة.
  /// القائمة الفارغة استجابة صحيحة إذا قرر الأدمن إيقاف جميع الخدمات.
  @visibleForTesting
  static Map<String, dynamic> parseServicesListResponse({
    required int statusCode,
    required String body,
  }) {
    Map<String, dynamic> failure(String message) => {
          'success': false,
          'data': <dynamic>[],
          'message': message,
          'status_code': statusCode,
        };

    dynamic decoded;
    try {
      decoded = jsonDecode(body);
    } catch (_) {
      return failure('استجابة خادم خدمات VIP غير مفهومة (HTTP $statusCode).');
    }

    if (statusCode < 200 || statusCode >= 300) {
      final serverMessage = decoded is Map
          ? (decoded['message'] ?? decoded['error'] ?? '').toString().trim()
          : '';
      if (serverMessage.isNotEmpty) return failure(serverMessage);
      if (statusCode == 401 || statusCode == 403) {
        return failure('تعذر التحقق من صلاحية الوصول إلى خدمات VIP (HTTP $statusCode).');
      }
      return failure('تعذر تحميل خدمات VIP من الخادم (HTTP $statusCode).');
    }

    List<dynamic>? extractedList;
    if (decoded is List) {
      extractedList = decoded;
    } else if (decoded is Map) {
      final envelope = Map<String, dynamic>.from(decoded);
      if (!_isSuccessfulResponse(statusCode, envelope)) {
        final serverMessage =
            (envelope['message'] ?? envelope['error'] ?? '').toString().trim();
        return failure(serverMessage.isNotEmpty
            ? serverMessage
            : 'رفض الخادم تحميل قائمة خدمات VIP.');
      }

      if (envelope['data'] is List) {
        extractedList = envelope['data'] as List<dynamic>;
      } else if (envelope['result'] is List) {
        extractedList = envelope['result'] as List<dynamic>;
      }
    }

    if (extractedList == null) {
      return failure('لم تصل قائمة خدمات VIP بصيغة صحيحة من الخادم.');
    }

    final services = <Map<String, dynamic>>[];
    for (final item in extractedList) {
      if (item is! Map) {
        return failure('تحتوي استجابة خدمات VIP على عنصر غير صالح.');
      }
      services.add(Map<String, dynamic>.from(item));
    }

    return {'success': true, 'data': services};
  }

  /// جلب قائمة الخدمات مباشرة من الخادم؛ لا نعرض قائمة ثابتة عند فشل الاتصال.
  static Future<Map<String, dynamic>> getServicesList({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      headers['Cache-Control'] = 'no-cache, no-store, max-age=0';
      headers['Pragma'] = 'no-cache';

      final baseUrl = Uri.parse('${ApiService.baseUrl}/vip-services/list');
      final url = baseUrl.replace(queryParameters: {
        ...baseUrl.queryParameters,
        '_ts': DateTime.now().microsecondsSinceEpoch.toString(),
      });

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);
      final result = parseServicesListResponse(
        statusCode: response.statusCode,
        body: response.body,
      );

      if (result['success'] != true && kDebugMode) {
        debugPrint('❌ [VIP catalogue] ${result['message']}');
      }
      return result;
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [API ERROR getServicesList]: $e');
      }
      return {
        'success': false,
        'data': <dynamic>[],
        'message': e is TimeoutException
            ? 'انتهت مهلة الاتصال بخدمات VIP. حاول مجدداً.'
            : 'تعذر الاتصال بخادم خدمات VIP. تحقق من الإنترنت ثم أعد المحاولة.',
      };
    }
  }

  /// 🟢 2. جلب طلبات الخدمات الإضافية للمستخدم
  static Future<Map<String, dynamic>> getUserOrders({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('${ApiService.baseUrl}/vip-services/my-orders');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);

      if (response.statusCode == 200 || response.statusCode == 201) {
        final dynamic decoded = jsonDecode(response.body);
        final validListResponse = decoded is List ||
            _isSuccessfulResponse(response.statusCode, decoded);
        if (!validListResponse) {
          return {'success': false, 'data': []};
        }
        List<dynamic> ordersList = [];
        if (decoded is Map<String, dynamic>) {
          if (decoded['data'] is List) {
            ordersList = decoded['data'];
          } else if (decoded['result'] is List) {
            ordersList = decoded['result'];
          }
        } else if (decoded is List) {
          ordersList = decoded;
        }
        return {'success': true, 'data': ordersList};
      }
      return {'success': false, 'data': []};
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [API ERROR getUserOrders]: $e');
      }
      return {'success': false, 'data': []};
    }
  }

  /// 🟢 3. إنشاء PaymentIntent عبر Stripe
  static Future<Map<String, dynamic>> createStripePaymentIntent({
    required String serviceType,
    required double amount,
    String? lang,
  }) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url =
          Uri.parse('${ApiService.baseUrl}/vip-services/stripe-intent');

      final body = {
        'service_type': serviceType,
        'amount': amount,
      };

      final response = await http
          .post(url, headers: headers, body: jsonEncode(body))
          .timeout(_timeoutDuration);

      final dynamic decoded = jsonDecode(response.body);

      if (_isSuccessfulResponse(response.statusCode, decoded) &&
          decoded is Map) {
        final payload = _payloadMap(Map<String, dynamic>.from(decoded));
        return {
          'success': true,
          'client_secret': payload['client_secret'] ?? decoded['client_secret'],
          'publishable_key': payload['publishable_key'] ?? decoded['publishable_key'],
          'order_id': payload['order_id'] ?? decoded['order_id'],
        };
      }

      final String errorMsg = (decoded is Map<String, dynamic>)
          ? (decoded['message']?.toString() ?? 'فشلت تهيئة Stripe')
          : 'فشلت تهيئة Stripe';

      return {'success': false, 'message': errorMsg};
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [API ERROR createStripePaymentIntent]: $e');
      }
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  /// 🟢 4. تأكيد نجاح عملية الدفع عبر Stripe
  static Future<Map<String, dynamic>> confirmStripeSuccess({
    required int orderId,
    String? lang,
  }) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url =
          Uri.parse('${ApiService.baseUrl}/vip-services/stripe-confirm');

      final response = await http
          .post(url, headers: headers, body: jsonEncode({'order_id': orderId}))
          .timeout(_timeoutDuration);

      final dynamic decoded = jsonDecode(response.body);

      if (_isSuccessfulResponse(response.statusCode, decoded) &&
          decoded is Map) {
        final payload = _payloadMap(Map<String, dynamic>.from(decoded));
        return {
          'success': true,
          'message': payload['message'] ?? decoded['message'] ?? 'تم الدفع بنجاح!',
        };
      }

      final String errorMsg = (decoded is Map<String, dynamic>)
          ? (decoded['message']?.toString() ?? 'فشلت عملية تأكيد الدفع')
          : 'فشلت عملية تأكيد الدفع';

      return {'success': false, 'message': errorMsg};
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [API ERROR confirmStripeSuccess]: $e');
      }
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }

  /// 🟢 5. إرسال إشعار تحويل شام كاش اليدوي
  static Future<Map<String, dynamic>> submitShamCashPayment({
    required String serviceType,
    required double amount,
    required String accountName,
    File? proofFile,
    String? lang,
  }) async {
    try {
      final token = await ApiService.getToken();
      final prefs = await SharedPreferences.getInstance();
      final String currentLang =
          lang ?? prefs.getString('app_language') ?? 'ar';

      final Uri url =
          Uri.parse('${ApiService.baseUrl}/vip-services/sham-cash-submit');

      var request = http.MultipartRequest('POST', url);
      request.headers.addAll({
        'Accept': 'application/json',
        'Accept-Language': currentLang,
        'app_language': currentLang,
        'X-Requested-With': 'XMLHttpRequest',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      });

      request.fields['service_type'] = serviceType;
      request.fields['amount'] = amount.toString();
      request.fields['account_name'] = accountName;

      if (proofFile != null) {
        request.files.add(
          await http.MultipartFile.fromPath('payment_proof', proofFile.path),
        );
      }

      final streamedResponse = await request.send().timeout(_timeoutDuration);
      final response = await http.Response.fromStream(streamedResponse);
      final dynamic decoded = jsonDecode(response.body);

      if (_isSuccessfulResponse(response.statusCode, decoded) &&
          decoded is Map) {
        final payload = _payloadMap(Map<String, dynamic>.from(decoded));
        return {
          'success': true,
          'message': payload['message'] ?? decoded['message'] ?? 'تم استلام الطلب بنجاح',
          'order_id': payload['order_id'] ?? decoded['order_id'],
        };
      }

      final String errorMsg = (decoded is Map<String, dynamic>)
          ? (decoded['message']?.toString() ?? 'فشل إرسال الطلب')
          : 'فشل إرسال الطلب';

      return {'success': false, 'message': errorMsg};
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [API ERROR submitShamCashPayment]: $e');
      }
      return {'success': false, 'message': 'تعذر إرسال الإشعار بالسيرفر'};
    }
  }

  /// 🟢 6. تقديم مواصفات الخدمة
  static Future<Map<String, dynamic>> submitSpecifications({
    required int orderId,
    required Map<String, dynamic> specifications,
    String? lang,
  }) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse(
          '${ApiService.baseUrl}/vip-services/submit-specifications/$orderId');

      final response = await http
          .post(url,
              headers: headers,
              body: jsonEncode({'specifications': specifications}))
          .timeout(_timeoutDuration);

      final dynamic decoded = jsonDecode(response.body);

      if (_isSuccessfulResponse(response.statusCode, decoded) &&
          decoded is Map) {
        final payload = _payloadMap(Map<String, dynamic>.from(decoded));
        return {
          'success': true,
          'message': payload['message'] ?? decoded['message'] ?? 'تم حفظ المواصفات بنجاح',
        };
      }

      final String errorMsg = (decoded is Map<String, dynamic>)
          ? (decoded['message']?.toString() ?? 'فشل حفظ المواصفات')
          : 'فشل حفظ المواصفات';

      return {'success': false, 'message': errorMsg};
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [API ERROR submitSpecifications]: $e');
      }
      return {'success': false, 'message': 'تعذر الاتصال بالسيرفر'};
    }
  }
}

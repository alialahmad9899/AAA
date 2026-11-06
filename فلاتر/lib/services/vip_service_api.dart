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

  /// 🟢 القائمة المعتمدة للخدمات الـ 3 بالمزايا المتاحة فقط
  static final List<Map<String, dynamic>> _defaultVipServices = [
    {
      'id': 1,
      'service_type': 'highlight_profile',
      'title_ar': 'خدمة نشر طلب زواج مميّز – منصة وصال',
      'title_en': 'Featured Marriage Proposal Service – Wesal',
      'price': 10.00,
      'currency': '\$',
      'promo_ar':
          '✨ فرصة أكبر لوصول طلبك إلى الأشخاص المناسبين، بطريقة مميزة وآمنة مع منصة وصال.',
      'promo_en':
          '✨ Higher reach for your proposal to suitable people in a safe and distinguished way.',
      'included_ar': [
        'منشور تفصيلي بمواصفاتك والشريك المطلوب على فيسبوك وتيك توك',
        'إنتاج فيديو خاص ومميز يعرض طلبك بطريقة جذابة',
        'نشر رابط بروفايلك للوصول المباشر والتواصل الآمن داخل التطبيق',
        'الحفاظ على الخصوصية الكاملة وتأمين بياناتك الشخصية'
      ],
      'included_en': [
        'Detailed post on Wesal Facebook and TikTok pages',
        'Custom video creation showcasing your proposal',
        'Direct profile link for safe in-app messaging',
        'Full privacy & profile protection'
      ],
    },
    {
      'id': 2,
      'service_type': 'special_matchmaking',
      'title_ar': 'خدمة البحث الخاص عن شريك الحياة – منصة وصال',
      'title_en': 'Custom Partner Search Service – Wesal',
      'price': 100.00,
      'currency': '\$',
      'promo_ar':
          '✨ أنت تحدد مواصفاتك، ونحن نتولى مهمة البحث! فريق وصال يتولى البحث عن الشريك المناسب لك.',
      'promo_en':
          '✨ You specify your expectations, and our team handles the personalized search for you!',
      'included_ar': [
        'بحث مخصص ويدوي نيابة عنك بواسطة فريق منصة وصال',
        'البحث الشامل في قاعدة بيانات المشتركين والمهتمين بالزواج',
        'ربط مباشر وآمن وتنسيق التواصل بين الطرفين داخل التطبيق',
        'خصوصية تامة وشديدة طوال فترة البحث والدراسة'
      ],
      'included_en': [
        'Manual dedicated search by Wesal matchmaking team',
        'Deep searching through entire registered user database',
        'Direct safe connection and coordination inside the app',
        '100% strict privacy protection throughout search'
      ],
    },
    {
      'id': 3,
      'service_type': 'direct_contact',
      'title_ar': 'خدمة التواصل المباشر خارج المنصة – منصة وصال',
      'title_en': 'Direct External Contact Service – Wesal',
      'price': 25.00,
      'currency': '\$',
      'promo_ar':
          '✨ وسّع فرصك في العثور على شريك الحياة المناسب، واجعل طلبك يصل للمزيد عبر الواتساب! ❤️',
      'promo_en':
          '✨ Expand your chances of finding the right partner through direct external contact! ❤️',
      'included_ar': [
        'منشور تفصيلي وفيديو مخصص على صفحات وصال (فيسبوك وتيك توك)',
        'نشر رابط بروفايلك الشخصي على المنصة',
        'إتاحة التواصل المباشر خارج التطبيق عبر الواتساب للمهتمين',
        'التحكم الكامل بأسلوب التواصل واختيار ما يناسبك'
      ],
      'included_en': [
        'Detailed post and video on Wesal social channels',
        'Attaching your personal profile link',
        'Allowing direct external WhatsApp contact for interested users',
        'Full control over your preferred contact method'
      ],
    },
  ];

  /// 🟢 دالة مساعدة لإنشاء نسخة جديدة من الخدمات الافتراضية لمنع التعديل المباشر
  static List<Map<String, dynamic>> _getCopyOfDefaultServices() {
    return _defaultVipServices.map((service) {
      return Map<String, dynamic>.from(service);
    }).toList();
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

  /// 🟢 1. جلب قائمة الخدمات الإضافية VIP
  static Future<Map<String, dynamic>> getServicesList({String? lang}) async {
    try {
      final headers = await _getHeaders(lang: lang);
      final Uri url = Uri.parse('${ApiService.baseUrl}/vip-services/list');

      final response =
          await http.get(url, headers: headers).timeout(_timeoutDuration);

      if (response.statusCode == 200 || response.statusCode == 201) {
        final dynamic decoded = jsonDecode(response.body);
        final validListResponse = decoded is List ||
            _isSuccessfulResponse(response.statusCode, decoded);
        if (!validListResponse) {
          return {
            'success': false,
            'data': _getCopyOfDefaultServices(),
            'is_fallback': true,
            'message': decoded is Map ? decoded['message']?.toString() : null,
          };
        }
        List<dynamic> extractedList = [];

        if (decoded is List) {
          extractedList = decoded;
        } else if (decoded is Map<String, dynamic>) {
          if (decoded['data'] is List) {
            extractedList = decoded['data'];
          } else if (decoded['result'] is List) {
            extractedList = decoded['result'];
          }
        }

        if (extractedList.isNotEmpty) {
          final List<Map<String, dynamic>> formattedList = [];

          for (var item in extractedList) {
            if (item is Map) {
              final Map<String, dynamic> service =
                  Map<String, dynamic>.from(item);

              final defaultMatch = _defaultVipServices.firstWhere(
                (d) => d['service_type'] == service['service_type'],
                orElse: () => {},
              );

              if (defaultMatch.isNotEmpty) {
                service['promo_ar'] ??= defaultMatch['promo_ar'];
                service['promo_en'] ??= defaultMatch['promo_en'];
                service['included_ar'] ??= defaultMatch['included_ar'];
                service['included_en'] ??= defaultMatch['included_en'];
                service['title_ar'] ??= defaultMatch['title_ar'];
                service['title_en'] ??= defaultMatch['title_en'];
              }
              formattedList.add(service);
            }
          }

          if (formattedList.isNotEmpty) {
            return {'success': true, 'data': formattedList};
          }
        }
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [API ERROR getServicesList]: $e');
      }
    }

    return {'success': false, 'is_fallback': true, 'data': _getCopyOfDefaultServices(), 'message': 'تعذر تحديث قائمة الخدمات من السيرفر'};
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

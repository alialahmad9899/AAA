// ============================================================================
// اسم الملف: lib/screens/vip_services_screen.dart
// مشروع: تطبيق وصال (Wesal Platform) - Modern Premium UI 2026
// الوصف: شاشة الخدمات الإضافية VIP بتسلسل تعبئة البيانات قبل الدفع،
//        وببروتوكول استلام الطلبات ونوافذ الشكر والارتباط بصفحة الفيسبوك.
// ============================================================================

import 'dart:io';
import 'package:flutter/material.dart';
import 'package:wesal_app/widgets/bottom_system_inset.dart';
import 'package:flutter/services.dart';
import 'package:flutter_stripe/flutter_stripe.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/vip_service_api.dart';
import '../widgets/app_snackbar.dart';

class VipServicesScreen extends StatefulWidget {
  final String currentLanguage;

  const VipServicesScreen({
    super.key,
    required this.currentLanguage,
  });

  @override
  State<VipServicesScreen> createState() => _VipServicesScreenState();
}

class _VipServicesScreenState extends State<VipServicesScreen> {
  static const String _officialShamCashCode =
      '0f50e77304209ae23d85b7971f1089b7';
  static const String _facebookPageUrl =
      'https://www.facebook.com/share/1PpDNVab2p/';

  bool _isLoading = true;
  List<dynamic> _services = [];
  List<dynamic> _myOrders = [];

  bool get _isAr => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _fetchVipData();
  }

  /// 🟢 جلب بيانات الخدمات والطلبات مع دعم تجربة السحب للتحديث بدون انقطاع
  Future<void> _fetchVipData({bool isRefresh = false}) async {
    if (!mounted) return;
    if (!isRefresh && _services.isEmpty) {
      setState(() => _isLoading = true);
    }

    final servicesRes =
        await VipServiceApi.getServicesList(lang: widget.currentLanguage);
    final ordersRes =
        await VipServiceApi.getUserOrders(lang: widget.currentLanguage);

    if (mounted) {
      setState(() {
        _services = servicesRes['data'] ?? [];
        _myOrders = ordersRes['data'] ?? [];
        _isLoading = false;
      });
    }
  }

  /// 🟢 فتح رابط صفحة الفيسبوك مع إمكانية نسخ الرابط كخطة احتياطية
  static Future<void> _openFacebookPage(
      BuildContext context, bool isArabic) async {
    final Uri uri = Uri.parse(_facebookPageUrl);
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        await Clipboard.setData(const ClipboardData(text: _facebookPageUrl));
        if (context.mounted) {
          AppSnackBar.show(
            context: context,
            type: NotificationType.success,
            isArabic: isArabic,
            messageAr: 'تم نسخ رابط صفحة الفيسبوك بنجاح',
            messageEn: 'Facebook page link copied to clipboard',
          );
        }
      }
    } catch (_) {
      await Clipboard.setData(const ClipboardData(text: _facebookPageUrl));
      if (context.mounted) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.success,
          isArabic: isArabic,
          messageAr: 'تم نسخ رابط صفحة الفيسبوك بنجاح',
          messageEn: 'Facebook page link copied to clipboard',
        );
      }
    }
  }

  String _getServiceTitle(dynamic item) {
    if (_isAr) {
      return item['title_ar'] ?? item['title'] ?? item['name'] ?? '';
    } else {
      return item['title_en'] ?? item['title'] ?? item['name'] ?? '';
    }
  }

  String _getServicePromo(dynamic item) {
    if (_isAr) {
      return item['promo_ar'] ?? item['description'] ?? '';
    } else {
      return item['promo_en'] ?? item['description'] ?? '';
    }
  }

  List<dynamic> _getIncludedList(dynamic item) {
    if (_isAr) {
      return item['included_ar'] is List
          ? item['included_ar']
          : (item['features_ar'] is List ? item['features_ar'] : []);
    } else {
      return item['included_en'] is List
          ? item['included_en']
          : (item['features_en'] is List ? item['features_en'] : []);
    }
  }

  IconData _getIconForFeatureText(String text) {
    final t = text.toLowerCase();
    if (t.contains('فيديو') || t.contains('video')) {
      return Icons.movie_creation_rounded;
    }
    if (t.contains('فيسبوك') ||
        t.contains('تيك توك') ||
        t.contains('منشور') ||
        t.contains('post') ||
        t.contains('social')) {
      return Icons.campaign_rounded;
    }
    if (t.contains('واتساب') ||
        t.contains('تواصل') ||
        t.contains('whatsapp') ||
        t.contains('contact')) {
      return Icons.chat_rounded;
    }
    if (t.contains('رابط') ||
        t.contains('بروفايل') ||
        t.contains('link') ||
        t.contains('profile')) {
      return Icons.link_rounded;
    }
    if (t.contains('خصوصية') ||
        t.contains('تأمين') ||
        t.contains('حماية') ||
        t.contains('privacy') ||
        t.contains('protection')) {
      return Icons.security_rounded;
    }
    if (t.contains('بحث') || t.contains('search') || t.contains('يدوي')) {
      return Icons.person_search_rounded;
    }
    if (t.contains('قاعدة بيانات') || t.contains('database')) {
      return Icons.storage_rounded;
    }
    if (t.contains('ربط') || t.contains('تنسيق') || t.contains('match')) {
      return Icons.handshake_rounded;
    }
    if (t.contains('تحكم') || t.contains('control')) {
      return Icons.tune_rounded;
    }
    return Icons.verified_rounded;
  }

  /// 🟢 الخطوة الأولى: البدء بملء الاستبيان والمواصفات أولاً قبل الدفع
  void _startOrderWorkflow(dynamic service) {
    final String serviceType = service['service_type'] ?? 'highlight_profile';
    final double price =
        double.tryParse(service['price']?.toString() ?? '0') ?? 0.0;
    final String title = _getServiceTitle(service);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _VipSpecificationsModal(
        orderId: 0,
        serviceType: serviceType,
        serviceTitle: title,
        price: price,
        isArabic: _isAr,
        currentLanguage: widget.currentLanguage,
        onProceedToPayment: (Map<String, dynamic> collectedSpecs) {
          Navigator.pop(ctx);
          if (mounted) {
            _openPaymentSelectionModal(service, collectedSpecs);
          }
        },
      ),
    );
  }

  /// 🟢 الخطوة الثانية: فتح نافذة الدفع مع تمرير البيانات التي تم جمعها
  void _openPaymentSelectionModal(
      dynamic service, Map<String, dynamic> collectedSpecs) {
    final String serviceType = service['service_type'] ?? 'highlight_profile';
    final double price =
        double.tryParse(service['price']?.toString() ?? '0') ?? 0.0;
    final String title = _getServiceTitle(service);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _VipPaymentModalSheet(
        isArabic: _isAr,
        serviceTitle: title,
        serviceType: serviceType,
        price: price,
        collectedSpecs: collectedSpecs,
        currentLanguage: widget.currentLanguage,
        onPaymentSuccess: (int orderId, Map<String, dynamic> specs) {
          Navigator.pop(ctx);
          if (mounted) {
            _fetchVipData(isRefresh: true);
            _showOrderReceiptAndThankYouDialog(orderId, title, price, specs);
          }
        },
      ),
    );
  }

  /// 🟢 الخطوة الثالثة: نافذة بروتوكول استلام الطلب + الشكر والتقدير وزر الفيسبوك
  void _showOrderReceiptAndThankYouDialog(int orderId, String serviceTitle,
      double price, Map<String, dynamic> specs) {
    final bool isHundredDollarService =
        price >= 90.0 || serviceTitle.contains('100');
    final String contactMethod = specs['contact_method'] ?? 'whatsapp';
    String contactDisplay = '';

    if (contactMethod == 'whatsapp') {
      final phone = specs['full_phone_number'] ?? specs['phone_number'] ?? '';
      contactDisplay = _isAr ? 'واتساب ($phone)' : 'WhatsApp ($phone)';
    } else {
      final link = specs['profile_link'] ?? '';
      contactDisplay = _isAr ? 'حساب/رابط ($link)' : 'Profile ($link)';
    }

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogCtx) => Directionality(
        textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          backgroundColor: Colors.transparent,
          insetPadding: const EdgeInsets.symmetric(horizontal: 20),
          child: Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              color: const Color(0xFF0D1322),
              borderRadius: BorderRadius.circular(28),
              border: Border.all(color: Colors.amber, width: 1.5),
              boxShadow: [
                BoxShadow(
                  color: Colors.amber.withValues(alpha: 0.2),
                  blurRadius: 25,
                  spreadRadius: 4,
                )
              ],
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.amber.withValues(alpha: 0.15),
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.amber, width: 1.5),
                    ),
                    child: const Icon(Icons.verified_rounded,
                        color: Colors.amber, size: 48),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _isAr
                        ? 'بروتوكول استلام الطلب 📜'
                        : 'Order Receipt Protocol 📜',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    _isAr
                        ? 'تم تسجيل طلبك ورقم الإشعار (VIP-$orderId) بنجاح في نظام منصة وصال.'
                        : 'Your order (VIP-$orderId) has been successfully registered.',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 13,
                      fontFamily: 'Cairo',
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.white12),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              _isAr ? 'الخدمة:' : 'Service:',
                              style: const TextStyle(
                                  color: Colors.white60,
                                  fontSize: 12,
                                  fontFamily: 'Cairo'),
                            ),
                            Expanded(
                              child: Text(
                                serviceTitle,
                                textAlign: TextAlign.end,
                                style: const TextStyle(
                                    color: Colors.amber,
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo'),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              _isAr
                                  ? 'طريقة التواصل المختارة:'
                                  : 'Chosen Contact:',
                              style: const TextStyle(
                                  color: Colors.white60,
                                  fontSize: 12,
                                  fontFamily: 'Cairo'),
                            ),
                            Text(
                              contactDisplay,
                              style: const TextStyle(
                                  color: Color(0xFF38BDF8),
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (isHundredDollarService) ...[
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            Colors.purple.withValues(alpha: 0.25),
                            Colors.indigo.withValues(alpha: 0.25),
                          ],
                        ),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.purpleAccent),
                      ),
                      child: Column(
                        children: [
                          const Icon(Icons.workspace_premium_rounded,
                              color: Colors.amber, size: 30),
                          const SizedBox(height: 8),
                          Text(
                            _isAr
                                ? 'رسالة شكر وتقدير 👑'
                                : 'Appreciation & Thanks 👑',
                            style: const TextStyle(
                              color: Colors.amber,
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            _isAr
                                ? 'نشكر ثقتكم العالية بـ منصة وصال. تم استلام بياناتكم الكاملة بنجاح، وسيقوم فريق عمل وصال المختص بالتواصل معكم مباشرة عبر [$contactDisplay] في أقرب وقت لتنسيق الخدمة المخصصة لكم.'
                                : 'Thank you for trusting Wesal Platform. Your details have been received and our team will contact you shortly via [$contactDisplay].',
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12.5,
                              fontFamily: 'Cairo',
                              height: 1.45,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                  // 🟢 رابط الفيسبوك الرسمي
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(
                          color: Color(0xFF1877F2), width: 1.5),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 12),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14)),
                    ),
                    icon: const Icon(Icons.facebook_rounded,
                        color: Color(0xFF1877F2), size: 22),
                    label: Text(
                      _isAr
                          ? 'متابعة صفحتنا الرسمية على فيسبوك 🔗'
                          : 'Follow Our Facebook Page 🔗',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12.5,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    onPressed: () => _openFacebookPage(dialogCtx, _isAr),
                  ),
                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.amber,
                        padding: const EdgeInsets.symmetric(vertical: 13),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14)),
                      ),
                      onPressed: () => Navigator.pop(dialogCtx),
                      child: Text(
                        _isAr ? 'تأكيد وموافق 👍' : 'Confirm & OK 👍',
                        style: const TextStyle(
                          color: Colors.black,
                          fontWeight: FontWeight.w900,
                          fontSize: 14,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showServiceDetailsModal(dynamic item) {
    final String title = _getServiceTitle(item);
    final String promo = _getServicePromo(item);
    final double price =
        double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final List<dynamic> included = _getIncludedList(item);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Directionality(
        textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(ctx).size.height * 0.85,
          ),
          padding: EdgeInsets.fromLTRB(22, 20, 22, 20 + WesalBottomInsets.of(ctx)),
          decoration: BoxDecoration(
            color: const Color(0xFF0D1322),
            borderRadius: const BorderRadius.vertical(top: Radius.circular(36)),
            border: Border.all(
                color: const Color(0xFFF59E0B).withValues(alpha: 0.4),
                width: 1.5),
            boxShadow: [
              BoxShadow(
                color: Colors.amber.withValues(alpha: 0.12),
                blurRadius: 30,
                spreadRadius: 5,
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Center(
                child: Container(
                  width: 48,
                  height: 5,
                  decoration: BoxDecoration(
                    color: Colors.white24,
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                  ),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Colors.white12),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
                        ),
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: Colors.amber.withValues(alpha: 0.3),
                            blurRadius: 12,
                          ),
                        ],
                      ),
                      child: const Icon(Icons.workspace_premium_rounded,
                          color: Colors.black, size: 28),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            title,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                              height: 1.3,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '\$$price USD',
                            style: const TextStyle(
                              color: Color(0xFFF59E0B),
                              fontSize: 17,
                              fontWeight: FontWeight.w900,
                              fontFamily: 'Cairo',
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              Expanded(
                child: SingleChildScrollView(
                  physics: const BouncingScrollPhysics(),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (promo.isNotEmpty)
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                Colors.amber.withValues(alpha: 0.12),
                                Colors.orangeAccent.withValues(alpha: 0.05),
                              ],
                            ),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                                color: Colors.amber.withValues(alpha: 0.3)),
                          ),
                          child: Text(
                            promo,
                            style: const TextStyle(
                              color: Color(0xFFFCD34D),
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                              height: 1.45,
                            ),
                          ),
                        ),
                      const SizedBox(height: 20),
                      Text(
                        _isAr
                            ? '✨ المزايا المتاحة بالخدمة:'
                            : '✨ Included Features:',
                        style: const TextStyle(
                          color: Color(0xFF38BDF8),
                          fontSize: 15,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                      ),
                      const SizedBox(height: 12),
                      ...included.map((itemText) {
                        String text = itemText is Map
                            ? (itemText['title'] ?? '')
                            : itemText.toString();
                        IconData icon = _getIconForFeatureText(text);

                        return Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color:
                                const Color(0xFF1E293B).withValues(alpha: 0.7),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                                color: const Color(0xFF38BDF8)
                                    .withValues(alpha: 0.2)),
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF0284C7)
                                      .withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Icon(icon,
                                    color: const Color(0xFF38BDF8), size: 20),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  text,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 13,
                                    fontFamily: 'Cairo',
                                    height: 1.4,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      }),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16)),
                    backgroundColor: Colors.amber,
                  ),
                  onPressed: () {
                    Navigator.pop(ctx);
                    _startOrderWorkflow(item);
                  },
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        _isAr
                            ? 'طلب الخدمة الآن (\$ $price)'
                            : 'Order Service Now (\$ $price)',
                        style: const TextStyle(
                          color: Colors.black,
                          fontWeight: FontWeight.w900,
                          fontSize: 14.5,
                          fontFamily: 'Cairo',
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Icon(Icons.arrow_forward_rounded,
                          color: Colors.black, size: 20),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: Scaffold(
        backgroundColor: const Color(0xFF090D16),
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          title: Text(
            _isAr ? 'الخدمات الإضافية VIP' : 'VIP Extra Services',
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w800,
              fontSize: 18,
              fontFamily: 'Cairo',
            ),
          ),
          centerTitle: true,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new_rounded,
                color: Colors.white, size: 20),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: SafeArea(
          child: _isLoading
              ? const Center(
                  child: CircularProgressIndicator(color: Colors.amber))
              : RefreshIndicator(
                  color: Colors.amber,
                  backgroundColor: const Color(0xFF0F172A),
                  onRefresh: () => _fetchVipData(isRefresh: true),
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF2E1065), Color(0xFF1E1B4B)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(26),
                            border: Border.all(
                                color: Colors.amber.withValues(alpha: 0.3),
                                width: 1.2),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.purple.withValues(alpha: 0.2),
                                blurRadius: 20,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: Colors.amber.withValues(alpha: 0.15),
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                      color:
                                          Colors.amber.withValues(alpha: 0.4)),
                                ),
                                child: const Icon(
                                    Icons.workspace_premium_rounded,
                                    color: Colors.amber,
                                    size: 38),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      _isAr
                                          ? 'خدمات VIP الحصرية 👑'
                                          : 'Exclusive VIP Services 👑',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 16.5,
                                        fontWeight: FontWeight.bold,
                                        fontFamily: 'Cairo',
                                      ),
                                    ),
                                    const SizedBox(height: 3),
                                    Text(
                                      _isAr
                                          ? 'تمتع بخدمات الترويج والبحث المخصص للوصول للشريك المثالي.'
                                          : 'Custom matching and promotion services.',
                                      style: TextStyle(
                                        color: Colors.white
                                            .withValues(alpha: 0.75),
                                        fontSize: 12,
                                        fontFamily: 'Cairo',
                                        height: 1.35,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),
                        Row(
                          children: [
                            Container(
                              width: 4,
                              height: 18,
                              decoration: BoxDecoration(
                                color: Colors.amber,
                                borderRadius: BorderRadius.circular(4),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              _isAr ? 'الخدمات المتاحة' : 'Available Services',
                              style: const TextStyle(
                                color: Colors.amber,
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        if (_services.isEmpty)
                          Center(
                            child: Padding(
                              padding: const EdgeInsets.all(20.0),
                              child: Text(
                                _isAr
                                    ? 'لا توجد خدمات متاحة حالياً'
                                    : 'No services available',
                                style: const TextStyle(
                                    color: Colors.white54, fontFamily: 'Cairo'),
                              ),
                            ),
                          )
                        else
                          ..._services.map((item) => _buildServiceCard(item)),
                        if (_myOrders.isNotEmpty) ...[
                          const SizedBox(height: 28),
                          Row(
                            children: [
                              Container(
                                width: 4,
                                height: 18,
                                decoration: BoxDecoration(
                                  color: const Color(0xFF38BDF8),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Text(
                                _isAr
                                    ? 'سجل طلباتي السابقة'
                                    : 'My Orders History',
                                style: const TextStyle(
                                  color: Color(0xFF38BDF8),
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),
                          ..._myOrders.map((order) => _buildOrderCard(order)),
                        ],
                      ],
                    ),
                  ),
                ),
        ),
      ),
    );
  }

  Widget _buildServiceCard(dynamic item) {
    final double price =
        double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final String title = _getServiceTitle(item);
    final String promo = _getServicePromo(item);
    final List<dynamic> included = _getIncludedList(item);

    return Container(
      margin: const EdgeInsets.only(bottom: 18),
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF131C2E),
        borderRadius: BorderRadius.circular(26),
        border:
            Border.all(color: Colors.amber.withValues(alpha: 0.3), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 15,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(11),
                decoration: BoxDecoration(
                  color: Colors.amber.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                  border:
                      Border.all(color: Colors.amber.withValues(alpha: 0.3)),
                ),
                child: const Icon(Icons.auto_awesome_rounded,
                    color: Colors.amber, size: 24),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14.5,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                        height: 1.35,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 3),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
                        ),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '\$$price USD',
                        style: const TextStyle(
                          color: Colors.black,
                          fontSize: 13,
                          fontWeight: FontWeight.w900,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          if (promo.isNotEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.amber.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.amber.withValues(alpha: 0.2)),
              ),
              child: Text(
                promo,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Color(0xFFFCD34D),
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  fontFamily: 'Cairo',
                  height: 1.4,
                ),
              ),
            ),
          const SizedBox(height: 14),
          ...included.take(2).map((itemText) {
            String text = itemText is Map
                ? (itemText['title'] ?? '')
                : itemText.toString();
            IconData icon = _getIconForFeatureText(text);

            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  Icon(icon, color: const Color(0xFF38BDF8), size: 18),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      text,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.85),
                        fontSize: 12,
                        fontFamily: 'Cairo',
                      ),
                    ),
                  ),
                ],
              ),
            );
          }),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    side:
                        const BorderSide(color: Color(0xFF38BDF8), width: 1.2),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14)),
                  ),
                  icon: const Icon(Icons.list_alt_rounded,
                      color: Color(0xFF38BDF8), size: 16),
                  label: Text(
                    _isAr ? 'عرض كافة المزايا' : 'View All Features',
                    style: const TextStyle(
                      color: Color(0xFF38BDF8),
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  onPressed: () => _showServiceDetailsModal(item),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.amber,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14)),
                    elevation: 3,
                    shadowColor: Colors.amber.withValues(alpha: 0.3),
                  ),
                  icon: const Icon(Icons.flash_on_rounded,
                      color: Colors.black, size: 16),
                  label: Text(
                    _isAr ? 'طلب الخدمة' : 'Order Now',
                    style: const TextStyle(
                      color: Colors.black,
                      fontWeight: FontWeight.w900,
                      fontSize: 12.5,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  onPressed: () => _startOrderWorkflow(item),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildOrderCard(dynamic order) {
    final String status =
        order['execution_status'] ?? order['status'] ?? 'pending';
    final String code = order['order_code'] ?? 'VIP-${order['id'] ?? '00'}';

    final Map<String, dynamic> specs = order['specifications'] is Map
        ? Map<String, dynamic>.from(order['specifications'])
        : {};
    final String? rejectionReason = specs['rejection_reason'];

    Color statusColor = Colors.orange;
    String statusText = _isAr ? 'قيد المعالجة' : 'Pending';

    if (status == 'completed') {
      statusColor = Colors.green;
      statusText = _isAr ? 'مكتمل' : 'Completed';
    } else if (status == 'in_progress') {
      statusColor = Colors.blue;
      statusText = _isAr ? 'جاري التنفيذ' : 'In Progress';
    } else if (status == 'cancelled') {
      statusColor = Colors.redAccent;
      statusText = _isAr ? 'مرفوض' : 'Rejected';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: const Color(0xFF131C2E),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: statusColor.withValues(alpha: 0.35)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.receipt_long_rounded,
                  color: Color(0xFF38BDF8), size: 22),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      code,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13.5,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    if (order['created_at'] != null)
                      Text(
                        order['created_at'].toString().split('T').first,
                        style: const TextStyle(
                          color: Colors.white54,
                          fontSize: 11,
                          fontFamily: 'Cairo',
                        ),
                      ),
                  ],
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: statusColor, width: 1),
                ),
                child: Text(
                  statusText,
                  style: TextStyle(
                    color: statusColor,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
              ),
            ],
          ),
          if (status == 'cancelled' &&
              rejectionReason != null &&
              rejectionReason.isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.redAccent.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
                border:
                    Border.all(color: Colors.redAccent.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.info_outline_rounded,
                      color: Colors.redAccent, size: 18),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '${_isAr ? "سبب الرفض:" : "Rejection Reason:"} $rejectionReason',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontFamily: 'Cairo',
                        height: 1.3,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ============================================================================
// 🟢 مودال اختيار طريقة الدفع مع إرسال المواصفات فقط عند نجاح الدفع
// ============================================================================

class _VipPaymentModalSheet extends StatefulWidget {
  final bool isArabic;
  final String serviceTitle;
  final String serviceType;
  final double price;
  final Map<String, dynamic> collectedSpecs;
  final String currentLanguage;
  final Function(int orderId, Map<String, dynamic> specs) onPaymentSuccess;

  const _VipPaymentModalSheet({
    required this.isArabic,
    required this.serviceTitle,
    required this.serviceType,
    required this.price,
    required this.collectedSpecs,
    required this.currentLanguage,
    required this.onPaymentSuccess,
  });

  @override
  State<_VipPaymentModalSheet> createState() => _VipPaymentModalSheetState();
}

class _VipPaymentModalSheetState extends State<_VipPaymentModalSheet> {
  // يستخدم مودال الدفع نفس اختيار اللغة الممرر من شاشة الخدمات.
  bool get _isAr => widget.isArabic;

  int _selectedCategory = 2;
  bool _isSubmitting = false;
  String? _cardErrorMessage;

  final TextEditingController _accountNameController = TextEditingController();
  File? _proofFile;

  @override
  void dispose() {
    _accountNameController.dispose();
    super.dispose();
  }

  Future<void> _pickProofImage() async {
    try {
      final ImagePicker picker = ImagePicker();
      final XFile? image =
          await picker.pickImage(source: ImageSource.gallery, imageQuality: 85);
      if (image != null && mounted) {
        setState(() => _proofFile = File(image.path));
      }
    } catch (_) {}
  }

  /// 🟢 الدفع عبر Stripe ثم إرسال المواصفات للسيرفر
  Future<void> _startStripeNativePayment() async {
    if (_isSubmitting) return;

    setState(() {
      _isSubmitting = true;
      _cardErrorMessage = null;
    });

    try {
      final res = await VipServiceApi.createStripePaymentIntent(
        serviceType: widget.serviceType,
        amount: widget.price,
        lang: widget.currentLanguage,
      );

      if (!mounted) return;

      final clientSecret = res['client_secret']?.toString().trim();
      final orderId = int.tryParse(res['order_id']?.toString() ?? '');
      if (res['success'] == true &&
          clientSecret != null &&
          clientSecret.isNotEmpty &&
          orderId != null &&
          orderId > 0) {
        final String publishableKey = res['publishable_key']?.toString() ?? '';

        if (publishableKey.isNotEmpty) {
          Stripe.publishableKey = publishableKey;
        }

        await Stripe.instance.initPaymentSheet(
          paymentSheetParameters: SetupPaymentSheetParameters(
            paymentIntentClientSecret: clientSecret,
            merchantDisplayName: 'تطبيق وصال - Wesal VIP',
            style: ThemeMode.dark,
            appearance: const PaymentSheetAppearance(
              colors: PaymentSheetAppearanceColors(
                primary: Color(0xFF0288D1),
                background: Color(0xFF0F172A),
                componentBackground: Color(0xFF1E293B),
                componentText: Colors.white,
                primaryText: Colors.white,
              ),
            ),
          ),
        );

        if (!mounted) return;
        await Stripe.instance.presentPaymentSheet();

        if (!mounted) return;
        // لا نعرض نجاحاً ولا نسجل الطلب محلياً قبل تأكيد الخادم.
        final confirmation = await VipServiceApi.confirmStripeSuccess(
          orderId: orderId,
          lang: widget.currentLanguage,
        );
        if (!mounted) return;
        if (confirmation['success'] != true) {
          setState(() {
            _isSubmitting = false;
            _cardErrorMessage = confirmation['message']?.toString() ??
                (_isAr
                    ? 'تم الدفع لدى Stripe لكن لم يصل تأكيد الطلب من السيرفر. لا تعاود الدفع قبل مراجعة حالة الطلب.'
                    : 'Stripe payment completed but the server did not confirm the order. Do not pay again before checking its status.');
          });
          return;
        }

        // بعد تأكيد الدفع فقط نرسل مواصفات الخدمة.
        final specificationsResult = await VipServiceApi.submitSpecifications(
          orderId: orderId,
          specifications: widget.collectedSpecs,
          lang: widget.currentLanguage,
        );
        if (!mounted) return;
        if (specificationsResult['success'] != true) {
          setState(() {
            _isSubmitting = false;
            _cardErrorMessage = _isAr
                ? 'تم تأكيد الدفع، لكن تعذر حفظ مواصفات الخدمة. تم تسجيل الطلب؛ يرجى التواصل مع الدعم وعدم إعادة الدفع.'
                : 'Payment was confirmed, but service details could not be saved. The order was recorded; contact support and do not pay again.';
          });
          return;
        }

        setState(() => _isSubmitting = false);
        widget.onPaymentSuccess(orderId, widget.collectedSpecs);
      } else {
        setState(() {
          _isSubmitting = false;
          _cardErrorMessage =
              res['message'] ?? 'تعذر بدء عملية الدفع عبر Stripe';
        });
      }
    } on StripeException catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      if (e.error.code != FailureCode.Canceled) {
        setState(() =>
            _cardErrorMessage = e.error.localizedMessage ?? 'فشلت عملية الدفع');
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isSubmitting = false;
        _cardErrorMessage = 'خطأ في عملية الدفع: $e';
      });
    }
  }

  /// 🟢 الدفع عبر شام كاش ثم إرسال المواصفات للسيرفر
  Future<void> _submitShamCash() async {
    if (_isSubmitting) return;

    if (_accountNameController.text.trim().isEmpty) {
      AppSnackBar.show(
        context: context,
        type: NotificationType.warning,
        isArabic: widget.isArabic,
        messageAr: 'يرجى إدخال اسم حسابك على التطبيق',
        messageEn: 'Please enter your account name',
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final res = await VipServiceApi.submitShamCashPayment(
      serviceType: widget.serviceType,
      amount: widget.price,
      accountName: _accountNameController.text.trim(),
      proofFile: _proofFile,
      lang: widget.currentLanguage,
    );

    if (!mounted) return;

    if (res['success'] == true) {
      final orderId = int.tryParse(res['order_id']?.toString() ?? '');
      if (orderId == null || orderId <= 0) {
        if (!mounted) return;
        setState(() {
          _isSubmitting = false;
          _cardErrorMessage = _isAr
              ? 'استلم السيرفر طلب الدفع، لكن لم يُرجع رقم الطلب. يرجى مراجعة الدعم قبل إعادة الإرسال.'
              : 'The server received the payment request but returned no order ID. Contact support before resubmitting.';
        });
        return;
      }

      final specificationsResult = await VipServiceApi.submitSpecifications(
        orderId: orderId,
        specifications: widget.collectedSpecs,
        lang: widget.currentLanguage,
      );
      if (!mounted) return;
      if (specificationsResult['success'] != true) {
        setState(() {
          _isSubmitting = false;
          _cardErrorMessage = _isAr
              ? 'تم تسجيل طلب الدفع، لكن تعذر حفظ مواصفات الخدمة. يرجى التواصل مع الدعم.'
              : 'The payment request was recorded, but service details could not be saved. Contact support.';
        });
        return;
      }

      setState(() => _isSubmitting = false);
      widget.onPaymentSuccess(orderId, widget.collectedSpecs);
    } else {
      setState(() => _isSubmitting = false);
      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: widget.isArabic,
        messageAr: res['message'],
        messageEn: res['message'],
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = WesalBottomInsets.of(context);

    return Directionality(
      textDirection: widget.isArabic ? TextDirection.rtl : TextDirection.ltr,
      child: Container(
        padding: EdgeInsets.only(
          top: 20,
          left: 20,
          right: 20,
          bottom: bottomInset + 20,
        ),
        decoration: BoxDecoration(
          color: const Color(0xFF0F172A).withValues(alpha: 0.98),
          borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
          border:
              Border.all(color: const Color(0xFF38BDF8).withValues(alpha: 0.4)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                  child:
                      Container(width: 40, height: 4, color: Colors.white30)),
              const SizedBox(height: 16),
              Text(
                widget.serviceTitle,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo'),
              ),
              const SizedBox(height: 4),
              Text(
                '${widget.isArabic ? "القيمة المطلوب دفعها:" : "Total Price:"} \$${widget.price}',
                style: const TextStyle(
                    color: Colors.amber,
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo'),
              ),
              const SizedBox(height: 18),
              Row(
                children: [
                  Expanded(
                    child: GestureDetector(
                      onTap: _isSubmitting
                          ? null
                          : () => setState(() => _selectedCategory = 2),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        decoration: BoxDecoration(
                          color: _selectedCategory == 2
                              ? const Color(0xFF0288D1).withValues(alpha: 0.25)
                              : Colors.white.withValues(alpha: 0.05),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                              color: _selectedCategory == 2
                                  ? const Color(0xFF38BDF8)
                                  : Colors.white12),
                        ),
                        child: Column(
                          children: [
                            Icon(Icons.credit_card_rounded,
                                color: _selectedCategory == 2
                                    ? const Color(0xFF38BDF8)
                                    : Colors.white54,
                                size: 24),
                            const SizedBox(height: 4),
                            Text(
                                widget.isArabic
                                    ? 'فيزا / ماستركارد'
                                    : 'Visa / Credit Card',
                                style: TextStyle(
                                    color: _selectedCategory == 2
                                        ? const Color(0xFF38BDF8)
                                        : Colors.white70,
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo')),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: GestureDetector(
                      onTap: _isSubmitting
                          ? null
                          : () => setState(() => _selectedCategory = 1),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        decoration: BoxDecoration(
                          color: _selectedCategory == 1
                              ? Colors.amber.withValues(alpha: 0.25)
                              : Colors.white.withValues(alpha: 0.05),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                              color: _selectedCategory == 1
                                  ? Colors.amber
                                  : Colors.white12),
                        ),
                        child: Column(
                          children: [
                            Icon(Icons.account_balance_wallet_rounded,
                                color: _selectedCategory == 1
                                    ? Colors.amber
                                    : Colors.white54,
                                size: 24),
                            const SizedBox(height: 4),
                            Text(widget.isArabic ? 'شام كاش' : 'Sham Cash',
                                style: TextStyle(
                                    color: _selectedCategory == 1
                                        ? Colors.amber
                                        : Colors.white70,
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo')),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              if (_selectedCategory == 2) ...[
                if (_cardErrorMessage != null) ...[
                  Text(_cardErrorMessage!,
                      style: const TextStyle(
                          color: Colors.redAccent,
                          fontSize: 12,
                          fontFamily: 'Cairo')),
                  const SizedBox(height: 10),
                ],
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0288D1),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16)),
                    ),
                    icon: _isSubmitting
                        ? const SizedBox.shrink()
                        : const Icon(Icons.payment_rounded,
                            color: Colors.white, size: 20),
                    label: _isSubmitting
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                                color: Colors.white, strokeWidth: 2))
                        : Text(
                            widget.isArabic
                                ? 'الدفع والتأكيد الآن (\$${widget.price})'
                                : 'Pay & Confirm (\$${widget.price})',
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo')),
                    onPressed: _isSubmitting ? null : _startStripeNativePayment,
                  ),
                ),
              ],
              if (_selectedCategory == 1) ...[
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.06),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                          color: Colors.amber.withValues(alpha: 0.4))),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                          widget.isArabic
                              ? 'حول قيمة الخدمة لحساب شام كاش التالي:'
                              : 'Transfer the amount to Sham Cash code:',
                          style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 12.5,
                              fontFamily: 'Cairo')),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.45),
                            borderRadius: BorderRadius.circular(12)),
                        child: Row(
                          children: [
                            const Expanded(
                                child: SelectableText(
                                    _VipServicesScreenState
                                        ._officialShamCashCode,
                                    style: TextStyle(
                                        color: Colors.amber,
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold))),
                            InkWell(
                              onTap: () {
                                Clipboard.setData(const ClipboardData(
                                    text: _VipServicesScreenState
                                        ._officialShamCashCode));
                                AppSnackBar.show(
                                    context: context,
                                    type: NotificationType.success,
                                    isArabic: widget.isArabic,
                                    messageAr: 'تم نسخ الكود بنجاح',
                                    messageEn: 'Code copied');
                              },
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                    color: Colors.amber,
                                    borderRadius: BorderRadius.circular(8)),
                                child: Text(widget.isArabic ? 'نسخ' : 'Copy',
                                    style: const TextStyle(
                                        color: Colors.black,
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                        fontFamily: 'Cairo')),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _accountNameController,
                  enabled: !_isSubmitting,
                  style:
                      const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
                  decoration: InputDecoration(
                    hintText: widget.isArabic
                        ? 'اسم حسابك على تطبيق وصال...'
                        : 'Your account name on Wesal...',
                    hintStyle: const TextStyle(
                        color: Colors.white60,
                        fontSize: 12.5,
                        fontFamily: 'Cairo'),
                    filled: true,
                    fillColor: Colors.white.withValues(alpha: 0.08),
                    border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14)),
                  ),
                ),
                const SizedBox(height: 10),
                GestureDetector(
                  onTap: _isSubmitting ? null : _pickProofImage,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        vertical: 12, horizontal: 14),
                    decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.white24)),
                    child: Row(
                      children: [
                        const Icon(Icons.cloud_upload_rounded,
                            color: Color(0xFF38BDF8), size: 20),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _proofFile != null
                                ? _proofFile!.path.split('/').last
                                : (widget.isArabic
                                    ? 'إرفاق صورة إشعار/وصل التحويل (اختياري)'
                                    : 'Upload receipt screenshot'),
                            style: const TextStyle(
                                color: Colors.white70,
                                fontSize: 12,
                                fontFamily: 'Cairo'),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0288D1),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16)),
                    ),
                    onPressed: _isSubmitting ? null : _submitShamCash,
                    child: _isSubmitting
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                                color: Colors.white, strokeWidth: 2))
                        : Text(
                            widget.isArabic
                                ? 'تأكيد وإرسال إشعار شام كاش'
                                : 'Confirm Sham Cash Payment',
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo')),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// 🟢 مودال تعبئة المواصفات والاستبيان - الخطوة الأولى قبل الانتقال للدفع
// ============================================================================

class _VipSpecificationsModal extends StatefulWidget {
  final int orderId;
  final String serviceType;
  final String serviceTitle;
  final double price;
  final bool isArabic;
  final String currentLanguage;
  final Function(Map<String, dynamic> collectedSpecs) onProceedToPayment;

  const _VipSpecificationsModal({
    required this.orderId,
    required this.serviceType,
    required this.serviceTitle,
    required this.price,
    required this.isArabic,
    required this.currentLanguage,
    required this.onProceedToPayment,
  });

  @override
  State<_VipSpecificationsModal> createState() =>
      _VipSpecificationsModalState();
}

class _VipSpecificationsModalState extends State<_VipSpecificationsModal> {
  int _currentStep = 0;

  final _aboutMeController = TextEditingController();
  final _originCountryCityController = TextEditingController();
  final _residenceCountryCityController = TextEditingController();
  final _ageController = TextEditingController();
  final _nationalityController = TextEditingController();
  final _occupationController = TextEditingController();

  late String _maritalStatus;
  late String _financialStatus;

  final _partnerExpectationsController = TextEditingController();
  final _customRequirementsController = TextEditingController();

  bool get isDirectContactAllowed => widget.price >= 25.0;

  String _selectedContactMethod = 'whatsapp';

  final List<Map<String, String>> _countryCodes = const [
    {'name': 'سوريا', 'code': '+963', 'flag': '🇸🇾'},
    {'name': 'السعودية', 'code': '+966', 'flag': '🇸🇦'},
    {'name': 'الإمارات', 'code': '+971', 'flag': '🇦🇪'},
    {'name': 'تركيا', 'code': '+90', 'flag': '🇹🇷'},
    {'name': 'ألمانيا', 'code': '+49', 'flag': '🇩🇪'},
    {'name': 'لبنان', 'code': '+961', 'flag': '🇱🇧'},
    {'name': 'الأردن', 'code': '+962', 'flag': '🇯🇴'},
    {'name': 'مصر', 'code': '+20', 'flag': '🇪🇬'},
    {'name': 'الكويت', 'code': '+965', 'flag': '🇰🇼'},
    {'name': 'قطر', 'code': '+974', 'flag': '🇶🇦'},
    {'name': 'البحرين', 'code': '+973', 'flag': '🇧🇭'},
    {'name': 'عُمان', 'code': '+968', 'flag': '🇴🇲'},
    {'name': 'العراق', 'code': '+964', 'flag': '🇮🇶'},
    {'name': 'فلسطين', 'code': '+970', 'flag': '🇵🇸'},
    {'name': 'السويد', 'code': '+46', 'flag': '🇸🇪'},
    {'name': 'هولندا', 'code': '+31', 'flag': '🇳🇱'},
    {'name': 'النمسا', 'code': '+43', 'flag': '🇦🇹'},
    {'name': 'فرنسا', 'code': '+33', 'flag': '🇫🇷'},
    {'name': 'المملكة المتحدة', 'code': '+44', 'flag': '🇬🇧'},
    {'name': 'إيطاليا', 'code': '+39', 'flag': '🇮🇹'},
    {'name': 'إسبانيا', 'code': '+34', 'flag': '🇪🇸'},
    {'name': 'سويسرا', 'code': '+41', 'flag': '🇨🇭'},
    {'name': 'بلجيكا', 'code': '+32', 'flag': '🇧🇪'},
    {'name': 'النرويج', 'code': '+47', 'flag': '🇳🇴'},
    {'name': 'الدنمارك', 'code': '+45', 'flag': '🇩🇰'},
    {'name': 'فنلندا', 'code': '+358', 'flag': '🇫🇮'},
    {'name': 'اليونان', 'code': '+30', 'flag': '🇬🇷'},
    {'name': 'أمريكا / كندا', 'code': '+1', 'flag': '🇺🇸'},
    {'name': 'أستراليا', 'code': '+61', 'flag': '🇦🇺'},
    {'name': 'نيوزيلندا', 'code': '+64', 'flag': '🇳🇿'},
    {'name': 'الجزائر', 'code': '+213', 'flag': '🇩ℤ'},
    {'name': 'المغرب', 'code': '+212', 'flag': '🇲🇦'},
    {'name': 'تونس', 'code': '+216', 'flag': '🇹🇳'},
    {'name': 'ليبيا', 'code': '+218', 'flag': '🇱🇾'},
    {'name': 'السودان', 'code': '+249', 'flag': '🇸🇩'},
    {'name': 'اليمن', 'code': '+967', 'flag': '🇾🇪'},
    {'name': 'دولة أخرى 🌍', 'code': '+', 'flag': '🌐'},
  ];

  late String _selectedCountryCode;
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _profileLinkController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _maritalStatus = widget.isArabic ? 'أعزب' : 'Single';
    _financialStatus = widget.isArabic ? 'مستقر' : 'Stable';
    _selectedCountryCode = _countryCodes.first['code']!;
  }

  @override
  void didUpdateWidget(covariant _VipSpecificationsModal oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.isArabic != oldWidget.isArabic) {
      final List<String> maritalItems = widget.isArabic
          ? ['أعزب', 'مطلق', 'أرمل', 'متزوج']
          : ['Single', 'Divorced', 'Widowed', 'Married'];
      final List<String> financialItems = widget.isArabic
          ? ['مستقر', 'متوسط', 'محدود']
          : ['Stable', 'Medium', 'Limited'];

      if (!maritalItems.contains(_maritalStatus)) {
        _maritalStatus = maritalItems.first;
      }
      if (!financialItems.contains(_financialStatus)) {
        _financialStatus = financialItems.first;
      }
    }
  }

  @override
  void dispose() {
    _aboutMeController.dispose();
    _originCountryCityController.dispose();
    _residenceCountryCityController.dispose();
    _ageController.dispose();
    _nationalityController.dispose();
    _occupationController.dispose();
    _partnerExpectationsController.dispose();
    _customRequirementsController.dispose();
    _phoneController.dispose();
    _profileLinkController.dispose();
    super.dispose();
  }

  void _showCountryPicker() {
    String searchQuery = '';
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF0F172A),
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          final filtered = _countryCodes.where((c) {
            final nameMatch = c['name']!.contains(searchQuery);
            final codeMatch = c['code']!.contains(searchQuery);
            return nameMatch || codeMatch;
          }).toList();

          return Container(
            height: MediaQuery.of(context).size.height * 0.70,
            padding: EdgeInsets.fromLTRB(18, 18, 18, 18 + WesalBottomInsets.of(context)),
            child: Column(
              children: [
                Center(
                    child:
                        Container(width: 40, height: 4, color: Colors.white30)),
                const SizedBox(height: 14),
                Text(
                  widget.isArabic
                      ? 'اختر رمز الدولة 🌍'
                      : 'Select Country Code 🌍',
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo'),
                ),
                const SizedBox(height: 12),
                TextField(
                  style:
                      const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
                  decoration: InputDecoration(
                    hintText: widget.isArabic
                        ? 'بحث بالاسم أو الرمز...'
                        : 'Search country...',
                    hintStyle: const TextStyle(color: Colors.white60),
                    prefixIcon: const Icon(Icons.search, color: Colors.amber),
                    filled: true,
                    fillColor: Colors.white10,
                    border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14)),
                  ),
                  onChanged: (val) =>
                      setModalState(() => searchQuery = val.trim()),
                ),
                const SizedBox(height: 10),
                Expanded(
                  child: ListView.builder(
                    itemCount: filtered.length,
                    itemBuilder: (context, index) {
                      final item = filtered[index];
                      return ListTile(
                        leading: Text(item['flag']!,
                            style: const TextStyle(fontSize: 22)),
                        title: Text(item['name']!,
                            style: const TextStyle(
                                color: Colors.white, fontFamily: 'Cairo')),
                        trailing: Text(item['code']!,
                            style: const TextStyle(
                                color: Colors.amber,
                                fontWeight: FontWeight.bold)),
                        onTap: () {
                          setState(() => _selectedCountryCode = item['code']!);
                          Navigator.pop(ctx);
                        },
                      );
                    },
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  /// 🟢 التحقق المحلي من صحة البيانات وجمعها دون إرسال للسيرفر
  void _validateAndProceedToPayment() {
    final ageParsed = int.tryParse(_ageController.text.trim());

    if (_aboutMeController.text.trim().isEmpty ||
        _originCountryCityController.text.trim().isEmpty ||
        _residenceCountryCityController.text.trim().isEmpty ||
        _ageController.text.trim().isEmpty ||
        _nationalityController.text.trim().isEmpty ||
        _occupationController.text.trim().isEmpty ||
        _partnerExpectationsController.text.trim().isEmpty) {
      AppSnackBar.show(
        context: context,
        type: NotificationType.warning,
        isArabic: widget.isArabic,
        messageAr: 'جميع البيانات الأساسية إجبارية للمتابعة',
        messageEn: 'All core details are required',
      );
      return;
    }

    if (ageParsed == null || ageParsed < 18 || ageParsed > 99) {
      AppSnackBar.show(
        context: context,
        type: NotificationType.warning,
        isArabic: widget.isArabic,
        messageAr: 'يرجى إدخال عمر صحيح بين 18 و 99',
        messageEn: 'Please enter a valid age between 18 and 99',
      );
      return;
    }

    if (isDirectContactAllowed) {
      if (_selectedContactMethod == 'whatsapp') {
        if (_phoneController.text.trim().isEmpty) {
          AppSnackBar.show(
            context: context,
            type: NotificationType.warning,
            isArabic: widget.isArabic,
            messageAr: 'يرجى إدخال رقم هاتف الواتساب للتواصل',
            messageEn: 'Please enter WhatsApp phone number',
          );
          return;
        }
      } else {
        if (_profileLinkController.text.trim().isEmpty) {
          AppSnackBar.show(
            context: context,
            type: NotificationType.warning,
            isArabic: widget.isArabic,
            messageAr: 'يرجى إدخال رابط البروفايل أو اسم الحساب',
            messageEn: 'Please enter profile link or username',
          );
          return;
        }
      }
    }

    final Map<String, dynamic> collectedSpecs = {
      'submitted_at': DateTime.now().toIso8601String(),
      'about_me': _aboutMeController.text.trim(),
      'origin_country_city': _originCountryCityController.text.trim(),
      'residence_country_city': _residenceCountryCityController.text.trim(),
      'age': ageParsed,
      'nationality': _nationalityController.text.trim(),
      'marital_status': _maritalStatus,
      'financial_status': _financialStatus,
      'occupation': _occupationController.text.trim(),
      'partner_expectations': _partnerExpectationsController.text.trim(),
      'custom_requirements': _customRequirementsController.text.trim(),
      'has_direct_contact': isDirectContactAllowed,
      'contact_method':
          isDirectContactAllowed ? _selectedContactMethod : 'internal_app',
      'country_code':
          (isDirectContactAllowed && _selectedContactMethod == 'whatsapp')
              ? _selectedCountryCode
              : null,
      'phone_number':
          (isDirectContactAllowed && _selectedContactMethod == 'whatsapp')
              ? _phoneController.text.trim()
              : null,
      'full_phone_number':
          (isDirectContactAllowed && _selectedContactMethod == 'whatsapp')
              ? '$_selectedCountryCode${_phoneController.text.trim()}'
              : null,
      'profile_link':
          (isDirectContactAllowed && _selectedContactMethod != 'whatsapp')
              ? _profileLinkController.text.trim()
              : null,
    };

    widget.onProceedToPayment(collectedSpecs);
  }

  InputDecoration _inputDecoration(String labelText, {String? hintText}) {
    return InputDecoration(
      labelText: labelText,
      labelStyle: const TextStyle(
        fontSize: 13.5,
        color: Color(0xE6FFFFFF),
        fontFamily: 'Cairo',
        fontWeight: FontWeight.w600,
      ),
      hintText: hintText,
      hintStyle: const TextStyle(
        fontSize: 12,
        color: Colors.white60,
        fontFamily: 'Cairo',
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      filled: true,
      fillColor: Colors.white.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Colors.white24),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.2)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFF38BDF8), width: 1.5),
      ),
    );
  }

  Widget _buildStepHeader() {
    final steps = widget.isArabic
        ? ['بياناتك', 'الشريك', 'التواصل']
        : ['Info', 'Partner', 'Contact'];

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 10),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white12),
      ),
      child: Row(
        children: List.generate(steps.length, (index) {
          final isActive = index <= _currentStep;
          final isCurrent = index == _currentStep;
          return Expanded(
            child: GestureDetector(
              onTap: () => setState(() => _currentStep = index),
              child: Row(
                children: [
                  Container(
                    width: 26,
                    height: 26,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: isCurrent
                          ? const Color(0xFF0288D1)
                          : (isActive
                              ? const Color(0xFF0288D1).withValues(alpha: 0.4)
                              : Colors.white12),
                      border: Border.all(
                        color:
                            isActive ? const Color(0xFF38BDF8) : Colors.white24,
                        width: 1.5,
                      ),
                    ),
                    child: Center(
                      child: Text(
                        '${index + 1}',
                        style: TextStyle(
                          color: isActive ? Colors.white : Colors.white54,
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: FittedBox(
                      fit: BoxFit.scaleDown,
                      alignment: widget.isArabic
                          ? Alignment.centerRight
                          : Alignment.centerLeft,
                      child: Text(
                        steps[index],
                        style: TextStyle(
                          color: isCurrent
                              ? Colors.white
                              : (isActive ? Colors.white70 : Colors.white38),
                          fontWeight:
                              isCurrent ? FontWeight.bold : FontWeight.normal,
                          fontSize: 12,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                  ),
                  if (index < steps.length - 1)
                    Container(
                      width: 10,
                      height: 1.5,
                      margin: const EdgeInsets.symmetric(horizontal: 4),
                      color: index < _currentStep
                          ? const Color(0xFF38BDF8)
                          : Colors.white12,
                    ),
                ],
              ),
            ),
          );
        }),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final List<String> maritalItems = widget.isArabic
        ? ['أعزب', 'مطلق', 'أرمل', 'متزوج']
        : ['Single', 'Divorced', 'Widowed', 'Married'];

    final List<String> financialItems = widget.isArabic
        ? ['مستقر', 'متوسط', 'محدود']
        : ['Stable', 'Medium', 'Limited'];

    final bottomPadding = WesalBottomInsets.of(context);

    return Directionality(
      textDirection: widget.isArabic ? TextDirection.rtl : TextDirection.ltr,
      child: Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(context).size.height * 0.88,
        ),
        padding: EdgeInsets.only(
          top: 18,
          left: 18,
          right: 18,
          bottom: bottomPadding + 18,
        ),
        decoration: const BoxDecoration(
          color: Color(0xFF0F172A),
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.workspace_premium_rounded,
                        color: Colors.amber, size: 26),
                    const SizedBox(width: 8),
                    Text(
                      widget.isArabic
                          ? 'استبيان وتفاصيل الخدمة 👑'
                          : 'Service Specifications 👑',
                      style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                          fontFamily: 'Cairo'),
                    ),
                  ],
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded, color: Colors.white70),
                  onPressed: () {
                    if (context.mounted) Navigator.of(context).pop();
                  },
                ),
              ],
            ),
            _buildStepHeader(),
            const Divider(color: Colors.white24, height: 1),
            const SizedBox(height: 12),
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                child: _buildModalStepContent(maritalItems, financialItems),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      if (_currentStep < 2) {
                        setState(() => _currentStep++);
                      } else {
                        _validateAndProceedToPayment();
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0288D1),
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                    child: Text(
                      _currentStep == 2
                          ? (widget.isArabic
                              ? 'متابعة والدفع المباشر 💳'
                              : 'Proceed to Payment 💳')
                          : (widget.isArabic ? 'التالي' : 'Next'),
                      style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          color: Colors.white,
                          fontFamily: 'Cairo'),
                    ),
                  ),
                ),
                if (_currentStep > 0) ...[
                  const SizedBox(width: 10),
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () {
                        setState(() => _currentStep--);
                      },
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: Colors.white38),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                      child: Text(
                        widget.isArabic ? 'السابق' : 'Back',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontFamily: 'Cairo'),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildModalStepContent(
      List<String> maritalItems, List<String> financialItems) {
    if (_currentStep == 0) {
      return Column(
        children: [
          TextField(
            controller: _aboutMeController,
            maxLines: 3,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(
              widget.isArabic ? 'نبذة عني *' : 'About me *',
              hintText: widget.isArabic
                  ? 'مثال: شاب ملتزم، أعمل في مجال كذا، أقيم في كذا.. (توضيح طبيعة السكن والاستقرار)...'
                  : 'Example: Committed young man, working in..., residing in...',
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _ageController,
                  keyboardType: TextInputType.number,
                  style:
                      const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
                  decoration:
                      _inputDecoration(widget.isArabic ? 'العمر *' : 'Age *'),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: TextField(
                  controller: _nationalityController,
                  style:
                      const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
                  decoration: _inputDecoration(
                      widget.isArabic ? 'الجنسية *' : 'Nationality *'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _originCountryCityController,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(widget.isArabic
                ? 'بلد الأصل والمدينة *'
                : 'Origin Country/City *'),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _residenceCountryCityController,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(widget.isArabic
                ? 'بلد الإقامة والمدينة *'
                : 'Residence Country/City *'),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  isExpanded: true,
                  value: _maritalStatus,
                  dropdownColor: const Color(0xFF1E293B),
                  style: const TextStyle(
                      color: Colors.white, fontFamily: 'Cairo', fontSize: 13),
                  decoration: _inputDecoration(widget.isArabic
                      ? 'الحالة الاجتماعية *'
                      : 'Marital Status *'),
                  items: maritalItems
                      .map((e) => DropdownMenuItem(
                          value: e,
                          child: Text(e, overflow: TextOverflow.ellipsis)))
                      .toList(),
                  onChanged: (val) {
                    if (val != null) setState(() => _maritalStatus = val);
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: DropdownButtonFormField<String>(
                  isExpanded: true,
                  value: _financialStatus,
                  dropdownColor: const Color(0xFF1E293B),
                  style: const TextStyle(
                      color: Colors.white, fontFamily: 'Cairo', fontSize: 13),
                  decoration: _inputDecoration(widget.isArabic
                      ? 'الوضع المادي *'
                      : 'Financial Status *'),
                  items: financialItems
                      .map((e) => DropdownMenuItem(
                          value: e,
                          child: Text(e, overflow: TextOverflow.ellipsis)))
                      .toList(),
                  onChanged: (val) {
                    if (val != null) {
                      setState(() => _financialStatus = val);
                    }
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _occupationController,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(
                widget.isArabic ? 'العمل والمهنة *' : 'Occupation *'),
          ),
        ],
      );
    } else if (_currentStep == 1) {
      return Column(
        children: [
          TextField(
            controller: _partnerExpectationsController,
            maxLines: 4,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(
              widget.isArabic
                  ? 'مواصفات شريك الحياة المطلوبة *'
                  : 'Partner specifications *',
              hintText: widget.isArabic
                  ? 'مثال: أنثى خلوقة ومحجبة، العمر من 22 إلى 28، الإقامة في..'
                  : 'Example: Polite female, age 22 to 28, residing in...',
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _customRequirementsController,
            maxLines: 2,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(
              widget.isArabic
                  ? 'تفاصيل أو شروط إضافية (اختياري)'
                  : 'Custom requirements (Optional)',
              hintText: widget.isArabic
                  ? 'أدخل أي ملاحظات مخصصة تود إبلاغ الإدارة بها...'
                  : 'Enter any additional preferences...',
            ),
          ),
        ],
      );
    } else {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (!isDirectContactAllowed) ...[
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.amber.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.amber.withValues(alpha: 0.4)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.security_rounded,
                      color: Colors.amber, size: 26),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      widget.isArabic
                          ? '🔒 حماية وخصوصية: التواصل في هذه الخدمة يتم حصرياً وداخلياً عبر تطبيق وصال للحفاظ على الأمان المطلق للمشتركين.'
                          : '🔒 Privacy Notice: Contact for this service is handled internally via Wesal App for safety.',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12.5,
                        fontFamily: 'Cairo',
                        height: 1.4,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ] else ...[
            Text(
              widget.isArabic
                  ? 'اختر وسيلة التواصل المباشر المفضلة للتنسيق معكم:'
                  : 'Select preferred direct contact method:',
              style: const TextStyle(
                  color: Colors.white70, fontSize: 12.5, fontFamily: 'Cairo'),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                _buildPlatformChip(
                    'whatsapp',
                    widget.isArabic ? 'واتساب' : 'WhatsApp',
                    Icons.chat_rounded,
                    Colors.green),
                const SizedBox(width: 6),
                _buildPlatformChip(
                    'facebook',
                    widget.isArabic ? 'فيسبوك' : 'Facebook',
                    Icons.facebook_rounded,
                    Colors.blue),
                const SizedBox(width: 6),
                _buildPlatformChip(
                    'instagram',
                    widget.isArabic ? 'إنستغرام' : 'Instagram',
                    Icons.camera_alt_rounded,
                    Colors.pink),
                const SizedBox(width: 6),
                _buildPlatformChip(
                    'telegram',
                    widget.isArabic ? 'تيليغرام' : 'Telegram',
                    Icons.send_rounded,
                    Colors.lightBlue),
              ],
            ),
            const SizedBox(height: 14),
            if (_selectedContactMethod == 'whatsapp') ...[
              Text(
                widget.isArabic
                    ? 'رمز الدولة ورقم الواتساب:'
                    : 'Country Code & Phone Number:',
                style: const TextStyle(
                    color: Colors.white70, fontSize: 12, fontFamily: 'Cairo'),
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  InkWell(
                    onTap: _showCountryPicker,
                    child: Container(
                      height: 48,
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        color: Colors.white10,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                            color: Colors.amber.withValues(alpha: 0.5)),
                      ),
                      child: Row(
                        children: [
                          Text(_selectedCountryCode,
                              style: const TextStyle(
                                  color: Colors.amber,
                                  fontWeight: FontWeight.bold)),
                          const SizedBox(width: 4),
                          const Icon(Icons.arrow_drop_down,
                              color: Colors.amber),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _phoneController,
                      keyboardType: TextInputType.phone,
                      style: const TextStyle(
                          color: Colors.white, fontFamily: 'Cairo'),
                      decoration: _inputDecoration('912345678'),
                    ),
                  ),
                ],
              ),
            ] else ...[
              Text(
                widget.isArabic
                    ? 'رابط البروفايل أو اسم المستخدم (Username):'
                    : 'Profile Link or Username:',
                style: const TextStyle(
                    color: Colors.white70, fontSize: 12, fontFamily: 'Cairo'),
              ),
              const SizedBox(height: 6),
              TextField(
                controller: _profileLinkController,
                style:
                    const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
                decoration: _inputDecoration(widget.isArabic
                    ? 'https://... أو @username'
                    : 'https://... or @username'),
              ),
            ],
          ],
          const SizedBox(height: 14),
          const Center(
            child: Text(
              'عند النقر على متابعة والدفع، سيتم نقلكم مباشرة لاختيار طريقة الدفع ولا تُرسل البيانات إلا بعد نجاح الدفع.',
              textAlign: TextAlign.center,
              style: TextStyle(
                  color: Colors.white54, fontSize: 11.5, fontFamily: 'Cairo'),
            ),
          ),
        ],
      );
    }
  }

  Widget _buildPlatformChip(
      String key, String label, IconData icon, Color activeColor) {
    final bool isSelected = _selectedContactMethod == key;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _selectedContactMethod = key),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 2),
          decoration: BoxDecoration(
            color: isSelected
                ? activeColor.withValues(alpha: 0.25)
                : Colors.white.withValues(alpha: 0.06),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
                color: isSelected ? activeColor : Colors.white12,
                width: isSelected ? 1.5 : 1),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon,
                  color: isSelected ? activeColor : Colors.white54, size: 20),
              const SizedBox(height: 4),
              FittedBox(
                fit: BoxFit.scaleDown,
                child: Text(
                  label,
                  style: TextStyle(
                    color: isSelected ? Colors.white : Colors.white60,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

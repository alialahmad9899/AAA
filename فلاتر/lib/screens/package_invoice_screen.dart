import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../constants/colors.dart';
import '../services/api_service.dart';

class PackageInvoiceScreen extends StatefulWidget {
  final int paymentId;
  final String currentLanguage;

  const PackageInvoiceScreen({
    super.key,
    required this.paymentId,
    this.currentLanguage = 'ar',
  });

  @override
  State<PackageInvoiceScreen> createState() => _PackageInvoiceScreenState();
}

class _PackageInvoiceScreenState extends State<PackageInvoiceScreen> {
  late Future<Map<String, dynamic>?> _invoiceFuture;

  bool get _isEnglish => widget.currentLanguage == 'en';

  @override
  void initState() {
    super.initState();
    _invoiceFuture = ApiService.getPackageInvoice(
      widget.paymentId,
      lang: widget.currentLanguage,
    );
  }

  void _retry() {
    setState(() {
      _invoiceFuture = ApiService.getPackageInvoice(
        widget.paymentId,
        lang: widget.currentLanguage,
      );
    });
  }

  String _value(Map<String, dynamic> data, String key) {
    final value = data[key];
    return value == null || value.toString().trim().isEmpty
        ? '—'
        : value.toString();
  }

  String _statusLabel(String value) {
    switch (value.trim().toLowerCase()) {
      case 'paid':
        return _isEnglish ? 'Paid' : 'مدفوعة';
      case 'pending':
        return _isEnglish ? 'Pending' : 'قيد المراجعة';
      case 'rejected':
      case 'failed':
        return _isEnglish ? 'Rejected' : 'مرفوضة';
      default:
        return _isEnglish ? 'Unpaid' : 'غير مدفوعة';
    }
  }

  Future<void> _copyInvoice(Map<String, dynamic> data) async {
    final text = [
      _isEnglish ? 'Wesal package invoice' : 'فاتورة باقة وصال',
      '${_isEnglish ? 'Invoice' : 'رقم الفاتورة'}: ${_value(data, 'purchase_code')}',
      '${_isEnglish ? 'Date' : 'التاريخ'}: ${_value(data, 'purchase_date')}',
      '${_isEnglish ? 'Package' : 'الباقة'}: ${_value(data, 'package_name')}',
      '${_isEnglish ? 'Amount' : 'المبلغ'}: ${_value(data, 'total')}',
      '${_isEnglish ? 'Payment method' : 'طريقة الدفع'}: ${_value(data, 'payment_method')}',
      '${_isEnglish ? 'Status' : 'حالة الدفع'}: ${_statusLabel(_value(data, 'payment_status'))}',
      '${_isEnglish ? 'Customer' : 'اسم العميل'}: ${_value(data, 'name')}',
    ].join('\n');

    await Clipboard.setData(ClipboardData(text: text));
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          _isEnglish ? 'Invoice details copied' : 'تم نسخ تفاصيل الفاتورة',
          style: const TextStyle(fontFamily: 'Cairo'),
        ),
      ),
    );
  }

  Widget _detail(String label, String value, {bool emphasized = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                fontFamily: 'Cairo',
                color: AppColors.textMuted,
                fontSize: 13,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: TextStyle(
                fontFamily: 'Cairo',
                color: AppColors.textDark,
                fontSize: emphasized ? 16 : 13,
                fontWeight: emphasized ? FontWeight.bold : FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isEnglish ? TextDirection.ltr : TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.backgroundColor,
        appBar: AppBar(
          backgroundColor: AppColors.backgroundColor,
          foregroundColor: AppColors.textDark,
          elevation: 0,
          title: Text(
            _isEnglish ? 'Package invoice' : 'فاتورة الاشتراك',
            style: const TextStyle(
              fontFamily: 'Cairo',
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        body: SafeArea(
          child: FutureBuilder<Map<String, dynamic>?>(
            future: _invoiceFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Center(child: CircularProgressIndicator());
              }

              final data = snapshot.data;
              if (data == null || data['result'] == false) {
                final String serverMessage =
                    data?['message']?.toString().trim() ?? '';
                final String errorMessage = serverMessage.isNotEmpty
                    ? serverMessage
                    : (_isEnglish
                        ? 'Invoice could not be loaded. Check your connection and try again.'
                        : 'تعذر تحميل الفاتورة. تحقق من الاتصال ثم أعد المحاولة.');
                return Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.receipt_long_outlined,
                            size: 46, color: AppColors.textMuted),
                        const SizedBox(height: 12),
                        Text(
                          errorMessage,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontFamily: 'Cairo',
                            color: AppColors.textDark,
                          ),
                        ),
                        const SizedBox(height: 16),
                        OutlinedButton(
                          onPressed: _retry,
                          child: Text(_isEnglish ? 'Retry' : 'إعادة المحاولة'),
                        ),
                      ],
                    ),
                  ),
                );
              }

              final status = _value(data, 'payment_status');
              final isPaid = status.toLowerCase() == 'paid';
              return ListView(
                padding: const EdgeInsets.all(18),
                children: [
                  Container(
                    padding: const EdgeInsets.all(22),
                    decoration: BoxDecoration(
                      color: AppColors.cardColor,
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(color: AppColors.lineColor),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.receipt_long_rounded,
                                color: AppColors.primaryTeal, size: 30),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                _isEnglish ? 'Wesal receipt' : 'إيصال وصال',
                                style: const TextStyle(
                                  fontFamily: 'Cairo',
                                  color: AppColors.textDark,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 12, vertical: 6),
                              decoration: BoxDecoration(
                                color: (isPaid
                                        ? AppColors.success
                                        : AppColors.warning)
                                    .withValues(alpha: 0.13),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                _statusLabel(status),
                                style: TextStyle(
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold,
                                  color: isPaid
                                      ? AppColors.success
                                      : AppColors.warning,
                                  fontSize: 12,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 18),
                        const Divider(color: AppColors.lineColor),
                        _detail(
                          _isEnglish ? 'Invoice number' : 'رقم الفاتورة',
                          _value(data, 'purchase_code'),
                        ),
                        _detail(
                          _isEnglish ? 'Purchase date' : 'تاريخ الشراء',
                          _value(data, 'purchase_date'),
                        ),
                        _detail(
                          _isEnglish ? 'Package' : 'اسم الباقة',
                          _value(data, 'package_name'),
                        ),
                        _detail(
                          _isEnglish ? 'Payment method' : 'طريقة الدفع',
                          _value(data, 'payment_method'),
                        ),
                        _detail(
                          _isEnglish ? 'Customer' : 'اسم العميل',
                          _value(data, 'name'),
                        ),
                        _detail(
                          _isEnglish ? 'Email' : 'البريد الإلكتروني',
                          _value(data, 'email'),
                        ),
                        const Divider(color: AppColors.lineColor),
                        _detail(
                          _isEnglish ? 'Total' : 'الإجمالي',
                          _value(data, 'total'),
                          emphasized: true,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  SizedBox(
                    height: 50,
                    child: ElevatedButton.icon(
                      onPressed: () => _copyInvoice(data),
                      icon: const Icon(Icons.copy_rounded),
                      label: Text(
                        _isEnglish ? 'Copy invoice details' : 'نسخ تفاصيل الفاتورة',
                        style: const TextStyle(
                          fontFamily: 'Cairo',
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryTeal,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    _isEnglish
                        ? 'This invoice is loaded securely from your own purchase record.'
                        : 'تُحمّل هذه الفاتورة بأمان من سجل مشتريات حسابك فقط.',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontFamily: 'Cairo',
                      color: AppColors.textMuted,
                      fontSize: 11,
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// اسم الملف: lib/widgets/vip_survey_wizard_dialog.dart
// مشروع: تطبيق وصال (Wesal Platform) - Modern Premium UI 2026
// الوصف: نافذة تقديم طلب باقة VIP التفاعلية مع تحسينات كاملة للواجهة وتجربة المستخدم
// ============================================================================

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'app_snackbar.dart';

class VipSurveyWizardDialog extends StatefulWidget {
  final int? packagePaymentId;
  final String userToken;
  final String baseUrl;
  final bool isArabic;

  const VipSurveyWizardDialog({
    super.key,
    this.packagePaymentId,
    required this.userToken,
    required this.baseUrl,
    this.isArabic = true,
  });

  @override
  State<VipSurveyWizardDialog> createState() => _VipSurveyWizardDialogState();
}

class _VipSurveyWizardDialogState extends State<VipSurveyWizardDialog> {
  int _currentStep = 0;
  bool _isLoading = false;

  // متحكمات الحقول الشخصية المطلوبة
  final _aboutMeController = TextEditingController();
  final _originCountryCityController = TextEditingController();
  final _residenceCountryCityController = TextEditingController();
  final _ageController = TextEditingController();
  final _nationalityController = TextEditingController();
  final _occupationController = TextEditingController();

  // القوائم المنسدلة
  late String _maritalStatus;
  late String _financialStatus;

  // متحكم الشريك المطلوب
  final _partnerExpectationsController = TextEditingController();

  @override
  void initState() {
    super.initState();
    // ضبط القيم الأولية للقوائم بناءً على لغة الواجهة
    _maritalStatus = widget.isArabic ? 'أعزب' : 'Single';
    _financialStatus = widget.isArabic ? 'مستقر' : 'Stable';
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
    super.dispose();
  }

  /// 🟢 دالة التحقق الإجباري الصارم وإرسال البيانات بأمان عالي
  Future<void> _submitSurvey() async {
    if (_isLoading) return;

    final ageParsed = int.tryParse(_ageController.text.trim());

    // 1️⃣ التحقق من تعبئة جميع الحقول
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
        messageAr: 'جميع الحقول إجبارية، يرجى تعبئة كافة البيانات للاستمرار',
        messageEn: 'All fields are required, please fill in all details',
      );
      return;
    }

    // 2️⃣ التحقق من صحة القيمة الرقمية للحقل (العمر)
    if (ageParsed == null || ageParsed < 18 || ageParsed > 99) {
      AppSnackBar.show(
        context: context,
        type: NotificationType.warning,
        isArabic: widget.isArabic,
        messageAr: 'يرجى إدخال عمر صحيح محصور بين 18 و 99 سنة',
        messageEn: 'Please enter a valid age between 18 and 99',
      );
      return;
    }

    setState(() => _isLoading = true);

    final String cleanBaseUrl = widget.baseUrl.endsWith('/')
        ? widget.baseUrl.substring(0, widget.baseUrl.length - 1)
        : widget.baseUrl;

    final url = Uri.parse('$cleanBaseUrl/member/vip-survey/store');

    try {
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ${widget.userToken}',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'package_payment_id': widget.packagePaymentId,
          'about_me': _aboutMeController.text.trim(),
          'origin_country_city': _originCountryCityController.text.trim(),
          'residence_country_city': _residenceCountryCityController.text.trim(),
          'age': ageParsed,
          'nationality': _nationalityController.text.trim(),
          'marital_status': _maritalStatus,
          'financial_status': _financialStatus,
          'occupation': _occupationController.text.trim(),
          'partner_expectations': _partnerExpectationsController.text.trim(),
        }),
      );

      if (!mounted) return;

      dynamic data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        data = null;
      }

      setState(() => _isLoading = false);

      if (response.statusCode == 200 &&
          data is Map &&
          (data['result'] == true || data['success'] == true)) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.success,
          isArabic: widget.isArabic,
          messageAr: 'تم إرسال طلبك بنجاح! جاري إعداد بطاقة العرض الخاصة بك 🚀',
          messageEn: 'Request submitted! Your post card is being generated 🚀',
        );

        Navigator.of(context).pop();
      } else {
        final errorMessage = (data is Map && data['message'] != null)
            ? data['message'].toString()
            : null;

        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: widget.isArabic,
          messageAr: errorMessage ?? 'حدث خطأ أثناء حفظ الطلب',
          messageEn: errorMessage ?? 'Error saving your request',
        );
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _isLoading = false);

      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: widget.isArabic,
        messageAr: 'تعذر الاتصال بالخادم، يرجى التحقق من الشبكة',
        messageEn: 'Network connection failed',
      );
    }
  }

  /// 🎨 تصميم التنسيق الخاص بحقول الإدخال المحسّن للرؤية العالية
  InputDecoration _inputDecoration(String labelText, {String? hintText}) {
    return InputDecoration(
      labelText: labelText,
      labelStyle: const TextStyle(
        fontSize: 13.5,
        color: Color(
            0xE6FFFFFF), // 🟢 تم الإصلاح: درجة الأبيض بشفافية 90% مقبولة كـ const
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

  /// 👑 هيدر الخطوات التفاعلي المستجيب لمنع الطفح والتداخل
  Widget _buildStepHeader() {
    final steps = widget.isArabic
        ? ['بياناتك', 'الشريك', 'التأكيد']
        : ['Info', 'Partner', 'Confirm'];

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
              onTap: _isLoading
                  ? null
                  : () => setState(() => _currentStep = index),
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

    if (!maritalItems.contains(_maritalStatus)) {
      _maritalStatus = maritalItems.first;
    }
    if (!financialItems.contains(_financialStatus)) {
      _financialStatus = financialItems.first;
    }

    final bottomPadding = MediaQuery.of(context).viewInsets.bottom;

    return PopScope(
      canPop: !_isLoading,
      child: Directionality(
        textDirection: widget.isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          backgroundColor: const Color(0xFF0F172A),
          insetPadding:
              const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 150),
            padding: EdgeInsets.only(
              top: 18,
              left: 18,
              right: 18,
              bottom: bottomPadding > 0 ? 12 : 18,
            ),
            constraints: BoxConstraints(
              maxWidth: 500,
              maxHeight: MediaQuery.of(context).size.height * 0.85,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // 1️⃣ الهيدر الرئيسي للنافذة
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
                              ? 'نشر طلب باقة VIP 👑'
                              : 'VIP Promotion 👑',
                          style: const TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                              fontFamily: 'Cairo'),
                        ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded,
                          color: Colors.white70),
                      onPressed: _isLoading
                          ? null
                          : () {
                              if (context.mounted) Navigator.of(context).pop();
                            },
                    ),
                  ],
                ),

                // 2️⃣ شريط الخطوات المخصص
                _buildStepHeader(),
                const Divider(color: Colors.white24, height: 1),
                const SizedBox(height: 12),

                // 3️⃣ المحتوى المتغير للخطوات مع إمكانية التمرير الخالي من الأخطاء
                Expanded(
                  child: SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    child: _buildStepContent(maritalItems, financialItems),
                  ),
                ),

                // 4️⃣ الأزرار السفلية الثابتة للتنقل
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton(
                        onPressed: _isLoading
                            ? null
                            : () {
                                if (_currentStep < 2) {
                                  setState(() => _currentStep++);
                                } else {
                                  _submitSurvey();
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
                                  ? 'إرسال الطلب 🚀'
                                  : 'Submit 🚀')
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
                          onPressed: _isLoading
                              ? null
                              : () {
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

                if (_isLoading)
                  const Padding(
                    padding: EdgeInsets.only(top: 8.0),
                    child: CircularProgressIndicator(color: Color(0xFF0288D1)),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// 🧩 بناء المحتوى الخاص بكل خطوة
  Widget _buildStepContent(
      List<String> maritalItems, List<String> financialItems) {
    if (_currentStep == 0) {
      return Column(
        children: [
          TextField(
            controller: _aboutMeController,
            enabled: !_isLoading,
            maxLines: 3,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(
              widget.isArabic ? 'نبذة عني *' : 'About me *',
              hintText: widget.isArabic
                  ? 'مثال: شاب ملتزم، أعمل في مجال كذا، أقيم في كذا.. (في حال مطلق أو متزوج، يرجى توضيح الأطفال والاستقرار)...'
                  : 'Example: Committed young man, working in..., residing in...',
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _ageController,
                  enabled: !_isLoading,
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
                  enabled: !_isLoading,
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
            enabled: !_isLoading,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(widget.isArabic
                ? 'بلد الأصل والمدينة *'
                : 'Origin Country/City *'),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _residenceCountryCityController,
            enabled: !_isLoading,
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
                  isExpanded: true, // 💡 توسعة الخيارات لمنع اقتطاع النص
                  initialValue: _maritalStatus,
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
                  onChanged: _isLoading
                      ? null
                      : (val) {
                          if (val != null) setState(() => _maritalStatus = val);
                        },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: _financialStatus,
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
                  onChanged: _isLoading
                      ? null
                      : (val) {
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
            enabled: !_isLoading,
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
            enabled: !_isLoading,
            maxLines: 5,
            style: const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
            decoration: _inputDecoration(
              widget.isArabic
                  ? 'مواصفات شريك الحياة المطلوبة (العمر، الإقامة، الصفات...) *'
                  : 'Partner specifications *',
              hintText: widget.isArabic
                  ? 'مثال: أنثى خلوقة ومحجبة، العمر من 22 إلى 28، الإقامة في..، الاهتمام بـ...'
                  : 'Example: Polite veiled female, age 22 to 28, residing in...',
            ),
          ),
        ],
      );
    } else {
      return const Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(height: 12),
          Icon(Icons.stars_rounded, size: 64, color: Colors.amber),
          SizedBox(height: 12),
          Text(
            'جميع البيانات جاهزة ومكتملة. عند الضغط على إرسال، سيتم إنشاء بطاقة العرض الخاصة بك ونشرها على فيسبوك بعد مراجعة الإدارة.',
            textAlign: TextAlign.center,
            style: TextStyle(
                color: Colors.white70,
                fontFamily: 'Cairo',
                fontSize: 13.5,
                height: 1.5),
          ),
          SizedBox(height: 12),
        ],
      );
    }
  }
}

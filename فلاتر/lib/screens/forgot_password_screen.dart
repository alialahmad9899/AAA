import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/colors.dart';
import '../services/api_service.dart';
import '../utils/arabic_number_formatter.dart';

class ForgotPasswordScreen extends StatefulWidget {
  final String currentLanguage;
  final Future<Map<String, dynamic>> Function(String email)? onRequestCode;
  final Future<Map<String, dynamic>> Function(String email, String code, String password, String confirmation)? onResetPassword;

  const ForgotPasswordScreen({
    super.key,
    this.currentLanguage = 'ar',
    this.onRequestCode,
    this.onResetPassword,
  });

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _code = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _codeRequested = false, _loading = false, _completed = false;
  bool _showPassword = false, _showConfirm = false;
  bool get _ar => widget.currentLanguage == 'ar';

  void _message(String message, bool success) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(message, textAlign: _ar ? TextAlign.right : TextAlign.left),
        backgroundColor: success ? AppColors.success : AppColors.error,
      ));
  }

  Future<void> _request() async {
    if (_loading || !_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final email = _email.text.trim().toLowerCase();
      final result = widget.onRequestCode != null
          ? await widget.onRequestCode!(email)
          : await ApiService.requestPasswordReset(email);
      if (!mounted) return;
      setState(() => _loading = false);
      if (result['success'] == true || result['result'] == true) {
        setState(() => _codeRequested = true);
        _message(
          _ar ? 'إذا كان البريد مسجلاً فسيصلك رمز الاستعادة. تحقق من الرسائل غير المرغوب فيها أيضاً.' : 'If the email is registered, a reset code was sent. Check your spam folder too.',
          true,
        );
      } else {
        _message(result['message']?.toString() ?? (_ar ? 'تعذر إرسال الرمز.' : 'Could not send the code.'), false);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
      _message(_ar ? 'تعذر الاتصال. تحقق من الإنترنت.' : 'Connection failed. Check your internet.', false);
    }
  }

  Future<void> _reset() async {
    if (_loading || !_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final email = _email.text.trim().toLowerCase();
      final result = widget.onResetPassword != null
          ? await widget.onResetPassword!(email, _code.text.trim(), _password.text, _confirm.text)
          : await ApiService.resetPassword(
              email: email,
              verificationCode: _code.text.trim(),
              password: _password.text,
              passwordConfirmation: _confirm.text,
            );
      if (!mounted) return;
      setState(() => _loading = false);
      if (result['success'] == true || result['result'] == true) {
        setState(() => _completed = true);
      } else {
        _message(result['message']?.toString() ?? (_ar ? 'الرمز غير صحيح أو منتهي الصلاحية.' : 'The code is invalid or expired.'), false);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
      _message(_ar ? 'تعذر الاتصال بالسيرفر.' : 'Could not reach the server.', false);
    }
  }

  @override
  void dispose() {
    _email.dispose(); _code.dispose(); _password.dispose(); _confirm.dispose();
    super.dispose();
  }

  InputDecoration _input(String label, IconData icon) => InputDecoration(
    labelText: label,
    prefixIcon: Icon(icon, color: AppColors.primaryTeal),
    filled: true,
    fillColor: AppColors.cardColor,
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: const BorderSide(color: AppColors.lineColor),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: const BorderSide(color: AppColors.primaryTeal, width: 1.5),
    ),
  );

  @override
  Widget build(BuildContext context) => Directionality(
    textDirection: _ar ? TextDirection.rtl : TextDirection.ltr,
    child: Scaffold(
      backgroundColor: AppColors.backgroundColor,
      appBar: AppBar(
        title: Text(_ar ? 'استعادة كلمة المرور' : 'Reset password',
          style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.backgroundColor,
        foregroundColor: AppColors.textDark,
        elevation: 0,
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Icon(Icons.mark_email_read_outlined, size: 58, color: AppColors.primaryTeal),
                    const SizedBox(height: 18),
                    Text(
                      _completed ? (_ar ? 'تم تغيير كلمة المرور' : 'Password updated') : (_ar ? 'استعد الوصول إلى حسابك' : 'Recover access to your account'),
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 21, fontWeight: FontWeight.bold, color: AppColors.textDark, fontFamily: 'Cairo'),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      _completed
                        ? (_ar ? 'استخدم كلمة المرور الجديدة لتسجيل الدخول.' : 'Use your new password to sign in.')
                        : (_ar ? 'أدخل بريدك المسجل وسنرسل رمزاً صالحاً لمدة 10 دقائق.' : 'Enter your registered email. The code is valid for 10 minutes.'),
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 13, height: 1.7, color: AppColors.textMuted, fontFamily: 'Cairo'),
                    ),
                    const SizedBox(height: 24),
                    if (_completed) ...[
                      const Icon(Icons.check_circle_rounded, size: 54, color: AppColors.success),
                      const SizedBox(height: 18),
                      ElevatedButton(
                        key: const Key('password-reset-back-to-login'),
                        onPressed: () => Navigator.of(context).pop(true),
                        style: ElevatedButton.styleFrom(
                          minimumSize: const Size.fromHeight(52),
                          backgroundColor: AppColors.primaryTeal,
                          foregroundColor: Colors.white,
                        ),
                        child: Text(_ar ? 'العودة لتسجيل الدخول' : 'Back to sign in'),
                      ),
                    ] else ...[
                      TextFormField(
                        key: const Key('password-reset-email'),
                        controller: _email,
                        enabled: !_loading && !_codeRequested,
                        keyboardType: TextInputType.emailAddress,
                        autofillHints: const [AutofillHints.email],
                        decoration: _input(_ar ? 'البريد الإلكتروني' : 'Email address', Icons.email_outlined),
                        validator: (v) => RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(v?.trim() ?? '')
                          ? null : (_ar ? 'أدخل بريداً إلكترونياً صحيحاً' : 'Enter a valid email address'),
                        onFieldSubmitted: (_) { if (!_codeRequested) _request(); },
                      ),
                      if (_codeRequested) ...[
                        const SizedBox(height: 14),
                        TextFormField(
                          key: const Key('password-reset-code'),
                          controller: _code,
                          keyboardType: TextInputType.number,
                          maxLength: 6,
                          inputFormatters: [ArabicToEnglishNumbersFormatter(), FilteringTextInputFormatter.digitsOnly],
                          decoration: _input(_ar ? 'رمز التحقق' : 'Verification code', Icons.password_rounded),
                          validator: (v) => RegExp(r'^\d{4,6}$').hasMatch(v?.trim() ?? '')
                            ? null : (_ar ? 'أدخل الرمز المكوّن من 4 إلى 6 أرقام' : 'Enter the 4–6 digit code'),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          key: const Key('password-reset-password'),
                          controller: _password,
                          obscureText: !_showPassword,
                          decoration: _input(_ar ? 'كلمة المرور الجديدة' : 'New password', Icons.lock_outline).copyWith(
                            suffixIcon: IconButton(
                              onPressed: () => setState(() => _showPassword = !_showPassword),
                              icon: Icon(_showPassword ? Icons.visibility_off : Icons.visibility),
                            ),
                          ),
                          validator: (v) => (v?.length ?? 0) >= 8 ? null : (_ar ? 'كلمة المرور 8 أحرف على الأقل' : 'Use at least 8 characters'),
                        ),
                        const SizedBox(height: 14),
                        TextFormField(
                          key: const Key('password-reset-confirmation'),
                          controller: _confirm,
                          obscureText: !_showConfirm,
                          decoration: _input(_ar ? 'تأكيد كلمة المرور' : 'Confirm password', Icons.lock_reset).copyWith(
                            suffixIcon: IconButton(
                              onPressed: () => setState(() => _showConfirm = !_showConfirm),
                              icon: Icon(_showConfirm ? Icons.visibility_off : Icons.visibility),
                            ),
                          ),
                          validator: (v) => v == _password.text
                            ? null : (_ar ? 'كلمتا المرور غير متطابقتين' : 'Passwords do not match'),
                          onFieldSubmitted: (_) => _reset(),
                        ),
                      ],
                      const SizedBox(height: 18),
                      ElevatedButton(
                        key: const Key('password-reset-send-code'),
                        onPressed: _loading ? null : (_codeRequested ? _reset : _request),
                        style: ElevatedButton.styleFrom(
                          minimumSize: const Size.fromHeight(52),
                          backgroundColor: AppColors.primaryTeal,
                          foregroundColor: Colors.white,
                        ),
                        child: _loading
                          ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white))
                          : Text(_codeRequested ? (_ar ? 'تأكيد وتغيير كلمة المرور' : 'Confirm and reset password') : (_ar ? 'إرسال رمز التحقق إلى البريد' : 'Send code to email')),
                      ),
                      if (_codeRequested) ...[
                        TextButton(onPressed: _loading ? null : _request, child: Text(_ar ? 'إعادة إرسال الرمز' : 'Resend code')),
                        TextButton(
                          onPressed: _loading ? null : () => setState(() {
                            _codeRequested = false; _code.clear(); _password.clear(); _confirm.clear();
                          }),
                          child: Text(_ar ? 'تغيير البريد الإلكتروني' : 'Change email address'),
                        ),
                      ],
                    ],
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  );
}

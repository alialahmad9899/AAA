import 'package:flutter/material.dart';
import '../constants/colors.dart';
import '../services/api_service.dart';

class ChangePasswordScreen extends StatefulWidget {
  final String currentLanguage;
  final Future<Map<String, dynamic>> Function(String oldPassword, String password, String confirmation)? onSubmit;
  const ChangePasswordScreen({super.key, this.currentLanguage = 'ar', this.onSubmit});

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _old = TextEditingController(), _new = TextEditingController(), _confirm = TextEditingController();
  bool _loading = false, _done = false, _showOld = false, _showNew = false, _showConfirm = false;
  bool get _ar => widget.currentLanguage == 'ar';

  void _message(String value, bool ok) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(value), backgroundColor: ok ? AppColors.success : AppColors.error));
  }

  Future<void> _submit() async {
    if (_loading || !_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final result = widget.onSubmit != null
        ? await widget.onSubmit!(_old.text, _new.text, _confirm.text)
        : await ApiService.changePassword(oldPassword: _old.text, password: _new.text, passwordConfirmation: _confirm.text);
      if (!mounted) return;
      setState(() { _loading = false; _done = result['success'] == true || result['result'] == true; });
      if (_done) {
        _old.clear(); _new.clear(); _confirm.clear();
        _message(_ar ? 'تم تغيير كلمة المرور. بقي هذا الجهاز مسجّل الدخول.' : 'Password changed. This device remains signed in.', true);
      } else {
        _message(result['message']?.toString() ?? (_ar ? 'تعذر تغيير كلمة المرور.' : 'Could not change password.'), false);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
      _message(_ar ? 'تعذر الاتصال بالسيرفر.' : 'Could not reach the server.', false);
    }
  }

  @override
  void dispose() { _old.dispose(); _new.dispose(); _confirm.dispose(); super.dispose(); }

  InputDecoration _dec(String label, IconData icon, bool visible, VoidCallback toggle) => InputDecoration(
    labelText: label,
    prefixIcon: Icon(icon, color: AppColors.primaryTeal),
    suffixIcon: IconButton(onPressed: toggle, icon: Icon(visible ? Icons.visibility_off : Icons.visibility)),
    filled: true, fillColor: AppColors.cardColor,
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
  );

  @override
  Widget build(BuildContext context) => Directionality(
    textDirection: _ar ? TextDirection.rtl : TextDirection.ltr,
    child: Scaffold(
      backgroundColor: AppColors.backgroundColor,
      appBar: AppBar(
        title: Text(_ar ? 'تغيير كلمة المرور' : 'Change password', style: const TextStyle(fontFamily: 'Cairo')),
        backgroundColor: AppColors.backgroundColor, foregroundColor: AppColors.textDark, elevation: 0,
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: _done
                ? Column(children: [
                    const SizedBox(height: 36),
                    const Icon(Icons.check_circle_rounded, size: 62, color: AppColors.success),
                    const SizedBox(height: 16),
                    Text(_ar ? 'تم تحديث كلمة المرور بنجاح' : 'Password updated successfully', textAlign: TextAlign.center, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: AppColors.textDark)),
                    const SizedBox(height: 10),
                    Text(_ar ? 'تم إنهاء الجلسات الأخرى لحماية حسابك.' : 'Other sessions were revoked to protect your account.', textAlign: TextAlign.center, style: const TextStyle(color: AppColors.textMuted)),
                  ])
                : Form(
                    key: _formKey,
                    child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                      Text(_ar ? 'أدخل كلمة المرور الحالية ثم اختر كلمة مرور جديدة.' : 'Enter your current password, then choose a new one.', style: const TextStyle(color: AppColors.textMuted)),
                      const SizedBox(height: 18),
                      TextFormField(key: const Key('change-password-old'), controller: _old, obscureText: !_showOld, decoration: _dec(_ar ? 'كلمة المرور الحالية' : 'Current password', Icons.lock_outline, _showOld, () => setState(() => _showOld = !_showOld)), validator: (v) => (v?.isNotEmpty ?? false) ? null : (_ar ? 'أدخل كلمة المرور الحالية' : 'Enter your current password')),
                      const SizedBox(height: 14),
                      TextFormField(key: const Key('change-password-new'), controller: _new, obscureText: !_showNew, decoration: _dec(_ar ? 'كلمة المرور الجديدة' : 'New password', Icons.lock_reset, _showNew, () => setState(() => _showNew = !_showNew)), validator: (v) => (v?.length ?? 0) >= 8 ? null : (_ar ? 'استخدم 8 أحرف على الأقل' : 'Use at least 8 characters')),
                      const SizedBox(height: 14),
                      TextFormField(key: const Key('change-password-confirmation'), controller: _confirm, obscureText: !_showConfirm, decoration: _dec(_ar ? 'تأكيد كلمة المرور' : 'Confirm password', Icons.verified_user_outlined, _showConfirm, () => setState(() => _showConfirm = !_showConfirm)), validator: (v) {
                        if (v != _new.text) return _ar ? 'كلمتا المرور غير متطابقتين' : 'Passwords do not match';
                        if (v == _old.text) return _ar ? 'اختر كلمة مرور مختلفة عن الحالية' : 'Choose a password different from the current one';
                        return null;
                      }, onFieldSubmitted: (_) => _submit()),
                      const SizedBox(height: 20),
                      ElevatedButton(key: const Key('change-password-submit'), onPressed: _loading ? null : _submit, style: ElevatedButton.styleFrom(minimumSize: const Size.fromHeight(52), backgroundColor: AppColors.primaryTeal, foregroundColor: Colors.white), child: _loading ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Text(_ar ? 'حفظ كلمة المرور' : 'Save password')),
                    ]),
                  ),
            ),
          ),
        ),
      ),
    ),
  );
}

// ============================================================================
// اسم الملف: lib/screens/login_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة تسجيل الدخول بالتصميم الزجاجي الفاخر المربوطة بـ Google Sign-In
//        ونظام الإشعارات المنبثقة المزدوجة العائمة والتخزين المشفّر.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_sign_in/google_sign_in.dart';
import '../constants/colors.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../widgets/app_snackbar.dart';
import '../utils/arabic_number_formatter.dart';
import 'main_home_screen.dart';
import 'signup_screen.dart';

/// شاشة تسجيل الدخول بالتصميم الزجاجي السماوي الفاخر 2026
class LoginScreen extends StatefulWidget {
  final String currentLanguage;

  const LoginScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();

  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _rememberMe = false;

  /// تهيئة كائن تسجيل الدخول الرسمي المباشر بواسطة Google
  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
  );

  bool get _isArabic => widget.currentLanguage == 'ar';

  /// 🌟 نظام إظهار التنويهات الزجاجية العائمة بتصميم Modern Premium UI 2026
  void _showProfessionalNotification({
    required String title,
    required String message,
    required bool isSuccess,
  }) {
    if (!mounted) return;
    final bool isArabic =
        Directionality.maybeOf(context) != TextDirection.ltr;
    AppSnackBar.show(
      context: context,
      type: isSuccess ? NotificationType.success : NotificationType.error,
      isArabic: isArabic,
      titleAr: title,
      titleEn: title,
      messageAr: message,
      messageEn: message,
      duration: const Duration(seconds: 4),
    );
  }

  /// 🟢 دالة معالجة تسجيل الدخول وربط FCM Token بشكل آمن
  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      // 1. إرسال طلب تسجيل الدخول للسيرفر
      final result = await ApiService.signin(
        _emailController.text.trim(),
        _passwordController.text.trim(),
      );

      if (!mounted) return;

      if (result['success'] == true) {
        await _updateFcmToken();

        if (!mounted) return;
        setState(() => _isLoading = false);

        // 2. عرض رسالة النجاح الاحترافية
        _showProfessionalNotification(
          title: _isArabic ? 'مرحباً بك مجدداً!' : 'Welcome Back!',
          message: _isArabic
              ? 'تم تسجيل الدخول بنجاح، جاري تحضير تطبيق وصال...'
              : 'Login successful, loading Wesal Platform...',
          isSuccess: true,
        );

        // 3. الانتقال للشاشة الرئيسية وتفريغ كدس التنقل بالكامل
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(
            builder: (context) => MainHomeScreen(
              currentLanguage: widget.currentLanguage,
            ),
          ),
          (route) => false,
        );
      } else {
        if (!mounted) return;
        setState(() => _isLoading = false);

        // 4. عرض رسالة الخطأ الاحترافية
        _showProfessionalNotification(
          title: _isArabic ? 'فشل تسجيل الدخول' : 'Login Failed',
          message: result['message'] ??
              (_isArabic
                  ? 'يرجى التأكد من البريد الإلكتروني وكلمة المرور'
                  : 'Please verify your credentials and try again'),
          isSuccess: false,
        );
      }
    } catch (e) {
      debugPrint('❌ [Login Error]: $e');
      if (!mounted) return;
      setState(() => _isLoading = false);

      _showProfessionalNotification(
        title: _isArabic ? 'خطأ في الاتصال' : 'Connection Error',
        message: _isArabic
            ? 'حدث خطأ في الاتصال، يرجى التأكد من شبكة الإنترنت والمحاولة لاحقاً'
            : 'Connection error, please check your network and try again',
        isSuccess: false,
      );
    }
  }

  /// 🟢 دالة تسجيل الدخول المباشر باستخدام حساب Google الرسمي
  Future<void> _handleGoogleSignIn() async {
    try {
      setState(() => _isLoading = true);

      // 1. إظهار نافذة اختيار حساب Google للمستخدم
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();

      if (!mounted) return;

      if (googleUser == null) {
        // إلغاء العملية من قبل المستخدم
        setState(() => _isLoading = false);
        return;
      }

      // 2. استخراج بيانات المصادقة والتوكنات المشفّرة
      final GoogleSignInAuthentication googleAuth =
          await googleUser.authentication;

      if (!mounted) return;

      final String? idToken = googleAuth.idToken;
      final String? accessToken = googleAuth.accessToken;
      final String email = googleUser.email;
      final String name = googleUser.displayName ?? '';
      final String googleId = googleUser.id;
      final String photoUrl = googleUser.photoUrl ?? '';

      // 3. إرسال البيانات الموثقة إلى سيرفر منصة "وصال"
      final result = await ApiService.googleSignIn(
        email: email,
        name: name,
        googleId: googleId,
        idToken: idToken,
        accessToken: accessToken,
        photoUrl: photoUrl,
      );

      if (!mounted) return;

      // 🟢 الحالة الأولى: الحساب موجود وتم تسجيل الدخول بنجاح
      if (result['success'] == true) {
        await _updateFcmToken();

        if (!mounted) return;
        setState(() => _isLoading = false);

        // عرض التنبيه الاحترافي للنجاح
        _showProfessionalNotification(
          title: _isArabic ? 'توثيق ناجح' : 'Authentication Success',
          message: _isArabic
              ? 'تم تسجيل الدخول بواسطة Google بنجاح!'
              : 'Signed in with Google successfully!',
          isSuccess: true,
        );

        // الانتقال المباشر وتفريغ الكدس
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(
            builder: (context) => MainHomeScreen(
              currentLanguage: widget.currentLanguage,
            ),
          ),
          (route) => false,
        );
      }
      // 🟡 الحالة الثانية: المستخدم لا يملك حساباً -> التحويل لإنشاء حساب مع تعبئة البيانات تلقائياً
      else if (result['is_registered'] == false) {
        setState(() => _isLoading = false);

        _showProfessionalNotification(
          title: _isArabic ? 'حساب جديد' : 'New Account',
          message: _isArabic
              ? 'لا يوجد حساب مرتبط بهذا البريد، جاري تحويلك لإكمال التسجيل...'
              : 'No account found. Redirecting to complete registration...',
          isSuccess: true,
        );

        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => SignupScreen(
              currentLanguage: widget.currentLanguage,
              initialEmail: result['email'] ?? email,
              initialName: result['name'] ?? name,
            ),
          ),
        );
      }
      // 🔴 الحالة الثالثة: خطأ في السيرفر أو الرفض
      else {
        setState(() => _isLoading = false);
        _showProfessionalNotification(
          title: _isArabic ? 'تنبيه التوثيق' : 'Auth Warning',
          message: result['message'] ??
              (_isArabic
                  ? 'تعذر توثيق حساب جوجل مع السيرفر'
                  : 'Google account verification failed on server'),
          isSuccess: false,
        );
      }
    } catch (e) {
      debugPrint('❌ [Google Sign-In Error]: $e');
      if (!mounted) return;
      setState(() => _isLoading = false);

      _showProfessionalNotification(
        title: _isArabic ? 'خطأ في الاتصال' : 'Connection Error',
        message: _isArabic
            ? 'حدث خطأ أثناء الاتصال بخدمة Google'
            : 'An error occurred connecting to Google service',
        isSuccess: false,
      );
    }
  }

  /// مزامنة FCM Token عبر المالك المركزي لخدمة الإشعارات بعد نجاح الدخول.
  Future<void> _updateFcmToken() async {
    try {
      await NotificationService.syncCurrentToken(force: true);
    } catch (e) {
      debugPrint('⚠️ تعذرت مزامنة رمز الإشعارات بعد تسجيل الدخول: $e');
    }
  }

  InputDecoration _buildInputDecoration({
    required String hintText,
    required IconData prefixIcon,
    Widget? suffixIcon,
  }) {
    return InputDecoration(
      hintText: hintText,
      hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 14, fontFamily: 'Cairo'),
      prefixIcon: Icon(prefixIcon, color: AppColors.primaryTeal, size: 20),
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: AppColors.sectionBackground,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.lineColor),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.primaryTeal, width: 1.8),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.error),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.error, width: 1.8),
      ),
      errorStyle: const TextStyle(fontFamily: 'Cairo', color: AppColors.error),
    );
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundColor,
      body: Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: SafeArea(
          child: LayoutBuilder(
            builder: (context, constraints) {
              return SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 26),
                child: Center(
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 480),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Row(
                            children: [
                              Material(
                                color: AppColors.cardColor,
                                shape: const CircleBorder(),
                                child: IconButton(
                                  tooltip: _isArabic ? 'رجوع' : 'Back',
                                  onPressed: () {
                                    if (Navigator.canPop(context)) {
                                      Navigator.pop(context);
                                    }
                                  },
                                  icon: Icon(
                                    _isArabic
                                        ? Icons.arrow_forward_rounded
                                        : Icons.arrow_back_rounded,
                                    color: AppColors.primaryTeal,
                                  ),
                                ),
                              ),
                              const Spacer(),
                              Text(
                                _isArabic ? 'وصال' : 'Wesal',
                                style: const TextStyle(
                                  color: AppColors.primaryTeal,
                                  fontSize: 17,
                                  fontWeight: FontWeight.w900,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Icon(
                                Icons.favorite_rounded,
                                color: AppColors.accentWarm,
                                size: 18,
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),
                          Container(
                            padding: const EdgeInsets.fromLTRB(22, 22, 22, 24),
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                colors: [
                                  AppColors.primaryTeal,
                                  AppColors.secondaryTeal,
                                ],
                                begin: Alignment.topRight,
                                end: Alignment.bottomLeft,
                              ),
                              borderRadius: BorderRadius.circular(30),
                              boxShadow: [
                                BoxShadow(
                                  color: AppColors.primaryTeal
                                      .withValues(alpha: 0.18),
                                  blurRadius: 24,
                                  offset: const Offset(0, 12),
                                ),
                              ],
                            ),
                            child: Stack(
                              children: [
                                Positioned(
                                  top: -34,
                                  left: -24,
                                  child: Container(
                                    width: 118,
                                    height: 118,
                                    decoration: BoxDecoration(
                                      color: Colors.white
                                          .withValues(alpha: 0.07),
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      width: 58,
                                      height: 58,
                                      decoration: BoxDecoration(
                                        color: AppColors.backgroundColor,
                                        borderRadius:
                                            BorderRadius.circular(19),
                                      ),
                                      child: const Icon(
                                        Icons.favorite_rounded,
                                        color: AppColors.accentWarm,
                                        size: 32,
                                      ),
                                    ),
                                    const SizedBox(height: 18),
                                    Text(
                                      _isArabic
                                          ? 'أهلاً بعودتك إلى وصال'
                                          : 'Welcome back to Wesal',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 24,
                                        fontWeight: FontWeight.w900,
                                        height: 1.35,
                                        fontFamily: 'Cairo',
                                      ),
                                    ),
                                    const SizedBox(height: 7),
                                    Text(
                                      _isArabic
                                          ? 'سجّل دخولك وتابع رحلتك نحو تعارف أكثر وضوحاً وراحة.'
                                          : 'Sign in and continue your journey toward a meaningful connection.',
                                      style: TextStyle(
                                        color: Colors.white
                                            .withValues(alpha: 0.88),
                                        fontSize: 13,
                                        height: 1.7,
                                        fontFamily: 'Cairo',
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    Wrap(
                                      spacing: 8,
                                      runSpacing: 8,
                                      children: [
                                        _LoginFeatureChip(
                                          icon: Icons.shield_outlined,
                                          label: _isArabic
                                              ? 'خصوصيتك أولاً'
                                              : 'Privacy first',
                                        ),
                                        _LoginFeatureChip(
                                          icon: Icons.auto_awesome_rounded,
                                          label: _isArabic
                                              ? 'توافق أوضح'
                                              : 'Better compatibility',
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 18),
                          Container(
                            padding: const EdgeInsets.fromLTRB(20, 22, 20, 20),
                            decoration: BoxDecoration(
                              color: AppColors.cardColor,
                              borderRadius: BorderRadius.circular(28),
                              border: Border.all(color: AppColors.lineColor),
                              boxShadow: [
                                BoxShadow(
                                  color: AppColors.textDark
                                      .withValues(alpha: 0.055),
                                  blurRadius: 22,
                                  offset: const Offset(0, 8),
                                ),
                              ],
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                Text(
                                  _isArabic
                                      ? 'تسجيل الدخول'
                                      : 'Sign in to your account',
                                  style: const TextStyle(
                                    color: AppColors.textDark,
                                    fontSize: 21,
                                    fontWeight: FontWeight.w900,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                                const SizedBox(height: 5),
                                Text(
                                  _isArabic
                                      ? 'أدخل بياناتك للمتابعة.'
                                      : 'Enter your details to continue.',
                                  style: const TextStyle(
                                    color: AppColors.textMuted,
                                    fontSize: 12.5,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                                const SizedBox(height: 20),
                                Text(
                                  _isArabic ? 'البريد الإلكتروني' : 'Email address',
                                  style: const TextStyle(
                                    color: AppColors.textDark,
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                                const SizedBox(height: 7),
                                TextFormField(
                                  controller: _emailController,
                                  enabled: !_isLoading,
                                  keyboardType: TextInputType.emailAddress,
                                  textInputAction: TextInputAction.next,
                                  style: const TextStyle(
                                    color: AppColors.textDark,
                                    fontSize: 15,
                                    fontFamily: 'Cairo',
                                  ),
                                  inputFormatters: [
                                    ArabicToEnglishNumbersFormatter(),
                                    FilteringTextInputFormatter.deny(
                                      RegExp(r'\s'),
                                    ),
                                  ],
                                  decoration: _buildInputDecoration(
                                    hintText: _isArabic
                                        ? 'name@example.com'
                                        : 'name@example.com',
                                    prefixIcon: Icons.email_outlined,
                                  ),
                                  validator: (value) {
                                    if (value == null || value.trim().isEmpty) {
                                      return _isArabic
                                          ? 'يرجى إدخال البريد الإلكتروني'
                                          : 'Please enter your email';
                                    }
                                    final emailRegExp = RegExp(
                                      r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$',
                                    );
                                    if (!emailRegExp.hasMatch(value.trim())) {
                                      return _isArabic
                                          ? 'يرجى إدخال بريد إلكتروني صحيح'
                                          : 'Please enter a valid email address';
                                    }
                                    return null;
                                  },
                                ),
                                const SizedBox(height: 15),
                                Text(
                                  _isArabic ? 'كلمة المرور' : 'Password',
                                  style: const TextStyle(
                                    color: AppColors.textDark,
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                                const SizedBox(height: 7),
                                TextFormField(
                                  controller: _passwordController,
                                  enabled: !_isLoading,
                                  obscureText: _obscurePassword,
                                  textInputAction: TextInputAction.done,
                                  onFieldSubmitted: (_) {
                                    if (!_isLoading) _handleLogin();
                                  },
                                  style: const TextStyle(
                                    color: AppColors.textDark,
                                    fontSize: 15,
                                    fontFamily: 'Cairo',
                                  ),
                                  inputFormatters: [
                                    ArabicToEnglishNumbersFormatter(),
                                  ],
                                  decoration: _buildInputDecoration(
                                    hintText: _isArabic
                                        ? 'أدخل كلمة المرور'
                                        : 'Enter your password',
                                    prefixIcon: Icons.lock_outline_rounded,
                                    suffixIcon: IconButton(
                                      tooltip: _isArabic
                                          ? 'إظهار أو إخفاء كلمة المرور'
                                          : 'Show or hide password',
                                      icon: Icon(
                                        _obscurePassword
                                            ? Icons.visibility_off_outlined
                                            : Icons.visibility_outlined,
                                        color: AppColors.textMuted,
                                      ),
                                      onPressed: () => setState(
                                        () => _obscurePassword =
                                            !_obscurePassword,
                                      ),
                                    ),
                                  ),
                                  validator: (value) =>
                                      value == null || value.trim().isEmpty
                                          ? (_isArabic
                                              ? 'يرجى إدخال كلمة المرور'
                                              : 'Please enter your password')
                                          : null,
                                ),
                                const SizedBox(height: 12),
                                Wrap(
                                  alignment: WrapAlignment.spaceBetween,
                                  crossAxisAlignment: WrapCrossAlignment.center,
                                  spacing: 12,
                                  runSpacing: 4,
                                  children: [
                                    Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Checkbox(
                                          value: _rememberMe,
                                          activeColor: AppColors.primaryTeal,
                                          checkColor: Colors.white,
                                          side: const BorderSide(
                                            color: AppColors.textLight,
                                          ),
                                          shape: RoundedRectangleBorder(
                                            borderRadius:
                                                BorderRadius.circular(5),
                                          ),
                                          onChanged: (value) => setState(
                                            () => _rememberMe = value ?? false,
                                          ),
                                        ),
                                        Text(
                                          _isArabic ? 'تذكرني' : 'Remember me',
                                          style: const TextStyle(
                                            color: AppColors.textDark,
                                            fontSize: 12.5,
                                            fontFamily: 'Cairo',
                                          ),
                                        ),
                                      ],
                                    ),
                                    TextButton(
                                      onPressed: () {},
                                      style: TextButton.styleFrom(
                                        foregroundColor: AppColors.primaryTeal,
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 4, vertical: 8),
                                      ),
                                      child: Text(
                                        _isArabic
                                            ? 'نسيت كلمة المرور؟'
                                            : 'Forgot password?',
                                        style: const TextStyle(
                                          color: AppColors.primaryTeal,
                                          fontSize: 12.5,
                                          fontWeight: FontWeight.bold,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                SizedBox(
                                  height: 54,
                                  child: ElevatedButton(
                                    onPressed:
                                        _isLoading ? null : _handleLogin,
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppColors.primaryTeal,
                                      foregroundColor: Colors.white,
                                      elevation: 0,
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(17),
                                      ),
                                    ),
                                    child: _isLoading
                                        ? const SizedBox(
                                            width: 22,
                                            height: 22,
                                            child: CircularProgressIndicator(
                                              color: Colors.white,
                                              strokeWidth: 2.4,
                                            ),
                                          )
                                        : Row(
                                            mainAxisAlignment:
                                                MainAxisAlignment.center,
                                            children: [
                                              const Icon(
                                                Icons.login_rounded,
                                                size: 20,
                                              ),
                                              const SizedBox(width: 9),
                                              Text(
                                                _isArabic
                                                    ? 'الدخول إلى حسابي'
                                                    : 'Sign in',
                                                style: const TextStyle(
                                                  fontSize: 15,
                                                  fontWeight: FontWeight.bold,
                                                  fontFamily: 'Cairo',
                                                ),
                                              ),
                                            ],
                                          ),
                                  ),
                                ),
                                const SizedBox(height: 14),
                                Row(
                                  children: [
                                    const Expanded(
                                      child: Divider(
                                        color: AppColors.lineColor,
                                        thickness: 1,
                                      ),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 12),
                                      child: Text(
                                        _isArabic
                                            ? 'أو تابع باستخدام'
                                            : 'Or continue with',
                                        style: const TextStyle(
                                          color: AppColors.textMuted,
                                          fontSize: 11.5,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                    ),
                                    const Expanded(
                                      child: Divider(
                                        color: AppColors.lineColor,
                                        thickness: 1,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                SizedBox(
                                  height: 50,
                                  child: OutlinedButton(
                                    onPressed: _isLoading
                                        ? null
                                        : _handleGoogleSignIn,
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: AppColors.textDark,
                                      backgroundColor: AppColors.cardColor,
                                      side: const BorderSide(
                                          color: AppColors.lineColor),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(16),
                                      ),
                                    ),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      children: [
                                        const Icon(
                                          Icons.g_mobiledata_rounded,
                                          size: 30,
                                          color: Colors.redAccent,
                                        ),
                                        const SizedBox(width: 7),
                                        Text(
                                          _isArabic
                                              ? 'المتابعة باستخدام Google'
                                              : 'Continue with Google',
                                          style: const TextStyle(
                                            color: AppColors.textDark,
                                            fontSize: 13.5,
                                            fontWeight: FontWeight.bold,
                                            fontFamily: 'Cairo',
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 18),
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 16, vertical: 14),
                            decoration: BoxDecoration(
                              color: AppColors.accentLight
                                  .withValues(alpha: 0.20),
                              borderRadius: BorderRadius.circular(18),
                              border: Border.all(
                                color: AppColors.accentWarm
                                    .withValues(alpha: 0.25),
                              ),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Flexible(
                                  child: Text(
                                    _isArabic
                                        ? 'ليس لديك حساب بعد؟'
                                        : 'New to Wesal?',
                                    style: const TextStyle(
                                      color: AppColors.textDark,
                                      fontSize: 13,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                                ),
                                TextButton(
                                  onPressed: _isLoading
                                      ? null
                                      : () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(
                                              builder: (context) => SignupScreen(
                                                currentLanguage:
                                                    widget.currentLanguage,
                                              ),
                                            ),
                                          );
                                        },
                                  style: TextButton.styleFrom(
                                    foregroundColor: AppColors.primaryTeal,
                                  ),
                                  child: Text(
                                    _isArabic
                                        ? 'إنشاء حساب جديد'
                                        : 'Create an account',
                                    style: const TextStyle(
                                      color: AppColors.primaryTeal,
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
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}


class _LoginFeatureChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _LoginFeatureChip({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(30),
        border: Border.all(color: Colors.white.withValues(alpha: 0.20)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: AppColors.accentLight, size: 15),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 10.5,
              fontWeight: FontWeight.w700,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }
}

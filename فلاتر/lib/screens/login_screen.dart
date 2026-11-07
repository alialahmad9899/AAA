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
    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        elevation: 0,
        behavior: SnackBarBehavior.floating,
        backgroundColor: Colors.transparent,
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        duration: const Duration(seconds: 4),
        content: ClipRRect(
          borderRadius: BorderRadius.circular(20),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: isSuccess
                    ? const Color(0xFF00897B).withValues(alpha: 0.92)
                    : const Color(0xFFD32F2F).withValues(alpha: 0.92),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.40),
                  width: 1.2,
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.22),
                    blurRadius: 18,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.22),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      isSuccess
                          ? Icons.check_circle_rounded
                          : Icons.error_outline_rounded,
                      color: Colors.white,
                      size: 26,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          message,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.92),
                            fontSize: 13,
                            fontFamily: 'Cairo',
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
      ),
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
    const Color primaryTeal = AppColors.primaryTeal;

    return Scaffold(
      body: Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          width: double.infinity,
          height: double.infinity,
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [
                AppColors.backgroundColor,
                AppColors.sectionBackground,
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
          child: SafeArea(
            child: Column(
              children: [
                Align(
                  alignment: _isArabic ? Alignment.topRight : Alignment.topLeft,
                  child: IconButton(
                    icon: Icon(
                      _isArabic
                          ? Icons.arrow_back_ios_new_rounded
                          : Icons.arrow_back_rounded,
                      color: AppColors.textDark,
                    ),
                    onPressed: () {
                      if (Navigator.canPop(context)) {
                        Navigator.pop(context);
                      }
                    },
                  ),
                ),
                Expanded(
                  child: Center(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 22.0, vertical: 8.0),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          children: [
                            Container(
                              width: 76,
                              height: 76,
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(
                                  colors: [
                                    AppColors.primaryTeal,
                                    AppColors.secondaryTeal,
                                  ],
                                  begin: Alignment.topRight,
                                  end: Alignment.bottomLeft,
                                ),
                                borderRadius: BorderRadius.circular(25),
                                boxShadow: [
                                  BoxShadow(
                                    color: AppColors.primaryTeal
                                        .withValues(alpha: 0.18),
                                    blurRadius: 18,
                                    offset: const Offset(0, 8),
                                  ),
                                ],
                              ),
                              child: const Icon(
                                Icons.favorite_rounded,
                                color: AppColors.accentLight,
                                size: 38,
                              ),
                            ),
                            const SizedBox(height: 14),
                            Text(
                              _isArabic ? 'أهلاً بعودتك' : 'Welcome Back',
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                color: AppColors.primaryTeal,
                                fontSize: 24,
                                fontWeight: FontWeight.w900,
                                fontFamily: 'Cairo',
                              ),
                            ),
                            const SizedBox(height: 5),
                            Text(
                              _isArabic
                                  ? 'سجّل دخولك لمتابعة رحلتك والتواصل بثقة.'
                                  : 'Sign in to continue your journey with confidence.',
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                color: AppColors.textMuted,
                                fontSize: 12.5,
                                height: 1.5,
                                fontFamily: 'Cairo',
                              ),
                            ),
                            const SizedBox(height: 20),
                            ClipRRect(
                              borderRadius: BorderRadius.circular(28),
                              child: BackdropFilter(
                                filter:
                                    ImageFilter.blur(sigmaX: 16, sigmaY: 16),
                                child: Container(
                                  padding: const EdgeInsets.all(24.0),
                                  decoration: BoxDecoration(
                                    color: AppColors.cardColor,
                                    borderRadius: BorderRadius.circular(28),
                                    border: Border.all(
                                      color:
                                          AppColors.lineColor,
                                      width: 1.5,
                                    ),
                                    boxShadow: [
                                      BoxShadow(
                                        color: AppColors.textDark
                                            .withValues(alpha: 0.06),
                                        blurRadius: 25,
                                        offset: const Offset(0, 10),
                                      ),
                                    ],
                                  ),
                                  child: Column(
                                    children: [
                                      const SizedBox(height: 8),
                                      Text(
                                        _isArabic ? 'تسجيل الدخول' : 'Sign In',
                                        style: const TextStyle(
                                          fontSize: 24,
                                          color: AppColors.textDark,
                                          fontWeight: FontWeight.bold,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                      const SizedBox(height: 22),

                                      // حقل البريد الإلكتروني
                                      TextFormField(
                                        controller: _emailController,
                                        enabled: !_isLoading,
                                        keyboardType:
                                            TextInputType.emailAddress,
                                        style: const TextStyle(
                                          color: AppColors.textDark,
                                          fontSize: 16,
                                          fontFamily: 'Cairo',
                                        ),
                                        inputFormatters: [
                                          ArabicToEnglishNumbersFormatter(),
                                          FilteringTextInputFormatter.deny(
                                              RegExp(r'\s')),
                                        ],
                                        decoration: _buildInputDecoration(
                                          hintText: _isArabic
                                              ? 'البريد الإلكتروني'
                                              : 'Email Address',
                                          prefixIcon: Icons.email_outlined,
                                        ),
                                        validator: (v) {
                                          if (v == null || v.trim().isEmpty) {
                                            return _isArabic
                                                ? 'يرجى إدخال البريد الإلكتروني'
                                                : 'Please enter your email';
                                          }
                                          final emailRegExp = RegExp(
                                              r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
                                          if (!emailRegExp.hasMatch(v.trim())) {
                                            return _isArabic
                                                ? 'يرجى إدخال بريد إلكتروني صحيح'
                                                : 'Please enter a valid email address';
                                          }
                                          return null;
                                        },
                                      ),
                                      const SizedBox(height: 16),

                                      // حقل كلمة المرور
                                      TextFormField(
                                        controller: _passwordController,
                                        enabled: !_isLoading,
                                        obscureText: _obscurePassword,
                                        style: const TextStyle(
                                          color: AppColors.textDark,
                                          fontSize: 16,
                                          fontFamily: 'Cairo',
                                        ),
                                        inputFormatters: [
                                          ArabicToEnglishNumbersFormatter(),
                                        ],
                                        decoration: _buildInputDecoration(
                                          hintText: _isArabic
                                              ? 'كلمة المرور'
                                              : 'Password',
                                          prefixIcon: Icons.lock_outline,
                                          suffixIcon: IconButton(
                                            icon: Icon(
                                              _obscurePassword
                                                  ? Icons
                                                      .visibility_off_outlined
                                                  : Icons.visibility_outlined,
                                              color: AppColors.textMuted,
                                            ),
                                            onPressed: () => setState(() =>
                                                _obscurePassword =
                                                    !_obscurePassword),
                                          ),
                                        ),
                                        validator: (v) => v == null ||
                                                v.trim().isEmpty
                                            ? (_isArabic
                                                ? 'يرجى إدخال كلمة المرور'
                                                : 'Please enter your password')
                                            : null,
                                      ),
                                      const SizedBox(height: 20),

                                      // زر تسجيل الدخول
                                      SizedBox(
                                        width: double.infinity,
                                        height: 52,
                                        child: ElevatedButton(
                                          onPressed:
                                              _isLoading ? null : _handleLogin,
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: primaryTeal,
                                            foregroundColor: Colors.white,
                                            shape: RoundedRectangleBorder(
                                              borderRadius:
                                                  BorderRadius.circular(30),
                                            ),
                                            elevation: 4,
                                            shadowColor: Colors.black26,
                                          ),
                                          child: _isLoading
                                              ? const SizedBox(
                                                  width: 22,
                                                  height: 22,
                                                  child:
                                                      CircularProgressIndicator(
                                                    color: Colors.white,
                                                    strokeWidth: 2.5,
                                                  ),
                                                )
                                              : Text(
                                                  _isArabic
                                                      ? 'تسجيل الدخول'
                                                      : 'Sign In',
                                                  style: const TextStyle(
                                                    fontSize: 17,
                                                    fontWeight: FontWeight.bold,
                                                    fontFamily: 'Cairo',
                                                  ),
                                                ),
                                        ),
                                      ),
                                      const SizedBox(height: 16),

                                      Row(
                                        mainAxisAlignment:
                                            MainAxisAlignment.spaceBetween,
                                        children: [
                                          Row(
                                            children: [
                                              SizedBox(
                                                width: 20,
                                                height: 20,
                                                child: Checkbox(
                                                  value: _rememberMe,
                                                  activeColor: AppColors.primaryTeal,
                                                   checkColor: Colors.white,
                                                   side: const BorderSide(
                                                       color: AppColors.textLight),
                                                  onChanged: (v) => setState(
                                                      () => _rememberMe =
                                                          v ?? false),
                                                ),
                                              ),
                                              const SizedBox(width: 8),
                                              Text(
                                                _isArabic
                                                    ? 'تذكرني'
                                                    : 'Remember Me',
                                                style: const TextStyle(
                                                  color: AppColors.textDark,
                                                  fontSize: 14,
                                                  fontFamily: 'Cairo',
                                                ),
                                              ),
                                            ],
                                          ),
                                          GestureDetector(
                                            onTap: () {},
                                            child: Text(
                                              _isArabic
                                                  ? 'نسيت كلمة المرور؟'
                                                  : 'Forgot Password?',
                                              style: const TextStyle(
                                                color: AppColors.accentWarm,
                                                fontSize: 14,
                                                fontFamily: 'Cairo',
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 16),

                            // 🟢 زر الدخول الزجاجي عبر Google المربوط بالخدمة
                            ClipRRect(
                              borderRadius: BorderRadius.circular(24),
                              child: BackdropFilter(
                                filter:
                                    ImageFilter.blur(sigmaX: 16, sigmaY: 16),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 16.0, vertical: 12.0),
                                  width: double.infinity,
                                  decoration: BoxDecoration(
                                    color: AppColors.cardColor,
                                    borderRadius: BorderRadius.circular(24),
                                    border: Border.all(
                                      color:
                                          AppColors.lineColor,
                                      width: 1.5,
                                    ),
                                  ),
                                  child: SizedBox(
                                    width: double.infinity,
                                    height: 48,
                                    child: OutlinedButton(
                                      onPressed: _isLoading
                                          ? null
                                          : _handleGoogleSignIn,
                                      style: OutlinedButton.styleFrom(
                                        backgroundColor: Colors.white,
                                        side: const BorderSide(color: AppColors.lineColor),
                                        shape: RoundedRectangleBorder(
                                          borderRadius:
                                              BorderRadius.circular(30),
                                        ),
                                      ),
                                      child: Row(
                                        mainAxisAlignment:
                                            MainAxisAlignment.center,
                                        children: [
                                          const Icon(
                                            Icons.g_mobiledata_rounded,
                                            size: 34,
                                            color: Colors.redAccent,
                                          ),
                                          const SizedBox(width: 6),
                                          Expanded(
                                            child: FittedBox(
                                              fit: BoxFit.scaleDown,
                                              child: Text(
                                                _isArabic
                                                    ? 'تسجيل الدخول عن طريق جوجل'
                                                    : 'Sign in with Google',
                                                style: const TextStyle(
                                                  fontSize: 15,
                                                  fontWeight: FontWeight.bold,
                                                  color: primaryTeal,
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
                            ),

                            const SizedBox(height: 16),

                            // 🟢 رابط إنشاء حساب جديد للمستخدمين غير المسجلين
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  _isArabic
                                      ? 'ليس لديك حساب؟'
                                      : "Don't have an account?",
                                  style: const TextStyle(
                                    color: AppColors.textMuted,
                                    fontSize: 14,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                                TextButton(
                                  onPressed: _isLoading
                                      ? null
                                      : () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(
                                              builder: (context) =>
                                                  SignupScreen(
                                                currentLanguage:
                                                    widget.currentLanguage,
                                              ),
                                            ),
                                          );
                                        },
                                  child: Text(
                                    _isArabic ? 'أنشئ حساباً الآن' : 'Sign Up',
                                    style: const TextStyle(
                                      color: AppColors.textDark,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                      fontFamily: 'Cairo',
                                      decoration: TextDecoration.underline,
                                      decorationColor: AppColors.accentWarm,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

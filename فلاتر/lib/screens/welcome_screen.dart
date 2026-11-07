// ============================================================================
// اسم الملف: lib/screens/welcome_screen.dart
// المشروع: تطبيق وصال (Wesal)
// الوصف: شاشة ترحيبية بهوية بصرية دافئة ومتناسقة مع تصميم التطبيق.
//        يحافظ الفحص المبدئي على تسجيل الدخول التلقائي الآمن.
// ============================================================================

import 'package:flutter/material.dart';
import '../constants/colors.dart';
import '../services/api_service.dart';
import 'login_screen.dart';
import 'main_home_screen.dart';
import 'signup_screen.dart';

class WelcomeScreen extends StatefulWidget {
  final String currentLanguage;

  const WelcomeScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<WelcomeScreen> createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends State<WelcomeScreen> {
  bool _isCheckingAuth = true;

  bool get _isAr => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _checkSecureSession();
  }

  Future<void> _checkSecureSession() async {
    try {
      final String? token = await ApiService.getToken();
      if (!mounted) return;

      if (token != null && token.trim().isNotEmpty) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => const MainHomeScreen()),
        );
        return;
      }

      setState(() => _isCheckingAuth = false);
    } catch (error) {
      debugPrint('تعذر التحقق من جلسة وصال: $error');
      if (mounted) setState(() => _isCheckingAuth = false);
    }
  }

  void _openLogin() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) =>
            LoginScreen(currentLanguage: widget.currentLanguage),
      ),
    );
  }

  void _openSignup() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) =>
            SignupScreen(currentLanguage: widget.currentLanguage),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundColor,
      body: Directionality(
        textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Stack(
          children: [
            Positioned(
              top: -94,
              left: _isAr ? null : -72,
              right: _isAr ? -72 : null,
              child: Container(
                width: 230,
                height: 230,
                decoration: BoxDecoration(
                  color: AppColors.accentLight.withValues(alpha: 0.20),
                  shape: BoxShape.circle,
                ),
              ),
            ),
            SafeArea(
              child: _isCheckingAuth
                  ? const Center(
                      child: CircularProgressIndicator(
                        color: AppColors.accentWarm,
                        strokeWidth: 3,
                      ),
                    )
                  : LayoutBuilder(
                      builder: (context, constraints) {
                        return SingleChildScrollView(
                          physics: const BouncingScrollPhysics(),
                          padding: const EdgeInsets.fromLTRB(20, 14, 20, 22),
                          child: ConstrainedBox(
                            constraints: BoxConstraints(
                              minHeight: constraints.maxHeight - 36,
                            ),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.fromLTRB(
                                      24, 30, 24, 28),
                                  decoration: BoxDecoration(
                                    gradient: const LinearGradient(
                                      colors: [
                                        AppColors.primaryTeal,
                                        AppColors.secondaryTeal,
                                      ],
                                      begin: Alignment.topRight,
                                      end: Alignment.bottomLeft,
                                    ),
                                    borderRadius: BorderRadius.circular(34),
                                    boxShadow: [
                                      BoxShadow(
                                        color: AppColors.primaryTeal
                                            .withValues(alpha: 0.18),
                                        blurRadius: 28,
                                        offset: const Offset(0, 14),
                                      ),
                                    ],
                                  ),
                                  child: Column(
                                    children: [
                                      Stack(
                                        alignment: Alignment.center,
                                        children: [
                                          Container(
                                            width: 112,
                                            height: 112,
                                            decoration: BoxDecoration(
                                              color: Colors.white
                                                  .withValues(alpha: 0.10),
                                              shape: BoxShape.circle,
                                              border: Border.all(
                                                color: Colors.white
                                                    .withValues(alpha: 0.24),
                                                width: 1.2,
                                              ),
                                            ),
                                          ),
                                          Container(
                                            width: 82,
                                            height: 82,
                                            decoration: BoxDecoration(
                                              color: AppColors.backgroundColor,
                                              borderRadius:
                                                  BorderRadius.circular(28),
                                              boxShadow: [
                                                BoxShadow(
                                                  color: Colors.black
                                                      .withValues(alpha: 0.12),
                                                  blurRadius: 18,
                                                  offset: const Offset(0, 8),
                                                ),
                                              ],
                                            ),
                                            child: const Icon(
                                              Icons.favorite_rounded,
                                              color: AppColors.accentWarm,
                                              size: 43,
                                            ),
                                          ),
                                          Positioned(
                                            top: 7,
                                            right: 7,
                                            child: Icon(
                                              Icons.auto_awesome_rounded,
                                              color: AppColors.accentLight,
                                              size: 22,
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 20),
                                      Text(
                                        _isAr ? 'وصال' : 'Wesal',
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 34,
                                          fontWeight: FontWeight.w900,
                                          letterSpacing: 0.4,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                      const SizedBox(height: 8),
                                      Text(
                                        _isAr
                                            ? 'كل لقاء جميل يبدأ بخطوة'
                                            : 'Every meaningful connection starts with a step',
                                        textAlign: TextAlign.center,
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 17,
                                          fontWeight: FontWeight.bold,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                      const SizedBox(height: 8),
                                      Text(
                                        _isAr
                                            ? 'مساحتك الآمنة للتعارف والتوافق وبناء علاقة قائمة على الوضوح والاحترام.'
                                            : 'A thoughtful space for meaningful connections, compatibility, and trust.',
                                        textAlign: TextAlign.center,
                                        style: TextStyle(
                                          color: Colors.white
                                              .withValues(alpha: 0.86),
                                          fontSize: 13,
                                          height: 1.7,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                      const SizedBox(height: 20),
                                      Wrap(
                                        alignment: WrapAlignment.center,
                                        spacing: 8,
                                        runSpacing: 8,
                                        children: [
                                          _WelcomeFeature(
                                            icon: Icons.shield_outlined,
                                            label: _isAr
                                                ? 'خصوصية'
                                                : 'Privacy',
                                          ),
                                          _WelcomeFeature(
                                            icon: Icons.auto_awesome_rounded,
                                            label: _isAr
                                                ? 'توافق'
                                                : 'Compatibility',
                                          ),
                                          _WelcomeFeature(
                                            icon: Icons.favorite_border_rounded,
                                            label: _isAr
                                                ? 'احترام'
                                                : 'Respect',
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 18),
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.fromLTRB(
                                      20, 22, 20, 18),
                                  decoration: BoxDecoration(
                                    color: AppColors.cardColor,
                                    borderRadius: BorderRadius.circular(28),
                                    border: Border.all(
                                      color: AppColors.lineColor,
                                    ),
                                    boxShadow: [
                                      BoxShadow(
                                        color: AppColors.textDark
                                            .withValues(alpha: 0.06),
                                        blurRadius: 24,
                                        offset: const Offset(0, 8),
                                      ),
                                    ],
                                  ),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.stretch,
                                    children: [
                                      Text(
                                        _isAr
                                            ? 'ابدأ رحلتك مع وصال'
                                            : 'Begin your Wesal journey',
                                        textAlign: TextAlign.center,
                                        style: const TextStyle(
                                          color: AppColors.textDark,
                                          fontSize: 20,
                                          fontWeight: FontWeight.bold,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                      const SizedBox(height: 6),
                                      Text(
                                        _isAr
                                            ? 'سجّل دخولك أو أنشئ حسابك بخطوات بسيطة.'
                                            : 'Sign in or create your account in a few simple steps.',
                                        textAlign: TextAlign.center,
                                        style: const TextStyle(
                                          color: AppColors.textMuted,
                                          fontSize: 12.5,
                                          height: 1.5,
                                          fontFamily: 'Cairo',
                                        ),
                                      ),
                                      const SizedBox(height: 20),
                                      SizedBox(
                                        height: 54,
                                        child: ElevatedButton.icon(
                                          onPressed: _openLogin,
                                          icon: const Icon(
                                            Icons.login_rounded,
                                            size: 20,
                                          ),
                                          label: Text(
                                            _isAr
                                                ? 'تسجيل الدخول'
                                                : 'Sign In',
                                            style: const TextStyle(
                                              fontSize: 16,
                                              fontWeight: FontWeight.bold,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor:
                                                AppColors.primaryTeal,
                                            foregroundColor: Colors.white,
                                            elevation: 0,
                                            shape: RoundedRectangleBorder(
                                              borderRadius:
                                                  BorderRadius.circular(17),
                                            ),
                                          ),
                                        ),
                                      ),
                                      const SizedBox(height: 12),
                                      SizedBox(
                                        height: 54,
                                        child: OutlinedButton.icon(
                                          onPressed: _openSignup,
                                          icon: const Icon(
                                            Icons.person_add_alt_1_rounded,
                                            size: 20,
                                          ),
                                          label: Text(
                                            _isAr
                                                ? 'إنشاء حساب جديد'
                                                : 'Create New Account',
                                            style: const TextStyle(
                                              fontSize: 15,
                                              fontWeight: FontWeight.bold,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                          style: OutlinedButton.styleFrom(
                                            foregroundColor:
                                                AppColors.primaryTeal,
                                            backgroundColor:
                                                AppColors.sectionBackground,
                                            side: const BorderSide(
                                              color: AppColors.lineColor,
                                            ),
                                            shape: RoundedRectangleBorder(
                                              borderRadius:
                                                  BorderRadius.circular(17),
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 16),
                                Text(
                                  _isAr
                                      ? 'وصال • تواصل يبدأ بالثقة'
                                      : 'Wesal • Connections begin with trust',
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: AppColors.textMuted,
                                    fontSize: 11.5,
                                    fontFamily: 'Cairo',
                                    letterSpacing: 0.2,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _WelcomeFeature extends StatelessWidget {
  final IconData icon;
  final String label;

  const _WelcomeFeature({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(30),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.20),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: AppColors.accentLight, size: 15),
          const SizedBox(width: 5),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 11,
              fontWeight: FontWeight.w600,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }
}

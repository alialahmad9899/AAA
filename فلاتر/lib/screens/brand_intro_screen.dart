import 'dart:async';

import 'package:flutter/material.dart';

import '../constants/colors.dart';
import '../services/api_service.dart';
import '../utils/wesal_page_route.dart';
import '../widgets/wesal_logo.dart';
import 'main_home_screen.dart';
import 'welcome_screen.dart';

/// First-frame branded intro for signed-out users.
///
/// Session lookup runs while the logo animation is visible, so the app does
/// not wait on secure storage before painting its first frame.
class BrandIntroScreen extends StatefulWidget {
  final bool checkSession;
  final String currentLanguage;

  const BrandIntroScreen({
    super.key,
    this.checkSession = true,
    this.currentLanguage = 'ar',
  });

  @override
  State<BrandIntroScreen> createState() => _BrandIntroScreenState();
}

class _BrandIntroScreenState extends State<BrandIntroScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _logoOpacity;
  late final Animation<double> _logoScale;
  late final Animation<double> _copyOpacity;
  late final Animation<Offset> _copyOffset;

  Timer? _introTimer;
  bool _sessionResolved = false;
  bool _introElapsed = false;
  bool _navigationStarted = false;

  @override
  void initState() {
    super.initState();

    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1900),
    );
    _logoOpacity = CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.0, 0.72, curve: Curves.easeOut),
    );
    _logoScale = Tween<double>(begin: 0.94, end: 1).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.78, curve: Curves.easeOutCubic),
      ),
    );
    _copyOpacity = CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.40, 1.0, curve: Curves.easeOut),
    );
    _copyOffset = Tween<Offset>(
      begin: const Offset(0, 0.10),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.40, 1.0, curve: Curves.easeOutCubic),
      ),
    );

    _controller.forward();
    _introTimer = Timer(const Duration(milliseconds: 2600), () {
      _introElapsed = true;
      if (!widget.checkSession || _sessionResolved) {
        _openWelcome();
      }
    });

    if (widget.checkSession) {
      unawaited(_resolveSavedSession());
    } else {
      _sessionResolved = true;
    }
  }

  Future<void> _resolveSavedSession() async {
    final isValidSession = await ApiService.validateCurrentSession();
    if (!mounted || _navigationStarted) return;

    _sessionResolved = true;
    if (isValidSession) {
      _introTimer?.cancel();
      _openMainHome();
    } else if (_introElapsed) {
      _openWelcome();
    }
  }

  void _openWelcome() {
    _navigateTo(
      WelcomeScreen(
        currentLanguage: widget.currentLanguage,
        checkSession: false,
      ),
    );
  }

  void _openMainHome() {
    _navigateTo(const MainHomeScreen());
  }

  void _navigateTo(Widget page) {
    if (!mounted || _navigationStarted) return;
    _navigationStarted = true;
    Navigator.of(context).pushReplacement(WesalPageRoute(child: page));
  }

  @override
  void dispose() {
    _introTimer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primaryTeal,
      body: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [
              AppColors.primaryTeal,
              AppColors.secondaryTeal,
              AppColors.primaryTeal,
            ],
            begin: Alignment.topRight,
            end: Alignment.bottomLeft,
          ),
        ),
        child: Stack(
          children: [
            Positioned(
              top: -112,
              right: -94,
              child: Container(
                width: 290,
                height: 290,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      AppColors.accentWarm.withValues(alpha: 0.20),
                      AppColors.accentWarm.withValues(alpha: 0),
                    ],
                  ),
                ),
              ),
            ),
            Positioned(
              bottom: -142,
              left: -112,
              child: Container(
                width: 320,
                height: 320,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      AppColors.accentLight.withValues(alpha: 0.16),
                      AppColors.accentLight.withValues(alpha: 0),
                    ],
                  ),
                ),
              ),
            ),
            SafeArea(
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      LayoutBuilder(
                        builder: (context, constraints) {
                          final double logoWidth = (constraints.maxWidth)
                              .clamp(0.0, 280.0)
                              .toDouble();
                          return FadeTransition(
                            opacity: _logoOpacity,
                            child: ScaleTransition(
                              scale: _logoScale,
                              child: WesalLogo(
                                key: const ValueKey('brand-intro-logo'),
                                width: logoWidth,
                                height: logoWidth * 0.54,
                                fit: BoxFit.contain,
                                excludeSemantics: true,
                              ),
                            ),
                          );
                        },
                      ),
                      const SizedBox(height: 30),
                      FadeTransition(
                        opacity: _copyOpacity,
                        child: SlideTransition(
                          position: _copyOffset,
                          child: Column(
                            children: [
                              Container(
                                width: 42,
                                height: 3,
                                decoration: BoxDecoration(
                                  color: AppColors.accentWarm,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                              ),
                              const SizedBox(height: 18),
                              Text(
                                widget.currentLanguage == 'ar'
                                    ? 'تواصل يبدأ بالثقة'
                                    : 'Connections begin with trust',
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontFamily: 'Cairo',
                                  fontSize: 19,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.2,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                widget.currentLanguage == 'ar'
                                    ? 'أهلاً بك في وصال'
                                    : 'Welcome to Wesal',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.78),
                                  fontFamily: 'Cairo',
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

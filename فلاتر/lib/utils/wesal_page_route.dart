import 'package:flutter/material.dart';

/// A restrained fade-and-rise transition used by Wesal's signed-out entry flow.
class WesalPageRoute<T> extends PageRouteBuilder<T> {
  WesalPageRoute({required Widget child})
      : super(
          pageBuilder: (context, animation, secondaryAnimation) => child,
          transitionDuration: const Duration(milliseconds: 520),
          reverseTransitionDuration: const Duration(milliseconds: 360),
          transitionsBuilder: (context, animation, secondaryAnimation, child) {
            final curved = CurvedAnimation(
              parent: animation,
              curve: Curves.easeOutCubic,
              reverseCurve: Curves.easeInCubic,
            );
            final position = Tween<Offset>(
              begin: const Offset(0, 0.02),
              end: Offset.zero,
            ).animate(curved);

            return FadeTransition(
              opacity: curved,
              child: SlideTransition(position: position, child: child),
            );
          },
        );
}

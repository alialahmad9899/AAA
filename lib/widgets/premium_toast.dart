// ============================================================================
// اسم الملف: lib/widgets/premium_toast.dart
// الوصف: نظام إشعارات زجاجي عائم (Modern Premium Glassmorphism Toast 2026)
//        مزود بهيكل آمن لمنع استثناءات Context وOverlay
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';

class PremiumToast {
  /// إظهار إشعار عائم زجاجي آمن ومتحرك
  static void show(
    BuildContext context, {
    required String message,
    bool isSuccess = true,
    Duration duration = const Duration(seconds: 3),
  }) {
    // 🛡️ 1. فحص أمان مرجع BuildContext قبل التعامل مع الشجرة
    if (!context.mounted) return;

    // 🛡️ 2. استدعاء Overlay المباشر بشكل آمن لمنع رمي استثناء
    final overlay = Overlay.maybeOf(context);
    if (overlay == null) return;

    late OverlayEntry overlayEntry;

    overlayEntry = OverlayEntry(
      builder: (context) => Positioned(
        top: MediaQuery.paddingOf(context).top + 12,
        left: 20,
        right: 20,
        child: Material(
          color: Colors.transparent,
          child: TweenAnimationBuilder<double>(
            duration: const Duration(milliseconds: 400),
            curve: Curves.easeOutBack,
            tween: Tween(begin: 0.0, end: 1.0),
            builder: (context, value, child) {
              return Transform.scale(
                scale: value,
                child: Opacity(
                  opacity: value.clamp(0.0, 1.0),
                  child: child,
                ),
              );
            },
            child: GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTap: () {
                if (overlayEntry.mounted) {
                  overlayEntry.remove();
                }
              },
              child: ClipRRect(
                borderRadius: BorderRadius.circular(20),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 14),
                    decoration: BoxDecoration(
                      color: isSuccess
                          ? const Color(0xFF064E3B).withValues(alpha: 0.85)
                          : const Color(0xFF7F1D1D).withValues(alpha: 0.85),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: isSuccess
                            ? const Color(0xFF34D399).withValues(alpha: 0.5)
                            : const Color(0xFFF87171).withValues(alpha: 0.5),
                        width: 1.2,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.25),
                          blurRadius: 20,
                          offset: const Offset(0, 8),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: isSuccess
                                ? const Color(0xFF10B981).withValues(alpha: 0.2)
                                : const Color(0xFFEF4444)
                                    .withValues(alpha: 0.2),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            isSuccess
                                ? Icons.check_circle_rounded
                                : Icons.error_rounded,
                            color: isSuccess
                                ? const Color(0xFF34D399)
                                : const Color(0xFFF87171),
                            size: 22,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            message,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
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
        ),
      ),
    );

    overlay.insert(overlayEntry);

    // 🛡️ 3. إزالة الإشعار بعد انتهاء المدة الزمنية مع التأكد من بقاء العنصر مثبتًا (mounted)
    Future.delayed(duration, () {
      if (overlayEntry.mounted) {
        overlayEntry.remove();
      }
    });
  }
}

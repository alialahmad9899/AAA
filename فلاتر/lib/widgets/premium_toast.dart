// ============================================================================
// توافق خلفي: يوجّه الإشعارات القديمة إلى مكوّن AppSnackBar الموحد.
// ============================================================================

import 'package:flutter/material.dart';

import 'app_snackbar.dart';

class PremiumToast {
  static void show(
    BuildContext context, {
    required String message,
    bool isSuccess = true,
    Duration duration = const Duration(seconds: 3),
  }) {
    if (!context.mounted) return;
    final bool isArabic =
        Directionality.maybeOf(context) != TextDirection.ltr;

    AppSnackBar.show(
      context: context,
      type: isSuccess ? NotificationType.success : NotificationType.error,
      isArabic: isArabic,
      titleAr: isSuccess ? 'تم بنجاح' : 'تعذّر إكمال العملية',
      titleEn: isSuccess ? 'Completed' : 'Action could not be completed',
      messageAr: message,
      messageEn: message,
      duration: duration,
    );
  }
}

import 'package:flutter/material.dart';

import 'package:url_launcher/url_launcher.dart';

import '../widgets/app_snackbar.dart';

/// Canonical Wesal legal-document destinations used throughout the mobile app.
class LegalDocumentLinks {
  LegalDocumentLinks._();

  static const String privacyPolicyUrl =
      'https://www.wesalsyria.com/privacy-policy';
  static const String termsOfUseUrl =
      'https://www.wesalsyria.com/terms-conditions';

  static Future<void> openPrivacyPolicy(
    BuildContext context, {
    required bool isArabic,
  }) {
    return _open(
      context,
      Uri.parse(privacyPolicyUrl),
      isArabic: isArabic,
      titleAr: 'تعذّر فتح سياسة الخصوصية',
      titleEn: 'Could not open the Privacy Policy',
    );
  }

  static Future<void> openTermsOfUse(
    BuildContext context, {
    required bool isArabic,
  }) {
    return _open(
      context,
      Uri.parse(termsOfUseUrl),
      isArabic: isArabic,
      titleAr: 'تعذّر فتح شروط الاستخدام',
      titleEn: 'Could not open the Terms of Use',
    );
  }

  static Future<void> _open(
    BuildContext context,
    Uri uri, {
    required bool isArabic,
    required String titleAr,
    required String titleEn,
  }) async {
    try {
      final bool opened = await launchUrl(
        uri,
        mode: LaunchMode.externalApplication,
      );
      if (!opened && context.mounted) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: isArabic,
          titleAr: titleAr,
          titleEn: titleEn,
          messageAr: 'لم نتمكن من فتح الصفحة المطلوبة.',
          messageEn: 'We could not open the requested page.',
          detailsAr: 'تحقق من اتصال الإنترنت ثم حاول مجدداً.',
          detailsEn: 'Check your internet connection and try again.',
        );
      }
    } catch (error, stackTrace) {
      debugPrint('Failed to open legal document ($uri): $error');
      debugPrintStack(stackTrace: stackTrace);
      if (context.mounted) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: isArabic,
          titleAr: titleAr,
          titleEn: titleEn,
          messageAr: 'لم نتمكن من فتح الصفحة المطلوبة.',
          messageEn: 'We could not open the requested page.',
          detailsAr: 'تحقق من اتصال الإنترنت ثم حاول مجدداً.',
          detailsEn: 'Check your internet connection and try again.',
        );
      }
    }
  }
}

// ============================================================================
// مكوّن موحّد لإشعارات وصال الداخلية السفلية
// ============================================================================

import 'dart:ui';

import 'package:flutter/material.dart';

import '../constants/colors.dart';
import 'bottom_system_inset.dart';

/// أنواع الإشعارات الداخلية التي تعرض نتيجة الإجراء أو ما يلزم المستخدم فعله.
enum NotificationType { success, error, info, warning }

class AppSnackBar {
  /// يعرض إشعاراً واحداً واضحاً ومتوافقاً مع RTL/LTR ومساحة النظام السفلية.
  static void show({
    required BuildContext context,
    required String messageAr,
    required String messageEn,
    NotificationType type = NotificationType.success,
    bool isArabic = true,
    String? titleAr,
    String? titleEn,
    String? detailsAr,
    String? detailsEn,
    Duration duration = const Duration(seconds: 4),
  }) {
    if (!context.mounted) return;

    final messenger = ScaffoldMessenger.maybeOf(context);
    if (messenger == null) return;

    late final Color accentColor;
    late final IconData iconData;
    late final String defaultTitleAr;
    late final String defaultTitleEn;

    switch (type) {
      case NotificationType.success:
        accentColor = AppColors.success;
        iconData = Icons.check_circle_rounded;
        defaultTitleAr = 'تم بنجاح';
        defaultTitleEn = 'Completed';
        break;
      case NotificationType.error:
        accentColor = AppColors.error;
        iconData = Icons.error_rounded;
        defaultTitleAr = 'تعذّر إكمال العملية';
        defaultTitleEn = 'Action could not be completed';
        break;
      case NotificationType.warning:
        accentColor = AppColors.warning;
        iconData = Icons.warning_rounded;
        defaultTitleAr = 'يرجى الانتباه';
        defaultTitleEn = 'Please note';
        break;
      case NotificationType.info:
        accentColor = AppColors.primaryTeal;
        iconData = Icons.info_rounded;
        defaultTitleAr = 'معلومة';
        defaultTitleEn = 'Information';
        break;
    }

    final String displayTitle = isArabic
        ? (titleAr ?? defaultTitleAr)
        : (titleEn ?? defaultTitleEn);
    final String displayMessage = isArabic ? messageAr : messageEn;
    final String displayDetails = isArabic ? (detailsAr ?? '') : (detailsEn ?? '');
    final bool hasDetails = displayDetails.trim().isNotEmpty;

    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(
      SnackBar(
        elevation: 0,
        padding: EdgeInsets.zero,
        backgroundColor: Colors.transparent,
        duration: duration,
        behavior: SnackBarBehavior.floating,
        margin: EdgeInsets.only(
          left: 16,
          right: 16,
          top: 4,
          bottom: WesalBottomInsets.of(context) + 12,
        ),
        content: Directionality(
          textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
          child: Semantics(
            liveRegion: true,
            container: true,
            label: [displayTitle, displayMessage, displayDetails]
                .where((part) => part.trim().isNotEmpty)
                .join('. '),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(20),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [AppColors.cardColor, AppColors.sectionBackground],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: accentColor.withValues(alpha: 0.42),
                      width: 1.4,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: accentColor.withValues(alpha: 0.14),
                        blurRadius: 16,
                        offset: const Offset(0, 5),
                      ),
                    ],
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: accentColor.withValues(alpha: 0.11),
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: accentColor.withValues(alpha: 0.32),
                          ),
                        ),
                        child: Icon(iconData, color: accentColor, size: 22),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              displayTitle,
                              style: const TextStyle(
                                color: AppColors.textDark,
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                                height: 1.25,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 3),
                            Text(
                              displayMessage,
                              style: const TextStyle(
                                color: AppColors.textDark,
                                fontSize: 12.5,
                                fontWeight: FontWeight.w600,
                                fontFamily: 'Cairo',
                                height: 1.4,
                              ),
                              maxLines: hasDetails ? 2 : 3,
                              overflow: TextOverflow.ellipsis,
                            ),
                            if (hasDetails) ...[
                              const SizedBox(height: 3),
                              Text(
                                displayDetails,
                                style: const TextStyle(
                                  color: AppColors.textMuted,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w500,
                                  fontFamily: 'Cairo',
                                  height: 1.35,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(width: 4),
                      IconButton(
                        tooltip: isArabic ? 'إغلاق الإشعار' : 'Dismiss notice',
                        visualDensity: VisualDensity.compact,
                        constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                        padding: const EdgeInsets.all(5),
                        onPressed: messenger.hideCurrentSnackBar,
                        icon: const Icon(
                          Icons.close_rounded,
                          color: AppColors.textMuted,
                          size: 18,
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
    );
  }

  static void showDeleted({
    required BuildContext context,
    bool isArabic = true,
  }) {
    show(
      context: context,
      type: NotificationType.success,
      isArabic: isArabic,
      titleAr: 'تم حذف الرسالة',
      titleEn: 'Message deleted',
      messageAr: 'حُذفت الرسالة من المحادثة بنجاح.',
      messageEn: 'The message was successfully removed from the conversation.',
    );
  }

  static void showUpdated({
    required BuildContext context,
    bool isArabic = true,
  }) {
    show(
      context: context,
      type: NotificationType.success,
      isArabic: isArabic,
      titleAr: 'تم تحديث البيانات',
      titleEn: 'Information updated',
      messageAr: 'حُفظت التعديلات وأصبحت معلوماتك محدثة.',
      messageEn: 'Your changes have been saved and your information is up to date.',
    );
  }
}

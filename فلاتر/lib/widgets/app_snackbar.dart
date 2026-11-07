// ============================================================================
// اسم الملف: lib/widgets/app_snackbar.dart
// الوصف: ويدجت إشعارات زجاجي عصري بتصميم Modern Premium UI مع حماية كاملة للـ Context
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import '../constants/colors.dart';

/// أنواع الإشعارات التفاعلية المتوفرة في التطبيق
enum NotificationType { success, error, info, warning }

/// ويدجت مستقل لتقديم إشعارات زجاجية عصرية بتصميم Modern Premium UI
class AppSnackBar {
  /// الدالة الرئيسية لإظهار الإشعار السفلي
  static void show({
    required BuildContext context,
    required String messageAr,
    required String messageEn,
    NotificationType type = NotificationType.success,
    bool isArabic = true,
    Duration duration = const Duration(seconds: 3),
  }) {
    // 🛡️ 1. حماية ضد استدعاء الـ Context بعد التفكيك أو التبديل اللاتزامني
    if (!context.mounted) return;

    // 🛡️ 2. استخدام maybeOf للبحث الآمن عن ScaffoldMessenger لمنع الاستثناءات
    final messenger = ScaffoldMessenger.maybeOf(context);
    if (messenger == null) return;

    // تحديد الألوان والأيقونات بناءً على نوع الإشعار
    late Color primaryColor;
    late Color borderColor;
    late IconData iconData;
    late String defaultTitle;

    switch (type) {
      case NotificationType.success:
        primaryColor = AppColors.success; // أخضر زاهي أنيق
        borderColor = AppColors.success;
        iconData = Icons.check_circle_rounded;
        defaultTitle = isArabic ? 'تم بنجاح' : 'Success';
        break;
      case NotificationType.error:
        primaryColor = AppColors.error; // أحمر أنيق
        borderColor = AppColors.error;
        iconData = Icons.error_rounded;
        defaultTitle = isArabic ? 'حدث خطأ' : 'Error';
        break;
      case NotificationType.warning:
        primaryColor = AppColors.warning; // برتقالي تحذيري
        borderColor = AppColors.warning;
        iconData = Icons.warning_rounded;
        defaultTitle = isArabic ? 'تنبيه' : 'Warning';
        break;
      case NotificationType.info:
        primaryColor =
            AppColors.primaryTeal; // الأزرق السماوي المعتمد بتطبيق وصال
        borderColor = AppColors.secondaryTeal;
        iconData = Icons.info_rounded;
        defaultTitle = isArabic ? 'معلومة' : 'Info';
        break;
    }

    final String displayMessage = isArabic ? messageAr : messageEn;

    // إخفاء أي إشعار سابق لمنع التراكم
    messenger.hideCurrentSnackBar();

    // عرض الإشعار السفلي بتأثير Glassmorphism
    messenger.showSnackBar(
      SnackBar(
        elevation: 0,
        backgroundColor: Colors.transparent,
        duration: duration,
        behavior: SnackBarBehavior.floating,
        margin: const EdgeInsets.only(left: 16, right: 16, bottom: 24),
        content: Directionality(
          textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [AppColors.cardColor, AppColors.sectionBackground],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: borderColor.withValues(alpha: 0.4),
                    width: 1.5,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: primaryColor.withValues(alpha: 0.2),
                      blurRadius: 15,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    // دائرة الأيقونة
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: primaryColor.withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: primaryColor.withValues(alpha: 0.5),
                          width: 1,
                        ),
                      ),
                      child: Icon(
                        iconData,
                        color: primaryColor,
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),

                    // محتوى النص
                    Expanded(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            defaultTitle,
                            style: TextStyle(
                              color: primaryColor,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            displayMessage,
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 13,
                              fontWeight: FontWeight.w500,
                              fontFamily: 'Cairo',
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),

                    // زر الإغلاق مع توسيع منطقة اللمس للاستجابة السريعة
                    GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () {
                        messenger.hideCurrentSnackBar();
                      },
                      child: Padding(
                        padding: const EdgeInsets.all(8.0),
                        child: Icon(
                          Icons.close_rounded,
                          color: AppColors.textMuted,
                          size: 18,
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
    );
  }

  // =========================================================================
  // اختصارات سريعة لاستخدامها مباشرة داخل التطبيق
  // =========================================================================

  /// إشعار عند حذف رسالة
  static void showDeleted({
    required BuildContext context,
    bool isArabic = true,
  }) {
    show(
      context: context,
      type: NotificationType.success,
      isArabic: isArabic,
      messageAr: 'تم حذف الرسالة بنجاح',
      messageEn: 'Message deleted successfully',
    );
  }

  /// إشعار عند تحديث المعلومات أو البيانات
  static void showUpdated({
    required BuildContext context,
    bool isArabic = true,
  }) {
    show(
      context: context,
      type: NotificationType.success,
      isArabic: isArabic,
      messageAr: 'تم تحديث المعلومات بنجاح',
      messageEn: 'Information updated successfully',
    );
  }
}

// ============================================================================
// اسم الملف: lib/utils/profile_update_manager.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: مدير تحديث البيانات الموازي لمعالجة جميع طلبات الملف الشخصي
//        بدون أي تأخير في الواجهة، مع نافذة تحميل حديثة راقية.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import '../constants/colors.dart';
import '../services/api_service.dart';
import '../widgets/app_snackbar.dart';

/// كلاس مخصص لإدارة عمليات تحديث بيانات الملف الشخصي بالتوازي لسرعة فائقة
class ProfileUpdateManager {
  /// دالة تنفيذيّة لتحديث كافة الأجزاء المعدّلة في وقت واحد بدون تأخير
  static Future<bool> saveAllProfileData({
    required BuildContext context,
    required Map<String, dynamic>? basicInfoData,
    required Map<String, dynamic>? physicalData,
    required Map<String, dynamic>? educationData,
    required Map<String, dynamic>? careerData,
  }) async {
    // 1. تجميع قائمة بالمهام البرمجية التي سيتم إرسالها إلى السيرفر
    final List<Future<Map<String, dynamic>>> updateTasks = [];

    // إذا تم تعديل البيانات الأساسية، أضف المهمة للقائمة مع معالجة حقول null الآمنة
    if (basicInfoData != null) {
      updateTasks.add(ApiService.updateBasicInfo(
        firstName: (basicInfoData['first_name'] ?? '').toString(),
        lastName: (basicInfoData['last_name'] ?? '').toString(),
        phone: (basicInfoData['phone'] ?? '').toString(),
        gender: (basicInfoData['gender'] ?? '').toString(),
        dateOfBirth: (basicInfoData['date_of_birth'] ?? '').toString(),
        maritalStatus: (basicInfoData['marital_status'] ?? '').toString(),
        onBehalf: (basicInfoData['on_behalf'] ?? '1').toString(),
        children: (basicInfoData['children'] ?? '0').toString(),
        introduction: basicInfoData['introduction']?.toString(),
      ));
    }

    // إذا تم تعديل البيانات الجسدية، أضف المهمة للقائمة
    if (physicalData != null) {
      updateTasks.add(ApiService.updatePhysicalAttributes(physicalData));
    }

    // إذا تم تعديل البيانات التعليمية، أضف المهمة للقائمة
    if (educationData != null) {
      updateTasks.add(ApiService.updateEducation(educationData));
    }

    // إذا تم تعديل البيانات المهنية، أضف المهمة للقائمة
    if (careerData != null) {
      updateTasks.add(ApiService.updateCareer(careerData));
    }

    // إذا لم تكن هناك أي تعديلات للحفظ، نكتفي بالتنبيه والإنهاء دون فتح نافذة تحميل
    if (updateTasks.isEmpty) {
      if (context.mounted) {
        _showModernSnackBar(
          context: context,
          messageAr: 'لا توجد تعديلات جديدة لحفظها.',
          messageEn: 'There are no new changes to save.',
          type: NotificationType.info,
        );
      }
      return true;
    }

    // 2. عرض مؤشر التحميل العصري فوراً لتوفير تغذية راجعة بصرية للمستخدم
    _showPremiumLoadingDialog(context);

    try {
      // 3. التنفيذ الموازي لجميع الطلبات في وقت واحد لتقليل وقت الانتظار
      final List<Map<String, dynamic>> results = await Future.wait(updateTasks);

      // 4. إغلاق نافذة التحميل بأمان فور اكتمال معالجة الطلبات
      if (context.mounted) {
        final navigator = Navigator.of(context, rootNavigator: true);
        if (navigator.canPop()) {
          navigator.pop();
        }
      }

      // 5. التحقق من نجاح كافة العمليات
      final bool allSuccessful =
          results.isNotEmpty && results.every((res) => res['success'] == true);

      // 6. إظهار إشعار عصري بنتيجة العملية
      if (context.mounted) {
        _showModernSnackBar(
          context: context,
          messageAr: allSuccessful
              ? 'تم حفظ جميع تعديلات الملف الشخصي بنجاح.'
              : 'حُفظت بعض البيانات، لكن بقيت تعديلات لم تكتمل.',
          messageEn: allSuccessful
              ? 'All profile changes were saved successfully.'
              : 'Some profile data was saved, but a few changes remain incomplete.',
          type: allSuccessful
              ? NotificationType.success
              : NotificationType.warning,
        );
      }

      return allSuccessful;
    } catch (e) {
      // إغلاق نافذة التحميل عند حدوث أي خطأ في الاتصال
      if (context.mounted) {
        final navigator = Navigator.of(context, rootNavigator: true);
        if (navigator.canPop()) {
          navigator.pop();
        }
        _showModernSnackBar(
          context: context,
          messageAr: 'تعذّر إكمال حفظ بيانات الملف الشخصي.',
          messageEn: 'We could not finish saving your profile information.',
          type: NotificationType.error,
        );
      }
      return false;
    }
  }

  /// دالة لبناء نافذة تحميل عصرية بمظهر Modern Premium UI 2026 مع بلور خلفي
  static void _showPremiumLoadingDialog(BuildContext context) {
    showDialog(
      context: context,
      barrierDismissible: false, // منع الإغلاق بالنقر الخارجي أثناء التحميل
      builder: (BuildContext ctx) {
        return PopScope(
          canPop: false, // منع زر الرجوع أثناء معالجة البيانات
          child: BackdropFilter(
            filter:
                ImageFilter.blur(sigmaX: 8.0, sigmaY: 8.0), // تأثير ضبابي راقي
            child: Dialog(
              backgroundColor: Colors.transparent,
              elevation: 0,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
                decoration: BoxDecoration(
                  color: const AppColors.cardColor.withValues(alpha: 0.98),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: AppColors.lineColor,
                    width: 1.5,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.textDark.withValues(alpha: 0.12),
                      blurRadius: 20,
                      spreadRadius: 5,
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // مؤشر تحميل دائري بتصميم عصري
                    const SizedBox(
                      width: 45,
                      height: 45,
                      child: CircularProgressIndicator(
                        strokeWidth: 3.5,
                        valueColor:
                            AlwaysStoppedAnimation<Color>(AppColors.primaryTeal),
                      ),
                    ),
                    const SizedBox(height: 20),
                    // نص التوضيح
                    const Text(
                      'جاري حفظ التغييرات...',
                      style: TextStyle(
                        color: AppColors.textDark,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'جاري الاتصال بسيرفر وصال الآمن',
                      style: TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 12,
                        fontFamily: 'Cairo',
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  /// تمرير النتيجة إلى مكوّن الإشعارات الموحد في التطبيق.
  static void _showModernSnackBar({
    required BuildContext context,
    required String messageAr,
    required String messageEn,
    required NotificationType type,
  }) {
    if (!context.mounted) return;
    final bool isArabic =
        Directionality.maybeOf(context) != TextDirection.ltr;
    final String titleAr;
    final String titleEn;
    switch (type) {
      case NotificationType.success:
        titleAr = 'تم حفظ البيانات';
        titleEn = 'Profile saved';
        break;
      case NotificationType.warning:
        titleAr = 'الحفظ يحتاج إلى مراجعة';
        titleEn = 'Review your saved changes';
        break;
      case NotificationType.error:
        titleAr = 'تعذّر الحفظ';
        titleEn = 'Could not save changes';
        break;
      case NotificationType.info:
        titleAr = 'لا توجد تغييرات';
        titleEn = 'No changes to save';
        break;
    }
    AppSnackBar.show(
      context: context,
      type: type,
      isArabic: isArabic,
      titleAr: titleAr,
      titleEn: titleEn,
      messageAr: messageAr,
      messageEn: messageEn,
      detailsAr: type == NotificationType.error
          ? 'تحقق من الاتصال وحاول حفظ البيانات مرة أخرى.'
          : null,
      detailsEn: type == NotificationType.error
          ? 'Check your connection and try saving again.'
          : null,
      duration: const Duration(seconds: 5),
    );
  }
}

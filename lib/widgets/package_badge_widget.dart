// ============================================================================
// اسم الملف: lib/widgets/package_badge_widget.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: ودجت موحد وعصري لعرض شارات الباقات والاشتراكات (الملكية، الذهبية، الفضية)
//        مع دعم التدويل اللحظي وإخفاء الشارة تلقائياً للحسابات المجانية.
// ============================================================================

import 'package:flutter/material.dart';

class PackageBadgeWidget extends StatelessWidget {
  final String packageName;
  final bool compact; // خيار لإظهار الشارة بحجم مصغر داخل كروت القوائم والشات
  final bool isArabic; // دعم التدويل اللحظي للغة
  final String? customTitle; // إمكانية تمرير عنوان مخصص للشارة
  final VoidCallback? onTap; // دالة النقر الاختيارية عند التفاعل مع الشارة

  const PackageBadgeWidget({
    super.key,
    required this.packageName,
    this.compact = false,
    this.isArabic = true,
    this.customTitle,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final String cleanName = packageName.trim().toLowerCase();

    // 🟢 1. استبعاد الحسابات المجانية، الفارغة، أو النيم الكلاسيكي وإخفاء الشارة تماماً
    if (cleanName.isEmpty ||
        cleanName == '0' ||
        cleanName == 'null' ||
        cleanName == 'none' ||
        cleanName.contains('مجاني') ||
        cleanName.contains('free') ||
        cleanName.contains('basic') ||
        cleanName.contains('عادي') ||
        cleanName.contains('بدون')) {
      return const SizedBox.shrink();
    }

    Color primaryColor;
    List<Color> gradientColors;
    IconData badgeIcon;
    String badgeTitle;

    // 🟢 2. تصنيف الباقة وتحديد الألوان والأيقونات بناءً على الكلمات المفتاحية
    if (cleanName.contains('ملكي') ||
        cleanName.contains('royal') ||
        cleanName.contains('vip') ||
        cleanName == '3') {
      // الباقة الملكية 👑
      primaryColor = const Color(0xFFAB47BC);
      gradientColors = const [Color(0xFF8E24AA), Color(0xFF4A148C)];
      badgeIcon = Icons.workspace_premium_rounded;
      badgeTitle = customTitle ?? (isArabic ? 'ملكية 👑' : 'Royal 👑');
    } else if (cleanName.contains('ذهبي') ||
        cleanName.contains('gold') ||
        cleanName.contains('شهر') ||
        cleanName.contains('month') ||
        cleanName == '1' ||
        cleanName == '2') {
      // الباقة الذهبية 🥇
      primaryColor = const Color(0xFFFFB300);
      gradientColors = const [Color(0xFFFFB300), Color(0xFFF57C00)];
      badgeIcon = Icons.stars_rounded;
      badgeTitle = customTitle ?? (isArabic ? 'ذهبية 🥇' : 'Gold 🥇');
    } else if (cleanName.contains('مميز') ||
        cleanName.contains('silver') ||
        cleanName.contains('فضية') ||
        cleanName.contains('أسبوع') ||
        cleanName.contains('week')) {
      // الباقة المميزة / الفضية 🥈
      primaryColor = const Color(0xFF38BDF8);
      gradientColors = const [Color(0xFF0288D1), Color(0xFF005691)];
      badgeIcon = Icons.verified_rounded;
      badgeTitle = customTitle ?? (isArabic ? 'مميزة 🥈' : 'Silver 🥈');
    } else {
      // 🟢 3. في حال كان اسم الباقة لا ينتمي لأي باقة مدفوعة معروفة، لا يتم عرض شارة
      return const SizedBox.shrink();
    }

    Widget content = Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 7 : 10,
        vertical: compact ? 3 : 5,
      ),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: gradientColors,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(compact ? 8 : 12),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.35),
          width: compact ? 0.8 : 1.0,
        ),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withValues(alpha: compact ? 0.25 : 0.40),
            blurRadius: compact ? 4 : 8,
            offset: Offset(0, compact ? 1 : 2),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Icon(
            badgeIcon,
            color: Colors.white,
            size: compact ? 11 : 14,
          ),
          SizedBox(width: compact ? 3 : 5),
          Flexible(
            child: Text(
              badgeTitle,
              style: TextStyle(
                color: Colors.white,
                fontSize: compact ? 9.5 : 11.5,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
                height: 1.1,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );

    if (onTap != null) {
      return GestureDetector(
        onTap: onTap,
        behavior: HitTestBehavior.opaque,
        child: content,
      );
    }

    return content;
  }
}

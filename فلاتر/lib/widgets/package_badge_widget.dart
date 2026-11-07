// ============================================================================
// اسم الملف: lib/widgets/package_badge_widget.dart
// المشروع: تطبيق وصال (Wesal)
// الوصف: يعرض اسم الباقة الفعلي القادم من API دون استنتاج اسم بديل من المدة.
// ============================================================================

import 'package:flutter/material.dart';
import '../constants/colors.dart';

class PackageBadgeWidget extends StatelessWidget {
  final String packageName;
  final bool compact;
  final bool isArabic;
  final VoidCallback? onTap;

  const PackageBadgeWidget({
    super.key,
    required this.packageName,
    this.compact = false,
    this.isArabic = true,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final String canonicalName = packageName.trim();
    final String normalizedName = canonicalName.toLowerCase();

    // The API only sends a package name for a current, paid and active plan.
    // Do not infer premium status from a duration, numeric ID, or plan wording.
    if (canonicalName.isEmpty ||
        normalizedName == '0' ||
        normalizedName == 'null' ||
        normalizedName == 'none') {
      return const SizedBox.shrink();
    }

    const Color primaryColor = AppColors.premiumGold;
    const List<Color> gradientColors = [
      AppColors.premiumGold,
      AppColors.premiumGoldDeep,
    ];
    final String badgeTitle = canonicalName;

    Widget content = Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 7 : 10,
        vertical: compact ? 3 : 5,
      ),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
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
            Icons.workspace_premium_rounded,
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

import 'package:flutter/material.dart';

import '../constants/colors.dart';
import 'bottom_system_inset.dart';

/// Shared presentation for bottom-sheet notices that ask the user to take action.
class AppActionBottomSheet {
  const AppActionBottomSheet._();

  static Future<void> show({
    required BuildContext context,
    required bool isArabic,
    required String title,
    required String message,
    required IconData icon,
    required String primaryLabel,
    required VoidCallback onPrimaryPressed,
    Color accentColor = AppColors.primaryTeal,
    IconData primaryIcon = Icons.arrow_forward_rounded,
  }) async {
    final bool? shouldContinue = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => Directionality(
        textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: SafeArea(
          top: false,
          child: ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.sizeOf(sheetContext).height * 0.68,
            ),
            child: Container(
              padding: EdgeInsets.fromLTRB(
                20, 12, 20, 16 + WesalBottomInsets.of(sheetContext),
              ),
              decoration: BoxDecoration(
                color: AppColors.cardColor,
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(28)),
                border: Border.all(
                  color: accentColor.withValues(alpha: 0.28), width: 1.1,
                ),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.textDark.withValues(alpha: 0.08),
                    blurRadius: 24,
                    offset: const Offset(0, -5),
                  ),
                ],
              ),
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(
                      child: Container(
                        width: 42, height: 4,
                        decoration: BoxDecoration(
                          color: AppColors.lineColor,
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                    const SizedBox(height: 18),
                    Row(
                      children: [
                        Container(
                          width: 48, height: 48,
                          decoration: BoxDecoration(
                            color: accentColor.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(15),
                          ),
                          child: Icon(icon, color: accentColor, size: 25),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            title,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              fontFamily: 'Cairo',
                              height: 1.3,
                            ),
                          ),
                        ),
                        IconButton(
                          tooltip: isArabic ? 'إغلاق' : 'Close',
                          onPressed: () =>
                              Navigator.of(sheetContext).pop(false),
                          icon: const Icon(Icons.close_rounded,
                              color: AppColors.textMuted),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      message,
                      style: const TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 13,
                        height: 1.55,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: () => Navigator.of(sheetContext).pop(true),
                        icon: Icon(primaryIcon, color: Colors.white, size: 19),
                        label: Text(
                          primaryLabel,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primaryTeal,
                          foregroundColor: Colors.white,
                          minimumSize: const Size.fromHeight(50),
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(15),
                          ),
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
    if (shouldContinue == true && context.mounted) onPrimaryPressed();
  }
}

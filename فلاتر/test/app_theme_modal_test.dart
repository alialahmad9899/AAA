import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/constants/colors.dart';
import 'package:wesal_app/theme/app_theme.dart';

void main() {
  group('Wesal modal theme', () {
    test('dialogs use a small, consistent inset on all four sides', () {
      expect(
        AppTheme.light.dialogTheme.insetPadding,
        const EdgeInsets.all(8),
      );
      expect(AppTheme.light.dialogTheme.backgroundColor, AppColors.cardColor);
      expect(
        AppTheme.light.dialogTheme.surfaceTintColor,
        Colors.transparent,
      );
    });

    test('bottom sheets use the common card surface and clipped rounded edge', () {
      expect(
        AppTheme.light.bottomSheetTheme.backgroundColor,
        AppColors.cardColor,
      );
      expect(
        AppTheme.light.bottomSheetTheme.surfaceTintColor,
        Colors.transparent,
      );
      expect(
        AppTheme.light.bottomSheetTheme.clipBehavior,
        Clip.antiAlias,
      );
    });
  });
}

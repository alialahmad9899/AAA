import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/constants/colors.dart';
import 'package:wesal_app/theme/app_theme.dart';

void main() {
  test('the application theme follows the Wesal brand palette', () {
    final theme = AppTheme.light;
    expect(theme.primaryColor, AppColors.primaryTeal);
    expect(theme.scaffoldBackgroundColor, AppColors.backgroundColor);
    expect(theme.colorScheme.secondary, AppColors.accentWarm);
    expect(theme.cardColor, AppColors.cardColor);
    expect(theme.appBarTheme.backgroundColor, AppColors.backgroundColor);
    expect(theme.bottomSheetTheme.backgroundColor, AppColors.cardColor);
    expect(theme.dialogTheme.backgroundColor, AppColors.cardColor);
    expect(AppColors.textMuted, const Color(0xFF596875));
    expect(theme.textTheme.bodyMedium?.color, AppColors.textDark);
    expect(theme.textTheme.displayLarge?.color, AppColors.textDark);
    expect(theme.textTheme.bodyLarge?.color, AppColors.textDark);
    expect(theme.inputDecorationTheme.hintStyle?.color, AppColors.textMuted);
    expect(theme.elevatedButtonTheme.style?.foregroundColor?.resolve({}),
        const Color(0xFFFFFFFF));
  });

  test('dark text tokens remain readable on ivory and white surfaces', () {
    expect(_contrastRatio(AppColors.textDark, AppColors.backgroundColor),
        greaterThanOrEqualTo(4.5));
    expect(_contrastRatio(AppColors.textMuted, AppColors.backgroundColor),
        greaterThanOrEqualTo(4.5));
    expect(_contrastRatio(AppColors.textDark, AppColors.cardColor),
        greaterThanOrEqualTo(4.5));
    expect(_contrastRatio(AppColors.textMuted, AppColors.cardColor),
        greaterThanOrEqualTo(4.5));
  });

  test('semantic and decorative colors meet contrast requirements', () {
    const lightSurfaces = [
      AppColors.backgroundColor,
      AppColors.sectionBackground,
      AppColors.cardColor,
    ];

    for (final surface in lightSurfaces) {
      expect(
        _contrastRatio(AppColors.success, surface),
        greaterThanOrEqualTo(4.5),
        reason: 'Success text must remain readable on light surfaces.',
      );
      expect(
        _contrastRatio(AppColors.warning, surface),
        greaterThanOrEqualTo(4.5),
        reason: 'Warning text must remain readable on light surfaces.',
      );
      expect(
        _contrastRatio(AppColors.error, surface),
        greaterThanOrEqualTo(4.5),
        reason: 'Error text must remain readable on light surfaces.',
      );
      expect(
        _contrastRatio(AppColors.accentWarm, surface),
        greaterThanOrEqualTo(3.0),
        reason: 'Accent controls and icons need visible non-text contrast.',
      );
      expect(
        _contrastRatio(AppColors.textLight, surface),
        greaterThanOrEqualTo(3.0),
        reason: 'Secondary controls and handles need visible contrast.',
      );
    }
  });

  test('premium, silver and gender accents remain readable', () {
    const whiteLabelBackgrounds = [
      AppColors.accentWarmDeep,
      AppColors.premiumGold,
      AppColors.premiumGoldDeep,
      AppColors.premiumSilver,
      AppColors.premiumSilverDeep,
    ];

    for (final background in whiteLabelBackgrounds) {
      expect(
        _contrastRatio(Colors.white, background),
        greaterThanOrEqualTo(4.5),
        reason: 'White labels must stay readable on premium and gender accents.',
      );
    }

    const readableTextAccents = [
      AppColors.accentWarmDeep,
      AppColors.premiumGold,
      AppColors.premiumSilver,
    ];

    for (final foreground in readableTextAccents) {
      expect(
        _contrastRatio(foreground, AppColors.backgroundColor),
        greaterThanOrEqualTo(4.5),
      );
      expect(
        _contrastRatio(foreground, AppColors.sectionBackground),
        greaterThanOrEqualTo(4.5),
      );
    }
  });

  test('primary teal actions remain readable with white labels', () {
    expect(_contrastRatio(Colors.white, AppColors.primaryTeal),
        greaterThanOrEqualTo(4.5));
  });

  test('interactive controls keep strong contrast on light surfaces', () {
    final theme = AppTheme.light;
    expect(theme.appBarTheme.foregroundColor, AppColors.textDark);
    expect(theme.inputDecorationTheme.labelStyle?.color, AppColors.textMuted);
    expect(theme.inputDecorationTheme.floatingLabelStyle?.color,
        AppColors.primaryTeal);
    expect(theme.elevatedButtonTheme.style?.backgroundColor?.resolve({}),
        AppColors.primaryTeal);
    expect(theme.outlinedButtonTheme.style?.foregroundColor?.resolve({}),
        AppColors.primaryTeal);
  });
}

double _contrastRatio(Color foreground, Color background) {
  final double foregroundLuminance = foreground.computeLuminance();
  final double backgroundLuminance = background.computeLuminance();
  final double lighter = foregroundLuminance > backgroundLuminance
      ? foregroundLuminance
      : backgroundLuminance;
  final double darker = foregroundLuminance > backgroundLuminance
      ? backgroundLuminance
      : foregroundLuminance;
  return (lighter + 0.05) / (darker + 0.05);
}

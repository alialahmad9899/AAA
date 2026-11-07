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
    expect(theme.textTheme.bodyMedium?.color, AppColors.textDark);
    expect(theme.textTheme.displayLarge?.color, AppColors.textDark);
    expect(theme.inputDecorationTheme.textStyle?.color, AppColors.textDark);
    expect(theme.inputDecorationTheme.hintStyle?.color, AppColors.textMuted);
    expect(theme.elevatedButtonTheme.style?.foregroundColor?.resolve({}),
        const Color(0xFFFFFFFF));
  });
}

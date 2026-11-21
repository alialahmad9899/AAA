import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/constants/colors.dart';
import 'package:wesal_app/widgets/vip_survey_wizard_dialog.dart';

void main() {
  testWidgets(
    'VIP partner specifications are required before confirmation and Back remains readable',
    (tester) async {
      tester.view.physicalSize = const Size(1080, 1920);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);

      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: Center(
              child: VipSurveyWizardDialog(
                userToken: 'test-token',
                baseUrl: 'https://example.test',
                isArabic: true,
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();

      await tester.tap(find.text('التالي').first);
      await tester.pumpAndSettle();
      expect(
        find.text('مواصفات شريك الحياة المطلوبة (العمر، الإقامة، الصفات...) *'),
        findsOneWidget,
      );

      final backButtonFinder = find.ancestor(
        of: find.text('السابق'),
        matching: find.byType(OutlinedButton),
      );
      expect(backButtonFinder, findsOneWidget);
      final backButton = tester.widget<OutlinedButton>(backButtonFinder);
      expect(
        backButton.style?.foregroundColor?.resolve(const <WidgetState>{}),
        AppColors.textDark,
      );

      await tester.tap(find.text('التالي').first);
      await tester.pump(const Duration(milliseconds: 400));
      expect(
        find.text('مواصفات شريك الحياة المطلوبة (العمر، الإقامة، الصفات...) *'),
        findsOneWidget,
      );
      expect(find.textContaining('جميع البيانات جاهزة'), findsNothing);

      await tester.enterText(
        find.byType(TextField).first,
        'أبحث عن شريكة متفاهمة',
      );
      tester.testTextInput.hide();
      await tester.pump(const Duration(milliseconds: 100));
      await tester.tap(find.text('التالي').first);
      await tester.pumpAndSettle();
      expect(find.textContaining('جميع البيانات جاهزة'), findsOneWidget);
    },
  );
}

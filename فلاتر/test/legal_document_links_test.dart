import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/screens/widgets/step1_basic_info.dart';
import 'package:wesal_app/utils/legal_document_links.dart';
import 'package:wesal_app/widgets/about_app_dialog.dart';

void main() {
  group('Wesal legal document links', () {
    test('uses the requested privacy and terms destinations', () {
      expect(
        LegalDocumentLinks.privacyPolicyUrl,
        'https://www.wesalsyria.com/privacy-policy',
      );
      expect(
        LegalDocumentLinks.termsOfUseUrl,
        'https://www.wesalsyria.com/terms-conditions',
      );
      expect(
        LegalDocumentLinks.privacyPolicyUrl,
        isNot(LegalDocumentLinks.termsOfUseUrl),
      );
    });

    testWidgets('About app shows separate privacy and terms entries',
        (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: AboutAppDialog(currentLanguage: 'ar'),
          ),
        ),
      );

      expect(find.text('سياسة الخصوصية'), findsOneWidget);
      expect(find.text('شروط الاستخدام'), findsOneWidget);
    });

    testWidgets('registration consent names both documents separately',
        (WidgetTester tester) async {
      final controllers = List<TextEditingController>.generate(
        6,
        (_) => TextEditingController(),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: Step1BasicInfo(
              formKey: GlobalKey<FormState>(),
              firstNameController: controllers[0],
              lastNameController: controllers[1],
              dobController: controllers[2],
              emailController: controllers[3],
              passwordController: controllers[4],
              confirmPasswordController: controllers[5],
              gender: '',
              onBehalf: '',
              agreedToTerms: false,
              profilePhoto: null,
              lang: 'ar',
              onGenderChanged: (_) {},
              onBehalfChanged: (_) {},
              onTermsChanged: (_) {},
              onPhotoChanged: (_) {},
            ),
          ),
        ),
      );

      final consentText = tester
          .widgetList<RichText>(find.byType(RichText))
          .map((widget) => widget.text.toPlainText())
          .join(' ');

      expect(consentText, contains('سياسة الخصوصية'));
      expect(consentText, contains('شروط الاستخدام'));
      expect(
        tester.widget<Checkbox>(find.byType(Checkbox)).value,
        isFalse,
      );

      for (final controller in controllers) {
        controller.dispose();
      }
    });
  });
}

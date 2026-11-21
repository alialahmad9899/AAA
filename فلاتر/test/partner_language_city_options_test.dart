import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/screens/edit_profile/widgets/profile_pickers.dart';
import 'package:wesal_app/screens/widgets/step4_partner_expectations.dart';

void main() {
  group('Partner language options', () {
    test('puts Arabic first and removes country pseudo-language rows', () {
      final languages = Step4PartnerExpectations.normalizePartnerLanguages([
        {'id': 7, 'name': 'English'},
        {'id': 8, 'name': 'Österreich (Austrian)'},
        {'id': 9, 'name': 'French'},
        {'id': 10, 'name': 'Arabic'},
        {'id': 11, 'name': 'Austrian'},
      ]);
      expect(languages.map((item) => item['id']).toList(), [10, 7, 9]);
      expect(Step4PartnerExpectations.languageLabel(languages.first), 'العربية / Arabic');
      expect(languages.any((item) => item['name'].toString().toLowerCase().contains('österreich')), isFalse);
    });

    test('drops duplicate language identifiers and incomplete rows', () {
      final languages = Step4PartnerExpectations.normalizePartnerLanguages([
        {'id': 1, 'name': 'English'},
        {'id': 1, 'name': 'English'},
        {'id': '', 'name': 'French'},
        {'id': 2, 'name': ''},
      ]);
      expect(languages.map((item) => item['id']).toList(), [1]);
    });
  });

  testWidgets('searches a city by its original name while showing Arabic', (tester) async {
    await tester.pumpWidget(MaterialApp(
      home: Scaffold(
        body: GenericPickerBottomSheetContent(
          title: 'اختر المدينة',
          items: const [
            {'id': '1', 'name': 'لايبزيغ', 'name_ar': 'لايبزيغ', 'name_en': 'Leipzig'},
            {'id': '2', 'name': 'برلين', 'name_ar': 'برلين', 'name_en': 'Berlin'},
          ],
          currentValue: null,
          onSelected: (_) {},
          isAr: true,
          enableSearch: true,
        ),
      ),
    ));

    await tester.enterText(find.byType(TextField), 'Leipzig');
    await tester.pump(const Duration(milliseconds: 220));
    await tester.pumpAndSettle();
    expect(find.text('لايبزيغ'), findsOneWidget);
    expect(find.text('برلين'), findsNothing);
  });

  group('Arabic city labels', () {
    test('uses curated Arabic transliterations for common German cities', () {
      expect(ProfilePickerTranslations.translateCity('Leipzig', true), 'لايبزيغ');
      expect(ProfilePickerTranslations.translateCity('Berlin', true), 'برلين');
      expect(ProfilePickerTranslations.translateCity('München', true), 'ميونخ');
    });

    test('prefers verified Arabic API translations and does not guess unknown cities', () {
      expect(ProfilePickerTranslations.translateCity('Leipzig', true, arabicName: 'لايبزيغ'), 'لايبزيغ');
      expect(ProfilePickerTranslations.translateCity('Unknown City', true), 'Unknown City');
      expect(ProfilePickerTranslations.translateCity('Leipzig', false), 'Leipzig');
    });
  });
}

import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/screens/widgets/step4_partner_expectations.dart';

void main() {
  group('Step 4 partner question numbering', () {
    test('renumbers later questions when governorate options are absent', () {
      final numbers = Step4PartnerExpectations.questionNumbers(
        hasReligion: true,
        hasCountry: true,
        hasGovernorate: false,
      );

      expect(numbers['religion'], 9);
      expect(numbers['country'], 10);
      expect(numbers.containsKey('governorate'), isFalse);
      expect(numbers['career'], 11);
      expect(numbers['children'], 12);
      expect(numbers['smoking'], 13);
      expect(numbers['drinking'], 14);
    });

    test('keeps the full sequence when every optional question is visible', () {
      final numbers = Step4PartnerExpectations.questionNumbers(
        hasReligion: true,
        hasCountry: true,
        hasGovernorate: true,
      );

      expect(numbers.values.toList(), [9, 10, 11, 12, 13, 14, 15]);
    });

    test('does not reserve numbers for unavailable optional lists', () {
      final numbers = Step4PartnerExpectations.questionNumbers(
        hasReligion: false,
        hasCountry: false,
        hasGovernorate: false,
      );

      expect(numbers['career'], 9);
      expect(numbers['children'], 10);
      expect(numbers['smoking'], 11);
      expect(numbers['drinking'], 12);
    });
  });
}

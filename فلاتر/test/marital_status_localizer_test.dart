import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/marital_status_localizer.dart';

void main() {
  group('MaritalStatusLocalizer', () {
    test('translates Arabic single labels with spacing and emoji', () {
      expect(MaritalStatusLocalizer.toEnglish('أعزب / عزباء 💍'), 'Single');
      expect(MaritalStatusLocalizer.toEnglish('عزباء'), 'Single');
    });

    test('translates every marital status', () {
      expect(MaritalStatusLocalizer.toEnglish('متزوج / ة'), 'Married');
      expect(MaritalStatusLocalizer.toEnglish('مطلقة'), 'Divorced');
      expect(MaritalStatusLocalizer.toEnglish('أرمل / ة'), 'Widowed');
    });

    test('keeps already-English values and ignores unknown labels safely', () {
      expect(MaritalStatusLocalizer.toEnglish('Single'), 'Single');
      expect(MaritalStatusLocalizer.toEnglish(''), isNull);
      expect(MaritalStatusLocalizer.toEnglish('غير معروف'), isNull);
    });
  });
}

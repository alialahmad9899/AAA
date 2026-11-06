import 'package:flutter_test/flutter_test.dart';

import '../lib/screens/home/tabs/auto_matching_tab.dart';
import '../lib/screens/partner_expectations_screen.dart';
import '../lib/screens/widgets/step4_partner_expectations.dart';

void main() {
  test('smart matching and partner expectation screens compile', () {
    final constructors = <Function>[
      AutoMatchingTab.new,
      PartnerExpectationsScreen.new,
      Step4PartnerExpectations.new,
    ];

    expect(constructors, hasLength(3));
  });
}

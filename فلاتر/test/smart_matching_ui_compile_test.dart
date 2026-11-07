import 'package:flutter_test/flutter_test.dart';

import 'package:wesal_app/screens/home/tabs/auto_matching_tab.dart';
import 'package:wesal_app/screens/partner_expectations_screen.dart';
import 'package:wesal_app/screens/widgets/step4_partner_expectations.dart';

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

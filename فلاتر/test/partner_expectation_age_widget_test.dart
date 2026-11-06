import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/screens/widgets/step4_partner_expectations.dart';
import 'package:wesal_app/services/api_service.dart';

void main() {
  group('Partner age API payload contract', () {
    test('uses the canonical Laravel field names', () {
      expect(
        ApiService.buildPartnerAgeRangePayload(minAge: 25, maxAge: 45),
        <String, int>{'min_age': 25, 'max_age': 45},
      );
    });

    test('rejects invalid or reversed age ranges', () {
      expect(
        () => ApiService.buildPartnerAgeRangePayload(minAge: 17, maxAge: 45),
        throwsArgumentError,
      );
      expect(
        () => ApiService.buildPartnerAgeRangePayload(minAge: 25, maxAge: 101),
        throwsArgumentError,
      );
      expect(
        () => ApiService.buildPartnerAgeRangePayload(minAge: 45, maxAge: 30),
        throwsArgumentError,
      );
    });
  });

  testWidgets('partner expectation step renders age range and forwards changes',
      (tester) async {
    RangeValues? changedAgeRange;
    final formKey = GlobalKey<FormState>();
    final generalController = TextEditingController();
    final professionController = TextEditingController();
    addTearDown(generalController.dispose);
    addTearDown(professionController.dispose);

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Step4PartnerExpectations(
            formKey: formKey,
            generalController: generalController,
            professionController: professionController,
            ageRange: const RangeValues(25, 45),
            heightRange: const RangeValues(155, 180),
            weightRange: const RangeValues(50, 80),
            ageRangeWasSelected: false,
            heightRangeWasSelected: false,
            weightRangeWasSelected: false,
            education: null,
            complexion: null,
            eyeColor: null,
            hairColor: null,
            languages: const [],
            religions: const [],
            countries: const [],
            states: const [],
            preferredReligionId: null,
            preferredCountryId: null,
            preferredStateId: null,
            isLoadingLanguages: false,
            languageId: null,
            childrenAcceptable: 'does_not_matter',
            smokingAcceptable: 'does_not_matter',
            drinkingAcceptable: 'does_not_matter',
            lang: 'ar',
            onAgeRangeChanged: (value) => changedAgeRange = value,
            onHeightRangeChanged: (_) {},
            onWeightRangeChanged: (_) {},
            onEducationChanged: (_) {},
            onComplexionChanged: (_) {},
            onEyeColorChanged: (_) {},
            onHairColorChanged: (_) {},
            onLanguageChanged: (_) {},
            onPreferredReligionChanged: (_) {},
            onPreferredCountryChanged: (_) {},
            onPreferredStateChanged: (_) {},
            onRetryLanguages: () {},
            onChildrenChanged: (_) {},
            onSmokingChanged: (_) {},
            onDrinkingChanged: (_) {},
          ),
        ),
      ),
    );

    expect(find.text('لم يتم تحديد نطاق العمر بعد'), findsOneWidget);
    final ageSlider = tester.widget<RangeSlider>(
      find.byKey(const ValueKey<String>('partner-age-range-slider')),
    );
    expect(ageSlider.min, 18);
    expect(ageSlider.max, 100);
    expect(ageSlider.values, const RangeValues(25, 45));

    ageSlider.onChanged!(const RangeValues(28, 52));
    expect(changedAgeRange, const RangeValues(28, 52));
  });
}

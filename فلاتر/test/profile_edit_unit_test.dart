import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/services/api_service.dart';
import 'package:wesal_app/models/member_profile_model.dart';

void main() {
  group('AttributeTranslator Tests', () {
    test('cleanEmojis removes all emojis and normalizes spaces', () {
      expect(AttributeTranslator.cleanEmojis('أعزب / عزباء 💍'), 'أعزب / عزباء');
      expect(AttributeTranslator.cleanEmojis('Single 💍'), 'Single');
      expect(AttributeTranslator.cleanEmojis('  لا أدخن أبداً 🌿  '), 'لا أدخن أبداً');
    });

    test('isValidValue filters invalid placeholders', () {
      expect(AttributeTranslator.isValidValue(null), false);
      expect(AttributeTranslator.isValidValue(''), false);
      expect(AttributeTranslator.isValidValue('---'), false);
      expect(AttributeTranslator.isValidValue('null'), false);
      expect(AttributeTranslator.isValidValue('ارسل رسالة'), false);
      expect(AttributeTranslator.isValidValue('مهندس'), true);
    });

    test('translateMaritalStatus works for both AR and EN', () {
      expect(AttributeTranslator.translateMaritalStatus('1', isArabic: true), 'أعزب / عزباء 💍');
      expect(AttributeTranslator.translateMaritalStatus('1', isArabic: false), 'Single 💍');
      expect(AttributeTranslator.translateMaritalStatus('2', isArabic: true), 'متزوج / متزوجة 💍');
      expect(AttributeTranslator.translateMaritalStatus('3', isArabic: false), 'Divorced 💍');
    });

    test('translateReligion works correctly', () {
      expect(AttributeTranslator.translateReligion('1', isArabic: true), 'إسلام ☪️');
      expect(AttributeTranslator.translateReligion('1', isArabic: false), 'Islam ☪️');
      expect(AttributeTranslator.translateReligion('2', isArabic: true), 'مسيحية ✝️');
      expect(AttributeTranslator.translateReligion('2', isArabic: false), 'Christianity ✝️');
    });
  });

  group('MemberProfileModel Parsing Test', () {
    test('MemberProfileModel parses basic info correctly', () {
      final json = {
        'id': 105,
        'user_id': 105,
        'introduction': 'مرحباً بكم في ملفي الشخصي',
        'basic_info': {
          'first_name': 'أحمد',
          'last_name': 'العلي',
          'gender': '1',
          'phone': '0912345678',
          'date_of_birth': '1995-05-15',
          'marital_status': '1',
          'religion': '1',
        },
        'physical_attributes': {
          'height': '1.80',
          'weight': '75',
          'eye_color': 'بني',
          'hair_color': 'أسود',
          'body_type': 'رياضي',
          'body_art': 'وشم بسيط',
        }
      };

      final profile = MemberProfileModel.fromJson(json);

      expect(profile.userId, 105);
      expect(profile.introduction, 'مرحباً بكم في ملفي الشخصي');
      expect(profile.basicInfo.firstName, 'أحمد');
      expect(profile.basicInfo.lastName, 'العلي');
      expect(profile.basicInfo.gender, '1');
      expect(profile.basicInfo.isFemale, false);
      expect(profile.physicalAttributes?.height, '1.80');
      expect(profile.physicalAttributes?.weight, '75');
      expect(profile.physicalAttributes?.bodyType, 'رياضي');
      expect(profile.physicalAttributes?.bodyArt, 'وشم بسيط');
    });
  });
  test('parses the canonical marital status ID before translated labels', () {
    final profile = MemberProfileModel.fromJson({
      'basic_info': {
        'maritial_status': 'متزوج / متزوجة',
        'marital_status': 'Married',
        'marital_status_id': 2,
      },
    });
    expect(profile.basicInfo.maritalStatus, '2');
  });

  group('Premium subscription status', () {
    String dateOnly(DateTime date) =>
        '${date.year.toString().padLeft(4, '0')}-'
        '${date.month.toString().padLeft(2, '0')}-'
        '${date.day.toString().padLeft(2, '0')}';

    test('requires the server premium flag for a paid current package', () {
      final profile = MemberProfileModel.fromJson({
        'basic_info': {
          'gender': 'ذكر',
          'package_name': 'الباقة الشهرية',
          'package_validity': dateOnly(DateTime.now().add(const Duration(days: 7))),
          'is_premium': true,
        },
      });
      expect(profile.isSubscribedActive, isTrue);
    });

    test('rejects future validity when the server says the package is not premium', () {
      final profile = MemberProfileModel.fromJson({
        'basic_info': {
          'gender': 'ذكر',
          'package_name': 'باقة مجانية',
          'package_validity': dateOnly(DateTime.now().add(const Duration(days: 30))),
          'is_premium': false,
        },
      });
      expect(profile.isSubscribedActive, isFalse);
    });

    test('rejects an expired package even when the premium flag is true', () {
      final profile = MemberProfileModel.fromJson({
        'basic_info': {
          'gender': 'ذكر',
          'package_name': 'الباقة الشهرية',
          'package_validity': dateOnly(DateTime.now().subtract(const Duration(days: 1))),
          'is_premium': true,
        },
      });
      expect(profile.isSubscribedActive, isFalse);
    });

    test('does not mark a free female account as a paid subscription', () {
      final profile = MemberProfileModel.fromJson({
        'basic_info': {
          'gender': 'أنثى',
          'package_name': '',
          'package_validity': dateOnly(DateTime.now().add(const Duration(days: 30))),
          'is_premium': false,
        },
      });
      expect(profile.isSubscribedActive, isFalse);
    });

    test('keeps a paid package active through its inclusive expiry date', () {
      final profile = MemberProfileModel.fromJson({
        'basic_info': {
          'gender': 'ذكر',
          'package_name': 'الباقة الأسبوعية',
          'package_validity': dateOnly(DateTime.now()),
          'is_premium': true,
        },
      });
      expect(profile.isSubscribedActive, isTrue);
    });

    test('recognizes weekly, monthly and three-month paid terms through their inclusive last day', () {
      final today = DateTime.now();
      final todayDate = DateTime(today.year, today.month, today.day);
      for (final term in [
        {'days': 7, 'name': 'الاشتراك الأسبوعي'},
        {'days': 30, 'name': 'الاشتراك الشهري'},
        {'days': 90, 'name': 'اشتراك 3 أشهر'},
      ]) {
        final lastValidDay = todayDate.add(
          Duration(days: (term['days'] as int) - 1),
        );
        final profile = MemberProfileModel.fromJson({
          'basic_info': {
            'gender': 'ذكر',
            'package_name': term['name'],
            'package_validity': dateOnly(lastValidDay),
            'is_premium': true,
          },
        });
        expect(profile.isSubscribedActive, isTrue);
      }
    });

    test('accepts supported boolean representations from the API', () {
      for (final value in [1, '1', 'true']) {
        final profile = MemberProfileModel.fromJson({
          'basic_info': {
            'gender': 'ذكر',
            'package_name': 'اسم الباقة من الخادم',
            'package_validity': dateOnly(DateTime.now().add(const Duration(days: 2))),
            'is_premium': value,
          },
        });
        expect(profile.isSubscribedActive, isTrue);
      }
    });
  });

  group('Managed member account disclosure', () {
    test('shows child disclosure based on the member gender', () {
      final son = MemberProfileModel.fromJson({
        'basic_info': {'gender': 'ذكر', 'on_behalf_id': 2},
      });
      final daughter = MemberProfileModel.fromJson({
        'basic_info': {'gender': 'أنثى', 'on_behalf_id': 2},
      });

      expect(
        son.basicInfo.getManagedAccountLabel(),
        'حساب يُدار نيابةً عن ابني',
      );
      expect(
        daughter.basicInfo.getManagedAccountLabel(),
        'حساب يُدار نيابةً عن ابنتي',
      );
    });

    test('shows sibling disclosure based on the member gender', () {
      final brother = MemberProfileModel.fromJson({
        'basic_info': {'gender': 'ذكر', 'on_behalf_id': 3},
      });
      final sister = MemberProfileModel.fromJson({
        'basic_info': {'gender': 'أنثى', 'on_behalf_id': 3},
      });

      expect(
        brother.basicInfo.getManagedAccountLabel(),
        'حساب يُدار نيابةً عن أخي',
      );
      expect(
        sister.basicInfo.getManagedAccountLabel(),
        'حساب يُدار نيابةً عن أختي',
      );
    });

    test('hides personal and unknown account types', () {
      final personal = MemberProfileModel.fromJson({
        'basic_info': {'gender': 'ذكر', 'on_behalf_id': 1},
      });
      final unknown = MemberProfileModel.fromJson({
        'basic_info': {'gender': 'أنثى', 'on_behalf_id': 99},
      });

      expect(personal.basicInfo.getManagedAccountLabel(), isNull);
      expect(unknown.basicInfo.getManagedAccountLabel(), isNull);
    });

    test('accepts the nested onbehalf resource and localizes English labels', () {
      final profile = MemberProfileModel.fromJson({
        'basic_info': {
          'gender': 'Female',
          'onbehalf': {'id': 3, 'name': 'For My Sister'},
        },
      });

      expect(profile.basicInfo.onBehalfId, '3');
      expect(
        profile.basicInfo.getManagedAccountLabel(isArabic: false),
        'Managed on behalf of my sister',
      );
    });
  });

  group('Laravel API resource envelope parsing', () {
    test('unwraps a single model wrapped in data', () {
      expect(
        ApiService.extractFirstResourceMap({
          'result': true,
          'data': {
            'id': 12,
            'degree': 'بكالوريوس',
          },
        }),
        {'id': 12, 'degree': 'بكالوريوس'},
      );
    });

    test('returns the first row from a resource collection', () {
      expect(
        ApiService.extractFirstResourceMap({
          'result': true,
          'data': [
            {'id': 31, 'designation': 'Developer'},
            {'id': 32, 'designation': 'Designer'},
          ],
        }),
        {'id': 31, 'designation': 'Developer'},
      );
    });

    test('unwraps nested data envelopes and rejects empty collections', () {
      expect(
        ApiService.extractFirstResourceMap({
          'data': {
            'data': {'id': 44, 'company': 'Wesal'},
          },
        }),
        {'id': 44, 'company': 'Wesal'},
      );
      expect(
        ApiService.extractFirstResourceMap({'result': true, 'data': []}),
        isNull,
      );
      expect(ApiService.extractFirstResourceMap(null), isNull);
    });
  });

  group('Preferred partner location mapping', () {
    test('prefers preferred_country_id over the independent residence country', () {
      expect(
        ApiService.preferredPartnerCountryId({
          'residence_country_id': 10,
          'partner_country_id': 20,
          'preferred_country_id': 30,
        }),
        '30',
      );
    });

    test('supports the partner_country_id compatibility alias only', () {
      expect(
        ApiService.preferredPartnerCountryId({
          'residence_country_id': 10,
          'partner_country_id': 20,
        }),
        '20',
      );
    });

    test('does not mistake residence country for preferred partner country', () {
      expect(
        ApiService.preferredPartnerCountryId({
          'residence_country_id': 10,
        }),
        isNull,
      );
    });

    test('sends preferred country and state without residence_country_id', () {
      final payload = ApiService.buildPreferredPartnerLocationPayload(
        countryId: '30',
        stateId: '301',
      );
      expect(payload, {
        'partner_country_id': '30',
        'partner_state_id': '301',
      });
      expect(payload.containsKey('residence_country_id'), isFalse);
    });

    test('does not send a state without a preferred country', () {
      expect(
        ApiService.buildPreferredPartnerLocationPayload(
          countryId: null,
          stateId: '301',
        ),
        isEmpty,
      );
    });
  });

  group('ApiService API write response validation', () {
    test('rejects empty responses', () {
      expect(ApiService.isSuccessfulResult({}), false);
      expect(ApiService.isSuccessfulResult(null), false);
    });

    test('rejects explicit failures and validation errors', () {
      expect(ApiService.isSuccessfulResult({'result': false}), false);
      expect(ApiService.isSuccessfulResult({'result': 'false'}), false);
      expect(
        ApiService.isSuccessfulResult({
          'result': true,
          'errors': {'email': ['Already exists']},
        }),
        false,
      );
    });

    test('accepts successful API envelopes', () {
      expect(ApiService.isSuccessfulResult({'result': true}), true);
      expect(ApiService.isSuccessfulResult({'status': 'success'}), true);
      expect(
        ApiService.isSuccessfulResult({
          'result': true,
          'message': 'Saved successfully',
        }),
        true,
      );
    });

    test('rejects nested failed response envelopes', () {
      expect(
        ApiService.isSuccessfulResult({
          'result': true,
          'data': {'result': false, 'message': 'Rejected'},
        }),
        false,
      );
    });
  });

  group('Arabic physical attribute values', () {
    test('keeps Arabic eye and hair colors unchanged in Arabic', () {
      expect(AttributeTranslator.translate('بني', isArabic: true), 'بني');
      expect(AttributeTranslator.translate('أسود', isArabic: true), 'أسود');
    });

    test('localizes raw Arabic physical attributes in English', () {
      expect(AttributeTranslator.translate('بني', isArabic: false), 'Brown');
      expect(AttributeTranslator.translate('حنطي', isArabic: false), 'Wheat / Tan');
      expect(AttributeTranslator.translate('أشقر', isArabic: false), 'Blonde');
    });
  });

  group('Profile field change detection', () {
    test('does not resend an unchanged loaded value', () {
      expect(
        ApiService.shouldSendChangedProfileField(
          wasLoaded: true,
          originalValue: 'بكالوريوس',
          currentValue: 'بكالوريوس',
        ),
        false,
      );
    });

    test('sends a changed loaded value', () {
      expect(
        ApiService.shouldSendChangedProfileField(
          wasLoaded: true,
          originalValue: 'جامعة دمشق',
          currentValue: 'جامعة حمص',
        ),
        true,
      );
    });

    test('sends a new non-empty value that was not loaded', () {
      expect(
        ApiService.shouldSendChangedProfileField(
          wasLoaded: false,
          currentValue: 'مطور',
        ),
        true,
      );
    });

    test('does not send an unchanged empty value', () {
      expect(
        ApiService.shouldSendChangedProfileField(
          wasLoaded: true,
          originalValue: '',
          currentValue: '',
        ),
        false,
      );
    });

    test('sends an intentionally cleared loaded optional value', () {
      expect(
        ApiService.shouldSendChangedProfileField(
          wasLoaded: true,
          originalValue: 'مستقل',
          currentValue: '',
        ),
        true,
      );
    });
  });


  group('Optional profile fields compatibility', () {
    test('parses profiles without a phone and ignores legacy start years', () {
      final profile = MemberProfileModel.fromJson({
        'id': 106,
        'basic_info': {
          'first_name': 'سارة',
          'last_name': 'محمد',
          'gender': '2',
          'phone': null,
        },
        'education': {
          'degree': 'بكالوريوس',
          'institution': 'جامعة دمشق',
          'start': 2018,
          'education_start': 2018,
        },
        'career': {
          'designation': 'مصممة',
          'company': '',
          'start': 2021,
          'career_start': 2021,
        },
      });
      expect(profile.basicInfo.phone, isEmpty);
      expect(profile.education?.degree, 'بكالوريوس');
      expect(profile.education?.institution, 'جامعة دمشق');
      expect(profile.career?.designation, 'مصممة');
      expect(profile.career?.company, isEmpty);
    });
  });

}

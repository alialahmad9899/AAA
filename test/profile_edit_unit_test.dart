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

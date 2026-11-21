import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/member_profile_link.dart';

void main() {
  group('MemberProfileLink', () {
    test('web share link keeps the public membership number', () {
      expect(
        MemberProfileLink.webUrl('20260358'),
        'https://www.wesalsyria.com/profile?id=20260358',
      );
    });

    test('Android intent targets the installed package with web fallback', () {
      final link = MemberProfileLink.androidIntentUrl('20260358');
      expect(link, contains('package=com.example.wesal_app'));
      expect(link, contains('scheme=wesal'));
      expect(link, contains('S.browser_fallback_url='));
      expect(link, contains('id%3D20260358'));
    });

    test('HTTPS profile link accepts member_number parameter', () {
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('https://www.wesalsyria.com/profile?member_number=20260358'),
        ),
        20260358,
      );
    });

    test('custom Wesal link resolves public member number', () {
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('wesal://profile?id=20260358'),
        ),
        20260358,
      );
    });

    test('generated Android intent can be parsed consistently', () {
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse(MemberProfileLink.androidIntentUrl('20260358')),
        ),
        20260358,
      );
    });

    test('profile path identifiers are accepted', () {
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('https://www.wesalsyria.com/profile/20260358'),
        ),
        20260358,
      );
    });

    test('Arabic and Persian digits are normalized before parsing', () {
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('wesal://profile?id=٢٠٢٦٠٣٥٨'),
        ),
        20260358,
      );
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('wesal://profile?id=۲۰۲۶۰۳۵۸'),
        ),
        20260358,
      );
    });

    test('unrelated and malformed links are rejected', () {
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('https://www.wesalsyria.com/members?id=20260358'),
        ),
        isNull,
      );
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('https://www.wesalsyria.com/profile?id=not-a-number'),
        ),
        isNull,
      );
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('wesal://settings?id=20260358'),
        ),
        isNull,
      );
    });
  });
}

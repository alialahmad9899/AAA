import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/member_profile_link.dart';

void main() {
  group('MemberProfileLink', () {
    test('web link preserves the requested membership reference', () {
      expect(
        MemberProfileLink.webUrl(20260358),
        'https://www.wesalsyria.com/profile?id=20260358',
      );
    });

    test('Android intent targets the Flutter app and keeps a web fallback', () {
      final link = MemberProfileLink.androidIntentUrl(20260358);
      expect(link, contains('intent://profile?id=20260358#Intent;'));
      expect(link, contains('scheme=wesal;'));
      expect(link, contains('package=com.example.wesal_app;'));
      expect(
        link,
        contains(
          'S.browser_fallback_url=https%3A%2F%2Fwww.wesalsyria.com%2Fprofile%3Fid%3D20260358',
        ),
      );
      expect(link, endsWith(';end'));
    });
  });
}

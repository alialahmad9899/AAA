import 'package:flutter_test/flutter_test.dart';

import '../lib/models/app_announcement.dart';

void main() {
  group('AppAnnouncement', () {
    test('parses the announcement and its optional action button', () {
      final announcement = AppAnnouncement.fromJson({
        'id': 12,
        'type': 'app_update',
        'title': 'تحديث جديد',
        'message': 'يتوفر إصدار جديد من وصال.',
        'button_enabled': true,
        'button_text': 'تحديث الآن',
        'button_url': 'https://play.google.com/store/apps/details?id=com.wesal',
      });

      expect(announcement.id, 12);
      expect(announcement.type, 'app_update');
      expect(announcement.title, 'تحديث جديد');
      expect(announcement.hasContent, isTrue);
      expect(announcement.hasButton, isTrue);
    });

    test('hides a disabled, absent, or unsafe action button', () {
      final disabled = AppAnnouncement.fromJson({
        'title': 'ملاحظة',
        'message': 'نص الملاحظة',
        'button_enabled': false,
        'button_text': 'افتح',
        'button_url': 'https://example.com',
      });
      final unsafe = AppAnnouncement.fromJson({
        'title': 'ملاحظة',
        'message': 'نص الملاحظة',
        'button_enabled': true,
        'button_text': 'افتح',
        'button_url': 'javascript:alert(1)',
      });

      expect(disabled.hasButton, isFalse);
      expect(unsafe.hasButton, isFalse);
    });

    test('supports a popup that has no action button', () {
      final announcement = AppAnnouncement.fromJson({
        'title': 'تنبيه',
        'message': 'سيتم تحديث الخدمة الليلة.',
        'button_enabled': false,
      });

      expect(announcement.hasContent, isTrue);
      expect(announcement.hasButton, isFalse);
    });
  });
}

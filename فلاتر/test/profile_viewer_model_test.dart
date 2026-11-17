import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/models/profile_viewer_model.dart';

void main() {
  group('ProfileViewerModel.tryParse', () {
    test('ignores null and non-map visitor records', () {
      expect(ProfileViewerModel.tryParse(null, isArabic: true), isNull);
      expect(ProfileViewerModel.tryParse('invalid', isArabic: true), isNull);
      expect(ProfileViewerModel.tryParse(42, isArabic: true), isNull);
    });

    test('parses numeric identifiers and ages supplied as strings', () {
      final viewer = ProfileViewerModel.tryParse(
        {
          'user_id': '58',
          'name': 'زائر وصال',
          'photo': 'https://cdn.example.com/avatar.jpg',
          'age': '34',
          'date': 'منذ ساعتين',
        },
        isArabic: true,
      );

      expect(viewer, isNotNull);
      expect(viewer!.userId, 58);
      expect(viewer.name, 'زائر وصال');
      expect(viewer.photo, 'https://cdn.example.com/avatar.jpg');
      expect(viewer.age, 34);
      expect(viewer.visitedAt, 'منذ ساعتين');
    });

    test('uses alternate API keys and ignores malformed optional fields', () {
      final viewer = ProfileViewerModel.tryParse(
        {
          'viewed_by': 73,
          'user_name': 100,
          'first_name': 'Member',
          'last_name': 'Seven',
          'user_photo': 99,
          'age': 'unknown',
          'time': 123,
        },
        isArabic: false,
      );

      expect(viewer, isNotNull);
      expect(viewer!.userId, 73);
      expect(viewer.name, 'Member Seven');
      expect(viewer.photo, isEmpty);
      expect(viewer.age, 0);
      expect(viewer.visitedAt, isEmpty);
    });

    test('rejects records that cannot identify a visitor', () {
      expect(
        ProfileViewerModel.tryParse(
          {'user_id': 'not-an-id', 'name': 'Unknown'},
          isArabic: true,
        ),
        isNull,
      );
    });

    test('falls back to localized member name and safe image state', () {
      final viewer = ProfileViewerModel.tryParse(
        {
          'id': 81,
          'name': '',
          'photo': 'not a valid network URL',
          'age': 155,
        },
        isArabic: true,
      );

      expect(viewer, isNotNull);
      expect(viewer!.name, 'عضو');
      expect(viewer.photo, isEmpty);
      expect(viewer.age, 0);
    });
  });
}

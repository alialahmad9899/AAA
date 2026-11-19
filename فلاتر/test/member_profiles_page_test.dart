import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/models/member_model.dart';

void main() {
  group('MemberProfilesPage', () {
    test('parses member rows and pagination metadata', () {
      final page = MemberProfilesPage.fromJson({
        'result': true,
        'data': [
          {'user_id': 41, 'first_name': 'Rana', 'last_name': 'Ali'},
          {'user_id': 42, 'first_name': 'Maya', 'last_name': 'Khaled'},
        ],
        'pagination': {
          'current_page': 2,
          'per_page': 20,
          'has_more': true,
        },
      }, fallbackPage: 1);
      expect(page.currentPage, 2);
      expect(page.hasMore, isTrue);
      expect(page.members.map((member) => member.id).toList(), [41, 42]);
      expect(page.members.first.name, 'Rana Ali');
    });

    test('preserves an empty final page', () {
      final page = MemberProfilesPage.fromJson({
        'result': true,
        'data': <dynamic>[],
        'pagination': {
          'current_page': 4,
          'per_page': 20,
          'has_more': false,
        },
      });
      expect(page.currentPage, 4);
      expect(page.hasMore, isFalse);
      expect(page.members, isEmpty);
    });

    test('accepts Laravel next_page_url metadata', () {
      final page = MemberProfilesPage.fromJson({
        'data': [
          {'user_id': 55, 'first_name': 'Lina'},
        ],
        'meta': {
          'current_page': 1,
          'next_page_url': 'https://api.example.test/member/matched-profile?page=2',
        },
      });
      expect(page.hasMore, isTrue);
      expect(page.members.single.id, 55);
    });
  });
}

import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/models/member_model.dart';

void main() {
  group('MemberModel interest state', () {
    test('reads Laravel is_interested boolean', () {
      expect(MemberModel.fromJson({'id': 7, 'is_interested': true}).isInterested, isTrue);
    });
    test('supports legacy interest_status numeric value', () {
      expect(MemberModel.fromJson({'id': 8, 'interest_status': 1}).isInterested, isTrue);
    });
    test('does not mark uninterested members', () {
      expect(MemberModel.fromJson({'id': 9, 'is_interested': false, 'interest_status': 0}).isInterested, isFalse);
    });
  });
}

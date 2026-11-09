import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/models/member_model.dart';
import 'package:wesal_app/models/member_profile_model.dart';

void main() {
  group('interest state parsing', () {
    test('match cards read the viewer-specific is_interested flag', () {
      final liked = MemberModel.fromJson({
        'user_id': 41,
        'is_interested': true,
      });
      final notLiked = MemberModel.fromJson({
        'user_id': 42,
        'is_interested': false,
      });

      expect(liked.isInterested, isTrue);
      expect(notLiked.isInterested, isFalse);
    });

    test('a profile owner quota does not imply that the viewer liked them', () {
      final profile = MemberProfileModel.fromJson({
        'id': 51,
        'basic_info': {
          'can_express_interest': false,
          'remaining_interests': 0,
        },
      });

      expect(profile.isInterested, isFalse);
      expect(profile.canExpressInterest, isTrue);
    });

    test('public profile uses root-level viewer interest state', () {
      final profile = MemberProfileModel.fromJson({
        'id': 52,
        'basic_info': {
          'can_express_interest': false,
          'remaining_interests': 0,
        },
        'is_interested': true,
        'can_express_interest': false,
        'remaining_interests': 0,
      });

      expect(profile.isInterested, isTrue);
      expect(profile.canExpressInterest, isFalse);
      expect(profile.remainingInterests, 0);
    });
  });
}

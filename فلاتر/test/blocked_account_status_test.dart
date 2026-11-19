import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/models/blocked_account_status.dart';

void main() {
  test('parses blocked account and preserves admin blocking reason', () {
    final status = BlockedAccountStatus.fromJson({
      'result': true,
      'blocked': 1,
      'blocking_reason': 'مخالفة قواعد وشروط الاستخدام',
    });

    expect(status.isBlocked, isTrue);
    expect(status.reason, 'مخالفة قواعد وشروط الاستخدام');
  });

  test('parses nested API status and boolean blocked flag', () {
    final status = BlockedAccountStatus.fromJson({
      'result': true,
      'data': {
        'blocked': true,
        'blocked_reason': 'Repeated abuse',
      },
    });

    expect(status.isBlocked, isTrue);
    expect(status.reason, 'Repeated abuse');
  });

  test('does not mark an unblocked account as blocked', () {
    final status = BlockedAccountStatus.fromJson({
      'result': true,
      'blocked': 0,
      'blocking_reason': '',
    });

    expect(status.isBlocked, isFalse);
    expect(status.reason, isEmpty);
  });
}

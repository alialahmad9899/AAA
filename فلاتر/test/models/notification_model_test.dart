import 'package:flutter_test/flutter_test.dart';

import '../../lib/models/notification_model.dart';

void main() {
  group('NotificationModel routing metadata', () {
    test('retains actor, invoice ID and deep-link route from API payload', () {
      final notification = NotificationModel.fromJson({
        'id': 'notif-42',
        'type': 'package',
        'notify_by': 9,
        'info_id': 381,
        'route': 'wesal://package-invoice/381',
        'title': 'تفعيل الباقة',
        'message': 'تم تفعيل الباقة',
        'read_at': null,
      });

      expect(notification.notificationId, 'notif-42');
      expect(notification.infoId, '381');
      expect(notification.route, 'wesal://package-invoice/381');
      expect(notification.toRoutePayload(), containsPair('notify_by', '9'));
      expect(notification.toRoutePayload(), containsPair('info_id', '381'));
    });

    test('accepts explicit invoice metadata aliases', () {
      final notification = NotificationModel.fromJson({
        'notification_id': 'notif-43',
        'type': 'package_purchase',
        'payment_id': 501,
        'notify_by': '0',
        'title': 'فاتورة',
        'message': 'تفاصيل الفاتورة',
        'read_at': '2026-11-18 01:00:00',
      });

      expect(notification.infoId, '501');
    });
  });
}

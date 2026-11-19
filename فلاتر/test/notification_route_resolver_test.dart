import 'package:flutter_test/flutter_test.dart';

import 'package:wesal_app/utils/notification_route_resolver.dart';

void main() {
  group('notification destination resolver', () {
    test('resolves package invoice from an explicit payment ID in the payload', () {
      final result = resolveNotificationDestination({
        'type': 'package_activated',
        'info_id': '16',
        'payment_id': '501',
      });

      expect(result?.kind, NotificationDestinationKind.packageInvoice);
      expect(result?.id, 501);
    });

    test('does not mistake a legacy package info_id for a payment ID', () {
      final result = resolveNotificationDestination({
        'type': 'package',
        'info_id': '16',
      });

      expect(result, isNull);
    });

    test('resolves package invoice from the Wesal deep link alone', () {
      final result = resolveNotificationDestination({
        'type': 'admin_notice',
        'route': 'wesal://package-invoice/502',
      });

      expect(result?.kind, NotificationDestinationKind.packageInvoice);
      expect(result?.id, 502);
    });

    test('resolves member profile from a custom Wesal deep link', () {
      final result = resolveNotificationDestination({
        'type': 'admin_notice',
        'route': 'wesal://member/73',
      });

      expect(result?.kind, NotificationDestinationKind.profile);
      expect(result?.id, 73);
    });

    test('resolves profile IDs from a https profile link', () {
      final result = resolveNotificationDestination({
        'route': 'https://www.wesalsyria.com/profile?id=74',
      });

      expect(result?.kind, NotificationDestinationKind.profile);
      expect(result?.id, 74);
    });

    test('resolves support and chat deep links', () {
      final support = resolveNotificationDestination({
        'route': 'wesal://support-ticket/909',
      });
      final chat = resolveNotificationDestination({
        'route': 'wesal://chat/109',
      });

      expect(support?.kind, NotificationDestinationKind.supportTicket);
      expect(support?.id, 909);
      expect(chat?.kind, NotificationDestinationKind.chat);
      expect(chat?.id, 109);
    });

    test('keeps interest notifications in the interests screen without actor ID', () {
      final result = resolveNotificationDestination({
        'notification_type': 'express_interest',
      });

      expect(result?.kind, NotificationDestinationKind.interests);
      expect(result?.id, isNull);
    });

    test('does not open unknown or arbitrary external routes', () {
      expect(
        resolveNotificationDestination({
          'type': 'admin_notice',
          'route': 'https://example.com/account/123',
        }),
        isNull,
      );
    });
  });
}

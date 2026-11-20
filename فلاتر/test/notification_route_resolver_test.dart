import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/notification_route_resolver.dart';

void main() {
  group('notification destination resolver', () {
    test('resolves package invoice from explicit payment ID', () {
      final result=resolveNotificationDestination({'type':'package','info_id':'16','payment_id':'501'});
      expect(result?.kind,NotificationDestinationKind.packageInvoice); expect(result?.id,501);
    });
    test('uses purchase history instead of guessing from info_id', () {
      final result=resolveNotificationDestination({'type':'package','info_id':'16'});
      expect(result?.kind,NotificationDestinationKind.packagePurchaseHistory); expect(result?.id,isNull);
    });
    test('resolves trusted invoice deep link', () {
      final result=resolveNotificationDestination({'type':'package','info_id':'16','route':'wesal://package-invoice/502'});
      expect(result?.kind,NotificationDestinationKind.packageInvoice); expect(result?.id,502);
    });
    test('resolves explicit invoice ID with generic type', () {
      final result=resolveNotificationDestination({'type':'admin_notice','payment_id':'503'});
      expect(result?.kind,NotificationDestinationKind.packageInvoice); expect(result?.id,503);
    });
    test('resolves profile visit to visitor profile', () {
      final result=resolveNotificationDestination({'type':'profile_view','viewer_id':'74'});
      expect(result?.kind,NotificationDestinationKind.profile); expect(result?.id,74);
    });
    test('routes profile visit without viewer ID to visitor list', () {
      final result=resolveNotificationDestination({'type':'profile_view'});
      expect(result?.kind,NotificationDestinationKind.profileVisitors); expect(result?.id,isNull);
    });
    test('routes like to actor profile', () {
      final result=resolveNotificationDestination({'type':'like','notify_by':'84'});
      expect(result?.kind,NotificationDestinationKind.profile); expect(result?.id,84);
    });
    test('routes like without actor to interest requests', () {
      final result=resolveNotificationDestination({'notification_type':'express_interest'});
      expect(result?.kind,NotificationDestinationKind.interests); expect(result?.id,isNull);
    });
    test('keeps support/chat deep links', () {
      final support=resolveNotificationDestination({'route':'wesal://support-ticket/909'});
      final chat=resolveNotificationDestination({'route':'wesal://chat/109'});
      expect(support?.kind,NotificationDestinationKind.supportTicket); expect(support?.id,909);
      expect(chat?.kind,NotificationDestinationKind.chat); expect(chat?.id,109);
    });
    test('rejects unknown external routes', () {
      expect(resolveNotificationDestination({'type':'admin_notice','route':'https://example.com/account/123'}),isNull);
    });

    test('normalizes FCM and local notification taps to the same chat destination', () {
      final fcmDestination = resolveNotificationDestination({
        'type': 'chat_message',
        'thread_id': '51',
        'message_id': 'message-1',
      });
      final localDestination = resolveNotificationDestination({
        'route': 'wesal://chat/51',
        'notification_id': 'notification-1',
      });
      final fcmKey = notificationDestinationKey(fcmDestination);
      final localKey = notificationDestinationKey(localDestination);
      final active = <String>{};

      expect(fcmKey, 'chat:51');
      expect(localKey, fcmKey);
      expect(
        claimNotificationDestination(fcmKey, activeDestinations: active),
        isTrue,
      );
      expect(
        claimNotificationDestination(localKey, activeDestinations: active),
        isFalse,
      );

      releaseNotificationDestination(fcmKey, activeDestinations: active);
      expect(
        claimNotificationDestination(localKey, activeDestinations: active),
        isTrue,
      );
    });


    test('keeps null active chat IDs safe', () {
      final current = resolveNotificationDestination({
        'type': 'chat_message',
        'thread_id': '81',
      });

      expect(
        isNotificationDestinationAlreadyVisible(
          current,
          activeChatThreadId: null,
        ),
        isFalse,
      );
    });

    test('does not reopen the chat that is already visible', () {
      final current = resolveNotificationDestination({
        'type': 'chat_message',
        'thread_id': '81',
      });
      final other = resolveNotificationDestination({
        'type': 'chat_message',
        'thread_id': '82',
      });

      expect(
        isNotificationDestinationAlreadyVisible(
          current,
          activeChatThreadId: 81,
        ),
        isTrue,
      );
      expect(
        isNotificationDestinationAlreadyVisible(
          other,
          activeChatThreadId: 81,
        ),
        isFalse,
      );
      expect(
        isNotificationDestinationAlreadyVisible(
          current,
          activeChatThreadId: 0,
        ),
        isFalse,
      );
    });

    test('allows different chat threads to open independently', () {
      final first = notificationDestinationKey(
        resolveNotificationDestination({'type': 'chat_message', 'thread_id': '51'}),
      );
      final second = notificationDestinationKey(
        resolveNotificationDestination({'type': 'chat_message', 'thread_id': '52'}),
      );
      final active = <String>{};

      expect(first, isNot(second));
      expect(claimNotificationDestination(first, activeDestinations: active), isTrue);
      expect(claimNotificationDestination(second, activeDestinations: active), isTrue);
    });
  });
}

import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/chat_notification_utils.dart';

void main() {
  group('Chat notification policy', () {
    test('recognizes chat notifications without classifying other alerts', () {
      expect(isChatNotificationType({'type': 'chat_message'}), isTrue);
      expect(isChatNotificationType({'notification_type': 'new-message'}), isTrue);
      expect(isChatNotificationType({'type': 'conversation_update'}), isTrue);
      expect(isChatNotificationType({'type': 'wallet_credit'}), isFalse);
      expect(isChatNotificationType({'type': 'express_interest'}), isFalse);
    });

    test('extracts thread identifiers from compatible payload keys', () {
      expect(chatThreadIdFromNotification({'thread_id': '81'}), 81);
      expect(chatThreadIdFromNotification({'chat_thread_id': 92}), 92);
      expect(chatThreadIdFromNotification({'info_id': '103'}), 103);
      expect(chatThreadIdFromNotification({}), 0);
    });

    test('uses one stable local notification id per conversation', () {
      expect(chatNotificationId(91, 'message-a', 1), 91.hashCode);
      expect(chatNotificationId(0, 'message-a', 1), 'message-a'.hashCode);
    });

    test('parses silent flags sent through FCM data fields', () {
      expect(isSilentChatNotification('true'), isTrue);
      expect(isSilentChatNotification(1), isTrue);
      expect(isSilentChatNotification('false'), isFalse);
      expect(isSilentChatNotification(null), isFalse);
    });
  });
}

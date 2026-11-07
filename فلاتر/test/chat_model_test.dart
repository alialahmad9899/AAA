import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/models/chat_model.dart';

void main() {
  test('ChatThreadModel prefers exact last_message_at over relative text', () {
    final thread = ChatThreadModel.fromJson({
      'id': 7,
      'user_id': 18,
      'last_message_time': 'منذ 3 دقائق',
      'last_message_at': '2026-11-08T12:34:56+00:00',
    });
    expect(thread.time, '2026-11-08T12:34:56+00:00');
  });

  test('ChatMessageModel reads created_at and preserves sender/read state', () {
    final message = ChatMessageModel.fromJson({
      'id': 22,
      'sender_user_id': 18,
      'message': 'أهلاً',
      'created_at': '2026-11-08T12:30:00+00:00',
      'seen': 1,
    }, 18, currentUserId: 42);
    expect(message.time, '2026-11-08T12:30:00+00:00');
    expect(message.isMe, isFalse);
    expect(message.isSeen, isTrue);
  });

  test('ChatMessageModel prefers created_at when both time fields exist', () {
    final message = ChatMessageModel.fromJson({
      'id': 23,
      'sender_user_id': 42,
      'time': 'Now',
      'created_at': '2026-11-08T12:29:00+00:00',
    }, 18, currentUserId: 42);
    expect(message.time, '2026-11-08T12:29:00+00:00');
  });
}

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

  test('ChatThreadModel treats API active boolean as online', () {
    final thread = ChatThreadModel.fromJson({
      'id': 8,
      'user_id': 19,
      'active': true,
      'last_seen': 'منذ دقيقة',
    });

    expect(thread.isOnline, isTrue);
    expect(thread.lastSeen, 'منذ دقيقة');
  });

  test('ChatThreadModel preserves offline state when API marker is false', () {
    final thread = ChatThreadModel.fromJson({
      'id': 9,
      'user_id': 20,
      'active': false,
      'last_seen': 'منذ 4 دقائق',
    });

    expect(thread.isOnline, isFalse);
    expect(thread.lastSeen, 'منذ 4 دقائق');
  });
  test('ChatMessageModel newestFirst puts the latest and pending message at the bottom', () {
    ChatMessageModel message(int id) => ChatMessageModel(
          id: id,
          senderId: 42,
          message: 'message-$id',
          time: '2026-11-20T00:00:00Z',
          isMe: true,
          isSeen: false,
        );

    final chronological = [message(1), message(2), message(-7)];
    final rendered = ChatMessageModel.newestFirst(chronological);

    expect(rendered.map((item) => item.id).toList(), [-7, 2, 1]);
    expect(chronological.map((item) => item.id).toList(), [1, 2, -7]);
  });

  test('ChatMessageModel mergeHistory preserves older pages and pending messages', () {
    ChatMessageModel message(int id, String text) => ChatMessageModel(
          id: id,
          senderId: 42,
          message: text,
          time: '2026-11-20T00:00:00Z',
          isMe: true,
          isSeen: false,
        );

    final merged = ChatMessageModel.mergeHistory(
      [message(1, 'old copy'), message(2, 'older page'), message(-7, 'sending')],
      [message(1, 'server copy'), message(3, 'new message')],
    );

    expect(merged.map((item) => item.id).toList(), [1, 2, 3, -7]);
    expect(merged.first.message, 'server copy');
    expect(merged.last.message, 'sending');
  });

  test('chat photo sharing unlocks only after a persisted partner reply', () {
    ChatMessageModel message({
      required int id,
      required int senderId,
      required bool isMe,
    }) =>
        ChatMessageModel(
          id: id,
          senderId: senderId,
          message: 'message',
          time: '2026-11-21T00:00:00Z',
          isMe: isMe,
          isSeen: false,
        );

    expect(
      ChatMessageModel.hasPartnerReplied(
        [message(id: 1, senderId: 42, isMe: true)],
        partnerUserId: 18,
      ),
      isFalse,
    );
    expect(
      ChatMessageModel.hasPartnerReplied(
        [message(id: -1, senderId: 18, isMe: false)],
        partnerUserId: 18,
      ),
      isFalse,
    );
    expect(
      ChatMessageModel.hasPartnerReplied(
        [message(id: 2, senderId: 18, isMe: false)],
        partnerUserId: 18,
      ),
      isTrue,
    );
    expect(
      ChatMessageModel.hasPartnerReplied(
        [message(id: 3, senderId: 99, isMe: false)],
        partnerUserId: 18,
      ),
      isFalse,
    );
  });

  test('ChatMessageModel parses an API-confirmed message attachment resource', () {
    final message = ChatMessageModel.fromJson({
      'id': 24,
      'sender_user_id': 42,
      'message': 'صورة',
      'created_at': '2026-11-20T00:00:00Z',
      'attachment': [
        {'attachment': 'https://cdn.example.test/chat/photo.jpg'},
      ],
      'seen': 0,
    }, 18, currentUserId: 42);

    expect(message.id, 24);
    expect(message.isMe, isTrue);
    expect(message.attachmentUrl, 'https://cdn.example.test/chat/photo.jpg');
  });

  test('ChatThreadModel uses durable last_seen_at when formatted value is empty', () {
    final thread = ChatThreadModel.fromJson({
      'id': 10,
      'user_id': 21,
      'last_seen': '',
      'last_seen_at': '2026-11-20T01:20:00Z',
    });

    expect(thread.lastSeen, '2026-11-20T01:20:00Z');
  });

  test('ChatMessageModel mergeHistory deduplicates repeated server IDs', () {
    ChatMessageModel message(int id, String text) => ChatMessageModel(
          id: id,
          senderId: 42,
          message: text,
          time: '2026-11-20T00:00:00Z',
          isMe: true,
          isSeen: false,
        );

    final merged = ChatMessageModel.mergeHistory(
      [message(30, 'existing')],
      [
        message(31, 'new'),
        message(30, 'server-updated'),
        message(31, 'duplicate-latest'),
      ],
    );

    expect(merged.map((item) => item.id).toList(), [30, 31]);
    expect(merged.map((item) => item.message).toList(),
        ['server-updated', 'duplicate-latest']);
  });

}

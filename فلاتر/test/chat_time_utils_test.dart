import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/chat_time_utils.dart';

void main() {
  group('Chat timestamp parsing', () {
    final now = DateTime(2026, 11, 8, 15, 30);
    test('parses ISO-8601 values', () {
      final parsed = parseChatTimestamp('2026-11-08T13:45:00+00:00', now: now);
      expect(parsed, isNotNull);
      expect(parsed!.isUtc, isFalse);
    });
    test('keeps offset-aware ISO timestamps in device local time', () {
      final raw = '2026-11-08T13:45:00Z';
      expect(
        parseChatTimestamp(raw, now: now),
        DateTime.parse(raw).toLocal(),
      );
    });
    test('parses Arabic plural minutes', () {
      expect(parseChatTimestamp('منذ ٥ دقائق', now: now),
          now.subtract(const Duration(minutes: 5)));
    });
    test('parses Arabic plural seconds, days and weeks', () {
      expect(parseChatTimestamp('منذ ٣ ثواني', now: now),
          now.subtract(const Duration(seconds: 3)));
      expect(parseChatTimestamp('منذ ٢ أيام', now: now),
          now.subtract(const Duration(days: 2)));
      expect(parseChatTimestamp('منذ ٣ أسابيع', now: now),
          now.subtract(const Duration(days: 21)));
    });
    test('parses second-level labels', () {
      expect(parseChatTimestamp('3 seconds ago', now: now),
          now.subtract(const Duration(seconds: 3)));
    });
    test('returns null for empty and unrecognized values', () {
      expect(parseChatTimestamp(''), isNull);
      expect(parseChatTimestamp('not-a-time', now: now), isNull);
    });
  });
  group('Message timestamp formatting', () {
    final now = DateTime(2026, 11, 8, 15, 30);
    test('shows clock time for messages sent today', () {
      expect(formatChatMessageTime(DateTime(2026, 11, 8, 9, 7).toIso8601String(),
          isArabic: true, now: now), '09:07');
    });
    test('shows yesterday and clock time in both languages', () {
      final raw = DateTime(2026, 11, 7, 8, 5).toIso8601String();
      expect(formatChatMessageTime(raw, isArabic: true, now: now), 'أمس 08:05');
      expect(formatChatMessageTime(raw, isArabic: false, now: now), 'Yesterday 08:05');
    });
    test('shows date and clock time for older messages', () {
      expect(formatChatMessageTime(DateTime(2026, 10, 30, 18, 42).toIso8601String(),
          isArabic: false, now: now), '30/10 18:42');
    });
  });
}

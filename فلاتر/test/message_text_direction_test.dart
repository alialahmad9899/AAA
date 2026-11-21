import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/message_text_direction.dart';

void main() {
  group('MessageTextDirection', () {
    test('ignores leading punctuation before English text', () {
      expect(MessageTextDirection.of('.instantly'), TextDirection.ltr);
      expect(MessageTextDirection.of('...  (hello)'), TextDirection.ltr);
    });

    test('ignores emoji, spacing and punctuation before Arabic text', () {
      expect(MessageTextDirection.of('✨  ... مرحباً'), TextDirection.rtl);
    });

    test('uses the first strong letter for mixed-language messages', () {
      expect(MessageTextDirection.of('Hello مرحباً'), TextDirection.ltr);
      expect(MessageTextDirection.of('مرحبا Hello'), TextDirection.rtl);
    });

    test('defaults punctuation-only and numeric messages to left-to-right', () {
      expect(MessageTextDirection.of('... 12345 ⭐'), TextDirection.ltr);
      expect(MessageTextDirection.of(''), TextDirection.ltr);
    });
  });
}

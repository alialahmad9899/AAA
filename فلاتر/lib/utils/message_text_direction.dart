import 'package:flutter/widgets.dart';

/// Resolves the direction of a message from its first strong letter.
///
/// Whitespace, punctuation, digits and emoji are skipped, so leading
/// punctuation does not reverse the visual order of an English sentence.
class MessageTextDirection {
  const MessageTextDirection._();

  static TextDirection of(String text) {
    for (final rune in text.runes) {
      if (_isRightToLeftLetter(rune)) return TextDirection.rtl;
      if (_isLeftToRightLetter(rune)) return TextDirection.ltr;
    }
    return TextDirection.ltr;
  }

  static bool _isRightToLeftLetter(int value) {
    return (value >= 0x05D0 && value <= 0x05EA) ||
        (value >= 0x05EF && value <= 0x05F2) ||
        (value >= 0x0620 && value <= 0x063F) ||
        (value >= 0x0641 && value <= 0x064A) ||
        (value >= 0x066E && value <= 0x066F) ||
        (value >= 0x0671 && value <= 0x06D3) ||
        value == 0x06D5 ||
        (value >= 0x06E5 && value <= 0x06E6) ||
        (value >= 0x06EE && value <= 0x06EF) ||
        (value >= 0x06FA && value <= 0x06FC) ||
        value == 0x06FF ||
        (value >= 0x0710 && value <= 0x072F) ||
        (value >= 0x0780 && value <= 0x07A5) ||
        (value >= 0x08A0 && value <= 0x08C9) ||
        (value >= 0x08D3 && value <= 0x08FF) ||
        (value >= 0xFB1D && value <= 0xFDFF) ||
        (value >= 0xFE70 && value <= 0xFEFC);
  }

  static bool _isLeftToRightLetter(int value) {
    return (value >= 0x0041 && value <= 0x005A) ||
        (value >= 0x0061 && value <= 0x007A) ||
        (value >= 0x00C0 && value <= 0x02AF) ||
        (value >= 0x0370 && value <= 0x052F) ||
        (value >= 0x0900 && value <= 0x1FFF) ||
        (value >= 0x2C00 && value <= 0x2DFF) ||
        (value >= 0x3040 && value <= 0x30FF) ||
        (value >= 0x3400 && value <= 0x9FFF) ||
        (value >= 0xAC00 && value <= 0xD7AF) ||
        (value >= 0xF900 && value <= 0xFAFF);
  }
}

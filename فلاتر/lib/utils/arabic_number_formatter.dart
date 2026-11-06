// ============================================================================
// اسم الملف: lib/utils/arabic_to_english_numbers_formatter.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: كلاس مخصص لتحويل الأرقام العربية المشرقية والفارسية إلى إنجليزية
//        فور كتابتها مع الحفاظ التام على موضع المؤشر بدون قفز.
// ============================================================================

import 'package:flutter/services.dart';

/// كلاس مخصص لتحويل الأرقام العربية (المشرقية والفارسية) إلى أرقام إنجليزية فور كتابتها
class ArabicToEnglishNumbersFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    // إذا كان النص المدخل فارغاً، نُعيد القيمة كما هي مباشرة
    if (newValue.text.isEmpty) {
      return newValue;
    }

    bool hasArabicDigits = false;
    final StringBuffer buffer = StringBuffer();

    // فحص وتحويل الأرقام عبر فحص Unicode للحروف بمرور أحادي سريع للذاكرة
    for (int i = 0; i < newValue.text.length; i++) {
      final int code = newValue.text.codeUnitAt(i);

      // الأرقام العربية المشرقية (٠ - ٩)
      if (code >= 0x0660 && code <= 0x0669) {
        buffer.writeCharCode(code - 0x0660 + 0x0030);
        hasArabicDigits = true;
      }
      // الأرقام الفارسية / الأوردية (۰ - ۹)
      else if (code >= 0x06F0 && code <= 0x06F9) {
        buffer.writeCharCode(code - 0x06F0 + 0x0030);
        hasArabicDigits = true;
      } else {
        buffer.writeCharCode(code);
      }
    }

    // إذا لم يتضمن النص أي أرقام شرقية، نُعيد القيمة الأصلية لتفادي التعديل غير الضروري
    if (!hasArabicDigits) {
      return newValue;
    }

    final String convertedText = buffer.toString();

    // نُعيد القيمة المحوّلة مع الحفاظ التام على موقع المؤشر الأصلي (selection)
    return newValue.copyWith(
      text: convertedText,
      selection: newValue.selection,
    );
  }
}

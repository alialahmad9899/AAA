import 'package:flutter/material.dart';

/// واحد منسّق لاحتساب المساحة السفلية التي يجب أن تبقى خالية من عناصر الواجهة.
///
/// يجمع مساحة لوحة المفاتيح مع المساحة الآمنة المتبقية لشريط النظام.
/// تعتمد MediaQuery.padding.bottom أصلاً على viewInsets، لذلك لا نضيف
/// viewPadding مباشرة كي لا نحسب المنطقة نفسها مرتين عند فتح لوحة المفاتيح.
class WesalBottomInsets {
  const WesalBottomInsets._();

  static double of(BuildContext context) {
    final mediaQuery = MediaQuery.of(context);
    return mediaQuery.viewInsets.bottom + mediaQuery.padding.bottom;
  }

  static EdgeInsets padding(
    BuildContext context, {
    double additional = 0,
  }) {
    return EdgeInsets.only(bottom: of(context) + additional);
  }
}

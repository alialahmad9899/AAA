import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/constants/colors.dart';
import 'package:wesal_app/widgets/app_snackbar.dart';

void main() {
  testWidgets(
    'shows an explained RTL notification with accessible dark text and safe inset',
    (tester) async {
      final mediaQuery = MediaQueryData.fromView(tester.view).copyWith(
        viewPadding: const EdgeInsets.only(bottom: 24),
        padding: const EdgeInsets.only(bottom: 24),
        viewInsets: EdgeInsets.zero,
      );

      await tester.pumpWidget(
        MaterialApp(
          home: MediaQuery(
            data: mediaQuery,
            child: Scaffold(
              body: Builder(
                builder: (context) => Center(
                  child: ElevatedButton(
                    onPressed: () => AppSnackBar.show(
                      context: context,
                      type: NotificationType.error,
                      isArabic: true,
                      titleAr: 'تعذّر حفظ التعديلات',
                      titleEn: 'Could not save changes',
                      messageAr: 'لم نتمكن من حفظ بيانات الملف الشخصي.',
                      messageEn: 'We could not save your profile information.',
                      detailsAr: 'تحقق من الاتصال ثم حاول مجدداً.',
                      detailsEn: 'Check your connection and try again.',
                    ),
                    child: const Text('Show notification'),
                  ),
                ),
              ),
            ),
          ),
        ),
      );

      await tester.tap(find.text('Show notification'));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 400));

      expect(find.text('تعذّر حفظ التعديلات'), findsOneWidget);
      expect(find.text('لم نتمكن من حفظ بيانات الملف الشخصي.'), findsOneWidget);
      expect(find.text('تحقق من الاتصال ثم حاول مجدداً.'), findsOneWidget);

      final snackBar = tester.widget<SnackBar>(find.byType(SnackBar));
      expect(snackBar.margin?.bottom, 36);

      final title = tester.widget<Text>(find.text('تعذّر حفظ التعديلات'));
      expect(title.style?.color, AppColors.textDark);
    },
  );
}

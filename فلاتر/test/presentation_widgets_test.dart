import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/widgets/app_action_bottom_sheet.dart';
import 'package:wesal_app/widgets/app_empty_state.dart';

void main() {
  testWidgets('action bottom sheet invokes its primary action',
      (tester) async {
    var actionCalled = false;
    await tester.pumpWidget(
      MaterialApp(
        home: Builder(
          builder: (context) => Scaffold(
            body: Center(
              child: TextButton(
                onPressed: () {
                  AppActionBottomSheet.show(
                    context: context,
                    isArabic: true,
                    title: 'تنبيه تجريبي',
                    message: 'شرح مختصر وواضح للإجراء.',
                    icon: Icons.info_outline_rounded,
                    primaryLabel: 'متابعة',
                    onPrimaryPressed: () => actionCalled = true,
                  );
                },
                child: const Text('Open'),
              ),
            ),
          ),
        ),
      ),
    );
    await tester.tap(find.text('Open'));
    await tester.pumpAndSettle();
    expect(find.text('تنبيه تجريبي'), findsOneWidget);
    expect(find.text('شرح مختصر وواضح للإجراء.'), findsOneWidget);
    expect(find.text('متابعة'), findsOneWidget);
    await tester.tap(find.text('متابعة'));
    await tester.pumpAndSettle();
    expect(actionCalled, isTrue);
  });

  testWidgets('empty state presents guidance and optional action',
      (tester) async {
    var actionCalled = false;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: AppEmptyState(
            icon: Icons.search_off_rounded,
            title: 'لا توجد نتائج',
            message: 'جرّب تغيير خيارات البحث.',
            actionLabel: 'تعديل البحث',
            onAction: () => actionCalled = true,
          ),
        ),
      ),
    );
    expect(find.text('لا توجد نتائج'), findsOneWidget);
    expect(find.text('جرّب تغيير خيارات البحث.'), findsOneWidget);
    await tester.tap(find.text('تعديل البحث'));
    expect(actionCalled, isTrue);
  });
}

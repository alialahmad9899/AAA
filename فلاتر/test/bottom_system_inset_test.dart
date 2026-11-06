import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/widgets/bottom_system_inset.dart';

void main() {
  Future<double> readBottomInset(
    WidgetTester tester,
    MediaQueryData data,
  ) async {
    late double result;

    await tester.pumpWidget(
      MaterialApp(
        home: MediaQuery(
          data: data,
          child: Builder(
            builder: (context) {
              result = WesalBottomInsets.of(context);
              return const SizedBox.shrink();
            },
          ),
        ),
      ),
    );

    return result;
  }

  testWidgets('reserves the system navigation inset when keyboard is closed',
      (tester) async {
    final data = MediaQueryData.fromView(tester.view).copyWith(
      viewPadding: const EdgeInsets.only(bottom: 24),
      padding: const EdgeInsets.only(bottom: 24),
      viewInsets: EdgeInsets.zero,
    );

    expect(await readBottomInset(tester, data), 24);
  });

  testWidgets('uses keyboard inset without adding the same system inset twice',
      (tester) async {
    final data = MediaQueryData.fromView(tester.view).copyWith(
      viewPadding: const EdgeInsets.only(bottom: 24),
      padding: EdgeInsets.zero,
      viewInsets: const EdgeInsets.only(bottom: 280),
    );

    expect(await readBottomInset(tester, data), 280);
  });

  testWidgets('combines partially overlapping keyboard and system insets',
      (tester) async {
    final data = MediaQueryData.fromView(tester.view).copyWith(
      viewPadding: const EdgeInsets.only(bottom: 24),
      padding: const EdgeInsets.only(bottom: 14),
      viewInsets: const EdgeInsets.only(bottom: 10),
    );

    expect(await readBottomInset(tester, data), 24);
  });
}

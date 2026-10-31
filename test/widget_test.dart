import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/main.dart';

void main() {
  testWidgets('Wesal app opens the welcome screen for a signed-out user',
      (WidgetTester tester) async {
    await tester.pumpWidget(const MyApp(isLoggedIn: false));

    expect(find.byType(MaterialApp), findsOneWidget);

    // The welcome screen checks secure storage before rendering its actions.
    await tester.pump(const Duration(seconds: 1));

    expect(find.text('تطبيق وصال'), findsOneWidget);
    expect(find.text('منصتك الآمنة للتوافق والتواصل الراقٍ'), findsOneWidget);
  });
}

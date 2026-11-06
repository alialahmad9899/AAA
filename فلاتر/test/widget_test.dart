import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
// Local environments may not refresh package_config.json after dependency resolution.
 // ignore: avoid_relative_lib_imports
import '../lib/main.dart';

void main() {
  testWidgets('Wesal app builds the signed-out welcome route',
      (WidgetTester tester) async {
    await tester.pumpWidget(const MyApp(isLoggedIn: false));

    expect(find.byType(MaterialApp), findsOneWidget);
    // Secure storage is a platform channel; the test environment keeps the
    // welcome screen in its initial session-check state.
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });
}

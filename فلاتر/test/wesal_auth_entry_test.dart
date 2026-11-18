import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/screens/login_screen.dart';
import 'package:wesal_app/screens/signup_screen.dart';
import 'package:wesal_app/screens/welcome_screen.dart';
import 'package:wesal_app/widgets/wesal_logo.dart';

void main() {
  const secureStorageChannel =
      MethodChannel('plugins.it_nomads.com/flutter_secure_storage');

  setUp(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(secureStorageChannel, (call) async => null);
  });

  tearDown(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(secureStorageChannel, null);
  });

  Future<void> setPhoneSize(WidgetTester tester) async {
    await tester.binding.setSurfaceSize(const Size(390, 844));
  }

  testWidgets('login shows one centered logo above centered welcome copy',
      (WidgetTester tester) async {
    await setPhoneSize(tester);
    await tester.pumpWidget(const MaterialApp(home: LoginScreen()));

    final logoFinder = find.byType(WesalLogo);
    expect(logoFinder, findsOneWidget);
    expect(tester.getCenter(logoFinder).dx, closeTo(195, 1));

    final heading = tester.widget<Text>(
      find.text('أهلاً بعودتك إلى وصال'),
    );
    expect(heading.textAlign, TextAlign.center);

    final description = tester.widget<Text>(
      find.text('سجّل دخولك وتابع رحلتك نحو تعارف أكثر وضوحاً وراحة.'),
    );
    expect(description.textAlign, TextAlign.center);
    expect(tester.takeException(), isNull);

    await tester.binding.setSurfaceSize(null);
  });

  testWidgets('signup keeps the logo and hero heading centered',
      (WidgetTester tester) async {
    await setPhoneSize(tester);
    await tester.pumpWidget(const MaterialApp(home: SignupScreen()));

    final logoFinder = find.byType(WesalLogo);
    expect(logoFinder, findsOneWidget);
    expect(tester.getCenter(logoFinder).dx, closeTo(195, 1));

    final heading = tester.widget<Text>(
      find.text('لنبدأ رحلتك مع وصال'),
    );
    expect(heading.textAlign, TextAlign.center);
    expect(tester.takeException(), isNull);

    await tester.binding.setSurfaceSize(null);
  });

  testWidgets('welcome hero gives the original logo a wide contain box',
      (WidgetTester tester) async {
    await setPhoneSize(tester);
    await tester.pumpWidget(const MaterialApp(home: WelcomeScreen()));
    await tester.pumpAndSettle();

    final logoFinder = find.byType(WesalLogo);
    expect(logoFinder, findsOneWidget);
    final logo = tester.widget<WesalLogo>(logoFinder);
    expect(logo.fit, BoxFit.contain);
    expect(logo.width, greaterThanOrEqualTo(220));
    expect(logo.height, lessThanOrEqualTo(145));
    expect(tester.takeException(), isNull);

    await tester.binding.setSurfaceSize(null);
  });
}
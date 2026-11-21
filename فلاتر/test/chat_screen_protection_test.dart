import 'dart:io';

import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/services/chat_screen_protection.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const channel = MethodChannel('com.wesalsyria.wesal/chat_security');

  tearDown(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, null);
  });

  test('requests Android secure-window enable and disable', () async {
    final calls = <bool>[];
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async {
      expect(call.method, 'setSecure');
      calls.add(call.arguments['enabled'] as bool);
      return true;
    });

    expect(
      await ChatScreenProtection.setEnabled(true, androidOverride: true),
      isTrue,
    );
    expect(
      await ChatScreenProtection.setEnabled(false, androidOverride: true),
      isTrue,
    );
    expect(calls, [true, false]);
  });

  test('Android activity sets and clears FLAG_SECURE around the chat route', () {
    final source = File(
      'android/app/src/main/kotlin/com/example/wesal_app/MainActivity.kt',
    ).readAsStringSync();
    expect(source, contains('WindowManager.LayoutParams.FLAG_SECURE'));
    expect(source, contains('activityWindow.setFlags(secureFlag, secureFlag)'));
    expect(source, contains('activityWindow.clearFlags(secureFlag)'));
  });

  test('does not claim screenshot prevention on iOS', () async {
    var invoked = false;
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async {
      invoked = true;
      return true;
    });

    expect(
      await ChatScreenProtection.setEnabled(true, androidOverride: false),
      isFalse,
    );
    expect(invoked, isFalse);
  });
}

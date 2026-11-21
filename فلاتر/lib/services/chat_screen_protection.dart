import 'dart:io';

import 'package:flutter/services.dart';

/// Enables native screenshot/recording protection while a chat route is open.
/// Android uses FLAG_SECURE. iOS cannot prevent the system screenshot action.
class ChatScreenProtection {
  ChatScreenProtection._();

  static const MethodChannel _channel =
      MethodChannel('com.wesalsyria.wesal/chat_security');

  static Future<bool> setEnabled(
    bool enabled, {
    bool? androidOverride,
  }) async {
    final supportedPlatform = androidOverride ?? Platform.isAndroid;
    if (!supportedPlatform) return false;

    try {
      return await _channel.invokeMethod<bool>(
            'setSecure',
            <String, Object>{'enabled': enabled},
          ) ??
          false;
    } on PlatformException {
      return false;
    } on MissingPluginException {
      return false;
    }
  }
}

import 'package:flutter/foundation.dart';

/// Shared in-memory marker used to suppress a chat alert while that exact
/// conversation is visible and actively being read in the foreground.
class ChatActivityState {
  ChatActivityState._();

  static final ValueNotifier<int?> activeThreadId = ValueNotifier<int?>(null);
}

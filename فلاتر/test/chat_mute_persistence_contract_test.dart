import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  late String localStore;
  late String apiService;
  late String chatScreen;
  late String notificationController;
  late String chatService;
  late String chatController;
  late String memberController;
  late String migration;

  setUpAll(() {
    localStore = File('lib/services/chat_local_store.dart').readAsStringSync();
    apiService = File('lib/services/api_service.dart').readAsStringSync();
    chatScreen = File('lib/screens/chat_detail_screen.dart').readAsStringSync();
    notificationController = File('../المشروع/WESAL/app/Http/Controllers/Api/NotificationController.php').readAsStringSync();
    chatService = File('../المشروع/WESAL/app/Services/ChatService.php').readAsStringSync();
    chatController = File('../المشروع/WESAL/app/Http/Controllers/Api/ChatController.php').readAsStringSync();
    memberController = File('../المشروع/WESAL/app/Http/Controllers/Api/MemberController.php').readAsStringSync();
    migration = File('../المشروع/WESAL/database/migrations/2026_11_22_000002_create_chat_mutes_table.php').readAsStringSync();
  });

  test('local mute state is keyed by partner and migrated from thread preferences', () {
    expect(localStore, contains('chat_mute_preferences'));
    expect(localStore, contains('partner_user_id'));
    expect(localStore, contains('final legacyMutes = await db.rawQuery'));
    expect(localStore, contains('version: 2'));
  });

  test('API sync and chat screen mute use the partner identity, not the thread ID', () {
    expect(apiService, contains("'partner_user_id': partnerUserId"));
    expect(apiService, contains('setChatMuted(partnerUserId: entry.key'));
    expect(chatScreen, contains('ChatLocalStore.isMuted(\n          _currentUserId,\n          widget.thread.userId'));
    expect(chatScreen, contains('ChatLocalStore.setMuted(\n        _currentUserId,\n        widget.thread.userId'));
    expect(chatScreen, contains('partnerUserId: widget.thread.userId'));
    expect(chatScreen, contains("_isChatMuted = res['muted_by_me'] == true"));
  });

  test('server stores the mute per user pair and suppresses pushes when muted or blocked', () {
    expect(notificationController, contains('ChatMute::updateOrCreate'));
    expect(notificationController, contains(r"'muted_by' => $userId"));
    expect(notificationController, contains(r"'muted_user_id' => $partnerUserId"));
    expect(chatService, contains('ChatMute::query()'));
    expect(chatService, contains('IgnoredUser::query()'));
    expect(chatService, contains('|| $isMuted || $isBlocked'));
    expect(chatController, contains("'muted_by_me'"));
  });

  test('block removal does not delete the independent mute preference', () {
    final start = memberController.indexOf('public function remove_from_ignored_list');
    final end = memberController.indexOf('public function report_member', start);
    expect(start, isNonNegative);
    expect(end, greaterThan(start));
    final method = memberController.substring(start, end);
    expect(method, contains('IgnoredUser::destroy'));
    expect(method, isNot(contains('ChatMute::')));
    expect(migration, contains('unique([\'muted_by\', \'muted_user_id\']'));
  });
}

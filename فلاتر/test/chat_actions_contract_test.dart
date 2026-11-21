import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  late String source;
  late String chatsTabSource;

  setUpAll(() {
    source = File('lib/screens/chat_detail_screen.dart').readAsStringSync();
    chatsTabSource = File('lib/screens/home/chats_tab.dart').readAsStringSync();
  });

  test('manual chat deletion stays behind the explicit confirmation dialog', () {
    expect(source, contains('_showDeleteChatConfirmationDialog();'));
    expect(source, contains('هل أنت متأكد من حذف سجل المحادثة'));
    expect(source, contains('_confirmDeleteChat();'));
    expect(
      RegExp(r'ApiService\.deleteChatThread\(').allMatches(source).length,
      1,
    );
  });

  test('chat-list swipe and long-press deletion require confirmation', () {
    expect(chatsTabSource, contains('confirmDismiss:'));
    expect(chatsTabSource, contains('_showDeleteConfirmationDialog(chat)'));
    expect(chatsTabSource, contains('if (confirm == true)'));
    expect(chatsTabSource, contains('_executeChatDeletion(chat)'));
  });

  test('blocking preserves the remote and local conversation history', () {
    final start = source.indexOf('Future<void> _executeBlock() async {');
    final end = source.indexOf('Future<void> _executeReport(String reason)', start);
    expect(start, isNonNegative);
    expect(end, greaterThan(start));
    final method = source.substring(start, end);
    expect(method, isNot(contains('deleteChatThread')));
    expect(method, isNot(contains('ChatLocalStore.deleteThread')));
    expect(method, contains('ApiService.addToIgnoreList'));
  });

  test('reporting attaches the real thread ID and does not destroy the chat', () {
    final start = source.indexOf('Future<void> _executeReport(String reason)');
    final end = source.indexOf('void _showDeleteChatConfirmationDialog()', start);
    expect(start, isNonNegative);
    expect(end, greaterThan(start));
    final method = source.substring(start, end);
    expect(method, contains('chatThreadId: _activeThreadId'));
    expect(method, isNot(contains('deleteChatThread')));
    expect(method, isNot(contains('ChatLocalStore.deleteThread')));
  });
}

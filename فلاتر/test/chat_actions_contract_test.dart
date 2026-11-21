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
    expect(source, contains('_confirmDeleteChat();'));
    expect(RegExp(r'ApiService\.deleteChatThread\(').allMatches(source).length, 1);
  });

  test('chat-list swipe and long-press deletion require confirmation', () {
    expect(chatsTabSource, contains('confirmDismiss:'));
    expect(chatsTabSource, contains('_showDeleteConfirmationDialog(chat)'));
    expect(chatsTabSource, contains('if (confirm == true)'));
    expect(chatsTabSource, contains('_executeChatDeletion(chat)'));
  });

  test('blocking preserves conversation and message history', () {
    final start = source.indexOf('Future<void> _executeBlock() async {');
    final end = source.indexOf('Future<void> _executeReport(', start);
    expect(start, isNonNegative);
    expect(end, greaterThan(start));
    final method = source.substring(start, end);
    expect(method, isNot(contains('deleteChatThread')));
    expect(method, isNot(contains('ChatLocalStore.deleteThread')));
    expect(method, contains('ApiService.addToIgnoreList'));
  });

  test('long press exposes copy, safe deletion and message-level report actions', () {
    expect(source, contains('onLongPress: () => _showMessageActions(msg)'));
    expect(source, contains('Clipboard.setData'));
    expect(source, contains('ApiService.deleteChatMessage('));
    expect(source, contains('ChatLocalStore.hideMessage('));
    expect(source, contains('_showReportReasonsDialog(reportedMessage: message)'));
    expect(source, contains('messageId: reportedMessage?.id'));
  });

  test('incoming messages are hidden locally and only own sent messages reach server delete', () {
    expect(source, contains('final canDeleteForEveryone = message.isMe && message.id > 0'));
    expect(source, contains('deleteForEveryone: canDeleteForEveryone'));
    expect(source, contains('The sender will keep their copy'));
    final start = source.indexOf('Future<void> _removeMessage(');
    final end = source.indexOf('void _showDeleteChatConfirmationDialog()', start);
    expect(start, isNonNegative);
    expect(end, greaterThan(start));
    final method = source.substring(start, end);
    expect(method, contains('if (deleteForEveryone && message.id > 0)'));
    expect(method, contains('ChatLocalStore.hideMessage'));
  });

  test('incoming images are blurred until reveal and provide an image-specific report action', () {
    expect(source, contains('ImageFilter.blur('));
    expect(source, contains('isImageRevealed'));
    expect(source, contains('اعرض الصورة'));
    expect(source, contains('إبلاغ عن الصورة'));
    expect(source, contains('onReportImage:'));
  });

  test('reporting a message sends its real message id and preserves the conversation', () {
    final start = source.indexOf('Future<void> _executeReport(');
    final end = source.indexOf('void _showDeleteChatConfirmationDialog()', start);
    expect(start, isNonNegative);
    expect(end, greaterThan(start));
    final method = source.substring(start, end);
    expect(method, contains('chatThreadId: _activeThreadId'));
    expect(method, contains('messageId: reportedMessage?.id'));
    expect(method, isNot(contains('deleteChatThread')));
    expect(method, isNot(contains('ChatLocalStore.deleteThread')));
  });
}

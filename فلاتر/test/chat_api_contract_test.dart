import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/services/api_service.dart';
import 'package:wesal_app/utils/member_profile_link.dart';

void main() {
  late String chatsTabSource;

  setUpAll(() {
    chatsTabSource = File('lib/screens/home/chats_tab.dart').readAsStringSync();
  });

  group('Wesal chat and deep-link API contracts', () {
    test('chats tab unwraps ChatThreadsPage before sorting or merging', () {
      expect(
        RegExp(r'final ChatThreadsPage page = await ApiService\.getChatThreads\(')
            .allMatches(chatsTabSource)
            .length,
        2,
      );
      expect(chatsTabSource, contains('for (final thread in page.threads)'));
      expect(chatsTabSource, contains('final threads = page.threads.toList();'));
      expect(chatsTabSource, contains('_chatPage = page.currentPage;'));
      expect(chatsTabSource, contains('_hasMoreChats = page.hasMore;'));
      expect(
        chatsTabSource,
        isNot(contains('final List<ChatThreadModel> chats = await ApiService.getChatThreads')),
      );
    });
    test('chat list API returns the typed paginated response', () {
      final Future<ChatThreadsPage> Function({
        String? lang,
        int page,
        int perPage,
      }) getChatThreads = ApiService.getChatThreads;

      const response = ChatThreadsPage(
        threads: [],
        currentPage: 1,
        perPage: 30,
        hasMore: false,
      );

      expect(getChatThreads, isA<Function>());
      expect(response.threads, isEmpty);
      expect(response.currentPage, 1);
      expect(response.perPage, 30);
      expect(response.hasMore, isFalse);
    });

    test('incremental message loading keeps the afterMessageId parameter', () {
      final Future<Map<String, dynamic>> Function(
        int threadId,
        int partnerUserId, {
        String? lang,
        int? afterMessageId,
      }) getChatMessages = ApiService.getChatMessages;

      expect(getChatMessages, isA<Function>());
    });

    test('profile deep links expose the parser used by main.dart', () {
      expect(
        MemberProfileLink.parseProfileUri(
          Uri.parse('wesal://profile?id=20260358'),
        ),
        20260358,
      );
    });
  });
}

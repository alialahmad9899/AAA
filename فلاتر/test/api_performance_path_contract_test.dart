import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  test('API requests reuse a persistent HTTP client', () async {
    final api = await File('lib/services/api_service.dart').readAsString();
    expect(api, contains('static final http.Client _httpClient = http.Client()'));
    expect(api, contains('_httpClient.get('));
    expect(api, contains('_httpClient.send(request)'));
    expect(api, isNot(contains('http.get(')));
    expect(api, isNot(contains('http.post(')));
    expect(api, isNot(contains('request.send()')));
  });

  test('chat polling requests only messages newer than the last known message', () async {
    final api = await File('lib/services/api_service.dart').readAsString();
    final screen = await File('lib/screens/chat_detail_screen.dart').readAsString();
    expect(api, contains('int? afterMessageId'));
    expect(api, contains("queryParameters['after_id']"));
    expect(screen, contains('afterMessageId: previousLatestMessageId'));
  });

  test('member search and matching use paginated loading', () async {
    final search = await File('lib/screens/home/search_results_screen.dart').readAsString();
    final matching = await File('lib/screens/home/tabs/auto_matching_tab.dart').readAsString();
    expect(search, contains('_loadMoreSearchResults'));
    expect(matching, contains('_fetchMoreMatches'));
    expect(matching, contains('perPage: _matchPageSize'));
  });

  test('chat room list is paginated and loads the next page near the end', () async {
    final api = await File('lib/services/api_service.dart').readAsString();
    final screen = await File('lib/screens/home/chats_tab.dart').readAsString();
    expect(api, contains('class ChatThreadsPage'));
    expect(api, contains('int page = 1'));
    expect(api, contains('int perPage = 30'));
    expect(api, contains(r"'per_page': '$safePerPage'"));
    expect(screen, contains('_loadMoreChats()'));
    expect(screen, contains('controller: _chatScrollController'));
    expect(screen, contains('page: nextPage'));
  });

  test('chat notification lookup follows paginated history and stops on repeated legacy pages', () async {
    final notification = await File('lib/services/notification_service.dart').readAsString();
    expect(notification, contains('page: pageNumber'));
    expect(notification, contains('perPage: 30'));
    expect(notification, contains('page.hasMore'));
    expect(notification, contains('!foundNewThread'));
  });

  test('startup compatibility callback receives chat thread models from the page', () async {
    final startup = await File('lib/services/startup_manager.dart').readAsString();
    expect(startup, contains('onChatsLoaded(chatsPage.threads)'));
  });

  test('member card images use bounded decode widths and chat mute sync does not block first messages', () async {
    final matching = await File('lib/screens/home/tabs/auto_matching_tab.dart').readAsString();
    final search = await File('lib/screens/home/search_results_screen.dart').readAsString();
    final chat = await File('lib/screens/chat_detail_screen.dart').readAsString();
    expect(matching, contains('memCacheWidth: 900'));
    expect(search, contains('memCacheWidth: 240'));
    expect(chat, contains('unawaited(\n      ApiService.syncChatMuteSettings(partnerUserId: widget.thread.userId),'));
  });

  test('public profile is rendered before the secondary account permission request finishes', () async {
    final profile = await File('lib/screens/member_profile_screen.dart').readAsString();
    expect(profile, contains('final currentUserFuture ='));
    expect(profile, contains('ApiService.getPublicProfile('));
    expect(profile, contains('ApiService.getCurrentUserProfile('));
    expect(profile, contains('Show the requested profile as soon as its own response is ready'));
  });

  test('signup step two overlaps independent profile reads and writes', () async {
    final signup = await File('lib/screens/signup_screen.dart').readAsString();
    expect(signup, contains('final existingProfileRecords = await Future.wait(['));
    expect(signup, contains('Future<void> saveEducation() async'));
    expect(signup, contains('Future<void> saveCareer() async'));
    expect(signup, contains('await Future.wait<void>([saveEducation(), saveCareer()])'));
    expect(signup, contains('final preferenceWrites = <Future<Map<String, dynamic>>>[]'));
    expect(signup, contains('await Future.wait(preferenceWrites)'));
  });

  test('signup step three loads partner dropdowns alongside profile updates', () async {
    final signup = await File('lib/screens/signup_screen.dart').readAsString();
    expect(signup, contains('final partnerOptionsFuture = _loadPartnerOptions()'));
    expect(signup, contains('await partnerOptionsFuture'));
  });

  test('signup has a saved resumable draft and login validates the returned token', () async {
    final signup = await File('lib/screens/signup_screen.dart').readAsString();
    final loginApi = await File('lib/services/api_service.dart').readAsString();
    expect(signup, contains('await ApiService.saveSignupDraft(draft)'));
    expect(signup, contains('await ApiService.getSignupDraft()'));
    expect(loginApi, contains('await saveToken(token)'));
    expect(loginApi, contains('final savedToken = await getToken()'));
  });
}

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

  test('smart matching shows only a simple status or percentage without criteria counters', () async {
    final matching = await File('lib/screens/home/tabs/auto_matching_tab.dart').readAsString();
    expect(matching, contains("member.matchStatus == 'no_preferences'"));
    expect(matching, contains("member.matchStatus == 'insufficient_data'"));
    expect(matching, contains('ملف غير مكتمل'));
    expect(matching, isNot(contains('matchCoverageLabel')));
    expect(matching, isNot(contains('المعايير التي تم تقييمها')));
    expect(matching, isNot(contains('Criteria evaluated')));
    expect(matching, contains(r"'${member.matchPercentage}%"));
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

  test('Android CI release signing uses one matching PKCS12 key and store password', () async {
    final workflow = await File('../.github/workflows/wesal-integration-validation.yml').readAsString();
    expect(workflow, contains('-storetype PKCS12'));
    expect(workflow, contains(r'KEY_PASSWORD="$STORE_PASSWORD"'));
    expect(workflow, isNot(contains(r'-keypass "$KEY_PASSWORD"')));
  });

  test('Android release declares OkHttp required by uCrop R8 references', () async {
    final gradle = await File('android/app/build.gradle.kts').readAsString();
    expect(gradle, contains('implementation("com.squareup.okhttp3:okhttp:4.12.1")'));
    expect(gradle, contains('isMinifyEnabled = true'));
  });

  test('home setup parallelizes local reads and pauses blocked-status polling in background', () async {
    final home = await File('lib/screens/main_home_screen.dart').readAsString();
    expect(home, contains('await Future.wait<void>(['));
    expect(home, contains('void _startBlockedStatusTimer()'));
    expect(home, contains('if (state == AppLifecycleState.resumed)'));
    expect(home, contains('_blockedStatusTimer = null;'));
  });

  test('FCM registration runs in the background after login and signup', () async {
    final login = await File('lib/screens/login_screen.dart').readAsString();
    final signup = await File('lib/screens/signup_screen.dart').readAsString();
    final home = await File('lib/screens/main_home_screen.dart').readAsString();
    expect(login, contains('unawaited(_updateFcmToken());'));
    expect(login, isNot(contains('await _updateFcmToken();')));
    expect(signup, contains('unawaited(\n              NotificationService.syncCurrentToken(force: true).then<void>((_) {}),'));
    expect(signup, isNot(contains('await NotificationService.syncCurrentToken(force: true)')));
    expect(home, contains('unawaited(NotificationService.syncCurrentToken().then<void>((_) {}));'));
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
    expect(signup, contains('final residencySave = _requireSignupSave('));
    expect(signup, contains('final spiritualSave = _requireSignupSave('));
    expect(signup, contains('await Future.wait([residencySave, spiritualSave])'));
    expect(signup, contains('await partnerOptionsFuture'));
    final stepThreeStart = signup.indexOf('else if (_currentStep == 2)');
    final stepFourStart = signup.indexOf('الخطوة الرابعة', stepThreeStart);
    final stepThree = signup.substring(stepThreeStart, stepFourStart);
    expect(
      stepThree.indexOf('await Future.wait([residencySave, spiritualSave])'),
      lessThan(stepThree.indexOf('ApiService.updateChildrenCount')),
    );
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

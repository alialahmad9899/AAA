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

  test('public profile is rendered before the secondary account permission request finishes', () async {
    final profile = await File('lib/screens/member_profile_screen.dart').readAsString();
    expect(profile, contains('final currentUserFuture ='));
    expect(profile, contains('ApiService.getPublicProfile('));
    expect(profile, contains('ApiService.getCurrentUserProfile('));
    expect(profile, contains('Show the requested profile as soon as its own response is ready'));
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

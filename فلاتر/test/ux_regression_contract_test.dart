import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  late String searchResults;
  late String matchingTab;
  late String signup;
  late String editProfile;
  late String accountTab;
  late String memberProfile;
  late String chatDetail;
  late String senderBubble;
  late String receiverBubble;
  late String signupBasicInfo;
  late String signupPersonalDetails;
  late String signupResidency;
  late String signupExpectations;
  late String cropManifest;
  late String cropStyles;

  setUpAll(() {
    searchResults = File('lib/screens/home/search_results_screen.dart').readAsStringSync();
    matchingTab = File('lib/screens/home/tabs/auto_matching_tab.dart').readAsStringSync();
    signup = File('lib/screens/signup_screen.dart').readAsStringSync();
    editProfile = File('lib/screens/edit_profile_screen.dart').readAsStringSync();
    accountTab = File('lib/screens/home/tabs/account_tab.dart').readAsStringSync();
    memberProfile = File('lib/screens/member_profile_screen.dart').readAsStringSync();
    chatDetail = File('lib/screens/chat_detail_screen.dart').readAsStringSync();
    senderBubble = File('lib/widgets/sender_message_bubble.dart').readAsStringSync();
    receiverBubble = File('lib/widgets/receiver_message_bubble.dart').readAsStringSync();
    signupBasicInfo = File('lib/screens/widgets/step1_basic_info.dart').readAsStringSync();
    signupPersonalDetails = File('lib/screens/widgets/step2_personal_details.dart').readAsStringSync();
    signupResidency = File('lib/screens/widgets/step3_residency_religion.dart').readAsStringSync();
    signupExpectations = File('lib/screens/widgets/step4_partner_expectations.dart').readAsStringSync();
    cropManifest = File('android/app/src/main/AndroidManifest.xml').readAsStringSync();
    cropStyles = File('android/app/src/main/res/values/styles.xml').readAsStringSync();
  });

  test('search results localize marital status from the raw model value', () {
    expect(searchResults, contains('member.getLocalizedMaritalStatus(isArabic: _isAr)'));
    expect(searchResults, contains("startsWith('ar')"));
  });

  test('signup header is shorter and validation feedback stays above Continue', () {
    expect(signup, contains('width: 104,'));
    expect(signup, contains('height: 54,'));
    expect(signup, contains('_profilePhotoValidationMessage'));
    expect(signup, contains('Complete the fields outlined in red, then continue.'));
    expect(signupBasicInfo, contains('showValidationErrors'));
    expect(signupPersonalDetails, contains('showValidationErrors'));
    expect(signupResidency, contains('showError: widget.showValidationErrors'));
    expect(signupExpectations, contains('showValidationErrors'));
  });

  test('signup and edit-profile back buttons share RTL-aware placement and glyph direction', () {
    expect(signup, contains('textDirection: isAr ? TextDirection.rtl : TextDirection.ltr'));
    expect(editProfile, contains('textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr'));
  });

  test('profile completion lists missing fields and navigates to their keyed controls', () {
    expect(editProfile, contains(r'Missing fields (${missingProfileFields.length}):'));
    expect(editProfile, contains('ElevatedButton.icon('));
    expect(editProfile, contains('_missingProfileNavigationKeys[field.targetKey]'));
    expect(editProfile, contains('Scrollable.ensureVisible('));
    expect(editProfile, contains('await WidgetsBinding.instance.endOfFrame'));
  });

  test('logout requires a deliberate confirmation before clearing the session', () {
    final start = accountTab.indexOf('Future<void> _confirmLogout() async {');
    final end = accountTab.indexOf('Future<void> _performFullLogout()', start);
    expect(start, isNonNegative);
    expect(end, greaterThan(start));
    final method = accountTab.substring(start, end);
    expect(method, contains('showDialog<bool>'));
    expect(method, contains('barrierDismissible: false'));
    expect(method, contains('if (confirmed == true && mounted)'));
    expect(method, contains('await _performFullLogout()'));
  });

  test('member profile exposes a confirmed block action backed by the existing ignore API', () {
    expect(memberProfile, contains('Icons.block_rounded'));
    expect(memberProfile, contains('_showBlockConfirmationDialog'));
    expect(memberProfile, contains('barrierDismissible: false'));
    expect(memberProfile, contains('ApiService.addToIgnoreList'));
    expect(memberProfile, contains('Member blocked; conversation history preserved'));
  });

  test('chat opens at the latest edge and the mute subtitle follows the mute state', () {
    expect(chatDetail, contains('_scrollController.jumpTo(_scrollController.position.minScrollExtent)'));
    expect(chatDetail, contains('reverse: true'));
    expect(chatDetail, contains('ChatMessageModel.newestFirst('));
    expect(chatDetail, contains('Turn alerts for this conversation back on.'));
    expect(chatDetail, contains('ستعود تنبيهات هذه المحادثة للعمل'));
  });

  test('both actual chat bubbles select text direction from the message language', () {
    expect(chatDetail, contains('MessageTextDirection.of(cleanText)'));
    expect(senderBubble, contains('MessageTextDirection.of(message.message)'));
    expect(receiverBubble, contains('MessageTextDirection.of(message.message)'));
  });

  test('Arabic next action uses an explicit physical left chevron', () {
    expect(matchingTab, contains('Icons.chevron_left_rounded'));
    expect(matchingTab, contains("startsWith('ar')"));
    expect(matchingTab, contains('iconDirection: TextDirection.ltr'));
  });

  test('Android cropper uses the dedicated uCrop activity theme', () {
    expect(cropManifest, contains('@style/Ucrop.CropTheme'));
    expect(cropStyles, contains('name="Ucrop.CropTheme"'));
  });
}

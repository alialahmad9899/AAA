import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/services/api_service.dart';

void main() {
  test('zodiac field remains connected from edit screen through API and model', () async {
    final editScreen = await File('lib/screens/edit_profile_screen.dart').readAsString();
    final apiService = await File('lib/services/api_service.dart').readAsString();
    final profileModel = await File('lib/models/member_profile_model.dart').readAsString();
    final backendRequest = await File('../المشروع/WESAL/app/Http/Requests/AstrologyRequest.php').readAsString();
    final backendController = await File('../المشروع/WESAL/app/Http/Controllers/Api/ProfileController.php').readAsString();
    final backendResource = await File('../المشروع/WESAL/app/Http/Resources/PublicProfile/AstronomicInformation.php').readAsString();
    final backendModel = await File('../المشروع/WESAL/app/Models/Astrology.php').readAsString();

    expect(editScreen, contains("fullData['astrologies']"));
    expect(editScreen, contains("'sun_sign'"));
    expect(apiService, contains("'sun_sign'"));
    expect(profileModel, contains("json['sun_sign']"));
    expect(backendRequest, contains("'sun_sign'"));
    expect(backendController, contains("Astrology::firstOrNew(['user_id' => auth()->id()])"));
    expect(backendController, contains(r'$astrologies->sun_sign'));
    expect(backendResource, contains(r"'sun_sign' => $this->sun_sign"));
    expect(backendModel, contains("'sun_sign'"));
  });

  test('a loaded zodiac value is submitted only when changed', () {
    expect(
      ApiService.shouldSendChangedProfileField(
        wasLoaded: true,
        originalValue: 'Aries',
        currentValue: 'Taurus',
      ),
      isTrue,
    );
    expect(
      ApiService.shouldSendChangedProfileField(
        wasLoaded: true,
        originalValue: 'Aries',
        currentValue: 'Aries',
      ),
      isFalse,
    );
    expect(
      ApiService.shouldSendChangedProfileField(
        wasLoaded: false,
        currentValue: 'Cancer',
      ),
      isTrue,
    );
  });
}

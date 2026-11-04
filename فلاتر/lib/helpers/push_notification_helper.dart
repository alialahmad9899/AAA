// ============================================================================
// اسم الملف: lib/helpers/push_notification_helper.dart
// الوصف: طبقة توافق للنداءات القديمة. لا تنشئ مستمعي FCM ولا ترسل طلبات HTTP
// مستقلة؛ جميع الوظائف تمر عبر NotificationService الموحدة.
// ============================================================================

import '../services/api_service.dart';
import '../services/notification_service.dart';

@Deprecated('Use NotificationService.initialize() directly.')
class PushNotificationHelper {
  static Future<void> initializeNotifications() {
    return NotificationService.initialize();
  }

  @Deprecated('Use NotificationService.syncCurrentToken() directly.')
  static Future<void> updateDeviceTokenOnServer(String userAccessToken) async {
    // لا نقبل تمرير توكن قديم أو تابع لحساب آخر إلى مسار المزامنة المركزي.
    final String? storedToken = await ApiService.getToken();
    final String suppliedToken = userAccessToken
        .trim()
        .replaceFirst(RegExp(r'^Bearer\s+', caseSensitive: false), '');
    if (storedToken == null || suppliedToken != storedToken) return;
    await NotificationService.syncCurrentToken(force: true);
  }
}

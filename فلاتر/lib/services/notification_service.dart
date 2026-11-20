// ============================================================================
// اسم الملف: lib/services/notification_service.dart
// الوصف: المالك الوحيد لدورة حياة Firebase Messaging داخل تطبيق وصال.
// - مستمع واحد للرسائل أثناء فتح التطبيق.
// - مستمع واحد لنقرات إشعارات الخلفية.
// - مستمع واحد لتجديد FCM Token.
// - مزامنة التوكن فقط عند وجود جلسة مستخدم مصادق عليها.
// - توجيه موحد للنقرات سواء من إشعار FCM أو الإشعار المحلي.
// ============================================================================

import 'dart:async';
import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/chat_model.dart';
import '../screens/chat_detail_screen.dart';
import '../screens/member_profile_screen.dart';
import '../screens/my_interests_screen.dart';
import '../screens/my_profile_viewers_screen.dart';
import '../screens/notifications_screen.dart';
import '../screens/package_purchase_history_screen.dart';
import '../screens/package_invoice_screen.dart';
import '../screens/support_ticket_detail_screen.dart';
import 'api_service.dart';
import 'fcm_service.dart';
import '../utils/chat_notification_utils.dart';
import '../utils/chat_activity_state.dart';
import '../utils/notification_route_resolver.dart';

const AndroidNotificationChannel _notificationChannel =
    AndroidNotificationChannel(
  'wesal_high_importance_channel_v2',
  'إشعارات تطبيق وصال',
  description: 'إشعارات الطلبات والتنبيهات المهمة في تطبيق وصال',
  importance: Importance.max,
  playSound: true,
  enableVibration: true,
);

const AndroidNotificationChannel _chatAlertChannel =
    AndroidNotificationChannel(
  'wesal_chat_alert_channel_v2',
  'رسائل وصال',
  description: 'تنبيه أول رسالة جديدة في المحادثة',
  importance: Importance.high,
  playSound: true,
  enableVibration: true,
);

const AndroidNotificationChannel _silentChatChannel =
    AndroidNotificationChannel(
  'wesal_chat_silent_channel',
  'تحديثات المحادثات',
  description: 'تحديثات هادئة للرسائل ضمن المحادثات',
  importance: Importance.low,
  playSound: false,
  enableVibration: false,
);

/// معالج رسائل الخلفية. لا يعيد عرض رسائل notification القادمة من FCM لأن
/// Android/iOS يعرضها تلقائياً في الخلفية؛ يعرض فقط رسائل data-only.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    WidgetsFlutterBinding.ensureInitialized();
    await Firebase.initializeApp();

    // Notification payloads are rendered by the operating system while the
    // app is in the background. Only data-only messages need a local alert.
    if (message.notification != null) return;

    final bool isChatMessage = isChatNotificationType(message.data);
    final bool isSilent = isChatMessage &&
        isSilentChatNotification(message.data['silent']);
    final String title = message.data['title']?.toString().trim() ?? '';
    final String body = (message.data['body'] ??
            message.data['message'] ??
            message.data['content'] ??
            '')
        .toString()
        .trim();

    if (title.isEmpty || body.isEmpty) return;

    final AndroidNotificationChannel selectedChannel = !isChatMessage
        ? _notificationChannel
        : (isSilent ? _silentChatChannel : _chatAlertChannel);
    final plugin = FlutterLocalNotificationsPlugin();
    const androidSettings = AndroidInitializationSettings('ic_stat_wesal');
    const iosSettings = DarwinInitializationSettings();
    const initializationSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await plugin.initialize(initializationSettings);
    await plugin
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(selectedChannel);

    await plugin.show(
      message.messageId?.hashCode ?? message.hashCode,
      title,
      body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          selectedChannel.id,
          selectedChannel.name,
          channelDescription: selectedChannel.description,
          icon: 'ic_stat_wesal',
          importance: isSilent ? Importance.low : Importance.max,
          priority: isSilent ? Priority.low : Priority.high,
          playSound: !isSilent,
          enableVibration: !isSilent,
          styleInformation: BigTextStyleInformation(body),
        ),
        iOS: DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: !isChatMessage,
          presentSound: !isSilent,
        ),
      ),
      payload: jsonEncode(message.data),
    );
  } catch (error, stackTrace) {
    debugPrint('[FCM Background] $error');
    debugPrintStack(stackTrace: stackTrace);
  }
}

class NotificationService {
  NotificationService._();

  static final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  /// يتغير عند وصول إشعار أثناء فتح التطبيق كي تحدّث الواجهة عدّاد غير المقروء.
  static final ValueNotifier<int> foregroundMessageVersion = ValueNotifier<int>(0);

  static bool _initialized = false;
  static Future<void>? _initializationFuture;
  static bool _localNotificationsInitialized = false;
  static bool _sessionEnabled = false;
  static int _sessionGeneration = 0;
  static String? _lastSyncedFcmToken;
  static String? _lastSyncedUserToken;
  static Future<bool>? _syncInProgress;
  static String? _pendingFcmToken;
  static final Set<String> _processedMessageIds = <String>{};
  static final Set<String> _handledNotificationTapIds = <String>{};
  static final Set<String> _messagesBeingHandled = <String>{};
  // A single chat thread may be delivered through both FCM and local launch
  // details. Keep its destination claimed for as long as its route is open.
  static final Set<String> _activeNotificationDestinations = <String>{};

  /// تهيئة مشتركة؛ أي استدعاء متزامن ينتظر نفس عملية Firebase والـlisteners.
  static Future<void> initialize() {
    if (_initialized) return Future<void>.value();
    return _initializationFuture ??= _initializeOnce().whenComplete(() {
      _initializationFuture = null;
    });
  }

  static Future<void> _initializeOnce() async {
    try {
      await Firebase.initializeApp();

      // يجب تسجيل معالج الخلفية قبل بدء استقبال الرسائل.
      FirebaseMessaging.onBackgroundMessage(
        firebaseMessagingBackgroundHandler,
      );

      try {
        final NotificationSettings permission =
            await _messaging.requestPermission(
          alert: true,
          badge: true,
          sound: true,
          provisional: false,
        );
        debugPrint(
          '[Notifications] Authorization: ${permission.authorizationStatus}',
        );
      } catch (error) {
        debugPrint('[Notifications] Permission request failed: $error');
      }

      await _ensureLocalNotificationsInitialized();
      await _configureForegroundPresentationOptions();

      _initialized = true;
      FirebaseMessaging.onMessage.listen(_onForegroundMessage);
      FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
        unawaited(_handleNotificationTap(
          message.data,
          messageId: message.messageId,
        ));
      });
      _messaging.onTokenRefresh.listen((String newToken) {
        unawaited(_syncSpecificToken(newToken).then<void>((_) {}));
      });

      try {
        final RemoteMessage? initialMessage =
            await _messaging.getInitialMessage();
        if (initialMessage != null) {
          unawaited(_handleNotificationTap(
            initialMessage.data,
            messageId: initialMessage.messageId,
          ));
        }
      } catch (error) {
        debugPrint('[Notifications] Initial FCM message failed: $error');
      }

      if (_localNotificationsInitialized) {
        try {
          final NotificationAppLaunchDetails? launchDetails =
              await _localNotifications.getNotificationAppLaunchDetails();
          final String? launchPayload =
              launchDetails?.didNotificationLaunchApp == true
                  ? launchDetails?.notificationResponse?.payload
                  : null;
          if (launchPayload != null && launchPayload.isNotEmpty) {
            _handleLocalPayload(launchPayload);
          }
        } catch (error) {
          debugPrint('[Notifications] Local launch details failed: $error');
        }
      }
    } catch (error, stackTrace) {
      _initialized = false;
      debugPrint('[NotificationService Initialize] $error');
      debugPrintStack(stackTrace: stackTrace);
      rethrow;
    }
  }

  static Future<bool> _ensureLocalNotificationsInitialized() async {
    if (_localNotificationsInitialized) return true;

    const androidSettings =
        AndroidInitializationSettings('ic_stat_wesal');
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );
    const initializationSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    try {
      await _localNotifications.initialize(
        initializationSettings,
        onDidReceiveNotificationResponse: _onLocalNotificationResponse,
      );
      final androidNotifications = _localNotifications
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>();
      await androidNotifications
          ?.createNotificationChannel(_notificationChannel);
      await androidNotifications?.createNotificationChannel(_chatAlertChannel);
      await androidNotifications?.createNotificationChannel(_silentChatChannel);
      _localNotificationsInitialized = true;
      return true;
    } catch (error, stackTrace) {
      _localNotificationsInitialized = false;
      debugPrint(
        '[Notifications] Local notification initialization failed: $error',
      );
      debugPrintStack(stackTrace: stackTrace);
      return false;
    }
  }

  static Future<void> _configureForegroundPresentationOptions() async {
    try {
      // Use the native iOS foreground banner as a fallback whenever the local
      // notification plugin is not ready; switch back to local-only when it is.
      await _messaging.setForegroundNotificationPresentationOptions(
        alert: !_localNotificationsInitialized,
        badge: true,
        sound: true,
      );
    } catch (error) {
      debugPrint(
        '[Notifications] Foreground presentation setup failed: $error',
      );
    }
  }

  static Future<void> _onForegroundMessage(RemoteMessage message) async {
    final String? messageId = message.messageId;
    if (messageId != null && !_processedMessageIds.add(messageId)) return;
    _trimProcessedMessageIds();

    final bool isChatMessage = isChatNotificationType(message.data);
    final int threadId = chatThreadIdFromNotification(message.data);
    if (isChatMessage &&
        threadId > 0 &&
        ChatActivityState.activeThreadId.value == threadId) {
      // The user is already reading this conversation. Do not cover it with
      // a local alert and do not increment the general notification counter.
      return;
    }
    if (!isChatMessage) foregroundMessageVersion.value++;

    final bool isSilent = isChatMessage &&
        isSilentChatNotification(message.data['silent']);
    final String title = (message.notification?.title ??
            message.data['title']?.toString() ??
            '')
        .trim();
    final String body = (message.notification?.body ??
            message.data['body']?.toString() ??
            message.data['message']?.toString() ??
            '')
        .trim();

    if (title.isEmpty || body.isEmpty) return;

    if (!_localNotificationsInitialized) {
      await _ensureLocalNotificationsInitialized();
      await _configureForegroundPresentationOptions();
    }
    if (!_localNotificationsInitialized) {
      debugPrint('[Notifications] Foreground alert skipped because local notifications are unavailable.');
      return;
    }

    final AndroidNotification? android = message.notification?.android;
    final AndroidNotificationChannel selectedChannel = !isChatMessage
        ? _notificationChannel
        : (isSilent ? _silentChatChannel : _chatAlertChannel);
    await _localNotifications.show(
      isChatMessage
          ? chatNotificationId(threadId, messageId, message.hashCode)
          : (messageId?.hashCode ?? message.hashCode),
      title,
      body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          selectedChannel.id,
          selectedChannel.name,
          channelDescription: selectedChannel.description,
          icon: android?.smallIcon ?? 'ic_stat_wesal',
          importance: isSilent ? Importance.low : Importance.max,
          priority: isSilent ? Priority.low : Priority.high,
          playSound: !isSilent,
          enableVibration: !isSilent,
          styleInformation: BigTextStyleInformation(body),
        ),
        iOS: DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: !isChatMessage,
          presentSound: !isSilent,
        ),
      ),
      payload: jsonEncode(message.data),
    );
  }

  static void _onLocalNotificationResponse(NotificationResponse response) {
    final String? payload = response.payload;
    if (payload != null && payload.isNotEmpty) {
      _handleLocalPayload(payload);
    }
  }

  static void _handleLocalPayload(String payload) {
    try {
      final dynamic decoded = jsonDecode(payload);
      if (decoded is Map) {
        unawaited(_handleNotificationTap(
          Map<String, dynamic>.from(decoded),
        ));
      }
    } catch (error) {
      debugPrint('[Notification Payload] Invalid payload: $error');
    }
  }

  /// يستدعى بعد نجاح تسجيل الدخول أو إنشاء الحساب.
  /// يجبر إعادة الربط لأن الحساب قد يكون مختلفاً على الجهاز نفسه.
  static Future<bool> syncCurrentToken({bool force = false}) async {
    try {
      // Token registration must never race Firebase initialization or be
      // silently lost when the saved login session is restored on cold start.
      await initialize();

      final String? userToken = await ApiService.getToken();
      if (userToken == null || userToken.isEmpty) {
        _sessionEnabled = false;
        debugPrint('[Notifications] Token sync skipped: no saved member session.');
        return false;
      }

      _sessionEnabled = true;
      if (force) {
        _lastSyncedFcmToken = null;
        _lastSyncedUserToken = null;
      }

      final String? fcmToken = await _messaging.getToken();
      if (fcmToken == null || fcmToken.isEmpty) {
        debugPrint('[Notifications] Firebase returned no device token.');
        return false;
      }
      final bool synced = await _syncSpecificToken(
        fcmToken,
        expectedUserToken: userToken,
      );
      if (!synced) {
        debugPrint('[Notifications] Device token registration did not complete.');
      }
      return synced;
    } catch (error) {
      debugPrint('[Notification Token Sync] $error');
      return false;
    }
  }

  static Future<bool> _syncSpecificToken(
    String fcmToken, {
    String? expectedUserToken,
  }) async {
    if (!_sessionEnabled || fcmToken.trim().isEmpty) return false;

    // جميع أسباب المزامنة تمر عبر قفل واحد لمنع الطلبات المتزامنة المكررة.
    final Future<bool>? inProgress = _syncInProgress;
    if (inProgress != null) {
      if (fcmToken != _lastSyncedFcmToken) {
        _pendingFcmToken = fcmToken;
      }
      return inProgress;
    }

    final int generation = _sessionGeneration;
    final Future<bool> operation = _performTokenSync(
      fcmToken,
      expectedUserToken: expectedUserToken,
      sessionGeneration: generation,
    );
    _syncInProgress = operation;
    try {
      return await operation;
    } finally {
      if (identical(_syncInProgress, operation)) {
        _syncInProgress = null;
        final String? pendingToken = _pendingFcmToken;
        _pendingFcmToken = null;
        if (_sessionEnabled &&
            pendingToken != null &&
            pendingToken.isNotEmpty &&
            pendingToken != _lastSyncedFcmToken) {
          unawaited(_syncSpecificToken(pendingToken).then<void>((_) {}));
        }
      }
    }
  }

  static Future<bool> _performTokenSync(
    String fcmToken, {
    String? expectedUserToken,
    required int sessionGeneration,
  }) async {
    final String? userToken = await ApiService.getToken();
    if (!_sessionEnabled || sessionGeneration != _sessionGeneration) {
      return false;
    }
    if (userToken == null || userToken.isEmpty) {
      _sessionEnabled = false;
      return false;
    }
    if (expectedUserToken != null && expectedUserToken != userToken) {
      return false;
    }

    if (_lastSyncedFcmToken == fcmToken &&
        _lastSyncedUserToken == userToken) {
      return true;
    }

    final bool success = await ApiService.updateDeviceToken(fcmToken);
    if (success &&
        _sessionEnabled &&
        sessionGeneration == _sessionGeneration &&
        (await ApiService.getToken()) == userToken) {
      _lastSyncedFcmToken = fcmToken;
      _lastSyncedUserToken = userToken;
      debugPrint('[Notifications] Device token synchronized.');
      return true;
    }
    return false;
  }

  /// يمنع أي مزامنة متأخرة بعد الخروج، من دون حذف FCM token الخاص بالتطبيق.
  static void resetSession() {
    _sessionGeneration++;
    _sessionEnabled = false;
    _lastSyncedFcmToken = null;
    _lastSyncedUserToken = null;
    _syncInProgress = null;
    _pendingFcmToken = null;
  }

  static void _trimProcessedMessageIds() {
    while (_processedMessageIds.length > 100) {
      _processedMessageIds.remove(_processedMessageIds.first);
    }
    while (_handledNotificationTapIds.length > 100) {
      _handledNotificationTapIds.remove(_handledNotificationTapIds.first);
    }
  }

  /// Opens a notification from the in-app center through the same resolver
  /// used for FCM tray taps. Returns false when the event has no safe target.
  static Future<bool> openFromNotificationCenter(
    Map<String, dynamic> data, {
    String language = 'ar',
  }) {
    return _dispatchNotificationTap(
      data,
      messageId: data['notification_id']?.toString(),
      languageOverride: language,
      fallbackToCenter: false,
      allowHandledRepeat: true,
      requireAuthToken: false,
    );
  }

  static Future<void> _handleNotificationTap(
    Map<String, dynamic> data, {
    String? messageId,
  }) async {
    await _dispatchNotificationTap(data, messageId: messageId);
  }

  static Future<bool> _dispatchNotificationTap(
    Map<String, dynamic> data, {
    String? messageId,
    String? languageOverride,
    bool fallbackToCenter = true,
    bool allowHandledRepeat = false,
    bool requireAuthToken = true,
  }) async {
    final String dedupeId = messageId?.trim().isNotEmpty == true
        ? messageId!.trim()
        : jsonEncode(data);
    // Ignore only an already handled tap; foreground delivery itself never
    // consumes the tap event.
    if (!claimNotificationTap(
      dedupeId,
      handled: _handledNotificationTapIds,
      inProgress: _messagesBeingHandled,
      allowHandledRepeat: allowHandledRepeat,
    )) {
      return false;
    }

    String? claimedDestinationKey;
    try {
      if (requireAuthToken) {
        final String? userToken = await ApiService.getToken();
        if (userToken == null || userToken.isEmpty) return false;
      }

      final SharedPreferences prefs = await SharedPreferences.getInstance();
      final String language = languageOverride ??
          prefs.getString('app_language') ??
          'ar';
      final NotificationDestination? route =
          resolveNotificationDestination(data);
      final WidgetBuilder? resolved = await _resolveDestination(
        data,
        language,
      );

      if (resolved == null && !fallbackToCenter) return false;

      // Deduplicate by the actual destination as well as by message ID.
      // FCM initial-message and local-notification launch callbacks can carry
      // different IDs while pointing to the same chat thread.
      claimedDestinationKey = notificationDestinationKey(route);
      if (!claimNotificationDestination(
        claimedDestinationKey,
        activeDestinations: _activeNotificationDestinations,
      )) {
        _handledNotificationTapIds.add(dedupeId);
        _trimProcessedMessageIds();
        return true;
      }

      final WidgetBuilder destination = resolved ??
          (_) => NotificationsScreen(currentLanguage: language);

      final bool pushed = await _pushWhenNavigatorReady(
        destination,
        claimedDestinationKey,
      );
      if (!pushed) {
        releaseNotificationDestination(
          claimedDestinationKey,
          activeDestinations: _activeNotificationDestinations,
        );
      }
      if (pushed) {
        _handledNotificationTapIds.add(dedupeId);
        _trimProcessedMessageIds();
      }
      return pushed;
    } catch (error, stackTrace) {
      if (claimedDestinationKey != null) {
        releaseNotificationDestination(
          claimedDestinationKey,
          activeDestinations: _activeNotificationDestinations,
        );
      }
      debugPrint('[Notification Navigation] $error');
      debugPrintStack(stackTrace: stackTrace);
      return false;
    } finally {
      _messagesBeingHandled.remove(dedupeId);
    }
  }

  static Future<WidgetBuilder?> _resolveDestination(
    Map<String, dynamic> data,
    String language,
  ) async {
    final NotificationDestination? destination =
        resolveNotificationDestination(data);
    if (destination == null) return null;

    switch (destination.kind) {
      case NotificationDestinationKind.supportTicket:
        final int? ticketId = destination.id;
        if (ticketId == null || ticketId < 1) return null;
        return (_) => SupportTicketDetailScreen(
              ticketId: ticketId,
              currentLanguage: language,
            );

      case NotificationDestinationKind.interests:
        return (_) => MyInterestsScreen(currentLanguage: language);

      case NotificationDestinationKind.profileVisitors:
        return (_) => MyProfileViewersScreen(currentLanguage: language);

      case NotificationDestinationKind.packagePurchaseHistory:
        return (_) => PackagePurchaseHistoryScreen(currentLanguage: language);

      case NotificationDestinationKind.profile:
        final int? memberId = destination.id;
        if (memberId == null || memberId < 1) return null;
        return (_) => MemberProfileScreen(
              userId: memberId,
              currentLanguage: language,
            );

      case NotificationDestinationKind.packageInvoice:
        final int? paymentId = destination.id;
        if (paymentId == null || paymentId < 1) return null;
        return (_) => PackageInvoiceScreen(
              paymentId: paymentId,
              currentLanguage: language,
            );

      case NotificationDestinationKind.chat:
        final int? threadId = destination.id;
        if (threadId == null || threadId < 1) return null;
        try {
          final List<ChatThreadModel> threads =
              await ApiService.getChatThreads(lang: language);
          for (final ChatThreadModel thread in threads) {
            if (thread.id == threadId) {
              return (_) => ChatDetailScreen(
                    thread: thread,
                    currentLanguage: language,
                  );
            }
          }
        } catch (error) {
          debugPrint('[Notification Chat Route] $error');
        }
        return null;
    }
  }

  static Future<bool> _pushWhenNavigatorReady(
    WidgetBuilder builder,
    String destinationKey,
  ) async {
    for (int attempt = 0; attempt < 24; attempt++) {
      final NavigatorState? navigator = FCMService.navigatorKey.currentState;
      if (navigator != null && navigator.mounted) {
        unawaited(
          navigator
              .push<void>(MaterialPageRoute<void>(builder: builder))
              .whenComplete(() {
            // Hold the destination lock until the opened page is closed.
            releaseNotificationDestination(
              destinationKey,
              activeDestinations: _activeNotificationDestinations,
            );
            // Refresh the notification badge after the notification route closes.
            foregroundMessageVersion.value++;
          }),
        );
        return true;
      }
      await Future<void>.delayed(const Duration(milliseconds: 250));
    }
    debugPrint('[Notification Navigation] Navigator was not ready.');
    return false;
  }
}

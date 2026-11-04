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
import '../screens/notifications_screen.dart';
import '../screens/support_ticket_detail_screen.dart';
import 'api_service.dart';
import 'fcm_service.dart';

const AndroidNotificationChannel _notificationChannel =
    AndroidNotificationChannel(
  'wesal_high_importance_channel',
  'إشعارات تطبيق وصال',
  description: 'إشعارات الرسائل والطلبات والتنبيهات المهمة في تطبيق وصال',
  importance: Importance.max,
  playSound: true,
  enableVibration: true,
);

/// معالج رسائل الخلفية. لا يعيد عرض رسائل notification القادمة من FCM لأن
/// Android/iOS يعرضها تلقائياً في الخلفية؛ يعرض فقط رسائل data-only.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    WidgetsFlutterBinding.ensureInitialized();
    await Firebase.initializeApp();

    // الرسائل ذات notification payload يعرضها نظام التشغيل تلقائياً.
    if (message.notification != null) return;

    final String title =
        message.data['title']?.toString().trim() ?? '';
    final String body =
        (message.data['body'] ?? message.data['message'] ?? '').toString().trim();

    if (title.isEmpty || body.isEmpty) return;

    final plugin = FlutterLocalNotificationsPlugin();
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings();
    const initializationSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await plugin.initialize(initializationSettings);
    await plugin
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_notificationChannel);

    await plugin.show(
      message.messageId?.hashCode ?? message.hashCode,
      title,
      body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          _notificationChannel.id,
          _notificationChannel.name,
          channelDescription: _notificationChannel.description,
          icon: '@mipmap/ic_launcher',
          importance: Importance.max,
          priority: Priority.high,
          playSound: true,
          enableVibration: true,
          styleInformation: BigTextStyleInformation(body),
        ),
        iOS: const DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
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

  static bool _initialized = false;
  static bool _initializing = false;
  static bool _localNotificationsInitialized = false;
  static bool _sessionEnabled = false;
  static int _sessionGeneration = 0;
  static String? _lastSyncedFcmToken;
  static String? _lastSyncedUserToken;
  static Future<bool>? _syncInProgress;
  static String? _pendingFcmToken;
  static final Set<String> _processedMessageIds = <String>{};
  static final Set<String> _messagesBeingHandled = <String>{};

  /// تهيئة الخدمة مرة واحدة فقط طوال عمر التطبيق.
  static Future<void> initialize() async {
    if (_initialized || _initializing) return;
    _initializing = true;

    try {
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
        // لا نوقف استقبال الإشعارات أو توجيهها بسبب فشل طلب الإذن.
        debugPrint('[Notifications] Permission request failed: $error');
      }

      const androidSettings =
          AndroidInitializationSettings('@mipmap/ic_launcher');
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
        _localNotificationsInitialized = true;

        await _localNotifications
            .resolvePlatformSpecificImplementation<
                AndroidFlutterLocalNotificationsPlugin>()
            ?.createNotificationChannel(_notificationChannel);
      } catch (error) {
        debugPrint('[Notifications] Local notifications setup failed: $error');
      }

      try {
        // نتولى العرض محلياً أثناء فتح التطبيق لتوحيد شكل وطريقة الإشعار.
        await _messaging.setForegroundNotificationPresentationOptions(
          alert: false,
          badge: true,
          sound: true,
        );
      } catch (error) {
        debugPrint('[Notifications] Foreground options failed: $error');
      }

      // من هذه النقطة أصبحت مستمعات FCM مربوطة مرة واحدة.
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

      // فتح التطبيق من إشعار FCM وهو مغلق بالكامل.
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

      // فتح التطبيق من إشعار محلي (رسالة data-only في الخلفية).
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

      // مزامنة التوكن لا تتم إلا إذا كانت هناك جلسة دخول محفوظة.
      await syncCurrentToken();
    } catch (error, stackTrace) {
      debugPrint('[NotificationService Initialize] $error');
      debugPrintStack(stackTrace: stackTrace);
    } finally {
      _initializing = false;
    }
  }

  static Future<void> _onForegroundMessage(RemoteMessage message) async {
    final String? messageId = message.messageId;
    if (messageId != null && !_processedMessageIds.add(messageId)) return;
    _trimProcessedMessageIds();

    final String title = (message.notification?.title ??
            message.data['title']?.toString() ??
            '')
        .trim();
    final String body = (message.notification?.body ??
            message.data['body']?.toString() ??
            message.data['message']?.toString() ??
            '')
        .trim();

    if (title.isEmpty || body.isEmpty || !_localNotificationsInitialized) {
      return;
    }

    final AndroidNotification? android = message.notification?.android;
    await _localNotifications.show(
      messageId?.hashCode ?? message.hashCode,
      title,
      body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          _notificationChannel.id,
          _notificationChannel.name,
          channelDescription: _notificationChannel.description,
          icon: android?.smallIcon ?? '@mipmap/ic_launcher',
          importance: Importance.max,
          priority: Priority.high,
          playSound: true,
          enableVibration: true,
          styleInformation: BigTextStyleInformation(body),
        ),
        iOS: const DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
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
      final String? userToken = await ApiService.getToken();
      if (userToken == null || userToken.isEmpty) {
        _sessionEnabled = false;
        return false;
      }

      _sessionEnabled = true;
      if (force) {
        _lastSyncedFcmToken = null;
        _lastSyncedUserToken = null;
      }

      final String? fcmToken = await _messaging.getToken();
      if (fcmToken == null || fcmToken.isEmpty) return false;
      return _syncSpecificToken(fcmToken, expectedUserToken: userToken);
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
  }

  static Future<void> _handleNotificationTap(
    Map<String, dynamic> data, {
    String? messageId,
  }) async {
    final String dedupeId = messageId?.trim().isNotEmpty == true
        ? messageId!.trim()
        : jsonEncode(data);
    if (_processedMessageIds.contains(dedupeId) ||
        !_messagesBeingHandled.add(dedupeId)) {
      return;
    }

    try {
      final String? userToken = await ApiService.getToken();
      if (userToken == null || userToken.isEmpty) return;

      final SharedPreferences prefs = await SharedPreferences.getInstance();
      final String language = prefs.getString('app_language') ?? 'ar';
      final WidgetBuilder destination = await _resolveDestination(
        data,
        language,
      );

      final bool pushed = await _pushWhenNavigatorReady(destination);
      if (pushed) {
        _processedMessageIds.add(dedupeId);
        _trimProcessedMessageIds();
      }
    } catch (error, stackTrace) {
      debugPrint('[Notification Navigation] $error');
      debugPrintStack(stackTrace: stackTrace);
    } finally {
      _messagesBeingHandled.remove(dedupeId);
    }
  }

  static Future<WidgetBuilder> _resolveDestination(
    Map<String, dynamic> data,
    String language,
  ) async {
    final String type = (data['type'] ??
            data['notification_type'] ??
            data['action'] ??
            '')
        .toString()
        .toLowerCase()
        .replaceAll('-', '_')
        .replaceAll(' ', '_');

    final int? ticketId = _firstPositiveInt(data, const [
      'ticket_id',
      'ticketId',
      'support_ticket_id',
      'support_id',
      'thread_id',
      'info_id',
      'id',
    ]);
    if ((type.contains('support') || type.contains('ticket')) &&
        ticketId != null) {
      return (_) => SupportTicketDetailScreen(
            ticketId: ticketId,
            currentLanguage: language,
          );
    }

    if (type.contains('interest') ||
        type.contains('match') ||
        type.contains('like')) {
      return (_) => MyInterestsScreen(currentLanguage: language);
    }

    if (type.contains('chat') ||
        type.contains('message') ||
        type.contains('conversation')) {
      final int? threadId = _firstPositiveInt(data, const [
        'thread_id',
        'threadId',
        'chat_thread_id',
        'chat_id',
        'chatId',
        'conversation_id',
        'conversationId',
      ]);
      if (threadId != null) {
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
      }
      return (_) => NotificationsScreen(currentLanguage: language);
    }

    if (type.contains('profile') ||
        type.contains('member') ||
        type.contains('visitor')) {
      final int? memberId = _firstPositiveInt(data, const [
        'user_id',
        'userId',
        'member_id',
        'memberId',
        'profile_id',
        'sender_id',
        'visitor_id',
      ]);
      if (memberId != null) {
        return (_) => MemberProfileScreen(
              userId: memberId,
              currentLanguage: language,
            );
      }
    }

    // للأنواع الإدارية والمالية أو الحمولة غير المعروفة، افتح مركز الإشعارات
    // بدلاً من توجيه المستخدم إلى شاشة غير مؤكدة أو بمعرّف غير صحيح.
    return (_) => NotificationsScreen(currentLanguage: language);
  }

  static int? _firstPositiveInt(
    Map<String, dynamic> data,
    List<String> keys,
  ) {
    for (final String key in keys) {
      final dynamic value = data[key];
      if (value == null) continue;
      final int? parsed = int.tryParse(value.toString());
      if (parsed != null && parsed > 0) return parsed;
    }
    return null;
  }

  static Future<bool> _pushWhenNavigatorReady(WidgetBuilder builder) async {
    for (int attempt = 0; attempt < 24; attempt++) {
      final NavigatorState? navigator = FCMService.navigatorKey.currentState;
      if (navigator != null && navigator.mounted) {
        navigator.push(MaterialPageRoute<void>(builder: builder));
        return true;
      }
      await Future<void>.delayed(const Duration(milliseconds: 250));
    }
    debugPrint('[Notification Navigation] Navigator was not ready.');
    return false;
  }
}

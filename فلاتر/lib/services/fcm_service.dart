// ============================================================================
// اسم الملف: lib/services/fcm_service.dart
// الوصف: يحتفظ بمفتاح الملاحة العام المستخدم في التطبيق.
// إدارة Firebase Messaging ومزامنة التوكن ومعالجة النقرات أصبحت مركزية داخل
// NotificationService لمنع تعدد المستمعين وإرسال التوكن أكثر من مرة.
// ============================================================================

import 'package:flutter/material.dart';

class FCMService {
  FCMService._();

  /// مفتاح الملاحة الموحد الذي تستخدمه MaterialApp وخدمة الإشعارات.
  static final GlobalKey<NavigatorState> navigatorKey =
      GlobalKey<NavigatorState>();
}

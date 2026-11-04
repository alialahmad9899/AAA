// ============================================================================
// اسم الملف: lib/models/notification_model.dart
// الوصف: نموذج بيانات الإشعارات مع إصلاح نوع المعرف ليقبل UUID النصي من السيرفر
// ============================================================================

class NotificationModel {
  final String
      notificationId; // تم التغيير من int إلى String لقراءة UUID السيرفر
  final String type;
  final int notifyBy;
  final String photo;
  final String title;
  final String message;
  final String time;
  final bool isNew;
  final bool check;

  NotificationModel({
    required this.notificationId,
    required this.type,
    required this.notifyBy,
    required this.photo,
    required this.title,
    required this.message,
    required this.time,
    required this.isNew,
    required this.check,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json,
      {String lang = 'ar'}) {
    String rawTime =
        json['time']?.toString() ?? json['created_at']?.toString() ?? '';
    String formattedTime = _formatTime(rawTime, lang: lang);

    String extractedMessage = json['message']?.toString() ??
        json['body']?.toString() ??
        json['content']?.toString() ??
        (lang == 'en'
            ? 'New notification from Wesal'
            : 'إشعار جديد من إدارة وصال');

    extractedMessage = extractedMessage.trim();

    // فحص هل الإشعار خاص بخصم الإدارة أم بخصم إظهار بيانات الاتصال
    final bool isAdminDebit = extractedMessage.contains('الإدارة') ||
        extractedMessage.contains('من قبل الإدارة');
    final bool isContactViewDebit = extractedMessage.contains('اظهار بيانات') ||
        extractedMessage.contains('إظهار بيانات') ||
        extractedMessage.contains('بيانات الاتصال');
    final bool isDebit =
        isAdminDebit || isContactViewDebit || extractedMessage.contains('خصم');

    if (isContactViewDebit && !isAdminDebit) {
      final amountMatch =
          RegExp(r'(\d+(?:\.\d+)?)').firstMatch(extractedMessage);
      if (amountMatch != null) {
        final amount = amountMatch.group(1);
        extractedMessage =
            'تم خصم مبلغ $amount\$ من محفظتك لقاء إظهار بيانات الاتصال.';
      } else {
        extractedMessage = 'تم خصم مبلغ من محفظتك لقاء إظهار بيانات الاتصال.';
      }
    } else if (!isDebit) {
      final walletRegex = RegExp(r'^(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s*(.*)$');
      if (walletRegex.hasMatch(extractedMessage)) {
        final match = walletRegex.firstMatch(extractedMessage);
        if (match != null) {
          final amount = match.group(1);
          final balance = match.group(2);
          final rest = match.group(3);
          extractedMessage =
              'تمت إضافة مبلغ $amount\$ إلى محفظتك بنجاح (الرصيد الحالي: $balance\$). $rest';
        }
      }
    }

    String extractedTitle = json['title']?.toString() ??
        json['subject']?.toString() ??
        json['header']?.toString() ??
        '';

    String typeStr = json['type']?.toString() ?? '';

    final isUnderscoreTitle = RegExp(r'^_+$').hasMatch(extractedTitle.trim());
    bool isBadTitle = extractedTitle.trim().isEmpty ||
        extractedTitle.trim() == '--' ||
        isUnderscoreTitle ||
        extractedTitle.contains('طلبات الاهتمام الذي يمكن استخدامها') ||
        (isDebit && extractedTitle == 'رصيد المحفظة') ||
        (extractedMessage.contains('الباقة') &&
            extractedTitle.contains('اهتمام'));

    if (isBadTitle) {
      extractedTitle = _getDefaultTitleByContent(typeStr, extractedMessage,
          isDebit: isDebit, lang: lang);
    }

    // استخراج المعرف النصي بمرونة (سواء جاء باسم id أو notification_id)
    String idVal =
        json['notification_id']?.toString() ?? json['id']?.toString() ?? '';

    return NotificationModel(
      notificationId: idVal,
      type: typeStr,
      notifyBy: json['notify_by'] is int
          ? json['notify_by']
          : int.tryParse(json['notify_by']?.toString() ?? '0') ?? 0,
      photo: json['photo']?.toString() ?? '',
      title: extractedTitle,
      message: extractedMessage,
      time: formattedTime,
      isNew: json['read_at'] == 'New' ||
          json['is_new'] == true ||
          json['read_at'] == null,
      check: _parseBoolean(json['check'], defaultValue: true),
    );
  }

  static bool _parseBoolean(dynamic value, {required bool defaultValue}) {
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value is String) {
      final normalized = value.trim().toLowerCase();
      if (const ['1', 'true', 'yes', 'y'].contains(normalized)) return true;
      if (const ['0', 'false', 'no', 'n'].contains(normalized)) return false;
    }
    return defaultValue;
  }

  static String _getDefaultTitleByContent(String type, String message,
      {bool isDebit = false, required String lang}) {
    final lowerType = type.toLowerCase();
    final bool isEn = lang == 'en';

    if (isDebit || lowerType.contains('debit') || message.contains('خصم')) {
      return isEn ? 'Wallet Deduction' : 'خصم من الرصيد';
    } else if (lowerType.contains('package') ||
        message.contains('الباقة') ||
        message.contains('باقة')) {
      return isEn ? 'Package Activated' : 'تفعيل الباقة';
    } else if (lowerType.contains('wallet') ||
        message.contains('المحفظة') ||
        message.contains('محفظتك') ||
        message.contains('رصيد')) {
      return isEn ? 'Wallet Recharged' : 'رصيد المحفظة';
    } else if (lowerType.contains('chat') || message.contains('رسالة')) {
      return isEn ? 'New Message' : 'رسالة جديدة';
    } else if (lowerType.contains('match') || message.contains('تطابق')) {
      return isEn ? 'New Match' : 'تطابق جديد';
    } else if (lowerType.contains('interest') ||
        message.contains('إعجاب') ||
        message.contains('اهتمام')) {
      return isEn ? 'Interest Request' : 'طلب اهتمام';
    }
    return isEn ? 'Admin Notification' : 'إشعار إداري';
  }

  static String _formatTime(String raw, {required String lang}) {
    if (raw.isEmpty) return '';
    try {
      if (raw.contains('T')) {
        final parsedDate = DateTime.tryParse(raw);
        if (parsedDate != null) {
          final local = parsedDate.toLocal();
          final hour = local.hour > 12
              ? local.hour - 12
              : (local.hour == 0 ? 12 : local.hour);
          final period = lang == 'en'
              ? (local.hour >= 12 ? 'PM' : 'AM')
              : (local.hour >= 12 ? 'م' : 'ص');
          final minute = local.minute.toString().padLeft(2, '0');
          return '$hour:$minute $period';
        }
      }
      return raw;
    } catch (_) {
      return raw;
    }
  }

  bool get read => !isNew;
}

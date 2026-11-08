// ============================================================================
// اسم الملف: lib/models/chat_model.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: نموذج بيانات المحادثة والرسائل الفردية وقواعد المحادثة مع دعم محصن
//        وشامل لاستخراج شارات الباقات والاشتراكات لغرف الشات.
// ============================================================================

import 'package:flutter/material.dart';

/// 🟢 نموذج قواعد المحادثة وقيود عدم الإزعاج القادمة من السيرفر
class ChatRulesModel {
  final bool isUnlocked;
  final int sentMessagesCount;
  final int maxAllowedBeforeReply;
  final int remainingMessages;
  final String infoMessage;

  ChatRulesModel({
    required this.isUnlocked,
    required this.sentMessagesCount,
    required this.maxAllowedBeforeReply,
    required this.remainingMessages,
    required this.infoMessage,
  });

  factory ChatRulesModel.fromJson(Map<String, dynamic> json) {
    return ChatRulesModel(
      isUnlocked: json['is_unlocked'] == true ||
          json['is_unlocked'] == 1 ||
          json['is_unlocked'] == '1' ||
          json['is_unlocked'] == 'true',
      sentMessagesCount: json['sent_messages_count'] is int
          ? json['sent_messages_count']
          : int.tryParse(json['sent_messages_count']?.toString() ?? '0') ?? 0,
      maxAllowedBeforeReply: json['max_allowed_before_reply'] is int
          ? json['max_allowed_before_reply']
          : int.tryParse(json['max_allowed_before_reply']?.toString() ?? '3') ??
              3,
      remainingMessages: json['remaining_messages'] is int
          ? json['remaining_messages']
          : int.tryParse(json['remaining_messages']?.toString() ?? '0') ?? 0,
      infoMessage: json['info_message']?.toString() ?? '',
    );
  }
}

/// 🟢 نموذج بيانات غرفة المحادثة المطابق لـ ChatThreadResource مع دعم كامل للشارات
class ChatThreadModel {
  final int id;
  final int userId;
  final String name;
  final String image;
  final String gender;
  final String lastMessage;
  final String time;
  final int unreadCount;
  final bool isOnline;
  final String lastSeen;
  final String packageName; // الحقل الخاص بشارة الباقة

  ChatThreadModel({
    required this.id,
    required this.userId,
    required this.name,
    required this.image,
    required this.gender,
    required this.lastMessage,
    required this.time,
    required this.unreadCount,
    required this.isOnline,
    required this.lastSeen,
    this.packageName = '',
  });

  // --------------------------------------------------------------------------
  // 🟢 خصائص التمييز السريع للباقات داخل المحادثات (Package Classification Getters)
  // --------------------------------------------------------------------------

  /// هل يمتلك الطرف الآخر في المحادثة باقة نشطة؟
  bool get hasActivePackage => packageName.trim().isNotEmpty;

  /// هل الباقة ملكية؟
  bool get isRoyalPackage {
    final clean = packageName.trim().toLowerCase();
    return clean.contains('ملكي') ||
        clean.contains('royal') ||
        clean.contains('3');
  }

  /// هل الباقة ذهبية؟
  bool get isGoldPackage {
    final clean = packageName.trim().toLowerCase();
    return clean.contains('ذهبي') ||
        clean.contains('gold') ||
        clean.contains('شهر') ||
        clean.contains('month') ||
        clean == '1' ||
        clean == '2';
  }

  /// هل الباقة فضية / مميزة؟
  bool get isSilverPackage {
    if (!hasActivePackage) return false;
    return !isRoyalPackage && !isGoldPackage;
  }

  /// التأكد من وجود صورة شخصية مخصصة وليست افتراضية
  static bool hasCustomPhoto(String? url) {
    if (url == null || url.trim().isEmpty) return false;
    final clean = url.trim().toLowerCase();
    if (clean.contains('avatar-place.png') || clean.contains('default')) {
      return false;
    }
    return clean.startsWith('http://') || clean.startsWith('https://');
  }

  // --------------------------------------------------------------------------
  // 🟢 دالة تحليل واسعة واستخراج اسم الباقة من استجابات الشات المختلفة
  // --------------------------------------------------------------------------
  static String _extractPackageName(Map<String, dynamic> json) {
    final Map<String, dynamic> userMap = json['user'] is Map<String, dynamic>
        ? json['user'] as Map<String, dynamic>
        : <String, dynamic>{};

    final Map<String, dynamic> memberMap =
        json['member'] is Map<String, dynamic>
            ? json['member'] as Map<String, dynamic>
            : <String, dynamic>{};

    final Map<String, dynamic> partnerMap =
        json['partner'] is Map<String, dynamic>
            ? json['partner'] as Map<String, dynamic>
            : <String, dynamic>{};

    final List<dynamic> possiblePackageValues = [
      json['package_name'],
      json['package'],
      json['package_title'],
      json['member_package'],
      json['current_package'],
      json['user_package'],
      json['subscription_name'],
      userMap['package_name'],
      userMap['package'],
      userMap['package_title'],
      memberMap['package_name'],
      memberMap['package'],
      memberMap['package_title'],
      partnerMap['package_name'],
      partnerMap['package'],
      partnerMap['package_title'],
    ];

    for (var val in possiblePackageValues) {
      if (val == null) continue;

      if (val is String && val.trim().isNotEmpty) {
        return val.trim();
      }

      if (val is Map) {
        final String nestedName = (val['name'] ??
                val['title'] ??
                val['package_name'] ??
                val['name_ar'] ??
                val['name_en'] ??
                '')
            .toString()
            .trim();
        if (nestedName.isNotEmpty) return nestedName;
      }
    }

    final dynamic packageId = json['package_id'] ??
        userMap['package_id'] ??
        memberMap['package_id'] ??
        partnerMap['package_id'];

    if (packageId != null) {
      final String pIdStr = packageId.toString().trim();
      if (pIdStr == '3') return 'الباقة الملكية';
      if (pIdStr == '2' || pIdStr == '1') return 'الباقة الذهبية';
    }

    return '';
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'user_id': userId,
        'member_name': name,
        'member_photo': image,
        'member_gender': gender,
        'last_message': lastMessage,
        'last_message_at': time,
        'unseen_message_count': unreadCount,
        'is_online': isOnline,
        'last_seen': lastSeen,
        'package_name': packageName,
      };

  factory ChatThreadModel.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'] ?? json['chat_thread_id'] ?? json['thread_id'];
    final int parsedId =
        rawId is int ? rawId : int.tryParse(rawId?.toString() ?? '0') ?? 0;

    final rawUserId = json['user_id'] ??
        json['member_id'] ??
        json['partner_id'] ??
        json['active_member_id'] ??
        json['receiver_id'] ??
        (json['user'] is Map ? json['user']['id'] : null) ??
        (json['member'] is Map ? json['member']['id'] : null);
    final int parsedUserId = rawUserId is int
        ? rawUserId
        : int.tryParse(rawUserId?.toString() ?? '0') ?? 0;

    final rawUnread = json['unseen_message_count'] ??
        json['unread_count'] ??
        json['unread'] ??
        0;
    final int parsedUnread = rawUnread is int
        ? rawUnread
        : int.tryParse(rawUnread?.toString() ?? '0') ?? 0;

    final String name = json['member_name']?.toString() ??
        json['name']?.toString() ??
        json['user_name']?.toString() ??
        (json['user'] is Map ? json['user']['name']?.toString() : null) ??
        (json['member'] is Map ? json['member']['name']?.toString() : null) ??
        'عضو وصال';

    final String photo = json['member_photo']?.toString() ??
        json['photo']?.toString() ??
        json['avatar']?.toString() ??
        (json['user'] is Map ? json['user']['photo']?.toString() : null) ??
        (json['member'] is Map ? json['member']['photo']?.toString() : null) ??
        '';

    final String gender = json['member_gender']?.toString() ??
        json['gender']?.toString() ??
        (json['user'] is Map ? json['user']['gender']?.toString() : null) ??
        (json['member'] is Map ? json['member']['gender']?.toString() : null) ??
        'male';

    final String lastMessage = json['last_message']?.toString() ??
        json['message']?.toString() ??
        json['last_msg']?.toString() ??
        '';

    final String time = json['last_message_at']?.toString() ??
        json['last_message_time']?.toString() ??
        json['updated_at']?.toString() ??
        json['created_at']?.toString() ??
        json['time']?.toString() ??
        '';

    final rawLastSeen = json['last_seen'] ??
        json['last_active_at'] ??
        json['last_active'] ??
        json['active_at'];

    // 🟢 استخراج اسم الباقة بشكل حذر وموسع
    final String packageName = _extractPackageName(json);

    return ChatThreadModel(
      id: parsedId,
      userId: parsedUserId,
      name: name,
      image: photo,
      gender: gender,
      lastMessage: lastMessage,
      time: time,
      unreadCount: parsedUnread,
      isOnline: json['active'] == 1 ||
          json['active'] == true ||
          json['active'] == '1' ||
          json['active'] == 'true' ||
          json['is_online'] == true ||
          json['is_online'] == 1 ||
          json['is_online'] == '1' ||
          json['is_online'] == 'true' ||
          json['online'] == 1 ||
          json['online'] == true ||
          json['online'] == '1' ||
          json['online'] == 'true',
      lastSeen: rawLastSeen?.toString() ?? '',
      packageName: packageName,
    );
  }
}

/// 🟢 نموذج بيانات الرسالة الفردية
class ChatMessageModel {
  static final RegExp _cleanAttachmentRegExp = RegExp(r'[\[\]\\"]');

  final int id;
  final int senderId;
  final String message;
  final String time;
  final bool isMe;
  final bool isSeen;
  final String? attachmentUrl;

  ChatMessageModel({
    required this.id,
    required this.senderId,
    required this.message,
    required this.time,
    required this.isMe,
    required this.isSeen,
    this.attachmentUrl,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'sender_user_id': senderId,
        'message': message,
        'created_at': time,
        'seen': isSeen ? 1 : 0,
        'is_me': isMe,
        if (attachmentUrl != null) 'attachment': attachmentUrl,
      };

  factory ChatMessageModel.fromJson(
      Map<String, dynamic> json, int partnerUserId,
      {int currentUserId = 0}) {
    final rawSender = json['sender_user_id'] ??
        json['user_id'] ??
        json['from_user_id'] ??
        json['sender_id'] ??
        json['created_by'] ??
        0;
    final int parsedSenderId =
        rawSender is int ? rawSender : int.tryParse(rawSender.toString()) ?? 0;

    bool isMyMessage = false;

    if (currentUserId != 0 && parsedSenderId != 0) {
      isMyMessage = (parsedSenderId == currentUserId);
    } else if (partnerUserId != 0 && parsedSenderId != 0) {
      isMyMessage = (parsedSenderId != partnerUserId);
    } else if (json['is_me'] != null) {
      isMyMessage = json['is_me'] == true ||
          json['is_me'] == 1 ||
          json['is_me'] == '1' ||
          json['is_me'] == 'true';
    } else if (json['is_sender'] != null) {
      isMyMessage = json['is_sender'] == true ||
          json['is_sender'] == 1 ||
          json['is_sender'] == '1' ||
          json['is_sender'] == 'true';
    } else {
      isMyMessage = true;
    }

    final bool parsedSeen = json['seen'] == 1 ||
        json['seen'] == true ||
        json['is_seen'] == true ||
        json['seen'] == '1' ||
        json['seen'] == 'true';

    String? parsedAttachment;
    try {
      if (json['attachment'] != null) {
        final att = json['attachment'];
        if (att is List && att.isNotEmpty) {
          final firstItem = att.first;
          if (firstItem is Map) {
            parsedAttachment = firstItem['attachment']?.toString() ??
                firstItem['url']?.toString() ??
                firstItem['file_name_url']?.toString() ??
                firstItem['file_name']?.toString();
          } else if (firstItem is String) {
            parsedAttachment = firstItem;
          }
        } else if (att is Map) {
          parsedAttachment = att['attachment']?.toString() ??
              att['url']?.toString() ??
              att['file_name_url']?.toString() ??
              att['file_name']?.toString();
        } else if (att is String && att.isNotEmpty) {
          parsedAttachment = att;
        }
      }

      if (parsedAttachment != null && parsedAttachment.isNotEmpty) {
        String attStr =
            parsedAttachment.replaceAll(_cleanAttachmentRegExp, '').trim();

        // تنظيف السلاش المبتدئ لتفادي تكوّن رابط مكرر
        while (attStr.startsWith('/')) {
          attStr = attStr.substring(1).trim();
        }

        if (!attStr.startsWith('http://') && !attStr.startsWith('https://')) {
          if (!attStr.startsWith('public/')) {
            attStr = 'public/$attStr';
          }
          attStr = 'https://www.wesalsyria.com/$attStr';
        }
        parsedAttachment = attStr;
      }
    } catch (e) {
      debugPrint('⚠️ [Attachment Parsing Issue]: $e');
    }

    final rawMessageId = json['id'] ?? json['message_id'] ?? json['chat_id'];
    final int parsedMessageId = rawMessageId is int
        ? rawMessageId
        : int.tryParse(rawMessageId?.toString() ?? '0') ?? 0;

    return ChatMessageModel(
      id: parsedMessageId,
      senderId: parsedSenderId,
      message: json['message']?.toString() ?? '',
      time: json['created_at']?.toString() ?? json['time']?.toString() ?? '',
      isMe: isMyMessage,
      isSeen: parsedSeen,
      attachmentUrl: parsedAttachment,
    );
  }
}

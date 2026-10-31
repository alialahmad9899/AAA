// ============================================================================
// اسم الملف: lib/models/support_ticket_model.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: نماذج بيانات تذاكر الدعم الفني، الأقسام، والردود
// ============================================================================

class SupportCategoryModel {
  final int id;
  final String name;

  SupportCategoryModel({
    required this.id,
    required this.name,
  });

  factory SupportCategoryModel.fromJson(Map<String, dynamic> json) {
    return SupportCategoryModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? json['category_name'] ?? 'قسم عام',
    );
  }
}

class SupportReplyModel {
  final int id;
  final int repliedUserId;
  final String repliedUserName;
  final String repliedUserImage;
  final String reply;
  final String attachments;
  final String createdAt;

  SupportReplyModel({
    required this.id,
    required this.repliedUserId,
    required this.repliedUserName,
    required this.repliedUserImage,
    required this.reply,
    required this.attachments,
    required this.createdAt,
  });

  factory SupportReplyModel.fromJson(
      Map<String, dynamic> json, int currentUserId) {
    final int userId = json['replied_user_id'] is int
        ? json['replied_user_id']
        : (int.tryParse(json['replied_user_id']?.toString() ?? '0') ?? 0);

    return SupportReplyModel(
      id: json['id'] ?? 0,
      repliedUserId: userId,
      repliedUserName: json['replied_user_name'] ?? 'فريق الدعم الفني',
      repliedUserImage: json['replied_user_image'] ?? '',
      reply: json['reply'] ?? '',
      attachments: json['reply_attachment'] ?? json['attachments'] ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class SupportTicketModel {
  final int id;
  final String ticketId;
  final String subject;
  final int categoryId;
  final String categoryName;
  final String description;
  final String status; // "0" قيد المتابعة، "1" تم الرد / مغلقة
  final String attachments;
  final String createdAt;
  final List<SupportReplyModel> replies;

  SupportTicketModel({
    required this.id,
    required this.ticketId,
    required this.subject,
    required this.categoryId,
    required this.categoryName,
    required this.description,
    required this.status,
    required this.attachments,
    required this.createdAt,
    required this.replies,
  });

  factory SupportTicketModel.fromJson(Map<String, dynamic> json,
      {int currentUserId = 0}) {
    List<SupportReplyModel> parsedReplies = [];

    // دعم قراءة الردود سواء أُرسلت بـ reply أو replies من Laravel
    final rawReplies = json['reply'] ?? json['replies'];
    if (rawReplies != null && rawReplies is List) {
      parsedReplies = rawReplies
          .map((r) => SupportReplyModel.fromJson(r, currentUserId))
          .toList();
    }

    return SupportTicketModel(
      id: json['id'] ?? 0,
      ticketId: json['ticket_id']?.toString() ?? json['id']?.toString() ?? '',
      subject: json['subject'] ?? 'بدون عنوان',
      categoryId: json['support_category_id'] is int
          ? json['support_category_id']
          : (int.tryParse(json['support_category_id']?.toString() ?? '0') ?? 0),
      categoryName:
          json['support_category_name'] ?? json['category_name'] ?? 'عام',
      description: json['description'] ?? '',
      status: json['status']?.toString() ?? '0',
      attachments: json['attachments'] ?? json['attachment'] ?? '',
      createdAt: json['created_at']?.toString() ?? '',
      replies: parsedReplies,
    );
  }
}

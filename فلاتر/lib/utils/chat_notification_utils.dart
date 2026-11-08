String chatNotificationType(Map<String, dynamic> data) {
  return (data['type'] ?? data['notification_type'] ?? data['category'] ?? '')
      .toString()
      .trim()
      .toLowerCase()
      .replaceAll('-', '_');
}

bool isChatNotificationType(Map<String, dynamic> data) {
  final type = chatNotificationType(data);
  return type.contains('chat') ||
      type.contains('message') ||
      type.contains('conversation');
}

int chatThreadIdFromNotification(Map<String, dynamic> data) {
  final raw = data['thread_id'] ??
      data['chat_thread_id'] ??
      data['info_id'];
  if (raw is int) return raw;
  return int.tryParse(raw?.toString() ?? '') ?? 0;
}

int chatNotificationId(int threadId, String? messageId, int fallback) {
  if (threadId > 0) return threadId.hashCode;
  final cleanId = messageId?.trim() ?? '';
  return cleanId.isNotEmpty ? cleanId.hashCode : fallback;
}

bool isSilentChatNotification(dynamic value) {
  if (value == true || value == 1) return true;
  final normalized = value?.toString().trim().toLowerCase() ?? '';
  return normalized == '1' || normalized == 'true' || normalized == 'yes';
}

bool claimNotificationTap(
  String id, {
  required Set<String> handled,
  required Set<String> inProgress,
}) {
  if (handled.contains(id)) return false;
  return inProgress.add(id);
}

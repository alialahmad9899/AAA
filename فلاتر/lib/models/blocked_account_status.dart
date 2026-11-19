class BlockedAccountStatus {
  final bool isBlocked;
  final String reason;

  const BlockedAccountStatus({
    required this.isBlocked,
    required this.reason,
  });

  factory BlockedAccountStatus.fromJson(Map<String, dynamic> json) {
    final nested = json['data'];
    final Map<String, dynamic> payload = nested is Map
        ? Map<String, dynamic>.from(nested)
        : json;

    final rawBlocked =
        payload['blocked'] ?? payload['is_blocked'] ?? payload['is_banned'];
    final normalized = rawBlocked?.toString().trim().toLowerCase();
    final isBlocked = rawBlocked == true ||
        rawBlocked == 1 ||
        normalized == '1' ||
        normalized == 'true' ||
        normalized == 'yes';

    return BlockedAccountStatus(
      isBlocked: isBlocked,
      reason: (payload['blocking_reason'] ?? payload['blocked_reason'] ?? '')
          .toString()
          .trim(),
    );
  }
}

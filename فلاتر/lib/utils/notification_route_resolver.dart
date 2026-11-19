/// Resolves only known, in-app notification destinations.
///
/// Remote/admin URLs are never opened directly. The route string is parsed as
/// metadata and accepted only for the supported Wesal destinations below.
enum NotificationDestinationKind {
  profile,
  supportTicket,
  chat,
  packageInvoice,
  interests,
}

class NotificationDestination {
  const NotificationDestination(this.kind, {this.id});

  final NotificationDestinationKind kind;
  final int? id;
}

NotificationDestination? resolveNotificationDestination(
  Map<String, dynamic> data,
) {
  final String type = _normaliseType(
    data['type'] ?? data['notification_type'] ?? data['action'],
  );

  int? firstId(Iterable<String> keys) {
    for (final String key in keys) {
      final dynamic value = data[key];
      final int? parsed = int.tryParse(value?.toString().trim() ?? '');
      if (parsed != null && parsed > 0) return parsed;
    }
    return null;
  }

  if (type.contains('support') || type.contains('ticket')) {
    final int? id = firstId(const [
      'ticket_id',
      'ticketId',
      'support_ticket_id',
      'support_id',
      'info_id',
      'id',
    ]);
    if (id != null) {
      return NotificationDestination(
        NotificationDestinationKind.supportTicket,
        id: id,
      );
    }
  }

  if (type.contains('interest') ||
      type.contains('match') ||
      type.contains('like')) {
    final int? actorId = firstId(const [
      'notify_by',
      'actor_id',
      'sender_id',
      'interested_by',
      'user_id',
      'member_id',
      'viewer_id',
    ]);
    if (actorId != null) {
      return NotificationDestination(
        NotificationDestinationKind.profile,
        id: actorId,
      );
    }
    return const NotificationDestination(NotificationDestinationKind.interests);
  }

  if (type.contains('chat') ||
      type.contains('message') ||
      type.contains('conversation')) {
    final int? id = firstId(const [
      'thread_id',
      'threadId',
      'chat_thread_id',
      'chat_id',
      'chatId',
      'conversation_id',
      'conversationId',
      'info_id',
    ]);
    if (id != null) {
      return NotificationDestination(
        NotificationDestinationKind.chat,
        id: id,
      );
    }
  }

  if (type.contains('package') ||
      type.contains('invoice') ||
      type.contains('purchase')) {
    final int? id = firstId(const [
      'payment_id',
      'package_payment_id',
      'invoice_id',
      'info_id',
    ]);
    if (id != null) {
      return NotificationDestination(
        NotificationDestinationKind.packageInvoice,
        id: id,
      );
    }
  }

  if (type.contains('profile') ||
      type.contains('member') ||
      type.contains('visitor')) {
    final int? id = firstId(const [
      'viewer_id',
      'user_id',
      'userId',
      'member_id',
      'memberId',
      'profile_id',
      'sender_id',
      'visitor_id',
      'info_id',
      'notify_by',
    ]);
    if (id != null) {
      return NotificationDestination(
        NotificationDestinationKind.profile,
        id: id,
      );
    }
  }

  return _resolveKnownRoute(data['route']?.toString() ?? '');
}

String _normaliseType(dynamic value) {
  return (value?.toString() ?? '')
      .trim()
      .toLowerCase()
      .replaceAll('-', '_')
      .replaceAll(' ', '_');
}

NotificationDestination? _resolveKnownRoute(String rawRoute) {
  final String value = rawRoute.trim();
  if (value.isEmpty) return null;

  final Uri? uri = Uri.tryParse(value);
  if (uri == null) return null;

  final List<String> segments = uri.pathSegments
      .where((segment) => segment.trim().isNotEmpty)
      .toList();
  final String routeName =
      (uri.scheme == 'wesal' && uri.host.isNotEmpty)
          ? uri.host.toLowerCase().replaceAll('-', '_')
          : (segments.isNotEmpty
              ? segments.first.toLowerCase().replaceAll('-', '_')
              : uri.host.toLowerCase());

  int? routeId(Iterable<String> queryKeys, {int segmentIndex = 1}) {
    for (final String key in queryKeys) {
      final int? parsed = int.tryParse(uri.queryParameters[key] ?? '');
      if (parsed != null && parsed > 0) return parsed;
    }
    final int effectiveSegmentIndex =
        uri.scheme == 'wesal' && uri.host.isNotEmpty
            ? segmentIndex - 1
            : segmentIndex;
    final int? parsedFromPath =
        effectiveSegmentIndex >= 0 && effectiveSegmentIndex < segments.length
            ? int.tryParse(segments[effectiveSegmentIndex])
            : null;
    return parsedFromPath != null && parsedFromPath > 0
        ? parsedFromPath
        : null;
  }

  if (const {'package_invoice', 'invoice', 'package', 'package_purchase'}
      .contains(routeName)) {
    final int? id = routeId(const [
      'payment_id',
      'package_payment_id',
      'invoice_id',
      'id',
    ]);
    if (id != null) {
      return NotificationDestination(
        NotificationDestinationKind.packageInvoice,
        id: id,
      );
    }
  }

  if (const {'support_ticket', 'support', 'ticket'}.contains(routeName)) {
    final int? id = routeId(const [
      'ticket_id',
      'support_ticket_id',
      'support_id',
      'id',
    ]);
    if (id != null) {
      return NotificationDestination(
        NotificationDestinationKind.supportTicket,
        id: id,
      );
    }
  }

  if (const {'member', 'profile', 'member_profile', 'visitor'}
      .contains(routeName)) {
    final int? id = routeId(const [
      'user_id',
      'member_id',
      'profile_id',
      'viewer_id',
      'id',
    ]);
    if (id != null) {
      return NotificationDestination(
        NotificationDestinationKind.profile,
        id: id,
      );
    }
  }

  if (const {'chat', 'conversation', 'message'}.contains(routeName)) {
    final int? id = routeId(const [
      'thread_id',
      'chat_thread_id',
      'conversation_id',
      'chat_id',
      'id',
    ]);
    if (id != null) {
      return NotificationDestination(
        NotificationDestinationKind.chat,
        id: id,
      );
    }
  }

  if (const {'interests', 'my_interests', 'interest_requests'}
      .contains(routeName)) {
    return const NotificationDestination(NotificationDestinationKind.interests);
  }

  return null;
}

/// Resolves only known, in-app notification destinations.
/// Generic info_id values are never treated as payment IDs.
enum NotificationDestinationKind { profile, profileVisitors, supportTicket, chat, packageInvoice, packagePurchaseHistory, interests }
class NotificationDestination {
  const NotificationDestination(this.kind, {this.id});
  final NotificationDestinationKind kind;
  final int? id;
}
NotificationDestination? resolveNotificationDestination(Map<String, dynamic> data) {
  final String type = _normaliseType(data['type'] ?? data['notification_type'] ?? data['action']);
  final String text = [data['title'], data['message'], data['body']].whereType<String>().join(' ').toLowerCase();
  int? firstId(Iterable<String> keys) { for (final String key in keys) { final int? parsed = int.tryParse(data[key]?.toString().trim() ?? ''); if (parsed != null && parsed > 0) return parsed; } return null; }
  final int? paymentId = firstId(const ['payment_id', 'package_payment_id', 'invoice_id']);
  if (paymentId != null) return NotificationDestination(NotificationDestinationKind.packageInvoice, id: paymentId);
  final NotificationDestination? routeDestination = _resolveKnownRoute(data['route']?.toString() ?? '');
  if (routeDestination?.kind == NotificationDestinationKind.packageInvoice) return routeDestination;
  if (type.contains('support') || type.contains('ticket')) { final int? id = firstId(const ['ticket_id','ticketId','support_ticket_id','support_id','info_id','id']); if (id != null) return NotificationDestination(NotificationDestinationKind.supportTicket,id:id); }
  final bool interest = type.contains('interest') || type.contains('match') || type.contains('like') || text.contains('إعجاب') || text.contains('اعجاب') || text.contains('اهتمام') || text.contains('interest') || text.contains('like');
  if (interest) { final int? id = firstId(const ['notify_by','actor_id','sender_id','interested_by','user_id','member_id','viewer_id']); if (id != null) return NotificationDestination(NotificationDestinationKind.profile,id:id); return const NotificationDestination(NotificationDestinationKind.interests); }
  if (type.contains('chat') || type.contains('message') || type.contains('conversation')) { final int? id=firstId(const ['thread_id','threadId','chat_thread_id','chat_id','chatId','conversation_id','conversationId','info_id']); if(id!=null)return NotificationDestination(NotificationDestinationKind.chat,id:id); }
  final bool visit=type.contains('profile_view') || type.contains('profile_visit') || type.contains('visitor') || type=='profile_visited' || text.contains('زيارة ملفك الشخصي');
  if(visit){final int? id=firstId(const ['viewer_id','notify_by','actor_id','sender_id','user_id','member_id','info_id']);if(id!=null)return NotificationDestination(NotificationDestinationKind.profile,id:id);return const NotificationDestination(NotificationDestinationKind.profileVisitors);}
  if(type.contains('profile')||type.contains('member')){final int? id=firstId(const ['viewer_id','user_id','userId','member_id','memberId','profile_id','sender_id','visitor_id','notify_by']);if(id!=null)return NotificationDestination(NotificationDestinationKind.profile,id:id);}
  if(type.contains('package')||type.contains('invoice')||type.contains('purchase')||text.contains('فاتورة')||text.contains('invoice')||text.contains('الباقة')||text.contains('باقة'))return const NotificationDestination(NotificationDestinationKind.packagePurchaseHistory);
  return routeDestination;
}
String _normaliseType(dynamic value)=>(value?.toString()??'').trim().toLowerCase().replaceAll('-','_').replaceAll(' ','_');
NotificationDestination? _resolveKnownRoute(String rawRoute){
  final String value=rawRoute.trim();if(value.isEmpty)return null;final Uri? uri=Uri.tryParse(value);if(uri==null)return null;
  final List<String> segments=uri.pathSegments.where((s)=>s.trim().isNotEmpty).toList();
  final String name=(uri.scheme=='wesal'&&uri.host.isNotEmpty)?uri.host.toLowerCase().replaceAll('-','_'):(segments.isNotEmpty?segments.first.toLowerCase().replaceAll('-','_'):uri.host.toLowerCase());
  int? routeId(Iterable<String> keys,{int segmentIndex=1}){for(final String key in keys){final int? x=int.tryParse(uri.queryParameters[key]??'');if(x!=null&&x>0)return x;}final int i=uri.scheme=='wesal'&&uri.host.isNotEmpty?segmentIndex-1:segmentIndex;final int? x=i>=0&&i<segments.length?int.tryParse(segments[i]):null;return x!=null&&x>0?x:null;}
  if(const {'package_invoice','invoice','package','package_purchase'}.contains(name)){final int? id=routeId(const ['payment_id','package_payment_id','invoice_id','id']);if(id!=null)return NotificationDestination(NotificationDestinationKind.packageInvoice,id:id);}
  if(const {'package_history','package_purchase_history','purchase_history'}.contains(name))return const NotificationDestination(NotificationDestinationKind.packagePurchaseHistory);
  if(const {'support_ticket','support','ticket'}.contains(name)){final int? id=routeId(const ['ticket_id','support_ticket_id','support_id','id']);if(id!=null)return NotificationDestination(NotificationDestinationKind.supportTicket,id:id);}
  if(const {'member','profile','member_profile','visitor'}.contains(name)){final int? id=routeId(const ['user_id','member_id','profile_id','viewer_id','id']);if(id!=null)return NotificationDestination(NotificationDestinationKind.profile,id:id);if(name=='visitor')return const NotificationDestination(NotificationDestinationKind.profileVisitors);}
  if(const {'profile_visitors','my_profile_viewers','profile_views'}.contains(name))return const NotificationDestination(NotificationDestinationKind.profileVisitors);
  if(const {'chat','conversation','message'}.contains(name)){final int? id=routeId(const ['thread_id','chat_thread_id','conversation_id','chat_id','id']);if(id!=null)return NotificationDestination(NotificationDestinationKind.chat,id:id);}
  if(const {'interests','my_interests','interest_requests'}.contains(name))return const NotificationDestination(NotificationDestinationKind.interests);
  return null;
}


/// Stable key for a resolved destination, independent of the notification
/// transport (FCM, local notification, or in-app notification center).
String notificationDestinationKey(NotificationDestination? destination) {
  if (destination == null) return 'notification-center';
  return '${destination.kind.name}:${destination.id ?? 0}';
}

/// Keeps one route per destination on the navigation stack. A notification
/// delivered through two transports must not open the same screen twice.
bool claimNotificationDestination(
  String key, {
  required Set<String> activeDestinations,
}) {
  return activeDestinations.add(key);
}

void releaseNotificationDestination(
  String key, {
  required Set<String> activeDestinations,
}) {
  activeDestinations.remove(key);
}

/// Returns true when the requested chat is already the visible conversation.
/// Opening it again from a notification would only stack a duplicate route.
bool isNotificationDestinationAlreadyVisible(
  NotificationDestination? destination, {
  required int activeChatThreadId,
}) {
  return activeChatThreadId > 0 &&
      destination?.kind == NotificationDestinationKind.chat &&
      destination?.id == activeChatThreadId;
}

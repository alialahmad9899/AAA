// ============================================================================
// اسم الملف: lib/screens/notifications_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة مركز التنبيهات مع دعم الإشعار السفلي الزجاجي المترجم
//        والتفاعل الفوري مع تحديثات السيرفر دون أي أخطاء.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/colors.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../models/notification_model.dart';
import '../services/api_service.dart';
import '../widgets/app_snackbar.dart';

class NotificationsScreen extends StatefulWidget {
  final String currentLanguage;

  const NotificationsScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<NotificationModel> _notifications = [];
  bool _isLoading = true;
  String? _errorMessage;

  Map<String, String> get _labels => widget.currentLanguage == 'en'
      ? {
          'title': 'Notifications Center',
          'mark_all': 'Mark all as read',
          'no_notif': 'No notifications at the moment',
          'error': 'Failed to fetch notifications. Please try again later.',
          'retry': 'Retry',
          'marked_success': 'All notifications marked as read',
          'close': 'Close',
        }
      : {
          'title': 'مركز التنبيهات',
          'mark_all': 'تحديد الكل كمقروء',
          'no_notif': 'لا توجد إشعارات حالياً',
          'error': 'تعذر جلب الإشعارات حالياً، يرجى المحاولة لاحقاً',
          'retry': 'إعادة المحاولة',
          'marked_success': 'تم تحديد جميع الإشعارات كمقروءة',
          'close': 'إغلاق',
        };

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  /// جلب قائمة الإشعارات من السيرفر
  Future<void> _fetchNotifications() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final rawList = await ApiService.getNotifications();
      if (mounted) {
        setState(() {
          _notifications = rawList;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = _labels['error'];
          _isLoading = false;
        });
      }
    }
  }

  /// تحديد إشعار منفرد كمقروء
  Future<void> _markSingleAsRead(NotificationModel notification) async {
    if (!notification.isNew || notification.notificationId.isEmpty) return;

    setState(() {
      final index = _notifications
          .indexWhere((n) => n.notificationId == notification.notificationId);
      if (index != -1) {
        _notifications[index] = NotificationModel(
          notificationId: notification.notificationId,
          type: notification.type,
          notifyBy: notification.notifyBy,
          photo: notification.photo,
          title: notification.title,
          message: notification.message,
          time: notification.time,
          isNew: false,
          check: notification.check,
        );
      }
    });

    try {
      await ApiService.markNotificationAsRead(notification.notificationId);
    } catch (e) {
      debugPrint("❌ فشل تحديد الإشعار كمقروء في السيرفر: $e");
    }
  }

  /// تحديد جميع الإشعارات كمقروءة مع معالجة الاستثناءات
  Future<void> _markAllAsRead() async {
    try {
      final bool success = await ApiService.markAllNotificationsAsRead();

      if (!mounted) return;

      if (success) {
        setState(() {
          _notifications = _notifications
              .map((n) => NotificationModel(
                    notificationId: n.notificationId,
                    type: n.type,
                    notifyBy: n.notifyBy,
                    photo: n.photo,
                    title: n.title,
                    message: n.message,
                    time: n.time,
                    isNew: false,
                    check: n.check,
                  ))
              .toList();
        });

        AppSnackBar.show(
          context: context,
          type: NotificationType.success,
          isArabic: widget.currentLanguage == 'ar',
          messageAr: 'تم تحديد جميع الإشعارات كمقروءة بنجاح',
          messageEn: 'All notifications marked as read successfully',
        );
      } else {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: widget.currentLanguage == 'ar',
          messageAr: 'تعذر تحديد جميع الإشعارات كمقروءة',
          messageEn: 'Failed to mark all notifications as read',
        );
      }
    } catch (e) {
      if (mounted) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: widget.currentLanguage == 'ar',
          messageAr: 'حدث خطأ أثناء الاتصال بالسيرفر',
          messageEn: 'Network connection error',
        );
      }
    }
  }

  /// عرض نافذة تفاصيل الإشعار الفاخرة
  void _showNotificationDetailsModal(NotificationModel notification) {
    _markSingleAsRead(notification);

    showDialog(
      context: context,
      barrierDismissible: true,
      barrierColor: Colors.black.withValues(alpha: 0.65),
      builder: (BuildContext context) {
        final styleConfig = _getNotificationStyle(notification.type);
        final Color themeColor = styleConfig['color'] as Color;
        final String displayTitle =
            notification.title.isNotEmpty && notification.title != '--'
                ? notification.title
                : styleConfig['title'] as String;

        return Directionality(
          textDirection: widget.currentLanguage == 'en'
              ? TextDirection.ltr
              : TextDirection.rtl,
          child: Dialog(
            backgroundColor: Colors.transparent,
            elevation: 0,
            insetPadding:
                const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(32),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 30, sigmaY: 30),
                child: Container(
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    color: AppColors.cardColor.withValues(alpha: 0.92),
                    borderRadius: BorderRadius.circular(32),
                    border: Border.all(
                      color: themeColor.withValues(alpha: 0.4),
                      width: 1.5,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: themeColor.withValues(alpha: 0.12),
                        blurRadius: 30,
                        spreadRadius: 5,
                      ),
                    ],
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          _buildAvatar(notification, styleConfig, radius: 24),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  displayTitle,
                                  style: const TextStyle(
                                    color: AppColors.textDark,
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                                if (notification.time.isNotEmpty)
                                  Text(
                                    notification.time,
                                    style: const TextStyle(
                                      color: AppColors.textMuted,
                                      fontSize: 11,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                              ],
                            ),
                          ),
                          IconButton(
                            style: IconButton.styleFrom(
                              backgroundColor:
                                  AppColors.sectionBackground,
                              shape: const CircleBorder(),
                            ),
                            icon: const Icon(Icons.close_rounded,
                                color: AppColors.textDark, size: 20),
                            onPressed: () => Navigator.pop(context),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      const Divider(color: AppColors.lineColor, height: 1),
                      const SizedBox(height: 18),
                      ConstrainedBox(
                        constraints: BoxConstraints(
                          maxHeight: MediaQuery.of(context).size.height * 0.45,
                        ),
                        child: SingleChildScrollView(
                          physics: const BouncingScrollPhysics(),
                          child: Text(
                            notification.message,
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 15,
                              height: 1.6,
                              fontFamily: 'Cairo',
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 22),
                      SizedBox(
                        width: double.infinity,
                        height: 48,
                        child: Container(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(18),
                            gradient: LinearGradient(
                              colors: [
                                themeColor.withValues(alpha: 0.85),
                                themeColor.withValues(alpha: 0.60),
                              ],
                            ),
                          ),
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.transparent,
                              shadowColor: Colors.transparent,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(18),
                              ),
                            ),
                            onPressed: () => Navigator.pop(context),
                            child: Text(
                              _labels['close']!,
                              style: const TextStyle(
                                color: AppColors.textDark,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                                fontSize: 14,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    const Color bgDark = AppColors.backgroundColor;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.dark,
        systemNavigationBarColor: bgDark,
        systemNavigationBarIconBrightness: Brightness.dark,
        systemNavigationBarDividerColor: Colors.transparent,
      ),
      child: Scaffold(
        backgroundColor: bgDark,
        body: Directionality(
          textDirection: widget.currentLanguage == 'en'
              ? TextDirection.ltr
              : TextDirection.rtl,
          child: Container(
            width: double.infinity,
            height: double.infinity,
            decoration: const BoxDecoration(
              gradient: RadialGradient(
                center: Alignment(-0.8, -0.6),
                radius: 1.2,
                colors: [
                  Color(0xFF1E293B),
                  Color(0xFF0F172A),
                  Color(0xFF070A11),
                ],
              ),
            ),
            child: SafeArea(
              child: Column(
                children: [
                  _buildHeader(context),
                  const SizedBox(height: 10),
                  Expanded(
                    child: _buildBodyContent(),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHeader(BuildContext context) {
    final int unreadCount = _notifications.where((n) => n.isNew).length;
    final bool isAr = widget.currentLanguage == 'ar';

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 18.0, vertical: 10.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          _buildGlassIconButton(
            icon: isAr
                ? Icons.arrow_back_ios_rounded
                : Icons.arrow_back_ios_new_rounded,
            onPressed: () => Navigator.pop(context),
          ),
          ClipRRect(
            borderRadius: BorderRadius.circular(22),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.cardColor,
                  borderRadius: BorderRadius.circular(22),
                  border:
                      Border.all(color: AppColors.lineColor),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.notifications_active_rounded,
                        color: AppColors.primaryTeal, size: 20),
                    const SizedBox(width: 10),
                    Text(
                      _labels['title']!,
                      style: const TextStyle(
                        color: AppColors.textDark,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    if (unreadCount > 0) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: AppColors.primaryTeal,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: const [
                            BoxShadow(
                              color: AppColors.primaryTeal,
                              blurRadius: 8,
                            )
                          ],
                        ),
                        child: Text(
                          '$unreadCount',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
          _buildGlassIconButton(
            icon: Icons.done_all_rounded,
            tooltip: _labels['mark_all'],
            onPressed:
                _notifications.any((n) => n.isNew) ? _markAllAsRead : null,
          ),
        ],
      ),
    );
  }

  Widget _buildGlassIconButton({
    required IconData icon,
    required VoidCallback? onPressed,
    String? tooltip,
  }) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(18),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
        child: Container(
          decoration: BoxDecoration(
            color: AppColors.cardColor,
            shape: BoxShape.circle,
            border: Border.all(color: AppColors.lineColor),
          ),
          child: IconButton(
            icon: Icon(icon,
                color: onPressed != null ? AppColors.textDark : AppColors.textDark24,
                size: 18),
            tooltip: tooltip,
            onPressed: onPressed,
          ),
        ),
      ),
    );
  }

  Widget _buildBodyContent() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: AppColors.primaryTeal),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.wifi_off_rounded,
                  color: AppColors.textMuted, size: 56),
              const SizedBox(height: 14),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(
                    color: AppColors.textDark, fontSize: 14, fontFamily: 'Cairo'),
              ),
              const SizedBox(height: 18),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primaryTeal,
                  foregroundColor: Colors.white,
                  padding:
                      const EdgeInsets.symmetric(horizontal: 22, vertical: 10),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16)),
                ),
                icon: const Icon(Icons.refresh_rounded, size: 18),
                label: Text(_labels['retry']!,
                    style: const TextStyle(
                        fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
                onPressed: _fetchNotifications,
              ),
            ],
          ),
        ),
      );
    }

    if (_notifications.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(26),
              decoration: BoxDecoration(
                color: AppColors.sectionBackground,
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.lineColor),
              ),
              child: const Icon(Icons.notifications_off_outlined,
                  color: AppColors.textDark24, size: 48),
            ),
            const SizedBox(height: 16),
            Text(
              _labels['no_notif']!,
              style: const TextStyle(
                color: AppColors.textMuted,
                fontSize: 15,
                fontWeight: FontWeight.w600,
                fontFamily: 'Cairo',
              ),
            ),
          ],
        ),
      );
    }

    final double bottomPadding = MediaQuery.of(context).padding.bottom + 18;

    return RefreshIndicator(
      color: AppColors.primaryTeal,
      backgroundColor: AppColors.cardColor,
      onRefresh: _fetchNotifications,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(
          parent: BouncingScrollPhysics(),
        ),
        padding: EdgeInsets.fromLTRB(18, 8, 18, bottomPadding),
        itemCount: _notifications.length,
        itemBuilder: (context, index) {
          final notification = _notifications[index];
          return _buildNotificationCard(notification);
        },
      ),
    );
  }

  Widget _buildNotificationCard(NotificationModel notification) {
    final styleConfig = _getNotificationStyle(notification.type);
    final Color cardColor = styleConfig['color'] as Color;
    final String displayTitle =
        notification.title.isNotEmpty && notification.title != '--'
            ? notification.title
            : styleConfig['title'] as String;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: notification.isNew
            ? AppColors.cardColor.withValues(alpha: 0.80)
            : AppColors.sectionBackground.withValues(alpha: 0.50),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: notification.isNew
              ? cardColor.withValues(alpha: 0.5)
              : AppColors.lineColor,
          width: notification.isNew ? 1.2 : 0.8,
        ),
        boxShadow: notification.isNew
            ? [
                BoxShadow(
                  color: cardColor.withValues(alpha: 0.12),
                  blurRadius: 16,
                  offset: const Offset(0, 4),
                )
              ]
            : [],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(24),
        child: InkWell(
          borderRadius: BorderRadius.circular(24),
          onTap: () => _showNotificationDetailsModal(notification),
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildAvatar(notification, styleConfig),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              displayTitle,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                color: notification.isNew
                                    ? AppColors.textDark
                                    : AppColors.textMuted,
                                fontSize: 14.5,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ),
                          if (notification.time.isNotEmpty)
                            Text(
                              notification.time,
                              style: const TextStyle(
                                color: AppColors.textMuted,
                                fontSize: 11,
                                fontFamily: 'Cairo',
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        notification.message,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 13,
                          height: 1.4,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ],
                  ),
                ),
                if (notification.isNew) ...[
                  const SizedBox(width: 10),
                  Container(
                    margin: const EdgeInsets.only(top: 4),
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: cardColor,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: cardColor,
                          blurRadius: 8,
                          spreadRadius: 2,
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildAvatar(
      NotificationModel notification, Map<String, dynamic> config,
      {double radius = 22}) {
    final Color iconColor = config['color'] as Color;
    final String photoUrl = notification.photo.trim();
    final bool hasValidPhoto = photoUrl.isNotEmpty &&
        photoUrl != 'null' &&
        (photoUrl.startsWith('http://') || photoUrl.startsWith('https://'));

    if (hasValidPhoto) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(radius),
        child: CachedNetworkImage(
          imageUrl: photoUrl,
          width: radius * 2,
          height: radius * 2,
          fit: BoxFit.cover,
          placeholder: (context, url) => Container(
            width: radius * 2,
            height: radius * 2,
            color: Colors.white10,
            child: Center(
              child: SizedBox(
                width: radius,
                height: radius,
                child: const CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Color(0xFF38BDF8),
                ),
              ),
            ),
          ),
          errorWidget: (context, url, error) => Container(
            width: radius * 2,
            height: radius * 2,
            decoration: BoxDecoration(
              color: iconColor.withValues(alpha: 0.15),
              shape: BoxShape.circle,
              border: Border.all(
                color: iconColor.withValues(alpha: 0.35),
                width: 1.0,
              ),
            ),
            child: Icon(config['icon'] as IconData,
                color: iconColor, size: radius * 1.0),
          ),
        ),
      );
    }

    return Container(
      width: radius * 2,
      height: radius * 2,
      decoration: BoxDecoration(
        color: iconColor.withValues(alpha: 0.15),
        shape: BoxShape.circle,
        border: Border.all(
          color: iconColor.withValues(alpha: 0.35),
          width: 1.0,
        ),
      ),
      child: Icon(config['icon'] as IconData,
          color: iconColor, size: radius * 1.0),
    );
  }

  Map<String, dynamic> _getNotificationStyle(String type) {
    final lower = type.toLowerCase();
    final bool isEn = widget.currentLanguage == 'en';

    if (lower.contains('package') ||
        lower.contains('باقة') ||
        lower.contains('subscription')) {
      return {
        'title': isEn ? 'Package Activated' : 'تفعيل الباقة',
        'icon': Icons.workspace_premium_rounded,
        'color': AppColors.warning,
      };
    }

    if (lower.contains('wallet_credit') ||
        lower.contains('credit') ||
        lower.contains('شحن')) {
      return {
        'title': isEn ? 'Wallet Recharged' : 'شحن رصيد',
        'icon': Icons.add_card_rounded,
        'color': AppColors.success,
      };
    }

    if (lower.contains('wallet_debit') ||
        lower.contains('debit') ||
        lower.contains('خصم')) {
      return {
        'title': isEn ? 'Wallet Deduction' : 'خصم من الرصيد',
        'icon': Icons.remove_circle_outline_rounded,
        'color': AppColors.warning,
      };
    }

    if (lower.contains('interest') ||
        lower.contains('like') ||
        lower.contains('اهتمام') ||
        lower.contains('إعجاب')) {
      return {
        'title': isEn ? 'Interest Request' : 'طلب اهتمام',
        'icon': Icons.favorite_rounded,
        'color': AppColors.accentWarm,
      };
    }

    if (lower.contains('match') || lower.contains('تطابق')) {
      return {
        'title': isEn ? 'New Match' : 'تطابق جديد',
        'icon': Icons.volunteer_activism_rounded,
        'color': AppColors.accentWarm,
      };
    }

    if (lower.contains('chat') ||
        lower.contains('message') ||
        lower.contains('رسالة')) {
      return {
        'title': isEn ? 'New Message' : 'رسالة جديدة',
        'icon': Icons.mark_chat_unread_rounded,
        'color': AppColors.primaryTeal,
      };
    }

    if (lower.contains('reject') || lower.contains('رفض')) {
      return {
        'title': isEn ? 'Request Rejected' : 'رفض الطلب',
        'icon': Icons.cancel_rounded,
        'color': AppColors.error,
      };
    }

    if (lower.contains('support') ||
        lower.contains('دعم') ||
        lower.contains('مساعدة')) {
      return {
        'title': isEn ? 'Technical Support' : 'الدعم الفني',
        'icon': Icons.headset_mic_rounded,
        'color': AppColors.secondaryTeal,
      };
    }

    return {
      'title': isEn ? 'Admin Notification' : 'إشعار إداري',
      'icon': Icons.admin_panel_settings_rounded,
      'color': AppColors.primaryTeal,
    };
  }
}

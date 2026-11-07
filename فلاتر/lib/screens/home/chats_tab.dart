// ============================================================================
// اسم الملف: lib/screens/home/chats_tab.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: تبويب المحادثات المحسّن - إتاحة المراسلة لجميع المستخدمين بلا قيود
//        مع إظهار شارات الباقات الموحدة بدقة عالية ومزامنة لحظية للغة.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import '../../constants/colors.dart';
import 'package:cached_network_image/cached_network_image.dart';

import '../../models/chat_model.dart';
import '../../services/api_service.dart';
import '../../widgets/app_snackbar.dart';
import '../../widgets/package_badge_widget.dart';
import '../chat_detail_screen.dart';

class ChatsTab extends StatefulWidget {
  final String currentLanguage;
  final String Function(String) txt;
  final int unreadNotificationCount;
  final VoidCallback onNotificationPressed;
  final VoidCallback? onUpgradeRequested;

  const ChatsTab({
    super.key,
    required this.currentLanguage,
    required this.txt,
    required this.unreadNotificationCount,
    required this.onNotificationPressed,
    this.onUpgradeRequested,
  });

  @override
  State<ChatsTab> createState() => _ChatsTabState();
}

class _ChatsTabState extends State<ChatsTab> with WidgetsBindingObserver {
  // --------------------------------------------------------------------------
  // 1. المتغيرات المحلية وحالة الشاشة
  // --------------------------------------------------------------------------
  List<ChatThreadModel> _chatThreads = [];
  bool _isChatsLoading = true;
  String? _chatsErrorMessage;
  int _chatFetchCounter = 0;

  bool _isSubscribedOrFemale = true;
  bool _isCheckingSubscription = true;

  String _searchQuery = '';
  String _selectedFilter = 'all';
  final TextEditingController _searchController = TextEditingController();

  final Map<int, DateTime> _localActivityOverrides = {};

  /// التحقق من لغة التطبيق الحالية (عربي أم إنجليزي)
  bool get isArabicLocale => widget.currentLanguage == 'ar';

  /// قاموس النصوص المحلية للواجهة بمرونة عالية
  Map<String, String> get _labels => !isArabicLocale
      ? {
          'search_hint': 'Search name or chat...',
          'all_chats': 'All Chats',
          'unread': 'Unread',
          'online': 'Online',
          'no_search_results': 'No matching search results',
          'delete_success': 'Chat deleted successfully',
          'delete_error': 'Failed to delete chat from server',
          'network_error': 'Server connection failed. Chat restored.',
        }
      : {
          'search_hint': 'ابحث عن اسم أو محادثة...',
          'all_chats': 'جميع المحادثات',
          'unread': 'غير مقروءة',
          'online': 'متصل الآن',
          'no_search_results': 'لا توجد نتائج تطابق بحثك',
          'delete_success': 'تم حذف المحادثة بنجاح',
          'delete_error': 'تعذر حذف المحادثة من السيرفر',
          'network_error': 'تعذر الاتصال بالسيرفر. تمت استعادة المحادثة.',
        };

  // --------------------------------------------------------------------------
  // 2. دورة حياة التطبيق والتهيئة
  // --------------------------------------------------------------------------
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _checkSubscriptionAndFetch();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _searchController.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _checkSubscriptionAndFetch();
    }
  }

  // --------------------------------------------------------------------------
  // 3. المنطق البرمجي والتحقق من الاشتراك والرسائل والترجمة
  // --------------------------------------------------------------------------

  /// التحقق من حالة حساب العضو وجلب المحادثات دائماً للجميع
  Future<void> _checkSubscriptionAndFetch() async {
    if (!mounted) return;

    try {
      final profile = await ApiService.getCurrentUserProfile();
      if (mounted) {
        setState(() {
          if (profile != null) {
            _isSubscribedOrFemale =
                profile.isFemale || profile.isSubscribedActive;
          }
          _isCheckingSubscription = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _isCheckingSubscription = false;
        });
      }
    }

    _fetchChats();
  }

  /// تحويل النص الزمني القادم من السيرفر إلى كائن DateTime لترتيب وصياغة المحادثات
  DateTime _parseChatTimeToDateTime(String timeStr) {
    if (timeStr.isEmpty) return DateTime(2000);

    String clean = timeStr.trim().toLowerCase();

    const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    for (int i = 0; i < arabicNumbers.length; i++) {
      clean = clean.replaceAll(arabicNumbers[i], i.toString());
    }

    final DateTime now = DateTime.now();

    if (clean.contains('الآن') ||
        clean.contains('now') ||
        clean.contains('لحظات') ||
        clean.contains('قليل')) {
      return now;
    }
    if (clean.contains('أمس') ||
        clean.contains('امس') ||
        clean.contains('yesterday')) {
      if (clean.contains('أول') || clean.contains('اول')) {
        return now.subtract(const Duration(days: 2));
      }
      return now.subtract(const Duration(days: 1));
    }

    if (clean.contains('أسبوعين') || clean.contains('اسبوعين')) {
      return now.subtract(const Duration(days: 14));
    }
    if (clean.contains('شهرين')) {
      return now.subtract(const Duration(days: 60));
    }
    if (clean.contains('يومين')) {
      return now.subtract(const Duration(days: 2));
    }
    if (clean.contains('ساعتين')) {
      return now.subtract(const Duration(hours: 2));
    }
    if (clean.contains('دقيقتين')) {
      return now.subtract(const Duration(minutes: 2));
    }

    final RegExp numReg = RegExp(r'\d+');
    final match = numReg.firstMatch(clean);
    int value = match != null ? int.parse(match.group(0)!) : 1;

    if (clean.contains('دقيق') || clean.contains('min')) {
      return now.subtract(Duration(minutes: value));
    }
    if (clean.contains('ساع') || clean.contains('hour')) {
      return now.subtract(Duration(hours: value));
    }
    if (clean.contains('يوم') || clean.contains('day')) {
      return now.subtract(Duration(days: value));
    }
    if (clean.contains('أسبوع') ||
        clean.contains('اسبوع') ||
        clean.contains('week')) {
      return now.subtract(Duration(days: value * 7));
    }
    if (clean.contains('شهر') || clean.contains('month')) {
      return now.subtract(Duration(days: value * 30));
    }

    final parsedDate = DateTime.tryParse(timeStr);
    if (parsedDate != null) {
      return parsedDate;
    }

    return DateTime(2000);
  }

  /// صياغة الوقت ديناميكياً بحسب لغة التطبيق الحالية (عربي / إنجليزي)
  String _formatChatTimeDynamic(String rawTime) {
    if (rawTime.isEmpty) return '';

    final DateTime parsedDate = _parseChatTimeToDateTime(rawTime);
    if (parsedDate.year == 2000) return _translateRawTimeFallback(rawTime);

    final now = DateTime.now();
    final difference = now.difference(parsedDate);

    if (difference.inSeconds < 60) {
      return isArabicLocale ? 'الآن' : 'Now';
    } else if (difference.inMinutes < 60) {
      final mins = difference.inMinutes;
      return isArabicLocale ? 'منذ $mins د' : '${mins}m ago';
    } else if (difference.inHours < 24) {
      final hours = difference.inHours;
      return isArabicLocale ? 'منذ $hours س' : '${hours}h ago';
    } else if (difference.inDays == 1) {
      return isArabicLocale ? 'أمس' : 'Yesterday';
    } else if (difference.inDays < 7) {
      final days = difference.inDays;
      return isArabicLocale ? 'منذ $days أيام' : '${days}d ago';
    } else if (difference.inDays < 30) {
      final weeks = (difference.inDays / 7).floor();
      if (weeks <= 1) {
        return isArabicLocale ? 'منذ أسبوع' : '1w ago';
      }
      return isArabicLocale ? 'منذ $weeks أسابيع' : '${weeks}w ago';
    } else {
      final months = (difference.inDays / 30).floor();
      if (months <= 1) {
        return isArabicLocale ? 'منذ شهر' : '1mo ago';
      }
      return isArabicLocale ? 'منذ $months أشهر' : '${months}mo ago';
    }
  }

  /// ترجمة جانبية احتياطية للنصوص الزمنيّة العربية الثابتة
  String _translateRawTimeFallback(String rawTime) {
    if (isArabicLocale) return rawTime;
    String clean = rawTime.trim();
    if (clean.isEmpty) return '';

    if (RegExp(r'^[a-zA-Z0-9\s\.-]+$').hasMatch(clean)) {
      return clean;
    }

    clean = clean
        .replaceAll('منذ', '')
        .replaceAll('لحظات', 'just now')
        .replaceAll('أسبوعين', '2 weeks ago')
        .replaceAll('اسبوعين', '2 weeks ago')
        .replaceAll('أسبوع', '1 week ago')
        .replaceAll('اسبوع', '1 week ago')
        .replaceAll('أسابيع', 'weeks ago')
        .replaceAll('شهرين', '2 months ago')
        .replaceAll('شهر', '1 month ago')
        .replaceAll('أشهر', 'months ago')
        .replaceAll('يومين', '2 days ago')
        .replaceAll('يوم', '1 day ago')
        .replaceAll('أيام', 'days ago')
        .replaceAll('ساعتين', '2 hours ago')
        .replaceAll('ساعة', '1 hour ago')
        .replaceAll('ساعات', 'hours ago')
        .replaceAll('دقيقتين', '2 minutes ago')
        .replaceAll('دقيقة', '1 minute ago')
        .replaceAll('دقائق', 'minutes ago')
        .replaceAll('أمس', 'Yesterday')
        .replaceAll('امس', 'Yesterday')
        .replaceAll('الآن', 'Now')
        .replaceAll('الان', 'Now')
        .trim();

    if (clean.toLowerCase() == 'yesterday' ||
        clean.toLowerCase() == 'now' ||
        clean.toLowerCase() == 'just now' ||
        clean.endsWith('ago')) {
      return clean;
    }

    return clean.isNotEmpty ? '$clean ago' : rawTime;
  }

  /// جلب قائمة المحادثات من الخادم وترتيبها حسب الأحدث
  Future<void> _fetchChats() async {
    if (!mounted) return;

    _chatFetchCounter++;
    final int currentFetch = _chatFetchCounter;

    setState(() {
      _isChatsLoading = true;
      _chatsErrorMessage = null;
    });

    try {
      final threads = await ApiService.getChatThreads();

      final List<MapEntry<ChatThreadModel, DateTime>> timeMappedThreads =
          threads.map((thread) {
        final DateTime parsedTime =
            _localActivityOverrides.containsKey(thread.id)
                ? _localActivityOverrides[thread.id]!
                : _parseChatTimeToDateTime(thread.time);
        return MapEntry(thread, parsedTime);
      }).toList();

      timeMappedThreads.sort((a, b) {
        int timeComp = b.value.compareTo(a.value);
        if (timeComp == 0) {
          return b.key.id.compareTo(a.key.id);
        }
        return timeComp;
      });

      final List<ChatThreadModel> sortedThreads =
          timeMappedThreads.map((entry) => entry.key).toList();

      if (mounted && currentFetch == _chatFetchCounter) {
        setState(() {
          _chatThreads = sortedThreads;
          _isChatsLoading = false;
        });
      }
    } catch (e) {
      if (mounted && currentFetch == _chatFetchCounter) {
        setState(() {
          _chatsErrorMessage = widget.txt('retry');
          _isChatsLoading = false;
        });
      }
    }
  }

  /// تنفيذ عملية حذف المحادثة مع إمكانية التراجع والتأكيد
  Future<void> _executeChatDeletion(ChatThreadModel chat) async {
    final int targetId = chat.id;
    final int originalIndex = _chatThreads.indexOf(chat);

    setState(() {
      _chatThreads.removeWhere((t) => t.id == targetId);
    });

    try {
      final result = await ApiService.deleteChatThread(targetId);

      if (!mounted) return;

      if (result['success'] == true) {
        AppSnackBar.showDeleted(
          context: context,
          isArabic: isArabicLocale,
        );
      } else {
        _revertChatDeletion(chat, originalIndex);

        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: isArabicLocale,
          messageAr: 'تعذر حذف المحادثة من السيرفر حالياً',
          messageEn: 'Failed to delete chat from server',
        );
      }
    } catch (e) {
      if (!mounted) return;
      _revertChatDeletion(chat, originalIndex);

      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: isArabicLocale,
        messageAr: 'تعذر الاتصال بالسيرفر، تمت استعادة المحادثة',
        messageEn: 'Server connection failed. Chat restored.',
      );
    }
  }

  /// استعادة المحادثة في حال فشل الاتصال بالشبكة
  void _revertChatDeletion(ChatThreadModel chat, int originalIndex) {
    if (!mounted) return;
    setState(() {
      if (originalIndex != -1 && originalIndex <= _chatThreads.length) {
        _chatThreads.insert(originalIndex, chat);
      } else {
        _chatThreads.add(chat);
      }
    });
  }

  /// نافذة حوار التأكيد قبل حذف المحادثة
  Future<bool?> _showDeleteConfirmationDialog(ChatThreadModel chat) {
    FocusScope.of(context).unfocus();
    return showDialog<bool>(
      context: context,
      builder: (context) => Directionality(
        textDirection: isArabicLocale ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          insetPadding: const EdgeInsets.symmetric(horizontal: 44),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(28),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
              child: Container(
                padding: const EdgeInsets.all(22),
                decoration: BoxDecoration(
                  color: AppColors.cardColor.withValues(alpha: 0.92),
                  borderRadius: BorderRadius.circular(28),
                  border: Border.all(
                    color: AppColors.lineColor,
                    width: 1.5,
                  ),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppColors.error.withValues(alpha: 0.15),
                        shape: BoxShape.circle,
                        border: Border.all(
                            color: AppColors.error.withValues(alpha: 0.3)),
                      ),
                      child: const Icon(
                        Icons.delete_forever_rounded,
                        color: AppColors.error,
                        size: 32,
                      ),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      widget.txt('delete_chat'),
                      style: const TextStyle(
                        color: AppColors.textDark,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${widget.txt('delete_confirm')}\n(${chat.name})',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: AppColors.textDark.withValues(alpha: 0.85),
                        fontSize: 14,
                        height: 1.4,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(
                          child: TextButton(
                            onPressed: () => Navigator.pop(context, false),
                            child: Text(
                              widget.txt('cancel'),
                              style: const TextStyle(
                                  color: AppColors.primaryTeal, fontFamily: 'Cairo'),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.error,
                              elevation: 4,
                              shadowColor:
                                  AppColors.error.withValues(alpha: 0.4),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                            onPressed: () => Navigator.pop(context, true),
                            child: Text(
                              widget.txt('delete_action'),
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo'),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// القائمة المفلترة بناءً على البحث والفلتر المختار
  List<ChatThreadModel> get _filteredThreads {
    final String query = _searchQuery.trim().toLowerCase();

    return _chatThreads.where((chat) {
      if (query.isNotEmpty) {
        final matchesName = chat.name.toLowerCase().contains(query);
        final matchesLastMsg = chat.lastMessage.toLowerCase().contains(query);
        if (!matchesName && !matchesLastMsg) return false;
      }

      if (_selectedFilter == 'unread') {
        return chat.unreadCount > 0;
      } else if (_selectedFilter == 'online') {
        return chat.isOnline;
      }

      return true;
    }).toList();
  }

  // --------------------------------------------------------------------------
  // 4. بناء الواجهات العلوية وشريط الترقية الاختياري
  // --------------------------------------------------------------------------

  /// شريط ترقية اختياري يظهر من دون أن يعيق الوصول إلى المحادثات.
  Widget _buildOptionalUpgradeBanner() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.sectionBackground,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.accentLight.withValues(alpha: 0.65)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(9),
            decoration: const BoxDecoration(
              color: Color(0xFFECE7DE),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.workspace_premium_rounded,
              color: AppColors.primaryTeal,
              size: 21,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  isArabicLocale ? 'ارتقِ بتجربتك مع وصال' : 'Make more of Wesal',
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  isArabicLocale
                      ? 'ميزات إضافية وظهور أفضل لملفك الشخصي'
                      : 'Extra benefits and better profile visibility',
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 11,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primaryTeal,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 9),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              elevation: 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            onPressed: widget.onUpgradeRequested,
            child: Text(
              isArabicLocale ? 'ترقية' : 'Upgrade',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCompactHeader(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 12, 16, 8),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  isArabicLocale
                      ? 'كل محادثاتك ورسائلك في مكان واحد'
                      : 'Your conversations, all in one place',
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 12,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
          Material(
            color: AppColors.cardColor,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: widget.onNotificationPressed,
              child: SizedBox(
                width: 44,
                height: 44,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    const Icon(
                      Icons.notifications_none_rounded,
                      color: AppColors.primaryTeal,
                      size: 23,
                    ),
                    if (widget.unreadNotificationCount > 0)
                      Positioned(
                        top: 5,
                        right: 5,
                        child: Container(
                          constraints: const BoxConstraints(minWidth: 16),
                          padding: const EdgeInsets.all(3),
                          decoration: const BoxDecoration(
                            color: AppColors.primaryTeal,
                            shape: BoxShape.circle,
                          ),
                          child: Text(
                            '${widget.unreadNotificationCount}',
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 8,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchAndFilterSection() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: AppColors.cardColor,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: AppColors.lineColor),
          boxShadow: [
            BoxShadow(
              color: AppColors.textDark.withValues(alpha: 0.04),
              blurRadius: 14,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: Column(
          children: [
            TextField(
              controller: _searchController,
              onChanged: (val) => setState(() => _searchQuery = val),
              style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 14,
                fontFamily: 'Cairo',
                fontWeight: FontWeight.w500,
              ),
              decoration: InputDecoration(
                hintText: _labels['search_hint'],
                hintStyle: const TextStyle(
                  color: AppColors.textMuted,
                  fontSize: 12.5,
                  fontFamily: 'Cairo',
                ),
                prefixIcon: const Icon(
                  Icons.search_rounded,
                  color: AppColors.primaryTeal,
                  size: 22,
                ),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(
                          Icons.close_rounded,
                          color: AppColors.textMuted,
                          size: 18,
                        ),
                        onPressed: () {
                          _searchController.clear();
                          setState(() => _searchQuery = '');
                        },
                      )
                    : null,
                filled: true,
                fillColor: AppColors.sectionBackground,
                contentPadding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: BorderSide.none,
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppColors.lineColor),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(
                    color: AppColors.primaryTeal,
                    width: 1.3,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 11),
            Row(
              children: [
                _buildFilterPill(
                  id: 'all',
                  label: _labels['all_chats']!,
                  icon: Icons.chat_bubble_outline_rounded,
                ),
                const SizedBox(width: 8),
                _buildFilterPill(
                  id: 'unread',
                  label: _labels['unread']!,
                  icon: Icons.mark_chat_unread_outlined,
                ),
                const SizedBox(width: 8),
                _buildFilterPill(
                  id: 'online',
                  label: _labels['online']!,
                  icon: Icons.sensors_rounded,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterPill({
    required String id,
    required String label,
    required IconData icon,
  }) {
    final bool isSelected = _selectedFilter == id;

    return Expanded(
      child: InkWell(
        onTap: () => setState(() => _selectedFilter = id),
        borderRadius: BorderRadius.circular(13),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 4),
          decoration: BoxDecoration(
            color: isSelected ? AppColors.primaryTeal : AppColors.sectionBackground,
            borderRadius: BorderRadius.circular(13),
            border: Border.all(
              color: isSelected ? AppColors.primaryTeal : AppColors.lineColor,
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 14,
                color: isSelected ? Colors.white : AppColors.textMuted,
              ),
              const SizedBox(width: 5),
              Flexible(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: isSelected ? Colors.white : AppColors.textDark,
                    fontSize: 11,
                    fontWeight:
                        isSelected ? FontWeight.bold : FontWeight.w600,
                    fontFamily: 'Cairo',
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStyledAvatar({
    required String imageUrl,
    required String gender,
    double radius = 24,
  }) {
    final bool isFemale = gender.toLowerCase().contains('female') ||
        gender.contains('أنثى') ||
        gender.contains('انثى');

    final List<Color> gradientColors = isFemale
        ? [AppColors.accentWarm, AppColors.accentWarmDeep]
        : [AppColors.primaryTeal, AppColors.secondaryTeal];

    final IconData avatarIcon =
        isFemale ? Icons.female_rounded : Icons.male_rounded;

    if (!ChatThreadModel.hasCustomPhoto(imageUrl)) {
      return Container(
        width: radius * 2,
        height: radius * 2,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          gradient: LinearGradient(
            colors: gradientColors,
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          border: Border.all(
              color: Colors.white.withValues(alpha: 0.6), width: 1.5),
        ),
        child: Icon(
          avatarIcon,
          color: Colors.white,
          size: radius * 1.1,
        ),
      );
    }

    return Container(
      width: radius * 2,
      height: radius * 2,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border:
            Border.all(color: Colors.white.withValues(alpha: 0.6), width: 1.5),
      ),
      child: ClipOval(
        child: CachedNetworkImage(
          imageUrl: imageUrl,
          fit: BoxFit.cover,
          placeholder: (context, url) => Container(color: AppColors.sectionBackground),
          errorWidget: (context, url, error) {
            return Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: gradientColors,
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Icon(
                avatarIcon,
                color: Colors.white,
                size: radius * 1.1,
              ),
            );
          },
        ),
      ),
    );
  }

  Future<void> _openChatDetail(ChatThreadModel chat) async {
    FocusScope.of(context).unfocus();

    await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (context) => ChatDetailScreen(
          thread: chat,
          currentLanguage: widget.currentLanguage,
        ),
      ),
    );

    if (!mounted) return;
    _checkSubscriptionAndFetch();
  }

  // --------------------------------------------------------------------------
  // 5. بناء شجرة المكونات الرئيسية (UI Build)
  // --------------------------------------------------------------------------
  @override
  @override
  Widget build(BuildContext context) {
    final filteredList = _filteredThreads;

    return Directionality(
      textDirection: isArabicLocale ? TextDirection.rtl : TextDirection.ltr,
      child: Column(
        children: [
          _buildCompactHeader(widget.txt('chats')),
          _buildSearchAndFilterSection(),
          if (!_isSubscribedOrFemale && widget.onUpgradeRequested != null)
            _buildOptionalUpgradeBanner(),
          const SizedBox(height: 4),
          Expanded(
            child: _isCheckingSubscription || _isChatsLoading
                ? const Center(
                    child: CircularProgressIndicator(
                      color: AppColors.primaryTeal,
                    ),
                  )
                : _chatsErrorMessage != null
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.all(24),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(
                                Icons.cloud_off_rounded,
                                color: AppColors.textLight,
                                size: 44,
                              ),
                              const SizedBox(height: 12),
                              Text(
                                _chatsErrorMessage!,
                                style: const TextStyle(
                                  color: AppColors.textDark,
                                  fontSize: 14,
                                  fontFamily: 'Cairo',
                                ),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 12),
                              ElevatedButton(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: AppColors.primaryTeal,
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(14),
                                  ),
                                ),
                                onPressed: _checkSubscriptionAndFetch,
                                child: Text(
                                  widget.txt('retry'),
                                  style: const TextStyle(fontFamily: 'Cairo'),
                                ),
                              ),
                            ],
                          ),
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _checkSubscriptionAndFetch,
                        color: AppColors.primaryTeal,
                        backgroundColor: AppColors.cardColor,
                        child: filteredList.isEmpty
                            ? ListView(
                                physics: const AlwaysScrollableScrollPhysics(),
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 40,
                                ),
                                children: [
                                  SizedBox(
                                    height:
                                        MediaQuery.of(context).size.height *
                                            0.35,
                                    child: Center(
                                      child: Column(
                                        mainAxisAlignment:
                                            MainAxisAlignment.center,
                                        children: [
                                          const Icon(
                                            Icons.chat_bubble_outline_rounded,
                                            color: AppColors.textLight,
                                            size: 54,
                                          ),
                                          const SizedBox(height: 12),
                                          Text(
                                            _searchQuery.isNotEmpty
                                                ? _labels['no_search_results']!
                                                : widget.txt('no_chats'),
                                            textAlign: TextAlign.center,
                                            style: const TextStyle(
                                              color: AppColors.textMuted,
                                              fontSize: 14,
                                              fontWeight: FontWeight.w600,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              )
                            : ListView.builder(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 8,
                                ),
                                itemCount: filteredList.length,
                                itemBuilder: (context, index) {
                                  final chat = filteredList[index];
                                  final String displayTime =
                                      _formatChatTimeDynamic(chat.time);
                                  final bool isRecentNow = displayTime ==
                                          'الآن' ||
                                      displayTime == 'Now' ||
                                      chat.time.contains('الآن') ||
                                      chat.time.toLowerCase().contains('now');

                                  return Dismissible(
                                    key: Key('chat_thread_${chat.id}'),
                                    direction: DismissDirection.endToStart,
                                    confirmDismiss: (direction) async =>
                                        _showDeleteConfirmationDialog(chat),
                                    onDismissed: (direction) =>
                                        _executeChatDeletion(chat),
                                    background: Container(
                                      margin: const EdgeInsets.only(bottom: 12),
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 20,
                                      ),
                                      decoration: BoxDecoration(
                                        color: AppColors.error,
                                        borderRadius: BorderRadius.circular(20),
                                      ),
                                      alignment: Alignment.centerLeft,
                                      child: Row(
                                        children: [
                                          const Icon(
                                            Icons.delete_forever_rounded,
                                            color: Colors.white,
                                          ),
                                          const SizedBox(width: 8),
                                          Text(
                                            widget.txt('delete_action'),
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontWeight: FontWeight.bold,
                                              fontSize: 13,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    child: Material(
                                      color: AppColors.cardColor,
                                      borderRadius: BorderRadius.circular(20),
                                      child: InkWell(
                                        onLongPress: () async {
                                          final confirm =
                                              await _showDeleteConfirmationDialog(
                                                  chat);
                                          if (confirm == true) {
                                            _executeChatDeletion(chat);
                                          }
                                        },
                                        onTap: () => _openChatDetail(chat),
                                        borderRadius: BorderRadius.circular(20),
                                        child: Container(
                                          margin:
                                              const EdgeInsets.only(bottom: 12),
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 14,
                                            vertical: 13,
                                          ),
                                          decoration: BoxDecoration(
                                            color: AppColors.cardColor,
                                            borderRadius:
                                                BorderRadius.circular(20),
                                            border: Border.all(
                                              color: AppColors.lineColor,
                                            ),
                                            boxShadow: [
                                              BoxShadow(
                                                color: AppColors.textDark
                                                    .withValues(alpha: 0.045),
                                                blurRadius: 14,
                                                offset: const Offset(0, 5),
                                              ),
                                            ],
                                          ),
                                          child: Row(
                                            children: [
                                              Stack(
                                                children: [
                                                  _buildStyledAvatar(
                                                    imageUrl: chat.image,
                                                    gender: chat.gender,
                                                    radius: 27,
                                                  ),
                                                  if (chat.isOnline)
                                                    Positioned(
                                                      bottom: 0,
                                                      right: 0,
                                                      child: Container(
                                                        width: 13,
                                                        height: 13,
                                                        decoration:
                                                            BoxDecoration(
                                                          color: AppColors.success,
                                                          shape:
                                                              BoxShape.circle,
                                                          border: Border.all(
                                                            color: Colors.white,
                                                            width: 1.8,
                                                          ),
                                                        ),
                                                      ),
                                                    ),
                                                ],
                                              ),
                                              const SizedBox(width: 13),
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment:
                                                      CrossAxisAlignment.start,
                                                  children: [
                                                    Row(
                                                      children: [
                                                        Flexible(
                                                          child: Text(
                                                            chat.name,
                                                            maxLines: 1,
                                                            overflow:
                                                                TextOverflow
                                                                    .ellipsis,
                                                            style:
                                                                const TextStyle(
                                                              color: AppColors
                                                                  .textDark,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .w800,
                                                              fontSize: 15,
                                                              fontFamily:
                                                                  'Cairo',
                                                            ),
                                                          ),
                                                        ),
                                                        if (chat.packageName
                                                            .isNotEmpty) ...[
                                                          const SizedBox(
                                                              width: 6),
                                                          PackageBadgeWidget(
                                                            packageName:
                                                                chat.packageName,
                                                            compact: true,
                                                            isArabic:
                                                                isArabicLocale,
                                                          ),
                                                        ],
                                                      ],
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      chat.lastMessage,
                                                      maxLines: 1,
                                                      overflow:
                                                          TextOverflow.ellipsis,
                                                      style: TextStyle(
                                                        color:
                                                            chat.unreadCount > 0
                                                                ? AppColors
                                                                    .textDark
                                                                : AppColors
                                                                    .textMuted,
                                                        fontSize: 12.5,
                                                        height: 1.3,
                                                        fontWeight:
                                                            chat.unreadCount > 0
                                                                ? FontWeight
                                                                    .w700
                                                                : FontWeight
                                                                    .w400,
                                                        fontFamily: 'Cairo',
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                              const SizedBox(width: 8),
                                              Column(
                                                crossAxisAlignment:
                                                    CrossAxisAlignment.end,
                                                children: [
                                                  Text(
                                                    displayTime,
                                                    style: TextStyle(
                                                      color: isRecentNow
                                                          ? AppColors.primaryTeal
                                                          : AppColors.textLight,
                                                      fontSize: 10.5,
                                                      fontWeight: isRecentNow
                                                          ? FontWeight.bold
                                                          : FontWeight.w600,
                                                      fontFamily: 'Cairo',
                                                    ),
                                                  ),
                                                  const SizedBox(height: 6),
                                                  if (chat.unreadCount > 0)
                                                    Container(
                                                      constraints:
                                                          const BoxConstraints(
                                                              minWidth: 22),
                                                      padding: const EdgeInsets
                                                          .symmetric(
                                                        horizontal: 7,
                                                        vertical: 3,
                                                      ),
                                                      decoration:
                                                          const BoxDecoration(
                                                        color: AppColors
                                                            .accentWarm,
                                                        borderRadius:
                                                            BorderRadius.all(
                                                          Radius.circular(10),
                                                        ),
                                                      ),
                                                      child: Text(
                                                        '${chat.unreadCount}',
                                                        textAlign:
                                                            TextAlign.center,
                                                        style: const TextStyle(
                                                          color: Colors.white,
                                                          fontSize: 10,
                                                          fontWeight:
                                                              FontWeight.bold,
                                                          fontFamily: 'Cairo',
                                                        ),
                                                      ),
                                                    ),
                                                ],
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ),
                                  );
                                },
                              ),
                      ),
          ),
        ],
      ),
    );
  }
}

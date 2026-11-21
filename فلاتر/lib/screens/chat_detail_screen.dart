// ============================================================================
// اسم الملف: lib/screens/chat_detail_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة الدردشة التفاعلية المزودة بالهيدر الزجاجي الفاخر مع دعم كامل
//        لإظهار شارات الباقات الموحدة والترجمة المزدوجة اللحظية.
// ============================================================================

import 'dart:async';
import 'dart:io';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' as services;
import '../constants/colors.dart';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/chat_model.dart';
import '../utils/message_text_direction.dart';
import '../models/member_profile_model.dart';
import '../services/api_service.dart';
import '../services/chat_local_store.dart';
import '../services/chat_screen_protection.dart';
import '../utils/chat_time_utils.dart';
import '../utils/chat_activity_state.dart';
import '../widgets/app_snackbar.dart';
import '../widgets/bottom_system_inset.dart';
import 'member_profile_screen.dart';

class ChatDetailScreen extends StatefulWidget {
  final ChatThreadModel thread;
  final String currentLanguage;

  const ChatDetailScreen({
    super.key,
    required this.thread,
    this.currentLanguage = 'ar',
  });

  @override
  State<ChatDetailScreen> createState() => _ChatDetailScreenState();
}

class _ChatDetailScreenState extends State<ChatDetailScreen>
    with WidgetsBindingObserver {
  final TextEditingController _messageController = TextEditingController();
  final TextEditingController _captionController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final FocusNode _inputFocusNode = FocusNode();
  final ImagePicker _picker = ImagePicker();
  final TextEditingController _messageSearchController = TextEditingController();

  late int _activeThreadId;
  int _currentUserId = 0;
  bool _isChatListReady = false;
  bool _partnerIsOnline = false;
  String _partnerLastSeen = '';
  bool _isChatMuted = false;
  final Set<int> _revealedIncomingImageIds = <int>{};
  bool _showMessageSearch = false;
  bool _isSearchingSavedMessages = false;
  List<ChatMessageModel> _messageSearchResults = [];
  List<ChatMessageModel> _messages = [];
  ChatRulesModel? _chatRules;
  bool _isLoading = false;
  bool _hasMessageLoadError = false;
  String _messageLoadError = '';
  bool _isSending = false;
  bool _isLoadingMore = false;
  bool _isFetchingMessages = false;
  bool _pendingMessageRefresh = false;
  bool _hasMoreOldMessages = true;
  Timer? _messageRefreshTimer;
  bool _showEmojiPicker = false;
  int _selectedPickerTab = 0;
  File? _selectedImageFile;

  bool _hasSentMessage = false;
  bool _canPopNow = false;

  bool get _isArabic => widget.currentLanguage.trim().toLowerCase().startsWith('ar');

  bool get _partnerHasReplied => ChatMessageModel.hasPartnerReplied(
        _messages,
        partnerUserId: widget.thread.userId,
      );

  void _showImageReplyRequiredMessage() {
    AppSnackBar.show(
      context: context,
      type: NotificationType.warning,
      isArabic: _isArabic,
      messageAr: 'يمكنك إرفاق الصور بعد أن يرسل الطرف الآخر أول رد في المحادثة.',
      messageEn: 'You can share photos after the other person sends their first reply.',
    );
  }

  final List<String> _emojis = [
    '❤️',
    '🌹',
    '😍',
    '😊',
    '🥰',
    '😘',
    '🤍',
    '✨',
    '💍',
    '🕊️',
    '🌸',
    '👑',
    '⭐',
    '🤝',
    '👍',
    '🎉',
    '🎁',
    '😄',
    '😌',
    '🙏',
    '💯',
    '🔥',
    '💙'
  ];

  List<String> get _stickers => _isArabic
      ? [
          '👋 أهلين وسهلين',
          '❤️ يسعد أوقاتك',
          '🌸 شكراً جزيلاً',
          '✨ تشرفنا فيك',
          '💍 بالتوفيق يارب',
          '👑 يومك سعيد'
        ]
      : [
          '👋 Welcome!',
          '❤️ Good times',
          '🌸 Thank you very much',
          '✨ Honored to meet you',
          '💍 Best of luck',
          '👑 Have a great day'
        ];

  List<String> get _reportReasons => _isArabic
      ? [
          'حساب وهمي أو انتحال شخصية',
          'محتوى غير لائق أو مسيء',
          'إزعاج ومضايقة مستمرة',
          'سبب آخر',
        ]
      : [
          'Fake account or impersonation',
          'Inappropriate or offensive content',
          'Continuous harassment',
          'Other reason',
        ];

  @override
  void initState() {
    super.initState();
    _activeThreadId = widget.thread.id;
    // Publish the visible thread immediately, before async cache/API loading,
    // so a simultaneous notification tap cannot stack the same conversation.
    ChatActivityState.activeThreadId.value = _activeThreadId;
    _partnerIsOnline = widget.thread.isOnline;
    _partnerLastSeen = widget.thread.lastSeen;
    WidgetsBinding.instance.addObserver(this);
    _scrollController.addListener(_scrollListener);
    _inputFocusNode.addListener(_onFocusChange);

    _isLoading = true;

    unawaited(ChatScreenProtection.setEnabled(true));
    unawaited(_initializeChatExperience());

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkAndShowMaleWarningDialog();
    });
  }

  void _onFocusChange() {
    if (!mounted || !_inputFocusNode.hasFocus) return;
    if (_showEmojiPicker) {
      setState(() {
        _showEmojiPicker = false;
      });
    }
    _scrollToBottom();
  }

  @override
  void dispose() {
    _messageRefreshTimer?.cancel();
    unawaited(ChatScreenProtection.setEnabled(false));
    if (ChatActivityState.activeThreadId.value == _activeThreadId) {
      ChatActivityState.activeThreadId.value = null;
    }
    unawaited(ApiService.updateChatPresence(
      threadId: 0,
      appIsActive:
          WidgetsBinding.instance.lifecycleState == AppLifecycleState.resumed,
    ));
    WidgetsBinding.instance.removeObserver(this);
    _scrollController.removeListener(_scrollListener);
    _inputFocusNode.removeListener(_onFocusChange);
    _messageController.dispose();
    _captionController.dispose();
    _messageSearchController.dispose();
    _scrollController.dispose();
    _inputFocusNode.dispose();
    super.dispose();
  }

  Future<void> _initializeChatExperience() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _currentUserId = prefs.getInt('user_id') ?? 0;
      if (_currentUserId > 0 && _activeThreadId > 0) {
        final cachedMessages = await ChatLocalStore.getMessages(
          _currentUserId,
          _activeThreadId,
          currentUserId: _currentUserId,
        );
        final muted = await ChatLocalStore.isMuted(
          _currentUserId,
          widget.thread.userId,
        );
        if (!mounted) return;
        setState(() {
          _isChatMuted = muted;
          if (_messages.isEmpty && cachedMessages.isNotEmpty) {
            _messages = cachedMessages;
            _isLoading = false;
          }
        });
        _markChatListReady();
      }
    } catch (error) {
      debugPrint('[Chat Local State] $error');
    }

    if (!mounted) return;
    ChatActivityState.activeThreadId.value = _activeThreadId;
    unawaited(ApiService.updateChatPresence(
        threadId: _activeThreadId,
        appIsActive: true,
      ));
    await ApiService.syncChatMuteSettings(partnerUserId: widget.thread.userId);
    if (!mounted) return;
    _startMessageRefreshTimer();
    await _loadMessages(
      silent: _messages.isNotEmpty,
      preserveHistory: _messages.isNotEmpty,
    );
  }

  void _startMessageRefreshTimer() {
    _messageRefreshTimer?.cancel();
    if (_activeThreadId <= 0) return;
    _messageRefreshTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      if (!mounted ||
          WidgetsBinding.instance.lifecycleState != AppLifecycleState.resumed ||
          _isSending ||
          _isLoadingMore) {
        return;
      }
      ChatActivityState.activeThreadId.value = _activeThreadId;
      unawaited(ApiService.updateChatPresence(
        threadId: _activeThreadId,
        appIsActive: true,
      ));
      _loadMessages(silent: true, preserveHistory: true);
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ChatActivityState.activeThreadId.value = _activeThreadId;
      unawaited(ApiService.updateChatPresence(
        threadId: _activeThreadId,
        appIsActive: true,
      ));
      unawaited(ApiService.syncChatMuteSettings(partnerUserId: widget.thread.userId));
      _loadMessages(silent: true, preserveHistory: true);
      _startMessageRefreshTimer();
    } else {
      _messageRefreshTimer?.cancel();
      _messageRefreshTimer = null;
      ChatActivityState.activeThreadId.value = null;
      if (state == AppLifecycleState.paused ||
          state == AppLifecycleState.detached) {
        unawaited(ApiService.updateChatPresence(
          threadId: 0,
          appIsActive: false,
        ));
      }
    }
  }

  @override
  void didChangeMetrics() {
    if (!_inputFocusNode.hasFocus || _showEmojiPicker) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted || !_scrollController.hasClients) return;
      _scrollController.animateTo(
        _scrollController.position.minScrollExtent,
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOut,
      );
    });
  }

  // A reversed chat list places the newest message at minScrollExtent.
  // Position there explicitly after the first real layout (cached or remote)
  // before enabling older-history pagination.
  void _markChatListReady() {
    if (_isChatListReady) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted || _isChatListReady) return;
      _isChatListReady = true;
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(_scrollController.position.minScrollExtent);
      }
    });
  }

  void _toggleMessageSearch() {
    setState(() {
      _showMessageSearch = !_showMessageSearch;
      _messageSearchResults = [];
      _messageSearchController.clear();
    });
  }

  Future<void> _searchSavedMessages(String query) async {
    final cleanQuery = query.trim();
    if (cleanQuery.isEmpty) {
      if (mounted) setState(() => _messageSearchResults = []);
      return;
    }
    setState(() => _isSearchingSavedMessages = true);
    try {
      final cached = await ChatLocalStore.searchMessages(
        _currentUserId,
        _activeThreadId,
        cleanQuery,
        currentUserId: _currentUserId,
      );
      final visibleMatches = _messages.where((message) =>
          message.message.toLowerCase().contains(cleanQuery.toLowerCase()));
      final byId = <int, ChatMessageModel>{
        for (final message in cached) message.id: message,
        for (final message in visibleMatches) message.id: message,
      };
      final results = byId.values.toList()
        ..sort((left, right) => left.id.compareTo(right.id));
      if (mounted && _messageSearchController.text.trim() == cleanQuery) {
        setState(() => _messageSearchResults = results);
      }
    } catch (error) {
      debugPrint('[Chat Message Search] $error');
    } finally {
      if (mounted) setState(() => _isSearchingSavedMessages = false);
    }
  }

  Widget _buildMessageSearchBar() {
    if (!_showMessageSearch) return const SizedBox.shrink();
    final count = _messageSearchResults.length;
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 0, 14, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextField(
            controller: _messageSearchController,
            autofocus: true,
            onChanged: _searchSavedMessages,
            style: const TextStyle(
              color: AppColors.textDark,
              fontFamily: 'Cairo',
              fontSize: 13,
            ),
            decoration: InputDecoration(
              hintText: _isArabic
                  ? 'ابحث داخل الرسائل المحفوظة...'
                  : 'Search saved messages...',
              hintStyle: const TextStyle(
                color: AppColors.textMuted,
                fontFamily: 'Cairo',
                fontSize: 12,
              ),
              prefixIcon: const Icon(
                Icons.search_rounded,
                color: AppColors.primaryTeal,
                size: 20,
              ),
              suffixIcon: IconButton(
                tooltip: _isArabic ? 'إغلاق البحث' : 'Close search',
                onPressed: _toggleMessageSearch,
                icon: const Icon(Icons.close_rounded,
                    color: AppColors.textMuted, size: 19),
              ),
              filled: true,
              fillColor: AppColors.cardColor,
              contentPadding:
                  const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: AppColors.lineColor),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: AppColors.lineColor),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(
                    color: AppColors.primaryTeal, width: 1.2),
              ),
            ),
          ),
          if (_messageSearchController.text.trim().isNotEmpty)
            Padding(
              padding: const EdgeInsetsDirectional.only(start: 8, top: 5),
              child: Text(
                _isSearchingSavedMessages
                    ? (_isArabic ? 'جارٍ البحث...' : 'Searching...')
                    : (_isArabic ? 'نتائج البحث: $count' : 'Results: $count'),
                style: const TextStyle(
                  color: AppColors.textMuted,
                  fontFamily: 'Cairo',
                  fontSize: 11,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Future<void> _toggleChatMute() async {
    final nextMuted = !_isChatMuted;
    if (_currentUserId <= 0) {
      final prefs = await SharedPreferences.getInstance();
      _currentUserId = prefs.getInt('user_id') ?? 0;
    }
    if (_currentUserId > 0) {
      await ChatLocalStore.setMuted(
        _currentUserId,
        widget.thread.userId,
        nextMuted,
      );
    }
    if (mounted) setState(() => _isChatMuted = nextMuted);

    final synced = await ApiService.setChatMuted(
      partnerUserId: widget.thread.userId,
      muted: nextMuted,
    );
    if (synced && _currentUserId > 0) {
      await ChatLocalStore.markMuteSynced(
        _currentUserId,
        widget.thread.userId,
      );
    }
    if (!mounted) return;
    AppSnackBar.show(
      context: context,
      type: synced ? NotificationType.success : NotificationType.info,
      isArabic: _isArabic,
      messageAr: synced
          ? (nextMuted ? 'تم كتم إشعارات هذه المحادثة' : 'تم إلغاء كتم المحادثة')
          : 'تم حفظ الإعداد محلياً وستتم مزامنته عند عودة الاتصال',
      messageEn: synced
          ? (nextMuted ? 'Conversation muted' : 'Conversation unmuted')
          : 'Preference saved locally and will sync when connection returns',
    );
  }

  /// 🟢 معالجة إغلاق الشاشة وإرجاع النتيجة بشكل آمن
  void _handlePop() {
    _dismissKeyboard();
    if (!_canPopNow) {
      setState(() {
        _canPopNow = true;
      });
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        Navigator.of(context).pop(_hasSentMessage);
      }
    });
  }

  /// 🟢 ترجمة وصياغة نص "آخر ظهور" ديناميكياً بحسب لغة الواجهة
  String _translateLastSeen(String rawLastSeen) {
    if (rawLastSeen.trim().isEmpty) {
      return _isArabic ? 'غير متصل' : 'Offline';
    }

    String clean = rawLastSeen.trim();

    if (_isArabic) {
      if (clean.startsWith('آخر ظهور')) return clean;
      return 'آخر ظهور: $clean';
    }

    if (clean.toLowerCase().startsWith('last seen')) {
      return clean;
    }

    const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    for (int i = 0; i < arabicNumbers.length; i++) {
      clean = clean.replaceAll(arabicNumbers[i], i.toString());
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
        .replaceAll('الآن', 'just now')
        .replaceAll('الان', 'just now')
        .trim();

    if (clean.toLowerCase() == 'yesterday' ||
        clean.toLowerCase() == 'just now' ||
        clean.endsWith('ago')) {
      return 'Last seen: $clean';
    }

    if (RegExp(r'^\d+\s+[a-zA-Z]+$').hasMatch(clean)) {
      return 'Last seen: $clean ago';
    }

    return 'Last seen: $clean';
  }

  /// الانتقال إلى البروفايل الخاص بالطرف الآخر
  void _navigateToProfile() {
    _dismissKeyboard();
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => MemberProfileScreen(
          userId: widget.thread.userId,
          currentLanguage: widget.currentLanguage,
        ),
      ),
    );
  }

  /// التحقق من جنس المستخدم وحالة التنبيه في الشاشة
  Future<void> _checkAndShowMaleWarningDialog() async {
    try {
      final MemberProfileModel? currentUser =
          await ApiService.getCurrentUserProfile(lang: widget.currentLanguage);

      if (currentUser == null || !mounted) return;

      final bool isMaleUser = !currentUser.isFemale;

      if (isMaleUser) {
        final SharedPreferences prefs = await SharedPreferences.getInstance();
        if (!mounted) return;
        final String warningKey =
            'has_seen_male_chat_warning_${currentUser.userId}';
        final bool hasSeenWarning = prefs.getBool(warningKey) ?? false;

        if (!hasSeenWarning && mounted) {
          _showProfessionalMaleWarningDialog(prefs, warningKey);
        }
      }
    } catch (e) {
      debugPrint("خطأ أثناء التحقق من تنبيه الدردشة: $e");
    }
  }

  /// إظهار النافذة التحذيرية الرسمية للذكور
  void _showProfessionalMaleWarningDialog(
      SharedPreferences prefs, String warningKey) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(28),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: AppColors.cardColor.withValues(alpha: 0.96),
                  borderRadius: BorderRadius.circular(28),
                  border: Border.all(
                    color: AppColors.error.withValues(alpha: 0.6),
                    width: 1.8,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.error.withValues(alpha: 0.25),
                      blurRadius: 25,
                      offset: const Offset(0, 8),
                    )
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.error.withValues(alpha: 0.15),
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: AppColors.error,
                          width: 1.5,
                        ),
                      ),
                      child: const Icon(
                        Icons.gavel_rounded,
                        color: AppColors.error,
                        size: 42,
                      ),
                    ),
                    const SizedBox(height: 18),
                    Text(
                      _isArabic
                          ? 'تنبيه أمني وقانوني هام ⚖️'
                          : 'Important Security & Legal Notice ⚖️',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: AppColors.textDark,
                        fontSize: 18.5,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    const SizedBox(height: 14),
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppColors.sectionBackground,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                            color: AppColors.lineColor),
                      ),
                      child: Text(
                        _isArabic
                            ? 'حرصاً على توفير بيئة تواصل آمنة ومحترمة للجميع، يُرجى الالتزام التام بالآداب العامة والضوابط الأخلاقية.\n\nنُودّ التذكير بأن إرسال أي صور أو رسائل غير لائقة يُعد مخالفة جسيمة، وتعرّض صاحب الحساب للمساءلة القانونية المباشرة وحظر الحساب نهائياً، وفق الأنظمة والقوانين الخاصة بالجرائم الإلكترونية.'
                            : 'Please maintain maximum respect and professional behavior. Sending inappropriate messages or media will lead to immediate permanent ban and direct legal prosecution under cybercrime laws.',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 13,
                          fontFamily: 'Cairo',
                          height: 1.6,
                        ),
                      ),
                    ),
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.error,
                          padding: const EdgeInsets.symmetric(vertical: 13),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          elevation: 6,
                        ),
                        icon: const Icon(Icons.check_circle_rounded,
                            color: AppColors.textDark, size: 20),
                        label: Text(
                          _isArabic
                              ? 'أقر بالالتزام والمتابعة'
                              : 'I Understand & Agree',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14.5,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
                        ),
                        onPressed: () async {
                          await prefs.setBool(warningKey, true);
                          if (dialogContext.mounted) {
                            Navigator.pop(dialogContext);
                          }
                        },
                      ),
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

  void _dismissKeyboard() {
    FocusScope.of(context).unfocus();
    _inputFocusNode.unfocus();
  }

  void _scrollListener() {
    if (!_isChatListReady || !_scrollController.hasClients) return;
    final position = _scrollController.position;
    // In reverse mode, zero is the newest-message edge and maxScrollExtent
    // is the oldest-message edge.
    if (position.maxScrollExtent - position.pixels <= 30 &&
        !_isLoadingMore &&
        _hasMoreOldMessages &&
        _messages.isNotEmpty) {
      _loadOldMessages();
    }
  }

  List<ChatMessageModel> _mergeMessageHistory(
      List<ChatMessageModel> existing, List<ChatMessageModel> incoming) {
    return ChatMessageModel.mergeHistory(existing, incoming);
  }

  Future<void> _loadMessages({
    bool silent = false,
    bool preserveHistory = false,
  }) async {
    if (!mounted) return;
    if (_isFetchingMessages || _isLoadingMore) {
      _pendingMessageRefresh = true;
      return;
    }

    _isFetchingMessages = true;
    final requestedThreadId = _activeThreadId;
    final positiveExistingIds = _messages
        .where((message) => message.id > 0)
        .map((message) => message.id);
    final int? previousLatestMessageId = positiveExistingIds.isEmpty
        ? null
        : positiveExistingIds.reduce((left, right) => left > right ? left : right);
    final bool wasNearBottom = _scrollController.hasClients &&
        (_scrollController.position.pixels -
                _scrollController.position.minScrollExtent <=
            120);

    try {
      final res = await ApiService.getChatMessages(
        requestedThreadId,
        widget.thread.userId,
        lang: widget.currentLanguage,
      );
      if (!mounted || requestedThreadId != _activeThreadId) return;

      if (res['success'] != true) {
        if (!silent) {
          setState(() {
            _isLoading = false;
            _hasMessageLoadError = true;
            _messageLoadError = res['message']?.toString() ??
                (_isArabic ? 'تعذر تحميل الرسائل' : 'Could not load messages');
          });
          if (_messages.isEmpty) {
            AppSnackBar.show(
              context: context,
              type: NotificationType.error,
              isArabic: _isArabic,
              messageAr: _messageLoadError,
              messageEn: _messageLoadError,
            );
          }
        }
        return;
      }

      final hiddenMessageIds = _currentUserId > 0
          ? await ChatLocalStore.getHiddenMessageIds(_currentUserId, requestedThreadId)
          : <int>{};
      if (!mounted || requestedThreadId != _activeThreadId) return;
      final List<ChatMessageModel> messages = res['messages'] is List
          ? (res['messages'] as List)
              .whereType<ChatMessageModel>()
              .where((message) => !hiddenMessageIds.contains(message.id))
              .toList()
          : <ChatMessageModel>[];
      final ChatRulesModel? rules = res['chat_rules'];
      // Keep locally loaded older pages and optimistic messages through every
      // refresh; the server response updates matching IDs rather than replacing
      // the entire in-memory transcript with only its latest 40 messages.
      final displayedMessages = _mergeMessageHistory(_messages, messages);
      final positiveDisplayedIds = displayedMessages
          .where((message) => message.id > 0)
          .map((message) => message.id);
      final int? latestMessageId = positiveDisplayedIds.isEmpty
          ? null
          : positiveDisplayedIds.reduce((left, right) => left > right ? left : right);
      final bool hasNewLatestMessage = latestMessageId != null &&
          (previousLatestMessageId == null ||
              latestMessageId > previousLatestMessageId);
      final incomingLastSeen = res['partner_last_seen']?.toString().trim() ?? '';

      setState(() {
        _messages = displayedMessages;
        if (res.containsKey('partner_is_online')) {
          _partnerIsOnline = res['partner_is_online'] == true;
        }
        // A blank response must not erase the last valid value already shown.
        if (incomingLastSeen.isNotEmpty) {
          _partnerLastSeen = incomingLastSeen;
        }
        _chatRules = rules;
        if (res.containsKey('muted_by_me')) {
          _isChatMuted = res['muted_by_me'] == true;
        }
        _hasMoreOldMessages = preserveHistory
            ? (_hasMoreOldMessages || res['has_more_old_messages'] == true)
            : res['has_more_old_messages'] == true;
        _isLoading = false;
        _hasMessageLoadError = false;
        _messageLoadError = '';
      });

      if (res.containsKey('muted_by_me') && _currentUserId > 0) {
        await ChatLocalStore.setMutedFromServer(
          _currentUserId,
          widget.thread.userId,
          res['muted_by_me'] == true,
        );
        if (!mounted) return;
      }

      if (!_isChatListReady) {
        _markChatListReady();
      } else if (!silent ||
          (preserveHistory && wasNearBottom && hasNewLatestMessage)) {
        _scrollToBottom();
      }
    } catch (error) {
      if (!mounted) return;
      if (!silent) {
        setState(() {
          _isLoading = false;
          _hasMessageLoadError = true;
          _messageLoadError = _isArabic
              ? 'تعذر الاتصال بالسيرفر أثناء تحميل الرسائل'
              : 'Could not connect to load messages';
        });
        if (_messages.isEmpty) {
          AppSnackBar.show(
            context: context,
            type: NotificationType.error,
            isArabic: _isArabic,
            messageAr: _messageLoadError,
            messageEn: _messageLoadError,
          );
        }
      }
    } finally {
      _isFetchingMessages = false;
      if (_pendingMessageRefresh && mounted && !_isLoadingMore) {
        _pendingMessageRefresh = false;
        unawaited(_loadMessages(silent: true, preserveHistory: true));
      }
    }
  }

  Future<void> _loadOldMessages() async {
    if (!_isChatListReady ||
        _isFetchingMessages ||
        _messages.isEmpty ||
        _isLoadingMore ||
        !_hasMoreOldMessages) {
      return;
    }

    final storedMessages = _messages.where((message) => message.id > 0).toList();
    if (storedMessages.isEmpty) return;

    final requestThreadId = _activeThreadId;
    final firstMessageId = storedMessages.first.id;
    final previousPixels = _scrollController.hasClients
        ? _scrollController.position.pixels
        : 0.0;

    setState(() => _isLoadingMore = true);
    try {
      final result = await ApiService.getOldMessages(
        firstMessageId,
        widget.thread.userId,
        lang: widget.currentLanguage,
        threadId: requestThreadId,
      );
      if (!mounted || requestThreadId != _activeThreadId) return;

      final hiddenMessageIds = _currentUserId > 0
          ? await ChatLocalStore.getHiddenMessageIds(_currentUserId, requestThreadId)
          : <int>{};
      if (!mounted || requestThreadId != _activeThreadId) return;
      final oldMessages = result['messages'] is List
          ? (result['messages'] as List)
              .whereType<ChatMessageModel>()
              .where((message) => !hiddenMessageIds.contains(message.id))
              .toList()
          : <ChatMessageModel>[];
      setState(() {
        _isLoadingMore = false;
        if (result['success'] == true) {
          if (oldMessages.isNotEmpty) {
            _messages = _mergeMessageHistory(oldMessages, _messages);
          }
          _hasMoreOldMessages = result['has_more'] == true;
        }
      });

      if (result['success'] != true) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isArabic,
          messageAr: result['message']?.toString() ?? 'تعذر تحميل الرسائل السابقة',
          messageEn: result['message']?.toString() ?? 'Could not load older messages',
        );
      } else if (oldMessages.isNotEmpty) {
        // Older items are appended at the far end of the reversed list, so
        // the visible newest-side offset should remain unchanged.
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (!mounted || !_scrollController.hasClients) return;
          final position = _scrollController.position;
          final target = previousPixels
              .clamp(position.minScrollExtent, position.maxScrollExtent)
              .toDouble();
          if ((position.pixels - target).abs() > 1) {
            _scrollController.jumpTo(target);
          }
        });
      }
    } catch (error) {
      debugPrint('[Chat History] Could not load older messages: $error');
      if (mounted) setState(() => _isLoadingMore = false);
    } finally {
      if (mounted && _isLoadingMore) {
        setState(() => _isLoadingMore = false);
      }
      if (mounted && _pendingMessageRefresh && !_isFetchingMessages) {
        _pendingMessageRefresh = false;
        unawaited(_loadMessages(silent: true, preserveHistory: true));
      }
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted || !_scrollController.hasClients) return;
      final position = _scrollController.position;
      final target = position.minScrollExtent;
      if ((target - position.pixels).abs() < 1) return;
      _scrollController.animateTo(
        target,
        duration: const Duration(milliseconds: 220),
        curve: Curves.easeOut,
      );
    });
  }

  void _toggleEmojiPicker() {
    if (_showEmojiPicker) {
      setState(() => _showEmojiPicker = false);
      _inputFocusNode.requestFocus();
    } else {
      _dismissKeyboard();
      setState(() => _showEmojiPicker = true);
    }
  }

  Future<void> _pickImage(ImageSource source) async {
    if (!_partnerHasReplied) {
      _showImageReplyRequiredMessage();
      return;
    }
    try {
      _dismissKeyboard();
      final XFile? pickedFile = await _picker.pickImage(
        source: source,
        imageQuality: 80,
      );
      if (pickedFile != null) {
        setState(() {
          _selectedImageFile = File(pickedFile.path);
        });
        _showImagePreviewModal();
      }
    } catch (e) {
      debugPrint("خطأ في اختيار الصورة: $e");
    }
  }

  void _showImagePreviewModal() {
    _captionController.clear();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          bottom: WesalBottomInsets.of(context),
        ),
        child: Directionality(
          textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
          child: SafeArea(
            child: Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.cardColor.withValues(alpha: 0.95),
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(32)),
                border: Border.all(
                    color: AppColors.primaryTeal.withValues(alpha: 0.4)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    _isArabic ? 'إرسال صورة' : 'Send Image',
                    style: const TextStyle(
                      color: AppColors.textDark,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Cairo',
                    ),
                  ),
                  const SizedBox(height: 14),
                  SizedBox(
                    height: 200,
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(20),
                      child: Image.file(
                        _selectedImageFile!,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _captionController,
                    style: const TextStyle(
                        color: AppColors.textDark, fontFamily: 'Cairo'),
                    decoration: InputDecoration(
                      hintText: _isArabic
                          ? 'اكتب وصفاً للصورة (اختياري)...'
                          : 'Add a caption (optional)...',
                      hintStyle: const TextStyle(
                          color: AppColors.textMuted, fontFamily: 'Cairo'),
                      filled: true,
                      fillColor: AppColors.sectionBackground,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: BorderSide(
                            color: AppColors.lineColor),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: const BorderSide(color: AppColors.primaryTeal),
                      ),
                    ),
                  ),
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      Expanded(
                        child: TextButton(
                          onPressed: () {
                            setState(() => _selectedImageFile = null);
                            Navigator.pop(context);
                          },
                          child: Text(_isArabic ? 'إلغاء' : 'Cancel',
                              style: const TextStyle(
                                  color: AppColors.textMuted, fontFamily: 'Cairo')),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primaryTeal,
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16)),
                          ),
                          icon: const Icon(Icons.send_rounded,
                              size: 18, color: Colors.white),
                          label: Text(_isArabic ? 'إرسال الآن' : 'Send Now',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold)),
                          onPressed: () {
                            final fileToSend = _selectedImageFile;
                            final captionText = _captionController.text.trim();
                            setState(() => _selectedImageFile = null);
                            Navigator.pop(context);
                            _sendMessage(
                                imageFile: fileToSend, customText: captionText);
                          },
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
    );
  }

  Future<void> _sendMessage({File? imageFile, String? customText}) async {
    final text = customText ?? _messageController.text.trim();
    if ((text.isEmpty && imageFile == null) || _isSending) return;
    if (imageFile != null && !_partnerHasReplied) {
      _showImageReplyRequiredMessage();
      return;
    }

    if (customText == null) _messageController.clear();

    final tempMsg = ChatMessageModel(
      id: -DateTime.now().millisecondsSinceEpoch,
      senderId: _currentUserId,
      message:
          text.isNotEmpty ? text : (_isArabic ? 'أرسل صورة' : 'Sent an image'),
      time: _isArabic ? 'الآن' : 'Now',
      isMe: true,
      isSeen: false,
      attachmentUrl: imageFile?.path,
    );

    setState(() {
      _isSending = true;
      _hasSentMessage = true;
      _messages.add(tempMsg);
    });
    _scrollToBottom();

    try {
      final result = await ApiService.sendChatMessage(
        chatThreadId: _activeThreadId,
        message: text,
        attachmentFile: imageFile,
        partnerUserId: widget.thread.userId,
        lang: widget.currentLanguage,
      );
      if (!mounted) return;

      if (result['success'] == true) {
        final responseEnvelope = result['data'] is Map
            ? Map<String, dynamic>.from(result['data'] as Map)
            : <String, dynamic>{};
        final rawThreadId =
            responseEnvelope['chat_thread_id'] ?? result['chat_thread_id'];
        final newThreadId = int.tryParse(rawThreadId?.toString() ?? '');

        if (newThreadId != null && newThreadId > 0) {
          _activeThreadId = newThreadId;
          ChatActivityState.activeThreadId.value = newThreadId;
          unawaited(ApiService.updateChatPresence(threadId: newThreadId));
          _startMessageRefreshTimer();
        }

        final rawMessage = responseEnvelope['data'] is Map
            ? Map<String, dynamic>.from(responseEnvelope['data'] as Map)
            : (responseEnvelope.containsKey('id') ? responseEnvelope : null);
        final parsedMessage = rawMessage == null
            ? null
            : ChatMessageModel.fromJson(
                rawMessage,
                widget.thread.userId,
                currentUserId: _currentUserId,
              );
        final sentMessage =
            (parsedMessage != null && parsedMessage.id > 0) ? parsedMessage : null;

        setState(() {
          _isSending = false;
          if (sentMessage != null) {
            _messages.removeWhere((message) =>
                message.id == tempMsg.id || message.id == sentMessage.id);
            _messages = _mergeMessageHistory(_messages, [sentMessage]);
          }
        });

        if (sentMessage != null &&
            _currentUserId > 0 &&
            _activeThreadId > 0) {
          try {
            await ChatLocalStore.saveMessages(
              _currentUserId,
              _activeThreadId,
              [sentMessage],
            );
          } catch (cacheError) {
            debugPrint('[Chat Cache] Could not save sent message: $cacheError');
          }
        }

        // Keep the confirmed (or still optimistic) item visible through refresh.
        _scrollToBottom();
        unawaited(_loadMessages(silent: true, preserveHistory: true));
      } else {
        setState(() {
          _isSending = false;
          _messages.removeWhere((message) => message.id == tempMsg.id);
        });

        final String status = result['status'] ?? '';
        final String serverErrorMessage = result['message'] ??
            (_isArabic ? 'تعذر إرسال الرسالة' : 'Failed to send message');

        if (status == 'subscription_expired' ||
            status == 'package_required' ||
            status == 'forbidden') {
          _showSubscriptionDialog(serverErrorMessage);
        } else if (status == 'image_requires_partner_reply') {
          _showImageReplyRequiredMessage();
        } else if (status == 'limit_reached_wait_reply') {
          _showWaitReplyDialog(serverErrorMessage);
        } else {
          AppSnackBar.show(
            context: context,
            type: NotificationType.error,
            isArabic: _isArabic,
            messageAr: serverErrorMessage,
            messageEn: serverErrorMessage,
          );
        }
      }
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isSending = false;
        _messages.removeWhere((message) => message.id == tempMsg.id);
      });
      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: _isArabic,
        messageAr: 'حدث خطأ أثناء إرسال الرسالة',
        messageEn: 'An error occurred while sending message',
      );
    }
  }

  void _showSubscriptionDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: AlertDialog(
          backgroundColor: AppColors.cardColor,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          title: Row(
            children: [
              const Icon(Icons.workspace_premium_rounded,
                  color: AppColors.premiumGold, size: 28),
              const SizedBox(width: 10),
              Text(
                _isArabic ? 'تجديد الاشتراك' : 'Renew Subscription',
                style: const TextStyle(
                    color: AppColors.textDark,
                    fontFamily: 'Cairo',
                    fontSize: 18,
                    fontWeight: FontWeight.bold),
              ),
            ],
          ),
          content: Text(
            message,
            style: const TextStyle(
                color: AppColors.textMuted, fontFamily: 'Cairo', fontSize: 14),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(
                _isArabic ? 'إلغاء' : 'Cancel',
                style:
                    const TextStyle(color: AppColors.textMuted, fontFamily: 'Cairo'),
              ),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primaryTeal,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: () {
                Navigator.pop(context);
                Navigator.pop(context, true);
              },
              child: Text(
                _isArabic ? 'اشترك الآن' : 'Subscribe Now',
                style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showWaitReplyDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: AlertDialog(
          backgroundColor: AppColors.cardColor,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          title: Row(
            children: [
              const Icon(Icons.mark_chat_read_rounded,
                  color: AppColors.primaryTeal, size: 28),
              const SizedBox(width: 10),
              Text(
                _isArabic ? 'في انتظار الرد' : 'Waiting for Reply',
                style: const TextStyle(
                    color: AppColors.textDark,
                    fontFamily: 'Cairo',
                    fontSize: 18,
                    fontWeight: FontWeight.bold),
              ),
            ],
          ),
          content: Text(
            message,
            style: const TextStyle(
                color: AppColors.textMuted,
                fontFamily: 'Cairo',
                fontSize: 14,
                height: 1.4),
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primaryTeal,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: () => Navigator.pop(context),
              child: Text(
                _isArabic ? 'حسناً، فهمت' : 'OK, Got it',
                style: const TextStyle(
                    color: AppColors.textDark,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showChatOptionsMenu() {
    _dismissKeyboard();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: SafeArea(
          child: ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
              child: Container(
                padding: EdgeInsets.fromLTRB(
                  22,
                  24,
                  22,
                  24 + MediaQuery.of(context).padding.bottom,
                ),
                decoration: BoxDecoration(
                  color: AppColors.cardColor.withValues(alpha: 0.92),
                  borderRadius:
                      const BorderRadius.vertical(top: Radius.circular(32)),
                  border:
                      Border.all(color: AppColors.lineColor),
                ),
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 42,
                          height: 4.5,
                          decoration: BoxDecoration(
                            color: AppColors.lineColor,
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: AppColors.primaryTeal
                                  .withValues(alpha: 0.15),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.shield_outlined,
                                color: AppColors.primaryTeal, size: 22),
                          ),
                          const SizedBox(width: 12),
                          Text(
                            _isArabic
                                ? 'خيارات الحماية والتواصل'
                                : 'Safety & Chat Options',
                            style: const TextStyle(
                                color: AppColors.textDark,
                                fontSize: 17,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Cairo'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      Material(
                        color: Colors.transparent,
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: AppColors.primaryTeal
                                  .withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: const Icon(Icons.person_outline_rounded,
                                color: AppColors.primaryTeal, size: 22),
                          ),
                          title: Text(
                              _isArabic ? 'عرض الملف الشخصي' : 'View Profile',
                              style: const TextStyle(
                                  color: AppColors.textDark,
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15)),
                          subtitle: Text(
                              _isArabic
                                  ? 'الاطلاع على تفاصيل ومعلومات العضو'
                                  : 'View member profile details',
                              style: const TextStyle(
                                  color: AppColors.textMuted,
                                  fontSize: 12,
                                  fontFamily: 'Cairo')),
                          trailing: Icon(
                              _isArabic
                                  ? Icons.arrow_back_ios_rounded
                                  : Icons.arrow_forward_ios_rounded,
                              color: AppColors.textMuted,
                              size: 16),
                          onTap: () {
                            Navigator.pop(context);
                            _navigateToProfile();
                          },
                        ),
                      ),
                      const Divider(color: AppColors.lineColor, height: 24),
                      Material(
                        color: Colors.transparent,
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: AppColors.warning.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: const Icon(Icons.report_problem_rounded,
                                color: AppColors.warning, size: 22),
                          ),
                          title: Text(
                              _isArabic
                                  ? 'الإبلاغ عن الحساب'
                                  : 'Report Account',
                              style: const TextStyle(
                                  color: AppColors.textDark,
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15)),
                          subtitle: Text(
                              _isArabic
                                  ? 'إرسال بلاغ لفريق الإدارة لمراجعته'
                                  : 'Submit a report to moderation team',
                              style: const TextStyle(
                                  color: AppColors.textMuted,
                                  fontSize: 12,
                                  fontFamily: 'Cairo')),
                          trailing: Icon(
                              _isArabic
                                  ? Icons.arrow_back_ios_rounded
                                  : Icons.arrow_forward_ios_rounded,
                              color: AppColors.textMuted,
                              size: 16),
                          onTap: () {
                            Navigator.pop(context);
                            _showReportReasonsDialog();
                          },
                        ),
                      ),
                      const Divider(color: AppColors.lineColor, height: 24),
                      Material(
                        color: Colors.transparent,
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: AppColors.error.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: const Icon(Icons.block_rounded,
                                color: AppColors.error, size: 22),
                          ),
                          title: Text(
                              _isArabic
                                  ? 'حظر العضو (إضافة للتجاهل)'
                                  : 'Block Member',
                              style: const TextStyle(
                                  color: AppColors.error,
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15)),
                          subtitle: Text(
                              _isArabic
                                  ? 'منع العضو من مراسلتك وإغلاق المحادثة فوراً'
                                  : 'Block member and remove chat thread',
                              style: const TextStyle(
                                  color: AppColors.textMuted,
                                  fontSize: 12,
                                  fontFamily: 'Cairo')),
                          trailing: Icon(
                              _isArabic
                                  ? Icons.arrow_back_ios_rounded
                                  : Icons.arrow_forward_ios_rounded,
                              color: AppColors.error,
                              size: 16),
                          onTap: () {
                            Navigator.pop(context);
                            _showBlockConfirmationDialog();
                          },
                        ),
                      ),
                      const Divider(color: AppColors.lineColor, height: 24),
                      Material(
                        color: Colors.transparent,
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: AppColors.primaryTeal.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: Icon(
                              _isChatMuted
                                  ? Icons.notifications_active_outlined
                                  : Icons.notifications_off_outlined,
                              color: AppColors.primaryTeal,
                              size: 22,
                            ),
                          ),
                          title: Text(
                            _isArabic
                                ? (_isChatMuted ? 'إلغاء كتم المحادثة' : 'كتم المحادثة')
                                : (_isChatMuted ? 'Unmute conversation' : 'Mute conversation'),
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontFamily: 'Cairo',
                              fontWeight: FontWeight.bold,
                              fontSize: 15,
                            ),
                          ),
                          subtitle: Text(
                            _isArabic
                                ? (_isChatMuted
                                    ? 'ستعود تنبيهات هذه المحادثة للعمل مع استمرار استقبال الرسائل'
                                    : 'إيقاف تنبيهات هذه المحادثة فقط مع استمرار استقبال الرسائل')
                                : (_isChatMuted
                                    ? 'Turn alerts for this conversation back on.'
                                    : 'Silence alerts for this conversation only.'),
                            style: const TextStyle(
                              color: AppColors.textMuted,
                              fontSize: 12,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          onTap: () {
                            Navigator.pop(context);
                            _toggleChatMute();
                          },
                        ),
                      ),
                      const Divider(color: AppColors.lineColor, height: 24),
                      Material(
                        color: Colors.transparent,
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: AppColors.sectionBackground,
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: const Icon(Icons.delete_outline_rounded,
                                color: AppColors.textMuted, size: 22),
                          ),
                          title: Text(
                              _isArabic ? 'حذف المحادثة' : 'Delete Chat',
                              style: const TextStyle(
                                  color: AppColors.textMuted,
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15)),
                          subtitle: Text(
                              _isArabic
                                  ? 'حذف سجل الرسائل الحالي من التطبيق والسيرفر'
                                  : 'Remove chat history from app and server',
                              style: const TextStyle(
                                  color: AppColors.textMuted,
                                  fontSize: 12,
                                  fontFamily: 'Cairo')),
                          onTap: () {
                            Navigator.pop(context);
                            _showDeleteChatConfirmationDialog();
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showReportReasonsDialog({ChatMessageModel? reportedMessage}) {
    _dismissKeyboard();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: SafeArea(
          child: ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
              child: Container(
                padding: EdgeInsets.fromLTRB(
                  22,
                  22,
                  22,
                  22 + MediaQuery.of(context).padding.bottom,
                ),
                decoration: BoxDecoration(
                  color: AppColors.cardColor.withValues(alpha: 0.95),
                  borderRadius:
                      const BorderRadius.vertical(top: Radius.circular(32)),
                  border:
                      Border.all(color: AppColors.warning.withValues(alpha: 0.3)),
                ),
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                          _isArabic
                              ? 'اختر سبب الإبلاغ'
                              : 'Select Report Reason',
                          style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo')),
                      const SizedBox(height: 6),
                      Text(
                          _isArabic
                              ? 'ساعدنا في الحفاظ على بيئة آمنة ومحترمة داخل وصال'
                              : 'Help us maintain a safe community in Wesal',
                          style: const TextStyle(
                              color: AppColors.textMuted,
                              fontSize: 12,
                              fontFamily: 'Cairo')),
                      const SizedBox(height: 16),
                      ..._reportReasons.map((reason) {
                        return Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          decoration: BoxDecoration(
                            color: AppColors.sectionBackground,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                                color: AppColors.lineColor),
                          ),
                          child: Material(
                            color: Colors.transparent,
                            borderRadius: BorderRadius.circular(16),
                            child: ListTile(
                              title: Text(reason,
                                  style: const TextStyle(
                                      color: AppColors.textDark,
                                      fontSize: 14,
                                      fontFamily: 'Cairo')),
                              trailing: Icon(
                                  _isArabic
                                      ? Icons.chevron_left_rounded
                                      : Icons.chevron_right_rounded,
                                  color: AppColors.textMuted),
                              onTap: () {
                                Navigator.pop(context);
                                _executeReport(reason, reportedMessage: reportedMessage);
                              },
                            ),
                          ),
                        );
                      }),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showBlockConfirmationDialog() {
    _dismissKeyboard();

    showDialog(
      context: context,
      builder: (context) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          insetPadding: const EdgeInsets.symmetric(horizontal: 40),
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
                      color: AppColors.error.withValues(alpha: 0.4),
                      width: 1.5),
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
                      child: const Icon(Icons.block_rounded,
                          color: AppColors.error, size: 32),
                    ),
                    const SizedBox(height: 14),
                    Text(
                        _isArabic
                            ? 'حظر ${widget.thread.name}؟'
                            : 'Block ${widget.thread.name}?',
                        style: const TextStyle(
                            color: AppColors.textDark,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo')),
                    const SizedBox(height: 8),
                    Text(
                        _isArabic
                            ? 'سيتم منع تبادل الرسائل مع هذا العضو مع الاحتفاظ بسجل المحادثة والرسائل.'
                            : 'Messaging will be blocked while the conversation history is preserved.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                            color: AppColors.textMuted,
                            fontSize: 13,
                            height: 1.4,
                            fontFamily: 'Cairo')),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(
                          child: TextButton(
                            onPressed: () => Navigator.pop(context),
                            child: Text(_isArabic ? 'إلغاء' : 'Cancel',
                                style: const TextStyle(
                                    color: AppColors.textMuted,
                                    fontFamily: 'Cairo')),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.error,
                                shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(14)),
                                padding:
                                    const EdgeInsets.symmetric(vertical: 12)),
                            onPressed: () {
                              Navigator.pop(context);
                              _executeBlock();
                            },
                            child: Text(
                                _isArabic ? 'تأكيد الحظر' : 'Confirm Block',
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo')),
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

  Future<void> _executeBlock() async {
    _dismissKeyboard();
    try {
      final result = await ApiService.addToIgnoreList(widget.thread.userId);
      if (!mounted) return;

      if (result['success'] == true) {
        if (!mounted) return;
        Navigator.pop(context, true);

        AppSnackBar.show(
          context: context,
          type: NotificationType.warning,
          isArabic: _isArabic,
          messageAr: 'تم حظر العضو مع الاحتفاظ بسجل المحادثة',
          messageEn: 'Member blocked; conversation history preserved',
        );
      } else {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isArabic,
          messageAr: 'تعذر تنفيذ الحظر حالياً، يرجى المحاولة لاحقاً',
          messageEn: 'Failed to block user',
        );
      }
    } catch (e) {
      if (!mounted) return;
      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: _isArabic,
        messageAr: 'تعذر تنفيذ الحظر حالياً، يرجى المحاولة لاحقاً',
        messageEn: 'Failed to block user',
      );
    }
  }

  Future<void> _executeReport(
    String reason, {
    ChatMessageModel? reportedMessage,
  }) async {
    _dismissKeyboard();
    try {
      final result = await ApiService.reportMember(
        widget.thread.userId,
        reason,
        chatThreadId: _activeThreadId,
        messageId: reportedMessage?.id,
        contentType: reportedMessage == null
            ? null
            : ((reportedMessage.attachmentUrl?.isNotEmpty ?? false) ? 'image' : 'message'),
      );
      if (!mounted) return;

      if (result['success'] == true) {
        if (!mounted) return;
        AppSnackBar.show(
          context: context,
          type: NotificationType.success,
          isArabic: _isArabic,
          messageAr: 'تم إرسال البلاغ مع حفظ آخر خمس رسائل للأدمن',
          messageEn: 'Report submitted with the latest five messages saved for review',
        );
      } else {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isArabic,
          messageAr: 'تعذر إرسال البلاغ حالياً، يرجى المحاولة لاحقاً',
          messageEn: 'Failed to submit report',
        );
      }
    } catch (e) {
      if (!mounted) return;
      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: _isArabic,
        messageAr: 'تعذر إرسال البلاغ حالياً، يرجى المحاولة لاحقاً',
        messageEn: 'Failed to submit report',
      );
    }
  }

  void _showMessageActions(ChatMessageModel message) {
    _dismissKeyboard();
    services.HapticFeedback.selectionClick();

    final cleanText = message.message.trim();
    final canCopy = cleanText.isNotEmpty && !_isDefaultPlaceholderText(cleanText);
    final canDeleteForEveryone = message.isMe && message.id > 0;

    showModalBottomSheet<void>(
      context: context,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: Container(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
          decoration: const BoxDecoration(
            color: AppColors.cardColor,
            borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 38,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.textLight,
                  borderRadius: BorderRadius.circular(20),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                _isArabic ? 'خيارات الرسالة' : 'Message options',
                style: const TextStyle(
                  color: AppColors.textDark,
                  fontFamily: 'Cairo',
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
              if (canCopy)
                ListTile(
                  leading: const Icon(Icons.copy_rounded, color: AppColors.primaryTeal),
                  title: Text(_isArabic ? 'نسخ النص' : 'Copy text'),
                  onTap: () {
                    Navigator.pop(sheetContext);
                    _copyMessageText(cleanText);
                  },
                ),
              ListTile(
                leading: Icon(
                  canDeleteForEveryone ? Icons.delete_forever_rounded : Icons.visibility_off_rounded,
                  color: AppColors.error,
                ),
                title: Text(
                  canDeleteForEveryone
                      ? (_isArabic ? 'حذف الرسالة لدى الطرفين' : 'Delete for everyone')
                      : (_isArabic ? 'إخفاء الرسالة لدي' : 'Hide for me'),
                ),
                subtitle: Text(
                  canDeleteForEveryone
                      ? (_isArabic ? 'ستُحذف رسالتك من المحادثة لدى الطرفين' : 'Your sent message will be removed for both participants')
                      : (_isArabic ? 'لن تتأثر نسخة الرسالة لدى المرسل' : 'The sender will keep their copy'),
                  style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                ),
                onTap: () {
                  Navigator.pop(sheetContext);
                  _confirmMessageRemoval(
                    message,
                    deleteForEveryone: canDeleteForEveryone,
                  );
                },
              ),
              if (!message.isMe)
                ListTile(
                  leading: const Icon(Icons.flag_rounded, color: AppColors.warning),
                  title: Text(_isArabic ? 'الإبلاغ عن هذه الرسالة' : 'Report this message'),
                  onTap: () {
                    Navigator.pop(sheetContext);
                    _showReportReasonsDialog(reportedMessage: message);
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _copyMessageText(String text) async {
    try {
      await services.Clipboard.setData(services.ClipboardData(text: text));
      if (!mounted) return;
      AppSnackBar.show(
        context: context,
        type: NotificationType.success,
        isArabic: _isArabic,
        messageAr: 'تم نسخ نص الرسالة',
        messageEn: 'Message text copied',
      );
    } catch (_) {
      if (!mounted) return;
      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: _isArabic,
        messageAr: 'تعذر نسخ الرسالة',
        messageEn: 'Could not copy message',
      );
    }
  }

  Future<void> _confirmMessageRemoval(
    ChatMessageModel message, {
    required bool deleteForEveryone,
  }) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: AlertDialog(
          title: Text(_isArabic ? 'تأكيد حذف الرسالة' : 'Confirm message removal'),
          content: Text(
            deleteForEveryone
                ? (_isArabic
                    ? 'سيتم حذف رسالتك من المحادثة لدى الطرفين.'
                    : 'Your sent message will be deleted for both participants.')
                : (_isArabic
                    ? 'سيتم إخفاء هذه الرسالة من محادثتك فقط.'
                    : 'This message will be hidden only in your chat.'),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: Text(_isArabic ? 'إلغاء' : 'Cancel'),
            ),
            FilledButton(
              style: FilledButton.styleFrom(backgroundColor: AppColors.error),
              onPressed: () => Navigator.pop(dialogContext, true),
              child: Text(_isArabic ? 'تأكيد' : 'Confirm'),
            ),
          ],
        ),
      ),
    );
    if (confirmed == true && mounted) {
      await _removeMessage(message, deleteForEveryone: deleteForEveryone);
    }
  }

  Future<void> _removeMessage(
    ChatMessageModel message, {
    required bool deleteForEveryone,
  }) async {
    if (deleteForEveryone && message.id > 0) {
      final result = await ApiService.deleteChatMessage(
        threadId: _activeThreadId,
        messageId: message.id,
      );
      if (!mounted) return;
      if (result['success'] != true) {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isArabic,
          messageAr: 'تعذر حذف الرسالة من الطرفين',
          messageEn: 'Could not delete the message for both participants',
        );
        return;
      }
    }

    if (message.id > 0 && _currentUserId > 0) {
      await ChatLocalStore.hideMessage(
        _currentUserId,
        _activeThreadId,
        message.id,
      );
    }
    if (!mounted) return;
    setState(() {
      _messages.removeWhere((item) => item.id == message.id);
      _messageSearchResults.removeWhere((item) => item.id == message.id);
      _revealedIncomingImageIds.remove(message.id);
    });
    AppSnackBar.show(
      context: context,
      type: NotificationType.success,
      isArabic: _isArabic,
      messageAr: deleteForEveryone
          ? 'تم حذف الرسالة من المحادثة'
          : 'تم إخفاء الرسالة من محادثتك',
      messageEn: deleteForEveryone
          ? 'Message deleted from the conversation'
          : 'Message hidden from your chat',
    );
  }

  void _showDeleteChatConfirmationDialog() {
    _dismissKeyboard();
    showDialog<void>(
      context: context,
      builder: (dialogContext) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: AlertDialog(
          title: Text(_isArabic ? 'تأكيد حذف المحادثة' : 'Delete conversation?'),
          content: Text(_isArabic
              ? 'هل أنت متأكد من حذف سجل المحادثة من التطبيق والسيرفر؟ لا يمكن التراجع عن هذا الإجراء.'
              : 'Are you sure you want to delete this conversation from the app and server? This cannot be undone.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: Text(_isArabic ? 'إلغاء' : 'Cancel'),
            ),
            FilledButton(
              style: FilledButton.styleFrom(backgroundColor: AppColors.error),
              onPressed: () {
                Navigator.of(dialogContext).pop();
                _confirmDeleteChat();
              },
              child: Text(_isArabic ? 'حذف المحادثة' : 'Delete chat'),
            ),
          ],
        ),
      ),
    );
  }


  Future<void> _confirmDeleteChat() async {
    _dismissKeyboard();
    try {
      final result = await ApiService.deleteChatThread(_activeThreadId);
      if (!mounted) return;
      if (result['success'] == true) {
        await ChatLocalStore.deleteThread(_currentUserId, _activeThreadId);
        if (!mounted) return;
        Navigator.pop(context, true);

        AppSnackBar.showDeleted(
          context: context,
          isArabic: _isArabic,
        );
      } else {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: _isArabic,
          messageAr: 'تعذر حذف المحادثة حالياً، يرجى المحاولة لاحقاً',
          messageEn: 'Failed to delete chat thread',
        );
      }
    } catch (e) {
      if (!mounted) return;
      AppSnackBar.show(
        context: context,
        type: NotificationType.error,
        isArabic: _isArabic,
        messageAr: 'تعذر حذف المحادثة حالياً، يرجى المحاولة لاحقاً',
        messageEn: 'Failed to delete chat thread',
      );
    }
  }

  /// شريط إرشادي واضح لا يعيق قراءة الرسائل أو التفاعل معها.
  Widget _buildRulesBanner() {
    if (_chatRules == null || _chatRules!.isUnlocked) {
      return const SizedBox.shrink();
    }

    final String bannerText = _isArabic
        ? _chatRules!.infoMessage
        : 'To prevent spam, you can send up to ${_chatRules!.remainingMessages} remaining message(s). Once the other party replies, unlimited chat unlocks.';

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(14, 0, 14, 8),
      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.sectionBackground,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.lineColor),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.info_outline_rounded,
            color: AppColors.primaryTeal,
            size: 19,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              bannerText,
              style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 11.5,
                fontFamily: 'Cairo',
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool hasMessageSearch =
        _showMessageSearch && _messageSearchController.text.trim().isNotEmpty;
    final List<ChatMessageModel> visibleMessages =
        ChatMessageModel.newestFirst(
      hasMessageSearch ? _messageSearchResults : _messages,
    );
    return PopScope<bool>(
      canPop: _canPopNow,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        _handlePop();
      },
      child: Scaffold(
        backgroundColor: AppColors.backgroundColor,
        body: Directionality(
          textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
          child: SafeArea(
            child: Column(
              children: [
                _buildHeader(),
                _buildMessageSearchBar(),
                _buildRulesBanner(),
                if (Platform.isIOS) _buildIosScreenshotNotice(),
                if (_isLoadingMore)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 6),
                    child: SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        color: AppColors.primaryTeal,
                        strokeWidth: 2,
                      ),
                    ),
                  ),
                Expanded(
                  child: GestureDetector(
                    onTap: () {
                      _dismissKeyboard();
                      if (_showEmojiPicker) {
                        setState(() => _showEmojiPicker = false);
                      }
                    },
                    child: _isLoading
                        ? const Center(
                            child: CircularProgressIndicator(
                              color: AppColors.primaryTeal,
                            ),
                          )
                        : (_messages.isEmpty && !hasMessageSearch)
                            ? _hasMessageLoadError
                                ? Center(
                                    child: Padding(
                                      padding: const EdgeInsets.all(24),
                                      child: Column(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(
                                            Icons.cloud_off_rounded,
                                            color: AppColors.textLight,
                                            size: 46,
                                          ),
                                          const SizedBox(height: 12),
                                          Text(
                                            _messageLoadError,
                                            textAlign: TextAlign.center,
                                            style: const TextStyle(
                                              color: AppColors.textDark,
                                              fontSize: 14,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                          const SizedBox(height: 14),
                                          ElevatedButton.icon(
                                            style: ElevatedButton.styleFrom(
                                              backgroundColor:
                                                  AppColors.primaryTeal,
                                              foregroundColor: Colors.white,
                                            ),
                                            onPressed: () => _loadMessages(),
                                            icon: const Icon(
                                              Icons.refresh_rounded,
                                            ),
                                            label: Text(
                                              _isArabic
                                                  ? 'إعادة المحاولة'
                                                  : 'Retry',
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  )
                                : Center(
                                    child: Padding(
                                      padding: const EdgeInsets.all(24),
                                      child: Column(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(
                                            Icons.forum_outlined,
                                            color: AppColors.textLight,
                                            size: 42,
                                          ),
                                          const SizedBox(height: 12),
                                          Text(
                                            _isArabic
                                                ? 'ابدأ محادثة لطيفة ومحترمة'
                                                : 'Start a kind and respectful conversation',
                                            textAlign: TextAlign.center,
                                            style: const TextStyle(
                                              color: AppColors.textDark,
                                              fontSize: 14,
                                              fontWeight: FontWeight.w600,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            _isArabic
                                                ? 'ستظهر رسائلك هنا'
                                                : 'Your messages will appear here',
                                            style: const TextStyle(
                                              color: AppColors.textMuted,
                                              fontSize: 12,
                                              fontFamily: 'Cairo',
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  )
                            : hasMessageSearch && visibleMessages.isEmpty
                                ? Center(
                                    child: Padding(
                                      padding: const EdgeInsets.all(24),
                                      child: Text(
                                        _isArabic
                                            ? 'لا توجد رسائل محفوظة تطابق هذا البحث'
                                            : 'No saved messages match this search',
                                        textAlign: TextAlign.center,
                                        style: const TextStyle(
                                          color: AppColors.textMuted,
                                          fontFamily: 'Cairo',
                                          fontSize: 13,
                                        ),
                                      ),
                                    ),
                                  )
                                : ListView.builder(
                                controller: _scrollController,
                                reverse: true,
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 14,
                                  vertical: 12,
                                ),
                                itemCount: visibleMessages.length,
                                itemBuilder: (context, index) {
                                  final msg = visibleMessages[index];
                                  return GestureDetector(
                                    onLongPress: () => _showMessageActions(msg),
                                    child: RepaintBoundary(
                                      key: ValueKey(msg.id),
                                      child: msg.isMe
                                          ? _SenderMessageBubble(
                                              message: msg,
                                              isArabic: _isArabic,
                                            )
                                          : _ReceiverMessageBubble(
                                              message: msg,
                                              isArabic: _isArabic,
                                              isImageRevealed:
                                                  _revealedIncomingImageIds.contains(msg.id),
                                              onRevealImage: () {
                                                if (mounted) {
                                                  setState(() => _revealedIncomingImageIds.add(msg.id));
                                                }
                                              },
                                              onReportImage: () =>
                                                  _showReportReasonsDialog(reportedMessage: msg),
                                            ),
                                    ),
                                  );
                                },
                              ),
                  ),
                ),
                _buildInputArea(),
                if (_showEmojiPicker) _buildEmojiStickerPicker(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildIosScreenshotNotice() {
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 0, 14, 8),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.warning.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.warning.withValues(alpha: 0.28)),
      ),
      child: Row(
        children: [
          const Icon(Icons.info_outline_rounded,
              color: AppColors.warning, size: 18),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              _isArabic
                  ? 'على iPhone لا يتيح النظام للتطبيق منع لقطة الشاشة قبل التقاطها؛ الحماية الفعلية هنا متاحة على Android.'
                  : 'iOS does not let apps prevent screenshots before capture; active screenshot blocking is available here on Android.',
              style: const TextStyle(
                color: AppColors.textDark,
                fontSize: 10.5,
                fontFamily: 'Cairo',
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// رأس المحادثة ببطاقة فاتحة مع بقاء ملف العضو وقائمة الخيارات قابلين للفتح.
  Widget _buildHeader() {
    final String presenceText = _partnerIsOnline
        ? (_isArabic ? 'متصل الآن' : 'Online')
        : _translateLastSeen(_partnerLastSeen);

    return Container(
      margin: const EdgeInsets.fromLTRB(14, 8, 14, 10),
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 7),
      decoration: BoxDecoration(
        color: AppColors.cardColor,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: AppColors.lineColor),
        boxShadow: [
          BoxShadow(
            color: AppColors.textDark.withValues(alpha: 0.045),
            blurRadius: 14,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Row(
        children: [
          Material(
            color: AppColors.sectionBackground,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: _handlePop,
              child: SizedBox(
                width: 40,
                height: 40,
                child: Icon(
                  _isArabic
                      ? Icons.arrow_back_ios_new_rounded
                      : Icons.arrow_forward_ios_rounded,
                  color: AppColors.textDark,
                  size: 17,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: InkWell(
              onTap: _navigateToProfile,
              borderRadius: BorderRadius.circular(15),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 3),
                child: Row(
                  children: [
                    Stack(
                      children: [
                        _StyledGenderAvatar(
                          imageUrl: widget.thread.image,
                          gender: widget.thread.gender,
                          radius: 21,
                        ),
                        if (_partnerIsOnline)
                          Positioned(
                            bottom: 0,
                            right: 0,
                            child: Container(
                              width: 12,
                              height: 12,
                              decoration: BoxDecoration(
                                color: AppColors.success,
                                shape: BoxShape.circle,
                                border: Border.all(
                                  color: Colors.white,
                                  width: 2,
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Row(
                            children: [
                              Flexible(
                                child: Text(
                                  widget.thread.name,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: AppColors.textDark,
                                    fontSize: 14.5,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Cairo',
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 2),
                          Row(
                            children: [
                              if (_partnerIsOnline)
                                Container(
                                  margin: const EdgeInsetsDirectional.only(end: 5),
                                  width: 6,
                                  height: 6,
                                  decoration: const BoxDecoration(
                                    color: AppColors.success,
                                    shape: BoxShape.circle,
                                  ),
                                ),
                              Expanded(
                                child: Text(
                                  presenceText,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    color: _partnerIsOnline
                                        ? AppColors.success
                                        : AppColors.textMuted,
                                    fontSize: 10.5,
                                    fontFamily: 'Cairo',
                                    fontWeight: _partnerIsOnline
                                        ? FontWeight.w600
                                        : FontWeight.normal,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(width: 4),
          Material(
            color: AppColors.sectionBackground,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: _toggleMessageSearch,
              child: SizedBox(
                width: 40,
                height: 40,
                child: Icon(
                  _showMessageSearch
                      ? Icons.search_off_rounded
                      : Icons.search_rounded,
                  color: AppColors.primaryTeal,
                  size: 20,
                ),
              ),
            ),
          ),
          const SizedBox(width: 6),
          Material(
            color: AppColors.sectionBackground,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: _showChatOptionsMenu,
              child: const SizedBox(
                width: 40,
                height: 40,
                child: Icon(
                  Icons.more_vert_rounded,
                  color: AppColors.textDark,
                  size: 22,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInputArea() {
    final outline = OutlineInputBorder(
      borderRadius: BorderRadius.circular(20),
      borderSide: const BorderSide(color: AppColors.lineColor),
    );
    return Container(
      padding: const EdgeInsets.fromLTRB(5, 8, 5, 8),
      decoration: BoxDecoration(
        color: AppColors.cardColor,
        border: const Border(
          top: BorderSide(color: AppColors.lineColor),
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.textDark.withValues(alpha: 0.035),
            blurRadius: 10,
            offset: const Offset(0, -3),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          IconButton(
            constraints: const BoxConstraints.tightFor(width: 40, height: 48),
            padding: EdgeInsets.zero,
            visualDensity: VisualDensity.compact,
            tooltip: _partnerHasReplied
                ? (_isArabic ? 'إرفاق صورة' : 'Attach image')
                : (_isArabic
                    ? 'تتاح الصور بعد أول رد من الطرف الآخر'
                    : 'Available after the other person replies'),
            icon: Icon(
              Icons.add_a_photo_rounded,
              color: _partnerHasReplied
                  ? AppColors.primaryTeal
                  : AppColors.textMuted,
              size: 21,
            ),
            onPressed: () => _pickImage(ImageSource.gallery),
          ),
          IconButton(
            constraints: const BoxConstraints.tightFor(width: 40, height: 48),
            padding: EdgeInsets.zero,
            visualDensity: VisualDensity.compact,
            tooltip: _isArabic ? 'الرموز والملصقات' : 'Emojis and stickers',
            icon: Icon(
              _showEmojiPicker
                  ? Icons.keyboard_rounded
                  : Icons.sentiment_satisfied_alt_rounded,
              color: AppColors.accentWarm,
              size: 23,
            ),
            onPressed: _toggleEmojiPicker,
          ),
          const SizedBox(width: 5),
          Expanded(
            child: ConstrainedBox(
              constraints: const BoxConstraints(
                minHeight: 48,
                maxHeight: 104,
              ),
              child: TextField(
                controller: _messageController,
                focusNode: _inputFocusNode,
                minLines: 1,
                maxLines: 4,
                keyboardType: TextInputType.multiline,
                textCapitalization: TextCapitalization.sentences,
                textAlignVertical: TextAlignVertical.center,
                style: const TextStyle(
                  color: AppColors.textDark,
                  fontFamily: 'Cairo',
                  fontSize: 14,
                ),
                decoration: InputDecoration(
                  hintText: _isArabic
                      ? 'اكتب رسالتك هنا...'
                      : 'Type your message...',
                  hintStyle: const TextStyle(
                    color: AppColors.textMuted,
                    fontFamily: 'Cairo',
                    fontSize: 12.5,
                  ),
                  filled: true,
                  fillColor: AppColors.sectionBackground,
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                  border: outline,
                  enabledBorder: outline,
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(20),
                    borderSide: const BorderSide(
                      color: AppColors.primaryTeal,
                      width: 1.3,
                    ),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(width: 7),
          Material(
            color: AppColors.primaryTeal,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: () => _sendMessage(),
              child: const SizedBox(
                width: 44,
                height: 44,
                child: Icon(
                  Icons.send_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmojiStickerPicker() {
    return Container(
      height: 230,
      decoration: const BoxDecoration(
        color: AppColors.cardColor,
        border: Border(
          top: BorderSide(color: AppColors.lineColor),
        ),
      ),
      child: Column(
        children: [
          Container(
            height: 44,
            decoration: const BoxDecoration(
              color: AppColors.sectionBackground,
              border: Border(
                bottom: BorderSide(color: AppColors.lineColor),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _selectedPickerTab = 0),
                    child: Container(
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        border: Border(
                          bottom: BorderSide(
                            color: _selectedPickerTab == 0
                                ? AppColors.primaryTeal
                                : Colors.transparent,
                            width: 2,
                          ),
                        ),
                      ),
                      child: Text(
                        _isArabic ? 'وجوه تعبيرية' : 'Emojis',
                        style: TextStyle(
                          color: _selectedPickerTab == 0
                              ? AppColors.primaryTeal
                              : AppColors.textMuted,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _selectedPickerTab = 1),
                    child: Container(
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        border: Border(
                          bottom: BorderSide(
                            color: _selectedPickerTab == 1
                                ? AppColors.primaryTeal
                                : Colors.transparent,
                            width: 2,
                          ),
                        ),
                      ),
                      child: Text(
                        _isArabic ? 'ملصقات وصال' : 'Wesal Stickers',
                        style: TextStyle(
                          color: _selectedPickerTab == 1
                              ? AppColors.primaryTeal
                              : AppColors.textMuted,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: _selectedPickerTab == 0
                ? GridView.builder(
                    padding: const EdgeInsets.all(10),
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 8,
                      mainAxisSpacing: 8,
                      crossAxisSpacing: 8,
                    ),
                    itemCount: _emojis.length,
                    itemBuilder: (context, index) {
                      final emoji = _emojis[index];
                      return InkWell(
                        onTap: () {
                          _messageController.text =
                              _messageController.text + emoji;
                          _messageController.selection =
                              TextSelection.fromPosition(
                            TextPosition(
                              offset: _messageController.text.length,
                            ),
                          );
                        },
                        child: Center(
                          child: Text(
                            emoji,
                            style: const TextStyle(fontSize: 22),
                          ),
                        ),
                      );
                    },
                  )
                : GridView.builder(
                    padding: const EdgeInsets.all(12),
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 2.8,
                      mainAxisSpacing: 10,
                      crossAxisSpacing: 10,
                    ),
                    itemCount: _stickers.length,
                    itemBuilder: (context, index) {
                      final sticker = _stickers[index];
                      return InkWell(
                        onTap: () => _sendMessage(customText: sticker),
                        borderRadius: BorderRadius.circular(14),
                        child: Container(
                          padding:
                              const EdgeInsets.symmetric(horizontal: 10),
                          decoration: BoxDecoration(
                            color: AppColors.accentLight.withValues(alpha: 0.28),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: AppColors.accentLight,
                            ),
                          ),
                          alignment: Alignment.center,
                          child: Text(
                            sticker,
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                            ),
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

}

bool _isDefaultPlaceholderText(String text) {
  final clean = text.trim().toLowerCase();
  return clean == 'أرسل صورة' ||
      clean == 'صورة مرفقة' ||
      clean == 'sent an image' ||
      clean == 'image attachment' ||
      clean == 'photo attachment';
}

bool _isNetworkUrl(String? url) {
  if (url == null || url.isEmpty) return false;
  final lower = url.toLowerCase();
  return lower.startsWith('http://') || lower.startsWith('https://');
}

/// 🟢 صيغة ترجمة توقيت الرسائل الفردية
String _formatMessageTime(String time, bool isArabic) {
  return formatChatMessageTime(time, isArabic: isArabic);
}

class _SenderMessageBubble extends StatelessWidget {
  final ChatMessageModel message;
  final bool isArabic;

  const _SenderMessageBubble({
    required this.message,
    this.isArabic = true,
  });

  @override
  Widget build(BuildContext context) {
    final bool hasImage =
        message.attachmentUrl != null && message.attachmentUrl!.isNotEmpty;
    final String cleanText = message.message.trim();
    final bool isDefaultPhotoText = _isDefaultPlaceholderText(cleanText);
    final bool shouldShowText = cleanText.isNotEmpty && !isDefaultPhotoText;

    const borderRadius = BorderRadius.only(
      topLeft: Radius.circular(20),
      topRight: Radius.circular(20),
      bottomLeft: Radius.circular(20),
      bottomRight: Radius.circular(4),
    );

    return Align(
      alignment: Alignment.centerRight,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              constraints: BoxConstraints(
                  maxWidth: MediaQuery.of(context).size.width * 0.75),
              decoration: const BoxDecoration(
                color: AppColors.primaryTeal,
                borderRadius: borderRadius,
              ),
              child: ClipRRect(
                borderRadius: borderRadius,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    if (hasImage)
                      _isNetworkUrl(message.attachmentUrl)
                          ? CachedNetworkImage(
                              imageUrl: message.attachmentUrl!,
                              width: double.infinity,
                              fit: BoxFit.cover,
                              memCacheWidth: 800,
                              placeholder: (context, url) =>
                                  Container(height: 180, color: Colors.black26),
                              errorWidget: (context, url, error) =>
                                  const SizedBox.shrink(),
                            )
                          : Image.file(
                              File(message.attachmentUrl!),
                              width: double.infinity,
                              fit: BoxFit.cover,
                              cacheWidth: 800,
                              errorBuilder: (context, error, stackTrace) =>
                                  Container(
                                height: 120,
                                color: Colors.black26,
                                alignment: Alignment.center,
                                child: const Icon(Icons.broken_image_rounded,
                                    color: Colors.white54, size: 30),
                              ),
                            ),
                    if (shouldShowText)
                      Padding(
                        padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
                        child: Text(
                          cleanText,
                          textDirection: MessageTextDirection.of(cleanText),
                          textAlign: TextAlign.start,
                          style: const TextStyle(
                                color: Colors.white,
                                fontSize: 14.5,
                                fontFamily: 'Cairo',
                                height: 1.35)),
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 3),
            Padding(
              padding: const EdgeInsets.only(right: 4, left: 4),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(_formatMessageTime(message.time, isArabic),
                      style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 10.5,
                          fontFamily: 'Cairo')),
                  const SizedBox(width: 5),
                  Icon(
                      message.isSeen
                          ? Icons.done_all_rounded
                          : Icons.check_rounded,
                      size: 14,
                      color: message.isSeen
                          ? AppColors.primaryTeal
                          : AppColors.textLight),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ReceiverMessageBubble extends StatelessWidget {
  final ChatMessageModel message;
  final bool isArabic;
  final bool isImageRevealed;
  final VoidCallback? onRevealImage;
  final VoidCallback? onReportImage;

  const _ReceiverMessageBubble({
    required this.message,
    this.isArabic = true,
    this.isImageRevealed = false,
    this.onRevealImage,
    this.onReportImage,
  });

  @override
  Widget build(BuildContext context) {
    final bool hasImage =
        message.attachmentUrl != null && message.attachmentUrl!.isNotEmpty;
    final String cleanText = message.message.trim();
    final bool isDefaultPhotoText = _isDefaultPlaceholderText(cleanText);
    final bool shouldShowText = cleanText.isNotEmpty && !isDefaultPhotoText;

    const borderRadius = BorderRadius.only(
      topLeft: Radius.circular(20),
      topRight: Radius.circular(20),
      bottomLeft: Radius.circular(4),
      bottomRight: Radius.circular(20),
    );

    Widget buildImage() {
      if (_isNetworkUrl(message.attachmentUrl)) {
        return CachedNetworkImage(
          imageUrl: message.attachmentUrl!,
          width: double.infinity,
          fit: BoxFit.cover,
          memCacheWidth: 800,
          placeholder: (context, url) =>
              Container(height: 180, color: Colors.black26),
          errorWidget: (context, url, error) => Container(
            height: 120,
            color: Colors.black26,
            alignment: Alignment.center,
            child: const Icon(Icons.broken_image_rounded,
                color: Colors.white54, size: 30),
          ),
        );
      }
      return Image.file(
        File(message.attachmentUrl!),
        width: double.infinity,
        fit: BoxFit.cover,
        cacheWidth: 800,
        errorBuilder: (context, error, stackTrace) => Container(
          height: 120,
          color: Colors.black26,
          alignment: Alignment.center,
          child: const Icon(Icons.broken_image_rounded,
              color: Colors.white54, size: 30),
        ),
      );
    }

    return Align(
      alignment: Alignment.centerLeft,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              constraints: BoxConstraints(
                  maxWidth: MediaQuery.of(context).size.width * 0.75),
              decoration: BoxDecoration(
                color: AppColors.sectionBackground,
                borderRadius: borderRadius,
                border: Border.all(color: AppColors.lineColor),
              ),
              child: ClipRRect(
                borderRadius: borderRadius,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (hasImage)
                      Stack(
                        alignment: Alignment.center,
                        children: [
                          if (isImageRevealed)
                            buildImage()
                          else
                            ImageFiltered(
                              imageFilter: ImageFilter.blur(
                                sigmaX: 16,
                                sigmaY: 16,
                              ),
                              child: buildImage(),
                            ),
                          if (!isImageRevealed) ...[
                            Positioned.fill(
                              child: Container(
                                color: Colors.black.withValues(alpha: 0.20),
                              ),
                            ),
                            FilledButton.icon(
                              style: FilledButton.styleFrom(
                                backgroundColor: AppColors.primaryTeal,
                                foregroundColor: Colors.white,
                              ),
                              onPressed: onRevealImage,
                              icon: const Icon(Icons.visibility_rounded, size: 18),
                              label: Text(
                                isArabic ? 'اعرض الصورة' : 'Reveal image',
                                style: const TextStyle(fontFamily: 'Cairo'),
                              ),
                            ),
                          ],
                        ],
                      ),
                    if (hasImage && onReportImage != null)
                      Align(
                        alignment: AlignmentDirectional.centerStart,
                        child: TextButton.icon(
                          onPressed: onReportImage,
                          icon: const Icon(
                            Icons.flag_rounded,
                            size: 16,
                            color: AppColors.warning,
                          ),
                          label: Text(
                            isArabic ? 'إبلاغ عن الصورة' : 'Report image',
                            style: const TextStyle(
                              color: AppColors.warning,
                              fontFamily: 'Cairo',
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                    if (shouldShowText)
                      Padding(
                        padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
                        child: Text(
                          cleanText,
                          textDirection: MessageTextDirection.of(cleanText),
                          textAlign: TextAlign.start,
                          style: const TextStyle(
                            color: AppColors.textDark,
                            fontSize: 14.5,
                            fontFamily: 'Cairo',
                            height: 1.35,
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 3),
            Padding(
              padding: const EdgeInsets.only(left: 4, right: 4),
              child: Text(
                _formatMessageTime(message.time, isArabic),
                style: const TextStyle(
                  color: AppColors.textMuted,
                  fontSize: 10.5,
                  fontFamily: 'Cairo',
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StyledGenderAvatar extends StatelessWidget {
  final String imageUrl;
  final String gender;
  final double radius;

  const _StyledGenderAvatar({
    required this.imageUrl,
    required this.gender,
    this.radius = 20,
  });

  @override
  Widget build(BuildContext context) {
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
          gradient: LinearGradient(colors: gradientColors),
          border: Border.all(
              color: Colors.white.withValues(alpha: 0.6), width: 1.5),
        ),
        child: Icon(avatarIcon, color: Colors.white, size: radius * 1.1),
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
          memCacheWidth: 150,
          memCacheHeight: 150,
          placeholder: (context, url) => Container(color: Colors.white12),
          errorWidget: (context, url, error) => Container(
            color: Colors.white24,
            child: Icon(avatarIcon, color: Colors.white, size: radius * 1.1),
          ),
        ),
      ),
    );
  }
}

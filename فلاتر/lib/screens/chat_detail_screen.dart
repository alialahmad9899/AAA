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
import '../constants/colors.dart';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/chat_model.dart';
import '../models/member_profile_model.dart';
import '../services/api_service.dart';
import '../utils/chat_time_utils.dart';
import '../widgets/app_snackbar.dart';
import '../widgets/bottom_system_inset.dart';
import '../widgets/package_badge_widget.dart';
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
  static final Map<int, List<ChatMessageModel>> _globalMessagesCache = {};

  final TextEditingController _messageController = TextEditingController();
  final TextEditingController _captionController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final FocusNode _inputFocusNode = FocusNode();
  final ImagePicker _picker = ImagePicker();

  late int _activeThreadId;
  List<ChatMessageModel> _messages = [];
  ChatRulesModel? _chatRules;
  bool _isLoading = false;
  bool _hasMessageLoadError = false;
  String _messageLoadError = '';
  bool _isSending = false;
  bool _isLoadingMore = false;
  bool _isFetchingMessages = false;
  bool _hasMoreOldMessages = true;
  Timer? _messageRefreshTimer;
  bool _showEmojiPicker = false;
  int _selectedPickerTab = 0;
  File? _selectedImageFile;

  bool _hasSentMessage = false;
  bool _canPopNow = false;

  bool get _isArabic => widget.currentLanguage == 'ar';

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
    WidgetsBinding.instance.addObserver(this);
    _scrollController.addListener(_scrollListener);
    _startMessageRefreshTimer();

    _inputFocusNode.addListener(_onFocusChange);

    if (_globalMessagesCache.containsKey(_activeThreadId)) {
      _messages = List.from(_globalMessagesCache[_activeThreadId]!);
      _isLoading = false;
    } else {
      _isLoading = true;
    }

    _loadMessages(
      silent: _messages.isNotEmpty,
      preserveHistory: _messages.isNotEmpty,
    );

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkAndShowMaleWarningDialog();
    });
  }

  void _onFocusChange() {
    if (mounted && _inputFocusNode.hasFocus && _showEmojiPicker) {
      setState(() {
        _showEmojiPicker = false;
      });
    }
  }

  @override
  void dispose() {
    _messageRefreshTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    _scrollController.removeListener(_scrollListener);
    _inputFocusNode.removeListener(_onFocusChange);
    _messageController.dispose();
    _captionController.dispose();
    _scrollController.dispose();
    _inputFocusNode.dispose();
    super.dispose();
  }

  void _startMessageRefreshTimer() {
    _messageRefreshTimer?.cancel();
    if (_activeThreadId <= 0) return;
    _messageRefreshTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      if (!mounted || WidgetsBinding.instance.lifecycleState != AppLifecycleState.resumed ||
          _isSending || _isLoadingMore) return;
      _loadMessages(silent: true, preserveHistory: true);
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _loadMessages(silent: true, preserveHistory: true);
      _startMessageRefreshTimer();
    } else {
      _messageRefreshTimer?.cancel();
      _messageRefreshTimer = null;
    }
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
    if (_scrollController.hasClients &&
        _scrollController.position.pixels <= 30 &&
        !_isLoadingMore &&
        _hasMoreOldMessages &&
        _messages.isNotEmpty) {
      _loadOldMessages();
    }
  }

  List<ChatMessageModel> _mergeMessageHistory(
      List<ChatMessageModel> existing, List<ChatMessageModel> incoming) {
    final byId = <int, ChatMessageModel>{};
    for (final message in existing) {
      if (message.id > 0) byId[message.id] = message;
    }
    for (final message in incoming) {
      if (message.id > 0) byId[message.id] = message;
    }
    final merged = byId.values.toList()
      ..sort((a, b) => a.id.compareTo(b.id));
    return merged;
  }

  Future<void> _loadMessages({
    bool silent = false,
    bool preserveHistory = false,
  }) async {
    if (!mounted || _isFetchingMessages || _activeThreadId <= 0) return;
    _isFetchingMessages = true;
    final requestedThreadId = _activeThreadId;
    final int previousMessageCount = _messages.length;
    final bool wasNearBottom = _scrollController.hasClients &&
        (_scrollController.position.maxScrollExtent -
                _scrollController.position.pixels <=
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

      final List<ChatMessageModel> messages = res['messages'] ?? [];
      final ChatRulesModel? rules = res['chat_rules'];
      final displayedMessages = preserveHistory
          ? _mergeMessageHistory(_messages, messages)
          : messages;
      _globalMessagesCache[_activeThreadId] = List.from(displayedMessages);
      setState(() {
        _messages = displayedMessages;
        _chatRules = rules;
        _hasMoreOldMessages = res['has_more_old_messages'] == true;
        _isLoading = false;
        _hasMessageLoadError = false;
        _messageLoadError = '';
      });
      if (!silent ||
          (preserveHistory && wasNearBottom &&
              displayedMessages.length > previousMessageCount)) {
        _scrollToBottom();
      }
    } catch (e) {
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
    }
  }

  Future<void> _loadOldMessages() async {
    if (_messages.isEmpty || _isLoadingMore || !_hasMoreOldMessages) return;
    final previousPixels = _scrollController.hasClients
        ? _scrollController.position.pixels : 0.0;
    final previousMax = _scrollController.hasClients
        ? _scrollController.position.maxScrollExtent : 0.0;
    setState(() => _isLoadingMore = true);
    try {
      final result = await ApiService.getOldMessages(
        _messages.first.id,
        widget.thread.userId,
        lang: widget.currentLanguage,
      );
      final List<ChatMessageModel> oldMsgs =
          result['messages'] is List ? result['messages'] : <ChatMessageModel>[];
      if (mounted) {
        setState(() {
          _isLoadingMore = false;
          if (result['success'] == true) {
            if (oldMsgs.isNotEmpty) {
              _messages.insertAll(0, oldMsgs);
              _globalMessagesCache[_activeThreadId] = List.from(_messages);
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
        } else if (oldMsgs.isNotEmpty) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (!_scrollController.hasClients) return;
            final addedExtent = _scrollController.position.maxScrollExtent - previousMax;
            final target = (previousPixels + addedExtent)
                .clamp(0.0, _scrollController.position.maxScrollExtent);
            _scrollController.jumpTo(target);
          });
        }
      }
    } catch (e) {
      if (mounted) setState(() => _isLoadingMore = false);
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
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

    if (customText == null) _messageController.clear();

    final tempMsg = ChatMessageModel(
      id: DateTime.now().millisecondsSinceEpoch,
      senderId: 0,
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
      _globalMessagesCache[_activeThreadId] = List.from(_messages);
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
      setState(() => _isSending = false);

      if (result['success'] == true) {
        int? newThreadId;
        if (result['data'] != null &&
            result['data']['chat_thread_id'] != null) {
          newThreadId =
              int.tryParse(result['data']['chat_thread_id'].toString());
        } else if (result['chat_thread_id'] != null) {
          newThreadId = int.tryParse(result['chat_thread_id'].toString());
        }

        if (newThreadId != null && newThreadId > 0) {
          _activeThreadId = newThreadId;
          _startMessageRefreshTimer();
        }
        _messages.removeWhere((message) => message.id == tempMsg.id);
        _globalMessagesCache[_activeThreadId] = List.from(_messages);
        _loadMessages(silent: true);
      } else {
        setState(() {
          _messages.removeWhere((m) => m.id == tempMsg.id);
          _globalMessagesCache[_activeThreadId] = List.from(_messages);
        });

        final String status = result['status'] ?? '';
        final String serverErrorMessage = result['message'] ??
            (_isArabic ? 'تعذر إرسال الرسالة' : 'Failed to send message');

        if (status == 'subscription_expired' ||
            status == 'package_required' ||
            status == 'forbidden') {
          _showSubscriptionDialog(serverErrorMessage);
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
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isSending = false;
        _messages.removeWhere((m) => m.id == tempMsg.id);
        _globalMessagesCache[_activeThreadId] = List.from(_messages);
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
                            _confirmDeleteChat();
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

  void _showReportReasonsDialog() {
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
                                _executeReport(reason);
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
                            ? 'سيتم حظر العضو وحذف المحادثة فوراً ولن تظهر في قائمتك مجدداً.'
                            : 'Member will be blocked and chat removed permanently.',
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
        await ApiService.deleteChatThread(_activeThreadId);
        _globalMessagesCache.remove(_activeThreadId);
        if (!mounted) return;
        Navigator.pop(context, true);

        AppSnackBar.show(
          context: context,
          type: NotificationType.warning,
          isArabic: _isArabic,
          messageAr: 'تم حظر المستخدم وحذف المحادثة بنجاح',
          messageEn: 'User blocked and chat deleted successfully',
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

  Future<void> _executeReport(String reason) async {
    _dismissKeyboard();
    try {
      final result =
          await ApiService.reportMember(widget.thread.userId, reason);
      if (!mounted) return;

      if (result['success'] == true) {
        await ApiService.deleteChatThread(_activeThreadId);
        _globalMessagesCache.remove(_activeThreadId);
        if (!mounted) return;
        Navigator.pop(context, true);

        AppSnackBar.show(
          context: context,
          type: NotificationType.success,
          isArabic: _isArabic,
          messageAr: 'تم إرسال البلاغ وحذف المحادثة بنجاح',
          messageEn: 'Report submitted and chat deleted successfully',
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

  Future<void> _confirmDeleteChat() async {
    _dismissKeyboard();
    try {
      final result = await ApiService.deleteChatThread(_activeThreadId);
      if (!mounted) return;
      if (result['success'] == true) {
        _globalMessagesCache.remove(_activeThreadId);
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
                _buildRulesBanner(),
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
                        : _messages.isEmpty
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
                            : ListView.builder(
                                controller: _scrollController,
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 14,
                                  vertical: 12,
                                ),
                                itemCount: _messages.length,
                                itemBuilder: (context, index) {
                                  final msg = _messages[index];
                                  return RepaintBoundary(
                                    key: ValueKey(msg.id),
                                    child: msg.isMe
                                        ? _SenderMessageBubble(
                                            message: msg,
                                            isArabic: _isArabic,
                                          )
                                        : _ReceiverMessageBubble(
                                            message: msg,
                                            isArabic: _isArabic,
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

  /// رأس المحادثة ببطاقة فاتحة مع بقاء ملف العضو وقائمة الخيارات قابلين للفتح.
  Widget _buildHeader() {
    final String presenceText = widget.thread.isOnline
        ? (_isArabic ? 'متصل الآن' : 'Online')
        : _translateLastSeen(widget.thread.lastSeen);

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
                        if (widget.thread.isOnline)
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
                              if (widget.thread.packageName.isNotEmpty) ...[
                                const SizedBox(width: 5),
                                PackageBadgeWidget(
                                  packageName: widget.thread.packageName,
                                  compact: true,
                                  isArabic: _isArabic,
                                ),
                              ],
                            ],
                          ),
                          const SizedBox(height: 2),
                          Row(
                            children: [
                              if (widget.thread.isOnline)
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
                                    color: widget.thread.isOnline
                                        ? AppColors.success
                                        : AppColors.textMuted,
                                    fontSize: 10.5,
                                    fontFamily: 'Cairo',
                                    fontWeight: widget.thread.isOnline
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
    return Container(
      padding: const EdgeInsets.fromLTRB(8, 9, 8, 10),
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
        children: [
          IconButton(
            tooltip: _isArabic ? 'إرفاق صورة' : 'Attach image',
            icon: const Icon(
              Icons.add_a_photo_rounded,
              color: AppColors.primaryTeal,
              size: 21,
            ),
            onPressed: () => _pickImage(ImageSource.gallery),
          ),
          IconButton(
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
          Expanded(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14),
              decoration: BoxDecoration(
                color: AppColors.sectionBackground,
                borderRadius: BorderRadius.circular(22),
                border: Border.all(color: AppColors.lineColor),
              ),
              child: TextField(
                controller: _messageController,
                focusNode: _inputFocusNode,
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
                  border: InputBorder.none,
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
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
                        child: Text(cleanText,
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

  const _ReceiverMessageBubble({
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
      bottomLeft: Radius.circular(4),
      bottomRight: Radius.circular(20),
    );

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
                        child: Text(cleanText,
                            style: const TextStyle(
                                color: AppColors.textDark,
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
              padding: const EdgeInsets.only(left: 4, right: 4),
              child: Text(_formatMessageTime(message.time, isArabic),
                  style: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 10.5,
                      fontFamily: 'Cairo')),
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

// ============================================================================
// اسم الملف: lib/screens/support_tickets_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة إدارة تذاكر الدعم الفني وتوليد التذاكر الجديدة مع الربط
//        بمكوّن AppSnackBar والتنقل المرن.
// ============================================================================
import 'package:flutter/material.dart';
import '../constants/colors.dart';
import 'package:wesal_app/widgets/bottom_system_inset.dart';
import '../models/support_ticket_model.dart';
import '../services/api_service.dart';
import '../widgets/app_snackbar.dart';
import 'support_ticket_detail_screen.dart';

class SupportTicketsScreen extends StatefulWidget {
  final String currentLanguage;

  const SupportTicketsScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<SupportTicketsScreen> createState() => _SupportTicketsScreenState();
}

class _SupportTicketsScreenState extends State<SupportTicketsScreen> {
  List<SupportTicketModel> _tickets = [];
  bool _isLoading = true;

  bool get _isAr => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _fetchTickets();
  }

  Future<void> _fetchTickets() async {
    setState(() => _isLoading = true);
    final tickets =
        await ApiService.getMySupportTickets(lang: widget.currentLanguage);
    if (mounted) {
      setState(() {
        _tickets = tickets;
        _isLoading = false;
      });
    }
  }

  void _showCreateTicketBottomSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _CreateTicketModal(
        isArabic: _isAr,
        currentLanguage: widget.currentLanguage,
        onSuccess: () {
          Navigator.pop(context);
          _fetchTickets();
          AppSnackBar.show(
            context: context,
            type: NotificationType.success,
            isArabic: _isAr,
            messageAr: 'تم إرسال تذكرة الدعم بنجاح وسيجيبك الفريق قريباً',
            messageEn: 'Support ticket submitted successfully',
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
      child: Scaffold(
        backgroundColor: AppColors.backgroundColor,
        appBar: AppBar(
          backgroundColor: AppColors.cardColor,
          elevation: 0,
          leading: IconButton(
            icon: Icon(
              _isAr
                  ? Icons.arrow_back_ios_new_rounded
                  : Icons.arrow_back_ios_rounded,
              color: AppColors.textDark,
              size: 18,
            ),
            onPressed: () => Navigator.pop(context),
          ),
          title: Text(
            _isAr ? 'الدعم الفني والخدمات' : 'Support Tickets',
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 18,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _showCreateTicketBottomSheet,
          backgroundColor: AppColors.primaryTeal,
          icon: const Icon(Icons.add_rounded, color: Colors.white),
          label: Text(
            _isAr ? 'تذكرة جديدة' : 'New Ticket',
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
        ),
        body: SafeArea(top: false, bottom: true, child: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [AppColors.backgroundColor, AppColors.backgroundColor, AppColors.backgroundColor],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
          ),
          child: _isLoading
              ? const Center(
                  child: CircularProgressIndicator(color: AppColors.primaryTeal))
              : _tickets.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.headset_mic_outlined,
                              size: 70,
                              color: AppColors.textDark.withValues(alpha: 0.3)),
                          const SizedBox(height: 16),
                          Text(
                            _isAr
                                ? 'لا توجد تذاكر دعم حالياً'
                                : 'No active support tickets',
                            style: TextStyle(
                              color: AppColors.textDark.withValues(alpha: 0.7),
                              fontSize: 16,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          const SizedBox(height: 16),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primaryTeal,
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 24, vertical: 12),
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16)),
                            ),
                            icon: const Icon(Icons.add_rounded,
                                color: Colors.white),
                            onPressed: _showCreateTicketBottomSheet,
                            label: Text(
                              _isAr ? 'افتح تذكرة الآن' : 'Open Ticket Now',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontFamily: 'Cairo',
                                  fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _tickets.length,
                      itemBuilder: (context, index) {
                        final ticket = _tickets[index];
                        return _buildTicketCard(ticket);
                      },
                    ),
        )),
      ),
    );
  }

  Widget _buildTicketCard(SupportTicketModel ticket) {
    final bool isClosed = ticket.status != '0';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.cardColor.withValues(alpha: 0.6),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.textDark.withValues(alpha: 0.12)),
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(20),
        child: InkWell(
          onTap: () async {
            await Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => SupportTicketDetailScreen(
                  ticketId: ticket.id,
                  currentLanguage: widget.currentLanguage,
                ),
              ),
            );
            _fetchTickets();
          },
          borderRadius: BorderRadius.circular(20),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.primaryTeal.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '#${ticket.ticketId}',
                        style: const TextStyle(
                          color: AppColors.primaryTeal,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: isClosed
                            ? AppColors.success.withValues(alpha: 0.2)
                            : AppColors.warning.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        isClosed
                            ? (_isAr ? 'تم الرد (مغلقة)' : 'Closed')
                            : (_isAr ? 'قيد المتابعة' : 'Active'),
                        style: TextStyle(
                          color: AppColors.textDark,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Cairo',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  ticket.subject,
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  ticket.description,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: AppColors.textDark.withValues(alpha: 0.65),
                    fontSize: 13,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// ودجت حوار إنشاء التذكرة المصمم بأسلوب 2026 مع القائمة المنسدلة المترجمة
class _CreateTicketModal extends StatefulWidget {
  final bool isArabic;
  final String currentLanguage;
  final VoidCallback onSuccess;

  const _CreateTicketModal({
    required this.isArabic,
    required this.currentLanguage,
    required this.onSuccess,
  });

  @override
  State<_CreateTicketModal> createState() => _CreateTicketModalState();
}

class _CreateTicketModalState extends State<_CreateTicketModal> {
  final _descriptionController = TextEditingController();

  late final List<Map<String, dynamic>> _categories;
  late String _selectedSubject;
  late int _selectedCategoryId;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _categories = widget.isArabic
        ? [
            {'id': 1, 'title': 'استفسار عام'},
            {'id': 2, 'title': 'مشكلة تقنية في التطبيق'},
            {'id': 3, 'title': 'استفسار عن الحساب والعضوية'},
            {'id': 4, 'title': 'مشكلة في الدفع أو المحفظة'},
            {'id': 5, 'title': 'بلاغ أو شكوى'},
          ]
        : [
            {'id': 1, 'title': 'General Inquiry'},
            {'id': 2, 'title': 'Technical Issue'},
            {'id': 3, 'title': 'Account & Membership Inquiry'},
            {'id': 4, 'title': 'Payment or Wallet Issue'},
            {'id': 5, 'title': 'Report or Complaint'},
          ];

    _selectedSubject = _categories.first['title'];
    _selectedCategoryId = _categories.first['id'];
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    super.dispose();
  }

  Future<void> _submitTicket() async {
    final String description = _descriptionController.text.trim();

    if (description.isEmpty) {
      AppSnackBar.show(
        context: context,
        type: NotificationType.warning,
        isArabic: widget.isArabic,
        messageAr: 'يرجى كتابة شرح المشكلة أو الاستفسار',
        messageEn: 'Please fill problem description',
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final res = await ApiService.createSupportTicket(
      subject: _selectedSubject,
      categoryId: _selectedCategoryId,
      description: description,
      lang: widget.currentLanguage,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (res['success'] == true) {
        widget.onSuccess();
      } else {
        AppSnackBar.show(
          context: context,
          type: NotificationType.error,
          isArabic: widget.isArabic,
          messageAr: res['message'] ?? 'تعذر إرسال التذكرة، حاول لاحقاً',
          messageEn: res['message'] ?? 'Failed to submit ticket',
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: widget.isArabic ? TextDirection.rtl : TextDirection.ltr,
      child: Padding(
        padding:
            EdgeInsets.only(bottom: WesalBottomInsets.of(context)),
        child: Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            color: AppColors.cardColor.withValues(alpha: 0.98),
            borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
            border: Border.all(color: AppColors.textDark.withValues(alpha: 0.2)),
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: AppColors.lineColor,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                Text(
                  widget.isArabic
                      ? 'فتح تذكرة دعم جديدة'
                      : 'Create Support Ticket',
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 18),

                // 1. القائمة المنسدلة لاختيار قسم/موضوع التذكرة
                Text(
                  widget.isArabic ? 'اختر موضوع التذكرة' : 'Select Subject',
                  style: const TextStyle(
                    color: AppColors.primaryTeal,
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: AppColors.textDark.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(16),
                    border:
                        Border.all(color: AppColors.lineColor),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: _selectedSubject,
                      dropdownColor: AppColors.cardColor,
                      isExpanded: true,
                      icon: const Icon(Icons.keyboard_arrow_down_rounded,
                          color: AppColors.primaryTeal),
                      style: const TextStyle(
                          color: AppColors.textDark,
                          fontFamily: 'Cairo',
                          fontSize: 14),
                      items: _categories.map((cat) {
                        return DropdownMenuItem<String>(
                          value: cat['title'] as String,
                          child: Text(cat['title'] as String),
                          onTap: () {
                            _selectedCategoryId = cat['id'] as int;
                          },
                        );
                      }).toList(),
                      onChanged: (val) {
                        if (val != null) {
                          setState(() {
                            _selectedSubject = val;
                          });
                        }
                      },
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // 2. حقل شرح المشكلة بالتفصيل
                Text(
                  widget.isArabic ? 'شرح المشكلة بالتفصيل' : 'Description',
                  style: const TextStyle(
                    color: AppColors.primaryTeal,
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: _descriptionController,
                  maxLines: 5,
                  style:
                      const TextStyle(color: AppColors.textDark, fontFamily: 'Cairo'),
                  decoration: InputDecoration(
                    hintText: widget.isArabic
                        ? 'اكتب تفاصيل استفسارك أو المشكلة التي تواجهها...'
                        : 'Enter problem details...',
                    hintStyle: TextStyle(
                        color: AppColors.textDark.withValues(alpha: 0.35),
                        fontFamily: 'Cairo',
                        fontSize: 13),
                    filled: true,
                    fillColor: AppColors.textDark.withValues(alpha: 0.08),
                    contentPadding: const EdgeInsets.all(16),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide(
                          color: AppColors.textDark.withValues(alpha: 0.15)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: const BorderSide(
                          color: AppColors.primaryTeal, width: 1.5),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // زر إرسال التذكرة
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primaryTeal,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16)),
                      elevation: 4,
                    ),
                    onPressed: _isSubmitting ? null : _submitTicket,
                    child: _isSubmitting
                        ? const CircularProgressIndicator(
                            color: Colors.white, strokeWidth: 2.5)
                        : Text(
                            widget.isArabic ? 'إرسال التذكرة' : 'Submit Ticket',
                            style: const TextStyle(
                              color: AppColors.textDark,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Cairo',
                            ),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}


class BlockedSupportMessageScreen extends StatefulWidget {
  final String currentLanguage;
  const BlockedSupportMessageScreen({super.key, this.currentLanguage = 'ar'});
  @override
  State<BlockedSupportMessageScreen> createState() => _BlockedSupportMessageScreenState();
}

class _BlockedSupportMessageScreenState extends State<BlockedSupportMessageScreen> {
  final TextEditingController _messageController = TextEditingController();
  bool _isSending = false;
  bool get _isArabic => widget.currentLanguage.toLowerCase().startsWith('ar');

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _sendMessage() async {
    final message = _messageController.text.trim();
    if (message.isEmpty || _isSending) {
      AppSnackBar.show(context: context, type: NotificationType.warning, isArabic: _isArabic, messageAr: 'يرجى كتابة تفاصيل طلب المراجعة أولاً.', messageEn: 'Please enter your appeal message first.');
      return;
    }
    setState(() => _isSending = true);
    final result = await ApiService.sendBlockedSupportMessage(message: message, lang: widget.currentLanguage);
    if (!mounted) return;
    setState(() => _isSending = false);
    if (result['success'] == true) {
      _messageController.clear();
      AppSnackBar.show(context: context, type: NotificationType.success, isArabic: _isArabic, messageAr: 'وصلت رسالتك إلى فريق الدعم الفني.', messageEn: 'Your message has been sent to support.');
    } else {
      AppSnackBar.show(context: context, type: NotificationType.error, isArabic: _isArabic, messageAr: result['message']?.toString() ?? 'تعذر إرسال الرسالة.', messageEn: result['message']?.toString() ?? 'Could not send your message.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
      child: Scaffold(
        backgroundColor: AppColors.backgroundColor,
        appBar: AppBar(
          backgroundColor: AppColors.cardColor,
          elevation: 0,
          leading: IconButton(onPressed: () => Navigator.of(context).pop(), icon: Icon(_isArabic ? Icons.arrow_back_ios_new_rounded : Icons.arrow_back_ios_rounded, color: AppColors.textDark, size: 18)),
          title: Text(_isArabic ? 'مراسلة الدعم الفني' : 'Contact Support', style: const TextStyle(color: AppColors.textDark, fontSize: 17, fontWeight: FontWeight.bold, fontFamily: 'Cairo')),
        ),
        body: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(_isArabic ? 'اكتب اعتراضك أو وضّح ما تريد مراجعته بخصوص قرار الحظر. ستصل رسالتك مباشرة إلى فريق الدعم.' : 'Describe your appeal or explain what you would like the support team to review about this account restriction.', textAlign: TextAlign.center, style: const TextStyle(color: AppColors.textDark, fontSize: 14, height: 1.6, fontFamily: 'Cairo')),
                const SizedBox(height: 18),
                TextField(
                  controller: _messageController,
                  minLines: 6,
                  maxLines: 10,
                  maxLength: 10000,
                  textAlign: _isArabic ? TextAlign.right : TextAlign.left,
                  style: const TextStyle(color: AppColors.textDark, fontSize: 14, fontFamily: 'Cairo'),
                  decoration: InputDecoration(
                    hintText: _isArabic ? 'اكتب تفاصيل رسالتك هنا...' : 'Write your message here...',
                    hintStyle: const TextStyle(color: AppColors.textMuted, fontFamily: 'Cairo'),
                    filled: true,
                    fillColor: AppColors.cardColor,
                    contentPadding: const EdgeInsets.all(14),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: AppColors.lineColor)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: AppColors.primaryTeal, width: 1.3)),
                  ),
                ),
                const SizedBox(height: 18),
                SizedBox(
                  height: 52,
                  child: ElevatedButton.icon(
                    onPressed: _isSending ? null : _sendMessage,
                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.primaryTeal, foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15))),
                    icon: _isSending ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Icon(Icons.send_rounded, color: Colors.white),
                    label: Text(_isArabic ? 'إرسال رسالة للدعم' : 'Send a message to support', style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold, fontFamily: 'Cairo')),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

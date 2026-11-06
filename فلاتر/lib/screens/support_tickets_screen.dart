// ============================================================================
// اسم الملف: lib/screens/support_tickets_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة إدارة تذاكر الدعم الفني وتوليد التذاكر الجديدة مع الربط
//        بمكوّن AppSnackBar والتنقل المرن.
// ============================================================================

import 'package:wesal_app/widgets/bottom_system_inset.dart';
import 'package:flutter/material.dart';
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
        backgroundColor: const Color(0xFF090D16),
        appBar: AppBar(
          backgroundColor: const Color(0xFF0F172A),
          elevation: 0,
          leading: IconButton(
            icon: Icon(
              _isAr
                  ? Icons.arrow_back_ios_new_rounded
                  : Icons.arrow_back_ios_rounded,
              color: Colors.white,
              size: 18,
            ),
            onPressed: () => Navigator.pop(context),
          ),
          title: Text(
            _isAr ? 'الدعم الفني والخدمات' : 'Support Tickets',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _showCreateTicketBottomSheet,
          backgroundColor: const Color(0xFF0288D1),
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
        body: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [Color(0xFF081827), Color(0xFF0D233A), Color(0xFF090D16)],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
          ),
          child: _isLoading
              ? const Center(
                  child: CircularProgressIndicator(color: Color(0xFF38BDF8)))
              : _tickets.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.headset_mic_outlined,
                              size: 70,
                              color: Colors.white.withValues(alpha: 0.3)),
                          const SizedBox(height: 16),
                          Text(
                            _isAr
                                ? 'لا توجد تذاكر دعم حالياً'
                                : 'No active support tickets',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.7),
                              fontSize: 16,
                              fontFamily: 'Cairo',
                            ),
                          ),
                          const SizedBox(height: 16),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF0288D1),
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
        ),
      ),
    );
  }

  Widget _buildTicketCard(SupportTicketModel ticket) {
    final bool isClosed = ticket.status != '0';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B).withValues(alpha: 0.6),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
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
                        color: const Color(0xFF38BDF8).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '#${ticket.ticketId}',
                        style: const TextStyle(
                          color: Color(0xFF38BDF8),
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
                            ? Colors.green.withValues(alpha: 0.2)
                            : Colors.amber.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        isClosed
                            ? (_isAr ? 'تم الرد (مغلقة)' : 'Closed')
                            : (_isAr ? 'قيد المتابعة' : 'Active'),
                        style: TextStyle(
                          color: isClosed ? Colors.greenAccent : Colors.amber,
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
                    color: Colors.white,
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
                    color: Colors.white.withValues(alpha: 0.65),
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
            color: const Color(0xFF0F172A).withValues(alpha: 0.98),
            borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
            border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
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
                      color: Colors.white24,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                Text(
                  widget.isArabic
                      ? 'فتح تذكرة دعم جديدة'
                      : 'Create Support Ticket',
                  style: const TextStyle(
                    color: Colors.white,
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
                    color: Color(0xFF38BDF8),
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(16),
                    border:
                        Border.all(color: Colors.white.withValues(alpha: 0.15)),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: _selectedSubject,
                      dropdownColor: const Color(0xFF0F172A),
                      isExpanded: true,
                      icon: const Icon(Icons.keyboard_arrow_down_rounded,
                          color: Color(0xFF38BDF8)),
                      style: const TextStyle(
                          color: Colors.white,
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
                    color: Color(0xFF38BDF8),
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
                      const TextStyle(color: Colors.white, fontFamily: 'Cairo'),
                  decoration: InputDecoration(
                    hintText: widget.isArabic
                        ? 'اكتب تفاصيل استفسارك أو المشكلة التي تواجهها...'
                        : 'Enter problem details...',
                    hintStyle: TextStyle(
                        color: Colors.white.withValues(alpha: 0.35),
                        fontFamily: 'Cairo',
                        fontSize: 13),
                    filled: true,
                    fillColor: Colors.white.withValues(alpha: 0.08),
                    contentPadding: const EdgeInsets.all(16),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide(
                          color: Colors.white.withValues(alpha: 0.15)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: const BorderSide(
                          color: Color(0xFF38BDF8), width: 1.5),
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
                      backgroundColor: const Color(0xFF0288D1),
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
                              color: Colors.white,
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

// ============================================================================
// اسم الملف: lib/screens/support_ticket_detail_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: شاشة تفاصيل التذكرة وعرض ردود الإدارة بأسلوب فاخر مع دعم كامل
//        للتدويل المزدوج (عربي/إنجليزي) وضبط الاتجاه الديناميكي.
// ============================================================================

import 'package:flutter/material.dart';
import '../constants/colors.dart';
import '../models/support_ticket_model.dart';
import '../services/api_service.dart';

class SupportTicketDetailScreen extends StatefulWidget {
  final int ticketId;
  final String currentLanguage;

  const SupportTicketDetailScreen({
    super.key,
    required this.ticketId,
    this.currentLanguage = 'ar',
  });

  @override
  State<SupportTicketDetailScreen> createState() =>
      _SupportTicketDetailScreenState();
}

class _SupportTicketDetailScreenState extends State<SupportTicketDetailScreen> {
  SupportTicketModel? _ticket;
  bool _isLoading = true;

  bool get _isAr => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _loadTicketDetails();
  }

  /// جلب تفاصيل التذكرة والردود من السيرفر
  Future<void> _loadTicketDetails() async {
    setState(() => _isLoading = true);
    final ticket = await ApiService.getSupportTicketDetails(
      widget.ticketId,
      lang: widget.currentLanguage,
    );
    if (mounted) {
      setState(() {
        _ticket = ticket;
        _isLoading = false;
      });
    }
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
            _isAr ? 'تفاصيل التذكرة' : 'Ticket Details',
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 18,
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
              : _ticket == null
                  ? Center(
                      child: Text(
                        _isAr
                            ? 'تعذر تحميل بيانات التذكرة'
                            : 'Failed to load ticket details',
                        style: const TextStyle(
                            color: AppColors.textMuted, fontFamily: 'Cairo'),
                      ),
                    )
                  : SingleChildScrollView(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // 1. بطاقة معلومات التذكرة الرئيسية
                          _buildMainTicketCard(_ticket!),

                          const SizedBox(height: 20),

                          // 2. عنوان ردود الدعم الفني
                          Row(
                            children: [
                              const Icon(Icons.forum_rounded,
                                  color: AppColors.primaryTeal, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                _isAr
                                    ? 'ردود فريق الدعم الفني'
                                    : 'Support Team Replies',
                                style: const TextStyle(
                                  color: AppColors.textDark,
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'Cairo',
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),

                          // 3. قائمة الردود من الإدارة
                          if (_ticket!.replies.isEmpty)
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: AppColors.cardColor
                                    .withValues(alpha: 0.5),
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(
                                    color:
                                        AppColors.textDark.withValues(alpha: 0.08)),
                              ),
                              child: Column(
                                children: [
                                  Icon(Icons.hourglass_empty_rounded,
                                      color:
                                          AppColors.warning.withValues(alpha: 0.7),
                                      size: 36),
                                  const SizedBox(height: 10),
                                  Text(
                                    _isAr
                                        ? 'التذكرة قيد الدراسة والمتابعة من قبل الإدارة، سيصلك الرد هنا قريباً.'
                                        : 'Your ticket is under review. Reply will appear here soon.',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      color:
                                          AppColors.textDark.withValues(alpha: 0.7),
                                      fontSize: 13,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                                ],
                              ),
                            )
                          else
                            ListView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: _ticket!.replies.length,
                              itemBuilder: (context, index) {
                                final reply = _ticket!.replies[index];
                                return _buildReplyCard(reply);
                              },
                            ),
                        ],
                      ),
                    ),
        )),
      ),
    );
  }

  /// ودجت البطاقة الرئيسية للتذكرة
  Widget _buildMainTicketCard(SupportTicketModel ticket) {
    final bool isClosed = ticket.status != '0';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.cardColor.withValues(alpha: 0.85),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.textDark.withValues(alpha: 0.15)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: AppColors.primaryTeal.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                      color: AppColors.primaryTeal.withValues(alpha: 0.3)),
                ),
                child: Text(
                  '#${ticket.ticketId}',
                  style: const TextStyle(
                    color: AppColors.primaryTeal,
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: isClosed
                      ? AppColors.success.withValues(alpha: 0.2)
                      : AppColors.warning.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                      color: isClosed
                          ? AppColors.success.withValues(alpha: 0.4)
                          : AppColors.warning.withValues(alpha: 0.4)),
                ),
                child: Text(
                  isClosed
                      ? (_isAr ? 'تم الرد (مغلقة)' : 'Closed')
                      : (_isAr ? 'قيد المتابعة' : 'In Progress'),
                  style: TextStyle(
                    color: isClosed ? AppColors.success : AppColors.warning,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            ticket.subject,
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 18,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: AppColors.textDark.withValues(alpha: 0.06),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              _isAr
                  ? 'القسم: ${ticket.categoryName}'
                  : 'Category: ${ticket.categoryName}',
              style: TextStyle(
                color: AppColors.textDark.withValues(alpha: 0.6),
                fontSize: 12,
                fontFamily: 'Cairo',
              ),
            ),
          ),
          const Divider(height: 24, color: AppColors.lineColor),
          Text(
            _isAr ? 'تفاصيل الاستفسار:' : 'Description:',
            style: const TextStyle(
              color: AppColors.primaryTeal,
              fontSize: 12,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
            ),
          ),
          const SizedBox(height: 6),
          Text(
            ticket.description,
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 14,
              height: 1.5,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }

  /// ودجت عرض رد الإدارة بأسلوب راقٍ
  Widget _buildReplyCard(SupportReplyModel reply) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.cardColor.withValues(alpha: 0.8),
        borderRadius: BorderRadius.circular(20),
        border:
            Border.all(color: AppColors.primaryTeal.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const CircleAvatar(
                radius: 18,
                backgroundColor: AppColors.primaryTeal,
                child: Icon(Icons.support_agent_rounded,
                    color: Colors.white, size: 20),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      reply.repliedUserName,
                      style: const TextStyle(
                        color: AppColors.textDark,
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
                    Text(
                      reply.createdAt,
                      style: TextStyle(
                        color: AppColors.textDark.withValues(alpha: 0.45),
                        fontSize: 11,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: AppColors.success.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  _isAr ? 'إجابة الإدارة' : 'Admin Reply',
                  style: const TextStyle(
                    color: AppColors.success,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
              ),
            ],
          ),
          const Divider(height: 20, color: AppColors.lineColor),
          Text(
            reply.reply,
            style: const TextStyle(
              color: AppColors.textDark,
              fontSize: 14,
              height: 1.5,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
    );
  }
}

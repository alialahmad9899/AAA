// ============================================================================
// اسم الملف: lib/widgets/sender_message_bubble.dart
// المشروع: تطبيق وصال (Wesal)
// الوصف: ويدجت مستقلة ومحسّنة لرسائل المُرسِل (الطرف الأول) بأداء عالي
// ============================================================================

import 'package:flutter/material.dart';
import '../models/chat_model.dart';
import '../constants/colors.dart';

/// ويدجت مستقلة لرسائل المُرسِل (الطرف الأول)
class SenderMessageBubble extends StatelessWidget {
  /// نموذج البيانات المحتوي على نص الرسالة ووقت الإرسال
  final ChatMessageModel message;

  const SenderMessageBubble({
    super.key,
    required this.message,
  });

  @override
  Widget build(BuildContext context) {
    // 💡 تحسين الأداء: استخدام sizeOf لتجنب إعادة بناء الويدجت عند فتح/إغلاق لوحة المفاتيح
    final double screenWidth = MediaQuery.sizeOf(context).width;

    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        constraints: BoxConstraints(
          // أقصى عرض للفقاعة هو 75% من عرض الشاشة
          maxWidth: screenWidth * 0.75,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: AppColors.primaryTeal,
          borderRadius: const BorderRadius.only(
            topLeft: Radius.circular(18),
            topRight: Radius.circular(18),
            bottomLeft: Radius.circular(2),
            bottomRight: Radius.circular(18),
          ),
          border: Border.all(
            color: Colors.white.withValues(alpha: 0.3),
            width: 1,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min, // ضغط العمود عمودياً بحجم المحتوى
          children: [
            // نص الرسالة الرئيسية
            Text(
              message.message,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 15,
                fontFamily: 'Cairo',
                height: 1.3,
              ),
            ),
            const SizedBox(height: 4),
            // وقت إرسال الرسالة
            Text(
              message.time,
              style: const TextStyle(
                color: Colors.white.withValues(alpha: 0.86),
                fontSize: 10,
                fontFamily: 'Cairo',
              ),
            ),
          ],
        ),
      ),
    );
  }
}

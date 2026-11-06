// ============================================================================
// اسم الملف: lib/widgets/receiver_message_bubble.dart
// المشروع: تطبيق وصال (Wesal)
// الوصف: ويدجت مستقل ومحسن لعرض رسائل المستقبِل بتصميم زجاجي هادئ وأداء عالي
// ============================================================================

import 'package:flutter/material.dart';
import '../models/chat_model.dart';

/// ويدجت مستقلة لرسائل المُستقبِل (الطرف الثاني)
class ReceiverMessageBubble extends StatelessWidget {
  /// نموذج البيانات الخاص بالرسالة المحتوية على النص ووقت الإرسال
  final ChatMessageModel message;

  const ReceiverMessageBubble({
    super.key,
    required this.message,
  });

  @override
  Widget build(BuildContext context) {
    // 💡 تحسين الأداء: استخدام sizeOf لتجنب إعادة بناء الويدجت عند فتح لوحة المفاتيح
    final double screenWidth = MediaQuery.sizeOf(context).width;

    return Align(
      alignment: Alignment.centerRight,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        constraints: BoxConstraints(
          // أقصى عرض للفقاعة هو 75% من عرض الشاشة
          maxWidth: screenWidth * 0.75,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: Colors.black.withValues(alpha: 0.3),
          borderRadius: const BorderRadius.only(
            topLeft: Radius.circular(18),
            topRight: Radius.circular(18),
            bottomLeft: Radius.circular(18),
            bottomRight: Radius.circular(2),
          ),
          border: Border.all(
            color: Colors.white.withValues(alpha: 0.3),
            width: 1,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          mainAxisSize: MainAxisSize.min, // محاذاة عمودية مضغوطة بحجم المحتوى
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
                color: Colors.white60,
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

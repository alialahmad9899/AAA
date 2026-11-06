import 'package:flutter/material.dart';
import '../models/chat_model.dart';

/// ويدجت مستقلة لعرض الصورة الشخصية أو الرسمة الرمزية بحسب الجنس
class GenderAvatar extends StatelessWidget {
  final String imageUrl;
  final String gender;
  final double radius;

  const GenderAvatar({
    super.key,
    required this.imageUrl,
    required this.gender,
    this.radius = 20,
  });

  @override
  Widget build(BuildContext context) {
    // تنظيف نص الجنس وتوحيده لتفادي المسافات الزائدة
    final String cleanGender = gender.trim().toLowerCase();
    final bool isFemale = cleanGender.contains('female') ||
        gender.contains('أنثى') ||
        gender.contains('انثى');

    final Color bgColor =
        isFemale ? const Color(0xFFEC407A) : const Color(0xFF1E88E5);
    final IconData iconData =
        isFemale ? Icons.female_rounded : Icons.male_rounded;

    // في حال عدم وجود صورة شخصية مخصصة
    if (!ChatThreadModel.hasCustomPhoto(imageUrl)) {
      return CircleAvatar(
        radius: radius,
        backgroundColor: bgColor,
        child: Icon(iconData, color: Colors.white, size: radius * 1.2),
      );
    }

    // حساب أبعاد الصورة الفعلية بناءً على كثافة الشاشة لتقليل استهلاك الذاكرة
    final double devicePixelRatio = MediaQuery.of(context).devicePixelRatio;
    final int cacheSize = (radius * 2 * devicePixelRatio).round();

    return CircleAvatar(
      radius: radius,
      backgroundColor: bgColor,
      child: ClipOval(
        child: Image.network(
          imageUrl,
          width: radius * 2,
          height: radius * 2,
          fit: BoxFit.cover,
          cacheWidth: cacheSize > 0 ? cacheSize : null,
          cacheHeight: cacheSize > 0 ? cacheSize : null,
          errorBuilder: (context, error, stackTrace) {
            return Container(
              width: radius * 2,
              height: radius * 2,
              color: bgColor,
              child: Icon(iconData, color: Colors.white, size: radius * 1.2),
            );
          },
        ),
      ),
    );
  }
}

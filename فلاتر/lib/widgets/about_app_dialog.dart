// ============================================================================
// اسم الملف: lib/widgets/about_app_dialog.dart
// المشروع: تطبيق وصال (Wesal)
// الوصف: نافذة منبثقة زجاجية فاخرة لعرض تعريف تطبيق وصال (عن التطبيق)
//        بتصميم Modern Premium UI وترجمة ديناميكية (عربي/إنجليزي).
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import '../constants/colors.dart';
import 'package:wesal_app/widgets/bottom_system_inset.dart';

class AboutAppDialog extends StatelessWidget {
  final String currentLanguage;

  const AboutAppDialog({
    super.key,
    this.currentLanguage = 'ar',
  });

  /// دالة استدعاء النافذة المنبثقة بسلاسة من أي مكان في التطبيق مع تمرير اللغة
  static void show(BuildContext context, {String currentLanguage = 'ar'}) {
    // 🛡️ فحص أمان مرجع الـ Context قبل فتح النافذة
    if (!context.mounted) return;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      barrierColor: Colors.black.withValues(alpha: 0.65),
      builder: (context) => AboutAppDialog(currentLanguage: currentLanguage),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool isAr = currentLanguage == 'ar';
    // 💡 تحسين الأداء: استخدام paddingOf و sizeOf لتجنب إعادة بناء الويدجت عند تغيرات الشاشة غير المباشرة
    final double bottomInset = WesalBottomInsets.of(context);
    final double screenHeight = MediaQuery.sizeOf(context).height;

    return Directionality(
      textDirection: isAr ? TextDirection.rtl : TextDirection.ltr,
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
          child: Container(
            constraints: BoxConstraints(
              maxHeight: screenHeight * 0.85,
            ),
            decoration: BoxDecoration(
              color: AppColors.cardColor.withValues(alpha: 0.92),
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(32)),
              border: Border.all(
                color: AppColors.textDark.withValues(alpha: 0.18),
                width: 1.2,
              ),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primaryTeal.withValues(alpha: 0.2),
                  blurRadius: 25,
                  spreadRadius: 2,
                  offset: const Offset(0, -5),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // 1. مقبض السحب العلوي (Drag Handle)
                const SizedBox(height: 12),
                Container(
                  width: 48,
                  height: 5,
                  decoration: BoxDecoration(
                    color: AppColors.textDark.withValues(alpha: 0.3),
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                const SizedBox(height: 16),

                // 2. ترويسة النافذة مع الشعار والعنوان
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Row(
                    children: [
                      Container(
                        width: 48,
                        height: 48,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [AppColors.primaryTeal, AppColors.secondaryTeal],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.primaryTeal
                                  .withValues(alpha: 0.4),
                              blurRadius: 12,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.favorite_rounded,
                          color: AppColors.textDark,
                          size: 26,
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Flexible(
                                  child: Text(
                                    isAr ? 'وصال – WESAL' : 'Wesal – WESAL',
                                    style: const TextStyle(
                                      color: AppColors.textDark,
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                      fontFamily: 'Cairo',
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 8, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: AppColors.primaryTeal
                                        .withValues(alpha: 0.2),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(
                                      color: AppColors.secondaryTeal
                                          .withValues(alpha: 0.4),
                                    ),
                                  ),
                                  child: Text(
                                    isAr ? 'الإصدار 2026' : 'Version 2026',
                                    style: const TextStyle(
                                      color: AppColors.accentLight,
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                      fontFamily: 'Cairo',
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              isAr
                                  ? 'منصة للزواج الجاد حول العالم'
                                  : 'Syrian platform for serious marriage worldwide',
                              style: TextStyle(
                                color: AppColors.textDark.withValues(alpha: 0.65),
                                fontSize: 12,
                                fontFamily: 'Cairo',
                              ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        onPressed: () {
                          if (context.mounted) Navigator.pop(context);
                        },
                        icon: Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: AppColors.textDark.withValues(alpha: 0.08),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.close_rounded,
                            color: AppColors.textMuted,
                            size: 20,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 16),
                Divider(color: AppColors.textDark.withValues(alpha: 0.1), height: 1),

                // 3. محتوى النقاط الملخصة (Scrollable Content)
                Flexible(
                  child: SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildFeatureCard(
                          icon: Icons.verified_user_rounded,
                          iconColor: AppColors.primaryTeal,
                          title: isAr
                              ? 'زواج جاد وليست مواعدة'
                              : 'Serious Marriage, Not Dating',
                          description: isAr
                              ? 'وصال مخصص حصراً للتعارف الجاد بهدف الزواج وتكوين أسرة متحابة، وليس للعلاقات العابرة أو التسلية.'
                              : 'Wesal is exclusively dedicated to serious relationships aiming for marriage and building a family, not for casual dating.',
                        ),
                        _buildFeatureCard(
                          icon: Icons.public_rounded,
                          iconColor: AppColors.secondaryTeal,
                          title: isAr
                              ? 'شبكة زواج حول العالم'
                              : 'Syrian Network Worldwide',
                          description: isAr
                              ? 'يجمع الباحثين عن الزواج في كافة انحاء العالم مثل سوريا ,، تركيا، ألمانيا، السويد، هولندا، فرنسا، النمسا، الخليج، أمريكا، وكندا.'
                              : 'Connecting Syrians in Syria, Turkey, Germany, Sweden, Netherlands, France, Austria, Gulf, USA, and Canada.',
                        ),
                        _buildFeatureCard(
                          icon: Icons.shield_rounded,
                          iconColor: AppColors.accentWarm,
                          title: isAr
                              ? 'خصوصية وأمان تام'
                              : 'Total Privacy & Security',
                          description: isAr
                              ? 'تحكم كامل بالصور والمعلومات الشخصية الظاهرة. لا يتم نشر رقم الهاتف أو البريد علناً إلا بموافقك الشخصية في حال تم طلب خدمة إضافية، مع خيارات حظر وإبلاغ فورية.'
                              : 'Full control over displayed photos and personal info. Phone numbers and emails are never published publicly, with instant block options.',
                        ),
                        _buildFeatureCard(
                          icon: Icons.auto_awesome_rounded,
                          iconColor: AppColors.warning,
                          title: isAr
                              ? 'نظام مطابقة وتوافق ذكي'
                              : 'Smart Matching System',
                          description: isAr
                              ? 'يقدم التطبيق اقتراحات دقيقة بناءً على الإقامة، العمر، التعليم، المهنة، والحالة الاجتماعية لتسهيل الوصول للشريك.'
                              : 'The app provides accurate suggestions based on residence, age, education, profession, and marital status to find your partner easily.',
                        ),
                        _buildFeatureCard(
                          icon: Icons.mark_email_read_rounded,
                          iconColor: AppColors.accentWarm,
                          title: isAr
                              ? 'تواصل محترم ومباشر'
                              : 'Respectful Communication',
                          description: isAr
                              ? 'تواصل آمن داخل التطبيق بعد الاهتمام المتبادل، مع إمكانية توثيق الحسابات بـ (الهاتف/الإيميل) لمنع الحسابات الوهمية.'
                              : 'Secure in-app communication after mutual interest, with account verification to prevent fake accounts.',
                        ),
                        _buildFeatureCard(
                          icon: Icons.workspace_premium_rounded,
                          iconColor: AppColors.accentLight,
                          title: isAr
                              ? 'عضوية وصال Premium'
                              : 'Wesal Premium Membership',
                          description: isAr
                              ? 'تمنحك ميزات إضافية مثل البحث المتقدم، أولوية الظهور، وتواصل مفتوح لزيادة فرص التوافق المثالي.'
                              : 'Grants you extra features like advanced search, priority visibility, and open communication to increase perfect match chances.',
                        ),
                      ],
                    ),
                  ),
                ),

                // 4. زر الإغلاق والإنهاء السفلي مع مراعاة الحافة الآمنة
                Padding(
                  padding: EdgeInsets.fromLTRB(20, 10, 20, 24 + bottomInset),
                  child: SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: Container(
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [AppColors.primaryTeal, AppColors.primaryTeal],
                          begin: Alignment.centerRight,
                          end: Alignment.centerLeft,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color:
                                AppColors.primaryTeal.withValues(alpha: 0.35),
                            blurRadius: 12,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.transparent,
                          shadowColor: Colors.transparent,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        onPressed: () {
                          if (context.mounted) Navigator.pop(context);
                        },
                        child: Text(
                          isAr ? 'حسناً، فهمت' : 'Okay, I understand',
                          style: const TextStyle(
                            color: AppColors.textDark,
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                          ),
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
    );
  }

  /// ودجت بناء عنصر النقطة الملخصة مع أيقونة وخلفية زجاجية أنيقة
  Widget _buildFeatureCard({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String description,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.textDark.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: AppColors.textDark.withValues(alpha: 0.1),
          width: 1,
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: iconColor.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: iconColor.withValues(alpha: 0.3),
                width: 1,
              ),
            ),
            child: Icon(
              icon,
              color: iconColor,
              size: 22,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: AppColors.textDark,
                    fontSize: 14.5,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  description,
                  style: TextStyle(
                    color: AppColors.textDark.withValues(alpha: 0.7),
                    fontSize: 12.5,
                    fontFamily: 'Cairo',
                    height: 1.5,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// اسم الملف: lib/screens/user_guide_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: واجهة دليل الاستخدام والأسئلة الشائعة بتصميم فاخر زجاجي
//        مع التدويل الشامل المزدوج (عربي / إنجليزي) وتغطية كافة خدمات النظام.
// ============================================================================

import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// نموذج بيانات عنصر السؤال والجواب مع دعم التصنيف المزدوج
class FaqItem {
  final String id;
  final String question;
  final String answer;
  final String categoryAr;
  final String categoryEn;

  FaqItem({
    required this.id,
    required this.question,
    required this.answer,
    required this.categoryAr,
    required this.categoryEn,
  });
}

class UserGuideScreen extends StatefulWidget {
  final String currentLanguage;

  const UserGuideScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<UserGuideScreen> createState() => _UserGuideScreenState();
}

class _UserGuideScreenState extends State<UserGuideScreen> {
  final TextEditingController _searchController = TextEditingController();
  int _selectedCategoryIndex = 0;
  String _searchQuery = '';

  /// 🟢 مجموعة للتحفظ على معرفات الأسئلة المفتوحة لمنع فقدان حالة التوسع عند إعادة البناء
  final Set<String> _expandedItemIds = {};

  bool get _isAr => widget.currentLanguage == 'ar';

  /// 🟢 قوائم التصنيفات الديناميكية المحسنة والداعمة لجميع خدمات التطبيق
  List<String> get _categories => _isAr
      ? [
          'الكل',
          'عن التطبيق والمطابقة',
          'الدردشة والإعجابات',
          'الباقات وطرق الدفع',
          'خدمات VIP المميزة',
          'الحساب والخصوصية',
          'الدعم الفني والمساعدة',
        ]
      : [
          'All',
          'About & Matching',
          'Chat & Likes',
          'Plans & Payments',
          'VIP Services',
          'Account & Privacy',
          'Technical Support',
        ];

  /// 🟢 قائمة الأسئلة والأجوبة المكتملة والشاملة لكافة خدمات التطبيق
  List<FaqItem> get _allFaqs => _isAr
      ? [
          // 1️⃣ تصنيف: عن التطبيق والمطابقة
          FaqItem(
            id: '1',
            question: 'كيفية استخدام تطبيق وصال ونظام المطابقة الذكي؟',
            answer:
                'تطبيق وصال مصمم للربط بين الشريكين بأسلوب عصري وآمن. يمكنك البدء بإنشاء ملفك الشخصي بالكامل، ثم ضبط توقعات الشريك المناسب، وتصفح قائمة الأعضاء أو استخدام نظام المطابقة الذكي لحساب نسبة التوافق وإيجاد الشريك الأمثل.',
            categoryAr: 'عن التطبيق والمطابقة',
            categoryEn: 'About & Matching',
          ),
          FaqItem(
            id: '2',
            question: 'هل تطبيق وصال مجاني للسيدات والأعضاء؟',
            answer:
                'تطبيق وصال مجاني بالكامل 100% لجميع السيدات دون أي رسوم تقديراً لدور المرأة؛ حيث يمكنهن المراسلة واستخدام كافة الميزات دائماً. بالنسبة للرجال، فإن إنشاء البروفايل، تصفح الأعضاء ورؤية التوافق مجاني، بينما تتطلب المراسلة المباشرة وجود باقة نشطة.',
            categoryAr: 'عن التطبيق والمطابقة',
            categoryEn: 'About & Matching',
          ),
          FaqItem(
            id: '3',
            question: 'ما هي شاشة "توقعات الشريك" وكيف تساعد في التوافق؟',
            answer:
                'تتيح لك شاشة توقعات الشريك تحديد تفضيلاتك والمواصفات الدقيقة المطلوبة في فتى/فتاة الأحلام (مثل السن، السكن، التعليم، الحالة الاجتماعية، والتدين). تقوم خوارزمية التطبيق بمقارنة هذه البيانات مع ملفات الأعضاء واقتراح أعلى نسبة توافق تلقائياً.',
            categoryAr: 'عن التطبيق والمطابقة',
            categoryEn: 'About & Matching',
          ),

          // 2️⃣ تصنيف: الدردشة والإعجابات
          FaqItem(
            id: '4',
            question: 'كيف تعمل حصة الإعجابات المجانية وطلبات الاهتمام؟',
            answer:
                'يمنح التطبيق الحسابات المجانية حصة قدرها 5 إعجابات مجانية لإبداء الاهتمام بالأعضاء الآخرين. عند استنفاذ هذه الحصة، يمكنك ترقية حسابك لاختيار إحدى الباقات المميزة لإرسال إعجابات وطلبات اهتمام غير محدودة.',
            categoryAr: 'الدردشة والإعجابات',
            categoryEn: 'Chat & Likes',
          ),
          FaqItem(
            id: '5',
            question: 'كيف يمكنني بدء الدردشة والمراسلة الفورية مع الأعضاء؟',
            answer:
                'لبدء المحادثة، قم بزيارة الملف الشخصي للعضو واضغط على زر "إرسال رسالة" أو "بدء الدردشة". تتوفر المراسلة الفورية غير المحدودة لجميع المشتركين في الباقات المميزة ولجميع السيدات مجاناً.',
            categoryAr: 'الدردشة والإعجابات',
            categoryEn: 'Chat & Likes',
          ),

          // 3️⃣ تصنيف: الباقات وطرق الدفع
          FaqItem(
            id: '6',
            question: 'ما هي طرق الدفع المتاحة لتفعيل وتجديد الاشتراك؟',
            answer:
                'يوفر التطبيق وسائل دفع متنوعة وآمنة: الدفع المباشر بالبطاقات الائتمانية والفيزا عالمياً عبر Stripe من داخل التطبيق، أو تحويل شام كاش المحلمي عبر كود التحويل المخصص (0f50e77304209ae23d85b7971f1089b7) مع رفع صورة إشعار التحويل للتفعيل.',
            categoryAr: 'الباقات وطرق الدفع',
            categoryEn: 'Plans & Payments',
          ),
          FaqItem(
            id: '7',
            question: 'ما هي الميزات المتاحة عند الاشتراك في الباقات المدفوعة؟',
            answer:
                'تتيح لك الباقات المدفوعة إرسال واستقبال الرسائل والمحادثات بلا حدود، إرسال الإعجابات الفورية، معرفة زوار ملفك الشخصي، الأولوية في الظهور ضمن نتائج البحث، والوصول لميزات الترويج الاستثنائية.',
            categoryAr: 'الباقات وطرق الدفع',
            categoryEn: 'Plans & Payments',
          ),

          // 4️⃣ تصنيف: خدمات VIP المميزة
          FaqItem(
            id: '8',
            question: 'ما هي الخدمات الإضافية VIP وكيف يمكن الاستفادة منها؟',
            answer:
                'خدمات VIP هي باقة من الميزات الخاصة التي تضمن لك أعلى معدل وصول وتوافق، وتتضمن الترويج المخصص لملفك الشخصي عبر إعلانات ممتازة على منصات سوشال ميديا الخاصة بتطبيق وصال، الاستشارات المباشرة، واستبيان تحليل الشخصية التفاعلي.',
            categoryAr: 'خدمات VIP المميزة',
            categoryEn: 'VIP Services',
          ),
          FaqItem(
            id: '9',
            question: 'كيف تعمل خدمة الترويج لنشر بوست فيسبوك مخصص؟',
            answer:
                'عند تفعيل خدمات VIP أو الاشتراكات المتقدمة، يتم صياغة منشور مميز وموجه يبرز مواصفات ملفك الشخصي وتطلعاتك، ونشره على منصات فيسبوك الرسمية التابعة للتطبيق لجلب طلبات اهتمام حقيقية ومتوافقة جداً.',
            categoryAr: 'خدمات VIP المميزة',
            categoryEn: 'VIP Services',
          ),

          // 5️⃣ تصنيف: الحساب والخصوصية
          FaqItem(
            id: '10',
            question: 'كيف يمكنني معرفة ورؤية من زار ملفي الشخصي؟',
            answer:
                'يمكنك الضغط على أيقونة العين (زوار الملف الشخصي) الموجودة في شريط الترويسة العلوي بشاشة الحساب أو الشاشة الرئيسية. تتيح لك هذه الميزة رؤية القائمة الكاملة للأعضاء الذين زاروا بروفايلك بناءً على حصة الزوار المتاحة في باقتك.',
            categoryAr: 'الحساب والخصوصية',
            categoryEn: 'Account & Privacy',
          ),
          FaqItem(
            id: '11',
            question: 'كيف أتعامل مع الحسابات المزعجة أو حظرها وإدارتها؟',
            answer:
                'يمكنك حظر أي عضو أو الإبلاغ عنه بنقرة واحدة من داخل صفحة محادثته أو ملفه الشخصي. كما يمكنك معاينة وإلغاء حظر الحسابات من خلال تبويب "الحسابات المحظورة" في شاشة الحساب.',
            categoryAr: 'الحساب والخصوصية',
            categoryEn: 'Account & Privacy',
          ),
          FaqItem(
            id: '12',
            question: 'كيف يمكنني تغيير لغة التطبيق؟',
            answer:
                'يمكنك التبديل بين اللغتين العربية والإنجليزية بسهولة من شاشة "الحساب" عبر خيار "لغة التطبيق"، وسيقوم التطبيق بتحديث الواجهة بالكامل وتغيير الاتجاه تلقائياً (RTL / LTR).',
            categoryAr: 'الحساب والخصوصية',
            categoryEn: 'Account & Privacy',
          ),
          FaqItem(
            id: '13',
            question: 'كيف يمكنني تعديل بياناتي الشخصية أو حذف الحساب نهائياً؟',
            answer:
                'من تبويب "الحساب"، اختر "تعديل الملف الشخصي" لتحديث بياناتك وصورك. وفي حال رغبتك في مغادرة منصة وصال، يمكنك الضغط على "حذف الحساب" من قائمة إجراءات الحساب لمسح كافة بياناتك ومحادثاتك نهائياً وبشكل فورياً.',
            categoryAr: 'الحساب والخصوصية',
            categoryEn: 'Account & Privacy',
          ),

          // 6️⃣ تصنيف: الدعم الفني والمساعدة
          FaqItem(
            id: '14',
            question: 'كيف يمكنني التواصل مع فريق الدعم الفني وإرسال تذكرة؟',
            answer:
                'يمكنك الانتقال إلى شاشة "الحساب" ثم اختيار "الدعم الفني" لإنشاء تذكرة دعم جديدة لمتابعة أي استفسار أو مشكلة تواجهها في الدفع أو الحساب، وسيتم الرد عليك من قِبل الفريق خلال وقت قصير.',
            categoryAr: 'الدعم الفني والمساعدة',
            categoryEn: 'Technical Support',
          ),
        ]
      : [
          // English FAQs
          FaqItem(
            id: '1',
            question: 'How to use Wesal App & Smart Matching System?',
            answer:
                'Wesal is designed to connect partners safely. Start by creating your complete profile, set your partner expectations, browse members, or use the AI smart matching algorithm to calculate compatibility rate.',
            categoryAr: 'عن التطبيق والمطابقة',
            categoryEn: 'About & Matching',
          ),
          FaqItem(
            id: '2',
            question: 'Is Wesal completely free for female users?',
            answer:
                'Yes, Wesal is 100% free for all female users forever without any fees. For male users, profile creation, browsing, and compatibility checking are free, while direct messaging requires an active subscription package.',
            categoryAr: 'عن التطبيق والمطابقة',
            categoryEn: 'About & Matching',
          ),
          FaqItem(
            id: '3',
            question: 'What is Partner Expectations & how does it help?',
            answer:
                'Partner Expectations allows you to specify ideal partner criteria (age, location, education, marital status, religiosity). Our algorithm automatically compares this data with member profiles to suggest top matching profiles.',
            categoryAr: 'عن التطبيق والمطابقة',
            categoryEn: 'About & Matching',
          ),
          FaqItem(
            id: '4',
            question: 'How does free likes quota & interest requests work?',
            answer:
                'Free accounts receive 5 free likes quota to express interest. Once reached, you can upgrade to a premium package for unlimited likes and interest requests.',
            categoryAr: 'الدردشة والإعجابات',
            categoryEn: 'Chat & Likes',
          ),
          FaqItem(
            id: '5',
            question: 'How to start chatting & direct messaging with members?',
            answer:
                'Open any member profile and tap "Send Message" or "Start Chat". Unlimited direct chat is available for all active package subscribers and all female users for free.',
            categoryAr: 'الدردشة والإعجابات',
            categoryEn: 'Chat & Likes',
          ),
          FaqItem(
            id: '6',
            question: 'What payment methods are available for activation?',
            answer:
                'We support multiple secure payment options: direct credit card / Visa payment via in-app Stripe Native sheet, or local Sham Cash transfer (Code: 0f50e77304209ae23d85b7971f1089b7) with transaction receipt upload.',
            categoryAr: 'الباقات وطرق الدفع',
            categoryEn: 'Plans & Payments',
          ),
          FaqItem(
            id: '7',
            question: 'What features are included in premium plans?',
            answer:
                'Premium plans unlock unlimited messaging, instant likes, profile visitors discovery, priority listing in search results, and access to VIP promotion features.',
            categoryAr: 'الباقات وطرق الدفع',
            categoryEn: 'Plans & Payments',
          ),
          FaqItem(
            id: '8',
            question: 'What are VIP Extra Services & how to use them?',
            answer:
                'VIP Services provide premium advantages to maximize compatibility, including custom Facebook ad post promotion for your profile, direct consultation, and interactive personality surveys.',
            categoryAr: 'خدمات VIP المميزة',
            categoryEn: 'VIP Services',
          ),
          FaqItem(
            id: '9',
            question: 'How does Custom Facebook Promotion work?',
            answer:
                'When activating VIP packages or services, a custom tailored promotion for your profile is created and published on our official Facebook platforms to attract highly compatible interest requests.',
            categoryAr: 'خدمات VIP المميزة',
            categoryEn: 'VIP Services',
          ),
          FaqItem(
            id: '10',
            question: 'How can I see who visited my profile?',
            answer:
                'Tap the eye icon (Profile Visitors) on the header bar in the Account tab or Home tab. This feature reveals members who viewed your profile based on your active package quota.',
            categoryAr: 'الحساب والخصوصية',
            categoryEn: 'Account & Privacy',
          ),
          FaqItem(
            id: '11',
            question: 'How to block abusive users and manage blocked list?',
            answer:
                'You can block or report any member directly from their chat or profile page. Manage and unblock accounts anytime under "Blocked Accounts" in the Account tab.',
            categoryAr: 'الحساب والخصوصية',
            categoryEn: 'Account & Privacy',
          ),
          FaqItem(
            id: '12',
            question: 'How to change the app language?',
            answer:
                'You can switch between Arabic and English seamlessly from the Account screen via "App Language". The app interface and orientation (RTL / LTR) will update automatically.',
            categoryAr: 'الحساب والخصوصية',
            categoryEn: 'Account & Privacy',
          ),
          FaqItem(
            id: '13',
            question:
                'How to edit my profile details or permanently delete account?',
            answer:
                'From Account tab, click "Edit Profile" to update photos and details. If you wish to leave Wesal, tap "Delete Account" under Account Actions to erase all your profile data and chats permanently.',
            categoryAr: 'الحساب والخصوصية',
            categoryEn: 'Account & Privacy',
          ),
          FaqItem(
            id: '14',
            question: 'How to contact support team and open a ticket?',
            answer:
                'Navigate to Account tab and select "Support" to create a new support ticket. Our technical team will assist you with any payment, account, or feature inquiries shortly.',
            categoryAr: 'الدعم الفني والمساعدة',
            categoryEn: 'Technical Support',
          ),
        ];

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  /// 🟢 تصفية القائمة بناءً على البحث والتصنيف المحدد بمؤشر الفهرس
  List<FaqItem> get _filteredFaqs {
    final categories = _categories;
    final int safeCategoryIndex = (_selectedCategoryIndex >= 0 &&
            _selectedCategoryIndex < categories.length)
        ? _selectedCategoryIndex
        : 0;

    return _allFaqs.where((item) {
      final matchesCategory = (safeCategoryIndex == 0) ||
          (_isAr
              ? item.categoryAr == categories[safeCategoryIndex]
              : item.categoryEn == categories[safeCategoryIndex]);

      final matchesQuery = _searchQuery.isEmpty ||
          item.question.toLowerCase().contains(_searchQuery) ||
          item.answer.toLowerCase().contains(_searchQuery);

      return matchesCategory && matchesQuery;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    const Color darkBgColor = Color(0xFF0F0F1A);

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: darkBgColor,
        systemNavigationBarIconBrightness: Brightness.light,
        systemNavigationBarDividerColor: Colors.transparent,
      ),
      child: Directionality(
        textDirection: _isAr ? TextDirection.rtl : TextDirection.ltr,
        child: Scaffold(
          backgroundColor: darkBgColor,
          appBar: AppBar(
            backgroundColor: const Color(0xFF161626),
            elevation: 0,
            centerTitle: true,
            leading: IconButton(
              icon: Icon(
                _isAr
                    ? Icons.arrow_back_ios_rounded
                    : Icons.arrow_back_ios_new_rounded,
                color: Colors.white,
                size: 20,
              ),
              onPressed: () => Navigator.pop(context),
            ),
            title: Text(
              _isAr ? 'دليل الاستخدام والأسئلة الشائعة' : 'User Guide & FAQ',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
          ),
          body: SafeArea(top: false, bottom: true, child: Column(
            children: [
              // 1. شريط البحث والتصنيفات الشامل
              _buildHeaderSearchAndFilters(),

              // 2. قائمة الأسئلة والإجابات المحدثة
              Expanded(
                child: _filteredFaqs.isEmpty
                    ? _buildEmptySearchResult()
                    : ListView.builder(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 14),
                        itemCount: _filteredFaqs.length,
                        itemBuilder: (context, index) {
                          final item = _filteredFaqs[index];
                          return _buildFaqCard(item);
                        },
                      ),
              ),
            ],
          )),
        ),
      ),
    );
  }

  /// ودجت البحث والشرائح بتصميم زجاجي عالي الجودة
  Widget _buildHeaderSearchAndFilters() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
      decoration: BoxDecoration(
        color: const Color(0xFF161626).withValues(alpha: 0.8),
        border: Border(
          bottom: BorderSide(
            color: Colors.white.withValues(alpha: 0.08),
            width: 1,
          ),
        ),
      ),
      child: Column(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: const Color(0xFF8E24AA).withValues(alpha: 0.4),
                    width: 1.2,
                  ),
                ),
                child: TextField(
                  controller: _searchController,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    fontFamily: 'Cairo',
                  ),
                  onChanged: (val) {
                    setState(() {
                      _searchQuery = val.trim().toLowerCase();
                    });
                  },
                  decoration: InputDecoration(
                    hintText: _isAr
                        ? 'ابحث عن سؤال، ميزة، أو خدمة...'
                        : 'Search question, feature or service...',
                    hintStyle: TextStyle(
                      color: Colors.white.withValues(alpha: 0.5),
                      fontSize: 13,
                      fontFamily: 'Cairo',
                    ),
                    prefixIcon: const Icon(
                      Icons.search_rounded,
                      color: Color(0xFFE040FB),
                      size: 22,
                    ),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear_rounded,
                                color: Colors.white70, size: 18),
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _searchQuery = '');
                            },
                          )
                        : null,
                    border: InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 14),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 38,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: _categories.length,
              itemBuilder: (context, index) {
                final category = _categories[index];
                final bool isSelected = _selectedCategoryIndex == index;
                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 4.0),
                  child: InkWell(
                    onTap: () {
                      setState(() {
                        _selectedCategoryIndex = index;
                      });
                    },
                    borderRadius: BorderRadius.circular(20),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 8),
                      decoration: BoxDecoration(
                        gradient: isSelected
                            ? const LinearGradient(
                                colors: [Color(0xFF8E24AA), Color(0xFF00B0FF)],
                                begin: Alignment.topRight,
                                end: Alignment.bottomLeft,
                              )
                            : null,
                        color: isSelected
                            ? null
                            : Colors.white.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: isSelected
                              ? const Color(0xFFE040FB)
                              : Colors.white.withValues(alpha: 0.15),
                          width: 1,
                        ),
                        boxShadow: isSelected
                            ? [
                                BoxShadow(
                                  color: const Color(0xFF8E24AA)
                                      .withValues(alpha: 0.4),
                                  blurRadius: 10,
                                  offset: const Offset(0, 3),
                                )
                              ]
                            : [],
                      ),
                      child: Text(
                        category,
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 12.5,
                          fontWeight:
                              isSelected ? FontWeight.bold : FontWeight.w600,
                          fontFamily: 'Cairo',
                        ),
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

  /// ودجت بطاقة السؤال الزجاجية القابلة للتوسع بحواف مضيئة
  Widget _buildFaqCard(FaqItem item) {
    final bool isExpanded = _expandedItemIds.contains(item.id);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF181828),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isExpanded
              ? const Color(0xFFE040FB).withValues(alpha: 0.7)
              : Colors.white.withValues(alpha: 0.1),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: isExpanded
                ? const Color(0xFF8E24AA).withValues(alpha: 0.2)
                : Colors.black.withValues(alpha: 0.25),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(18),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: () {
              setState(() {
                if (isExpanded) {
                  _expandedItemIds.remove(item.id);
                } else {
                  _expandedItemIds.add(item.id);
                }
              });
            },
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [
                              const Color(0xFF8E24AA).withValues(alpha: 0.35),
                              const Color(0xFF00B0FF).withValues(alpha: 0.35),
                            ],
                            begin: Alignment.topRight,
                            end: Alignment.bottomLeft,
                          ),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.help_outline_rounded,
                          color: Color(0xFFE040FB),
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          item.question,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Cairo',
                            height: 1.4,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      AnimatedRotation(
                        turns: isExpanded ? 0.5 : 0.0,
                        duration: const Duration(milliseconds: 200),
                        child: Icon(
                          Icons.keyboard_arrow_down_rounded,
                          color: isExpanded
                              ? const Color(0xFFE040FB)
                              : Colors.white70,
                          size: 26,
                        ),
                      ),
                    ],
                  ),
                  AnimatedCrossFade(
                    firstChild: const SizedBox(width: double.infinity),
                    secondChild: Padding(
                      padding: const EdgeInsetsDirectional.only(
                          top: 14.0, start: 48),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Divider(
                            color: Colors.white.withValues(alpha: 0.12),
                            height: 1,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            item.answer,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 13.5,
                              fontFamily: 'Cairo',
                              height: 1.65,
                            ),
                          ),
                        ],
                      ),
                    ),
                    crossFadeState: isExpanded
                        ? CrossFadeState.showSecond
                        : CrossFadeState.showFirst,
                    duration: const Duration(milliseconds: 250),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// ودجت النتيجة الفارغة في حال عدم وجود بحث مطابق
  Widget _buildEmptySearchResult() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(22),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: const Color(0xFF1E1E2C),
                border: Border.all(
                    color: const Color(0xFF8E24AA).withValues(alpha: 0.4),
                    width: 1.5),
              ),
              child: const Icon(
                Icons.search_off_rounded,
                size: 54,
                color: Color(0xFFE040FB),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              _isAr ? 'لم نجد نتيجة مطابقة' : 'No Matching Results Found',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 17,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
            const SizedBox(height: 8),
            Text(
              _isAr
                  ? 'جرب البحث بكلمات أخرى أو اختر تصنيفاً مختلفاً من الأعلى.'
                  : 'Try searching with other keywords or choose a different category.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white70,
                fontSize: 13,
                fontFamily: 'Cairo',
              ),
            ),
          ],
        ),
      ),
    );
  }
}

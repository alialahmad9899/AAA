// ============================================================================
// اسم الملف: lib/screens/blocked_accounts_screen.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: واجهة الحسابات المحظورة بتصميم حديث مع دعم كامل للترجمة المزدوجة
//        (العربية والإنجليزية) والتحكم الديناميكي بالاتجاه (RTL / LTR).
// ============================================================================

import 'package:flutter/material.dart';
import '../models/member_model.dart';
import '../services/api_service.dart';

class BlockedAccountsScreen extends StatefulWidget {
  final String currentLanguage;

  const BlockedAccountsScreen({
    super.key,
    this.currentLanguage = 'ar',
  });

  @override
  State<BlockedAccountsScreen> createState() => _BlockedAccountsScreenState();
}

class _BlockedAccountsScreenState extends State<BlockedAccountsScreen> {
  bool _isLoading = true;
  List<MemberModel> _blockedUsers = [];
  final Set<int> _unblockingUserIds = {};

  bool get _isArabic => widget.currentLanguage == 'ar';

  @override
  void initState() {
    super.initState();
    _loadBlockedUsers();
  }

  /// جلب قائمة المستخدمين المحظورين من السيرفر
  Future<void> _loadBlockedUsers() async {
    if (_blockedUsers.isEmpty) {
      setState(() => _isLoading = true);
    }
    try {
      final users =
          await ApiService.getIgnoredUserList(lang: widget.currentLanguage);
      if (mounted) {
        setState(() {
          _blockedUsers = users;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  /// التعامل مع طلب إلغاء الحظر: إظهار تأكيد ثم تنفيذ الطلب
  Future<void> _handleUnblockUser(MemberModel user) async {
    if (_unblockingUserIds.contains(user.id)) return;

    final bool? confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
        child: AlertDialog(
          backgroundColor: const Color(0xFF1E1E2C),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          title: Text(
            _isArabic ? 'إلغاء الحظر' : 'Unblock Account',
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
              fontSize: 18,
              fontFamily: 'Cairo',
            ),
          ),
          content: Text(
            _isArabic
                ? 'هل أنت متأكد من إلغاء حظر الحساب (${user.name})؟'
                : 'Are you sure you want to unblock (${user.name})?',
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 14,
              fontFamily: 'Cairo',
            ),
          ),
          actionsAlignment: MainAxisAlignment.spaceEvenly,
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: Text(
                _isArabic ? 'إلغاء' : 'Cancel',
                style: const TextStyle(
                    color: Colors.grey, fontSize: 14, fontFamily: 'Cairo'),
              ),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFE53935),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
                padding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              ),
              onPressed: () => Navigator.of(ctx).pop(true),
              child: Text(
                _isArabic ? 'نعم، إلغاء الحظر' : 'Yes, Unblock',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Cairo',
                ),
              ),
            ),
          ],
        ),
      ),
    );

    if (confirm != true || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(
      SnackBar(
        content: Text(
          _isArabic ? 'جاري إلغاء الحظر...' : 'Unblocking...',
          style: const TextStyle(fontFamily: 'Cairo'),
        ),
        duration: const Duration(seconds: 1),
        backgroundColor: const Color(0xFF2C2C3E),
      ),
    );

    setState(() {
      _unblockingUserIds.add(user.id);
    });

    try {
      final result = await ApiService.removeFromIgnoreList(user.id);

      if (!mounted) return;

      if (result['success'] == true) {
        setState(() {
          _blockedUsers.removeWhere((item) => item.id == user.id);
        });
        messenger.hideCurrentSnackBar();
        messenger.showSnackBar(
          SnackBar(
            content: Text(
              result['message'] ??
                  (_isArabic
                      ? 'تم إلغاء الحظر بنجاح'
                      : 'User unblocked successfully'),
              style: const TextStyle(fontFamily: 'Cairo'),
            ),
            backgroundColor: const Color(0xFF4CAF50),
            behavior: SnackBarBehavior.floating,
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        );
      } else {
        messenger.hideCurrentSnackBar();
        messenger.showSnackBar(
          SnackBar(
            content: Text(
              result['message'] ??
                  (_isArabic
                      ? 'تعذر إلغاء الحظر، يرجى المحاولة لاحقاً'
                      : 'Failed to unblock user, please try again'),
              style: const TextStyle(fontFamily: 'Cairo'),
            ),
            backgroundColor: const Color(0xFFE53935),
            behavior: SnackBarBehavior.floating,
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      messenger.hideCurrentSnackBar();
      messenger.showSnackBar(
        SnackBar(
          content: Text(
            _isArabic
                ? 'تعذر إلغاء الحظر، يرجى المحاولة لاحقاً'
                : 'Failed to unblock user, please try again',
            style: const TextStyle(fontFamily: 'Cairo'),
          ),
          backgroundColor: const Color(0xFFE53935),
          behavior: SnackBarBehavior.floating,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _unblockingUserIds.remove(user.id);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isArabic ? TextDirection.rtl : TextDirection.ltr,
      child: Scaffold(
        backgroundColor: const Color(0xFF0F0F1A),
        appBar: AppBar(
          backgroundColor: const Color(0xFF161626),
          elevation: 0,
          centerTitle: true,
          leading: IconButton(
            icon: Icon(
              _isArabic
                  ? Icons.arrow_back_ios_rounded
                  : Icons.arrow_back_rounded,
              color: Colors.white,
              size: 20,
            ),
            onPressed: () => Navigator.pop(context),
          ),
          title: Text(
            _isArabic ? 'الحسابات المحظورة' : 'Blocked Accounts',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
              fontFamily: 'Cairo',
              letterSpacing: 0.3,
            ),
          ),
        ),
        body: RefreshIndicator(
          onRefresh: _loadBlockedUsers,
          color: const Color(0xFFE91E63),
          backgroundColor: const Color(0xFF1E1E2C),
          child: _isLoading
              ? const Center(
                  child: CircularProgressIndicator(
                    valueColor:
                        AlwaysStoppedAnimation<Color>(Color(0xFFE91E63)),
                  ),
                )
              : _blockedUsers.isEmpty
                  ? _buildEmptyState()
                  : ListView.builder(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 20),
                      itemCount: _blockedUsers.length,
                      itemBuilder: (context, index) {
                        final user = _blockedUsers[index];
                        return _buildUserCard(user);
                      },
                    ),
        ),
      ),
    );
  }

  /// ودجت الحالة الفارغة عند عدم وجود مستخدمين محظورين
  Widget _buildEmptyState() {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: Container(
        height: MediaQuery.of(context).size.height * 0.75,
        alignment: Alignment.center,
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: const Color(0xFF181828),
                border: Border.all(
                  color: const Color(0xFF00B0FF).withValues(alpha: 0.25),
                  width: 1.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF00B0FF).withValues(alpha: 0.12),
                    blurRadius: 20,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: const Icon(
                Icons.block_rounded,
                size: 60,
                color: Color(0xFF00B0FF),
              ),
            ),
            const SizedBox(height: 24),
            Text(
              _isArabic ? 'لا توجد حسابات محظورة' : 'No Blocked Accounts',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
                fontFamily: 'Cairo',
              ),
            ),
            const SizedBox(height: 10),
            Text(
              _isArabic
                  ? 'القائمة خالية حالياً. يمكنك حظر أو إلغاء حظر أي حساب من صفحة حسابه الشخصي أو الدردشة.'
                  : 'The list is currently empty. You can block or unblock accounts from their profile or chat.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Color(0xFF8E8E93),
                fontSize: 13,
                fontFamily: 'Cairo',
                height: 1.5,
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// ودجت بطاقة المستخدم المحظور
  Widget _buildUserCard(MemberModel user) {
    final bool isUnblocking = _unblockingUserIds.contains(user.id);
    final String imageUrl = user.image.isNotEmpty
        ? user.image
        : 'https://www.wesalsyria.com/public/assets/img/avatar-place.png';

    final String ageDisplay = (user.age > 0)
        ? '${user.age} ${_isArabic ? 'سنة' : 'years'}'
        : (_isArabic ? 'عضو في وصال' : 'Wesal Member');

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF181828),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.08),
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.25),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            padding: const EdgeInsets.all(2),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: const LinearGradient(
                colors: [
                  Color(0xFFE91E63),
                  Color(0xFF00B0FF),
                ],
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF00B0FF).withValues(alpha: 0.2),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Container(
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                color: Color(0xFF181828),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(50),
                child: Image.network(
                  imageUrl,
                  fit: BoxFit.cover,
                  cacheWidth: 150,
                  cacheHeight: 150,
                  errorBuilder: (context, error, stackTrace) => const Icon(
                    Icons.person_rounded,
                    color: Colors.white54,
                    size: 30,
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  user.name.isNotEmpty
                      ? user.name
                      : (_isArabic ? 'مستخدم' : 'User'),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Cairo',
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  ageDisplay,
                  style: const TextStyle(
                    color: Color(0xFF8E8E93),
                    fontSize: 12,
                    fontFamily: 'Cairo',
                  ),
                ),
              ],
            ),
          ),
          InkWell(
            onTap: isUnblocking ? null : () => _handleUnblockUser(user),
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFFE53935).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: const Color(0xFFE53935).withValues(alpha: 0.35),
                  width: 1,
                ),
              ),
              child: isUnblocking
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor:
                            AlwaysStoppedAnimation<Color>(Color(0xFFFF5252)),
                      ),
                    )
                  : Text(
                      _isArabic ? 'إلغاء الحظر' : 'Unblock',
                      style: const TextStyle(
                        color: Color(0xFFFF5252),
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Cairo',
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

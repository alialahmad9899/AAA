// ============================================================================
// اسم الملف: lib/models/member_model.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: موديل كروت قائمة الأعضاء والتطابقات المزود بدوال الترجمة الديناميكية اللحظية،
//        مع تحصين شامل لاستخراج وقراءة بيانات الباقات والاشتراكات.
// ============================================================================

import 'package:flutter/foundation.dart';
import 'member_profile_model.dart';

class MemberModel {
  final int id;
  final String code;
  final String name;
  final String image;
  final int age;
  final String rawCountry;
  final String rawResidence;
  final String rawOccupation;
  final String rawMaritalStatus;
  final String rawReligion;
  final int? matchPercentage;
  final int matchCriteriaCount;
  final int matchEvaluatedCriteriaCount;
  final String matchStatus;
  final String packageName;

  MemberModel({
    required this.id,
    required this.code,
    required this.name,
    required this.image,
    required this.age,
    required this.rawCountry,
    required this.rawResidence,
    required this.rawOccupation,
    required this.rawMaritalStatus,
    required this.rawReligion,
    this.matchPercentage,
    this.matchCriteriaCount = 0,
    this.matchEvaluatedCriteriaCount = 0,
    this.matchStatus = 'not_calculated',
    this.packageName = '',
  });

  // --------------------------------------------------------------------------
  // 🟢 خصائص مساعدة للتحقق من نوع الباقة والاشتراك (Package Classification)
  // --------------------------------------------------------------------------

  /// هل يمتلك العضو باقة نشطة؟
  bool get hasActivePackage => packageName.trim().isNotEmpty;

  /// هل الباقة ملكية؟
  bool get isRoyalPackage {
    final clean = packageName.trim().toLowerCase();
    return clean.contains('ملكي') ||
        clean.contains('royal') ||
        clean.contains('3');
  }

  /// هل الباقة ذهبية؟
  bool get isGoldPackage {
    final clean = packageName.trim().toLowerCase();
    return clean.contains('ذهبي') ||
        clean.contains('gold') ||
        clean.contains('شهر') ||
        clean.contains('month') ||
        clean == '1';
  }

  /// هل الباقة فضية / مميزة؟
  bool get isSilverPackage {
    if (!hasActivePackage) return false;
    return !isRoyalPackage && !isGoldPackage;
  }

  // --------------------------------------------------------------------------
  // 🟢 دوال الحصول على النصوص المترجمة ديناميكياً بحسب لغة الواجهة الحالية
  // --------------------------------------------------------------------------
  String getLocalizedMaritalStatus({required bool isArabic}) {
    return AttributeTranslator.translateMaritalStatus(rawMaritalStatus,
        isArabic: isArabic);
  }

  String getLocalizedReligion({required bool isArabic}) {
    return AttributeTranslator.translateReligion(rawReligion,
        isArabic: isArabic);
  }

  String getLocalizedOccupation({required bool isArabic}) {
    if (rawOccupation.isEmpty) {
      return isArabic ? 'غير محدد' : 'Not Specified';
    }
    return AttributeTranslator.translate(rawOccupation, isArabic: isArabic);
  }

  String getLocalizedResidence({required bool isArabic}) {
    final String residence =
        rawResidence.isNotEmpty ? rawResidence : rawCountry;
    return AttributeTranslator.translate(residence, isArabic: isArabic);
  }

  String getLocalizedCountry({required bool isArabic}) {
    return AttributeTranslator.translate(rawCountry, isArabic: isArabic);
  }

  /// دالة تجميع وصياغة الموقع الجغرافي (المدينة والبلد) مترجمين
  String getFormattedLocation({required bool isArabic}) {
    final String res = getLocalizedResidence(isArabic: isArabic);
    final String cnt = getLocalizedCountry(isArabic: isArabic);

    if (cnt.isNotEmpty && cnt != res) {
      return res.isNotEmpty ? '$res، $cnt' : cnt;
    }
    return res;
  }

  // 🟢 ممتلكات قديمة للتوافق المباشر
  String get maritalStatus => getLocalizedMaritalStatus(isArabic: true);
  String get religion => getLocalizedReligion(isArabic: true);
  String get occupation => getLocalizedOccupation(isArabic: true);
  String get residence => getLocalizedResidence(isArabic: true);
  String get country => getLocalizedCountry(isArabic: true);

  // --------------------------------------------------------------------------
  // 🟢 دالة التحليل واستخراج اسم الباقة بشكل حذر وموسع (Robust Package Parser)
  // --------------------------------------------------------------------------
  static String _extractPackageName(
      Map<String, dynamic> json, Map<String, dynamic> basicInfo) {
    // 1. القائمة العميقة للمفاتيح المحتملة لاسم الباقة
    final List<dynamic> possiblePackageValues = [
      json['package_name'],
      json['package'],
      json['package_title'],
      json['current_package'],
      json['user_package'],
      json['subscription_name'],
      json['package_details'],
      json['package_badge'],
      basicInfo['package_name'],
      basicInfo['package'],
      basicInfo['package_title'],
      basicInfo['current_package'],
      basicInfo['user_package'],
      basicInfo['subscription_name'],
    ];

    for (var val in possiblePackageValues) {
      if (val == null) continue;

      // إذا كانت القيمة نصية مباشرة
      if (val is String && val.trim().isNotEmpty) {
        return val.trim();
      }

      // إذا كانت القيمة عبارة عن Map يحتوي على اسم الباقة
      if (val is Map) {
        final String nestedName = (val['name'] ??
                val['title'] ??
                val['package_name'] ??
                val['name_ar'] ??
                val['name_en'] ??
                '')
            .toString()
            .trim();
        if (nestedName.isNotEmpty) {
          return nestedName;
        }
      }
    }

    // 2. الفحص في حال وجود package_id مباشر مع عدم وجود اسم صريح
    final dynamic packageId = json['package_id'] ?? basicInfo['package_id'];
    if (packageId != null) {
      final String pIdStr = packageId.toString().trim();
      if (pIdStr == '3') return 'الباقة الملكية';
      if (pIdStr == '2' || pIdStr == '1') return 'الباقة الذهبية';
    }

    return '';
  }

  /// دالة تحويل JSON إلى الموديل مع تحصين كامل
  factory MemberModel.fromJson(Map<String, dynamic> json,
      {bool isArabic = true}) {
    final Map<String, dynamic> basicInfo =
        json['basic_info'] is Map<String, dynamic>
            ? json['basic_info'] as Map<String, dynamic>
            : <String, dynamic>{};

    final String firstName = (json['first_name'] ??
            json['firs_name'] ??
            basicInfo['first_name'] ??
            basicInfo['firs_name'] ??
            '')
        .toString();

    final String lastName =
        (json['last_name'] ?? basicInfo['last_name'] ?? '').toString();

    final String fullName = '$firstName $lastName'.trim();

    // استخراج القيمة الخام للحالة الاجتماعية
    final String parsedRawMarital = (json['marital_status'] ??
            json['maritial_status'] ??
            json['maritalStatus'] ??
            basicInfo['marital_status'] ??
            basicInfo['maritial_status'] ??
            '')
        .toString();

    // استخراج القيمة الخام للديانة
    final String parsedRawReligion =
        (json['religion'] ?? json['religion_id'] ?? basicInfo['religion'] ?? '')
            .toString();

    // استخراج القيمة الخام للمهنة
    final String parsedRawOccupation = (json['occupation'] ??
            json['profession'] ??
            basicInfo['occupation'] ??
            basicInfo['profession'] ??
            '')
        .toString()
        .trim();

    // استخراج البلد والإقامة الخام
    final String parsedRawCountry =
        (json['country'] ?? basicInfo['country'] ?? '').toString().trim();

    final String parsedRawResidence =
        (json['residence'] != null && json['residence'].toString().isNotEmpty)
            ? json['residence'].toString().trim()
            : parsedRawCountry;

    final String parsedImage = (json['photo'] ??
            json['image'] ??
            basicInfo['photo'] ??
            basicInfo['image'] ??
            '')
        .toString();

    final dynamic rawAge = json['age'] ?? basicInfo['age'];
    int parsedAge = 0;
    if (rawAge is int) {
      parsedAge = rawAge;
    } else if (rawAge != null) {
      parsedAge = int.tryParse(rawAge.toString()) ?? 0;
    }

    int? parsedMatch;
    final rawMatch = json['profile_match_percentage'] ??
        json['match_percentage'] ??
        json['matching_percentage'] ??
        json['match_rate'] ??
        json['match'] ??
        json['percentage'];

    if (rawMatch != null) {
      final cleanStr = rawMatch.toString().replaceAll('%', '').trim();
      final doubleVal = double.tryParse(cleanStr);
      if (doubleVal != null && doubleVal.isFinite) {
        parsedMatch = doubleVal.round().clamp(0, 100).toInt();
      }
    }

    int parseMatchCount(dynamic value) {
      final parsed = int.tryParse(value?.toString() ?? '');
      return parsed == null ? 0 : parsed.clamp(0, 1000).toInt();
    }

    final parsedMatchCriteriaCount = parseMatchCount(
      json['match_criteria_count'] ?? json['profile_match_criteria_count'],
    );
    final parsedMatchEvaluatedCriteriaCount = parseMatchCount(
      json['match_evaluated_criteria_count'] ??
          json['profile_match_evaluated_criteria_count'],
    );
    final rawMatchStatus = (json['match_status'] ??
            json['profile_match_status'] ??
            '')
        .toString()
        .trim();
    final parsedMatchStatus = rawMatchStatus.isNotEmpty
        ? rawMatchStatus
        : (parsedMatch != null
            ? 'calculated'
            : (parsedMatchCriteriaCount > 0
                ? 'insufficient_data'
                : 'no_preferences'));

    // 🟢 استخراج اسم الباقة الموحد والتأكد من قراءة كافة الاستجابات الممكنة
    final String parsedPackageName = _extractPackageName(json, basicInfo);

    if (kDebugMode) {
      debugPrint(
          '🔍 [MemberModel] ID: ${json['id'] ?? json['user_id']} | PackageName: "$parsedPackageName" | RawReligion: "$parsedRawReligion"');
    }

    return MemberModel(
      id: json['user_id'] ?? json['id'] ?? basicInfo['id'] ?? 0,
      code: (json['code'] ?? basicInfo['code'])?.toString() ?? '',
      name: fullName.isNotEmpty
          ? fullName
          : (json['name'] ??
              basicInfo['name'] ??
              (isArabic ? 'عضو' : 'Member')),
      image: parsedImage,
      age: parsedAge,
      rawCountry: parsedRawCountry,
      rawResidence: parsedRawResidence,
      rawOccupation: parsedRawOccupation,
      rawMaritalStatus: parsedRawMarital,
      rawReligion: parsedRawReligion,
      matchPercentage: parsedMatch,
      matchCriteriaCount: parsedMatchCriteriaCount,
      matchEvaluatedCriteriaCount: parsedMatchEvaluatedCriteriaCount,
      matchStatus: parsedMatchStatus,
      packageName: parsedPackageName,
    );
  }
}

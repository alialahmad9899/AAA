import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/package_catalog_utils.dart';

void main() {
  group('PackageCatalogUtils', () {
    test('sorts packages by admin order then by stable package id', () {
      final packages = PackageCatalogUtils.sortPackages([
        {'id': 8, 'sort_order': 30},
        {'id': 4, 'sort_order': 10},
        {'id': 2, 'sort_order': 10},
      ]);
      expect(packages.map((item) => item['id']).toList(), [2, 4, 8]);
    });

    test('uses package id as fallback when order is missing', () {    test('formats Arabic package validity with correct day grammar', () {
      expect(PackageCatalogUtils.arabicValidityDaysLabel(1), 'يوم واحد');
      expect(PackageCatalogUtils.arabicValidityDaysLabel(2), 'يومين');
      expect(PackageCatalogUtils.arabicValidityDaysLabel(7), '7 أيام');
      expect(PackageCatalogUtils.arabicValidityDaysLabel(11), '11 يوماً');
      expect(PackageCatalogUtils.arabicValidityDaysLabel(0), '0 يوم');
    });


      final packages = PackageCatalogUtils.sortPackages([
        {'id': 7},
        {'id': 3},
      ]);
      expect(packages.map((item) => item['id']).toList(), [3, 7]);
    });

    test('uses exact admin-managed names in Arabic and English', () {
      const package = {
        'name': 'Display fallback',
        'name_ar': 'الباقة العربية المخصصة',
        'name_en': 'Custom English Package',
      };
      expect(PackageCatalogUtils.displayName(package, isArabic: true),
          'الباقة العربية المخصصة');
      expect(PackageCatalogUtils.displayName(package, isArabic: false),
          'Custom English Package');
    });

    test('uses configured descriptions in the selected language', () {
      const package = {
        'description': 'Generic description',
        'description_ar': 'وصف عربي',
        'description_en': 'English description',
      };
      expect(PackageCatalogUtils.displayDescription(package, isArabic: true),
          'وصف عربي');
      expect(PackageCatalogUtils.displayDescription(package, isArabic: false),
          'English description');
    });

    test('uses bilingual feature titles with selected icons', () {
      const package = {
        'feature_items_ar': [
          {'title': 'ميزة عربية أولى', 'icon_key': 'chat'},
          {'title': 'ميزة عربية ثانية', 'icon_key': 'shield'},
        ],
        'feature_items_en': [
          {'title': 'First English benefit', 'icon_key': 'star'},
        ],
      };
      expect(
        PackageCatalogUtils.displayFeatureItems(package, isArabic: true),
        [
          {'title': 'ميزة عربية أولى', 'icon_key': 'chat'},
          {'title': 'ميزة عربية ثانية', 'icon_key': 'shield'},
        ],
      );
      expect(PackageCatalogUtils.displayFeatures(package, isArabic: false),
          ['First English benefit']);
    });

    test('reads legacy plain-string feature arrays and supplies a safe icon', () {
      const package = {'features_ar': ['ميزة قديمة', '  ', 'ميزة ثانية']};
      expect(
        PackageCatalogUtils.displayFeatureItems(package, isArabic: true),
        [
          {'title': 'ميزة قديمة', 'icon_key': 'check'},
          {'title': 'ميزة ثانية', 'icon_key': 'check'},
        ],
      );
    });

    test('uses shared feature rows if localized data is absent', () {
      const package = {
        'feature_items': [
          {'title': 'Shared feature', 'icon_key': 'favorite'},
        ],
      };
      expect(PackageCatalogUtils.displayFeatures(package, isArabic: false),
          ['Shared feature']);
    });

    test('reads admin-managed badge, style, icon, featured state and duration', () {
      const package = {
        'badge_text_ar': 'الباقة الملكية',
        'badge_text_en': 'Royal Plan',
        'badge_style': 'gold',
        'badge_icon_key': 'workspace_premium',
        'is_featured': 1,
        'duration_label_ar': 'اشتراك شهري',
        'duration_label_en': 'Monthly Plan',
      };
      expect(PackageCatalogUtils.displayBadgeText(package, isArabic: true),
          'الباقة الملكية');
      expect(PackageCatalogUtils.displayBadgeText(package, isArabic: false),
          'Royal Plan');
      expect(PackageCatalogUtils.badgeStyle(package), 'gold');
      expect(PackageCatalogUtils.badgeIconKey(package), 'workspace_premium');
      expect(PackageCatalogUtils.isFeatured(package), isTrue);
      expect(PackageCatalogUtils.displayDurationLabel(package, isArabic: true),
          'اشتراك شهري');
      expect(PackageCatalogUtils.displayDurationLabel(package, isArabic: false),
          'Monthly Plan');
    });

    test('uses localized API fallbacks when an explicit translation is blank', () {
      const package = {
        'badge_text_ar': '',
        'badge_text': 'اسم الشارة البديل',
        'duration_label_en': '',
        'duration_label': 'Monthly fallback',
      };
      expect(PackageCatalogUtils.displayBadgeText(package, isArabic: true),
          'اسم الشارة البديل');
      expect(
        PackageCatalogUtils.displayDurationLabel(package, isArabic: false),
        'Monthly fallback',
      );
    });

    test('falls back safely when badge settings are missing or unknown', () {
      const package = {'badge_style': 'purple'};
      expect(PackageCatalogUtils.badgeStyle(package), 'teal');
      expect(PackageCatalogUtils.badgeIconKey(package), 'workspace_premium');
      expect(PackageCatalogUtils.displayBadgeText(package, isArabic: true),
          'اشتراك مميز');
      expect(PackageCatalogUtils.isFeatured(package), isFalse);
    });
  });
}

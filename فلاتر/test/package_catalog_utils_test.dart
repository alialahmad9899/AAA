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

    test('uses package id as fallback when order is missing', () {
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

    test('uses configured benefits in the selected language', () {
      const package = {
        'features_ar': ['ميزة عربية أولى', 'ميزة عربية ثانية'],
        'features_en': ['First English benefit'],
      };

      expect(
        PackageCatalogUtils.displayFeatures(package, isArabic: true),
        ['ميزة عربية أولى', 'ميزة عربية ثانية'],
      );
      expect(
        PackageCatalogUtils.displayFeatures(package, isArabic: false),
        ['First English benefit'],
      );
    });

    test('falls back to the localized generic feature list', () {
      const package = {'features': ['Shared benefit']};

      expect(
        PackageCatalogUtils.displayFeatures(package, isArabic: true),
        ['Shared benefit'],
      );
    });

    test('uses the localized description', () {
      const package = {
        'description': 'Generic description',
        'description_ar': 'وصف عربي',
        'description_en': 'English description',
      };

      expect(
        PackageCatalogUtils.displayDescription(package, isArabic: true),
        'وصف عربي',
      );
      expect(
        PackageCatalogUtils.displayDescription(package, isArabic: false),
        'English description',
      );
    });
  });
}

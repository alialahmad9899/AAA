<?php

namespace Tests\Unit;

use App\Services\PackagePresentation;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/Services/PackagePresentation.php';

class PackagePresentationTest extends TestCase
{
    public function test_bilingual_feature_rows_keep_their_own_text_and_icon(): void
    {
        $rows = [
            [
                'title_ar' => 'مشاهدة زوار الملف',
                'title_en' => 'View profile visitors',
                'icon_key' => 'visibility',
            ],
            [
                'title_ar' => 'تواصل آمن',
                'title_en' => 'Safe contact',
                'icon_key' => 'shield',
            ],
        ];

        $arabic = PackagePresentation::normaliseFeatureItems($rows, 'ar');
        $english = PackagePresentation::normaliseFeatureItems($rows, 'en');

        $this->assertSame(
            [
                ['title' => 'مشاهدة زوار الملف', 'icon_key' => 'visibility'],
                ['title' => 'تواصل آمن', 'icon_key' => 'shield'],
            ],
            $arabic
        );
        $this->assertSame(
            [
                ['title' => 'View profile visitors', 'icon_key' => 'visibility'],
                ['title' => 'Safe contact', 'icon_key' => 'shield'],
            ],
            $english
        );
    }

    public function test_legacy_string_features_remain_visible_with_a_safe_default_icon(): void
    {
        $features = PackagePresentation::normaliseFeatureItems(
            ['الميزة الأولى', '  ', 'الميزة الثانية'],
            'ar'
        );

        $this->assertSame(
            [
                ['title' => 'الميزة الأولى', 'icon_key' => 'check'],
                ['title' => 'الميزة الثانية', 'icon_key' => 'check'],
            ],
            $features
        );
    }

    public function test_unknown_feature_icons_fall_back_to_check_and_limit_is_thirty(): void
    {
        $rows = [];
        for ($index = 0; $index < 35; $index++) {
            $rows[] = [
                'title_ar' => 'ميزة ' . $index,
                'title_en' => 'Feature ' . $index,
                'icon_key' => $index === 0 ? 'unknown_icon' : 'star',
            ];
        }

        $features = PackagePresentation::normaliseFeatureItems($rows, 'ar');

        $this->assertCount(30, $features);
        $this->assertSame('check', $features[0]['icon_key']);
        $this->assertSame('star', $features[1]['icon_key']);
    }

    public function test_missing_translation_falls_back_to_the_available_feature_title(): void
    {
        $rows = [
            [
                'title_ar' => 'إمكانية مشاهدة الزوار',
                'title_en' => '',
                'icon_key' => 'visibility',
            ],
        ];

        $english = PackagePresentation::normaliseFeatureItems($rows, 'en');

        $this->assertSame(
            [['title' => 'إمكانية مشاهدة الزوار', 'icon_key' => 'visibility']],
            $english
        );
    }

    public function test_badge_style_is_limited_to_supported_palette_values(): void
    {
        $this->assertSame('silver', PackagePresentation::normaliseBadgeStyle('silver'));
        $this->assertSame('gold', PackagePresentation::normaliseBadgeStyle('GOLD'));
        $this->assertSame('teal', PackagePresentation::normaliseBadgeStyle('teal'));
        $this->assertSame('teal', PackagePresentation::normaliseBadgeStyle('purple'));
        $this->assertSame('teal', PackagePresentation::normaliseBadgeStyle(null));
    }
}

<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SubscriptionUtilityProfileViewerQuotaTest extends TestCase
{
    private function methodBody(string $source, string $start, string $end): string
    {
        $startAt = strpos($source, $start);
        self::assertNotFalse($startAt, 'Method start not found: ' . $start);
        $endAt = strpos($source, $end, $startAt + strlen($start));
        self::assertNotFalse($endAt, 'Method end not found: ' . $end);

        return substr($source, $startAt, $endAt - $startAt);
    }

    public function test_subscription_utility_imports_the_existing_profile_viewer_model(): void
    {
        $utilityPath = __DIR__ . '/../../app/Utility/SubscriptionUtility.php';
        $modelPath = __DIR__ . '/../../app/Models/ProfileViewer.php';

        $utility = file_get_contents($utilityPath);
        $model = file_get_contents($modelPath);

        self::assertIsString($utility);
        self::assertIsString($model);
        self::assertStringContainsString('namespace App\\Utility;', $utility);
        self::assertStringContainsString('use App\\Models\\ProfileViewer;', $utility);
        self::assertStringContainsString('namespace App\\Models;', $model);
        self::assertStringContainsString('class ProfileViewer extends Model', $model);
    }

    public function test_free_profile_viewer_allowance_queries_the_model_inside_the_quota_guard(): void
    {
        $path = __DIR__ . '/../../app/Utility/SubscriptionUtility.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $withQuota = $this->methodBody(
            $source,
            'public static function withQuota(',
            'public static function lifetimeMessageRecipientLimit('
        );

        self::assertStringContainsString(
            "$"."freeProfileViewerAllowance = !$"."isFemale",
            $withQuota
        );
        self::assertStringContainsString(
            "ProfileViewer::where('user_id', $"."user->id)->count() >= 3",
            $withQuota
        );
        self::assertStringContainsString(
            "if ($"."freeProfileViewerAllowance)",
            $withQuota
        );
        self::assertStringContainsString(
            "ProfileViewer::create([",
            file_get_contents(__DIR__ . '/../../app/Http/Controllers/Api/ProfileController.php')
        );
    }
}

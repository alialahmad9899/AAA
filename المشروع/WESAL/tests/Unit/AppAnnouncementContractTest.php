<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AppAnnouncementContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    private function sourceFlutter(string $relativePath): string
    {
        $path = dirname(__DIR__, 4) . '/فلاتر/lib/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    public function test_schema_and_member_targeting_are_defined(): void
    {
        $migration = $this->source('database/migrations/2026_11_20_000002_create_app_announcements_table.php');
        $model = $this->source('app/Models/AppAnnouncement.php');

        foreach ([
            "'announcement_type'",
            "'title_ar'",
            "'message_ar'",
            "'title_en'",
            "'message_en'",
            "'target_type'",
            "'target_gender'",
            "'age_min'",
            "'age_max'",
            "'button_enabled'",
            "'button_url'",
            "'is_active'",
        ] as $column) {
            $this->assertStringContainsString($column, $migration);
        }

        $this->assertStringContainsString('function matchesMember(?Member $member): bool', $model);
        $this->assertStringContainsString('(int) $member->gender === 1', $model);
        $this->assertStringContainsString('(int) $member->gender === 2', $model);
        $this->assertStringContainsString('$targetGender === \'male\'', $model);
        $this->assertStringContainsString('$targetGender === \'female\'', $model);
        $this->assertStringContainsString('Carbon::parse($member->birthday)->age', $model);
        $this->assertStringContainsString('$age >= (int) $this->age_min && $age <= (int) $this->age_max', $model);
    }

    public function test_existing_announcement_rows_receive_a_safe_gender_column_migration(): void
    {
        $migration = $this->source('database/migrations/2026_11_20_000003_add_target_gender_to_app_announcements_table.php');

        $this->assertStringContainsString("Schema::hasTable('app_announcements')", $migration);
        $this->assertStringContainsString("Schema::hasColumn('app_announcements', 'target_gender')", $migration);
        $this->assertStringContainsString("->default('all')", $migration);
        $this->assertStringContainsString("->after('target_type')", $migration);
        $this->assertStringContainsString('Keep audience settings intact on rollback', $migration);
    }

    public function test_member_api_is_scoped_to_authenticated_active_members(): void
    {
        $routes = $this->source('routes/api.php');
        $controller = $this->source('app/Http/Controllers/Api/AppAnnouncementController.php');
        $memberGate = strpos($routes, "Route::group(['middleware' => ['auth:sanctum', 'api_member']]");
        $memberPrefix = strpos($routes, "Route::group(['prefix' => 'member'], function () {", $memberGate);
        $announcementRoute = strpos($routes, "Route::get('/app-announcement', 'AppAnnouncementController@show')");

        $this->assertNotFalse($memberGate);
        $this->assertNotFalse($memberPrefix);
        $this->assertNotFalse($announcementRoute);
        $this->assertGreaterThan($memberGate, $memberPrefix);
        $this->assertGreaterThan($memberPrefix, $announcementRoute);
        $this->assertStringContainsString('$announcement->matchesMember($user->member)', $controller);
        $this->assertStringContainsString("'button_url'", $controller);
    }

    public function test_admin_routes_and_form_are_restricted_and_validate_targeting(): void
    {
        $routes = $this->source('routes/admin.php');
        $controller = $this->source('app/Http/Controllers/Admin/AppAnnouncementController.php');
        $view = $this->source('resources/views/admin/app_announcements/index.blade.php');

        $adminGate = strpos($routes, "Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']]");
        $editRoute = strpos($routes, "Route::get('/app-announcement'");
        $this->assertNotFalse($adminGate);
        $this->assertNotFalse($editRoute);
        $this->assertGreaterThan($adminGate, $editRoute);
        $this->assertStringContainsString("user_type === 'admin'", $controller);
        $this->assertStringContainsString("'required_if:target_type,age'", $controller);
        $this->assertStringContainsString("'target_gender' => ['nullable', 'required_if:target_type,age', 'in:all,male,female']", $controller);
        $this->assertStringContainsString("'target_gender' => \$validated['target_type'] === 'age'", $controller);
        $this->assertStringContainsString("'required_if:button_enabled,1'", $controller);
        $this->assertStringContainsString("['http', 'https']", $controller);
        $this->assertStringContainsString('button_enabled', $view);
        $this->assertStringContainsString('target_type', $view);
        $this->assertStringContainsString('name="target_gender"', $view);
        $this->assertStringContainsString('الجنس ضمن الفئة العمرية', $view);
    }

    public function test_flutter_parses_safe_action_and_loads_popup_once_after_block_check(): void
    {
        $model = $this->sourceFlutter('models/app_announcement.dart');
        $apiService = $this->sourceFlutter('services/api_service.dart');
        $home = $this->sourceFlutter('screens/main_home_screen.dart');

        $this->assertStringContainsString("get('/member/app-announcement'", $apiService);
        $this->assertStringContainsString('unawaited(_loadAndShowAppAnnouncement())', $home);
        $this->assertStringContainsString('_hasCheckedAppAnnouncement', $home);
        $this->assertStringContainsString('announcement.hasButton', $home);
        $this->assertStringContainsString('launchUrl(', $home);
        $this->assertStringContainsString("uri.scheme == 'https' || uri.scheme == 'http'", $model);
    }
}

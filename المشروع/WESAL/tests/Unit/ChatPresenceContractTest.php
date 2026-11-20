<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ChatPresenceContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    public function test_mobile_api_activity_refreshes_the_chat_online_marker(): void
    {
        $middleware = $this->source('app/Http/Middleware/IsApiMember.php');

        $this->assertStringContainsString("'user-is-online-' . \$user->id", $middleware);
        $this->assertStringContainsString("Cache::forever(", $middleware);
        $this->assertStringContainsString("'wesal:last-seen:' . \$user->id", $middleware);
        $this->assertStringContainsString("addMinutes(3)", $middleware);
    }

    public function test_mobile_lifecycle_can_mark_offline_without_confusing_chat_exit(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/NotificationController.php');

        $this->assertStringContainsString("'app_active' => 'sometimes|boolean'", $controller);
        $appActivityContract = <<<'SOURCE'
if ($appIsActive) {
            Cache::put($onlineKey, true, $activityAt->copy()->addMinutes(3));
        } else {
            Cache::forget($onlineKey);
        }
SOURCE;
        $this->assertStringContainsString($appActivityContract, $controller);
        $this->assertStringContainsString("Cache::forget('wesal:active-chat:' . \$userId)", $controller);
    }

    public function test_chat_resources_use_cached_app_presence_and_real_last_seen(): void
    {
        $threadResource = $this->source('app/Http/Resources/ChatThreadResource.php');
        $chatResource = $this->source('app/Http/Resources/ChatResource.php');

        $this->assertStringContainsString("Cache::has('user-is-online-' . \$targetUser->id)", $threadResource);
        $this->assertStringContainsString("'last_seen_at'", $threadResource);
        $this->assertStringNotContainsString("Carbon::parse(\$targetUser->updated_at)->diffForHumans()", $threadResource);
        $this->assertStringContainsString("'partner_is_online'", $chatResource);
        $this->assertStringContainsString("'wesal:last-seen:' . \$partnerUser->id", $chatResource);
    }
    public function test_last_seen_is_persisted_and_read_from_a_durable_user_column(): void
    {
        $middleware = $this->source('app/Http/Middleware/IsApiMember.php');
        $threadResource = $this->source('app/Http/Resources/ChatThreadResource.php');
        $chatResource = $this->source('app/Http/Resources/ChatResource.php');
        $migration = $this->source('database/migrations/2026_11_20_000004_add_last_seen_at_to_users_table.php');

        $this->assertStringContainsString("Schema::hasColumn('users', 'last_seen_at')", $middleware);
        $this->assertStringContainsString("DB::table('users')", $middleware);
        $this->assertStringContainsString("'last_seen_at' => \$activityAt->toDateTimeString()", $middleware);
        $this->assertStringContainsString("Cache::add(", $middleware);
        $this->assertStringContainsString("getAttribute('last_seen_at')", $threadResource);
        $this->assertStringContainsString("getAttribute('last_seen_at')", $chatResource);
        $this->assertStringContainsString("Schema::hasColumn('users', 'last_seen_at')", $migration);
    }

}

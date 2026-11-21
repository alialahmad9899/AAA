<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ChatMuteContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    public function test_mute_preference_is_stored_per_user_and_partner(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/NotificationController.php');
        $model = $this->source('app/Models/ChatMute.php');
        $migration = $this->source('database/migrations/2026_11_22_000002_create_chat_mutes_table.php');

        $this->assertStringContainsString('ChatMute::updateOrCreate', $controller);
        $this->assertStringContainsString(<<<'CODE'
'muted_by' => $userId
CODE, $controller);
        $this->assertStringContainsString(<<<'CODE'
'muted_user_id' => $partnerUserId
CODE, $controller);
        $this->assertStringContainsString('protected $guarded = [];', $model);
        $this->assertStringContainsString(<<<'CODE'
unique(['muted_by', 'muted_user_id']
CODE, $migration);
    }

    public function test_chat_push_respects_durable_mute_and_mutual_block(): void
    {
        $service = $this->source('app/Services/ChatService.php');

        $this->assertStringContainsString('ChatMute::query()', $service);
        $this->assertStringContainsString(<<<'CODE'
->where('muted_by', $receiverId)
CODE, $service);
        $this->assertStringContainsString(<<<'CODE'
->where('muted_user_id', $senderUserId)
CODE, $service);
        $this->assertStringContainsString('IgnoredUser::query()', $service);
        $this->assertStringContainsString('|| $isMuted || $isBlocked', $service);
    }

    public function test_chat_view_returns_the_current_mute_state(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ChatController.php');

        $this->assertStringContainsString("'muted_by_me'", $controller);
        $this->assertStringContainsString('ChatMute::where', $controller);
        $this->assertStringContainsString("'partner_user_id' => $partnerUserId", $this->source('app/Http/Controllers/Api/NotificationController.php'));
    }

    public function test_block_removal_does_not_delete_mute_preferences(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/MemberController.php');
        $start = strpos($controller, 'public function remove_from_ignored_list');
        $end = strpos($controller, 'public function report_member', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $method = substr($controller, $start, $end - $start);
        $this->assertStringContainsString('IgnoredUser::destroy', $method);
        $this->assertStringNotContainsString('ChatMute::', $method);
    }
}

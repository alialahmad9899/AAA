<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ChatMessageModerationContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    public function test_message_delete_is_limited_to_the_authored_message_in_an_owned_thread(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ChatController.php');
        $routes = $this->source('routes/api.php');

        $start = strpos($controller, 'public function chat_message_delete');
        $end = strpos($controller, 'public function chat_delete', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $method = substr($controller, $start, $end - $start);

        $this->assertStringContainsString(<<<'CODE'
->where('sender_user_id', $userId)
CODE, $method);
        $this->assertStringContainsString(<<<'CODE'
->where('chat_thread_id', $thread->id)
CODE, $method);
        $this->assertStringContainsString('$message->delete();', $method);
        $this->assertStringContainsString("Route::post('/chat-message-delete', 'ChatController@chat_message_delete')", $routes);
    }

    public function test_selected_message_report_is_verified_against_the_real_thread_and_sender(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/MemberController.php');

        $this->assertStringContainsString(<<<'CODE'
->where('chat_thread_id', $thread->id)
CODE, $controller);
        $this->assertStringContainsString(<<<'CODE'
->where('sender_user_id', $targetUserId)
CODE, $controller);
        $this->assertStringContainsString("'reported_message_id'", $controller);
        $this->assertStringContainsString("'is_reported_item' => true", $controller);
        $this->assertStringContainsString("'attachment_refs' => $attachmentEvidence['attachment_refs']", $controller);
    }

    public function test_selected_message_reports_append_evidence_without_trusting_client_content(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/MemberController.php');

        $this->assertStringContainsString('$reportedUser->chat_evidence = array_values([', $controller);
        $this->assertStringContainsString('$reportedMessage->attachment', $controller);
        $this->assertStringContainsString("'attachment_refs' => $attachmentEvidence['attachment_refs']", $controller);
        $this->assertStringNotContainsString(<<<'CODE'
$request->input('message')
CODE, $controller);

        $adminView = $this->source('resources/views/admin/members/reported_members.blade.php');
        $this->assertStringContainsString(<<<'CODE'
uploaded_asset($attachmentId)
CODE, $adminView);
        $this->assertStringContainsString('Specific reported', $adminView);
    }
}

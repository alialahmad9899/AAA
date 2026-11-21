<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ChatDeliveryContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    public function test_mobile_chat_reply_returns_the_serialized_message_resource(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ChatController.php');
        $resource = $this->source('app/Http/Resources/Chat/ChatViewResource.php');

        $expectedControllerSnippet = <<<'SOURCE'
'data' => (new ChatViewResource($new_chat))->resolve($request)
SOURCE;
        $this->assertStringContainsString($expectedControllerSnippet, $controller);

        $expectedResourceFields = [
            <<<'SOURCE'
'id'             => $this->id
SOURCE,
            <<<'SOURCE'
'sender_user_id' => $this->sender_user_id
SOURCE,
            <<<'SOURCE'
'attachment'     => $attachments
SOURCE,
            <<<'SOURCE'
'created_at'     => optional($this->created_at)->toIso8601String()
SOURCE,
        ];
        foreach ($expectedResourceFields as $field) {
            $this->assertStringContainsString($field, $resource);
        }
    }

    public function test_mobile_chat_photos_require_a_real_reply_from_the_other_participant(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ChatController.php');

        $replyCheck = strpos($controller, '$has_receiver_replied = Chat::where');
        $expectedGuard = <<<'SOURCE'
if ($request->hasFile('attachment') && !$has_receiver_replied)
SOURCE;
        $attachmentGuard = strpos($controller, $expectedGuard);
        $uploadStep = strpos($controller, '// 5. رفع المرفقات إن وجدت');

        $this->assertNotFalse($replyCheck);
        $this->assertNotFalse($attachmentGuard);
        $this->assertNotFalse($uploadStep);
        $this->assertLessThan($uploadStep, $attachmentGuard);
        $this->assertGreaterThan($replyCheck, $attachmentGuard);
        $this->assertStringContainsString(
            "'status' => 'image_requires_partner_reply'",
            $controller
        );
        $expectedOtherParticipantFilter = <<<'SOURCE'
->where('sender_user_id', '!=', $sender_id)
SOURCE;
        $this->assertStringContainsString($expectedOtherParticipantFilter, $controller);
    }
}

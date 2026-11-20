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
}

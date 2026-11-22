<?php

namespace Tests\\Unit;

use PHPUnit\\Framework\\TestCase;

class ChatMessageIdempotencyContractTest extends TestCase
{
    public function test_chat_api_returns_existing_message_for_same_sender_thread_and_client_key(): void
    {
        $path = __DIR__ . '/../../app/Http/Controllers/Api/ChatController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        self::assertStringContainsString("'client_message_id' =>", $source);
        self::assertStringContainsString("->where('chat_thread_id', $ownedThread->id)", $source);
        self::assertStringContainsString("->where('sender_user_id', $sender_id)", $source);
        self::assertStringContainsString("->where('client_message_id', $clientMessageId)", $source);
        self::assertStringContainsString("'idempotent_replay' => true", $source);
    }

    public function test_database_enforces_a_unique_client_message_key_per_sender(): void
    {
        $path = __DIR__ . '/../../database/migrations/2026_11_22_000003_add_client_message_id_to_chats_table.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        self::assertStringContainsString("Schema::hasColumn('chats', 'client_message_id')", $source);
        self::assertStringContainsString("['sender_user_id', 'client_message_id']", $source);
        self::assertStringContainsString("'chats_sender_client_message_unique'", $source);
    }
}

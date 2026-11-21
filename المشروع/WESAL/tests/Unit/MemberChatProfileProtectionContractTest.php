<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MemberChatProfileProtectionContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    public function test_public_profile_resolves_internal_id_first_then_exact_membership_code(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ProfileController.php');

        $this->assertStringContainsString("(clone \$profileQuery)->where('id', \$id)->first()", $controller);
        $this->assertStringContainsString("\$profileQuery->where('code', (string) \$id)->first()", $controller);

        $this->assertStringContainsString("\$data['user_id'] = (int) \$user->id;", $controller);
        $this->assertStringContainsString("Address::where('user_id', \$user->id)", $controller);
    }

    public function test_chat_reports_store_verified_server_side_evidence_for_admin_review(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/MemberController.php');
        $model = $this->source('app/Models/ReportedUser.php');
        $migration = $this->source('database/migrations/2026_11_22_000001_add_chat_evidence_to_reported_users_table.php');
        $adminView = $this->source('resources/views/admin/members/reported_members.blade.php');

        $this->assertStringContainsString("input('chat_thread_id', 0)", $controller);
        $this->assertStringContainsString("->whereKey(\$chatThreadId)", $controller);
        $this->assertStringContainsString("->limit(5)", $controller);
        $this->assertStringContainsString("'chat_evidence' => \$chatEvidence", $controller);
        $this->assertStringContainsString("'chat_evidence' => 'array'", $model);
        $this->assertStringContainsString("longText('chat_evidence')->nullable()", $migration);
        $this->assertStringContainsString("\$report->chat_evidence", $adminView);
        $this->assertStringContainsString("\$evidenceMessage['message']", $adminView);
    }

    public function test_blocked_participants_are_rejected_by_the_server_and_history_is_not_deleted(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ChatController.php');
        $mobile = file_get_contents(dirname(__DIR__, 4) . '/فلاتر/lib/screens/chat_detail_screen.dart');
        $this->assertIsString($mobile);

        $this->assertStringContainsString('private function hasBlockedParticipant', $controller);
        $this->assertStringContainsString('IgnoredUser::query()', $controller);
        $this->assertStringContainsString("'status' => 'chat_blocked'", $controller);

        $blockStart = strpos($mobile, 'Future<void> _executeBlock() async {');
        $reportStart = strpos($mobile, 'Future<void> _executeReport(', $blockStart);
        $this->assertNotFalse($blockStart);
        $this->assertNotFalse($reportStart);
        $blockMethod = substr($mobile, $blockStart, $reportStart - $blockStart);
        $this->assertStringNotContainsString('deleteChatThread', $blockMethod);
        $this->assertStringNotContainsString('ChatLocalStore.deleteThread', $blockMethod);
    }
}

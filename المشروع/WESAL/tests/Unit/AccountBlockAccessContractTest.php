<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AccountBlockAccessContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    public function test_admin_block_saves_reason_and_notifies_only_on_new_block(): void
    {
        $controller = $this->source('app/Http/Controllers/MemberController.php');

        $reasonAssignment = <<<'SOURCE'
$member->blocked_reason = $status === 1 ? $reason : '';
SOURCE;
        $transitionCondition = <<<'SOURCE'
'just_blocked' => $status === 1 && !$wasBlocked,
SOURCE;
        $this->assertStringContainsString($reasonAssignment, $controller);
        $this->assertStringContainsString($transitionCondition, $controller);
        $this->assertStringContainsString('NotificationUtility::send_notification(', $controller);
        $this->assertStringContainsString("'account_blocked'", $controller);
        $this->assertStringContainsString("'title' => 'تم حظر حسابك'", $controller);
    }

    public function test_block_status_route_is_outside_member_api_gate(): void
    {
        $routes = $this->source('routes/api.php');
        $controller = $this->source('app/Http/Controllers/Api/ProfileController.php');

        $routePosition = strpos($routes, "Route::get('/member/blocked-status'");
        $memberGatePosition = strpos($routes, "Route::group(['middleware' => ['auth:sanctum', 'api_member']]");
        $this->assertNotFalse($routePosition);
        $this->assertNotFalse($memberGatePosition);
        $this->assertLessThan($memberGatePosition, $routePosition);
        $this->assertStringContainsString("'blocking_reason'", $controller);
        $this->assertStringContainsString('Member::withTrashed()', $controller);
    }

    public function test_blocked_support_endpoint_is_auth_only_and_rejects_unblocked_accounts(): void
    {
        $routes = $this->source('routes/api.php');
        $controller = $this->source('app/Http/Controllers/Api/SupportTicketController.php');

        $routePosition = strpos($routes, "Route::post('/member/blocked-support-message'");
        $memberGatePosition = strpos($routes, "Route::group(['middleware' => ['auth:sanctum', 'api_member']]");
        $this->assertNotFalse($routePosition);
        $this->assertNotFalse($memberGatePosition);
        $this->assertLessThan($memberGatePosition, $routePosition);
        $blockedGuard = <<<'SOURCE'
(int) $request->user()->blocked !== 1
SOURCE;
        $this->assertStringContainsString($blockedGuard, $controller);
        $this->assertStringContainsString('$this->store($request)', $controller);
    }
}

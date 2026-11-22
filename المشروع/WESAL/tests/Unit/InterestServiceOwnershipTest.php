<?php

namespace Tests\\Unit;

use PHPUnit\\Framework\\TestCase;

class InterestServiceOwnershipTest extends TestCase
{
    private function methodSource(string $method, string $nextMethod): string
    {
        $path = __DIR__ . '/../../app/Services/InterestService.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $start = strpos($source, 'public function ' . $method . '(');
        self::assertNotFalse($start);
        $end = strpos($source, 'public function ' . $nextMethod . '(', $start);
        self::assertNotFalse($end);

        return substr($source, $start, $end - $start);
    }

    public function test_accept_only_mutates_a_pending_incoming_request_owned_by_actor(): void
    {
        $method = $this->methodSource('accept', 'reject');

        self::assertStringContainsString('DB::transaction(', $method);
        self::assertStringContainsString("where('user_id', $actorId)", $method);
        self::assertStringContainsString('lockForUpdate()', $method);
        self::assertStringContainsString('(int) $interest->status !== 0', $method);
        self::assertStringContainsString('$interest->status = 1', $method);
    }

    public function test_reject_only_deletes_a_pending_incoming_request_owned_by_actor(): void
    {
        $path = __DIR__ . '/../../app/Services/InterestService.php';
        $source = file_get_contents($path);
        self::assertIsString($source);
        $start = strpos($source, 'public function reject(');
        $end = strpos($source, 'public function notifyUser(', $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $method = substr($source, $start, $end - $start);

        self::assertStringContainsString('DB::transaction(', $method);
        self::assertStringContainsString("where('user_id', $actorId)", $method);
        self::assertStringContainsString('lockForUpdate()', $method);
        self::assertStringContainsString('(int) $interest->status !== 0', $method);
        self::assertStringContainsString('$interest->delete()', $method);
    }

    public function test_api_requires_a_positive_interest_id_for_both_actions(): void
    {
        $path = __DIR__ . '/../../app/Http/Controllers/Api/InterestController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        self::assertSame(2, substr_count($source, "'interest_id' => ['required', 'integer', 'min:1']"));
    }
}

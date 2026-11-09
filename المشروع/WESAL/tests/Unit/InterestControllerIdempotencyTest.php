<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class InterestControllerIdempotencyTest extends TestCase
{
    public function test_existing_interest_is_checked_before_quota_and_concurrent_duplicates_are_rechecked(): void
    {
        $path = __DIR__ . '/../../app/Http/Controllers/Api/InterestController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $methodStart = strpos($source, 'public function express_interest(Request $request)');
        self::assertNotFalse($methodStart);
        $methodEnd = strpos($source, 'public function interest_requests()', $methodStart);
        self::assertNotFalse($methodEnd);
        $method = substr($source, $methodStart, $methodEnd - $methodStart);

        $targetValidation = strpos($method, '$target_user = User::where');
        $firstDuplicate = strpos($method, '$already_sent =');
        $quotaCheck = strpos($method, 'MemberUtility::can_express_interest($sender_id)');
        $serviceCall = strpos($method, '$interestService->store($target_id)');
        $secondDuplicate = strpos($method, '$already_sent =', $firstDuplicate + 1);

        self::assertNotFalse($targetValidation);
        self::assertNotFalse($firstDuplicate);
        self::assertNotFalse($quotaCheck);
        self::assertNotFalse($serviceCall);
        self::assertNotFalse($secondDuplicate);
        self::assertLessThan($firstDuplicate, $targetValidation);
        self::assertLessThan($quotaCheck, $firstDuplicate);
        self::assertLessThan($serviceCall, $quotaCheck);
        self::assertLessThan($secondDuplicate, $serviceCall);
        self::assertStringContainsString("'status'  => 'already_expressed'", $method);
        self::assertStringContainsString("'status'  => 'package_required'", $method);
    }
}

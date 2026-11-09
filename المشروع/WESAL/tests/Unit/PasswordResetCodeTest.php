<?php

namespace Tests\Unit;

use App\Security\PasswordResetCode;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/Security/PasswordResetCode.php';

class PasswordResetCodeTest extends TestCase
{
    public function test_a_valid_unexpired_code_is_accepted(): void
    {
        $issued = PasswordResetCode::forCode('012345');

        self::assertTrue(PasswordResetCode::isValid(
            '012345',
            $issued['hash'],
            $issued['expires_at']
        ));
    }

    public function test_a_wrong_code_is_rejected(): void
    {
        $issued = PasswordResetCode::forCode('012345');

        self::assertFalse(PasswordResetCode::isValid(
            '543210',
            $issued['hash'],
            $issued['expires_at']
        ));
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $issued = PasswordResetCode::forCode('012345');

        self::assertFalse(PasswordResetCode::isValid(
            '012345',
            $issued['hash'],
            new DateTimeImmutable('-1 minute')
        ));
    }

    public function test_missing_hash_and_expiry_never_validate(): void
    {
        self::assertFalse(PasswordResetCode::isValid('000000', null, null));
        self::assertFalse(PasswordResetCode::isValid('000000', '', new DateTimeImmutable('+5 minutes')));
    }

    public function test_issued_codes_are_six_digits_and_stored_as_a_hash(): void
    {
        $issued = PasswordResetCode::issue();

        self::assertMatchesRegularExpression('/^\\d{6}$/', $issued['code']);
        self::assertNotSame($issued['code'], $issued['hash']);
        self::assertTrue(password_verify($issued['code'], $issued['hash']));
    }

    public function test_only_four_to_six_digit_codes_can_be_hashed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PasswordResetCode::forCode('123');
    }
}

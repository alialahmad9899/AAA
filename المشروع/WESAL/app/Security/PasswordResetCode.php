<?php

namespace App\Security;

use DateTimeImmutable;
use DateTimeInterface;

class PasswordResetCode
{
    /**
     * @return array{code: string, hash: string, expires_at: DateTimeImmutable}
     */
    public static function issue(int $lifetimeMinutes = 10): array
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return self::forCode($code, $lifetimeMinutes);
    }

    /**
     * Hash an already-issued code. Used when an SMS provider generates the code.
     *
     * @return array{code: string, hash: string, expires_at: DateTimeImmutable}
     */
    public static function forCode(string $code, int $lifetimeMinutes = 10): array
    {
        if (!preg_match('/^\d{4,6}$/D', $code)) {
            throw new \InvalidArgumentException('A reset code must contain 4 to 6 digits.');
        }

        $hash = password_hash($code, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') {
            throw new \RuntimeException('Unable to hash the password reset code.');
        }

        return [
            'code' => $code,
            'hash' => $hash,
            'expires_at' => new DateTimeImmutable('+' . max(1, $lifetimeMinutes) . ' minutes'),
        ];
    }

    public static function isValid(
        string $code,
        ?string $hash,
        ?DateTimeInterface $expiresAt
    ): bool {
        if (!preg_match('/^\d{4,6}$/D', $code)
            || !is_string($hash)
            || $hash === ''
            || !$expiresAt) {
            return false;
        }

        if ($expiresAt->getTimestamp() <= time()) {
            return false;
        }

        return password_verify($code, $hash);
    }
}

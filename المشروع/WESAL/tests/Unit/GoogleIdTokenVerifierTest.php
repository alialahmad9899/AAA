<?php

namespace Tests\Unit;

use App\Security\GoogleIdTokenVerifier;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/Security/GoogleIdTokenVerifier.php';

class GoogleIdTokenVerifierTest extends TestCase
{
    private $privateKey;
    private string $certificate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        self::assertNotFalse($this->privateKey);

        $request = openssl_csr_new(['commonName' => 'Wesal Google token verifier tests'], $this->privateKey, [
            'digest_alg' => 'sha256',
        ]);
        self::assertNotFalse($request);

        $certificate = openssl_csr_sign($request, null, $this->privateKey, 1, [
            'digest_alg' => 'sha256',
        ]);
        self::assertNotFalse($certificate);

        $exported = '';
        self::assertTrue(openssl_x509_export($certificate, $exported));
        $this->certificate = $exported;
    }

    public function test_accepts_a_valid_google_identity_token(): void
    {
        $token = $this->makeToken($this->validClaims());
        $claims = $this->verifier()->verify($token);

        self::assertIsArray($claims);
        self::assertSame('google-subject-123', $claims['sub']);
        self::assertSame('member@gmail.com', $claims['email']);
    }

    public function test_rejects_a_token_for_an_unconfigured_audience(): void
    {
        $claims = $this->validClaims();
        $claims['aud'] = 'untrusted-client-id';

        self::assertNull($this->verifier()->verify($this->makeToken($claims)));
    }

    public function test_rejects_an_expired_token(): void
    {
        $claims = $this->validClaims();
        $claims['exp'] = time() - 30;

        self::assertNull($this->verifier()->verify($this->makeToken($claims)));
    }

    public function test_rejects_a_token_with_unverified_email(): void
    {
        $claims = $this->validClaims();
        $claims['email_verified'] = false;

        self::assertNull($this->verifier()->verify($this->makeToken($claims)));
    }

    public function test_rejects_tampered_claims_even_when_the_header_is_valid(): void
    {
        $segments = explode('.', $this->makeToken($this->validClaims()));
        $payload = json_decode($this->decode($segments[1]), true);
        $payload['email'] = 'victim@example.com';
        $segments[1] = $this->encode(json_encode($payload));

        self::assertNull($this->verifier()->verify(implode('.', $segments)));
    }

    public function test_rejects_an_empty_or_malformed_token(): void
    {
        self::assertNull($this->verifier()->verify(null));
        self::assertNull($this->verifier()->verify('not-a-jwt'));
        self::assertNull($this->verifier()->verify(''));
    }

    /**
     * @return array<string, mixed>
     */
    private function validClaims(): array
    {
        $now = time();

        return [
            'iss' => 'https://accounts.google.com',
            'aud' => 'wesal-test-client',
            'sub' => 'google-subject-123',
            'email' => 'member@gmail.com',
            'email_verified' => true,
            'iat' => $now,
            'exp' => $now + 600,
        ];
    }

    private function verifier(): GoogleIdTokenVerifier
    {
        return new GoogleIdTokenVerifier(
            fn (): array => ['test-key-id' => $this->certificate],
            ['wesal-test-client']
        );
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function makeToken(array $claims): string
    {
        $header = $this->encode(json_encode(['alg' => 'RS256', 'kid' => 'test-key-id']));
        $payload = $this->encode(json_encode($claims));
        $signedContent = $header . '.' . $payload;

        $signature = '';
        self::assertTrue(openssl_sign($signedContent, $signature, $this->privateKey, OPENSSL_ALGO_SHA256));

        return $signedContent . '.' . $this->encode($signature);
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        return (string) base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding));
    }
}

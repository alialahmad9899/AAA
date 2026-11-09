<?php

namespace App\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleIdTokenVerifier
{
    private const CERTIFICATE_CACHE_KEY = 'wesal.google_id_token_certificates.v1';

    /** @var callable|null */
    private $certificateProvider;

    /** @var array<int, string> */
    private array $clientIds;

    /**
     * The optional provider is intended for deterministic unit tests.
     *
     * @param callable|null $certificateProvider Returns [key-id => PEM certificate].
     * @param array<int, string>|null $clientIds
     */
    public function __construct(?callable $certificateProvider = null, ?array $clientIds = null)
    {
        $this->certificateProvider = $certificateProvider;
        $configuredIds = $clientIds ?? config('services.google.client_ids', []);
        $this->clientIds = array_values(array_unique(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            is_array($configuredIds) ? $configuredIds : []
        ))));
    }

    /**
     * Verify the Google JWT signature and the claims used for account identity.
     *
     * @return array<string, mixed>|null
     */
    public function verify(?string $idToken): ?array
    {
        if (!is_string($idToken) || $idToken === '' || strlen($idToken) > 8192 || $this->clientIds === []) {
            return null;
        }

        $segments = explode('.', $idToken);
        if (count($segments) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;
        $headerJson = $this->decodeBase64Url($encodedHeader);
        $payloadJson = $this->decodeBase64Url($encodedPayload);
        $signature = $this->decodeBase64Url($encodedSignature);

        if ($headerJson === false || $payloadJson === false || $signature === false) {
            return null;
        }

        $header = json_decode($headerJson, true);
        $claims = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($claims)) {
            return null;
        }

        if (($header['alg'] ?? null) !== 'RS256' || empty($header['kid']) || !is_string($header['kid'])) {
            return null;
        }

        try {
            $certificates = $this->getCertificates();
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }

        $certificate = $certificates[$header['kid']] ?? null;
        if (!is_string($certificate) || $certificate === '') {
            return null;
        }

        $publicKey = openssl_pkey_get_public($certificate);
        if ($publicKey === false) {
            return null;
        }

        $signedContent = $encodedHeader . '.' . $encodedPayload;
        $signatureIsValid = openssl_verify(
            $signedContent,
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        ) === 1;

        if (!$signatureIsValid || !$this->claimsAreValid($claims)) {
            return null;
        }

        return $claims;
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function claimsAreValid(array $claims): bool
    {
        $issuer = $claims['iss'] ?? null;
        if (!in_array($issuer, ['https://accounts.google.com', 'accounts.google.com'], true)) {
            return false;
        }

        $audience = $claims['aud'] ?? null;
        $audiences = is_array($audience) ? $audience : [$audience];
        $audiences = array_filter($audiences, 'is_string');

        if (array_intersect($this->clientIds, $audiences) === []) {
            return false;
        }

        if (count($audiences) > 1) {
            $authorizedParty = $claims['azp'] ?? null;
            if (!is_string($authorizedParty) || !in_array($authorizedParty, $this->clientIds, true)) {
                return false;
            }
        }

        $now = time();
        $expiresAt = filter_var($claims['exp'] ?? null, FILTER_VALIDATE_INT);
        $issuedAt = filter_var($claims['iat'] ?? null, FILTER_VALIDATE_INT);
        $notBefore = isset($claims['nbf']) ? filter_var($claims['nbf'], FILTER_VALIDATE_INT) : null;

        if ($expiresAt === false || $expiresAt === null || $expiresAt <= $now) {
            return false;
        }

        if ($issuedAt === false || $issuedAt === null || $issuedAt > ($now + 60)) {
            return false;
        }

        if ($notBefore === false || ($notBefore !== null && $notBefore > ($now + 60))) {
            return false;
        }

        $subject = $claims['sub'] ?? null;
        $email = $claims['email'] ?? null;
        $emailVerified = $claims['email_verified'] ?? false;

        return is_string($subject)
            && $subject !== ''
            && is_string($email)
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && ($emailVerified === true || $emailVerified === 'true');
    }

    /**
     * @return array<string, string>
     */
    private function getCertificates(): array
    {
        if ($this->certificateProvider !== null) {
            $provided = ($this->certificateProvider)();
            return is_array($provided) ? $provided : [];
        }

        return Cache::remember(self::CERTIFICATE_CACHE_KEY, now()->addHour(), function (): array {
            $response = Http::acceptJson()
                ->timeout(5)
                ->get('https://www.googleapis.com/oauth2/v3/certs');

            if (!$response->successful()) {
                throw new \RuntimeException('Unable to retrieve Google signing certificates.');
            }

            $keys = $response->json('keys', []);
            if (!is_array($keys)) {
                throw new \RuntimeException('Google signing certificate response is invalid.');
            }

            $certificates = [];
            foreach ($keys as $key) {
                if (!is_array($key)
                    || ($key['kty'] ?? null) !== 'RSA'
                    || ($key['alg'] ?? null) !== 'RS256'
                    || empty($key['kid'])
                    || !isset($key['x5c'][0])
                    || !is_string($key['x5c'][0])) {
                    continue;
                }

                $certificates[$key['kid']] = "-----BEGIN CERTIFICATE-----\n"
                    . chunk_split($key['x5c'][0], 64, "\n")
                    . "-----END CERTIFICATE-----\n";
            }

            if ($certificates === []) {
                throw new \RuntimeException('No usable Google signing certificates were returned.');
            }

            return $certificates;
        });
    }

    private function decodeBase64Url(string $value)
    {
        if ($value === '' || preg_match('/[^A-Za-z0-9\-_]/', $value)) {
            return false;
        }

        $padding = (4 - (strlen($value) % 4)) % 4;
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding), true);

        return $decoded === false ? false : $decoded;
    }
}

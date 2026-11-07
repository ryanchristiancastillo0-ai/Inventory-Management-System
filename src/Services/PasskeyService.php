<?php

namespace App\Services;

use App\Repositories\AuthRepo;
use lbuchs\WebAuthn\WebAuthn;
use RuntimeException;

class PasskeyService
{
    private AuthRepo $authRepo;
    private WebAuthn $webauthn;

    private const CHALLENGE_LIFETIME = 300;

    public function __construct(AuthRepo $authRepo)
    {
        $this->authRepo = $authRepo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Use your actual domain in production.
        $rpId = getenv('WEBAUTHN_RP_ID') ?: 'localhost';

        $this->webauthn = new WebAuthn(
            'Inventory Management System',
            $rpId
        );
    }

    // =========================================
    // CHALLENGE MANAGEMENT
    // =========================================

    private function saveChallenge(
        string $type,
        ?int $userId = null
    ): void {
        $_SESSION['passkey_challenge'] = [
            'challenge' => base64_encode(
                $this->webauthn
                    ->getChallenge()
                    ->getBinaryString()
            ),
            'type' => $type,
            'user_id' => $userId,
            'expires_at' => time()
                + self::CHALLENGE_LIFETIME
        ];
    }

    private function consumeChallenge(
        string $type,
        ?int $userId = null
    ): string {
        $data = $_SESSION['passkey_challenge'] ?? null;

        // Prevent replay, including failed attempts.
        unset($_SESSION['passkey_challenge']);

        if (
            !is_array($data) ||
            ($data['type'] ?? '') !== $type ||
            ($data['expires_at'] ?? 0) < time() ||
            ($data['user_id'] ?? null) !== $userId
        ) {
            throw new RuntimeException(
                'Invalid or expired passkey challenge.'
            );
        }

        $challenge = base64_decode(
            $data['challenge'],
            true
        );

        if ($challenge === false) {
            throw new RuntimeException(
                'Invalid challenge data.'
            );
        }

        return $challenge;
    }

    // =========================================
    // BINARY DATA VALIDATION
    // =========================================

    private function decodeBase64(
        array $data,
        string $field
    ): string {
        $value = $data[$field] ?? null;

        if (
            !is_string($value) ||
            $value === '' ||
            strlen($value) > 131072
        ) {
            throw new RuntimeException(
                'Missing or invalid ' . $field
            );
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false || $decoded === '') {
            throw new RuntimeException(
                'Invalid Base64 data.'
            );
        }

        return $decoded;
    }

    // =========================================
    // PASSKEY REGISTRATION OPTIONS
    // =========================================

    public function registrationOptions(int $userId): object
    {
        $user = $this->authRepo->findUserById($userId);

        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        $existing = $this->authRepo
            ->getUserPasskeys($userId);

        $excludeIds = [];

        foreach ($existing as $passkey) {
            $raw = base64_decode(
                $passkey['credential_id'],
                true
            );

            if ($raw !== false) {
                $excludeIds[] = $raw;
            }
        }

        $options = $this->webauthn->getCreateArgs(
            'user:' . $userId,
            $user['email'],
            $user['username'],
            60,
            true,
            true,
            null,
            $excludeIds
        );

        $this->saveChallenge('register', $userId);

        return $options;
    }

    // =========================================
    // VERIFY AND SAVE NEW PASSKEY
    // =========================================

    public function registerPasskey(
        int $userId,
        array $data
    ): bool {
        $challenge = $this->consumeChallenge(
            'register',
            $userId
        );

        $clientData = $this->decodeBase64(
            $data,
            'clientDataJSON'
        );

        $attestation = $this->decodeBase64(
            $data,
            'attestationObject'
        );

        $result = $this->webauthn->processCreate(
            $clientData,
            $attestation,
            $challenge,
            true,
            true,
            false
        );

        $credentialId = base64_encode(
            $result->credentialId
        );

        $publicKey = $result->credentialPublicKey;

        $signCount = (int) (
            $result->signatureCounter ?? 0
        );

        if (
            !$credentialId ||
            !$publicKey ||
            strlen($credentialId) > 512
        ) {
            throw new RuntimeException(
                'Invalid passkey credential.'
            );
        }

        return $this->authRepo->savePasskey(
            $userId,
            $credentialId,
            $publicKey,
            $signCount
        );
    }

    // =========================================
    // LOGIN OPTIONS
    // =========================================

    public function loginOptions(): object
    {
        // Empty allowCredentials lets the device
        // discover an existing passkey for this site.
        $options = $this->webauthn->getGetArgs(
            [],
            60,
            true,
            true,
            true,
            true,
            true,
            true
        );

        $this->saveChallenge('login');

        return $options;
    }

    // =========================================
    // VERIFY PASSKEY LOGIN
    // =========================================

    public function verifyLogin(array $data): array
    {
        $challenge = $this->consumeChallenge('login');

        $credentialId = base64_encode(
            $this->decodeBase64($data, 'id')
        );

        $passkey = $this->authRepo->findPasskey(
            $credentialId
        );

        if (!$passkey) {
            throw new RuntimeException(
                'Passkey not registered.'
            );
        }

        $clientData = $this->decodeBase64(
            $data,
            'clientDataJSON'
        );

        $authenticatorData = $this->decodeBase64(
            $data,
            'authenticatorData'
        );

        $signature = $this->decodeBase64(
            $data,
            'signature'
        );

        $userId = (int) $passkey['user_id'];

        // Validate the credential's userHandle,
        // when the authenticator returns one.
        if (!empty($data['userHandle'])) {
            $handle = $this->decodeBase64(
                $data,
                'userHandle'
            );

            if (!hash_equals(
                'user:' . $userId,
                $handle
            )) {
                throw new RuntimeException(
                    'Passkey account mismatch.'
                );
            }
        }

        $previousCount = (int) $passkey['sign_count'];

        $this->webauthn->processGet(
            $clientData,
            $authenticatorData,
            $signature,
            $passkey['public_key'],
            $challenge,
            $previousCount,
            true,
            true
        );

        $newCount = $this->webauthn
            ->getSignatureCounter();

        if ($newCount !== null) {
            if (!$this->authRepo->updatePasskeyCounter(
                (int) $passkey['id'],
                $newCount
            )) {
                throw new RuntimeException(
                    'Unable to update passkey counter.'
                );
            }
        }

        $user = $this->authRepo->findUserById(
            $userId
        );

        if (!$user) {
            throw new RuntimeException(
                'User account not found.'
            );
        }

        // The controller may create a session
        // ONLY after this verification succeeds.
        return $user;
    }
}

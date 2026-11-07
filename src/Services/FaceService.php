<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FaceRepo;
use App\Repositories\AuthRepo;
use RuntimeException;

class FaceService
{
    private FaceRepo $faceRepo;
    private AuthRepo $authRepo;

    /**
     * Distance below this equals "same person" (0 = identical).
     * face-api.js commonly uses 0.6; 0.55 balances false accepts
     * against rejects across lighting and angle changes.
     */
    private const MATCH_THRESHOLD = 0.55;

    /**
     * If a second (different) user scores within this margin of the
     * best match, the result is ambiguous and rejected (1:N safety).
     */
    private const AMBIGUITY_MARGIN = 0.05;

    /** Descriptor samples accepted per login attempt (client sends its best frames). */
    private const MAX_LOGIN_SAMPLES = 3;

    /** Stored descriptors per user (multiple angles improve matching). */
    private const MAX_FACES = 3;

    /** Login challenge lifetime in seconds. */
    private const CHALLENGE_LIFETIME = 120;

    /**
     * After a face matches several accounts, the human has this many
     * seconds to pick one (one-time use, then it expires).
     */
    private const SELECTION_LIFETIME = 60;

    public function __construct(
        FaceRepo $faceRepo,
        AuthRepo $authRepo
    ) {
        $this->faceRepo = $faceRepo;
        $this->authRepo = $authRepo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ==========================================
    // LOGIN CHALLENGE (PREVENTS DESCRIPTOR REPLAY)
    // ==========================================

    public function beginLogin(): string
    {
        $challenge = bin2hex(random_bytes(16));

        $_SESSION["face_challenge"] = [
            "challenge"  => $challenge,
            "expires_at" => time() + self::CHALLENGE_LIFETIME
        ];

        return $challenge;
    }

    private function consumeChallenge(string $provided): void
    {
        $data = $_SESSION["face_challenge"] ?? null;

        // One-time use: consumed even on failed attempts.
        unset($_SESSION["face_challenge"]);

        if (
            !is_array($data) ||
            !is_string($provided) ||
            $provided === "" ||
            !hash_equals(
                (string) ($data["challenge"] ?? ""),
                $provided
            ) ||
            ($data["expires_at"] ?? 0) < time()
        ) {
            throw new RuntimeException(
                "Invalid or expired face scan session."
            );
        }
    }

    // ==========================================
    // DESCRIPTOR VALIDATION (face-api.js = 128 floats)
    // ==========================================

    private function validateDescriptor(
        mixed $raw
    ): array {
        if (!is_array($raw)) {
            throw new RuntimeException(
                "Missing or invalid face data."
            );
        }

        $descriptor = [];

        foreach ($raw as $value) {
            if (!is_numeric($value)) {
                throw new RuntimeException(
                    "Invalid face data."
                );
            }
            $descriptor[] = (float) $value;
        }

        if (count($descriptor) !== 128) {
            throw new RuntimeException(
                "Invalid face data."
            );
        }

        // face-api.js descriptors are L2-normalized (length = 1),
        // so any component beyond [-2, 2] is tampering.
        foreach ($descriptor as $component) {
            if (abs($component) > 2.0) {
                throw new RuntimeException(
                    "Invalid face data."
                );
            }
        }

        return $descriptor;
    }

    private function distance(array $a, array $b): float
    {
        $sum = 0.0;

        foreach ($a as $index => $component) {
            $difference = $component - (float) $b[$index];
            $sum += $difference * $difference;
        }

        return sqrt($sum);
    }

    // ==========================================
    // FACE LOGIN (RESOLVES THE USER BY SCAN)
    // ==========================================

    public function verifyLogin(array $data): array
    {
        $this->consumeChallenge(
            (string) ($data["challenge"] ?? "")
        );

        /* The client may send its best frames (up to 3) or a single one. */
        $rawSamples = is_array($data["descriptors"] ?? null)
            ? $data["descriptors"]
            : [$data["descriptor"] ?? null];

        if (count($rawSamples) > self::MAX_LOGIN_SAMPLES) {
            $rawSamples = array_slice(
                $rawSamples,
                0,
                self::MAX_LOGIN_SAMPLES
            );
        }

        $samples = [];

        foreach ($rawSamples as $raw) {
            if (is_array($raw) && $raw !== []) {
                $samples[] = $this->validateDescriptor($raw);
            }
        }

        if (!$samples) {
            throw new RuntimeException(
                "Missing or invalid face data."
            );
        }

        $faces = $this->faceRepo->getAllFaces();

        if (!$faces) {
            throw new RuntimeException(
                "No faces enrolled on this system."
            );
        }

        /* Per-user best distance across every sample and stored face. */
        $userBest = [];

        foreach ($faces as $face) {
            $stored = json_decode(
                (string) $face["descriptor"],
                true
            );

            if (!is_array($stored) || count($stored) !== 128) {
                continue;
            }

            $userId = (int) $face["user_id"];

            foreach ($samples as $sample) {
                $distance = $this->distance($sample, $stored);

                if (
                    !isset($userBest[$userId]) ||
                    $distance < $userBest[$userId]
                ) {
                    $userBest[$userId] = $distance;
                }
            }
        }

        asort($userBest);

        $ranked = [];

        foreach ($userBest as $userId => $distance) {
            if ($distance <= self::MATCH_THRESHOLD) {
                $ranked[] = [$userId, $distance];
            }
        }

        /* Not recognized at all — reject. */
        if (!$ranked) {
            throw new RuntimeException(
                "Face not recognized."
            );
        }

        /* One clear winner when the best match is meaningfully ahead —
           or only a single candidate matched below the threshold. */
        if (
            count($ranked) === 1 ||
            ($ranked[1][1] - $ranked[0][1]) >= self::AMBIGUITY_MARGIN
        ) {
            $user = $this->authRepo->findUserById(
                $ranked[0][0]
            );

            if (!$user) {
                throw new RuntimeException(
                    "User account not found."
                );
            }

            // The controller may create a session
            // ONLY after verification succeeds.
            return [
                "mode" => "login",
                "user" => $user
            ];
        }

        /* Two or more accounts sit statistically tied: the same person
           enrolled their face on several accounts (normal on shared
           devices). Present the human a one-time account chooser
           instead of guessing. */
        $userBestIds = [];
        $userBestAccounts = [];

        foreach ($ranked as [$userId, $distance]) {
            $found = $this->authRepo->findUserById($userId);

            if ($found) {
                $userBestIds[] = (int) $userId;
                $userBestAccounts[] = [
                    "id"       => (int) $userId,
                    "username" => (string) $found["username"],
                    "email"    => (string) $found["email"],
                    "role"     => (string) ($found["role"] ?? "USER"),
                    "distance" => round((float) $distance, 3)
                ];
            }
        }

        if (count($userBestIds) === 1) {
            $user = $this->authRepo->findUserById($userBestIds[0]);

            if (!$user) {
                throw new RuntimeException(
                    "User account not found."
                );
            }

            return [
                "mode" => "login",
                "user" => $user
            ];
        }

        if (count($userBestIds) < 2) {
            throw new RuntimeException(
                "Face not recognized."
            );
        }

        /* One-time selection token, 60s window, bound to this session. */
        $token = bin2hex(random_bytes(16));

        $_SESSION["face_accounts"] = [
            "token"      => $token,
            "ids"        => $userBestIds,
            "expires_at" => time() + self::SELECTION_LIFETIME
        ];

        return [
            "mode"     => "choose",
            "token"    => $token,
            "accounts" => $userBestAccounts
        ];
    }

    // ==========================================
    // ACCOUNT SELECTION (AFTER A MULTI-MATCH)
    // ==========================================

    /**
     * Consumed the one-time chooser token and returns the chosen user,
     * but ONLY if that account was among the verified candidates.
     */
    public function selectAccount(
        string $token,
        mixed $chosenId
    ): array {
        $selection = $_SESSION["face_accounts"] ?? null;

        // One-time use, even on failure.
        unset($_SESSION["face_accounts"]);

        if (
            !is_array($selection) ||
            !is_string($token) ||
            $token === "" ||
            !hash_equals(
                (string) ($selection["token"] ?? ""),
                $token
            ) ||
            ($selection["expires_at"] ?? 0) < time()
        ) {
            throw new RuntimeException(
                "Selection expired. Please scan your face again."
            );
        }

        $chosenId = (int) $chosenId;

        $allowed = array_map(
            static fn ($id): int => (int) $id,
            (array) ($selection["ids"] ?? [])
        );

        if (!in_array($chosenId, $allowed, true)) {
            throw new RuntimeException(
                "Pick one of the suggested accounts."
            );
        }

        $user = $this->authRepo->findUserById($chosenId);

        if (!$user) {
            throw new RuntimeException(
                "User account not found."
            );
        }

        return $user;
    }

    // ==========================================
    // ENROLLMENT (LOGGED-IN USER)
    // ==========================================

    public function enrollFace(
        int $userId,
        array $rawDescriptors
    ): int {
        if ($userId <= 0) {
            throw new RuntimeException("Invalid user session.");
        }

        if (count($rawDescriptors) < 1) {
            throw new RuntimeException(
                "At least one face scan is required to enroll."
            );
        }

        if (count($rawDescriptors) > self::MAX_FACES) {
            throw new RuntimeException(
                "A maximum of " . self::MAX_FACES . " scans is allowed."
            );
        }

        $descriptors = [];

        foreach ($rawDescriptors as $raw) {
            $descriptors[] = $this->validateDescriptor($raw);
        }

        // Replace-all semantics: re-enrolling refreshes stored angles.
        $this->faceRepo->deleteFacesByUserId($userId);

        foreach ($descriptors as $descriptor) {
            if (!$this->faceRepo->saveFace(
                $userId,
                json_encode($descriptor, JSON_THROW_ON_ERROR)
            )) {
                throw new RuntimeException(
                    "Unable to save face enrollment."
                );
            }
        }

        return count($descriptors);
    }

    public function getEnrollments(int $userId): array
    {
        $faces = $this->faceRepo->getFacesByUserId($userId);

        return [
            "count"  => count($faces),
            "faces"  => array_map(
                static fn (array $face): array => [
                    "id"         => (int) $face["id"],
                    "created_at" => (string) $face["created_at"]
                ],
                $faces
            )
        ];
    }

    public function deleteFaces(int $userId): void
    {
        if (!$this->faceRepo->deleteFacesByUserId($userId)) {
            throw new RuntimeException(
                "Unable to delete face enrollment."
            );
        }
    }
}
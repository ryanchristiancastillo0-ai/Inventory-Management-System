<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SettingsRepo;
use InvalidArgumentException;

class SettingsService
{
    private SettingsRepo $settingsRepo;
    private ProfileService $profileService;

    private const THEMES = [
        "LIGHT",
        "DARK",
        "SYSTEM"
    ];

    public function __construct(
        SettingsRepo $settingsRepo,
        ProfileService $profileService
    ) {
        $this->settingsRepo = $settingsRepo;
        $this->profileService = $profileService;
    }

    // ==========================================
    // GET SETTINGS
    // ==========================================

    public function getSettings(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                "Invalid user ID."
            );
        }

        $settings = $this->settingsRepo
            ->getSettingsByUserId($userId);

        if (!$settings) {
            return [
                "user_id" => $userId,
                "theme" => "SYSTEM",
                "email_notifications" => 1
            ];
        }

        return $settings;
    }

    // ==========================================
    // GET ACCOUNT
    // ==========================================

    public function getAccount(int $userId): ?array
    {
        return $this->profileService->getProfile($userId);
    }

    // ==========================================
    // UPDATE PREFERENCES
    // ==========================================

    public function updatePreferences(
        int $userId,
        string $theme,
        bool $emailNotifications
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                "Invalid user ID."
            );
        }

        $theme = strtoupper(trim($theme));

        if (!in_array($theme, self::THEMES, true)) {
            throw new InvalidArgumentException(
                "Invalid interface theme."
            );
        }

        return $this->settingsRepo->saveSettings(
            $userId,
            $theme,
            $emailNotifications
        );
    }

    // ==========================================
    // UPDATE ACCOUNT PROFILE
    // ==========================================

    public function updateProfile(
        int $userId,
        string $username,
        string $email
    ): bool {
        return $this->profileService->updateProfile($userId, $username, $email);
    }

    // ==========================================
    // CHANGE PASSWORD
    // ==========================================

    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword,
        string $confirmPassword
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                "Invalid user ID."
            );
        }

        if ($currentPassword === "") {
            throw new InvalidArgumentException(
                "Current password is required."
            );
        }

        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException(
                "New password must contain at least 8 characters."
            );
        }

        if ($newPassword !== $confirmPassword) {
            throw new InvalidArgumentException(
                "New passwords do not match."
            );
        }

        if ($currentPassword === $newPassword) {
            throw new InvalidArgumentException(
                "New password must be different."
            );
        }

        $currentHash = $this->settingsRepo
            ->getPasswordHash($userId);

        if (
            $currentHash === false ||
            !password_verify(
                $currentPassword,
                $currentHash
            )
        ) {
            throw new InvalidArgumentException(
                "Current password is incorrect."
            );
        }

        $newHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        return $this->settingsRepo->updatePassword(
            $userId,
            $newHash
        );
    }
}

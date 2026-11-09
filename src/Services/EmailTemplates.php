<?php

declare(strict_types=1);

namespace App\Services;

/**
 * HTML email templates.
 *
 * All emails share one visual system (premium minimal, matches the
 * app theme) and differ only by the kicker label, heading, body and
 * footer note. Everything is inline-styled because email clients
 * strip <style> blocks.
 */
class EmailTemplates
{
    /**
     * Generic notification email ("What changed" table + optional CTA).
     *
     * @param array<int, string> $paragraphs
     * @param array<string, string> $changes label => value (e.g. "Email" => "old -> new")
     * @param array{label: string, url: string}|null $cta
     */
    public static function render(
        string $heading,
        array $paragraphs = [],
        array $changes = [],
        ?array $cta = null
    ): string {
        $font = "'Segoe UI','Inter',-apple-system,'Helvetica Neue',Arial,sans-serif";
        $accent = "#1a1a1a";
        $text = "#1f2023";
        $muted = "#6d7079";
        $label = "#9a9d9f";
        $line = "#ececec";
        $cardBg = "#fafaf8";

        // ---- Paragraphs --------------------------------------------------
        $paragraphHtml = "";

        foreach ($paragraphs as $paragraph) {
            $safe = htmlspecialchars($paragraph, ENT_QUOTES, "UTF-8");

            $paragraphHtml .= "<p style=\"margin:0 0 10px;font-size:14px;line-height:1.6;color:{$text};\">{$safe}</p>";
        }

        // ---- "What changed" table ---------------------------------------
        $changesHtml = "";

        if ($changes !== []) {
            $rows = "";

            foreach ($changes as $changeLabel => $changeValue) {
                $safeLabel = htmlspecialchars((string) $changeLabel, ENT_QUOTES, "UTF-8");
                $safeValue = htmlspecialchars((string) $changeValue, ENT_QUOTES, "UTF-8");

                $rows .= <<<ROW
                <tr>
                    <td style="padding:9px 14px;font-family:{$font};font-size:13px;color:{$muted};border-top:1px solid {$line};white-space:nowrap;">{$safeLabel}</td>
                    <td style="padding:9px 14px;font-family:{$font};font-size:13px;color:{$text};font-weight:600;border-top:1px solid {$line};">{$safeValue}</td>
                </tr>
                ROW;
            }

            $changesHtml = <<<CHANGES
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{$cardBg};border:1px solid {$line};border-radius:10px;margin:16px 0 4px;">
                <tbody>
                    <tr>
                        <td colspan="2" style="padding:9px 14px;font-family:{$font};font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:{$label};border-bottom:1px solid {$line};">What changed</td>
                    </tr>
                    {$rows}
                </tbody>
            </table>
            CHANGES;
        }

        // ---- CTA button --------------------------------------------------
        $ctaHtml = "";

        if (
            $cta !== null &&
            isset($cta["url"]) && $cta["url"] !== "" &&
            isset($cta["label"]) && $cta["label"] !== ""
        ) {
            $safeLabel = htmlspecialchars($cta["label"], ENT_QUOTES, "UTF-8");
            $safeUrl = htmlspecialchars($cta["url"], ENT_QUOTES, "UTF-8");

            $ctaHtml = <<<CTA
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 4px;">
                <tr>
                    <td style="background:{$accent};border-radius:10px;">
                        <a href="{$safeUrl}" style="display:inline-block;padding:11px 20px;font-family:{$font};font-size:13px;font-weight:600;color:#ffffff;text-decoration:none;">{$safeLabel}</a>
                    </td>
                </tr>
            </table>
            CTA;
        }

        $bodyHtml = $paragraphHtml . $changesHtml . $ctaHtml;

        return static::shell(
            "Notification",
            $heading,
            $bodyHtml,
            "This email was sent automatically because email notifications are enabled in your account settings."
        );
    }

    /**
     * Password-reset code email — dedicated template.
     *
     * A premium minimal email with the 6-digit code in a large,
     * letter-spaced code box, matching the login-card design.
     */
    public static function renderResetCode(
        string $username,
        string $code,
        int $expiryMinutes,
        string $appBaseUrl
    ): string {
        $font = "'Segoe UI','Inter',-apple-system,'Helvetica Neue',Arial,sans-serif";
        $text = "#1f2023";
        $muted = "#6d7079";
        $line = "#ececec";
        $safeUser = htmlspecialchars(trim($username) !== "" ? $username : "there", ENT_QUOTES, "UTF-8");
        $safeCode = htmlspecialchars($code, ENT_QUOTES, "UTF-8");
        $safeMinutes = (int) $expiryMinutes;
        $safeUrl = htmlspecialchars($appBaseUrl . "/forgot-password", ENT_QUOTES, "UTF-8");

        $bodyHtml = <<<BODY
        <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:{$text};">Hi {$safeUser},</p>
        <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:{$text};">Someone requested a password reset for your STOKLIVO account. Use the verification code below to continue.</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fafaf8;border:1px solid {$line};border-radius:12px;margin:0 0 12px;">
            <tr>
                <td style="padding:22px 20px;text-align:center;">
                    <span style="display:inline-block;padding:14px 22px;background:#ffffff;border:1px solid {$line};border-radius:10px;font-family:'Courier New',monospace;font-size:30px;font-weight:700;letter-spacing:10px;text-indent:10px;color:#1a1a1a;">{$safeCode}</span>
                </td>
            </tr>
        </table>
        <p style="margin:0 0 10px;font-size:12px;line-height:1.55;color:{$muted};text-align:center;">The code expires in {$safeMinutes} minutes and can only be used once.</p>
        <p style="margin:14px 0 4px;font-size:14px;line-height:1.6;color:{$text};">Didn't request this? You can safely ignore this email &mdash; your password stays unchanged.</p>
        <p style="margin:0 0 2px;font-size:14px;line-height:1.6;color:{$text};">Need a new code? <a href="{$safeUrl}" style="color:#1a1a1a;font-weight:600;">Request it here</a>.</p>
        BODY;

        return static::shell(
            "Password reset",
            "Reset your password",
            $bodyHtml,
            "You're receiving this email because a password reset code was requested for this account."
        );
    }

    /**
     * Password-changed confirmation email — dedicated template.
     */
    public static function renderPasswordChanged(
        string $username,
        ?string $appBaseUrl
    ): string {
        $text = "#1f2023";
        $safeUser = htmlspecialchars(trim($username) !== "" ? $username : "there", ENT_QUOTES, "UTF-8");

        $ctaHtml = "";

        if ($appBaseUrl !== null && $appBaseUrl !== "") {
            $font = "'Segoe UI','Inter',-apple-system,'Helvetica Neue',Arial,sans-serif";
            $safeUrl = htmlspecialchars($appBaseUrl . "/auth?auth=login", ENT_QUOTES, "UTF-8");

            $ctaHtml = <<<CTA
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:18px 0 4px;">
                <tr>
                    <td style="background:#1a1a1a;border-radius:10px;">
                        <a href="{$safeUrl}" style="display:inline-block;padding:11px 20px;font-family:{$font};font-size:13px;font-weight:600;color:#ffffff;text-decoration:none;">Log in</a>
                    </td>
                </tr>
            </table>
            CTA;
        }

        $bodyHtml = <<<BODY
        <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:{$text};">Hi {$safeUser},</p>
        <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:{$text};">Your STOKLIVO account password was just changed.</p>
        <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:{$text};">If this was you, no action is needed &mdash; you can log in with your new password.</p>
        <p style="margin:0 0 2px;font-size:14px;line-height:1.6;color:{$text};">If you did <strong>not</strong> change your password, contact an administrator immediately.</p>
        {$ctaHtml}
        BODY;

        return static::shell(
            "Security",
            "Your password was changed",
            $bodyHtml,
            "You're receiving this email because the password for your account was changed."
        );
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    private static function shell(
        string $sectionLabel,
        string $heading,
        string $bodyHtml,
        string $footerNote
    ): string {
        $font = "'Segoe UI','Inter',-apple-system,'Helvetica Neue',Arial,sans-serif";
        $accent = "#1a1a1a";
        $label = "#9a9d9f";
        $faint = "#b3b4b2";
        $year = date("Y");

        $safeSectionLabel = htmlspecialchars($sectionLabel, ENT_QUOTES, "UTF-8");
        $safeHeading = htmlspecialchars($heading, ENT_QUOTES, "UTF-8");
        $safeFooter = htmlspecialchars($footerNote, ENT_QUOTES, "UTF-8");

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
        </head>
        <body style="margin:0;padding:32px 16px;background:#f4f4f2;font-family:{$font};">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;text-align:left;">
                            <tr>
                                <td style="padding:0 0 16px;">
                                    <span style="font-family:{$font};font-size:15px;font-weight:700;letter-spacing:.02em;color:{$accent};">Inventory&nbsp;Hub</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="background:#ffffff;border:1px solid #e7e7e3;border-radius:16px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:28px 32px 8px;font-family:{$font};font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:{$label};">{$safeSectionLabel}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0 32px 12px;font-family:{$font};font-size:22px;font-weight:700;line-height:1.25;color:{$accent};">{$safeHeading}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0 32px 28px;font-family:{$font};">{$bodyHtml}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 32px 8px;font-family:{$font};font-size:12px;line-height:1.5;color:{$faint};text-align:center;">
                                    {$safeFooter}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 32px 16px;text-align:center;">
                                    <span style="font-family:{$font};font-size:12px;color:#c4c5c3;">&copy; {$year} STOKLIVO</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }
}

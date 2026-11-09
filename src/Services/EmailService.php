<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Env;
use Brevo\Brevo;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestSender;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestToItem;
use RuntimeException;

/**
 * Transport for transactional emails through Brevo (Sendinblue).
 *
 * Reads the v3 API key and sender identity from the .env file and
 * delegates the actual delivery to the official brevo-php SDK.
 * Email rendering lives in EmailTemplates; persistence lives in
 * NotificationRepo (driven by NotificationService).
 *
 * @throws RuntimeException only while sending — callers (NotificationService)
 *         are expected to catch it and record a failed notification.
 */
class EmailService
{
    private ?string $apiKey;
    private string $fromEmail;
    private string $fromName;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = Env::get("BREVO_API_KEY");
        $this->fromEmail = Env::get("MAIL_FROM_EMAIL", "no-reply@inventoryhub.com") ?? "";
        $this->fromName = trim(Env::get("MAIL_FROM_NAME", "STOKLIVO") ?? "STOKLIVO");
        $this->timeout = (int) (Env::get("BREVO_TIMEOUT", "10") ?? 10);
    }

    /**
     * True when an API key is present in the environment — Brevo
     * accepts empty-string keys in its constructor, so keep the
     * client easy to build in local/dev environments.
     */
    public function isConfigured(): bool
    {
        return $this->apiKey !== null && trim($this->apiKey) !== "";
    }

    /**
     * Sends a transactional email. Quietly resolves to false when
     * Brevo is not configured (dev/local without a key).
     *
     * @param string $subject   Email subject line.
     * @param string $html      Full HTML body.
     * @param string $text      Plain-text fallback.
     * @param string $toEmail   Recipient address.
     * @param string $toName    Recipient display name.
     *
     * @return string Brevo message id (may be "" if Brevo omits it).
     */
    public function send(
        string $subject,
        string $html,
        string $text,
        string $toEmail,
        string $toName = ""
    ): string {
        if (!$this->isConfigured()) {
            throw new RuntimeException(
                "Brevo is not configured: BREVO_API_KEY is missing."
            );
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Invalid recipient email address.");
        }

        $client = new Brevo(
            apiKey: trim((string) $this->apiKey),
            options: ["timeout" => (float) $this->timeout]
        );

        $request = new SendTransacEmailRequest();
        $request->sender = new SendTransacEmailRequestSender([
            "email" => $this->fromEmail,
            "name" => $this->fromName
        ]);

        $request->to = [new SendTransacEmailRequestToItem([
            "email" => $toEmail,
            "name" => $toName !== "" ? $toName : null
        ])];

        $request->subject = $subject;
        $request->htmlContent = $html;
        $request->textContent = $text;

        $response = $client->transactionalEmails->sendTransacEmail($request);

        if ($response === null) {
            throw new RuntimeException("Brevo returned an empty response.");
        }

        $messageId = $response->messageId ?? "";

        if ($messageId === "" && !empty($response->messageIds)) {
            $messageId = (string) $response->messageIds[0];
        }

        return $messageId;
    }

    /**
     * Builds a plain-text version automatically from the same
     * structured data used for the HTML template.
     *
     * @param array<int, string> $paragraphs
     * @param array<string, string> $changes
     */
    public static function buildPlainText(
        string $heading,
        array $paragraphs = [],
        array $changes = []
    ): string {
        $lines = ["=== " . $heading . " ==="];

        foreach ($paragraphs as $paragraph) {
            $lines[] = "";
            $lines[] = $paragraph;
        }

        if ($changes !== []) {
            $lines[] = "";

            foreach ($changes as $label => $value) {
                $lines[] = $label . ": " . $value;
            }
        }

        $lines[] = "";
        $lines[] = "-STOKLIVO";

        return implode("\n", $lines);
    }
}

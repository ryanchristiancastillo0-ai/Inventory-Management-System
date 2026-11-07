<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Env;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

/**
 * UniSMS Service
 *
 * Sends SMS messages through the UniSMS REST API
 * (https://unismsapi.com). Returns are provider messages; references
 * and "pending" statuses mean the SMS was ACCEPTED FOR PROCESSING,
 * not yet delivered to a phone.
 *
 * Uses the Guzzle HTTP client that is already installed via Composer
 * (it ships with the Brevo integration, no extra packages needed).
 *
 * @see https://unismsapi.com/docs/sms
 */
class SmsService
{
    private const API_ENDPOINT_DEFAULT = 'https://unismsapi.com/api/sms';

    /**
     * The default Sender ID registered with this UniSMS account.
     * Do NOT invent brand names; supported networks under this
     * Sender ID are Globe, TM and DITO (no Smart / TNT / Sun).
     */
    private const SENDER_ID_DEFAULT = 'Unisoft';

    private const DEFAULT_TIMEOUT = 15; // seconds
    private const CONNECT_TIMEOUT = 10; // seconds

    private string $secretKey;
    private string $senderId;
    private string $apiEndpoint;
    private int $timeout;
    private int $connectTimeout;

    /** @param string|null $apiEndpoint testing override only. */
    public function __construct(?string $apiEndpoint = null)
    {
        $this->apiEndpoint = $apiEndpoint ?? self::API_ENDPOINT_DEFAULT;
        $this->secretKey = trim((string) (Env::get('UNISMS_SECRET_KEY') ?? ''));
        $this->senderId = trim((string) (Env::get('UNISMS_SENDER_ID', self::SENDER_ID_DEFAULT) ?? self::SENDER_ID_DEFAULT));
        $this->timeout = (int) (Env::get('UNISMS_TIMEOUT', (string) self::DEFAULT_TIMEOUT) ?? self::DEFAULT_TIMEOUT);
        $this->connectTimeout = (int) (Env::get('UNISMS_CONNECT_TIMEOUT', (string) self::CONNECT_TIMEOUT) ?? self::CONNECT_TIMEOUT);
    }

    /**
     * Check if UniSMS is configured with the secret key.
     */
    public function isConfigured(): bool
    {
        return $this->secretKey !== '';
    }

    /**
     * Send an SMS message via UniSMS.
     *
     * @param string $phoneNumber  Destination number in E.164 format (+639XXXXXXXXX)
     * @param string $message      SMS message content
     *
     * @return array Result array with keys:
     *               - success (bool) true only when UniSMS ACCEPTED the request
     *               - message (string) Human-readable message
     *               - data (array|null) Raw provider response (reference id etc.)
     *               - errorCode (string|null) Machine-readable error code
     */
    public function send(string $phoneNumber, string $message): array
    {
        // Validate configuration
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMS service is not configured. Please set UNISMS_SECRET_KEY.',
                'data' => null,
                'errorCode' => 'NOT_CONFIGURED',
            ];
        }

        // Validate phone number format
        if (!$this->isValidPhilippineNumber($phoneNumber)) {
            return [
                'success' => false,
                'message' => 'Invalid Philippine mobile number format.',
                'data' => null,
                'errorCode' => 'INVALID_PHONE',
            ];
        }

        $payload = [
            'recipient' => $phoneNumber,
            'content' => $message,
            'sender_id' => $this->senderId,
        ];

        $client = new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'http_errors' => false, // handle status codes manually
            // SSL certificate verification stays ON (Guzzle default).
        ]);

        try {
            $response = $client->request('POST', $this->apiEndpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                // HTTP Basic Auth: username = secret key, password = empty.
                // The credential never appears in logs or responses below.
                'auth' => [$this->secretKey, ''],
                'json' => $payload,
            ]);
        } catch (ConnectException $error) {
            // Network failure (timeout, DNS, TLS).
            $this->logFailure('CONNECT', $error->getMessage());
            return [
                'success' => false,
                'message' => 'Could not reach the SMS provider. Check network connectivity.',
                'data' => null,
                'errorCode' => 'CONNECTION_ERROR',
            ];
        } catch (RequestException $error) {
            $this->logFailure('REQUEST', $error->getMessage());
            return [
                'success' => false,
                'message' => 'Unexpected response from SMS provider.',
                'data' => null,
                'errorCode' => 'REQUEST_ERROR',
            ];
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        // Parse the actual response rather than assuming a shape.
        $data = null;
        if ($body !== '') {
            $data = json_decode($body, true);
            if (!is_array($data)) {
                $data = ['raw' => $body];
            }
        }

        /* HTTP 200/201: accepted for processing (pending — not a
           delivery receipt). 401 invalid credentials, 422 validation
           or restricted recipient, 429 rate limiting, 5xx provider. */
        if ($status === 200 || $status === 201) {
            return [
                'success' => true,
                'message' => 'SMS accepted for delivery.',
                'data' => $data,
                'errorCode' => null,
            ];
        }

        if ($status === 401) {
            $this->logFailure((string) $status, $body);
            return [
                'success' => false,
                'message' => 'Invalid UniSMS credentials.',
                'data' => $data,
                'errorCode' => 'UNAUTHORIZED',
            ];
        }

        if ($status === 422) {
            // Potentially a restricted recipient (Smart / TNT / Sun).
            // Prefix checks are only informative; the provider decides.
            if ($this->looksLikeRestrictedNetwork($data)) {
                $this->logFailure((string) $status, $body);
                return [
                    'success' => false,
                    'message' => 'This phone number cannot receive SMS from our current provider. Please use a supported network.',
                    'data' => $data,
                    'errorCode' => 'UNSUPPORTED_NETWORK',
                ];
            }

            $this->logFailure((string) $status, $body);
            return [
                'success' => false,
                'message' => 'The SMS request was rejected as invalid.',
                'data' => $data,
                'errorCode' => 'VALIDATION_ERROR',
            ];
        }

        if ($status === 429) {
            $this->logFailure((string) $status, $body);
            return [
                'success' => false,
                'message' => 'SMS rate limit or quota exceeded.',
                'data' => $data,
                'errorCode' => 'RATE_LIMITED',
            ];
        }

        $this->logFailure((string) $status, $body);
        return [
            'success' => false,
            'message' => 'SMS provider server error. Please try again later.',
            'data' => $data,
            'errorCode' => $status >= 500 ? 'PROVIDER_ERROR' : 'UNEXPECTED_RESPONSE',
        ];
    }

    /**
     * Inspect a 422 payload for clear network-restriction signals.
     * Never delivers a user-facing verdict from prefix guessing.
     */
    private function looksLikeRestrictedNetwork(?array $data): bool
    {
        if ($data === null) {
            return false;
        }

        // Flatten any nested error structure into a single string.
        $search = strtolower(
            preg_replace('/[^a-z0-9 ]/i', ' ', json_encode($data, JSON_THROW_ON_ERROR)) ?? ''
        );

        // The provider's documented restriction vocabulary.
        $providers = [
            'smart', 'tnt', 'sun', 'smart buddy', 'sun cellular',
            'smartisan', 'digitel',
        ];

        // A blanket "recipient not supported" counts even without a
        // named network in the message.
        $unsupported =
            str_contains($search, 'not supported') ||
            str_contains($search, 'unsupported') ||
            str_contains($search, 'restrict');

        $namesProvider = false;
        foreach ($providers as $provider) {
            if (str_contains($search, $provider)) {
                $namesProvider = true;
                break;
            }
        }

        // Confirm restriction context: named network OR recipient wording.
        $recipientContext = str_contains($search, 'recipient') ||
            str_contains($search, 'recipients');

        return ($namesProvider && $unsupported) || ($recipientContext && $unsupported);
    }

    /**
     * Log failures WITHOUT credentials or code words leaking.
     * @param string $context        HTTP status or failure context
     * @param string $technical      Provider response / exception detail
     */
    private function logFailure(string $context, string $technical): void
    {
        error_log('[SmsService] UniSMS send failed (HTTP ' . $context . '): '
            . mb_substr($technical, 0, 500));
    }

    /**
     * Validate Philippine mobile number and normalize to E.164 format.
     *
     * Accepts: 09XXXXXXXXX, 639XXXXXXXXX, +639XXXXXXXXX
     * Returns: +639XXXXXXXXX (E.164)
     */
    public function normalizePhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = trim($phoneNumber);
        $phoneNumber = preg_replace('/[\s\-\(\)]/', '', $phoneNumber);

        // Already in E.164 format
        if (preg_match('/^\+639\d{9}$/', $phoneNumber)) {
            return $phoneNumber;
        }

        // Starts with 639
        if (preg_match('/^639\d{9}$/', $phoneNumber)) {
            return '+' . $phoneNumber;
        }

        // Starts with 09
        if (preg_match('/^09\d{9}$/', $phoneNumber)) {
            return '+63' . substr($phoneNumber, 1);
        }

        // Return as-is for validation to catch it
        return $phoneNumber;
    }

    /**
     * Check if a phone number is a valid Philippine mobile number.
     */
    public function isValidPhilippineNumber(string $phoneNumber): bool
    {
        $normalized = $this->normalizePhoneNumber($phoneNumber);
        return preg_match('/^\+639\d{9}$/', $normalized) === 1;
    }
}

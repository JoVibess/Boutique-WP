<?php

namespace Genesii\DressMe\Service;

use Genesii\DressMe\Support\SettingsRepository;

final class SymfonyApiClient
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository = new SettingsRepository(),
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{ok: bool, status: int, body: array<string, mixed>, message: string}
     */
    public function post(string $path, array $payload): array
    {
        $baseUrl = $this->settingsRepository->getApiBaseUrl();

        return $this->postToBaseUrl(
            $baseUrl,
            $path,
            $payload,
            $this->settingsRepository->getApiKey(),
            $this->settingsRepository->getApiSecret(),
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{ok: bool, status: int, body: array<string, mixed>, message: string}
     */
    public function postToBaseUrl(
        string $baseUrl,
        string $path,
        array $payload,
        string $apiKey = '',
        string $apiSecret = '',
    ): array
    {
        if ('' === $baseUrl) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'message' => __('DressMe API base URL is not configured.', 'dressme'),
            ];
        }

        $baseUrl = untrailingslashit($baseUrl);
        $body = wp_json_encode($payload);

        if (!is_string($body)) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'message' => __('Unable to encode the DressMe request payload.', 'dressme'),
            ];
        }

        $response = wp_remote_post($this->buildUrl($baseUrl, $path), [
            'timeout' => 25,
            'headers' => $this->buildHeaders($path, $body, $apiKey, $apiSecret),
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'message' => $response->get_error_message(),
            ];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $rawBody = (string) wp_remote_retrieve_body($response);
        $decodedBody = json_decode($rawBody, true);
        $body = is_array($decodedBody) ? $decodedBody : [];

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => $body,
            'message' => (string) ($body['message'] ?? wp_remote_retrieve_response_message($response)),
        ];
    }

    private function buildUrl(string $baseUrl, string $path): string
    {
        return untrailingslashit($baseUrl) . '/' . ltrim($path, '/');
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(string $path, string $body, string $apiKey, string $apiSecret): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $apiKey = trim($apiKey);
        $apiSecret = trim($apiSecret);

        if ('' === $apiKey || '' === $apiSecret) {
            return $headers;
        }

        $timestamp = (string) time();
        $nonce = wp_generate_password(32, false, false);
        $bodyHash = hash('sha256', $body);
        $stringToSign = implode("\n", [
            'POST',
            '/' . ltrim($path, '/'),
            $timestamp,
            $nonce,
            $bodyHash,
        ]);

        $headers['X-DressMe-Key'] = $apiKey;
        $headers['X-DressMe-Timestamp'] = $timestamp;
        $headers['X-DressMe-Nonce'] = $nonce;
        $headers['X-DressMe-Signature'] = hash_hmac('sha256', $stringToSign, $apiSecret);

        @file_put_contents(
            '/tmp/dressme-plugin-hmac.log',
            sprintf(
                "[%s] path=%s timestamp=%s nonce=%s body_hash=%s signature=%s secret_length=%d secret_prefix=%s secret_suffix=%s body=%s\n",
                date(DATE_ATOM),
                '/' . ltrim($path, '/'),
                $timestamp,
                $nonce,
                $bodyHash,
                $headers['X-DressMe-Signature'],
                strlen($apiSecret),
                substr($apiSecret, 0, 6),
                substr($apiSecret, -6),
                $body,
            ),
            FILE_APPEND,
        );

        return $headers;
    }
}

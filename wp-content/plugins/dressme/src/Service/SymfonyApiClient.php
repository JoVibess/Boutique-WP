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

        return $this->postToBaseUrl($baseUrl, $path, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{ok: bool, status: int, body: array<string, mixed>, message: string}
     */
    public function postToBaseUrl(string $baseUrl, string $path, array $payload): array
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
        $response = wp_remote_post($this->buildUrl($baseUrl, $path), [
            'timeout' => 25,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
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
}

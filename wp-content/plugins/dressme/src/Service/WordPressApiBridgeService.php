<?php

namespace Genesii\DressMe\Service;

use Genesii\DressMe\Support\SettingsRepository;
use Genesii\Kernel\Service\AbstractService;

final class WordPressApiBridgeService extends AbstractService
{
    private SettingsRepository $settingsRepository;
    private SymfonyApiClient $apiClient;

    public function __construct(string $path)
    {
        $this->settingsRepository = new SettingsRepository();
        $this->apiClient = new SymfonyApiClient($this->settingsRepository);

        parent::__construct($path);
    }

    protected function hooks(): void
    {
        add_action('wp_ajax_dressme_validate_key', [$this, 'validateKey']);
        add_action('wp_ajax_dressme_try_on_request', [$this, 'requestTryOn']);
        add_action('wp_ajax_nopriv_dressme_try_on_request', [$this, 'requestTryOn']);
        add_action('wp_ajax_dressme_try_on_status', [$this, 'requestTryOnStatus']);
        add_action('wp_ajax_nopriv_dressme_try_on_status', [$this, 'requestTryOnStatus']);
        add_action('wp_ajax_dressme_download_image', [$this, 'downloadImage']);
        add_action('wp_ajax_nopriv_dressme_download_image', [$this, 'downloadImage']);
    }

    public function validateKey(): void
    {
        check_ajax_referer('dressme_validate_key', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'error_code' => 'FORBIDDEN',
                'message' => __('You are not allowed to validate this API key.', 'dressme'),
            ], 403);
        }

        $apiKey = sanitize_text_field(wp_unslash((string) ($_POST['api_key'] ?? $this->settingsRepository->getApiKey())));
        $apiSecret = sanitize_text_field(wp_unslash((string) ($_POST['api_secret'] ?? $this->settingsRepository->getApiSecret())));
        $apiBaseUrl = esc_url_raw(untrailingslashit(wp_unslash((string) ($_POST['api_base_url'] ?? $this->settingsRepository->getApiBaseUrl()))));

        $response = $this->apiClient->postToBaseUrl(
            $apiBaseUrl,
            '/api/wordpress/validate-key',
            [
                'api_key' => $apiKey,
                'site_url' => home_url(),
            ],
            $apiKey,
            $apiSecret,
        );

        $this->sendProxyResponse($response);
    }

    public function requestTryOn(): void
    {
        check_ajax_referer('dressme_try_on_request', 'nonce');

        if (!$this->settingsRepository->isConfigured()) {
            wp_send_json_error([
                'error_code' => 'NOT_CONFIGURED',
                'message' => __('DressMe API URL and key must be configured before requesting a try-on.', 'dressme'),
            ], 400);
        }

        $productPayload = $this->readProductPayload();
        $customerImage = wp_unslash((string) ($_POST['customer_image'] ?? ''));

        $payload = [
            'api_key' => $this->settingsRepository->getApiKey(),
            'anonymous_visitor_id' => sanitize_text_field(wp_unslash((string) ($_POST['anonymous_visitor_id'] ?? ''))),
            'site_url' => home_url(),
            'anonymous_daily_quota' => $this->settingsRepository->getAnonymousDailyQuota(),
            'product' => [
                'id' => absint((string) ($productPayload['product_id'] ?? 0)),
                'variation_id' => absint((string) ($productPayload['variation_id'] ?? 0)),
                'title' => sanitize_text_field((string) ($productPayload['product_title'] ?? '')),
                'description' => wp_strip_all_tags((string) ($productPayload['product_description'] ?? '')),
                'image_url' => esc_url_raw((string) ($productPayload['product_image_url'] ?? '')),
                'categories' => $this->sanitizeStringList($productPayload['product_category_terms'] ?? []),
            ],
            'customer_image' => $this->sanitizeCustomerImage($customerImage),
        ];

        $response = $this->apiClient->post('/api/wordpress/try-on/request', $payload);

        $this->sendProxyResponse($response);
    }

    public function requestTryOnStatus(): void
    {
        check_ajax_referer('dressme_try_on_status', 'nonce');

        if (!$this->settingsRepository->isConfigured()) {
            wp_send_json_error([
                'error_code' => 'NOT_CONFIGURED',
                'message' => __('DressMe API URL and key must be configured before checking a try-on status.', 'dressme'),
            ], 400);
        }

        $payload = [
            'api_key' => $this->settingsRepository->getApiKey(),
            'site_url' => home_url(),
            'job_id' => sanitize_text_field(wp_unslash((string) ($_POST['job_id'] ?? ''))),
        ];

        $response = $this->apiClient->post('/api/wordpress/try-on/status', $payload);

        $this->sendProxyResponse($response);
    }

    public function downloadImage(): void
    {
        check_ajax_referer('dressme_download_image', 'nonce');

        $imageUrl = esc_url_raw(wp_unslash((string) ($_GET['image_url'] ?? '')));
        $jobId = sanitize_text_field(wp_unslash((string) ($_GET['job_id'] ?? '')));

        if ('' === $imageUrl) {
            error_log('[DressMe] download: empty image_url');
            status_header(400);
            exit;
        }

        $apiBase = $this->settingsRepository->getApiBaseUrl();
        $apiHost = $apiBase ? parse_url($apiBase, PHP_URL_HOST) : null;
        $imageHost = parse_url($imageUrl, PHP_URL_HOST);
        $imageScheme = parse_url($imageUrl, PHP_URL_SCHEME);

        if (!in_array($imageScheme, ['http', 'https'], true)) {
            error_log('[DressMe] download: bad scheme ' . (string) $imageScheme);
            status_header(403);
            exit;
        }

        if (!$apiHost || !$imageHost || strcasecmp($apiHost, $imageHost) !== 0) {
            error_log(sprintf('[DressMe] download: host mismatch api=%s image=%s', (string) $apiHost, (string) $imageHost));
            status_header(403);
            exit;
        }

        $response = wp_remote_get($imageUrl, [
            'timeout' => 20,
            'sslverify' => apply_filters('dressme_download_sslverify', true),
        ]);

        if (is_wp_error($response)) {
            error_log('[DressMe] download wp_error: ' . $response->get_error_message());
            status_header(502);
            exit;
        }

        $code = wp_remote_retrieve_response_code($response);

        if (200 !== $code) {
            error_log(sprintf('[DressMe] download: upstream HTTP %d for %s', $code, $imageUrl));
            status_header(502);
            exit;
        }

        $body = wp_remote_retrieve_body($response);

        if ('' === $body) {
            error_log('[DressMe] download: empty body for ' . $imageUrl);
            status_header(502);
            exit;
        }

        $contentType = wp_remote_retrieve_header($response, 'content-type') ?: 'image/jpeg';
        $extension = $this->resolveExtensionFromMime((string) $contentType);
        $baseName = '' !== $jobId ? sanitize_file_name('dressme-' . $jobId) : 'dressme-look';

        nocache_headers();
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . strlen($body));
        header('Content-Disposition: attachment; filename="' . $baseName . '.' . $extension . '"');
        echo $body;
        exit;
    }

    private function resolveExtensionFromMime(string $mime): string
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));

        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function readProductPayload(): array
    {
        $rawPayload = wp_unslash((string) ($_POST['product_payload'] ?? '{}'));
        $decodedPayload = json_decode($rawPayload, true);

        return is_array($decodedPayload) ? $decodedPayload : [];
    }

    /**
     * @param mixed $values
     *
     * @return string[]
     */
    private function sanitizeStringList($values): array
    {
        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($value): string => sanitize_text_field((string) $value),
            $values
        )));
    }

    private function sanitizeCustomerImage(string $customerImage): string
    {
        $customerImage = trim($customerImage);

        if (str_starts_with($customerImage, 'data:image/')) {
            return $customerImage;
        }

        return sanitize_text_field($customerImage);
    }

    /**
     * @param array{ok: bool, status: int, body: array<string, mixed>, message: string} $response
     */
    private function sendProxyResponse(array $response): void
    {
        $status = $response['status'] > 0 ? $response['status'] : 502;
        $body = $response['body'];

        if ([] === $body) {
            $body = [
                'success' => false,
                'error_code' => 'API_UNREACHABLE',
                'message' => $response['message'],
            ];
        }

        if ($response['ok']) {
            wp_send_json_success($body, $status);
        }

        wp_send_json_error($body, $status);
    }
}

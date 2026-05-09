<?php

namespace Genesii\DressMe\Support;

final class SettingsRepository
{
    /**
     * @return mixed
     */
    public function get(string $optionName)
    {
        $defaults = Options::defaults();
        $default = $defaults[$optionName] ?? null;

        return get_option($optionName, $default);
    }

    public function isEnabled(): bool
    {
        return 'yes' === $this->get(Options::ENABLED);
    }

    public function isConfigured(): bool
    {
        return '' !== trim((string) $this->get(Options::API_KEY));
    }

    public function getButtonLabel(): string
    {
        return (string) $this->get(Options::BUTTON_LABEL);
    }

    public function getAnonymousDailyQuota(): int
    {
        return max(0, (int) $this->get(Options::ANONYMOUS_DAILY_QUOTA));
    }

    public function getVisibilityMode(): string
    {
        return (string) $this->get(Options::VISIBILITY_MODE);
    }

    /**
     * @return array<string, string>
     */
    public function getButtonStyleConfig(): array
    {
        $rawWidth = $this->sanitizeCssSize((string) $this->get(Options::BUTTON_WIDTH), '100%');

        return [
            'width' => $this->buildResponsiveWidth($rawWidth),
            'rawWidth' => $rawWidth,
            'height' => $this->sanitizeCssSize((string) $this->get(Options::BUTTON_HEIGHT), '52', 'px'),
            'radius' => $this->sanitizeCssSize((string) $this->get(Options::BUTTON_RADIUS), '8', 'px'),
            'bgColor' => $this->sanitizeHexColor((string) $this->get(Options::BUTTON_BG_COLOR), '#111111'),
            'textColor' => $this->sanitizeHexColor((string) $this->get(Options::BUTTON_TEXT_COLOR), '#ffffff'),
            'hoverBgColor' => $this->sanitizeHexColor((string) $this->get(Options::BUTTON_HOVER_BG_COLOR), '#2d2d2d'),
            'hoverTextColor' => $this->sanitizeHexColor((string) $this->get(Options::BUTTON_HOVER_TEXT_COLOR), '#ffffff'),
        ];
    }

    /**
     * @return int[]
     */
    public function getAllowedCategoryIds(): array
    {
        return $this->sanitizeIntegerList($this->get(Options::ALLOWED_CATEGORIES));
    }

    /**
     * @return int[]
     */
    public function getExcludedCategoryIds(): array
    {
        return $this->sanitizeIntegerList($this->get(Options::EXCLUDED_CATEGORIES));
    }

    /**
     * @return array<int, string>
     */
    public function getProductOverrides(): array
    {
        $rawOverrides = $this->get(Options::PRODUCT_OVERRIDES);
        $overrides = is_array($rawOverrides) ? $rawOverrides : [];
        $normalized = [];

        foreach ($overrides as $productId => $mode) {
            $productId = absint((string) $productId);
            $mode = $this->sanitizeProductMode((string) $mode);

            if (0 === $productId || Options::PRODUCT_MODE_GLOBAL === $mode) {
                continue;
            }

            $normalized[$productId] = $mode;
        }

        return $normalized;
    }

    public function getProductOverride(int $productId): string
    {
        $metaMode = get_post_meta($productId, Options::PRODUCT_META_MODE, true);

        if (is_string($metaMode) && '' !== $metaMode) {
            return $this->sanitizeProductMode($metaMode);
        }

        $overrides = $this->getProductOverrides();

        return $overrides[$productId] ?? Options::PRODUCT_MODE_GLOBAL;
    }

    /**
     * @param array<int|string, string> $overrides
     */
    public function saveProductOverrides(array $overrides): void
    {
        $normalized = [];

        foreach ($overrides as $productId => $mode) {
            $productId = absint((string) $productId);
            $mode = $this->sanitizeProductMode((string) $mode);

            if (0 === $productId || Options::PRODUCT_MODE_GLOBAL === $mode) {
                continue;
            }

            $normalized[$productId] = $mode;
        }

        update_option(Options::PRODUCT_OVERRIDES, $normalized, false);
        $this->syncProductMetaOverrides($normalized);
    }

    public function saveProductOverride(int $productId, string $mode): void
    {
        $productId = absint($productId);

        if (0 === $productId) {
            return;
        }

        $mode = $this->sanitizeProductMode($mode);
        $overrides = $this->getProductOverrides();

        if (Options::PRODUCT_MODE_GLOBAL === $mode) {
            unset($overrides[$productId]);
            delete_post_meta($productId, Options::PRODUCT_META_MODE);
        } else {
            $overrides[$productId] = $mode;
            update_post_meta($productId, Options::PRODUCT_META_MODE, $mode);
        }

        update_option(Options::PRODUCT_OVERRIDES, $overrides, false);
    }

    /**
     * @param mixed $values
     *
     * @return int[]
     */
    private function sanitizeIntegerList($values): array
    {
        $values = is_array($values) ? $values : [];

        return array_values(array_filter(array_map('absint', $values)));
    }

    private function sanitizeProductMode(string $mode): string
    {
        $allowedModes = [
            Options::PRODUCT_MODE_GLOBAL,
            Options::PRODUCT_MODE_FORCE_ENABLE,
            Options::PRODUCT_MODE_FORCE_DISABLE,
        ];

        return in_array($mode, $allowedModes, true) ? $mode : Options::PRODUCT_MODE_GLOBAL;
    }

    private function sanitizeHexColor(string $value, string $default): string
    {
        $sanitized = sanitize_hex_color($value);

        return is_string($sanitized) && '' !== $sanitized ? $sanitized : $default;
    }

    private function sanitizeCssSize(string $value, string $default, string $defaultUnit = ''): string
    {
        $value = trim($value);

        if ('' === $value) {
            return '' !== $defaultUnit ? $default . $defaultUnit : $default;
        }

        if (preg_match('/^\d+$/', $value)) {
            return $value . $defaultUnit;
        }

        if (preg_match('/^\d+(\.\d+)?(px|%|rem|em|vh|vw)$/', $value)) {
            return $value;
        }

        return '' !== $defaultUnit ? $default . $defaultUnit : $default;
    }

    private function buildResponsiveWidth(string $width): string
    {
        if ('100%' === $width) {
            return '100%';
        }

        if (str_ends_with($width, '%')) {
            return $width;
        }

        return sprintf('min(100%%, %s)', $width);
    }

    /**
     * @param array<int, string> $normalizedOverrides
     */
    private function syncProductMetaOverrides(array $normalizedOverrides): void
    {
        $previousOverrides = get_option(Options::PRODUCT_OVERRIDES, []);
        $previousProductIds = is_array($previousOverrides) ? array_map('absint', array_keys($previousOverrides)) : [];

        foreach ($previousProductIds as $productId) {
            if (!isset($normalizedOverrides[$productId])) {
                delete_post_meta($productId, Options::PRODUCT_META_MODE);
            }
        }

        foreach ($normalizedOverrides as $productId => $mode) {
            update_post_meta($productId, Options::PRODUCT_META_MODE, $mode);
        }
    }
}

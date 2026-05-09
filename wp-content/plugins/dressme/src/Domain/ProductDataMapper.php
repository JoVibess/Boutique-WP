<?php

namespace Genesii\DressMe\Domain;

use Genesii\DressMe\Support\Options;
use Genesii\DressMe\Support\SettingsRepository;
use WC_Product;

final class ProductDataMapper
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository = new SettingsRepository(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(WC_Product $product): array
    {
        $productId = $product->get_id();

        return [
            'product_id' => $productId,
            'variation_id' => 0,
            'product_title' => $this->resolveMappedValue($productId, (string) $product->get_name(), Options::TITLE_SOURCE, Options::TITLE_CUSTOM_KEY),
            'product_description' => $this->resolveDescription($product),
            'product_image_url' => $this->resolveImageUrl($product),
            'product_category_terms' => wp_get_post_terms($productId, 'product_cat', ['fields' => 'names']),
            'anonymous_daily_quota' => $this->settingsRepository->getAnonymousDailyQuota(),
        ];
    }

    private function resolveDescription(WC_Product $product): string
    {
        $productId = $product->get_id();
        $source = (string) $this->settingsRepository->get(Options::DESCRIPTION_SOURCE);
        $customKey = (string) $this->settingsRepository->get(Options::DESCRIPTION_CUSTOM_KEY);

        if ('woocommerce_description' === $source) {
            return (string) $product->get_description();
        }

        if ('woocommerce_short_description' === $source) {
            return (string) $product->get_short_description();
        }

        return $this->resolveCustomMappedValue($productId, $source, $customKey);
    }

    private function resolveImageUrl(WC_Product $product): string
    {
        $productId = $product->get_id();
        $source = (string) $this->settingsRepository->get(Options::IMAGE_SOURCE);
        $customKey = (string) $this->settingsRepository->get(Options::IMAGE_CUSTOM_KEY);

        if ('product_featured_image' === $source) {
            $imageId = $product->get_image_id();

            return $imageId ? (string) wp_get_attachment_image_url($imageId, 'full') : '';
        }

        $value = $this->resolveCustomMappedValue($productId, $source, $customKey);

        if (is_numeric($value)) {
            return (string) wp_get_attachment_image_url((int) $value, 'full');
        }

        return (string) $value;
    }

    private function resolveMappedValue(int $productId, string $defaultValue, string $sourceOption, string $customKeyOption): string
    {
        $source = (string) $this->settingsRepository->get($sourceOption);

        if ('product_title' === $source || 'post_title' === $source) {
            return $defaultValue;
        }

        $customKey = (string) $this->settingsRepository->get($customKeyOption);
        $value = $this->resolveCustomMappedValue($productId, $source, $customKey);

        return '' !== trim((string) $value) ? (string) $value : $defaultValue;
    }

    /**
     * @return mixed
     */
    private function resolveCustomMappedValue(int $productId, string $source, string $customKey)
    {
        if ('' === trim($customKey)) {
            return '';
        }

        if ('acf' === $source && function_exists('get_field')) {
            return get_field($customKey, $productId);
        }

        if ('meta' === $source) {
            return get_post_meta($productId, $customKey, true);
        }

        if ('attribute' === $source) {
            return get_post_meta($productId, 'attribute_' . sanitize_title($customKey), true);
        }

        return '';
    }
}

<?php

namespace Genesii\DressMe\Service;

use Genesii\DressMe\Support\Options;
use Genesii\DressMe\Support\SettingsRepository;
use Genesii\Kernel\Service\AbstractService;

final class ProductSettingsService extends AbstractService
{
    private SettingsRepository $settingsRepository;

    public function __construct(string $path)
    {
        $this->settingsRepository = new SettingsRepository();

        parent::__construct($path);
    }

    protected function hooks(): void
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'renderField']);
        add_action('woocommerce_process_product_meta', [$this, 'saveField']);
    }

    public function renderField(): void
    {
        global $post;

        $productId = $post instanceof \WP_Post ? $post->ID : 0;
        $value = $productId > 0
            ? $this->settingsRepository->getProductOverride($productId)
            : Options::PRODUCT_MODE_GLOBAL;

        echo '<div class="options_group">';

        woocommerce_wp_select([
            'id' => Options::PRODUCT_META_MODE,
            'label' => __('DressMe', 'dressme'),
            'description' => __('Control whether DressMe is available for this product.', 'dressme'),
            'desc_tip' => true,
            'value' => $value,
            'options' => [
                Options::PRODUCT_MODE_GLOBAL => __('Use global DressMe rules', 'dressme'),
                Options::PRODUCT_MODE_FORCE_ENABLE => __('Force enable DressMe', 'dressme'),
                Options::PRODUCT_MODE_FORCE_DISABLE => __('Disable DressMe', 'dressme'),
            ],
        ]);

        echo '</div>';
    }

    public function saveField(int $productId): void
    {
        $mode = isset($_POST[Options::PRODUCT_META_MODE])
            ? sanitize_text_field(wp_unslash((string) $_POST[Options::PRODUCT_META_MODE]))
            : Options::PRODUCT_MODE_GLOBAL;

        $this->settingsRepository->saveProductOverride($productId, $mode);
    }
}

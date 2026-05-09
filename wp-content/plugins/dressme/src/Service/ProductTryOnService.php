<?php

namespace Genesii\DressMe\Service;

use Genesii\DressMe\Domain\ProductDataMapper;
use Genesii\DressMe\Domain\ProductEligibilityResolver;
use Genesii\DressMe\Support\SettingsRepository;
use Genesii\Kernel\Service\AbstractService;

final class ProductTryOnService extends AbstractService
{
    private ProductEligibilityResolver $eligibilityResolver;
    private SettingsRepository $settingsRepository;
    private ProductDataMapper $productDataMapper;

    public function __construct(string $path)
    {
        $this->eligibilityResolver = new ProductEligibilityResolver();
        $this->settingsRepository = new SettingsRepository();
        $this->productDataMapper = new ProductDataMapper();

        parent::__construct($path);
    }

    protected function hooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('woocommerce_after_add_to_cart_button', [$this, 'renderButton']);
        add_action('wp_footer', [$this, 'renderModal']);
    }

    public function enqueueAssets(): void
    {
        if (!is_product()) {
            return;
        }

        global $product;

        if (!$product instanceof \WC_Product || !$this->eligibilityResolver->isEligible($product->get_id())) {
            return;
        }

        wp_enqueue_style(
            'dressme-front',
            plugins_url('assets/css/front.css', dirname(__DIR__, 2) . '/dressme.php'),
            [],
            '0.1.0'
        );

        $buttonStyle = $this->settingsRepository->getButtonStyleConfig();
        wp_add_inline_style(
            'dressme-front',
            sprintf(
                '.dressme-tryon-button{--dressme-button-width:%1$s;--dressme-button-height:%2$s;--dressme-button-radius:%3$s;--dressme-button-bg:%4$s;--dressme-button-color:%5$s;--dressme-button-hover-bg:%6$s;--dressme-button-hover-color:%7$s;}',
                esc_attr($buttonStyle['width']),
                esc_attr($buttonStyle['height']),
                esc_attr($buttonStyle['radius']),
                esc_attr($buttonStyle['bgColor']),
                esc_attr($buttonStyle['textColor']),
                esc_attr($buttonStyle['hoverBgColor']),
                esc_attr($buttonStyle['hoverTextColor'])
            )
        );

        wp_enqueue_script(
            'dressme-front',
            plugins_url('assets/js/front.js', dirname(__DIR__, 2) . '/dressme.php'),
            [],
            '0.1.0',
            true
        );

        wp_localize_script('dressme-front', 'dressmeTryOn', [
            'buttonLabel' => $this->settingsRepository->getButtonLabel(),
            'isConfigured' => $this->settingsRepository->isConfigured(),
            'anonymousDailyQuota' => $this->settingsRepository->getAnonymousDailyQuota(),
            'buttonStyle' => $buttonStyle,
            'productPayload' => $this->productDataMapper->buildPayload($product),
            'messages' => [
                'notConfigured' => __('DressMe is not configured yet. Add your API key in WooCommerce settings to continue.', 'dressme'),
                'uploadPrompt' => __('Choose a photo or open your camera to prepare the future try-on flow.', 'dressme'),
                'cameraUnavailable' => __('Camera access is not available in this browser yet. You can still upload a photo.', 'dressme'),
            ],
        ]);
    }

    public function renderButton(): void
    {
        global $product;

        if (!$product instanceof \WC_Product || !$this->eligibilityResolver->isEligible($product->get_id())) {
            return;
        }

        $buttonStyle = $this->settingsRepository->getButtonStyleConfig();
        $inlineStyle = $this->buildInlineButtonStyle($buttonStyle);

        printf(
            '<div class="dressme-button-slot"><button type="button" class="dressme-tryon-button" style="%1$s" data-dressme-open-modal="1" data-dressme-bg="%2$s" data-dressme-color="%3$s" data-dressme-hover-bg="%4$s" data-dressme-hover-color="%5$s">%6$s</button></div>',
            $inlineStyle,
            esc_attr($buttonStyle['bgColor']),
            esc_attr($buttonStyle['textColor']),
            esc_attr($buttonStyle['hoverBgColor']),
            esc_attr($buttonStyle['hoverTextColor']),
            esc_html($this->settingsRepository->getButtonLabel())
        );
    }

    /**
     * @param array<string, string> $buttonStyle
     */
    private function buildInlineButtonStyle(array $buttonStyle): string
    {
        $baseStyle = sprintf(
            'min-height:%1$s !important;padding:0 24px !important;border-radius:%2$s !important;background-color:%3$s !important;color:%4$s !important;border:0 !important;box-shadow:none !important;text-decoration:none !important;font-weight:600 !important;line-height:1.2 !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;white-space:nowrap !important;',
            esc_attr($buttonStyle['height']),
            esc_attr($buttonStyle['radius']),
            esc_attr($buttonStyle['bgColor']),
            esc_attr($buttonStyle['textColor'])
        );

        if ('100%' === $buttonStyle['rawWidth'] || str_ends_with($buttonStyle['rawWidth'], '%')) {
            return $baseStyle . sprintf('width:%s !important;', esc_attr($buttonStyle['width']));
        }

        return $baseStyle . sprintf(
            'width:%1$s !important;min-width:fit-content !important;max-width:100%% !important;',
            esc_attr($buttonStyle['width'])
        );
    }

    public function renderModal(): void
    {
        if (!is_product()) {
            return;
        }

        global $product;

        if (!$product instanceof \WC_Product || !$this->eligibilityResolver->isEligible($product->get_id())) {
            return;
        }

        ?>
        <div class="dressme-modal" data-dressme-modal hidden>
            <div class="dressme-modal__backdrop" data-dressme-close-modal></div>
            <div class="dressme-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="dressme-modal-title">
                <button type="button" class="dressme-modal__close" aria-label="<?php echo esc_attr__('Close DressMe modal', 'dressme'); ?>" data-dressme-close-modal>&times;</button>
                <div class="dressme-modal__header">
                    <p class="dressme-modal__eyebrow"><?php esc_html_e('DressMe', 'dressme'); ?></p>
                    <h3 id="dressme-modal-title"><?php esc_html_e('Prepare the virtual try-on experience', 'dressme'); ?></h3>
                    <p><?php esc_html_e('This first version prepares the future flow: photo capture, product payload, quota handling, and Symfony API connection.', 'dressme'); ?></p>
                </div>
                <div class="dressme-modal__grid">
                    <div class="dressme-modal__panel">
                        <h4><?php esc_html_e('Customer photo', 'dressme'); ?></h4>
                        <p data-dressme-camera-status><?php esc_html_e('Choose a source for your photo.', 'dressme'); ?></p>
                        <div class="dressme-modal__actions">
                            <button type="button" class="button" data-dressme-open-camera><?php esc_html_e('Open camera', 'dressme'); ?></button>
                            <label class="button dressme-modal__upload">
                                <span><?php esc_html_e('Upload photo', 'dressme'); ?></span>
                                <input type="file" accept="image/*" data-dressme-file-input hidden>
                            </label>
                        </div>
                        <div class="dressme-modal__preview" data-dressme-preview>
                            <span><?php esc_html_e('No photo selected yet.', 'dressme'); ?></span>
                        </div>
                    </div>
                    <div class="dressme-modal__panel">
                        <h4><?php esc_html_e('Try-on request payload', 'dressme'); ?></h4>
                        <ul class="dressme-modal__payload">
                            <li><?php esc_html_e('DressMe API key', 'dressme'); ?></li>
                            <li><?php esc_html_e('Anonymous visitor ID', 'dressme'); ?></li>
                            <li><?php esc_html_e('Mapped product title, description, and image', 'dressme'); ?></li>
                            <li><?php esc_html_e('Daily anonymous quota set by the merchant', 'dressme'); ?></li>
                        </ul>
                        <p class="dressme-modal__quota">
                            <?php
                            printf(
                                esc_html__('Current anonymous daily quota: %d generations.', 'dressme'),
                                $this->settingsRepository->getAnonymousDailyQuota()
                            );
                            ?>
                        </p>
                        <button type="button" class="button button-primary" data-dressme-generate disabled>
                            <?php esc_html_e('Generate try-on later', 'dressme'); ?>
                        </button>
                    </div>
                </div>
                <div class="dressme-modal__footer" data-dressme-feedback>
                    <?php
                    echo esc_html(
                        $this->settingsRepository->isConfigured()
                            ? __('DressMe is configured and ready for the Symfony connection phase.', 'dressme')
                            : __('DressMe still needs the API key before live generation can be enabled.', 'dressme')
                    );
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}

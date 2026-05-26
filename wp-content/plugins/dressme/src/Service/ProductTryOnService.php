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
    private bool $assetsPrinted = false;

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

        $product = $this->resolveCurrentProduct();

        if (!$product instanceof \WC_Product || !$this->eligibilityResolver->isEligible($product->get_id())) {
            return;
        }

        $this->enqueueAssetsForProduct($product);
    }

    public function renderButton(): void
    {
        $product = $this->resolveCurrentProduct();

        if (!$product instanceof \WC_Product || !$this->eligibilityResolver->isEligible($product->get_id())) {
            return;
        }

        $this->enqueueAssetsForProduct($product);

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

    private function enqueueAssetsForProduct(\WC_Product $product): void
    {
        if (wp_style_is('dressme-front', 'enqueued') && wp_script_is('dressme-front', 'enqueued')) {
            return;
        }

        wp_enqueue_style(
            'dressme-front',
            plugins_url('assets/css/front.css', dirname(__DIR__, 2) . '/dressme.php'),
            [],
            '0.2.2'
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
            '0.2.2',
            true
        );

        wp_localize_script('dressme-front', 'dressmeTryOn', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dressme_try_on_request'),
            'statusNonce' => wp_create_nonce('dressme_try_on_status'),
            'downloadNonce' => wp_create_nonce('dressme_download_image'),
            'buttonLabel' => $this->settingsRepository->getButtonLabel(),
            'isConfigured' => $this->settingsRepository->isConfigured(),
            'missingConfigurationFields' => $this->settingsRepository->getMissingConfigurationFields(),
            'anonymousDailyQuota' => $this->settingsRepository->getAnonymousDailyQuota(),
            'buttonStyle' => $buttonStyle,
            'productPayload' => $this->productDataMapper->buildPayload($product),
            'messages' => [
                'notConfigured' => __('This try-on is not ready yet. Please check your DressMe settings.', 'dressme'),
                'uploadPrompt' => __('Photo added. You can generate your preview now.', 'dressme'),
                'cameraUnavailable' => __('Camera access is not available on this device yet. You can still upload a photo.', 'dressme'),
                'missingPhoto' => __('Add your photo before generating your preview.', 'dressme'),
                'sending' => __('Generating your preview...', 'dressme'),
                'received' => __('Request received. Job ID: %s', 'dressme'),
                'failed' => __('We could not generate your preview right now.', 'dressme'),
                'processing' => __('Your preview is being generated. It will appear here automatically.', 'dressme'),
                'completed' => __('Your preview is ready.', 'dressme'),
                'statusFailed' => __('We could not refresh your preview right now.', 'dressme'),
                'download' => __('Download image', 'dressme'),
                'compressing' => __('Optimizing your photo before generation...', 'dressme'),
                'previewDefault' => __('Here is the item you have selected for the virtual try on', 'dressme'),
            ],
        ]);
    }

    private function printEnqueuedAssets(): void
    {
        if ($this->assetsPrinted) {
            return;
        }

        if (wp_style_is('dressme-front', 'enqueued')) {
            wp_print_styles(['dressme-front']);
        }

        if (wp_script_is('dressme-front', 'enqueued')) {
            wp_print_scripts(['dressme-front']);
        }

        $this->assetsPrinted = true;
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

        $product = $this->resolveCurrentProduct();

        if (!$product instanceof \WC_Product || !$this->eligibilityResolver->isEligible($product->get_id())) {
            return;
        }

        $this->enqueueAssetsForProduct($product);
        $this->printEnqueuedAssets();

        $logoUrl = plugins_url('assets/images/logo-dressme.png', dirname(__DIR__, 2) . '/dressme.php');
        $closeIconUrl = plugins_url('assets/images/cross.svg', dirname(__DIR__, 2) . '/dressme.php');
        $generateIconUrl = plugins_url('assets/images/ia.svg', dirname(__DIR__, 2) . '/dressme.php');
        $cameraIconUrl = plugins_url('assets/images/camera.svg', dirname(__DIR__, 2) . '/dressme.php');
        $uploadIconUrl = plugins_url('assets/images/upload.svg', dirname(__DIR__, 2) . '/dressme.php');
        ?>
        <div class="dressme-modal" data-dressme-modal hidden>
            <div class="dressme-modal__backdrop" data-dressme-close-modal></div>
            <div class="dressme-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="dressme-modal-title" data-dressme-state="idle">
                <img class="dressme-modal__logo" src="<?php echo esc_url($logoUrl); ?>" alt="DressMe" data-dressme-logo>
                <button type="button" class="dressme-modal__close" aria-label="<?php echo esc_attr__('Close DressMe modal', 'dressme'); ?>" data-dressme-close-modal>
                    <img src="<?php echo esc_url($closeIconUrl); ?>" alt="" aria-hidden="true">
                </button>

                <div class="dressme-modal__stage dressme-modal__stage--idle" data-dressme-stage="idle">
                    <div class="dressme-modal__header">
                        <h3 id="dressme-modal-title"><?php esc_html_e('Try it on', 'dressme'); ?></h3>
                        <p><?php esc_html_e('Add your photo, then generate a preview with this product.', 'dressme'); ?></p>
                        <p class="dressme-modal__quota dressme-modal__quota--header">
                            <?php
                            printf(
                                esc_html__('You have %d generations left today.', 'dressme'),
                                $this->settingsRepository->getAnonymousDailyQuota()
                            );
                            ?>
                        </p>
                    </div>
                    <div class="dressme-modal__grid">
                        <div class="dressme-modal__panel">
                            <h4><?php esc_html_e('Your photo', 'dressme'); ?></h4>
                            <p data-dressme-camera-status><?php esc_html_e('Choose a photo from your camera or your device.', 'dressme'); ?></p>
                            <div class="dressme-modal__actions">
                                <label class="dressme-modal__action-button dressme-modal__upload" data-dressme-open-camera>
                                    <span class="dressme-modal__action-icon" aria-hidden="true">
                                        <img src="<?php echo esc_url($cameraIconUrl); ?>" alt="">
                                    </span>
                                    <span><?php esc_html_e('Open camera', 'dressme'); ?></span>
                                    <input type="file" accept="image/*" capture="environment" data-dressme-camera-input hidden>
                                </label>
                                <label class="dressme-modal__action-button dressme-modal__upload">
                                    <span class="dressme-modal__action-icon" aria-hidden="true">
                                        <img src="<?php echo esc_url($uploadIconUrl); ?>" alt="">
                                    </span>
                                    <span><?php esc_html_e('Upload photo', 'dressme'); ?></span>
                                    <input type="file" accept="image/*" data-dressme-file-input hidden>
                                </label>
                            </div>
                            <div class="dressme-modal__preview" data-dressme-preview>
                                <span><?php esc_html_e('Your photo will appear here.', 'dressme'); ?></span>
                                <button type="button" class="dressme-modal__preview-remove" aria-label="<?php echo esc_attr__('Remove selected photo', 'dressme'); ?>" data-dressme-remove-photo hidden>
                                    <img src="<?php echo esc_url($closeIconUrl); ?>" alt="" aria-hidden="true">
                                </button>
                            </div>
                        </div>
                        <div class="dressme-modal__panel">
                            <h4><?php esc_html_e('Product preview', 'dressme'); ?></h4>
                            <p class="dressme-modal__result-caption" data-dressme-result-caption>
                                <?php esc_html_e('Here is the item you have selected for the virtual try on', 'dressme'); ?>
                            </p>
                            <div class="dressme-modal__result-media" data-dressme-result-media>
                                <span><?php esc_html_e('Product preview unavailable.', 'dressme'); ?></span>
                            </div>
                            <div class="dressme-modal__product-copy">
                                <h5 class="dressme-modal__product-title" data-dressme-product-title></h5>
                                <p class="dressme-modal__product-description" data-dressme-product-description></p>
                            </div>
                            <button type="button" class="dressme-modal__generate-button" data-dressme-generate data-dressme-disabled-hint="<?php echo esc_attr__('Add your photo to generate your preview.', 'dressme'); ?>" disabled>
                                <span><?php esc_html_e('Generate', 'dressme'); ?></span>
                                <img src="<?php echo esc_url($generateIconUrl); ?>" alt="" aria-hidden="true">
                            </button>
                        </div>
                    </div>
                    <div class="dressme-modal__footer">
                        <div class="dressme-modal__feedback-wrap">
                            <p class="dressme-modal__feedback" data-dressme-feedback>
                                <?php esc_html_e('Add your photo to generate your preview.', 'dressme'); ?>
                            </p>
                            <p class="dressme-modal__powered"><?php esc_html_e('Powered by DressMe', 'dressme'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="dressme-modal__stage dressme-modal__stage--camera" data-dressme-stage="camera" hidden>
                    <div class="dressme-modal__camera-frame">
                        <video class="dressme-modal__camera-video" data-dressme-camera-video playsinline autoplay muted></video>
                        <img class="dressme-modal__camera-snapshot" data-dressme-camera-snapshot alt="" hidden>
                    </div>
                    <div class="dressme-modal__camera-actions">
                        <button type="button" class="dressme-modal__action-button" data-dressme-camera-cancel>
                            <?php esc_html_e('Cancel', 'dressme'); ?>
                        </button>
                        <button type="button" class="dressme-modal__action-button dressme-modal__camera-shoot" data-dressme-camera-shoot>
                            <?php esc_html_e('Take photo', 'dressme'); ?>
                        </button>
                        <button type="button" class="dressme-modal__action-button" data-dressme-camera-retake hidden>
                            <?php esc_html_e('Retake', 'dressme'); ?>
                        </button>
                        <button type="button" class="dressme-modal__action-button dressme-modal__camera-shoot" data-dressme-camera-use hidden>
                            <?php esc_html_e('Use this photo', 'dressme'); ?>
                        </button>
                    </div>
                </div>

                <div class="dressme-modal__stage dressme-modal__stage--generating" data-dressme-stage="generating" aria-live="polite" hidden>
                    <div class="dressme-loader" aria-hidden="true"></div>
                    <p class="dressme-modal__generating-label"><?php esc_html_e('Generating your preview…', 'dressme'); ?></p>
                    <p class="dressme-modal__timer" data-dressme-timer>0:00</p>
                </div>

                <div class="dressme-modal__stage dressme-modal__stage--result" data-dressme-stage="result" hidden>
                    <div class="dressme-modal__result-header">
                        <h4><?php esc_html_e('Your preview', 'dressme'); ?></h4>
                        <a class="dressme-modal__download-button" href="#" data-dressme-download>
                            <?php esc_html_e('Download image', 'dressme'); ?>
                        </a>
                    </div>
                    <div class="dressme-modal__generated-media" data-dressme-generated-media></div>
                </div>
            </div>
        </div>
        <?php
    }

    private function resolveCurrentProduct(): ?\WC_Product
    {
        global $product;

        if ($product instanceof \WC_Product) {
            return $product;
        }

        $productId = get_queried_object_id();

        if ($productId > 0) {
            $resolvedProduct = wc_get_product($productId);

            if ($resolvedProduct instanceof \WC_Product) {
                return $resolvedProduct;
            }
        }

        $postId = get_the_ID();

        if (is_numeric($postId) && (int) $postId > 0) {
            $resolvedProduct = wc_get_product((int) $postId);

            if ($resolvedProduct instanceof \WC_Product) {
                return $resolvedProduct;
            }
        }

        return null;
    }
}

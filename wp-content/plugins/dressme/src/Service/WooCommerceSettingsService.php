<?php

namespace Genesii\DressMe\Service;

use Genesii\DressMe\Support\Options;
use Genesii\DressMe\Support\SettingsRepository;
use Genesii\Kernel\Service\AbstractService;
use WC_Product;

final class WooCommerceSettingsService extends AbstractService
{
    private SettingsRepository $settingsRepository;

    public function __construct(string $path)
    {
        $this->settingsRepository = new SettingsRepository();

        parent::__construct($path);
    }

    protected function hooks(): void
    {
        add_filter('woocommerce_settings_tabs_array', [$this, 'registerTab'], 50);
        add_action('woocommerce_settings_tabs_dressme', [$this, 'renderTab']);
        add_action('woocommerce_update_options_dressme', [$this, 'saveTab']);
    }

    /**
     * @param array<string, string> $tabs
     *
     * @return array<string, string>
     */
    public function registerTab(array $tabs): array
    {
        $tabs['dressme'] = __('DressMe', 'dressme');

        return $tabs;
    }

    public function renderTab(): void
    {
        $values = $this->getFormValues();
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        if (!is_array($categories)) {
            $categories = [];
        }

        $overrides = $this->buildOverridesForView();
        ?>
        <h2><?php esc_html_e('DressMe', 'dressme'); ?></h2>
        <p><?php esc_html_e('Prepare the WooCommerce store for DressMe virtual try-on before connecting the Symfony API.', 'dressme'); ?></p>
        <table class="form-table dressme-settings-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Global activation', 'dressme'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr(Options::ENABLED); ?>" value="yes" <?php checked('yes', $values[Options::ENABLED]); ?>>
                            <?php esc_html_e('Enable DressMe on the storefront', 'dressme'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Anonymous daily quota', 'dressme'); ?></th>
                    <td>
                        <input type="number" min="0" step="1" name="<?php echo esc_attr(Options::ANONYMOUS_DAILY_QUOTA); ?>" value="<?php echo esc_attr((string) $values[Options::ANONYMOUS_DAILY_QUOTA]); ?>">
                        <p class="description"><?php esc_html_e('The merchant decides how many free try-ons an anonymous visitor can trigger each day. One generated image equals one credit.', 'dressme'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Environment', 'dressme'); ?></th>
                    <td>
                        <select name="<?php echo esc_attr(Options::ENVIRONMENT); ?>">
                            <option value="test" <?php selected('test', $values[Options::ENVIRONMENT]); ?>><?php esc_html_e('Test', 'dressme'); ?></option>
                            <option value="production" <?php selected('production', $values[Options::ENVIRONMENT]); ?>><?php esc_html_e('Production', 'dressme'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('DressMe API key', 'dressme'); ?></th>
                    <td>
                        <input type="text" class="regular-text code" name="<?php echo esc_attr(Options::API_KEY); ?>" value="<?php echo esc_attr((string) $values[Options::API_KEY]); ?>">
                        <p class="description"><?php esc_html_e('Each WooCommerce store will be linked to its own DressMe key.', 'dressme'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Button label', 'dressme'); ?></th>
                    <td>
                        <input type="text" class="regular-text" name="<?php echo esc_attr(Options::BUTTON_LABEL); ?>" value="<?php echo esc_attr((string) $values[Options::BUTTON_LABEL]); ?>">
                    </td>
                </tr>
            </tbody>
        </table>

        <h3><?php esc_html_e('Button style', 'dressme'); ?></h3>
        <table class="form-table dressme-settings-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Button width', 'dressme'); ?></th>
                    <td>
                        <input type="text" class="regular-text dressme-style-input" name="<?php echo esc_attr(Options::BUTTON_WIDTH); ?>" value="<?php echo esc_attr((string) $values[Options::BUTTON_WIDTH]); ?>" data-dressme-preview-width placeholder="100% or 280px">
                        <p class="description"><?php esc_html_e('Use 100% for a fully fluid button. If you enter a fixed value like 300px or 18rem, DressMe treats it as a responsive max width.', 'dressme'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Button height', 'dressme'); ?></th>
                    <td>
                        <input type="number" min="32" step="1" class="small-text dressme-style-input" name="<?php echo esc_attr(Options::BUTTON_HEIGHT); ?>" value="<?php echo esc_attr((string) $values[Options::BUTTON_HEIGHT]); ?>" data-dressme-preview-height> px
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Border radius', 'dressme'); ?></th>
                    <td>
                        <input type="number" min="0" step="1" class="small-text dressme-style-input" name="<?php echo esc_attr(Options::BUTTON_RADIUS); ?>" value="<?php echo esc_attr((string) $values[Options::BUTTON_RADIUS]); ?>" data-dressme-preview-radius> px
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Background color', 'dressme'); ?></th>
                    <td>
                        <input type="color" class="dressme-style-input" name="<?php echo esc_attr(Options::BUTTON_BG_COLOR); ?>" value="<?php echo esc_attr((string) $values[Options::BUTTON_BG_COLOR]); ?>" data-dressme-preview-bg>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Text color', 'dressme'); ?></th>
                    <td>
                        <input type="color" class="dressme-style-input" name="<?php echo esc_attr(Options::BUTTON_TEXT_COLOR); ?>" value="<?php echo esc_attr((string) $values[Options::BUTTON_TEXT_COLOR]); ?>" data-dressme-preview-color>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Hover background', 'dressme'); ?></th>
                    <td>
                        <input type="color" class="dressme-style-input" name="<?php echo esc_attr(Options::BUTTON_HOVER_BG_COLOR); ?>" value="<?php echo esc_attr((string) $values[Options::BUTTON_HOVER_BG_COLOR]); ?>" data-dressme-preview-hover-bg>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Hover text color', 'dressme'); ?></th>
                    <td>
                        <input type="color" class="dressme-style-input" name="<?php echo esc_attr(Options::BUTTON_HOVER_TEXT_COLOR); ?>" value="<?php echo esc_attr((string) $values[Options::BUTTON_HOVER_TEXT_COLOR]); ?>" data-dressme-preview-hover-color>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="dressme-button-preview" data-dressme-button-preview-wrap>
            <p class="dressme-button-preview__label"><?php esc_html_e('Live preview', 'dressme'); ?></p>
            <div class="dressme-button-preview__stage">
                <button
                    type="button"
                    class="dressme-button-preview__button"
                    data-dressme-button-preview
                    data-label="<?php echo esc_attr((string) $values[Options::BUTTON_LABEL]); ?>"
                >
                    <?php echo esc_html((string) $values[Options::BUTTON_LABEL]); ?>
                </button>
            </div>
        </div>

        <h3><?php esc_html_e('Visibility rules', 'dressme'); ?></h3>
        <table class="form-table dressme-settings-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Display mode', 'dressme'); ?></th>
                    <td>
                        <select name="<?php echo esc_attr(Options::VISIBILITY_MODE); ?>">
                            <option value="<?php echo esc_attr(Options::VISIBILITY_ALL); ?>" <?php selected(Options::VISIBILITY_ALL, $values[Options::VISIBILITY_MODE]); ?>><?php esc_html_e('All products', 'dressme'); ?></option>
                            <option value="<?php echo esc_attr(Options::VISIBILITY_EXCLUDE); ?>" <?php selected(Options::VISIBILITY_EXCLUDE, $values[Options::VISIBILITY_MODE]); ?>><?php esc_html_e('All products except selected categories', 'dressme'); ?></option>
                            <option value="<?php echo esc_attr(Options::VISIBILITY_INCLUDE); ?>" <?php selected(Options::VISIBILITY_INCLUDE, $values[Options::VISIBILITY_MODE]); ?>><?php esc_html_e('Only selected categories', 'dressme'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Allowed categories', 'dressme'); ?></th>
                    <td>
                        <?php $this->renderCategorySelect(Options::ALLOWED_CATEGORIES, $categories, (array) $values[Options::ALLOWED_CATEGORIES]); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Excluded categories', 'dressme'); ?></th>
                    <td>
                        <?php $this->renderCategorySelect(Options::EXCLUDED_CATEGORIES, $categories, (array) $values[Options::EXCLUDED_CATEGORIES]); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <h3><?php esc_html_e('Product mapping', 'dressme'); ?></h3>
        <table class="form-table dressme-settings-table" role="presentation">
            <tbody>
                <?php $this->renderMappingRow(__('Title source', 'dressme'), Options::TITLE_SOURCE, Options::TITLE_CUSTOM_KEY, (string) $values[Options::TITLE_SOURCE], (string) $values[Options::TITLE_CUSTOM_KEY], [
                    'product_title' => __('WooCommerce product title', 'dressme'),
                    'acf' => __('ACF field', 'dressme'),
                    'meta' => __('Custom meta field', 'dressme'),
                ]); ?>
                <?php $this->renderMappingRow(__('Description source', 'dressme'), Options::DESCRIPTION_SOURCE, Options::DESCRIPTION_CUSTOM_KEY, (string) $values[Options::DESCRIPTION_SOURCE], (string) $values[Options::DESCRIPTION_CUSTOM_KEY], [
                    'woocommerce_short_description' => __('WooCommerce short description', 'dressme'),
                    'woocommerce_description' => __('WooCommerce long description', 'dressme'),
                    'acf' => __('ACF field', 'dressme'),
                    'meta' => __('Custom meta field', 'dressme'),
                ]); ?>
                <?php $this->renderMappingRow(__('Image source', 'dressme'), Options::IMAGE_SOURCE, Options::IMAGE_CUSTOM_KEY, (string) $values[Options::IMAGE_SOURCE], (string) $values[Options::IMAGE_CUSTOM_KEY], [
                    'product_featured_image' => __('WooCommerce featured image', 'dressme'),
                    'acf' => __('ACF field', 'dressme'),
                    'meta' => __('Custom meta field', 'dressme'),
                ]); ?>
            </tbody>
        </table>

        <h3><?php esc_html_e('Product exceptions', 'dressme'); ?></h3>
        <p><?php esc_html_e('Search for a product to add an override, then choose whether DressMe should be forced on or disabled for that product.', 'dressme'); ?></p>
        <div class="dressme-product-overrides" data-dressme-overrides-app>
            <div class="dressme-product-overrides__controls">
                <select class="wc-product-search" multiple="multiple" data-placeholder="<?php echo esc_attr__('Search for a product…', 'dressme'); ?>" data-action="woocommerce_json_search_products_and_variations"></select>
                <button type="button" class="button" data-dressme-add-products><?php esc_html_e('Add selected products', 'dressme'); ?></button>
            </div>
            <input type="hidden" name="<?php echo esc_attr(Options::PRODUCT_OVERRIDES); ?>" value="<?php echo esc_attr(wp_json_encode($overrides)); ?>" data-dressme-overrides-input>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Product', 'dressme'); ?></th>
                        <th><?php esc_html_e('Rule', 'dressme'); ?></th>
                        <th><?php esc_html_e('Action', 'dressme'); ?></th>
                    </tr>
                </thead>
                <tbody data-dressme-overrides-rows></tbody>
            </table>
            <p class="description"><?php echo esc_html(sprintf(__('Manual exceptions configured: %d', 'dressme'), count($overrides))); ?></p>
        </div>

        <p class="submit">
            <?php submit_button(__('Save changes', 'dressme'), 'primary', 'save', false); ?>
        </p>
        <?php
    }

    public function saveTab(): void
    {
        update_option(Options::ENABLED, isset($_POST[Options::ENABLED]) ? 'yes' : 'no', false);
        update_option(Options::BUTTON_LABEL, sanitize_text_field(wp_unslash((string) ($_POST[Options::BUTTON_LABEL] ?? ''))), false);
        update_option(Options::ANONYMOUS_DAILY_QUOTA, max(0, absint((string) ($_POST[Options::ANONYMOUS_DAILY_QUOTA] ?? 0))), false);
        update_option(Options::ENVIRONMENT, in_array((string) ($_POST[Options::ENVIRONMENT] ?? 'test'), ['test', 'production'], true) ? sanitize_text_field(wp_unslash((string) $_POST[Options::ENVIRONMENT])) : 'test', false);
        update_option(Options::API_KEY, sanitize_text_field(wp_unslash((string) ($_POST[Options::API_KEY] ?? ''))), false);
        update_option(Options::BUTTON_WIDTH, sanitize_text_field(wp_unslash((string) ($_POST[Options::BUTTON_WIDTH] ?? '100%'))), false);
        update_option(Options::BUTTON_HEIGHT, max(32, absint((string) ($_POST[Options::BUTTON_HEIGHT] ?? 52))), false);
        update_option(Options::BUTTON_RADIUS, max(0, absint((string) ($_POST[Options::BUTTON_RADIUS] ?? 8))), false);
        update_option(Options::BUTTON_BG_COLOR, sanitize_hex_color(wp_unslash((string) ($_POST[Options::BUTTON_BG_COLOR] ?? '#111111'))) ?: '#111111', false);
        update_option(Options::BUTTON_TEXT_COLOR, sanitize_hex_color(wp_unslash((string) ($_POST[Options::BUTTON_TEXT_COLOR] ?? '#ffffff'))) ?: '#ffffff', false);
        update_option(Options::BUTTON_HOVER_BG_COLOR, sanitize_hex_color(wp_unslash((string) ($_POST[Options::BUTTON_HOVER_BG_COLOR] ?? '#2d2d2d'))) ?: '#2d2d2d', false);
        update_option(Options::BUTTON_HOVER_TEXT_COLOR, sanitize_hex_color(wp_unslash((string) ($_POST[Options::BUTTON_HOVER_TEXT_COLOR] ?? '#ffffff'))) ?: '#ffffff', false);
        update_option(Options::VISIBILITY_MODE, $this->sanitizeVisibilityMode((string) ($_POST[Options::VISIBILITY_MODE] ?? Options::VISIBILITY_ALL)), false);
        update_option(Options::ALLOWED_CATEGORIES, $this->sanitizeIntegerList($_POST[Options::ALLOWED_CATEGORIES] ?? []), false);
        update_option(Options::EXCLUDED_CATEGORIES, $this->sanitizeIntegerList($_POST[Options::EXCLUDED_CATEGORIES] ?? []), false);

        update_option(Options::TITLE_SOURCE, $this->sanitizeMappingSource((string) ($_POST[Options::TITLE_SOURCE] ?? 'product_title'), ['product_title', 'acf', 'meta']), false);
        update_option(Options::TITLE_CUSTOM_KEY, sanitize_text_field(wp_unslash((string) ($_POST[Options::TITLE_CUSTOM_KEY] ?? ''))), false);
        update_option(Options::DESCRIPTION_SOURCE, $this->sanitizeMappingSource((string) ($_POST[Options::DESCRIPTION_SOURCE] ?? 'woocommerce_short_description'), ['woocommerce_short_description', 'woocommerce_description', 'acf', 'meta']), false);
        update_option(Options::DESCRIPTION_CUSTOM_KEY, sanitize_text_field(wp_unslash((string) ($_POST[Options::DESCRIPTION_CUSTOM_KEY] ?? ''))), false);
        update_option(Options::IMAGE_SOURCE, $this->sanitizeMappingSource((string) ($_POST[Options::IMAGE_SOURCE] ?? 'product_featured_image'), ['product_featured_image', 'acf', 'meta']), false);
        update_option(Options::IMAGE_CUSTOM_KEY, sanitize_text_field(wp_unslash((string) ($_POST[Options::IMAGE_CUSTOM_KEY] ?? ''))), false);

        $rawOverrides = wp_unslash((string) ($_POST[Options::PRODUCT_OVERRIDES] ?? '[]'));
        $decodedOverrides = json_decode($rawOverrides, true);
        $normalizedOverrides = [];

        if (is_array($decodedOverrides)) {
            foreach ($decodedOverrides as $override) {
                if (!is_array($override)) {
                    continue;
                }

                $productId = absint((string) ($override['id'] ?? 0));
                $mode = sanitize_text_field((string) ($override['mode'] ?? Options::PRODUCT_MODE_FORCE_DISABLE));

                if (0 === $productId) {
                    continue;
                }

                $normalizedOverrides[$productId] = $mode;
            }
        }

        $this->settingsRepository->saveProductOverrides($normalizedOverrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormValues(): array
    {
        $defaults = Options::defaults();
        $values = [];

        foreach ($defaults as $optionName => $defaultValue) {
            $values[$optionName] = $this->settingsRepository->get($optionName);
        }

        return $values;
    }

    /**
     * @return array<int, array{id: int, label: string, mode: string}>
     */
    private function buildOverridesForView(): array
    {
        $overrides = [];

        foreach ($this->settingsRepository->getProductOverrides() as $productId => $mode) {
            $product = wc_get_product($productId);

            if (!$product instanceof WC_Product) {
                continue;
            }

            $overrides[] = [
                'id' => $productId,
                'label' => sprintf('#%d %s', $productId, $product->get_name()),
                'mode' => $mode,
            ];
        }

        return $overrides;
    }

    /**
     * @param array<int, \WP_Term> $categories
     * @param mixed $selectedValues
     */
    private function renderCategorySelect(string $name, array $categories, $selectedValues): void
    {
        $selectedValues = is_array($selectedValues) ? array_map('absint', $selectedValues) : [];

        echo '<select name="' . esc_attr($name) . '[]" multiple="multiple" class="wc-enhanced-select" style="width: 420px;">';

        foreach ($categories as $category) {
            printf(
                '<option value="%1$d" %2$s>%3$s</option>',
                absint($category->term_id),
                selected(in_array((int) $category->term_id, $selectedValues, true), true, false),
                esc_html($category->name)
            );
        }

        echo '</select>';
    }

    /**
     * @param array<string, string> $choices
     */
    private function renderMappingRow(string $label, string $sourceName, string $customKeyName, string $selectedSource, string $customKey, array $choices): void
    {
        echo '<tr>';
        echo '<th scope="row">' . esc_html($label) . '</th>';
        echo '<td>';
        echo '<select name="' . esc_attr($sourceName) . '" class="dressme-mapping-source">';

        foreach ($choices as $value => $choiceLabel) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($value),
                selected($value, $selectedSource, false),
                esc_html($choiceLabel)
            );
        }

        echo '</select> ';
        echo '<input type="text" class="regular-text" name="' . esc_attr($customKeyName) . '" value="' . esc_attr($customKey) . '" placeholder="' . esc_attr__('Field key (for ACF or meta)', 'dressme') . '">';
        echo '</td>';
        echo '</tr>';
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

    private function sanitizeVisibilityMode(string $mode): string
    {
        $allowedModes = [
            Options::VISIBILITY_ALL,
            Options::VISIBILITY_EXCLUDE,
            Options::VISIBILITY_INCLUDE,
        ];

        return in_array($mode, $allowedModes, true) ? $mode : Options::VISIBILITY_ALL;
    }

    /**
     * @param string[] $allowedValues
     */
    private function sanitizeMappingSource(string $source, array $allowedValues): string
    {
        return in_array($source, $allowedValues, true) ? $source : $allowedValues[0];
    }
}

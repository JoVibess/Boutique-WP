<?php

namespace Genesii\DressMe\Support;

final class Options
{
    public const ENABLED = 'dressme_enabled';
    public const BUTTON_LABEL = 'dressme_button_label';
    public const ANONYMOUS_DAILY_QUOTA = 'dressme_anonymous_daily_quota';
    public const API_BASE_URL = 'dressme_api_base_url';
    public const API_KEY = 'dressme_api_key';
    public const ENVIRONMENT = 'dressme_environment';
    public const BUTTON_WIDTH = 'dressme_button_width';
    public const BUTTON_HEIGHT = 'dressme_button_height';
    public const BUTTON_RADIUS = 'dressme_button_radius';
    public const BUTTON_BG_COLOR = 'dressme_button_bg_color';
    public const BUTTON_TEXT_COLOR = 'dressme_button_text_color';
    public const BUTTON_HOVER_BG_COLOR = 'dressme_button_hover_bg_color';
    public const BUTTON_HOVER_TEXT_COLOR = 'dressme_button_hover_text_color';
    public const VISIBILITY_MODE = 'dressme_visibility_mode';
    public const ALLOWED_CATEGORIES = 'dressme_allowed_categories';
    public const EXCLUDED_CATEGORIES = 'dressme_excluded_categories';
    public const PRODUCT_OVERRIDES = 'dressme_product_overrides';
    public const TITLE_SOURCE = 'dressme_title_source';
    public const TITLE_CUSTOM_KEY = 'dressme_title_custom_key';
    public const DESCRIPTION_SOURCE = 'dressme_description_source';
    public const DESCRIPTION_CUSTOM_KEY = 'dressme_description_custom_key';
    public const IMAGE_SOURCE = 'dressme_image_source';
    public const IMAGE_CUSTOM_KEY = 'dressme_image_custom_key';
    public const PRODUCT_META_MODE = '_dressme_mode';

    public const PRODUCT_MODE_GLOBAL = 'global';
    public const PRODUCT_MODE_FORCE_ENABLE = 'force_enable';
    public const PRODUCT_MODE_FORCE_DISABLE = 'force_disable';

    public const VISIBILITY_ALL = 'all';
    public const VISIBILITY_EXCLUDE = 'exclude';
    public const VISIBILITY_INCLUDE = 'include';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            self::ENABLED => 'no',
            self::BUTTON_LABEL => 'Essayage virtuel',
            self::ANONYMOUS_DAILY_QUOTA => 20,
            self::API_BASE_URL => '',
            self::API_KEY => '',
            self::ENVIRONMENT => 'test',
            self::BUTTON_WIDTH => '100%',
            self::BUTTON_HEIGHT => '52',
            self::BUTTON_RADIUS => '8',
            self::BUTTON_BG_COLOR => '#111111',
            self::BUTTON_TEXT_COLOR => '#ffffff',
            self::BUTTON_HOVER_BG_COLOR => '#2d2d2d',
            self::BUTTON_HOVER_TEXT_COLOR => '#ffffff',
            self::VISIBILITY_MODE => self::VISIBILITY_ALL,
            self::ALLOWED_CATEGORIES => [],
            self::EXCLUDED_CATEGORIES => [],
            self::PRODUCT_OVERRIDES => [],
            self::TITLE_SOURCE => 'product_title',
            self::TITLE_CUSTOM_KEY => '',
            self::DESCRIPTION_SOURCE => 'woocommerce_short_description',
            self::DESCRIPTION_CUSTOM_KEY => '',
            self::IMAGE_SOURCE => 'product_featured_image',
            self::IMAGE_CUSTOM_KEY => '',
        ];
    }
}

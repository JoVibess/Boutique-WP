<?php
/**
 * Plugin Name:     DressMe
 * Plugin URI:      https://dressme.ai
 * Description:     Virtual try-on integration for WooCommerce stores.
 * Author:          Genesii
 * Author URI:      https://www.genesii.fr
 * Text Domain:     dressme
 * Domain Path:     /translations
 * Version:         0.1.0
 */

defined('ABSPATH') || exit;

$dressmePath = plugin_dir_path(__FILE__);
$dressmeKernelAutoloaders = [
    $dressmePath . 'vendor/autoload.php',
    WP_PLUGIN_DIR . '/change-me-theme/vendor/autoload.php',
];

$dressmeKernelLoaded = false;

foreach ($dressmeKernelAutoloaders as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $dressmeKernelLoaded = class_exists(\Genesii\Kernel\Service\AbstractService::class);

        if ($dressmeKernelLoaded) {
            break;
        }
    }
}

spl_autoload_register(static function (string $class) use ($dressmePath): void {
    $prefix = 'Genesii\\DressMe\\';

    if (0 !== strpos($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);
    $file = $dressmePath . 'src/' . $relativePath . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

if (!$dressmeKernelLoaded) {
    add_action('admin_notices', static function (): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__('DressMe requires the Genesii kernel autoloader. Install Composer dependencies for the plugin or keep the change-me-theme plugin available.', 'dressme');
        echo '</p></div>';
    });

    return;
}

add_action('plugins_loaded', static function () use ($dressmePath): void {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', static function (): void {
            if (!current_user_can('activate_plugins')) {
                return;
            }

            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('DressMe requires WooCommerce to be active.', 'dressme');
            echo '</p></div>';
        });

        return;
    }

    load_plugin_textdomain('dressme', false, dirname(plugin_basename(__FILE__)) . '/translations');

    new \Genesii\DressMe\Service\AdminAssetsService($dressmePath);
    new \Genesii\DressMe\Service\ProductSettingsService($dressmePath);
    new \Genesii\DressMe\Service\WooCommerceSettingsService($dressmePath);
    new \Genesii\DressMe\Service\WordPressApiBridgeService($dressmePath);
    new \Genesii\DressMe\Service\ProductTryOnService($dressmePath);
});

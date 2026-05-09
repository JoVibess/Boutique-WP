<?php

namespace Genesii\DressMe\Service;

use Genesii\Kernel\Service\AbstractService;

final class AdminAssetsService extends AbstractService
{
    protected function hooks(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        $screen = get_current_screen();

        if (null === $screen) {
            return;
        }

        $isDressMeSettings = 'woocommerce_page_wc-settings' === $screen->id
            && isset($_GET['tab'])
            && 'dressme' === sanitize_key((string) $_GET['tab']);

        $isProductEditor = in_array($screen->id, ['product', 'edit-product'], true);

        if (!$isDressMeSettings && !$isProductEditor) {
            return;
        }

        wp_enqueue_style(
            'dressme-admin',
            plugins_url('assets/css/admin.css', dirname(__DIR__, 2) . '/dressme.php'),
            [],
            '0.1.0'
        );

        wp_enqueue_script(
            'dressme-admin',
            plugins_url('assets/js/admin.js', dirname(__DIR__, 2) . '/dressme.php'),
            ['jquery'],
            '0.1.0',
            true
        );
    }
}

<?php

namespace Genesii\Service;

use Genesii\Kernel\Service\AbstractService;

final class WooCommerceTemplates extends AbstractService
{
    protected function hooks(): void
    {
        // Surcharger les templates WooCommerce depuis le plugin
        add_filter('woocommerce_locate_template', [&$this, 'overrideTemplates'], 10, 3);

        // Nombre de produits par page (via ACF options)
        add_filter('loop_shop_per_page', [&$this, 'productsPerPage'], 20);

    }

    /**
     * Force WooCommerce à chercher les templates dans :
     * /wp-content/plugins/change-me-theme/templates/woocommerce/
     */
    public function overrideTemplates(string $template, string $template_name, string $template_path): string
    {
        $plugin_template_dir = $this->path . 'templates/woocommerce/';

        $candidate = $plugin_template_dir . $template_name;

        if (file_exists($candidate)) {
            return $candidate;
        }

        return $template;
    }

    /**
     * Nombre de produits par page (ACF : nb_products_per_page)
     */
    public function productsPerPage(int $cols): int
    {
        if (function_exists('get_field')) {
            $val = get_field('nb_products_per_page', 'option');
            if (!empty($val) && is_numeric($val)) {
                return (int) $val;
            }
        }

        return $cols;
    }
}

<?php

namespace Genesii\Shortcode;

use Genesii\Kernel\Shortcode\AbstractShortcode;

final class ExempleShortcode extends AbstractShortcode {

    const CODE = "exemple-shortcode";

    protected function do(?array $args): void {
        // ...
        // ici, actions à faire à l'utilisation du shortcode
    }
}

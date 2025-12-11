<?php

namespace Genesii\Service;

use Genesii\Kernel\Service\AbstractService;

final class Initialisation extends AbstractService {

    protected function hooks(): void 
    {
        // ...
        // ici, définir les hooks et les méthodes à appeler dans ce service, exemple :

        add_action('init', [&$this, 'monAction']);
    }


    public function monAction() 
    {
        // ...
        // action personnalisée à effectuer sur le hook dont l'action est définie ci-dessus
    }
}

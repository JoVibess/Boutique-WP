<?php

namespace Genesii\Service;

use Genesii\Kernel\Service\AbstractService;

final class MenuStylerService extends AbstractService
{
    protected function hooks(): void
    {
        // Applique .dropdown aux li ayant un sous-menu
        add_filter('nav_menu_css_class', [$this, 'addDropdownClassToParent'], 10, 3);

        // Modifie les attributs du lien parent (ajoute .dropdown-toggle)
        add_filter('nav_menu_link_attributes', [$this, 'addDropdownToggle'], 10, 3);

        // Ajoute .dropdown-menu au UL des sous-menus
        add_filter('nav_menu_submenu_css_class', [$this, 'addDropdownMenuClass'], 10, 3);
    }

    /**
     * Ajoute la classe .dropdown aux <li> contenant un sous-menu
     */
    public function addDropdownClassToParent($classes, $item, $args)
    {
        if (in_array('menu-item-has-children', $classes)) {
            $classes[] = 'dropdown';
        }
        return $classes;
    }

    /**
     * Ajoute .dropdown-toggle + data attribute au <a> parent
     */
    public function addDropdownToggle($atts, $item, $args)
    {
        if (in_array('menu-item-has-children', $item->classes)) {

            // Classe toggle
            if (isset($atts['class'])) {
                $atts['class'] .= ' dropdown-toggle';
            } else {
                $atts['class'] = 'dropdown-toggle';
            }

            // On empêche le lien parent de rediriger
            $atts['href'] = '#';

            // Pour dropdown JS si besoin (ou à retirer si tu veux purement CSS)
            $atts['data-bs-toggle'] = 'dropdown';
        }

        return $atts;
    }

    /**
     * Ajoute .dropdown-menu aux UL enfants
     */
    public function addDropdownMenuClass($classes, $args, $depth)
    {
        // Seulement au premier niveau (catégories T-shirts, Pulls…)
        if ($depth === 0) {
            $classes[] = 'dropdown-menu';
        }

        return $classes;
    }
}

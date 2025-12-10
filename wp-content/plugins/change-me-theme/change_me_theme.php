<?php
/**
 * Plugin Name:     [ChangeMe] Thème
 * Plugin URI:      https://www.genesii.fr
 * Description:     Plugin servant à faire fonctionner le thème.
 * Author:          Genesii
 * Author URI:      https://www.genesii.fr
 * Text Domain:     genesii
 * Domain Path:     /translations
 * Version:         0.0.1
 */

$path = plugin_dir_path(__FILE__);

require_once($path . 'vendor/autoload.php');

use Genesii\Kernel\Plugin;

new Plugin($path);
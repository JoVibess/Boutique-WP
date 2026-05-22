<?php

define('DB_NAME', getenv('WORDPRESS_DB_NAME') ?: 'boutique_wp');
define('DB_USER', getenv('WORDPRESS_DB_USER') ?: 'wordpress');
define('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') ?: '');
define('DB_HOST', getenv('WORDPRESS_DB_HOST') ?: 'db');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('AUTH_KEY', getenv('WORDPRESS_AUTH_KEY') ?: 'change-me-auth-key');
define('SECURE_AUTH_KEY', getenv('WORDPRESS_SECURE_AUTH_KEY') ?: 'change-me-secure-auth-key');
define('LOGGED_IN_KEY', getenv('WORDPRESS_LOGGED_IN_KEY') ?: 'change-me-logged-in-key');
define('NONCE_KEY', getenv('WORDPRESS_NONCE_KEY') ?: 'change-me-nonce-key');
define('AUTH_SALT', getenv('WORDPRESS_AUTH_SALT') ?: 'change-me-auth-salt');
define('SECURE_AUTH_SALT', getenv('WORDPRESS_SECURE_AUTH_SALT') ?: 'change-me-secure-auth-salt');
define('LOGGED_IN_SALT', getenv('WORDPRESS_LOGGED_IN_SALT') ?: 'change-me-logged-in-salt');
define('NONCE_SALT', getenv('WORDPRESS_NONCE_SALT') ?: 'change-me-nonce-salt');

$table_prefix = getenv('WORDPRESS_TABLE_PREFIX') ?: 'wp_';

define('WP_DEBUG', filter_var(getenv('WORDPRESS_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN));

if (
    isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
    && 'https' === strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO'])
) {
    $_SERVER['HTTPS'] = 'on';
}

if (getenv('WORDPRESS_HOME')) {
    define('WP_HOME', getenv('WORDPRESS_HOME'));
}

if (getenv('WORDPRESS_SITEURL')) {
    define('WP_SITEURL', getenv('WORDPRESS_SITEURL'));
}

if (getenv('WP_ENVIRONMENT_TYPE')) {
    define('WP_ENVIRONMENT_TYPE', getenv('WP_ENVIRONMENT_TYPE'));
}

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';

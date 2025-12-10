<?php
/**
 * Plugin Name: Emails Catch All
 * Plugin URI:  https://iuliacazan.ro/emails-catch-all/
 * Description: This plugin allows you to configure an email address that can receive a copy or replace the recipients of all the emails sent from the site. Additionally, you can set the content type, keep a log of outgoing messages, apply SMTP settings, or disable all emails.
 * Text Domain: secas
 * Domain Path: /langs
 * Version:     3.5.3
 * Author:      Iulia Cazan
 * Author URI:  https://profiles.wordpress.org/iulia-cazan
 * Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ
 * License:     GPL2
 *
 * @package ic-devops
 *
 * Copyright (C) 2016-2025 Iulia Cazan
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// The current plugin version of BD.
define( 'SISANU_EMAILS_CATCH_ALL_DB_VERSION', 3.53 );

// The current plugin custom table name.
define( 'SISANU_EMAILS_CATCH_ALL_TABLE', 'emails_catch_all' );

define( 'ECA_PLUGIN_VERSION', 3.53 );
define( 'ECA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ECA_PLUGIN_SLUG', 'secas' );
define( 'ECA_NETWORK_SCREEN', ( is_multisite() && is_network_admin() ) );

/**
 * Class for Emails Catch All.
 */
class SISANU_Emails_Catch_All {

	const PLUGIN_NAME        = 'Emails Catch All';
	const PLUGIN_SUPPORT_URL = 'https://wordpress.org/support/plugin/emails-catch-all/';
	const PLUGIN_TRANSIENT   = 'secas-plugin-notice';

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Class settings.
	 *
	 * @var object
	 */
	public static $settings;

	/**
	 * Number of records per page.
	 *
	 * @var integer
	 */
	public static $records_per_page = 10;

	/**
	 * URL to the plugin.
	 *
	 * @var string
	 */
	public static $plugin_url = 'options-general.php?page=emails-catch-all-settings';

	/**
	 * URL to the network plugin.
	 *
	 * @var string
	 */
	public static $network_plugin_url = 'network/settings.php?page=emails-catch-all-settings';

	/**
	 * The plugin option name.
	 *
	 * @var string
	 */
	public static $option_name = 'emails_catch_all_settings';

	/**
	 * The plugin option prefix.
	 *
	 * @var string
	 */
	public static $option_prefix = 'SECAS_';

	/**
	 * The plugin option name.
	 *
	 * @var string
	 */
	public static $cron_hook = 'secas_cleanup_hook';

	/**
	 * The last inserted id.
	 *
	 * @var string
	 */
	public static $last_insert_id = 0;

	/**
	 * The plugin table name.
	 *
	 * @var string
	 */
	public static $table = SISANU_EMAILS_CATCH_ALL_TABLE;

	/**
	 * Get active object instance.
	 *
	 * @return object
	 */
	public static function get_instance() { // phpcs:ignore
		if ( ! self::$instance ) {
			self::$instance = new SISANU_Emails_Catch_All();
		}
		return self::$instance;
	}

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		$ob_class = get_called_class();
		self::get_options();
		self::table_name();

		if ( self::use_email_settings() ) {
			if ( ! empty( self::$settings->just_record ) ) {
				self::handle_enabled();
			} elseif ( 'disable' === self::$settings->recipient ) {
				self::handle_disabled();
			} elseif ( ! empty( self::$settings->email ) ) {
				self::handle_enabled();
			}
		}

		add_action( 'admin_init', [ $ob_class, 'update_settings' ] );
		if ( self::show_admin_menu() ) {
			add_action( 'admin_menu', [ $ob_class, 'admin_menu' ] );
		}
		add_action( 'network_admin_menu', [ $ob_class, 'network_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $ob_class, 'load_assets' ] );

		$action_links = 'plugin_action_links_' . plugin_basename( __FILE__ );
		add_filter( $action_links, [ $ob_class, 'plugin_action_links' ] );
		if ( ECA_NETWORK_SCREEN ) {
			add_filter( 'network_admin_' . $action_links, [ $ob_class, 'plugin_action_links' ] );
		}

		if ( is_admin() ) {
			add_action( 'wp_ajax_secas_navigate_to_page', [ $ob_class, 'load_emails_page' ] );
		}

		add_action( 'admin_notices', [ $ob_class, 'plugin_admin_notices' ] );
		add_action( 'network_admin_notices', [ $ob_class, 'plugin_admin_notices' ] );
		add_action( 'wp_ajax_plugin-deactivate-notice-secas', [ $ob_class, 'plugin_admin_notices_cleanup' ] );
		add_action( 'init', [ $ob_class, 'load_textdomain' ] );
		add_action( 'plugins_loaded', [ $ob_class, 'plugin_ver_check' ] );
		add_action( 'shutdown', [ $ob_class, 'cron_sanity_check' ] );
		add_action( 'secas_cleanup_hook', [ $ob_class, 'secas_cleanup_hook' ], 10, 2 );
		add_action( 'admin_footer', [ $ob_class, 'menu_style' ] );
	}

	/**
	 * Use the plugin settings.
	 *
	 * @return bool
	 */
	public static function use_email_settings(): bool {
		if ( ! is_multisite() ) {
			// Fail-fast, this is a single-site installation.
			return true;
		}

		$settings = self::get_network_options();
		$blog_id  = get_current_blog_id();
		if ( empty( $settings->sites_options[ $blog_id ]['enabled'] ) ) {
			// The plugin is not enabled for use on the current site.
			return false;
		}

		return true;
	}

	/**
	 * Use the plugin networks settings or nor.
	 *
	 * @param  mixed $blog_id The blog id.
	 * @return bool
	 */
	public static function inherit_network_settings( $blog_id = null ): bool { // phpcs:ignore
		if ( ! is_multisite() || ECA_NETWORK_SCREEN ) {
			if ( null === $blog_id ) {
				// Fail-fast, this is a single-site installation.
				return false;
			}
		}

		$settings = self::get_network_options();
		$blog_id  = ( null === $blog_id ) ? get_current_blog_id() : (int) $blog_id;
		if ( empty( $settings->sites_options[ $blog_id ]['enabled'] ) ) {
			// The plugin is not enabled for use on the current site.
			return false;
		}

		if ( ! empty( $settings->sites_options[ $blog_id ]['inherit'] ) ) {
			// The plugin is enabled for use on the current site and has it's own setting.
			return true;
		}

		return false;
	}

	/**
	 * Show the admin menu.
	 */
	public static function show_admin_menu(): bool {
		if ( ! is_multisite() ) {
			// Fail-fast, this is a single-site installation.
			return true;
		}

		$settings = self::get_network_options();
		$blog_id  = get_current_blog_id();
		if ( empty( $settings->sites_options[ $blog_id ]['enabled'] ) ) {
			// The plugin is not enabled for use on the current site.
			return false;
		}

		if ( empty( $settings->sites_options[ $blog_id ]['inherit'] ) ) {
			// The plugin is enabled for use on the current site and has it's own setting.
			return true;
		}

		return false;
	}

	/**
	 * Filters and actions for when the catch all is enabled.
	 */
	public static function handle_enabled() {
		$ob_class = get_called_class();
		// Enable the plugin filters only if this is necessary.
		add_filter( 'wp_mail', [ $ob_class, 'wp_mail_catch_all' ], PHP_INT_MAX );
		add_filter( 'wp_mail_content_type', [ $ob_class, 'set_content_type' ], PHP_INT_MAX );

		// Maybe alter the mailer settings.
		add_action( 'phpmailer_init', [ $ob_class, 'maybe_phpmailer_settings' ], PHP_INT_MAX );
	}

	/**
	 * Filters and actions for when the catch all is disabled.
	 */
	public static function handle_disabled() {
		$ob_class = get_called_class();
		add_filter( 'wp_mail', [ $ob_class, 'wp_mail_disable_all' ], PHP_INT_MAX );
		add_action( 'phpmailer_init', [ $ob_class, 'maybe_phpmailer_disable' ], PHP_INT_MAX );
	}

	/**
	 * Load text domain for internalization
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'secas', false, basename( __DIR__ ) . '/langs/' );
	}

	/**
	 * Return the specified email content type.
	 *
	 * @param  string $content_type Initial content type.
	 * @return string
	 */
	public static function set_content_type( $content_type = '' ): string {
		if ( 'html' === self::$settings->content_type ) {
			return 'text/html';
		} elseif ( 'plain' === self::$settings->content_type ) {
			return 'text/plain';
		}

		// Fallback to default.
		return $content_type;
	}

	/**
	 * Get the plugin default settings.
	 */
	public static function get_options_defaults(): array {
		return [
			'email'              => '',
			'just_record'        => false,
			'recipient'          => 'receive',
			'history'            => true,
			'content_type'       => 'html',
			'smtp_auth'          => false,
			'smtp_host'          => '',
			'smtp_port'          => 25,
			'smtp_uname'         => '',
			'smtp_upass'         => '',
			'smtp_secure'        => 'tls',
			'smtp_from'          => '',
			'smtp_from_name'     => '',
			'deactivate_cleanup' => false,
			'compact_view'       => false,
			'auto_cleanup'       => 0,
			'email_test'         => '',
		];
	}

	/**
	 * Get the plugin prepared settings.
	 *
	 * @param  array $options  The option list.
	 * @param  array $defaults The option defaults.
	 * @return object
	 */
	public static function get_options_prepared( $options, $defaults ) { // phpcs:ignore
		$defaults = ( empty( $defaults ) ) ? self::get_options_defaults() : $defaults;
		if ( is_object( $options ) ) {
			$options = (array) $options;
		}
		$settings = wp_parse_args( $options, $defaults );
		$bool     = [ 'just_record', 'history', 'smtp_auth', 'deactivate_cleanup', 'compact_view' ];
		foreach ( $bool as $k ) {
			$settings[ $k ] = (bool) $settings[ $k ];
		}

		$settings['content_type'] = empty( $settings['content_type'] ) ? 'auto' : $settings['content_type'];

		$the_settings = (object) $settings;

		$the_settings->icons_list = [
			'receive' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="#00b341" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 17h-8l-3.5 -5h-6.5" /><path d="M21 7h-8l-3.495 5" /><path d="M18 10l3 -3l-3 -3" /><path d="M18 20l3 -3l-3 -3" /></svg>',
			'replace' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="#00bfd8" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7h5l3.5 5h9.5" /><path d="M3 17h5l3.495 -5" /><path d="M18 15l3 -3l-3 -3" /></svg>',
			'disable' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="#ff4500" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M10 10l4 4m0 -4l-4 4" /></svg>',
			'record'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="#ffbf00" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="12 8 12 12 14 14" /><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5" /></svg>',
			''        => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="#00bfd8" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2" /><polyline points="3 7 12 13 21 7" /></svg>',
		];

		if ( true === $the_settings->just_record ) {
			$the_settings->icon = $the_settings->icons_list['record'];
		} else {
			$the_settings->icon = $the_settings->icons_list[ $the_settings->recipient ];
		}

		return $the_settings;
	}

	/**
	 * Get the plugin settings.
	 */
	public static function get_options() {
		$defaults = self::get_options_defaults();

		if ( ECA_NETWORK_SCREEN ) {
			$defaults['sites_options'] = [];

			$sites_list = get_sites( [ 'network_id' => get_current_network_id() ] );
			if ( ! empty( $sites_list ) ) {
				foreach ( $sites_list as $site ) {
					$defaults['sites_options'][ $site->blog_id ] = [
						'blog_id' => $site->blog_id,
						'enabled' => false,
						'inherit' => false,
					];
				}
			}
			$options      = get_network_option( get_current_network_id(), self::$option_name, [] );
			$keys_default = array_keys( $defaults['sites_options'] );
			$keys_options = ( ! empty( $options['sites_options'] ) ) ? array_keys( $options['sites_options'] ) : [];
			$keys_diff    = array_diff( $keys_default, $keys_options );
			if ( ! empty( $keys_diff ) ) {
				foreach ( $keys_diff as $id ) {
					$options['sites_options'][ $id ] = [
						'blog_id' => $id,
						'enabled' => false,
						'inherit' => false,
					];
				}
			}
		} elseif ( self::inherit_network_settings() ) {
			$options = get_network_option( get_current_network_id(), self::$option_name, [] );
			if ( 'record' === $options['recipient'] ) {
				$options['just_record'] = true;
				$options['recipient']   = '';
			}
			unset( $options['sites_options'] );
		} else {
			$options = get_option( self::$option_name, [] );
		}

		self::$settings = self::get_options_prepared( $options, $defaults );
	}

	/**
	 * Get the network settings.
	 *
	 * @return object
	 */
	public static function get_network_options(): object {
		return (object) get_network_option( get_current_network_id(), self::$option_name, [] );
	}

	/**
	 * Get the specified site options settings.
	 *
	 * @param  int $id Blog id.
	 * @return object
	 */
	public static function get_the_blog_options( int $id = 0 ): object {
		if ( is_multisite() ) {
			// Collect the current settings.
			$current = self::$settings;
			switch_to_blog( $id );

			$defaults = self::get_options_defaults();
			if ( self::inherit_network_settings( $id ) ) {
				$options = get_network_option( get_current_network_id(), self::$option_name, [] );
				unset( $options['sites_options'] );
			} else {
				$options = get_option( self::$option_name, [] );
			}

			$site_options = self::get_options_prepared( $options, $defaults );

			// Put back the settings.
			restore_current_blog();
			self::$settings = $current;

			return $site_options;
		}

		return self::$settings;
	}

	/**
	 * Returns the menu icon.
	 */
	public static function menu_icon(): string {
		return '<div class="wp-menu-image dashicons-before svg secas">' . str_replace( '="24"', '="18"', self::$settings->icon ) . '</div>';
	}

	/**
	 * Network admin menu.
	 */
	public static function network_admin_menu() {
		add_submenu_page(
			'settings.php',
			__( 'Emails Catch All', 'secas' ),
			self::menu_icon() . __( 'Emails Catch All', 'secas' ) . '<div class="clear"></div>',
			'manage_options',
			'emails-catch-all-settings',
			[ get_called_class(), 'emails_catch_all_settings' ]
		);
	}

	/**
	 * Add the plugin menu.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'options-general.php',
			__( 'Emails Catch All', 'secas' ),
			self::menu_icon() . __( 'Emails Catch All', 'secas' ) . '<div class="clear"></div>',
			'manage_options',
			'emails-catch-all-settings',
			[ get_called_class(), 'emails_catch_all_settings' ]
		);
	}

	/**
	 * Load scripts and styles used by the plugin.
	 */
	public static function load_assets() {
		$uri = $_SERVER['REQUEST_URI']; // phpcs:ignore
		if ( ! substr_count( $uri, 'page=emails-catch-all-settings' ) ) {
			// Fail-fast, the assets should not be loaded.
			return;
		}

		$dir = trailingslashit( plugin_dir_path( __FILE__ ) );
		$url = trailingslashit( plugins_url( '/', plugin_basename( __FILE__ ) ) );
		if ( file_exists( $dir . 'build/index.asset.php' ) ) {
			$deps = require_once $dir . 'build/index.asset.php';
		} else {
			$deps = [
				'dependencies' => [],
				'version'      => filemtime( $dir . 'build/index.js' ),
			];
		}

		wp_register_script( 'secas', $url . 'build/index.js', [], $deps['version'], true );
		wp_localize_script( 'secas', 'secasSettings', [
			'ajaxUrl'           => ECA_NETWORK_SCREEN ? admin_url( 'admin-ajax.php?network=on' ) : admin_url( 'admin-ajax.php' ),
			'cleanupConfirmAll' => __( 'Are you sure you want to delete all the records?', 'secas' ),
			'cleanupConfirmOne' => __( 'Are you sure you want to delete this record?', 'secas' ),
		] );
		wp_enqueue_script( 'secas' );
		wp_enqueue_style( 'secas', $url . 'build/style-index.css', [], $deps['version'] );
		wp_add_inline_style( 'secas', self::preset_colors() );
	}

	/**
	 * Make preset colors tokens.
	 */
	public static function preset_colors(): string {
		global $_wp_admin_css_colors;

		$user_id = get_current_user_id();
		$scheme  = get_user_option( 'admin_color', $user_id );
		$colors  = $_wp_admin_css_colors[ $scheme ]->colors ?? [];
		$dark    = $colors[0] ?? '#1e1e1e';
		$main    = $colors[2] ?? '#2271b1';
		if ( 'light' === $scheme ) {
			$main = $colors[3] ?? '#2271b1';
		} elseif ( 'modern' === $scheme ) {
			$main = $colors[1] ?? '#2271b1';
		} elseif ( 'blue' === $scheme ) {
			$main = '#e1a948';
		} elseif ( 'midnight' === $scheme ) {
			$main = $colors[3] ?? '#2271b1';
		}

		// Return the minified string.
		$style = ':root { --eca--color-main: ' . $main . '; --eca--color-faded: ' . $main . '25; --eca--color-dim: ' . $main . 'cc; }';
		$style = ! empty( $style ) ? trim( preg_replace( '/\s\s+/', ' ', $style ) ) : '';
		return $style;
	}

	/**
	 * Maybe update the plugin settings and send a test email.
	 */
	public static function update_settings() {
		// User can save the options.
		$used_nonce = filter_input( INPUT_POST, '_secas_settings_nonce', FILTER_DEFAULT );
		if ( ! empty( $used_nonce ) && wp_verify_nonce( $used_nonce, '_secas_settings_save' ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Action not allowed.', 'secas' ) );
			}

			$settings   = (array) self::$settings;
			$posted     = filter_input( INPUT_POST, '_secas_settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$update0    = filter_input( INPUT_POST, 'save-settings-0', FILTER_DEFAULT );
			$update1    = filter_input( INPUT_POST, 'save-settings-1', FILTER_DEFAULT );
			$update2    = filter_input( INPUT_POST, 'save-settings-2', FILTER_DEFAULT );
			$update3    = filter_input( INPUT_POST, 'save-settings-3', FILTER_DEFAULT );
			$maybe_test = false;

			if ( ! empty( $update0 ) ) {
				if ( ! empty( $posted['just_record'] ) ) {
					$settings['just_record'] = true;
					$settings['history']     = true;
				} else {
					$settings['just_record'] = false;
				}

				$settings['deactivate_cleanup'] = ! empty( $posted['deactivate_cleanup'] );
				$settings['compact_view']       = ! empty( $posted['compact_view'] );
				$settings['auto_cleanup']       = isset( $posted['auto_cleanup'] ) ? (int) $posted['auto_cleanup'] : 0;

				self::update_the_option( self::$option_name, $settings );
				if ( isset( $posted['test'] ) ) {
					$maybe_test = true;
				}
			} elseif ( ! empty( $update1 ) ) {
				$settings['email'] = $posted['email'] ?? '';
				if ( ! empty( $settings['email'] ) ) {
					$settings['email'] = trim( $settings['email'] );
				}

				$settings['email_test']   = $posted['email_test'] ?? '';
				$settings['recipient']    = $posted['recipient'] ?? '';
				$settings['content_type'] = $posted['content_type'];
				$settings['history']      = ! empty( $posted['history'] );
				$settings['auto_cleanup'] = isset( $posted['auto_cleanup'] ) ? (int) $posted['auto_cleanup'] : 0;

				self::update_the_option( self::$option_name, $settings );
				if ( isset( $posted['test'] ) ) {
					$maybe_test = true;
				}

				if ( isset( $posted['dummy'] ) ) {
					ob_start();
					if ( file_exists( __DIR__ . '/inc/dummy.php' ) ) {
						include_once __DIR__ . '/inc/dummy.php';

						if ( function_exists( 'eca_helper_add_dummy_content' ) ) {
							eca_helper_add_dummy_content();
						}
					}
					ob_clean();
				}
			} elseif ( ! empty( $update2 ) ) {
				$settings['smtp_auth']      = ! empty( $posted['smtp_auth'] );
				$settings['smtp_host']      = $posted['smtp_host'];
				$settings['smtp_port']      = $posted['smtp_port'];
				$settings['smtp_uname']     = $posted['smtp_uname'];
				$settings['smtp_upass']     = $posted['smtp_upass'];
				$settings['smtp_secure']    = $posted['smtp_secure'];
				$settings['smtp_from']      = $posted['smtp_from'];
				$settings['smtp_from_name'] = $posted['smtp_from_name'];
				self::update_the_option( self::$option_name, $settings );
			} elseif ( ! empty( $update3 ) ) {
				if ( ECA_NETWORK_SCREEN ) {
					foreach ( $settings['sites_options'] as $id => $site ) {
						$settings['sites_options'][ $id ]['enabled'] = ( ! empty( $posted['sites_options'][ $id ]['enabled'] ) );
						$settings['sites_options'][ $id ]['inherit'] = ( ! empty( $posted['sites_options'][ $id ]['inherit'] ) );
					}
					update_network_option( get_current_network_id(), self::$option_name, $settings );
				}
			}

			self::get_options();
			if ( true === $maybe_test ) {
				self::send_test_email();
			}

			delete_transient( self::$cron_hook . '_check' );
			add_action( 'admin_notices', [ get_called_class(), 'on_settings_update_notice' ] );
			add_action( 'network_admin_notices', [ get_called_class(), 'on_settings_update_notice' ] );
			self::get_options();
		}
	}

	/**
	 * Send a test email.
	 *
	 * @return void|object
	 */
	public static function send_test_email() { // phpcs:ignore
		if ( is_multisite() ) {
			$blogname = $GLOBALS['current_site']->site_name;
		} else {
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		}

		$recipient = get_option( 'admin_email', true );
		if ( ! empty( self::$settings->email_test ) ) {
			$recipient = self::$settings->email_test;
		}

		// Translators: %s site name.
		$title   = sprintf( __( '[%s] Test Email Catch All', 'secas' ), $blogname );
		$message = sprintf(
			// Translators: %1$s from %2$s date %3$s to %4$s url.
			__( 'This is <em>a sample test email</em> from <b>%1$s</b>, sent on <u>%2$s</u>, having the initial recipient <b>%3$s</b>.<br />Visit the site at <a href="%4$s">%4$s</a>', 'secas' ),
			$blogname,
			current_time( 'mysql' ),
			str_replace( ',', '</b>, <b>', $recipient ),
			get_site_url()
		);

		if ( 1 === wp_rand( 1, 10 ) % 2 ) {
			$message = str_replace( '</b>.<br />', '</b>. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. <br /><br />Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. <br />', $message );
		}

		$user_email = $recipient;
		$headers    = [];
		$headers[]  = 'Cc: ' . $user_email;
		$headers[]  = 'Bcc: ' . $user_email;
		$result     = false;
		if ( ! empty( $message ) ) {
			add_action( 'wp_mail_failed', [ get_called_class(), 'on_mail_error' ], 10, 1 );
			$result = wp_mail( $user_email, wp_specialchars_decode( $title ), $message, $headers );
			if ( true === $result ) {
				add_action( 'admin_notices', [ get_called_class(), 'on_mail_success_notice' ] );
				add_action( 'network_admin_notices', [ get_called_class(), 'on_mail_success_notice' ] );
			}
		}

		return $result;
	}

	/**
	 * Add the admin error message for failed email test.
	 *
	 * @param object $wp_error WP_Error object.
	 */
	public static function on_mail_error( $wp_error ) { // phpcs:ignore
		if ( ! empty( $wp_error ) ) {
			global $secas_test_mail_error;
			$secas_test_mail_error = $wp_error;
			add_action( 'admin_notices', [ get_called_class(), 'on_mail_error_notice' ] );
			add_action( 'network_admin_notices', [ get_called_class(), 'on_mail_error_notice' ] );
		}
	}

	/**
	 * Output the admin error message for failed email test.
	 */
	public static function on_mail_error_notice() {
		global $secas_test_mail_error;
		$class   = 'notice notice-error';
		$message = __( 'An error has occurred when sending the test email: ', 'secas' );
		if ( ! empty( $secas_test_mail_error->errors['wp_mail_failed'] ) ) {
			$message .= implode( ' &bull; ', $secas_test_mail_error->errors['wp_mail_failed'] );
		}

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	/**
	 * Output the admin success message for email test sent.
	 */
	public static function on_mail_success_notice() {
		$class   = 'notice notice-success is-dismissible';
		$message = __( 'Success! The test message has been sent.', 'secas' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	/**
	 * Output the admin success message for email test sent.
	 */
	public static function on_settings_update_notice() {
		$class   = 'notice notice-success is-dismissible';
		$message = __( 'Success! The settings have been updated.', 'secas' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	/**
	 * Output the cleanup records dropdown options.
	 */
	public static function cleanup_records_options() {
		$opt = [ 1, 7, 14, 30, 60, 90, 180, 0 ];
		foreach ( $opt as $i ) {
			if ( 0 === $i ) {
				$title = __( 'Never', 'secas' );
			} else {
				// Translators: %d - one day, %d - multiple days.
				$title = sprintf( _n( 'After one day', 'After %d days', $i, 'secas' ), $i ); // phpcs:ignore
			}
			?>
			<option value="<?php echo (int) $i; ?>" <?php selected( $i, (int) self::$settings->auto_cleanup ); ?>>
				<?php echo esc_html( $title ); ?>
			</option>
			<?php
		}
	}

	/**
	 * Return the pagination links.
	 *
	 * @param  int $total_pages   Total pages.
	 * @param  int $current_page  Current page.
	 * @param  int $total_records Total records.
	 * @return string
	 */
	public static function history_pagination( int $total_pages = 1, int $current_page = 1, int $total_records = 0 ): string {
		ob_start();
		?>

		<div class="as-row v-middle">
			<?php
			if ( $total_pages > 1 ) {
				$range = 4;
				$start = ceil( $current_page / $range ) * $range - $range + 1;
				$end   = $start + $range - 1;
				if ( $end > $total_pages ) {
					$end = $total_pages;
				}
				?>
				<div>
					<?php
					echo wp_kses_post( sprintf(
						// Translators: %1$d - current page, %2$d - total pages.
						__( 'Page %1$d of %2$d', 'secas' ),
						$current_page,
						$total_pages
					) );
					?>
				</div>

				<div>
					<?php
					if ( $start > $range ) {
						?>
						<a class="secas-page button" data-page="1" href="<?php echo esc_url( admin_url( self::get_plugin_url() . '&cp=1' ) ); ?>">&laquo;</a>
						<?php
					} else {
						?>
						<a class="button disabled">&laquo;</a>
						<?php
					}

					if ( $current_page > 1 ) {
						$val = (int) $current_page - 1;
						?>
						<a class="secas-page button" data-page="<?php echo (int) $val; ?>" href="<?php echo esc_url( admin_url( self::get_plugin_url() . '&cp=' . (int) $val ) ); ?>">&lsaquo;</a>
						<?php
					} else {
						?>
						<a class="button disabled">&lsaquo;</a>
						<?php
					}

					if ( $start > $range ) {
						echo '...';
					}

					for ( $i = $start; $i <= $end; $i++ ) {
						$class = ( (int) $i === (int) $current_page ) ? ' button-primary' : '';
						?>
						<a class="secas-page button<?php echo esc_attr( $class ); ?>"
							data-page="<?php echo (int) $i; ?>"
							href="<?php echo esc_url( admin_url( self::get_plugin_url() . '&cp=' . (int) $i ) ); ?>"><?php echo (int) $i; ?></a>
						<?php
					}
					if ( $end < $total_pages ) {
						echo '...';
					}

					if ( $current_page < $total_pages ) {
						$val = (int) $current_page + 1;
						?>
						<a class="secas-page button" data-page="<?php echo (int) $val; ?>" href="<?php echo esc_url( admin_url( self::get_plugin_url() . '&cp=' . (int) $val ) ); ?>">&rsaquo;</a>
						<?php
					} else {
						?>
						<a class="button disabled">&rsaquo;</a>
						<?php
					}

					if ( $current_page < $total_pages ) {
						?>
						<a class="secas-page button" data-page="<?php echo (int) $total_pages; ?>" href="<?php echo esc_url( admin_url( self::get_plugin_url() . '&cp=' . (int) $total_pages ) ); ?>">&raquo;</a>
						<?php
					} else {
						?>
						<a class="button disabled">&raquo;</a>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>


			<div>
				<a href="<?php echo esc_url( admin_url( self::get_plugin_url() ) ); ?>" id="secas_refresh" class="button as-icon refresh" title="<?php esc_html_e( 'Refresh the listing', 'secas' ); ?>"><span class="dashicons dashicons-update-alt"></span></a>
			</div>

			<div>
				<?php echo (int) $total_records; ?> <?php esc_html_e( 'records', 'secas' ); ?>
			</div>

			<div style="margin-left: auto;">
				<?php
				if ( ! empty( $total_records ) ) {
					$search = filter_input( INPUT_POST, 'search', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
					$blog   = ( ! empty( $search['blog'] ) ) ? (int) $search['blog'] : '';
					?>
					<a class="button button-item secas-cleanup bg-blog<?php echo (int) $blog; ?>"
						data-cleanid="all"
						data-cleanpag="<?php echo (int) $current_page; ?>" href="javascript:void(0);">
						<?php if ( ! empty( $blog ) ) : ?>
							<?php esc_html_e( 'Reset the site emails history', 'secas' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Reset the emails history', 'secas' ); ?>
						<?php endif; ?>
					</a>
					<?php
				}
				?>
			</div>
		</div>
		<?php
		$result = ob_get_clean();

		return $result;
	}

	/**
	 * Use network settings.
	 */
	public static function use_network(): bool {
		return ( ECA_NETWORK_SCREEN || substr_count( $_SERVER['REQUEST_URI'], '?network=on' ) ) ? true : false; // phpcs:ignore
	}

	/**
	 * Load a page of records.
	 */
	public static function load_emails_page() {
		global $wpdb;

		include_once __DIR__ . '/inc/page.php';
	}

	/**
	 * Cleanup styles, links, and scripts from a given content.
	 *
	 * @param  string $content Initial content.
	 * @return string
	 */
	public static function cleaned_message( string $content = '' ): string {
		if ( empty( $content ) ) {
			// Fail-fast, no content.
			return '';
		}

		// Create a new DOMDocument.
		$dom = new \DOMDocument();

		// Load the HTML content into the DOMDocument.
		\libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		\libxml_use_internal_errors( false );

		// Get all <meta>, <script>, and <style> elements.
		$meta_tags   = $dom->getElementsByTagName( 'meta' );
		$script_tags = $dom->getElementsByTagName( 'script' );
		$style_tags  = $dom->getElementsByTagName( 'style' );

		// Remove all <meta> tags.
		while ( $meta_tags->length > 0 ) {
			$tag = $meta_tags->item( 0 );
			$tag->parentNode->removeChild( $tag ); // phpcs:ignore
		}

		// Remove all <script> tags.
		while ( $script_tags->length > 0 ) {
			$tag = $script_tags->item( 0 );
			$tag->parentNode->removeChild( $tag ); // phpcs:ignore
		}

		// Remove all <style> tags.
		while ( $style_tags->length > 0 ) {
			$tag = $style_tags->item( 0 );
			$tag->parentNode->removeChild( $tag ); // phpcs:ignore
		}

		// Return the modified HTML content.
		return $dom->saveHTML();
	}

	/**
	 * Execute the full cleanup of the plugin history.
	 */
	public static function history_cleanup() {
		global $wpdb;
		$use_network = self::use_network();
		if ( $use_network ) {
			$search = filter_input( INPUT_POST, 'search', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			if ( ! empty( $search['blog'] ) ) {
				$wpdb->query( $wpdb->prepare( ' DELETE FROM `' . self::$table . '` where blog_id=%d ', $search['blog'] ) ); // phpcs:ignore
			} else {
				$wpdb->query( ' TRUNCATE table `' . self::$table . '` ' ); // phpcs:ignore
			}
		} else {
			$wpdb->query( $wpdb->prepare( ' DELETE FROM `' . self::$table . '` where blog_id=%d or blog_id IS NULL ', get_current_blog_id() ) ); // phpcs:ignore
		}
	}

	/**
	 * Execute the cleanup.
	 *
	 * @param string $val The value for the cleanup.
	 */
	public static function emails_catch_all_cleanup_records( $val ) { // phpcs:ignore
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Action not allowed.', 'secas' ) );
		} elseif ( ! empty( $val ) ) {
			if ( 'all' === $val ) {
				self::history_cleanup();
			} elseif ( is_numeric( $val ) ) {
				global $wpdb;
				$wpdb->query( $wpdb->prepare( ' DELETE from ' . self::$table . ' WHERE id = %d ', (int) $val ) ); // phpcs:ignore
			}
		}
	}

	/**
	 * Get plugin settings page URL.
	 */
	public static function get_plugin_url(): string {
		$use_network = self::use_network();
		if ( $use_network ) {
			return self::$network_plugin_url;
		}

		return self::$plugin_url;
	}

	/**
	 * Update the plugin option.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Option value.
	 */
	public static function update_the_option( $name, $value ) { // phpcs:ignore
		if ( ECA_NETWORK_SCREEN ) {
			update_network_option( get_current_network_id(), $name, $value );
		} else {
			update_option( $name, $value );
		}
	}

	/**
	 * The plugin settings and history page.
	 */
	public static function emails_catch_all_settings() {
		// Verify user capabilities in order to deny the access if the user does not have the capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Action not allowed.', 'secas' ) );
		}

		$tab = filter_input( INPUT_GET, 'tab', FILTER_DEFAULT );
		$tab = empty( $tab ) ? '' : $tab;
		$tab = ! in_array( $tab, [ 'settings', 'smtp', 'network' ], true ) ? '' : $tab;

		self::get_options();

		if ( ECA_NETWORK_SCREEN ) {
			echo '<style>';
			foreach ( self::$settings->sites_options as $id => $site ) {
				$info  = get_blog_details( [ 'blog_id' => $id ] );
				$color = self::string2color( $id . ( (int) $id * 5 + 1 ) . ( (int) $id * 3 + 2 ) . $info->path . ( (int) $id * 5 + 2 ) . ( (int) $id * 3 + 1 ) . $id );
				?>
				.eca-wrap .bg-blog { display: inline-block; border-radius: 3px; box-sizing: border-box; line-height: 1.65rem; height: 1.65rem; padding: 0} .eca-wrap .rows.items-list:not(.sticky).bg-blog<?php echo (int) $id; ?>, .eca-wrap .bg-blog<?php echo (int) $id; ?> {background-color: <?php echo esc_attr( $color ); ?> !important; min-width: 1.65rem; text-align: center;} .eca-wrap .bg-blog<?php echo (int) $id; ?>.button {border: 1px dotted #bbb !important}
				<?php
			}
			echo '</style>';
		}

		$url = admin_url( self::get_plugin_url() );
		?>
		<div class="wrap eca-wrap">
			<h1 class="plugin-title">
				<span class="dashicons dashicons-email"></span>
				<?php esc_html_e( 'Emails Catch All', 'secas' ); ?>
			</h1>

			<?php if ( empty( self::$settings->just_record ) && empty( self::$settings->email ) ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'The plugin settings will not be applied. You must set up the recipient email address in the general settings tab.', 'secas' ); ?></p>
				</div>
			<?php endif; ?>

			<?php include_once __DIR__ . '/inc/menu.php'; ?>

			<form method="post" autocomplete="new-password">
				<?php wp_nonce_field( '_secas_settings_save', '_secas_settings_nonce' ); ?>
				<?php
				switch ( $tab ) {
					case 'settings':
						self::tab_content_1();
						break;

					case 'smtp':
						self::tab_content_2();
						break;

					case 'network':
						if ( ECA_NETWORK_SCREEN ) {
							self::tab_content_3();
						}
						break;

					default:
						if ( ! empty( self::$settings->history ) || ! empty( self::$settings->just_record ) ) :
							self::tab_content_0();
							?>
							<div class="secas-list">
								<?php self::load_emails_page(); ?>
							</div>
							<?php
						else :
							?>
							<div class="as-row flat full border">
								<div>
									<h3><?php esc_html_e( 'The emails log is not available', 'secas' ); ?></h3>
									<p>
										<?php esc_html_e( 'Use the general settings to enable the option for recording the emails.', 'secas' ); ?>
									</p>
								</div>
							</div>
							<?php
						endif;
						break;
				}
				?>
			</form>

			<?php self::show_donate_text(); ?>
		</div>
		<?php
	}

	/**
	 * Tab content.
	 */
	public static function tab_content_0() {
		include_once __DIR__ . '/inc/tab-0.php';
	}

	/**
	 * Tab content.
	 */
	public static function tab_content_1() {
		if ( ! empty( self::$settings->just_record ) ) {
			// Fail-fast.
			return;
		}

		include_once __DIR__ . '/inc/tab-1.php';
	}

	/**
	 * Tab content.
	 */
	public static function tab_content_2() {
		if ( empty( self::$settings->email ) ) {
			// Fail-fast, settings won't apply if the email is not set.
			return;
		}

		include_once __DIR__ . '/inc/tab-2.php';
	}

	/**
	 * Tab content.
	 */
	public static function tab_content_3() {
		$network_settings = (array) self::$settings;
		unset( $network_settings['sites_options'] );

		if ( empty( self::$settings->sites_options ) ) {
			esc_html_e( 'No sites', 'secas' );

			// Fail-fast.
			return;
		}

		include_once __DIR__ . '/inc/tab-3.php';
	}

	/**
	 * Fix the links like <http://....> that are generated in the recent WP core.
	 *
	 * @param  string $content The email message.
	 * @return string
	 */
	public static function fix_new_links_format( string $content = '' ): string {
		if ( empty( $content ) || ! is_scalar( $content ) ) {
			return $content;
		}

		preg_match_all( '/<http(.*)>/', $content, $matches );
		if ( ! empty( $matches[0] ) && ! empty( $matches[1] ) ) {
			foreach ( $matches[0] as $key => $value ) {
				if ( ! empty( self::$settings->content_type ) && 'plain' === self::$settings->content_type ) {
					$content = str_replace( $value, 'http' . $matches[1][ $key ], $content );
				} else {
					$content = str_replace( $value, '<a href="http' . $matches[1][ $key ] . '">http' . $matches[1][ $key ] . '</a>', $content );
					$content = nl2br( $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Normalize the headers
	 *
	 * @param  string|array $headers Email headers.
	 * @return array
	 */
	public static function prepare_headers( $headers ) { // phpcs:ignore
		// Fix the headers, to convert all to array.
		if ( ! empty( $headers ) ) {
			if ( ! is_array( $headers ) ) {
				// Explode the headers out, so this function can take both string headers and an array of headers.
				$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
			} else {
				$headers = $headers;
			}
		} else {
			$headers = [];
		}
		return $headers;
	}

	/**
	 * Normalize the headers
	 *
	 * @param  string|array $headers Email headers.
	 * @return array
	 */
	public static function analyze_headers( $headers ) { // phpcs:ignore
		// Fix the headers, to convert all to array.
		$all_headers = [
			'from' => [],
			'to'   => [],
			'cc'   => [],
			'bcc'  => [],
			'any'  => [],
		];
		if ( ! empty( $headers ) ) {
			foreach ( $headers as $header ) {
				$lower = strtolower( $header );
				if ( 'bcc: ' === substr( $lower, 0, 5 ) ) {
					$all_headers['bcc'][] = $header;
				} elseif ( 'cc: ' === substr( $lower, 0, 4 ) ) {
					$all_headers['cc'][] = $header;
				} elseif ( 'from: ' === substr( $lower, 0, 6 ) ) {
					$all_headers['from'][] = $header;
				} elseif ( 'to: ' === substr( $lower, 0, 4 ) ) {
					$all_headers['to'][] = $header;
				} else {
					$all_headers['any'][] = $header;
				}
			}
		}
		return $all_headers;
	}

	/**
	 * Pack all headers into one array.
	 *
	 * @param  array $headers Email headers.
	 * @return array
	 */
	public static function pack_headers( $headers ) { // phpcs:ignore
		// Pack all headers into one array.
		$all_headers = [];
		if ( ! empty( $headers ) ) {
			foreach ( $headers as $header ) {
				if ( ! empty( $header ) ) {
					if ( is_array( $header ) ) {
						$all_headers = array_merge( $all_headers, $header );
					} else {
						$all_headers[] = $header;
					}
				}
			}
			// Make sure we remove empty value.
			$all_headers = array_filter( $all_headers );
		}
		return $all_headers;
	}

	/**
	 * Update the native mail headers and record history if the case.
	 *
	 * @param  array $args Email arguments.
	 * @return array
	 */
	public static function wp_mail_catch_all( $args = [] ) { // phpcs:ignore
		$new_wp_mail = $args;

		if ( empty( self::$settings->just_record ) ) {
			$new_wp_mail['headers'] = self::prepare_headers( $new_wp_mail['headers'] );

			$computed         = self::analyze_headers( $new_wp_mail['headers'] );
			$initial_computed = $computed;
			$bcc              = '';
			if ( ! empty( self::$settings->email ) ) {
				if ( 'receive' === self::$settings->recipient ) {
					array_push( $computed['bcc'], 'Bcc: ' . self::$settings->email );
					$bcc = self::$settings->email;
				} elseif ( 'replace' === self::$settings->recipient ) {
					$new_wp_mail['to'] = self::$settings->email;
					unset( $computed['to'] );
					unset( $computed['cc'] );
					unset( $computed['bcc'] );
				}
			}
			$new_wp_mail['headers'] = self::pack_headers( $computed );
			$new_wp_mail['message'] = self::fix_new_links_format( $new_wp_mail['message'] );
		}

		if ( ! empty( self::$settings->history ) || ! empty( self::$settings->just_record ) ) {
			$date  = current_time( 'timestamp' ); // phpcs:ignore
			$final = $new_wp_mail;
			if ( ! empty( self::$settings->content_type ) && 'plain' === self::$settings->content_type ) {
				$final['message'] = wp_strip_all_tags( $final['message'] );
			}

			$type = '';
			if ( empty( self::$settings->just_record ) ) {
				$final['bcc'] = '';
				if ( ! empty( $bcc ) ) {
					$final['bcc'] = $bcc;
				}

				$args['to']  = ( is_array( $args['to'] ) ) ? implode( ',', $args['to'] ) : $args['to'];
				$args['cc']  = ( ! empty( $initial_computed['cc'] ) ) ? implode( ',', $initial_computed['cc'] ) : '';
				$args['bcc'] = ( ! empty( $initial_computed['bcc'] ) ) ? implode( ',', $initial_computed['bcc'] ) : '';
				$final['to'] = ( is_array( $final['to'] ) ) ? implode( ',', $final['to'] ) : $final['to'];

				if ( 'disable' === self::$settings->recipient ) {
					$final['to'] = [];
				}

				$type = self::$settings->recipient;
			}

			$opt = [
				'date'    => $date,
				'initial' => $args,
				'final'   => $final,
				'type'    => $type,
			];
			if ( ! empty( $final['message'] ) ) {
				global $wpdb;
				$arr  = [
					'date'    => $opt['date'],
					'type'    => $opt['type'],
					'from'    => '',
					'initial' => maybe_serialize( $args['to'] ),
					'final'   => maybe_serialize( $final['to'] ),
					'subject' => $final['subject'],
					'content' => ( ! empty( $final['message'] ) ) ? self::fix_new_links_format( $final['message'] ) : '',
					'all'     => maybe_serialize( $opt ),
					'blog_id' => get_current_blog_id(),
				];
				$type = [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ];
				$wpdb->insert( self::$table, $arr, $type ); // phpcs:ignore
				self::$last_insert_id = $wpdb->insert_id;
			}
		}

		return $new_wp_mail;
	}

	/**
	 * Update the native mail headers and record history if the case.
	 *
	 * @param  array $args Email arguments.
	 * @return array
	 */
	public static function wp_mail_disable_all( $args = [] ) { // phpcs:ignore
		self::wp_mail_catch_all( $args );
		unset( $args['to'] );
		unset( $args['cc'] );
		unset( $args['bcc'] );
		unset( $args['headers'] );
		return $args;
	}

	/**
	 * Maybe update the phpmailer settings.
	 *
	 * @param object $phpmailer PHP Mailer instance.
	 */
	public static function maybe_phpmailer_settings( $phpmailer ) { // phpcs:ignore
		if ( empty( self::$settings->just_record ) ) {
			// phpcs:disable
			if ( ! empty( self::$settings->smtp_auth ) ) {
				$phpmailer->isSMTP();
				$phpmailer->SMTPAuth = self::$settings->smtp_auth;
			}
			if ( ! empty( self::$settings->smtp_host ) ) {
				$phpmailer->Host = self::$settings->smtp_host;
			}
			if ( ! empty( self::$settings->smtp_port ) ) {
				$phpmailer->Port = self::$settings->smtp_port;
			}
			if ( ! empty( self::$settings->smtp_uname ) ) {
				$phpmailer->Username = self::$settings->smtp_uname;
			}
			if ( ! empty( self::$settings->smtp_upass ) ) {
				$phpmailer->Password = self::$settings->smtp_upass;
			}
			if ( ! empty( self::$settings->smtp_secure ) ) {
				$phpmailer->SMTPSecure = self::$settings->smtp_secure;
			}
			if ( ! empty( self::$settings->smtp_from ) ) {
				$phpmailer->From = self::$settings->smtp_from;
			}
			if ( ! empty( self::$settings->smtp_from_name ) ) {
				$phpmailer->FromName = self::$settings->smtp_from_name;
			}
			// phpcs:enable
		}

		if ( ! empty( self::$last_insert_id ) ) {
			global $wpdb;
			$wpdb->update( // phpcs:ignore
				self::$table,
				[
					'from' => maybe_serialize( [
						'email' => $phpmailer->From, // phpcs:ignore
						'name'  => $phpmailer->FromName, // phpcs:ignore
					] ),
				],
				[ 'id' => self::$last_insert_id ],
				[ '%s' ],
				[ '%d' ]
			);
		}
	}

	/**
	 * Maybe update the phpmailer settings.
	 *
	 * @param object $phpmailer PHP Mailer instance.
	 */
	public static function maybe_phpmailer_disable( $phpmailer ) { // phpcs:ignore
		if ( ! empty( self::$last_insert_id ) ) {
			global $wpdb;
			$wpdb->update( // phpcs:ignore
				self::$table,
				[
					'from' => maybe_serialize( [
						'email' => $phpmailer->From, // phpcs:ignore
						'name'  => $phpmailer->FromName, // phpcs:ignore
					] ),
				],
				[ 'id' => self::$last_insert_id ],
				[ '%s' ],
				[ '%d' ]
			);
		}

		// Empty out the values that may be set.
		$phpmailer->clearAllRecipients();
		$phpmailer->clearAttachments();
		$phpmailer->clearCustomHeaders();
		$phpmailer->clearReplyTos();
	}

	/**
	 * Generate a color from a string.
	 *
	 * @param  string $string Initial string.
	 * @return string
	 */
	public static function string2color( $string ) { // phpcs:ignore
		$hash  = md5( $string );
		$base  = substr( $hash, 0, 6 );
		$color = '';
		for ( $i = 0; $i < 3; $i++ ) {
			$value  = max( hexdec( $base[ $i * 2 ] ), 9 ) * 16 + max( hexdec( $base[ $i * 2 + 1 ] ), 9 );
			$color .= str_pad( dechex( $value ), 2, '0', STR_PAD_LEFT );
		}

		return '#' . $color . '60';
	}

	/**
	 * The actions to be executed when the plugin is deactivated.
	 */
	public static function deactivate_plugin() {
		self::plugin_admin_notices_cleanup( false );
		self::maybe_unschedule_tasks();
		if ( ! empty( self::$settings->deactivate_cleanup ) ) {
			self::history_cleanup();
			delete_option( self::$option_name );
			delete_option( self::$option_name . '_db_ver' );
			global $wpdb;
			$wpdb->query( 'DROP TABLE IF EXISTS ' . self::$table ); // phpcs:ignore
		}
	}

	/**
	 * The actions to be executed when the plugin is activated.
	 */
	public static function activate_plugin() {
		set_transient( self::PLUGIN_TRANSIENT, true );
		self::update_the_option( self::$option_name . '_db_ver', 0 );
		self::maybe_upgrade_db();
		self::maybe_schedule_tasks();
	}

	/**
	 * Setup the table name.
	 */
	public static function table_name() {
		global $wpdb;
		self::$table = SISANU_EMAILS_CATCH_ALL_DB_VERSION < 3.5
			? SISANU_EMAILS_CATCH_ALL_TABLE :
			$wpdb->base_prefix . SISANU_EMAILS_CATCH_ALL_TABLE;
	}

	/**
	 * Maybe upgrade the table structure.
	 */
	public static function maybe_upgrade_db() {
		global $wpdb;

		if ( SISANU_EMAILS_CATCH_ALL_TABLE !== self::$table ) {
			$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', SISANU_EMAILS_CATCH_ALL_TABLE );
			if ( $wpdb->get_var( $query ) === SISANU_EMAILS_CATCH_ALL_TABLE ) { // phpcs:ignore
				// The old table exists, lets rename it.
				$wpdb->query( 'RENAME TABLE `' . SISANU_EMAILS_CATCH_ALL_TABLE . '` TO `' . self::$table . '`' ); // phpcs:ignore
			}
		}

		$db_version = get_option( self::$option_name . '_db_ver', 0 );
		if ( SISANU_EMAILS_CATCH_ALL_DB_VERSION !== (float) $db_version ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$sql = ' CREATE TABLE ' . self::$table . ' (
				`id` bigint(20) AUTO_INCREMENT,
				`blog_id` int(4) DEFAULT \'0\',
				`date` bigint(20),
				`type` varchar(15),
				`from` text,
				`initial` varchar(255),
				`final` varchar(255),
				`subject` varchar(255),
				`content` text,
				`all` text,
				UNIQUE KEY `id` (id)
			) CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = \'Table created by the Emails Catch All plugin\'';
			dbDelta( $sql );
			update_option( self::$option_name . '_db_ver', SISANU_EMAILS_CATCH_ALL_DB_VERSION );

			if ( ! is_multisite() ) {
				global $wpdb;
				$wpdb->query( $wpdb->prepare( 'update ' . self::$table . ' set blog_id = %d where blog_id IS NULL or blog_id = 0', get_current_blog_id() ) ); // phpcs:ignore
			}
		}
	}

	/**
	 * Add the settings and the plugin link.
	 *
	 * @param  array $links Plugin links array.
	 * @return array
	 */
	public static function plugin_action_links( $links ) { // phpcs:ignore
		$all       = [];
		$use_local = true;
		if ( ECA_NETWORK_SCREEN ) {
			$use_local = false;
		} elseif ( is_multisite() ) {
			if ( ! self::use_email_settings() ) {
				$use_local = false;
			}

			if ( self::inherit_network_settings() ) {
				$use_local = false;
			}
		}

		if ( $use_local ) {
			$all[] = '<a href="' . esc_url( admin_url( self::$plugin_url ) ) . '">' . __( 'Settings', 'secas' ) . '</a>';
		} elseif ( is_super_admin() ) {
			$all[] = '<a href="' . esc_url( admin_url( self::$network_plugin_url ) ) . '">' . __( 'Network Settings', 'secas' ) . '</a>';
		}

		$all[] = '<a href="https://iuliacazan.ro/emails-catch-all">' . __( 'Plugin URL', 'secas' ) . '</a>';
		$all   = array_merge( $all, $links );
		return $all;
	}

	/**
	 * The actions to be executed when the plugin is updated.
	 */
	public static function plugin_ver_check() {
		$opt = str_replace( '-', '_', self::PLUGIN_TRANSIENT ) . '_db_ver';
		$dbv = get_option( $opt, 0 );
		if ( SISANU_EMAILS_CATCH_ALL_DB_VERSION !== (float) $dbv ) {
			update_option( $opt, SISANU_EMAILS_CATCH_ALL_DB_VERSION );
			self::maybe_upgrade_db();
			set_transient( self::PLUGIN_TRANSIENT, true );
		}
	}

	/**
	 * Execute notices cleanup.
	 *
	 * @param bool $ajax Is AJAX call.
	 */
	public static function plugin_admin_notices_cleanup( $ajax = true ) { // phpcs:ignore
		// Delete transient, only display this notice once.
		delete_transient( self::PLUGIN_TRANSIENT );

		if ( true === $ajax ) {
			// No need to continue.
			wp_die();
		}
	}

	/**
	 * Cron sanity check.
	 */
	public static function cron_sanity_check() {
		$checked = get_transient( self::$cron_hook . '_check' );
		if ( false === $checked ) {
			self::maybe_unschedule_tasks();
			self::maybe_schedule_tasks();
			set_transient( self::$cron_hook . '_check', time(), 1 * HOUR_IN_SECONDS );
		}
	}

	/**
	 * Maybe unschedule tasks.
	 */
	public static function maybe_unschedule_tasks() {
		wp_unschedule_hook( self::$cron_hook );
		wp_clear_scheduled_hook( self::$cron_hook );
	}

	/**
	 * Maybe schedule tasks, at 2 minutes apart.
	 */
	public static function maybe_schedule_tasks() {
		$args = [ get_current_blog_id(), self::$settings->auto_cleanup ];
		if ( ! wp_next_scheduled( self::$cron_hook, $args ) ) {
			wp_schedule_event(
				strtotime( gmdate( 'Y-m-d' ) . ' 00:05 +1 day' ),
				'daily',
				self::$cron_hook,
				$args
			);
		}
	}

	/**
	 * Hookup custom execution based on which sync task is called.
	 *
	 * @param int $site_id Site ID.
	 * @param int $days    Number of days.
	 */
	public static function secas_cleanup_hook( int $site_id = 0, int $days = 0 ) {
		if ( ! empty( $site_id ) && ! empty( $days ) ) {
			global $wpdb;
			$limit = strtotime( gmdate( 'Y-m-d' ) . ' -' . (int) $days . ' day' );
			$wpdb->query( $wpdb->prepare( ' DELETE FROM `' . self::$table . '` where ( blog_id=%d OR blog_id IS NULL ) and date <= %d', $site_id, $limit ) ); // phpcs:ignore
		}
	}

	/**
	 * Admin notices.
	 */
	public static function plugin_admin_notices() {
		if ( apply_filters( 'secas_filter_remove_update_info', false ) ) {
			return;
		}

		$maybe_trans = get_transient( self::PLUGIN_TRANSIENT );
		if ( ! empty( $maybe_trans ) ) {

			$slug   = md5( ECA_PLUGIN_SLUG );
			$ptitle = __( 'Emails Catch All', 'secas' );
			$donate = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( $ptitle ) . ')';

			// Translators: %1$s - plugin name.
			$activated = sprintf( __( '%1$s plugin was activated!', 'secas' ), '<b>' . $ptitle . '</b>' );

			$other_notice = sprintf(
				// Translators: %1$s - plugins URL, %2$s - heart, %3$s - extensions URL, %4$s - star, %5$s - pro.
				__( '%5$sCheck out my other <a href="%1$s" target="_blank" rel="noreferrer">%2$s free plugins</a> on WordPress.org and the <a href="%3$s" target="_blank" rel="noreferrer">%4$s other extensions</a> available!', 'secas' ),
				'https://profiles.wordpress.org/iulia-cazan/#content-plugins',
				'<span class="dashicons dashicons-heart"></span>',
				'https://iuliacazan.ro/shop/',
				'<span class="dashicons dashicons-star-filled"></span>',
				''
			);
			?>
			<div id="item-<?php echo esc_attr( $slug ); ?>" class="notice is-dismissible">
				<div class="content">
					<a class="icon" href="<?php echo esc_url( admin_url( self::get_plugin_url() ) ); ?>"><img src="<?php echo esc_url( ECA_PLUGIN_URL . 'assets/images/icon-128x128.gif' ); ?>"></a>
					<div class="details">
						<div>
							<h3><?php echo wp_kses_post( $activated ); ?></h3>
							<div class="notice-other-items"><?php echo wp_kses_post( $other_notice ); ?></div>
						</div>
						<div><?php echo wp_kses_post( self::donate_text() ); ?></div>
						<a class="notice-plugin-donate" href="<?php echo esc_url( $donate ); ?>" target="_blank"><img src="<?php echo esc_url( ECA_PLUGIN_URL . 'assets/images/buy-me-a-coffee.png?v=' . ECA_PLUGIN_VERSION ); ?>" width="200"></a>
					</div>
				</div>
				<button type="button" class="notice-dismiss" onclick="dismiss_notice_for_<?php echo esc_attr( $slug ); ?>()"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'secas' ); ?></span></button>
			</div>
			<?php
			$style = '#trans123super{--color-bg:rgba(176,3,4,0.1); --color-border:rgb(176,3,4); border-left-color:var(--color-border);padding:0 38px 0 0!important}#trans123super *{margin:0}#trans123super .dashicons{color:var(--color-border)}#trans123super a{text-decoration:none}#trans123super img{display:flex;}#trans123super .content,#trans123super .details{display:flex;gap:1rem;padding-block:.5em}#trans123super .details{align-items:center;flex-wrap:wrap;padding-block:0}#trans123super .details>*{flex:1 1 35rem}#trans123super .details .notice-plugin-donate{flex:1 1 auto}#trans123super .details .notice-plugin-donate img{max-width:100%}#trans123super .icon{background:var(--color-bg);flex:0 0 4rem;margin:-.5em 0;padding:1rem}#trans123super .icon img{display:flex;height:auto;width:4rem} #trans123super h3{margin-bottom:0.5rem;text-transform:none}';
			$style = str_replace( '#trans123super', '#item-' . esc_attr( $slug ), $style );
			echo '<style>' . $style . '</style>'; // phpcs:ignore
			?>
			<script>function dismiss_notice_for_<?php echo esc_attr( $slug ); ?>() { document.getElementById( 'item-<?php echo esc_attr( $slug ); ?>' ).style='display:none'; fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=plugin-deactivate-notice-<?php echo esc_attr( ECA_PLUGIN_SLUG ); ?>' ); }</script>
			<?php
		}
	}

	/**
	 * Donate or rate text.
	 */
	public static function donate_text(): string {
		$donate = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( self::PLUGIN_NAME ) . ')';
		$thanks = __( 'A huge thanks in advance!', 'secas' );

		return sprintf(
				// Translators: %1$s - donate URL, %2$s - rating, %3$s - thanks.
			__( 'If you find the plugin useful and would like to support my work, please consider making a <a href="%1$s" target="_blank">donation</a>. It would make me very happy if you would leave a %2$s rating. %3$s', 'secas' ),
			esc_url( $donate ),
			'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" rel="noreferrer" title="' . esc_attr( $thanks ) . '">★★★★★</a>',
			$thanks
		);
	}

	/**
	 * Maybe donate or rate.
	 */
	public static function show_donate_text() {
		if ( apply_filters( 'secas_filter_remove_donate_info', false ) ) {
			// Fail-fast.
			return;
		}
		?>
		<div class="donate">
			<img src="<?php echo esc_url( ECA_PLUGIN_URL . 'assets/images/icon-128x128.gif' ); ?>" width="32" height="32" alt="<?php echo esc_html( self::PLUGIN_NAME ); ?>">
			<div>
				<?php echo wp_kses_post( self::donate_text() ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Custom menu style.
	 */
	public static function menu_style() {
		echo '<style>#adminmenu div.wp-menu-image.svg.secas {height: 1em; margin-left: -10px}</style>';
	}
}

$sisanu_emails_catch_all = SISANU_Emails_Catch_All::get_instance();
register_activation_hook( __FILE__, [ $sisanu_emails_catch_all, 'activate_plugin' ] );
register_deactivation_hook( __FILE__, [ $sisanu_emails_catch_all, 'deactivate_plugin' ] );

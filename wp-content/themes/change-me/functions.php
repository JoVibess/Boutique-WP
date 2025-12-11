<?php
/**
 * Created by A. MACHEDA
 * @author Genesii SAS
 * @version 1.0
 */

if(!function_exists('dd')):
	function dd($data) {
		echo('<pre>');
		var_dump($data);
		echo('</pre>');

		exit();
	}
endif;

if(!function_exists('dump')):
	function dump($data) {
		echo('<pre>');
		var_dump($data);
		echo('</pre>');
	}
endif;

function genesii_theme_setup() {
	load_theme_textdomain('genesii_theme', get_template_directory() . '/translations');

	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	));

	add_theme_support('custom-logo', array(
		'height'      => 250,
		'width'       => 250,
		'flex-width'  => true,
		'flex-height' => true,
	));
}
add_action('after_setup_theme', 'genesii_theme_setup');

function genesii_theme_scripts() {
    global $wp_query;

    wp_enqueue_style('genesii-theme-styles', get_template_directory_uri() . '/build/css/index.css');
	wp_enqueue_script('genesii-theme-scripts', get_template_directory_uri() . '/build/js/index.js');

    wp_localize_script( 'genesii-theme-scripts', 'genesii_loadmore_params', array(
        'ajaxurl' => site_url() . '/wp-admin/admin-ajax.php', // WordPress AJAX
        'posts' => json_encode( $wp_query->query_vars ), // everything about your loop is here
        'current_page' => get_query_var( 'paged' ) ? get_query_var('paged') : 1,
        'max_page' => $wp_query->max_num_pages
    ));
}
add_action('wp_enqueue_scripts', 'genesii_theme_scripts');

function genesii_loadmore_ajax_handler(){

    // prepare our arguments for the query
    $args = json_decode( stripslashes( $_POST['query'] ), true );
    $args['paged'] = $_POST['page'] + 1; // we need next page to be loaded
    $args['post_status'] = 'publish';

    // it is always better to use WP_Query but not here
    query_posts( $args );

    if( have_posts() ) :

        // run the loop
        while( have_posts() ): the_post();
            get_template_part( 'template-parts/post/content-archive' );
        endwhile;

    endif;
    die; // here we exit the script and even no wp_reset_query() required!
}

add_action('wp_ajax_loadmore', 'genesii_loadmore_ajax_handler'); // wp_ajax_{action}
add_action('wp_ajax_nopriv_loadmore', 'genesii_loadmore_ajax_handler'); // wp_ajax_nopriv_{action}


/* Autoriser les fichiers SVG */
function wpc_mime_types($mimes) {
    $mimes['svg'] = 'image/svg xml';
    return $mimes;
}
add_filter('upload_mimes', 'wpc_mime_types');

//Pour ordonner les pages enfant selon leur ordre d'apparition dans le BO
add_action( 'admin_init', 'genesii_order_by_menu_order' );
function genesii_order_by_menu_order()
{
    add_post_type_support( 'post', 'page-attributes' );
}

function genesii_slugify($str)
{
    $search  = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', "'", '"', '«', '»');
    $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', '', '', '', '');


    $str = str_replace($search, $replace, $str);
    $str = preg_replace('/\s+/', '_', $str);

    return strtolower($str);
}

//Ajouter le menu "menu" dans apparence
add_theme_support( 'menus' );
function register_menus() {
    register_nav_menus(array(
            'header_menu' => 'Menu Principal (Header)',
            'primary' => 'Primary',
        )
    );
}
add_action( 'init', 'register_menus' );


/* ---- OPTIM SEO ---- */

//Redirect taxo page to archive page is no active
//add_action( 'wp', 'redirect_taxonomy' );  //hook into wp's init action hook
if ( ! function_exists( 'redirect_taxonomy' ) ) {

    function redirect_taxonomy() {

        if ( ! is_admin() && ! is_search()) {


            if ( is_archive() && (is_tax( 'statut' ) || is_tax( 'metier' ) || is_tax( 'typologie' ) || is_tax( 'annee' )
                    || is_tax( 'plage_date' ) || is_tax( 'lieu' ) || is_tax( 'region' )) ) {

                $redirect = get_post_type_archive_link('projet');
                if($redirect) {
                    wp_safe_redirect( $redirect );
                } else {
                    wp_safe_redirect( home_url() );
                }
                die();
            };


            if ( is_archive() && (is_tax( 'category' ) || is_tax( 'post_tag' ) || is_tax( 'date' )) ) {

                $redirect = get_post_type_archive_link('post');
                if($redirect) {
                    wp_safe_redirect( $redirect );
                } else {
                    wp_safe_redirect( home_url() );
                }
                die();
            };

            if ( is_archive() && (is_tag()) ) {

                $redirect = get_post_type_archive_link(get_post_type());
                if($redirect) {
                    wp_safe_redirect( $redirect );
                } else {
                    wp_safe_redirect( home_url() );
                }
                die();
            };

        }

    };

};

//No index pagination page
//add_filter( 'wpseo_robots', 'my_robots_func' );
function my_robots_func( $robotsstr ) {
    if ( is_paged() ) {
        return 'noindex,follow';
    }
    return $robotsstr;
}

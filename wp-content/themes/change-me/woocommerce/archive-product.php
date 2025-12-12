<?php
/**
 * Archive produits WooCommerce
 * Utilisé pour : Boutique, Catégories, Recherche produits
 *
 * @author Genesii SAS
 * @version 1.0
 */

defined('ABSPATH') || exit;

get_header('shop');
?>

<div class="container my-5">

	<!-- FIL D'ARIANE -->
	<div class="breadcrumb-wrapper mb-4">
		<?php
		if (function_exists('woocommerce_breadcrumb')) {
			woocommerce_breadcrumb([
				'delimiter' => ' <span class="breadcrumb-sep" aria-hidden="true">›</span> ',
			]);
		}
		?>
	</div>

	<?php if (is_search()) : ?>
		<!-- HEADER RECHERCHE -->
		<div class="search-results-page">
			<div class="search-header">
				<h1 class="search-title">
					Résultats de recherche pour : <span>"<?php echo get_search_query(); ?>"</span>
				</h1>
				<p class="search-count">
					<?php
					global $wp_query;
					$result_count = $wp_query->found_posts;
					echo $result_count . ' ' . ($result_count > 1 ? 'produits trouvés' : 'produit trouvé');
					?>
				</p>
			</div>
		</div>
	<?php else : ?>
		<!-- HEADER BOUTIQUE / CATÉGORIE -->
		<header class="woocommerce-products-header mb-4">
			<?php if (apply_filters('woocommerce_show_page_title', true)) : ?>
				<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
			<?php endif; ?>

			<?php
			/**
			 * Description de la catégorie
			 */
			do_action('woocommerce_archive_description');
			?>
		</header>
	<?php endif; ?>

	<?php if (woocommerce_product_loop()) : ?>

		<?php
		/**
		 * Hook: woocommerce_before_shop_loop.
		 *
		 * @hooked woocommerce_output_all_notices - 10
		 * @hooked woocommerce_result_count - 20
		 * @hooked woocommerce_catalog_ordering - 30
		 */
		do_action('woocommerce_before_shop_loop');
		?>

		<?php
		woocommerce_product_loop_start();

		if (wc_get_loop_prop('total')) {
			while (have_posts()) {
				the_post();

				/**
				 * Hook: woocommerce_shop_loop.
				 */
				do_action('woocommerce_shop_loop');

				wc_get_template_part('content', 'product');
			}
		}

		woocommerce_product_loop_end();
		?>

		<?php
		/**
		 * Hook: woocommerce_after_shop_loop.
		 *
		 * @hooked woocommerce_pagination - 10
		 */
		do_action('woocommerce_after_shop_loop');
		?>

	<?php else : ?>

		<!-- AUCUN PRODUIT TROUVÉ -->
		<div class="no-results woocommerce-info">
			<?php if (is_search()) : ?>
				<p>Aucun produit ne correspond à votre recherche "<strong><?php echo get_search_query(); ?></strong>".</p>
				<p>Essayez avec d'autres mots-clés ou parcourez nos catégories.</p>
			<?php else : ?>
				<?php
				/**
				 * Hook: woocommerce_no_products_found.
				 *
				 * @hooked wc_no_products_found - 10
				 */
				do_action('woocommerce_no_products_found');
				?>
			<?php endif; ?>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer('shop');

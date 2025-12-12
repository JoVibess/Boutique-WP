<?php
defined('ABSPATH') || exit;

get_header('shop'); 
?>

<main class="single-product-page container my-5">

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

    <?php
    while (have_posts()) :
        the_post();
        global $product;
    ?>

        <div class="row g-5">

            <!-- GALERIE PRODUIT -->
            <div class="col-12 col-md-6">
                <?php
                /**
                 * Galerie + image principale
                 */
                do_action('woocommerce_before_single_product_summary');
                ?>
            </div>

            <!-- INFOS PRODUIT -->
            <div class="col-12 col-md-6">

                <h1 class="product-title mb-3">
                    <?php the_title(); ?>
                </h1>

                <div class="product-price h4 mb-3">
                    <?php echo $product->get_price_html(); ?>
                </div>

                <div class="product-short-description mb-4">
                    <?php the_excerpt(); ?>
                </div>

                <!-- ADD TO CART -->
                <div class="product-add-to-cart mb-4">
                    <?php
                    woocommerce_template_single_add_to_cart();
                    ?>
                </div>

            </div>

        </div>

        <!-- DESCRIPTION / ONGLET -->
        <div class="row mt-5">
            <div class="col-12">
                <?php
                do_action('woocommerce_after_single_product_summary');
                ?>
            </div>
        </div>

    <?php endwhile; ?>

</main>

<?php
get_footer('shop'); 
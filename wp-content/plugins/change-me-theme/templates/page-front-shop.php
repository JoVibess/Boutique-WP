<?php
/*
    Template Name: Front-shop
*/

get_header();

$products_per_page = (int) get_field('nb_products_per_page', 'option');
if ($products_per_page <= 0) {
    $products_per_page = 12;
}

$paged = max(1, get_query_var('paged'), get_query_var('page'));

$args = [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => $products_per_page,
    'paged'          => $paged,
];

$products = new WP_Query($args);
?>

<div class="container my-5">
    <div class="row g-4">

        <?php if ($products->have_posts()) : ?>
            <?php while ($products->have_posts()) : $products->the_post();
                global $product; ?>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card h-100">

                        <a href="<?php the_permalink(); ?>" class="product-thumb d-block mb-3">
                            <?php
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('woocommerce_thumbnail', ['class' => 'img-fluid']);
                            } else {
                                echo wc_placeholder_img('woocommerce_thumbnail');
                            }
                            ?>
                        </a>

                        <h3 class="product-title h6 mb-1">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <div class="product-price">
                            <?php echo $product->get_price_html(); ?>
                        </div>

                    </div>
                </div>

            <?php endwhile; ?>
        <?php else : ?>
            <p>Aucun produit trouvé.</p>
        <?php endif; ?>

    </div>
</div>

<?php if ($products->max_num_pages > 1) : ?>
    <div class="container my-5">
        <div class="woocommerce-pagination">
            <?php
            echo paginate_links([
                'total'   => $products->max_num_pages,
                'current' => $paged,
            ]);
            ?>
        </div>
    </div>
<?php endif; ?>

<?php
wp_reset_postdata();
the_content();
get_footer();

<?php
/**
 * Template de recherche
 * Created by A. MACHEDA
 * @author Genesii SAS
 * @version 1.0
 */

get_header();
?>

<div class="container my-5 search-results-page">

	<div class="search-header">
		<?php if (have_posts()) : ?>
			<h1 class="search-title">
				Résultats de recherche pour : <span>"<?php echo get_search_query(); ?>"</span>
			</h1>
			<p class="search-count">
				<?php
				global $wp_query;
				$result_count = $wp_query->found_posts;
				echo $result_count . ' ' . ($result_count > 1 ? 'résultats trouvés' : 'résultat trouvé');
				?>
			</p>
		<?php else : ?>
			<h1 class="search-title">
				Aucun résultat pour : <span>"<?php echo get_search_query(); ?>"</span>
			</h1>
		<?php endif; ?>
	</div>

	<div class="search-results">
		<?php if (have_posts()) : ?>

			<div class="row g-4">
				<?php while (have_posts()) : the_post(); ?>

					<div class="col-12 col-md-6 col-lg-4">
						<article id="post-<?php the_ID(); ?>" <?php post_class('search-result-item'); ?>>

							<?php if (has_post_thumbnail()) : ?>
								<div class="result-thumbnail mb-3">
									<a href="<?php the_permalink(); ?>">
										<?php the_post_thumbnail('medium', ['class' => 'img-fluid rounded']); ?>
									</a>
								</div>
							<?php endif; ?>

							<h3 class="result-title h5 mb-2">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>

							<div class="result-excerpt text-muted small mb-2">
								<?php the_excerpt(); ?>
							</div>

							<div class="result-meta small text-muted">
								<?php
								// Afficher le type de post
								$post_type_obj = get_post_type_object(get_post_type());
								if ($post_type_obj) {
									echo '<span class="post-type">' . esc_html($post_type_obj->labels->singular_name) . '</span>';
								}

								// Afficher la date pour les posts
								if (get_post_type() === 'post') {
									echo ' • ' . get_the_date();
								}
								?>
							</div>

						</article>
					</div>

				<?php endwhile; ?>
			</div>

			<!-- Pagination -->
			<div class="search-pagination mt-5">
				<?php
				the_posts_pagination(array(
					'mid_size' => 2,
					'prev_text' => '← Précédent',
					'next_text' => 'Suivant →',
				));
				?>
			</div>

		<?php else : ?>

			<div class="no-results">
				<p class="mb-4">Désolé, aucun résultat ne correspond à votre recherche. Essayez avec d'autres mots-clés.</p>

				<!-- Formulaire de recherche -->
				<div class="search-again">
					<?php get_search_form(); ?>
				</div>
			</div>

		<?php endif; ?>
	</div>

</div>

<?php
get_footer();

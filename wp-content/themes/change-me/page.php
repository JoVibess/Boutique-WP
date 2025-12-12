<?php
/**
 * Template pour les pages standards
 * Created by A. MACHEDA
 * @author Genesii SAS
 * @version 1.0
 */

get_header();
?>

<div class="container my-5">
	<?php
	while (have_posts()) :
		the_post();
	?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php if (!is_front_page()) : ?>
				<header class="page-header mb-4">
					<h1 class="page-title"><?php the_title(); ?></h1>
				</header>
			<?php endif; ?>

			<div class="page-content">
				<?php the_content(); ?>
			</div>

		</article>
	<?php
	endwhile;
	?>
</div>

<?php
get_footer();

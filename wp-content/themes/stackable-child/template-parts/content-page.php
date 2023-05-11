<?php
/**
 * Template part for displaying page content in page.php.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Stackable
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="hentry-wrapper">
		<?php if ( ! has_post_thumbnail() ) : ?>
			<header class="entry-header" <?php stackable_background_image(); ?>>
				<div class="entry-header-wrapper">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</div><!-- .entry-header-wrapper -->
			</header><!-- .entry-header -->
		<?php endif; ?>

		<div class="entry-content">
			<?php
			the_content();

			wp_link_pages( array(
				'before'      => '<div class="page-links"><span class="page-links-title">' . esc_html__( 'Pages:', 'stackable' ) . '</span>',
				'after'       => '</div>',
				'link_before' => '<span>',
				'link_after'  => '</span>',
				'pagelink'    => '<span class="screen-reader-text">' . esc_html__( 'Page', 'stackable' ) . ' </span>%',
				'separator'   => '<span class="screen-reader-text">, </span>',
			) );
			?>
		</div><!-- .entry-content -->
	</div><!-- .hentry-wrapper -->
</article><!-- #post-## -->

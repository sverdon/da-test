<?php
/**
 * The header for our theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Stackable
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<!-- Stop search engines from indexing the site -->
<meta name="robots" content="noindex">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'stackable' ); ?></a>

	<header id="masthead" class="site-header" role="banner">
		<div class="site-header-wrapper">
			<div class="site-branding">
				<?php stackable_the_custom_logo(); ?>

				<?php if ( is_front_page() && is_home() ) : ?>
					<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
				<?php else : ?>
					<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
				<?php endif; ?>
				<p class="site-description"><?php bloginfo( 'description' ); ?></p>
			</div><!-- .site-branding -->

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<button id="menu-toggle" class="menu-toggle"><?php esc_html_e( 'Menu', 'stackable' ); ?></button>
				<div id="site-menu" class="site-menu">
					<nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php echo esc_attr( esc_html__( 'Primary Menu', 'stackable' ) ); ?>">
						<?php
						wp_nav_menu( array(
							'theme_location' => 'primary',
							'menu_class'     => 'primary-menu',
						 ) );
						?>
					</nav><!-- .main-navigation -->
				</div><!-- .site-menu -->
			<?php endif; ?>
			<?php
				if ( function_exists( 'stackable_woocommerce_header_cart' ) ) {
					stackable_woocommerce_header_cart();
				}
			?>
		</div><!-- .site-header-wrapper -->
	</header><!-- #masthead -->

	<div id="content" class="site-content">
		<?php if ( get_header_image() ) : ?>
			<div class="header-image">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<img src="<?php header_image(); ?>" width="<?php echo esc_attr( get_custom_header()->width ); ?>" height="<?php echo esc_attr( get_custom_header()->height ); ?>" alt="">
				</a>
			</div><!-- .header-image -->
		<?php endif; // End header image check. ?>
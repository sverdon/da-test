<?php

$theme = wp_get_theme();
define('THEME_VERSION', $theme->Version);

// Enqueue Parent Theme & Custom Scripts
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
    $parenthandle = 'stackable';
    $theme = wp_get_theme();
    wp_enqueue_style( $parenthandle, get_template_directory_uri() . '/style.css', 
        array(),  // if the parent theme code has a dependency, copy it to here
        $theme->parent()->get('Version')
    );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(), array($parenthandle), THEME_VERSION
    );
    wp_enqueue_script( 'custom-javascript', get_stylesheet_directory_uri() . '/js/scripts.js', array( 'jquery' ), THEME_VERSION );
}

// Conditional Nav Menu
function wpc_wp_nav_menu_args( $args = '' ) {
    if( is_user_logged_in() ) { 
        $args['menu'] = 'logged-in';
    } else { 
        $args['menu'] = 'logged-out';
    } 
        return $args;
    }
    add_filter( 'wp_nav_menu_args', 'wpc_wp_nav_menu_args' );

// Custom Login Logo
function my_login_logo_one() {
	?>
	<style type="text/css">
		body.login div#login h1 a {
			background-image: url(/wp-content/uploads/2022/04/Del-Logo-100x100-1.png);
			padding-bottom: 30px;
			width: 100px !important;
			background-size: 100px;
		}
	</style>
	 <?php
} add_action( 'login_enqueue_scripts', 'my_login_logo_one' );

function wpb_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'wpb_login_logo_url' );

function wpb_login_logo_url_title() {
    return 'DelAgua Dashboard';
}
add_filter( 'login_headertitle', 'wpb_login_logo_url_title' );

// Require Login If Parent Page Is Dashboard
add_action( 'template_redirect',
    function() {
    	global $post;

        if(!is_user_logged_in() && (39 == $post->post_parent)) {
            wp_safe_redirect(wp_login_url(get_permalink()));
            exit();
        }
    }
);
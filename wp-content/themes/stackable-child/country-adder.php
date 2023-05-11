<?php
/**
 * Template Name: Country Adder
 */

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
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
                    <?php the_content(); ?>

                    <form action="/da-forms/country-adder/add-country.php" class="da-form standard" id="country-adder">

                        <!-- Country -->
                        <div class="da-form-item">
                            <label for="country">Country</label>
                            <input type="text" name="country">
                        </div>

                        <!-- Work Region -->
                        <div class="da-form-item">
                            <label for="workregion">Work Region</label>
                            <input type="text" name="workregion">
                        </div>

                        <!-- Distribution Region -->
                        <div class="da-form-item">
                            <label for="distregion">Distribution Region</label>
                            <input type="text" name="distregion">
                        </div>

                        <!-- Administrative Boundaries -->
                        <div class="boundaries">
                            <label>Administrative Boundaries</label>
                            <div class="da-form-item clone hidden">
                                <label for="boundary"></label>
                                <input type="text" name="boundary[]">
                            </div>

                            <div class="da-form-item">
                                <label for="boundary">Level 1</label>
                                <input type="text" name="boundary[]">
                            </div>
                        </div>

                        <!-- Add Boundary Button -->
                        <div class="da-form-item">
                            <a href="" class="button" id="add-boundary">Add Boundary</a>
                        </div>

                        <!-- Submit -->
                        <div class="da-form-item">
                            <input type="submit">
                        </div>
                    </form>

                </div><!-- .entry-content -->

            </div><!-- .hentry-wrapper -->
        </article><!-- #post-## -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
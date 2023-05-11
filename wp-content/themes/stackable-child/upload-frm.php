<?php
/**
 * Template Name: Upload FRM
 */

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
                </div><!-- .entry-content -->

                <form action="/da-forms/upload-frm/upload-frm.php" enctype="multipart/form-data" method="POST" class="da-form file-upload">
                    <div class="da-form-item">
                        <label for="file">Upload CSV here:</label>
                        <input type="file" name="file" accept=".csv">
                    </div>
                    <div class="da-form-item">
                        <input type="submit" value="Upload">
                    </div>
                </form>

            </div><!-- .hentry-wrapper -->
        </article><!-- #post-## -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
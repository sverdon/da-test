<?php
/**
 * Template Name: NumHHs Uploader
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

                <form action="/da-forms/numhhs/upload-numhhs.php" enctype="multipart/form-data" method="POST" class="da-form file-upload">
                    <input type="hidden" name="username" value="<?= $cuName; ?>">
                    <div class="da-form-item">
                        <input type="file" name="numhhs" accept=".xlsx">
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
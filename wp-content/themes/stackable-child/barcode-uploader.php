<?php
/**
 * Template Name: Barcode Uploader
 */

require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

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

                <form action="/da-forms/upload-barcode/upload-barcode.php" enctype="multipart/form-data" method="POST" class="da-form file-upload">
                    <?php 
                    $sql = "SELECT VersionNumber FROM ProgramTemplates WHERE TemplateID = 2";
                    $result = mysqli_query($conn_da, $sql);

                    while($row = mysqli_fetch_assoc($result)) {
                        $tVer = $row['VersionNumber'];
                    }
                    ?>
                    <p>Please make sure the template being uploaded is version <?= $tVer; ?>. You can find the most up to date templates <a href="/dashboard/templates/">here</a>:</p>
                    <div class="da-form-item">
                        <input type="file" name="barcodes" accept=".xlsx">
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
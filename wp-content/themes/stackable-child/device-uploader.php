<?php
/**
 * Template Name: Device SN Uploader
 */
$cu = wp_get_current_user();
$cuName = $cu->user_firstname . ' ' . $cu->user_lastname;

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
                </div><!-- .entry-content -->

                <form action="/da-forms/device-uploader/upload-sn.php" enctype="multipart/form-data" method="POST" class="da-form file-upload">
                    <?php
                        $sql = "SELECT VersionNumber FROM ProgramTemplates WHERE TemplateID = 5";
                        $result = mysqli_query($conn_da, $sql);
                        while($row = mysqli_fetch_assoc($result)) {
                            $version = $row['VersionNumber'];
                        }
                    ?>
                    <input type="hidden" name="username" value="<?= $cuName; ?>">
                    <div class="da-form-item">
                        <label for="file">Please upload Version <?= $version; ?> templates only.</label>
                        <input type="file" name="file" accept=".xlsx">
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
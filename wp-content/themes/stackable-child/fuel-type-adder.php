<?php
/**
 * Template Name: Fuel Type Adder
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

                    <form action="/da-forms/fuel-type-adder/add-fuel.php" class="da-form standard" id="fuel-adder">

                        <!-- Country -->
                        <div class="da-form-item">
                            <label for="country">Country</label>
                            <select name="country">
                                <option value="">Please Select</option>
                                <?php
                                    $sql = "SELECT GID, RegionName 
                                            FROM g_Locations
                                            WHERE RegionType = 'Country'";
                                    $result = mysqli_query($conn_da, $sql);

                                    if (mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['GID'] . "'>" . $row['RegionName'] . "</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>

                        <!-- Fuel Type - English -->
                        <div class="da-form-item">
                            <label for="fuel-english">Fuel Type - English</label>
                            <input type="text" name="fuel-english">
                        </div>

                        <!-- Fuel Type - Local -->
                        <div class="da-form-item">
                            <label for="fuel-local">Fuel Type - Local</label>
                            <input type="text" name="fuel-local">
                        </div>

                        <!-- Biomass -->
                        <div class="da-form-item">
                            <label for="biomass">Is this fuel a type of Biomass?</label>
                            <select name="biomass">
                                <option value="">Please Select</option>
                                <option value="-1">Yes</option>
                                <option value="0">No</option>
                            </select>
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
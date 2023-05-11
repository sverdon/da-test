<?php
/**
 * Template Name: TID Adder
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

                    <form action="/da-forms/tid-adder/tid-adder.php" class="da-form standard" id="tidadder">
                        <!-- Form Generation Inputs -->
                        <input type="hidden" name="distrregion">

                        <!-- Central Warehouse -->
                        <div class="da-form-item central">
                            <label for="central">Is the outbound warehouse a central warehouse?</label>
                            <select name="central" id="central">
                                <option value="">Please Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                            <!-- <p class="alert hidden">Please finish selecting the Outbound Location above.</p> -->
                        </div>

                        <!-- Country / Regions -->
                        <div class="da-form-item region region-clone hidden">
                            <label for="region"></label>
                            <select name="region[]">
                                <option value="">Please Select</option>
                            </select>
                        </div>
                        <div class="da-form-item region">
                            <label for="region">Country</label>
                            <select name="region[]" class="country">
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

                        <!-- Outbound Warehouse -->
                        <div class="da-form-item outbound-wh">
                            <label for="outbound-wh">Outbound Warehouse</label>
                            <select name="outbound-wh" id="outbound-wh">
                                <option value="">Please Select</option>
                            </select>
                        </div>

                        <!-- Date -->
                        <div class="da-form-item">
                            <label for="date">Date</label>
                            <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Num Trucks -->
                        <div class="da-form-item">
                            <label for="numtrucks">Number of Trucks</label>
                            <input type="number" name="numtrucks" value="1" min="1" max="10">
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
<?php
/**
 * Template Name: Warehouse Adder
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

                    <form action="/da-forms/warehouse-adder/add-warehouse.php" class="da-form standard" id="warehouse-adder">

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

                        <!-- Warehouse Name -->
                        <div class="da-form-item">
                            <label for="wh-name">Warehouse Name</label>
                            <input type="text" name="wh-name">
                        </div>

                        <!-- Warehouse Type -->
                        <div class="da-form-item">
                            <label for="wh-type">Warehouse Type</label>
                            <select name="wh-type" id="wh-type">
                                <option value="">Please Select</option>
                                <option value="Government Office">Government Office</option>
                                <option value="School">School</option>
                                <option value="Non-Gov Office">Non-Gov Office</option>
                                <option value="Warehouse">Warehouse</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Describe Warehouse -->
                        <div class="da-form-item describe hidden">
                            <label for="describe">Please describe the warehouse:</label>
                            <textarea name="describe" id="" cols="15" rows="5"></textarea>
                        </div>

                        <!-- Central Warehouse -->
                        <div class="da-form-item">
                            <label for="central">Is this a central warehouse?</label>
                            <select name="central" id="">
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
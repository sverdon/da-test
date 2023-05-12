<?php
/**
 * Template Name: Templates
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
                </div><!-- .entry-content -->

                <!-- Bennie Adder Popup --> 
                <div id="popup-overlay" class="hidden"></div>
                <div class="popup hidden" id="ba-popup">
                    <div class="popup-wrapper">
                        <form action="/da-forms/beneficiary-adder/template-download-new.php" class="da-form return-url" id="ba-popup-form">
                            <input type="hidden" name="template-url">
                            <div class="da-form-item region region-clone hidden">
                                <label for="region"></label>
                                <select name="region[]">
                                    <option value="NULL">Please Select</option>
                                </select>
                            </div>
                            <div class="da-form-item region">
                                <label for="region">Country</label>
                                <select name="region[]">
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
                            <div class="da-form-item">
                                <input type="submit">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- CHW Popup -->
                <div class="popup hidden" id="chw-popup">
                    <div class="popup-wrapper">
                        <form action="/da-forms/templates/chw-template.php" class="da-form return-url" id="chw-popup-form">
                            <input type="hidden" name="workregion-chw">
                            <input type="hidden" name="template-url">
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
                            <div class="da-form-item">
                                <input type="submit">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Staff Adder Popup -->
                <div class="popup hidden" id="sa-popup">
                    <div class="popup-wrapper">
                        <form action="/da-forms/templates/sa-template.php" class="da-form return-url" id="chw-popup-form">
                            <input type="hidden" name="template-url">
                            <div class="da-form-item">
                                <label for="country">Country</label>
                                <select name="country" class="country">
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
                            <div class="da-form-item">
                                <input type="submit">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Region Adder Popup -->
                <div class="popup hidden" id="ra-popup">
                    <div class="popup-wrapper">
                        <form action="/da-forms/templates/ra-template.php" class="da-form return-url" id="region-adder-form">
                            <p>Select the boundary that new regions will be added to:</p>
                            <input type="hidden" name="template-url">
                            <input type="hidden" name="sublevel">
                            <div class="da-form-item">
                                <label for="country">Country</label>
                                <select name="country" class="country">
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
                            <div class="da-form-item hidden">
                                <label for="boundary">Boundary</label>
                                <select name="boundary" id="">
                                    <option value="">Please Select</option>
                                </select>
                            </div>
                            <div class="da-form-item">
                                <input type="submit">
                            </div>
                        </form>
                    </div>
                </div>

            </div><!-- .hentry-wrapper -->
        </article><!-- #post-## -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
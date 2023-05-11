<?php
/**
 * Template Name: Activity Schedule
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

                    <form action="/da-forms/activity-schedule/generate-table.php" class="da-form distrregion" id="activity-schedule">
                        <!-- Form Generation Inputs -->
                        <input type="hidden" name="distrregion">
                        <!-- Activity -->
                        <div class="da-form-item">
                            <label for="activity">Activity</label>
                            <select name="activity" id="activity">
                                <option value="">Select One</option>
                                <option value="Distr">Distr</option>
                                <option value="HHV">HHV</option>
                            </select>
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

                        <!-- Date Start -->
                        <div class="da-form-item">
                            <label for="date-start">Date Start</label>
                            <input type="date" name="date-start">
                        </div>

                        <!-- Date End -->
                        <div class="da-form-item">
                            <label for="date-end">Date End</label>
                            <input type="date" name="date-end">
                        </div>

                        <!-- Team ID -->
                        <div class="da-form-item">
                            <label for="teamid">Team ID</label>
                            <select name="teamid" id="teamid">
                                <option value="">Select One</option>
                            </select>
                        </div>

                        <!-- Submit -->
                        <div class="da-form-item">
                            <input type="submit">
                        </div>
                    </form>
                    
                    <!-- Table -->
                    <table class="da-table editable hidden">
                        <thead>
                            <tr></tr>
                        </thead>
                        <tbody> 
                            <tr class="clone hidden"></tr>    
                        </tbody>
                    </table>
                    <a href="" class="button hidden excel-export" id="export-activity-table">Export Schedule</a>
                    <!-- <div class="da-edit-tools hidden">
                        <a href="" class="new-row">New Entry</a>
                        <a href="" class="edit-row">Edit Entry</a>
                    </div> -->

                </div><!-- .entry-content -->

            </div><!-- .hentry-wrapper -->
        </article><!-- #post-## -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
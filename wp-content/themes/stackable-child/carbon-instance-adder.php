<?php
/**
 * Template Name: Carbon Instance Adder
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

                    <form action="/da-forms/carbon-project-adder/add-instance.php" class="da-form standard" id="carbon-instance-adder">

                        <!-- Project -->
                        <div class="da-form-item">
                            <label for="project">Project</label>
                            <select name="project">
                                <option value="">Please Select</option>
                                <?php
                                    $sql = "SELECT ProjectID, CONCAT(Registry,' - ',RegistryID) AS Project
                                            FROM c_Proj 
                                            WHERE isGrouped = 'Yes'";
                                    $result = mysqli_query($conn_da, $sql);

                                    if (mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['ProjectID'] . "'>" . $row['Project'] . "</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>

                        <!-- Investor -->
                        <div class="da-form-item">
                            <label for="investor">Investor</label>
                            <select name="investor">
                                <option value="">Please Select</option>
                                <?php
                                    $sql = "SELECT InvestorID, Investor 
                                            FROM Investors";
                                    $result = mysqli_query($conn_da, $sql);

                                    if (mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['InvestorID'] . "'>" . $row['Investor'] . "</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>

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

                        <!-- Instance Name -->
                        <div class="da-form-item">
                            <label for="instance-name">Instance Name</label>
                            <input type="text" name="instance-name">
                        </div>

                        <!-- Instance Notes -->
                        <div class="da-form-item">
                            <label for="instance-notes">Instance Notes</label>
                            <textarea name="instance-notes" cols="10" rows="5"></textarea>
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
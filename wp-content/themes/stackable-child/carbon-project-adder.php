<?php
/**
 * Template Name: Carbon Project Adder
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

                    <form action="/da-forms/carbon-project-adder/add-project.php" class="da-form standard" id="carbon-project-adder">

                        <!-- Project Name -->
                        <div class="da-form-item">
                            <label for="project-name">Project Name</label>
                            <input type="text" name="project-name">
                        </div>

                        <!-- Registry -->
                        <div class="da-form-item">
                            <label for="registry">Registry</label>
                            <select name="registry">
                                <option value="">Please Select</option>
                                <option value="VCS">VCS</option>
                                <option value="GS">GS</option>
                                <option value="CDM">CDM</option>
                            </select>
                        </div>

                        <!-- Registry ID -->
                        <div class="da-form-item">
                            <label for="registryid">Registry ID</label>
                            <input type="text" name="registryid">
                        </div>

                        <!-- Country -->
                        <div class="da-form-item">
                            <label for="country">Country</label>
                            <select name="country">
                                <option value="0">Please Select</option>
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
                            <p class="smalllink">Optional - select if project is located in only one country.</p>
                        </div>

                        <!-- Investor -->
                        <div class="da-form-item">
                            <label for="investor">Investor</label>
                            <select name="investor">
                                <option value="0">Please Select</option>
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
                            <p class="smalllink">Optional - select if only one investor in project.</p>
                        </div>

                        <!-- Grouped Project -->
                        <div class="da-form-item">
                            <label for="group-project">Is this a grouped project?</label>
                            <select name="group-project" id="group-project">
                                <option value="">Please Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>

                        <!-- Project Notes -->
                        <div class="da-form-item">
                            <label for="project-notes">Project Notes</label>
                            <textarea name="project-notes" cols="10" rows="5"></textarea>
                        </div>

                        <!-- HIDDEN -->
                            <!-- Instance Name -->
                            <div class="da-form-item hidden">
                                <label for="instance-name">Instance Name</label>
                                <input type="text" name="instance-name">
                            </div>

                            <!-- Instance Country -->
                            <div class="da-form-item hidden">
                                <label for="instance-country">Instance Country</label>
                                <select name="instance-country">
                                    <option value="0">Please Select</option>
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

                            <!-- Instance Investor -->
                            <div class="da-form-item hidden">
                                <label for="instance-investor">Investor</label>
                                <select name="instance-investor">
                                    <option value="0">Please Select</option>
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

                            <!-- Instance Notes -->
                            <div class="da-form-item hidden">
                                <label for="instance-notes">Instance Notes</label>
                                <textarea name="instance-notes" cols="10" rows="5"></textarea>
                            </div>
                        <!-- END HIDDEN -->

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
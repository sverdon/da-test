<?php
/**
 * Template Name: Delivery Notes
 */

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$tid = $_GET['tid'];

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
                    
                    <!-- Instructions -->
                    <ol>
                        <li><span style="color: #008de4; font-weight: bold;">Generate</span> the table by entering a Truck ID</li>
                        <li><span style="color: #008de4; font-weight: bold;">Click</span> one of the buttons to generate a PDF</li>
                        <li><span style="color: #008de4; font-weight: bold;">Add/Edit</span> rows by clicking on them</li>
                    </ol>

                    <!-- Truck ID Input -->
                    <div class="da-form-item">
                        <label for="truckid">Input Truck ID</label>
                        <input type="number" name="truckid" value="<?php echo $tid; ?>">
                    </div>
                    
                    <!-- Table -->
                    <table class="da-table editable hidden">
                        <thead>
                            <tr>
                                <th>TDID</th>
                                <th>Dest</th>
                                <th>Cell</th>
                                <th>Sector</th>
                                <th>District</th>
                                <th>Province</th>
                                <th>Stoves</th>
                                <th>Posters</th>
                                <th>Warehouse</th>
                                <th>BC Prefix</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="clone hidden">
                                <td class="tdid"></td>
                                <td class="destid"></td>
                                <td class="cell"></td>
                                <td class="sector"></td>
                                <td class="district"></td>
                                <td class="province"></td>
                                <td class="stoves"></td>
                                <td class="posters"></td>
                                <td class="location"></td>
                                <td class="bcprefix"></td>
                                <td class="investorid hidden"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="da-edit-tools hidden">
                        <a href="" class="new-row">New Entry</a>
                        <a href="" class="edit-row">Edit Entry</a>
                    </div>

                    <!-- Buttons -->
                    <div class="pdf-buttons hidden">
                        <a href="" class="button generate-pdf scanning">Generate Outbound Scanning Form</a>
                        <a href="" class="button generate-pdf delivery">Generate Delivery Note</a>
                    </div>

                    <!-- Popup -->
                    <div id="popup-overlay" class="hidden"></div>
                    <div class="popup hidden">
                        <div class="popup-wrapper">
                            <form action="/da-forms/delivery-notes/popup-submit.php" class="da-form standard" id="dnote-popup-form">
                                <input type="hidden" name="tdid">
                                <input type="hidden" name="tid">
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
                                                    WHERE ParentID = 0";
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
                                    <label for="stoves">Stoves</label>
                                    <input type="number" name="stoves">
                                </div>
                                <div class="da-form-item">
                                    <label for="posters">Posters</label>
                                    <input type="number" name="posters">
                                </div>
                                <div class="da-form-item">
                                    <label for="bcformat">BC Format</label>
                                    <select name="bcformat" id="bcformat">
                                        <option value="NULL">Select Prefix</option>
                                        <?php
                                            $sql = "SELECT Prefix, BCFID FROM Inv_BCFormat";
                                            $result = mysqli_query($conn_da, $sql);

                                            if (mysqli_num_rows($result) > 0) {
                                                while($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='" . $row['BCFID'] . "'>" . $row['Prefix'] . "</option>";
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

                </div><!-- .entry-content -->

            </div><!-- .hentry-wrapper -->
        </article><!-- #post-## -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
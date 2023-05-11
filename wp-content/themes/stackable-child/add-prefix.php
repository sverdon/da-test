<?php
/**
 * Template Name: Add Prefix
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

                    <form action="/da-forms/product-order/add-prefix.php" class="da-form standard" id="add-prefix">
                        <div class="da-form-item">
                            <label for="prefix">Prefix</label>
                            <input type="text" name="prefix">
                        </div>
                        <div class="da-form-item">
                            <label for="digits"># Digits</label>
                            <input type="number" name="digits">
                        </div>
                        <div class="da-form-item">
                            <label for="product">Select Product</label>
                            <select name="product">
                                <option value="">Please Select</option>
                                <?php
                                    $sql = "SELECT CONCAT(Manufacturer, ' - ', ProductName) AS ProductName, ProductID FROM Products";
                                    $result = mysqli_query($conn_da, $sql);
                                    if (mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['ProductID'] . "'>" . $row['ProductName'] . "</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="da-form-item">
                            <label for="investor">Select Investor</label>
                            <select name="investor">
                                <option value="">Please Select</option>
                                <?php
                                    $sql = "SELECT Investor, InvestorID FROM Investors";
                                    $result = mysqli_query($conn_da, $sql);
                                    if (mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['InvestorID'] . "'>" . $row['Investor'] . "</option>";
                                        }
                                    }
                                ?>
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
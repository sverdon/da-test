<?php
/**
 * Template Name: Product Order
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

                    <form action="https://insight.delagua.org/da-forms/product-order/submit.php" method="POST" class="da-form standard" id="product-order">
                        <div class="da-form-item">
                            <label for="investors">Select Investor</label>
                            <select name="investors" id="investors">
                                <option value="">Select One</option>
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
                        <div class="da-form-item">
                            <label for="products">Select Product</label>
                            <select name="products" id="products">
                                <option value="">Select One</option>
                                <?php
                                    $sql = "SELECT ProductID, CONCAT(Manufacturer, ' ', ProductName) AS Name
                                            FROM Products";
                                    $result = mysqli_query($conn_da, $sql);

                                    if (mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['ProductID'] . "'>" . $row['Name'] . "</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="da-form-item">
                            <label for="format">Select Barcode Prefix</label>
                            <select name="format" id="format" class="stoveCalc">
                                <option value="">Select One</option>
                            </select>
                            <a href="/dashboard/add-prefix" class="smallLink">Add Prefix</a>
                        </div>
                        <div class="da-form-item hidden">
                            <label for="numStoves">Enter # of Stoves in Order</label>
                            <input type="number" name="numStoves" class="stoveCalc">
                        </div>
                        <div class="da-form-item hidden">
                            <label for="start">Start #</label>
                            <input type="number" name="start" readonly>
                        </div>
                        <div class="da-form-item hidden">
                            <label for="end">End #</label>
                            <input type="number" name="end" readonly>
                        </div>
                        <div class="da-form-item hidden">
                            <label for="date">Order Date</label>
                            <input type="date" name="date">
                        </div>
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
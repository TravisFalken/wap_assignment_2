<?php

require('includes/config.inc.php');


// Check for a valid user ID, through GET or POST:
if ((isset($_GET['id'])) && (is_numeric($_GET['id']))) { // From shop.php
    $id = $_GET['id'];
} else {
    //REDIRECT the user because there is no product to show
    $url = BASE_URL . 'shop.php'; // Define the URL.
    ob_end_clean(); // Delete the buffer.
    header("Location: $url");
    exit(); // Quit the script.
}

//Connect to database
require(MYSQL);

// Make the query:
$q = "  SELECT 
            pd.product_id
            ,   pd.product_title
            ,   pd.product_description
            ,   pd.price
            ,   pd.stock_quantity
            ,	(
                    SELECT
                        ig.image_name
                    FROM product_images pi
                    INNER JOIN images ig ON ig.image_id = pi.image_id
                    WHERE 	pi.product_id = pd.product_id
                    AND		pi.cover_image = 1	
                    LIMIT 1
                ) AS cover_image_name
            FROM        products pd
            WHERE pd.product_id = $id
            ORDER BY product_title ASC";

//Query the database
$r = mysqli_query($dbc, $q);

if (mysqli_num_rows($r) == 1) {
    $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
    $page_title = $row['product_title'];
    include('includes/header.html');
    include('includes/navigation_bar.html');
    echo '
            <div class="row custom-back-button-div">
                <a href="' . BASE_URL . 'shop.php" class="btn custom-back-button"><i class="fas fa-chevron-left"></i>  Back</a>
            </div>
            <div class="row custom-row-style ">
                <div class="col-sm-12 col-md-12 col-lg-6 d-flex custom-view-product-image-div">
                    <img class="custom-view-product-image" src="' . BASE_URL . 'uploads/' . $row['cover_image_name'] . '" >
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6 custom-view-product-content">
                    <h1>' . $row['product_title'] . '</h1>
                    <p><b>$' . number_format($row['price'], 2) . '</b></p>
                    <p>' . $row['product_description'] . '</p>
                    <p>Remaining Stock: ' . $row['stock_quantity'] . '</p>
                    <div class="custom-product-column">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="lbl_num_products">Number of Products:</span>
                            </div>
                            <input type="number" class="form-control" name="num_of_products" min="1" max="' . $row['stock_quantity'] .  '" value="1" aria-label="Default" aria-describedby="lbl_num_products">
                        </div>
                    </div>
                    <a href="' . BASE_URL . 'add_cart.php?id=' . $row['product_id'] . '" class="btn btn-primary"><i class="fas fa-shopping-cart"></i>  Add to Cart</a>
                </div>';
} else {
    $page_title = 'View Product';
    include('includes/header.html');
    include('includes/navigation_bar.html');
    echo '<p>There is no product to show!</p>';
}

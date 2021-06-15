<?php
$page_title = 'Shop Products';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
include('includes/header.html');
require('includes/config.inc.php');
include('includes/navigation_bar.html');


// require('../../mysqli_connect.php'); //Connect to the database

// Page header:
echo '<div class="container"><h1>Shop</h1>';

//Connect to the database
require('../mysqli_connect.php');

// Make the query:
$q = "  SELECT 
            pd.product_id
            ,   pd.product_title
            ,   pd.price
            ,   pd.stock_quantity
            ,   pd.total_sales
            ,   pd.product_description
            ,   DATE_FORMAT(pd.created_date, '%d %M %Y') AS dr 
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
            ORDER BY product_title ASC";
$r = mysqli_query($dbc, $q); // Run the query.

// Count the number of returned rows:
$num = $r->num_rows;

if ($num > 0) { // If it ran OK, display the records.

    // Print how many users there are:
    //echo "<p>There are currently $num products.</p></div>\n";

    // Table header.
    echo '
	<div class="container">
        <div class="row">
';

    // Fetch and print all the records:
    while ($row = $r->fetch_object()) {
        echo '
                <div class="col-sm-6 col-lg-3 custom-card-container">
                    <div class="card custom-card">
                        <div class="custom-card-image-div">
                            <img class="custom-card-image" src="' . BASE_URL . 'uploads/' . $row->cover_image_name . '">
                        </div>
                        <div class="card-body">
                            <div class="custom-card-body">
                                <h5 class="card-title">' . $row->product_title . '</h5>
                                    <p class="card-text">' . $row->product_description . '</p>
                                
                                <p class="card-text"><b>$' . number_format($row->price, 2) . '</b></p>
                            </div>
                            <a href="' . BASE_URL . 'view_product.php?id=' . $row->product_id . '" class="btn btn-primary">View Product</a>
                        </div>
                    </div>
                </div>
		';
    }

    echo '</div></div>'; // Close the table.

    $r->free(); // Free up the resources.
    unset($r);
} else { // If no records were returned.

    echo '<p class="error">There are currently no products.</p>';
}

// Close the database connection.
mysqli_close($dbc);
unset($mysqli);
?>
<?php
include('includes/footer.html');
?>
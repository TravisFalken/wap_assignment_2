<?php
$page_title = 'Shop Products';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
include('includes/header.html');
require('includes/config.inc.php');
include('includes/navigation_bar.html');


// require('../../mysqli_connect.php'); //Connect to the database

// Page header:
echo '<div class="container"><h1>Shop</h1></div>';

//Connect to the database
require('../mysqli_connect.php');
$productPrice = $productDescription = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //Trim all incomming data
    $trimmed = array_map('trim', $_POST);



    //Get filter price
    if (isset($trimmed['productPrice']) && $trimmed['productPrice'] > 0) {
        $productPrice = $trimmed['productPrice'];
    }

    //Get product description
    if (isset($trimmed['productDesc'])) {
        $productDescription = mysqli_real_escape_string($dbc, $trimmed['productDesc']);
    }
}

$maxProductPrice = 1000;
//Get the max product price
$q = "  SELECT 
            MAX(price) AS max_product_price
        FROM products";

$r = mysqli_query($dbc, $q);

if (@mysqli_num_rows($r) == 1) {
    $row = mysqli_fetch_array($r, MYSQLI_ASSOC);

    $maxProductPrice = $row['max_product_price'];
    $maxProductPrice = $maxProductPrice + 20;
}

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
            WHERE       (pd.price               <= " . ((is_null($productPrice) ? "NULL" : $productPrice))    .  "                OR " . ((is_null($productPrice) ? "NULL" : $productPrice)    .  " IS NULL)
            AND         (pd.product_description LIKE '%$productDescription%'    OR " . ((is_null($productDescription)) ? "NULL" : "'$productDescription'")) . " IS NULL)
            ORDER BY product_title ASC";

$r = mysqli_query($dbc, $q); // Run the query.

// Count the number of returned rows:
$num = $r->num_rows;



// Print how many users there are:
//echo "<p>There are currently $num products.</p></div>\n";

// Table header.
echo '
	<div class="container">
        <form action="shop.php" method="post">
        <div class="card">
            <div class="card-header">
                <h3>Search Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-lg-4">
                        <label for="productDesc">Product Description:</label>
                        <input class="form-control" type="text" name="productDesc" value="' . $productDescription . '" size="20">
                    </div>
                    <div class="form-group col-lg-4">
                        <label for="productPrice">Product Price:</label>
                        <input class="form-control-range custom-range" type="range" min="0" max="' . $maxProductPrice . '"  value="' . ((is_null($productPrice)) ? $maxProductPrice : $productPrice) . '" id="productPriceRange" name="productPrice" size="20">
                        <small id="priceRangeOutput">Price Between <b>$0</b> and <b>$' . ((is_null($productPrice)) ? $maxProductPrice : $productPrice)  . '</b></small>
                        
                    </div>
    
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-8 col-xl-6">
                        <button type="submit" name="submit" class="btn btn-primary btn-lg btn-block"><i class="fas fa-search"></i> Search</button>
                    </div>
                </div>
            </div>
            </div>
        </form>
       
        <div class="row">
';
if ($num > 0) { // If it ran OK, display the records.
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

    echo '</div></div> </div>
            <script>
              
                var slider = document.getElementById("productPriceRange");
                var output = document.getElementById("priceRangeOutput");
                output.innerHTML = "Price Between <b>$0</b> and <b>$"  + slider.value + "</b>";

                slider.oninput = function() {
                output.innerHTML = "Price Between <b>$0</b> and <b>$" + this.value + "</b>";
                }
            </script>'; // Close the table.

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
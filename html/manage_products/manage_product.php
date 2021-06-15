<?php
$page_title = 'Manage Products';
include('../includes/header.html');
require('../includes/config.inc.php');
include('../includes/navigation_bar.html');

//Make sure user has admin permissions
if (isset($_SESSION['user_level']) && $_SESSION['user_level'] == 1) {

    // require('../../mysqli_connect.php'); //Connect to the database

    // Page header:
    echo '<div class="container"><h1>Manage Products</h1>';
} else {
    //REDIRECT the user because they do not have permission
    $url = BASE_URL . 'index.php'; // Define the URL.
    ob_end_clean(); // Delete the buffer.
    header("Location: $url");
    exit(); // Quit the script. 
}
//Connect to the database
require('../../mysqli_connect.php');

// Make the query:
$q = "  SELECT 
            pd.product_id
            ,   pd.product_title
            ,   pd.price
            ,   pd.stock_quantity
            ,   pd.total_sales
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
    echo "<p>There are currently $num products.</p></div>\n";

    // Table header.
    echo '
	<div class="table-responsive container">
	<table class="table" width="60%">
	<thead>
	<tr><td></td><td align="left"><strong>Title</strong></td><td align="left"><strong>Price</strong></td><td align="left"><strong>Stock Qty</strong></td><td align="left"><strong>Total Sales</strong></td><td align="left"><strong>Date Created</strong></td><td align="left"></td><td align="left"></td></tr>
	</thead>
	<tbody>
';

    // Fetch and print all the records:
    while ($row = $r->fetch_object()) {
        echo '<tr><td><img class="small-custom-image" src="' . BASE_URL . 'uploads/' . $row->cover_image_name . '"></td><td align="left">' . $row->product_title . '</td><td align="left">$' . number_format($row->price, 2) . '</td><td align="center">' . $row->stock_quantity . '</td><td align="center">' . $row->total_sales . '</td><td align="left">' . $row->dr . '</td>
		<td align="left" colspan="2"><button class="btn btn-primary custom-grid-button" onclick="editProductClicked(' . $row->product_id . ')">Edit</button><button class="btn btn-danger custom-grid-button" onclick="deleteProductClicked(' . $row->product_id . ')">Delete</button></td></tr>
		';
    }

    echo '</tbody></table></div>'; // Close the table.

    $r->free(); // Free up the resources.
    unset($r);
} else { // If no records were returned.

    echo '<p class="error">There are currently no products.</p>';
}

// Close the database connection.
mysqli_close($dbc);
unset($mysqli);
?>
<script>
    function deleteProductClicked(productID) {
        if (confirm('Are you sure you want to delete this product?')) {
            window.location = "<?php $baseUrl = BASE_URL;
                                echo $baseUrl . "manage_users/delete_product.php?id=" ?>" + productID;
        }
    }

    function editProductClicked(productID) {
        window.location = "<?php $baseUrl = BASE_URL;
                            echo $baseUrl . "manage_product/edit_product.php?id=" ?>" + productID;
    }
</script>
<?php
include('../includes/footer.html');
?>
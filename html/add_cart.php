<?php

$page_title = "Add to cart";

include("includes/header.html");
require('includes/config.inc.php');
include("includes/navigation_bar.html");

echo '<div class="container d-flex custom-notify-container">';

if (
    isset($_GET['productID']) && filter_var($_GET['productID'], FILTER_VALIDATE_INT, array('min-range' => 1))
    && isset($_GET['qty']) && filter_var($_GET['qty'], FILTER_VALIDATE_INT, array('min-range' => 1)) && isset($_SESSION['user_id'])
) {
    $productID = $_GET['productID'];
    $userID = $_SESSION['user_id'];
    $orderLineID = null;
    $orderID = null;
    $productQty = $_GET['qty'];
    $transactionSuccess = true;
    require(MYSQL);
    //Make sure the quantity is available and product exists
    $q = "  SELECT 
                stock_quantity
            FROM products
            WHERE product_id = $productID
            LIMIT 1";

    $r = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

    if (@mysqli_num_rows($r) != 1) {
        echo '<h3>Could not find product to add to cart!</h3>';
        $productID = null;
    } else {

        echo 'entered';

        //Check if the cart does not already exists
        $q = "  SELECT order_id 
                FROM orders 
                WHERE   user_id         = $userID 
                AND     order_status    = 'open'
                LIMIT 1";

        $r =  mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

        if (@mysqli_num_rows($r) == 1) {
            $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
            $orderID = $row['order_id'];
        }

        if ($orderID != null) {
            //Check if product is already on the order
            $q = "  SELECT
                    order_line_id
                ,   product_qty 
            FROM    order_lines
            WHERE   order_id    = $orderID
            AND     product_id  = $productID
            LIMIT 1";

            $r = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

            if (@mysqli_num_rows($r) == 1) {
                $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
                $orderLineID = $row['order_line_id'];
                $productQty = $row['product_qty'] + $_GET['qty'];
            }
        }

        //Begin Transaction
        mysqli_begin_transaction($dbc);
        //Add order if there is no open order existing
        if ($orderID == null) {
            $q = "INSERT INTO orders (user_id, created_by_user_id) VALUES($userID, $userID)";

            $r = mysqli_query($dbc, $q);

            if (mysqli_affected_rows($dbc) == 1) {
                $orderID = mysqli_insert_id($dbc);
            } else {
                $transactionSuccess = false;
            }
        }

        //Insert a new Order line if it does not exist
        if ($orderLineID == null) {
            $q = "INSERT INTO order_lines (product_id, order_id, product_qty) VALUES($productID, $orderID, $productQty)";
            $r = mysqli_query($dbc, $q);

            //Make sure row inserted
            if (@mysqli_affected_rows($dbc) != 1) {
                $transactionSuccess = false;
            }
        } else {
            $q = "UPDATE order_lines SET product_qty = $productQty WHERE order_line_id = $orderLineID";

            $r = mysqli_query($dbc, $q);

            //Make sure row changed
            if (@mysqli_affected_rows($dbc) != 1) {
                $transactionSuccess = false;
            }
        }

        //Make sure transaction is successful 
        if ($transactionSuccess) {
            mysqli_commit($dbc);
            echo '<h3>Product successfully added to cart!</h3>';
        } else {
            mysqli_rollback($dbc);
            echo '<h3>Product not successfully added to cart!</h3>';
        }


        // Close the database connection.
        mysqli_close($dbc);
        unset($mysqli);
        // Redirect the user:
        //  $url = BASE_URL . '/view_product.php?id=' . $productID; // Define the URL.
        //  ob_end_clean(); // Delete the buffer.
        // header("Location: $url");
        //exit(); // Quit the script.
    }
} else {
    echo '<h3>There was an issue. We are sorry for the inconvenience!</h3>';
    // Redirect the user:
    //$url = BASE_URL . '/index.php'; // Define the URL.
    //ob_end_clean(); // Delete the buffer.
    //header("Location: $url");
    //exit(); // Quit the script.
}

$okayButton = '<a href="' . BASE_URL;

if (isset($productID)) {
    $okayButton = $okayButton . 'view_product.php?id=' . $productID;
} else {
    $okayButton = $okayButton . 'index.php';
}

$okayButton = $okayButton . '" class="btn btn-primary btn-lg custom-notify-container-button">Ok</a></div>';
echo $okayButton;

include('includes/footer.html');

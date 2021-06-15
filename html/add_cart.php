<?php

$page_title = "Add to cart";

include("includes/header.html");

if (
    isset($_GET['productID']) && filter_var($_GET['productID'], FILTER_VALIDATE_INT, array('min-range' => 1))
    && isset($_GET['qty']) && filter_var($_GET['qty'], FILTER_VALIDATE_INT, array('min-range' => 1))
) {
    $productID = $_GET['productID'];
    $userID = $_GET['user_id'];
    $orderLineID = null;
    $orderID = null;
    $productQty = null;
    $transactionSuccess = true;

    require(MYSQL);
    //Check if the cart does not already exists
    $q = "  SELECT order_id 
            FROM order 
            WHERE   user_id         = $userID 
            AND     order_status    = 'open'
            LIMIT 1";

    $r =  mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

    if (@mysqli_num_rows($r) == 1) {
        $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
        $orderID = $row['order_id'];
    }


    //Check if product is already on the order
    $q = "  SELECT
                    order_line_id
                ,   product_qty 
            FROM    order_line
            WHERE   order_id    = $orderID
            AND     product_id  = $productID
            LIMIT 1";

    $r = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

    if (@mysqli_num_rows($r) == 1) {
        $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
        $orderLineID = $row['order_line_id'];
        $productQty = $row['product_qty'] + $_GET['qty'];
    }

    //Begin Transaction
    mysqli_begin_transaction($dbc);
    //Add order if there is no open order existing
    if ($orderID == null) {
        $q = "INSERT INTO order(user_id, created_by_user_id) VALUES($userID, $userID)";

        $r = mysqli_query($dbc, $q);

        if (mysqli_affected_rows($dbc) == 1) {
            $orderID = mysqli_insert_id($dbc);
        } else {
            $transactionSuccess = false;
        }
    }

    //Insert a new Order line if it does not exist
    if ($orderLineID == null) {
        $q = "INSERT INTO order_line(product_id, order_id, product_qty) VALUES($productID, $orderID, $productQty)";
        $r = mysqli_query($dbc, $q);

        //Make sure row inserted
        if (@mysqli_affected_rows($dbc) != 1) {
            $transactionSuccess = false;
        }
    } else {
        $q = "UPDATE order_line SET product_qty = $productQty WHERE order_line_id = $orderLineID";

        $r = mysqli_query($dbc, $q);

        //Make sure row changed
        if (@mysqli_affected_rows($dbc) != 1) {
            $transactionSuccess = false;
        }
    }

    //Make sure transaction is successful 
    if ($transactionSuccess) {
        mysqli_commit($dbc);
    } else {
        mysqli_rollback($dbc);
    }
    //Get the new order id 

    if (isset($_SESSION['cart']['productID'])) {
        $_SESSION['cart']['productID']['qty'];
    }
} else {
}

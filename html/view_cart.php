<?php # Script 19.11 - checkout.php
// This page inserts the order

// This page assumes that the billing


// Set the page title and include the HTML header:
$page_title = 'Order Confirmation';
require('includes/config.inc.php');
include('includes/header.html');
include('includes/navigation_bar.html');
// Assume that the customer is logged
$cid = 1; // Temporary.
require(MYSQL); //
// Assume that this page receives the
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //Trim all incomming data
    //$trimmed = array_map('trim', $_POST);

    echo '<div class="container" style="margin-top: 3rem; text-align: center;">';

    $userID = $orderID = $postType = $userOrder = false;
    $transactionValid = true; //Keep track of the transaction
    if (isset($_SESSION['user_id'])) {
        $userID = $_SESSION['user_id'];
    }

    //Get the order id
    if (isset($_POST['orderId']) && filter_var($_POST['orderId'], FILTER_VALIDATE_INT, array('min-range' => 1))) {
        $orderID =  $_POST['orderId'];
    }

    //Get the post type
    if (isset($_POST['formSubmitType']) && ($_POST['formSubmitType'] == 'checkout' || $_POST['formSubmitType'] == 'saveCart')) {
        $postType = mysqli_real_escape_string($dbc, $_POST['formSubmitType']);
    }


    if ($orderID && $userID) {
        //Make sure user is owner of order and order is open
        $q = "  SELECT
                order_id
            FROM orders
            WHERE   order_id        = $orderID
            AND     user_id         = $userID
            AND     order_status    = 'open'";

        $r = mysqli_query($dbc, $q);

        if (mysqli_num_rows($r) == 1) {
            $userOrder = true;
        }
    }

    //Make sure validation is okay
    if ($orderID && $postType && $userID && $userOrder) {
        if ($postType == 'checkout') {
            // Turn autocommit off:
            mysqli_autocommit($dbc, FALSE);
            //BEGIN transaction
            mysqli_begin_transaction($dbc);

            // Set order to 
            $q = "  UPDATE orders
                SET
                    order_status = 'checkedout'
                WHERE order_id = $orderID";
            $r = mysqli_query($dbc, $q);
            if (mysqli_affected_rows($dbc) == 1) {

                // Insert the specific order contents

                // Prepare the query:
                $q = "  UPDATE      order_lines ol
                        INNER JOIN  products pd ON pd.product_id = ol.product_id
                        SET
                                ol.product_qty      = ?
                            ,   ol.product_price    = pd.price
                            ,   pd.stock_quantity   = pd.stock_quantity - ?
                        WHERE   ol.order_id         = ?
                        AND     ol.order_line_id    = ?";

                $stmt = mysqli_prepare($dbc, $q);
                mysqli_stmt_bind_param(
                    $stmt,
                    'iiii',
                    $productQty,
                    $productQty,
                    $orderID,
                    $orderLineID
                );

                // Execute each query; count the


                foreach ($_POST['qty'] as $key => $item) {
                    $orderLineID = $productQty = false;

                    if (filter_var($item, FILTER_VALIDATE_INT, array('min-range' => 0)) || $item == 0) {
                        $productQty = (int)$item;
                    }

                    if (filter_var($key, FILTER_VALIDATE_INT, array('min-range' => 0))) {
                        $orderLineID = $key;
                    }

                    //Make sure values are valid
                    if ($productQty && $orderLineID || $productQty === 0) {
                        if ($productQty > 0) {
                            mysqli_stmt_execute($stmt);

                            if (mysqli_stmt_affected_rows($stmt) != 2) {
                                $transactionValid = false;
                            }
                            //Remove order lines with no qty
                        } else {
                            $q = "  DELETE 
                                FROM order_lines 
                                WHERE   order_id        = $orderID
                                AND     order_line_id   = $orderLineID";
                            $r = mysqli_query($dbc, $q);
                            if (mysqli_affected_rows($dbc) != 1) {
                                $transactionValid = false;
                            }
                        }
                    } else {
                        $transactionValid = false;
                    }
                }

                // Close this prepared statement:
                mysqli_stmt_close($stmt);

                // Make sure all of the transactions are successful
                if ($transactionValid) { // Whohoo!

                    // Commit the transaction:
                    mysqli_commit($dbc);;

                    // Message to the customer:
                    echo '
                    <div class="row d-flex justify-content-center custom-error-div">
                        <h2>Thank you for your order.You will be notified when the items ship.</h2>
                    </div>
                    <div class="row d-flex justify-content-center">
                        <div class="col-md-8 col-xl-6">
                            <a class="btn btn-primary btn-block" href="' . BASE_URL . 'index.php">OK</a>
                        </div>
                    </div>';
                    // Send emails and do whatever else.

                } else { // Rollback and report the

                    mysqli_rollback($dbc);
                    echo '
                    <div class="row d-flex justify-content-center custom-error-div">
                        <p>Your order could not be
                        processed due to a system error. You will be contacted in order
                        to have the problem fixed. We apologize for the inconvenience.
                        </ p>
                    </div>
                    <div class="row d-flex justify-content-center">
                        <div class="col-md-8 col-xl-6">
                            <a class="btn btn-primary btn-block" href="' . BASE_URL . 'index.php">OK</a>
                        </div>
                    </div>';
                    // Send the order information to


                }
            } else { // Rollback and report the problem.

                mysqli_rollback($dbc);

                echo '<div class="row d-flex justify-content-center custom-error-div">
                <p>Your order could not be
                processed due to a system error. You will be contacted in order to have the problem fixed. We apologize for the inconvenience.</p>
                </div>
                <div class="row d-flex justify-content-center">
                    <div class="col-md-8 col-xl-6">
                        <a class="btn btn-primary btn-block" href="' . BASE_URL . 'index.php">OK</a>
                    </div>
                </div>';

                // Send the order information to the


            }
            //When the user just wants to save the cart
        } else {


            // Turn autocommit off:
            mysqli_autocommit($dbc, FALSE);
            //BEGIN transaction
            mysqli_begin_transaction($dbc);

            // Prepare the query:
            $q = "  UPDATE  order_lines ol
            SET
                    ol.product_qty      = ?
            WHERE   ol.order_id         = ?
            AND     ol.order_line_id    = ?";

            $stmt = mysqli_prepare($dbc, $q);
            mysqli_stmt_bind_param(
                $stmt,
                'iii',
                $productQty,
                $orderID,
                $orderLineID
            );

            // Execute each query; count the


            foreach ($_POST['qty'] as $key => $item) {
                $orderLineID = $productQty = false;

                if (filter_var($item, FILTER_VALIDATE_INT, array('min-range' => 1)) || $item == 0) {
                    $productQty = (int)$item;
                }

                if (filter_var($key, FILTER_VALIDATE_INT, array('min-range' => 1))) {
                    $orderLineID = $key;
                }
                //Make sure values are valid
                if ($productQty && $orderLineID || $productQty === 0) {
                    //Make sure there productQty is new
                    $q = "  SELECT 
                        order_line_id
                    FROM  order_lines
                    WHERE   order_id        = $orderID
                    AND     order_line_id   = $orderLineID
                    AND     product_qty     <> $productQty";

                    $r = mysqli_query($dbc, $q);

                    if (mysqli_num_rows($r) > 0) {
                        //Remove items that are 0 qty
                        if ($item > 0) {
                            mysqli_stmt_execute($stmt);
                            if (mysqli_stmt_affected_rows($stmt) != 1) {
                                $transactionValid = false;
                            }
                        } else {
                            $q = "  DELETE 
                            FROM order_lines 
                            WHERE   order_id        = $orderID
                            AND     order_line_id   = $orderLineID";
                            $r = mysqli_query($dbc, $q);
                            if (mysqli_affected_rows($dbc) != 1) {
                                $transactionValid = false;
                            }
                        }
                    }
                } else {
                    $transactionValid = false;
                }
            }

            // Close this prepared statement:
            mysqli_stmt_close($stmt);

            // Make sure all of the transactions are successful
            if ($transactionValid) { // Whohoo!

                // Commit the transaction:
                mysqli_commit($dbc);;

                // Message to the customer:
                echo '
                <div class="row d-flex justify-content-center custom-error-div">
                    <h3>Your cart has been saved.</h3>
                </div>
                <div class="row d-flex justify-content-center">
                        <div class="col-md-8 col-xl-6">
                            <a class="btn btn-primary btn-block" href="' . BASE_URL . 'index.php">OK</a>
                        </div>
                    </div>';
                // Send emails and do whatever else.

            } else { // Rollback and report the

                mysqli_rollback($dbc);
                echo '
                <div class="row d-flex justify-content-center custom-error-div">
                <p>Your cart could not be
             saved due to a system error. You will be contacted in order
             to have the problem fixed. We apologize for the inconvenience.</ p>
             </div>
             <div class="row d-flex justify-content-center">
             <div class="col-md-8 col-xl-6">
                 <a class="btn btn-primary btn-block" href="' . BASE_URL . 'index.php">OK</a>
             </div>
         </div>';
                // Send the order information to


            }
        }


        //Close the connection
        mysqli_close($dbc);
    } else {
        echo '
        <div class="row d-flex justify-content-center custom-error-div">
            <h3>There was an issue when trying to save/checkout a cart. Please try again.<h3>
        </div>
        <div class="row d-flex justify-content-center">
        <div class="col-md-8 col-xl-6">
            <a class="btn btn-primary btn-block" href="' . BASE_URL . 'index.php">OK</a>
        </div>
    </div>';
    }
    echo '</div>';
} else {
    //Only logged in users can view the cart
    if (!isset($_SESSION['user_id'])) {
        $url = BASE_URL . 'index.php'; // Define the URL.
        ob_end_clean(); // Delete the buffer.
        header("Location: $url");
        exit(); // Quit the script.
    }

    $userID = $_SESSION['user_id'];

    $q = "  SELECT 
                    pd.product_id
                ,   pd.product_title 
                ,   pd.stock_quantity        AS remaining_stock
                ,   ol.order_line_id
                ,   od.order_id
                ,   ol.product_qty      AS order_line_qty
                ,   pd.price            AS current_product_price
                ,   (
                        SELECT
                            ig.image_name
                        FROM product_images pi
                        INNER JOIN images ig ON ig.image_id = pi.image_id
                        WHERE 	pi.product_id = pd.product_id
                        AND		pi.cover_image = 1	
                        LIMIT 1
                    )                   AS cover_image_name
            FROM        order_lines     ol
            INNER JOIN  orders          od  ON od.order_id      = ol.order_id
            INNER JOIN  products        pd  ON pd.product_id    = ol.product_id
            WHERE   od.order_status = 'open'
            AND     od.user_id      = $userID
            ORDER BY pd.product_title";

    $r = mysqli_query($dbc, $q);
    $num = $r->num_rows;
    if ($num > 0) {

        echo '<form id="cartForm" action="view_cart.php" method="post">
                <div class="table-responsive container">
                    <table class="table" width="60%">
                        <thead>
                            <tr>
                                <td></td>
                                <td align="left"><strong>Product</strong></td>
                                <td align="left"><strong>Price</strong></td>
                                <td align="left"><strong>Product Quantity</strong></td>
                                <td align="left"><strong>Total Price</strong></td>
                            </tr>
                        </thead>
                        <tbody>';

        $totalPrice = 0;
        $orderID = null;
        while ($row = $r->fetch_object()) {

            $orderLineQty = $row->order_line_qty;
            $currentPrice = $row->current_product_price;
            $subTotal = $currentPrice * $orderLineQty;
            $totalPrice += $subTotal;
            $orderID = $row->order_id;
            echo "<tr>
                        <td><img class=\"small-custom-image\" src=\"" . BASE_URL . "uploads/" . $row->cover_image_name . "\"></td>
                        <td align=\"left\">{$row->product_title}</td>
                        <td align=\"left\" name=\"currentPrice{$row->order_line_id}\">$" . number_format($row->current_product_price, 2) . "</td>
                        <td align=\"center\"><div class=\"form-group\"><input class=\"form-control\" onchange=\"recalculateTotal(this.name, this.value)\" type=\"number\" min=\"0\" max=\"" . $row->remaining_stock . "\" size=\"3\" name=\"qty[{$row->order_line_id}]\"
                        value=\"{$row->order_line_qty}\" /><small>Set to 0 will remove product from cart.</small></div></td>
                        <td align=\"right\"><b name=\"totalPrice{$row->order_line_id}\">$" . number_format($subTotal, 2) . "</b></td>
                    </tr>\n";
        }

        echo '</tbody></table>
            <div class="row d-flex justify-content-center">
                <input type="hidden" id="orderId" name="orderId" value="' . $orderID . '">
                <input type="hidden" id="formSubmitType" name="formSubmitType" value="checkout">
                <div class="col-md-8 col-lg-6 col-xl-4">
                    <button type="button" onclick="saveCart()" class="btn btn-success btn-lg btn-block" ><i class="far fa-save"></i>  Save Cart</button>
                </div>
                <div class="col-md-8 col-lg-6 col-xl-4">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" >Checkout!</button>
                </div>
            </div>
        </div>
        </form>'; // Close the table.
        echo '  <script>
                    function recalculateTotal (name, value) {
     
                        var splitName = name.split("[");
                        var id = splitName[1].substring(0,1);
                        var totalQtyName = "totalPrice" + id;
                        var totalQtyElements = document.getElementsByName(totalQtyName);
                        var totalQtyElement;
                        if(totalQtyElements.length == 1){
                            totalQtyElement = totalQtyElements[0];
                        }
                        var currentPriceName = "currentPrice" + id
                        var CurrentPriceElements = document.getElementsByName(currentPriceName);
                        if(CurrentPriceElements.length == 1){
                            var currentPrice = CurrentPriceElements[0].innerHTML.replace("$", "");
                            currentPrice = currentPrice.replace(",", "");
                            var newTotal = parseFloat(currentPrice) * parseInt(value);
                            totalQtyElement.innerHTML = "$" + newTotal.toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        }
                    }

                    function saveCart() {
                        document.getElementById("formSubmitType").value = "saveCart";
                        document.getElementById("cartForm").submit();
                    }
                </script>';
        $r->free(); // Free up the resources.
        unset($r);
    } else {
        echo '  <div class="container">
                <div class="row d-flex justify-content-center custom-error-div">
                    <h1>Your cart is Empty.</h1>
                </div>
            </div>';
    }
}
include('includes/footer.html');

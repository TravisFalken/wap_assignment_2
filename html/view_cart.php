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
    $trimmed = array_map('trim', $_POST);

    $userID = $orderID = $postType = $userOrder = false;
    $transactionValid = true; //Keep track of the transaction
    if (isset($_SESSION['user_id'])) {
        $userID = $_SESSION['user_id'];
    }

    //Get the order id
    if (isset($trimmed['orderID']) && filter_var($_GET['orderID'], FILTER_VALIDATE_INT, array('min-range' => 1))) {
        $orderID =  mysqli_real_escape_string($dbc, $trimmed['orderID']);
    }

    //Get the post type
    if (isset($trimmed['formSubmitType']) && ($trimmed['formSubmitType'] == 'checkout' || $trimmed['formSubmitType'] == 'saveChart')) {
        $postType = $trimmed['formSubmitType'];
    }

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

    //Make sure validation is okay
    if ($orderID && $postType && $userID && $userOrder) {
        echo $_POST['qty']['1'];
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
            $q = "  UPDATE ol
                    SET
                            product_qty = ?
                        ,   product_price = pd.price
                    FROM    order_lines ol
                    INNER JOIN products pd ON pd.product_id = ol.product_id
                    WHERE   ol.order_id         = ?
                    AND     ol.product_id       = ?
                    AND     ol.order_line_id    = ?";

            $stmt = mysqli_prepare($dbc, $q);
            mysqli_stmt_bind_param(
                $stmt,
                'iiii',
                $productQty,
                $orderID,
                $productID,
                $orderLineID
            );

            // Execute each query; count the

            $affected = 0;
            foreach ($_Get['qty'] as $key => $item) {
                $orderLineID = $key;
                $productQty = $item;
                mysqli_stmt_execute($stmt);
                $affected += mysqli_stmt_affected_rows($stmt);
            }

            // Close this prepared statement:
            mysqli_stmt_close($stmt);

            // Report on the success....
            if ($affected == count($_SESSION['cart'])) { // Whohoo!

                // Commit the transaction:
                mysqli_commit($dbc);

                // Clear the cart:
                unset($_SESSION['cart']);

                // Message to the customer:
                echo '<p>Thank you for your order.You will be notified when the items ship.</p>';
                // Send emails and do whatever else.

            } else { // Rollback and report the

                mysqli_rollback($dbc);
                echo '<p>Your order could not be
                    processed due to a system error. You will be contacted in order
                    to have the problem fixed. We apologize for the inconvenience.</ p>';
                // Send the order information to


            }
        } else { // Rollback and report the problem.

            mysqli_rollback($dbc);

            echo '<p>Your order could not be
                processed due to a system error. You will be contacted in order to have the problem fixed. We apologize for the inconvenience.</p>';

            // Send the order information to the


        }

        mysqli_close($dbc);
    } else {
        echo "<p>There was an issue when trying to save/checkout a cart. Please try again.<p>";
    }
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
                        <td align=\"center\"><input class=\"form-control\" onchange=\"recalculateTotal(this.name, this.value)\" type=\"number\" min=\"0\" max=\"" . $row->remaining_stock . "\" size=\"3\" name=\"qty[{$row->order_line_id}]\"
                        value=\"{$row->order_line_qty}\" /></td>
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
    }
}
include('includes/footer.html');

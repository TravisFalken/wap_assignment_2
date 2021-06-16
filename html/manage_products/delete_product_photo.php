<?php
$page_title = "Delete Product Image";
include('../includes/header.html');
require('../includes/config.inc.php');
include('../includes.navigation_bar.html');

if ($_SESSION['user_level'] == 1) {

    //Connect to the database
    require('../../mysqli_connect.php');

    if (isset($_GET['id'])) {
        $productIDToDelete = mysqli_real_escape_string($dbc, $_GET['id']);
    } else {

        //REDIRECT the id is wrong
        $url = BASE_URL . 'index.php'; // Define the URL.
        ob_end_clean(); // Delete the buffer.
        header("Location: $url");
        exit(); // Quit the script.

    }
    $transactionSuccess = true;

    //If there is an existing photo delete it
    $q = "  SELECT
                    product_image_id
                ,   ig.image_id
                ,   ig.image_name
            FROM product_images pi
            INNER JOIN  images  ig ON ig.image_id = pi.image_id
            WHERE   pi.product_id   = $productIDToDelete
            AND     pi.cover_image  = 1
            LIMIT 1";

    $r = mysqli_query($dbc, $q);

    //Only delete if image exists
    if (mysqli_num_rows($r) == 1) {
        $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
        $imageID = $row['image_id'];
        $productImageID = $row['product_image_id'];
        $oldImageName = $row['image_name'];

        //Begin Transaction
        mysqli_begin_transaction($dbc);

        //Delete from product images table
        $q = " DELETE FROM product_images WHERE product_image_id = $productImageID";

        $r = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

        if (mysqli_affected_rows($dbc) != 1) {
            $transactionSuccess = false;
        }

        //Delete from images table
        $q = "DELETE FROM images WHERE image_id = $imageID";

        $r = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

        if (mysqli_affected_rows($dbc) != 1) {
            $transactionSuccess = false;
        }

        //Remove  image
        if ($transactionSuccess) {
            $filePath = '../uploads/' . $oldImageName;
            if (!unlink($filePath)) {
                $transactionSuccess = false;
            }
        }
        //Check if transaction is ok
        if ($transactionSuccess) {
            mysqli_commit($dbc);
            //REDIRECT the user
            mysqli_close($dbc);
            $url = BASE_URL . 'manage_products/edit_product.php?id=' . $productIDToDelete; // Define the URL.
            ob_end_clean(); // Delete the buffer.
            header("Location: $url");
            exit(); // Quit the script.
        } else {
            mysqli_rollback($dbc);
            mysqli_close($dbc);
            echo '<p class="error">The product Image could not be deleted due to a system error.</p>'; // Public message.
        }
    } else {
        echo '<p class="error">The product image does not exist.</p>'; // Public message.
    }

    if (mysqli_affected_rows($dbc) == 1) { // If it ran OK.

        $url = BASE_URL . '/manage_products/manage_product.php'; // Define the URL.
        ob_end_clean(); // Delete the buffer.
        header("Location: $url");
        exit(); // Quit the script.
    } else { // If the query did not run OK.
        echo '<p class="error">The product could not be deleted due to a system error.</p>'; // Public message.
        echo '<p>' . mysqli_error($dbc) . '<br>Query: ' . $q . '</p>'; // Debugging message.
    }
} else {

    //REDIRECT the user because they do not have permission
    $url = BASE_URL . 'index.php'; // Define the URL.
    ob_end_clean(); // Delete the buffer.
    header("Location: $url");
    exit(); // Quit the script.

}

include('../includes/footer.html');

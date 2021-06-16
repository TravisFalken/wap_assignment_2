<?php
$page_title = 'Add Product';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page

include('../includes/header.html');
require('../includes/config.inc.php');


$allowedFiles = ['image/pjpeg', 'image/jpeg', 'image/JPG', 'image/X-PNG', 'image/PNG', 'image/png', 'image/x-png'];

//Redirect user if they do not have access
if (isset($_SESSION['user_level']) && $_SESSION['user_level'] != 1 || !isset($_SESSION['user_level'])) {
    //REDIRECT the user because they do not have permission
    $url = BASE_URL . 'index.php'; // Define the URL.
    ob_end_clean(); // Delete the buffer.
    header("Location: $url");
    exit(); // Quit the script.
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //Get db connection:
    require('../../mysqli_connect.php');

    //Trim all incomming data
    $trimmed = array_map('trim', $_POST);


    $productTitle = $productDescription = $productPrice = $productSKU = $productQty = $profilePhoto = $profilePhotoUploadSuccess = $dbProductPhotoInsert = $dbPhotoInsert =  $profilePhotoNotUploaded = FALSE;

    if (isset($_FILES['profilePhoto']) && file_exists($_FILES['profilePhoto']['tmp_name']) && in_array($_FILES['profilePhoto']['type'], $allowedFiles)) {

        $fileinfo = finfo_open(FILEINFO_MIME_TYPE);

        if (in_array(finfo_file($fileinfo, $_FILES['profilePhoto']['tmp_name']), $allowedFiles)) {
            $profilePhoto = $_FILES['profilePhoto'];
        }

        // Close the resource:
        finfo_close($fileinfo);
    } else if (!isset($_FILES['profilePhoto'])) {
        $profilePhotoNotUploaded = true;
    }

    //Get the product title
    if (isset($trimmed['productTitle'])) {
        $productTitle = mysqli_real_escape_string($dbc, $trimmed['productTitle']);
    } else {
        echo '<p> Please enter the product title!</p>';
    }

    //Get product description
    if (isset($trimmed['productDesc'])) {
        $productDescription = mysqli_real_escape_string($dbc, $trimmed['productDesc']);
    } else {
        echo '<p> Please enter the product description!</p>';
    }

    //Get Product price
    if (isset($trimmed['productPrice']) && $trimmed['productPrice'] > 0) {
        $productPrice = $trimmed['productPrice'];
    } else {
        echo '<p>Please enter the product price!</p>';
    }

    //Get the product SKU
    if (preg_match('/^[A-Z 0-9 \'.-]{2,40}$/i', $trimmed['productSKU'])) {
        $productSKU = $trimmed['productSKU'];
    } else {
        echo '<p>Please enter the product SKU!</p>';
    }

    //Get product Qty
    if (isset($trimmed['productQty']) && $trimmed['productQty'] >= 0) {
        $productQty = $trimmed['productQty'];
    } else {
        echo '<p>Please enter the product Qty';
    }

    //Make sure everything is okay
    if ($productTitle && $productDescription && $productPrice && $productSKU && $productQty && ($profilePhoto || $profilePhotoNotUploaded)) {
        $createdUserID = $_SESSION['user_id'];
        if ($profilePhoto) {
            $newFileName = uniqid(rand(), true);
            $existingPhotoName = explode(".",  $profilePhoto['name']);
            $ext = end($existingPhotoName);
            $profilePhoto['name'] = $newFileName . '.' . $ext;
            $finalProfilePhotoName = $profilePhoto['name'];
        }
        //Begin Transaction
        mysqli_begin_transaction($dbc);

        $q = "  UPDATE products
                SET 
                    product_title       = $productTitle
                ,   product_description = $productDescription
                ,   price               = $productPrice
                ,   stock_quantity      = $productQty
                ,   sku                 = $productSKU
                WHERE product_id = $id";

        $res = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

        //Make sure row has been added
        echo 'First:' . mysqli_affected_rows($dbc) . '<br>';
        if (mysqli_affected_rows($dbc) == 1) {
            $InsertedProductId = mysqli_insert_id($dbc);
            //echo $InsertedProductId;

            $profilePhotoSize = $profilePhoto['size'];
            $profilePhotoImageType = $profilePhoto['type'];
            $q = "INSERT INTO images (image_title, image_size, image_type, image_name, created_by_user_id)
                    VALUES ('Profile Photo', $profilePhotoSize, '$profilePhotoImageType', '$finalProfilePhotoName', $createdUserID)";

            $res = mysqli_query($dbc, $q);
            echo 'Second:' . mysqli_affected_rows($dbc);
            if (mysqli_affected_rows($dbc) == 1) {
                $dbPhotoInsert = true;
            }

            $insertedImageID = mysqli_insert_id($dbc);

            $q = "INSERT INTO product_images (product_id, image_id, cover_image, created_by_user_id)
                    VALUES($InsertedProductId, $insertedImageID, 1, $createdUserID)";

            $res = mysqli_query($dbc, $q);
            echo 'Third:' . mysqli_affected_rows($dbc);
            if (mysqli_affected_rows($dbc) == 1) {
                $dbProductPhotoInsert = true;
            }
            if ($dbPhotoInsert && $dbPhotoInsert) {
                if (move_uploaded_file($profilePhoto['tmp_name'], "../uploads/{$profilePhoto['name']}")) {
                    $profilePhotoSuccess = true;
                }
            }

            if ($dbPhotoInsert && $profilePhotoSuccess && $dbProductPhotoInsert) {
                mysqli_commit($dbc);
                //REDIRECT the user because they do not have permission
                $url = BASE_URL . 'index.php'; // Define the URL.
                ob_end_clean(); // Delete the buffer.
                header("Location: $url");
                exit(); // Quit the script.
            }

            mysqli_rollback($dbc);
        } else {
            mysqli_rollback($dbc);
            echo '<p class="error">You could not be registered due to a system error. We apologize for any inconvenience.</p>';
        }
    }
    mysqli_close($dbc);
} else {
    //Connect to database
    require('../../mysqli_connect.php');

    $id = $_GET['id'];
    // Make the query:
    $q = "  SELECT 
            pd.product_id
            ,   pd.product_title
            ,   pd.product_description
            ,   pd.price
            ,   pd.stock_quantity
            ,   pd.sku
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

        include('../includes/navigation_bar.html');



        echo '  <div class="container">
                    <h1>Edit Product</h1>
                </div>
                <form enctype="multipart/form-data" action="add_product.php" method="post">
                    <div class="container">
                        <div class="row d-flex justify-content-center">
                            <div class="col-6-md d-flex custom-view-product-image-div">
                                <img class="custom-view-product-image" src="' . BASE_URL . 'uploads/' . $row['cover_image_name'] . '">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="profilePhoto">Select Profile Photo:</label>
                            <input type="file" class="form-control" name="profilePhoto" />
                        </div>
                        <div class="form-group">
                            <label for="first_name">Product Title:</label>
                            <input type="text" class="form-control" name="productTitle" size="20" maxlength="20" value="' . $row['product_title'] . '">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Product Description:</label>
                            <input type="text" class="form-control" name="productDesc" size="20" maxlength="40" value="' . $row['product_description'] . '">
                        </div>
                        <div class="form-group">
                            <label for="email">Product Price:</label>
                            <input type="number" class="form-control" name="productPrice" step="any" size="30" maxlength="60" value="' . $row['price'] . '">
                        </div>
                        <div class="form-group">
                            <label for="password1">Product SKU:</label>
                            <input type="text" class="form-control" name="productSKU" size="20" value="' . $row['sku'] . '">
                        </div>
                        <div class="form-group">
                            <label for="password2">Product Quantity:</label>
                            <input type="number" class="form-control" name="productQty" size="20" value="' . $row['stock_quantity'] . '">
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <button type="submit" class="btn btn-success btn-block"><i class="far fa-save"></i>  Save</button>
                                
                            </div>
                            <div class="col-sm-6">
                                <a class="btn btn-danger btn-block" href="' . BASE_URL . 'manage_products/manage_product.php' . '"><i class="fas fa-times"></i>  Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>';
    }
}
include('../includes/footer.html');

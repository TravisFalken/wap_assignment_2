<?php
$page_title = 'Edit Product';
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
//Get db connection:
require('../../mysqli_connect.php');
$errors = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {



    //Trim all incomming data
    $trimmed = array_map('trim', $_POST);


    $productTitle = $productDescription = $productPrice = $productSKU = $productQty = $productID = $profilePhoto = $profilePhotoUploadSuccess = $dbProductPhotoInsert = $dbPhotoInsert =  $profilePhotoNotUploaded = FALSE;
    $transactionSuccess = true;

    if (isset($_FILES['profilePhoto']['tmp_name']) && file_exists($_FILES['profilePhoto']['tmp_name'])) {
        if (in_array($_FILES['profilePhoto']['type'], $allowedFiles)) {

            $fileinfo = finfo_open(FILEINFO_MIME_TYPE);

            if (in_array(finfo_file($fileinfo, $_FILES['profilePhoto']['tmp_name']), $allowedFiles)) {
                $profilePhoto = $_FILES['profilePhoto'];
            } else {
                $errors['profilePhoto'] = true;
            }

            // Close the resource:
            finfo_close($fileinfo);
        } else {
            $errors['profilePhoto'] = true;
        }
    } else {
        $profilePhotoNotUploaded = true;
    }

    echo $_FILES['profilePhoto']['tmp_name'];

    //Get the product title
    if (empty($trimmed['productTitle'])) {
        $errors['productTitle'] = true;
        echo '<p> Please enter the product title!</p>';
    } else {
        $productTitle = mysqli_real_escape_string($dbc, $trimmed['productTitle']);
    }

    //Get product description
    if (empty($trimmed['productDesc'])) {
        $errors['productDesc'] = true;
    } else {
        $productDescription = mysqli_real_escape_string($dbc, $trimmed['productDesc']);
    }

    //Get Product price
    if (isset($trimmed['productPrice']) && $trimmed['productPrice'] > 0) {
        $productPrice = $trimmed['productPrice'];
    } else {
        $errors['productPrice'] = true;
        echo '<p>Please enter the product price!</p>';
    }

    //Get the product SKU
    if (preg_match('/^[A-Z 0-9 \'.-]{2,40}$/i', $trimmed['productSKU'])) {
        $productSKU = $trimmed['productSKU'];
    } else {
        $errors['productSKU'] = true;
        echo '<p>Please enter the product SKU!</p>';
    }

    //Get product Qty
    if (isset($trimmed['productQty']) && $trimmed['productQty'] >= 0) {
        $productQty = $trimmed['productQty'];
    } else {
        $errors['productQty'] = true;
        echo '<p>Please enter the product Qty';
    }

    //Get product Id
    if (isset($trimmed['productId']) && filter_var($trimmed['productId'], FILTER_VALIDATE_INT, array('min-range' => 1))) {
        $productID =  $trimmed['productId'];
    } else {
        $errors['system'] = true;
    }

    //Make sure everything is okay
    if ($productID && $productTitle && $productDescription && $productPrice && $productSKU && $productQty && ($profilePhoto || $profilePhotoNotUploaded)) {
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

        //Make sure new values are being added
        $q = "  SELECT
                    product_id
                FROM products
                WHERE   product_id              = $productID
                AND     (product_title           <> '$productTitle'
                            OR      product_description     <> '$productDescription'
                            OR      price                   <> $productPrice
                            OR      stock_quantity          <> $productQty
                            OR      sku                     <> '$productSKU'
                        )
                ";

        $r = mysqli_query($dbc, $q);
        echo 'Rows:' . mysqli_num_rows($r);
        if (mysqli_num_rows($r) > 0) {

            $q = "  UPDATE products
                SET 
                    product_title       = '$productTitle'
                ,   product_description = '$productDescription'
                ,   price               = $productPrice
                ,   stock_quantity      = $productQty
                ,   sku                 = '$productSKU'
                WHERE product_id = $productID";

            $res = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

            if (mysqli_affected_rows($dbc) < 1) {

                $transactionSuccess = false;
            }
        }
        //Make sure row has been added
        if ($transactionSuccess) {
            if ($profilePhoto) {
                $profilePhotoSize = $profilePhoto['size'];
                $profilePhotoImageType = $profilePhoto['type'];

                //If there is an existing photo delete it
                $q = "  SELECT
                                product_image_id
                            ,   ig.image_id
                            ,   ig.image_name
                        FROM product_images pi
                        INNER JOIN  images  ig ON ig.image_id = pi.image_id
                        WHERE   pi.product_id   = $productID
                        AND     pi.cover_image  = 1
                        LIMIT 1";

                $r = mysqli_query($dbc, $q);

                //Only delete if image exists
                if (mysqli_num_rows($r) == 1) {
                    $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
                    $imageID = $row['image_id'];
                    $productImageID = $row['product_image_id'];
                    $oldImageName = $row['image_name'];

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
                }


                //Insert into image table
                $q = "INSERT INTO images (image_title, image_size, image_type, image_name, created_by_user_id)
                    VALUES ('Profile Photo', $profilePhotoSize, '$profilePhotoImageType', '$finalProfilePhotoName', $createdUserID)";

                $res = mysqli_query($dbc, $q);
                if (mysqli_affected_rows($dbc) != 1) {
                    $transactionSuccess = false;
                }

                $insertedImageID = mysqli_insert_id($dbc);
                //Insert into the join table product images
                $q = "INSERT INTO product_images (product_id, image_id, cover_image, created_by_user_id)
                    VALUES($productID, $insertedImageID, 1, $createdUserID)";

                $res = mysqli_query($dbc, $q);
                if (mysqli_affected_rows($dbc) != 1) {
                    $transactionSuccess = false;
                }

                // Add new Image
                if ($transactionSuccess) {
                    if (!move_uploaded_file($profilePhoto['tmp_name'], "../uploads/{$profilePhoto['name']}")) {
                        $transactionSuccess = false;
                    }
                }
                //Remove old image
                if (isset($oldImageName) && $transactionSuccess) {
                    $filePath = '../uploads/' . $oldImageName;
                    if (!unlink($filePath)) {
                        $transactionSuccess = false;
                    }
                }
            }
            if ($transactionSuccess) {
                mysqli_commit($dbc);
                //REDIRECT the user
                mysqli_close($dbc);
                $url = BASE_URL . 'manage_products/manage_product.php'; // Define the URL.
                ob_end_clean(); // Delete the buffer.
                header("Location: $url");
                exit(); // Quit the script.
            } else {
                mysqli_rollback($dbc);
                echo '<p class="error">Could not edit the product due to a system error. We apologize for any inconvenience.</p>';
            }

            mysqli_rollback($dbc);
        } else {
            mysqli_rollback($dbc);
            //echo '<p class="error">You could not be edit the product due to a system error. We apologize for any inconvenience.</p>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $id = $_GET['id'];
} else {
    $id = $productID;
}

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



    echo '   <div class="row custom-back-button-div">
        <a href="manage_product.php" class="btn custom-back-button"><i class="fas fa-chevron-left"></i>  Back</a>
    </div>  <div class="container">
                    <h1>Edit Product</h1>
                </div>
                <form enctype="multipart/form-data" action="edit_product.php" method="post">
                    <div class="container">
                        <div class="row d-flex justify-content-center">
                            <div class="col-8-md d-flex custom-view-product-image-div">
                                <img class="custom-view-product-image" src="' . BASE_URL . 'uploads/' . $row['cover_image_name'] . '">
                            </div>
                 
                        </div>
                        <div class="row d-flex justify-content-center">
                        <div class="col-6-md">
                        ';
    echo (isset($row['cover_image_name'])) ?  '<a class="btn btn-danger btn-lg" href ="delete_product_photo.php?id=' . $row['product_id'] . '">Remove Photo</a>' : '';
    echo                '</div>
                        </div>
                        <div class="form-group">
                            <label for="profilePhoto">Select Profile Photo:</label>
                            <input type="file" class="form-control" name="profilePhoto" />
                            ' .  ((array_key_exists("profilePhoto", $errors)) ? '<small class="error">Please upload a valid file!</small>' : '') . '
                        </div>
                        <div class="form-group">
                            <label for="first_name">Product Title:</label>
                            <input type="text" class="form-control" name="productTitle" size="20" maxlength="20" value="' . ((isset($productTitle)) ? $productTitle : $row['product_title']) . '">
                            ' .  ((array_key_exists("productTitle", $errors)) ? '<small class="error">Please enter a valid product tile!</small>' : '') . '
                        </div>
                        <div class="form-group">
                            <label for="last_name">Product Description:</label>
                            <input type="text" class="form-control" name="productDesc" size="20" value="' . ((isset($productDescription)) ? $productDescription : $row['product_description']) . '">
                            ' .  ((array_key_exists("productDesc", $errors)) ? '<small class="error">Please enter a valid product description!</small>' : '') . '
                        </div>
                        <div class="form-group">
                            <label for="email">Product Price:</label>
                            <input type="number" class="form-control" name="productPrice" step="any" size="30" maxlength="60" value="' . ((isset($productPrice)) ? $productPrice : $row['price']) . '">
                            ' .  ((array_key_exists("productPrice", $errors)) ? '<small class="error">Please enter a valid product price!</small>' : '') . '
                        </div>
                        <div class="form-group">
                            <label for="password1">Product SKU:</label>
                            <input type="text" class="form-control" name="productSKU" size="20" value="' . ((isset($productSKU)) ? $productSKU : $row['sku']) . '">
                            ' .  ((array_key_exists("productSKU", $errors)) ? '<small class="error">Please enter a valid product SKU!</small>' : '') . '
                        </div>
                        <div class="form-group">
                            <label for="password2">Product Quantity:</label>
                            <input type="number" class="form-control" name="productQty" size="20" value="' . ((isset($productQty)) ? $productQty : $row['stock_quantity']) . '">
                            ' .  ((array_key_exists("productQty", $errors)) ? '<small class="error">Please enter a valid product quantity!</small>' : '') . '
                        </div>
                        <input type="hidden" name="productId" value="' . $row["product_id"] . '">
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <button type="submit" class="btn btn-success btn-block"><i class="far fa-save"></i>  Save</button>
                                
                            </div>
                            ' .  ((array_key_exists("system", $errors)) ? '<small class="error">The product could not be edited because of a system error. Please try again later.</small>' : '') . '
                            <div class="col-sm-6">
                                <a class="btn btn-danger btn-block" href="' . BASE_URL . 'manage_products/manage_product.php' . '"><i class="fas fa-times"></i>  Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>';
}

include('../includes/footer.html');

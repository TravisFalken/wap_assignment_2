<?php
$page_title = 'Add Product';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page

include('../includes/header.html');
require('../includes/config.inc.php');
include('../includes/navigation_bar.html');

$allowedFiles = ['image/pjpeg', 'image/jpeg', 'image/JPG', 'image/X-PNG', 'image/PNG', 'image/png', 'image/x-png'];

//Redirect user if they do not have access
if (isset($_SESSION['user_level']) && $_SESSION['user_level'] != 1 || !isset($_SESSION['user_level'])) {
    //REDIRECT the user because they do not have permission
    $url = BASE_URL . 'index.php'; // Define the URL.
    ob_end_clean(); // Delete the buffer.
    header("Location: $url");
    exit(); // Quit the script.
}
$errors = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //Get db connection:
    require('../../mysqli_connect.php');

    //Trim all incomming data
    $trimmed = array_map('trim', $_POST);


    $productTitle = $productDescription = $productPrice = $productSKU = $productQty = $profilePhoto = $profilePhotoNotUploaded = FALSE;

    $transactionSuccess = true;
    if (isset($_FILES['profilePhoto'])) {
        if (file_exists($_FILES['profilePhoto']['tmp_name'])) {
            if (in_array($_FILES['profilePhoto']['type'], $allowedFiles)) {

                $fileinfo = finfo_open(FILEINFO_MIME_TYPE);

                if (in_array(finfo_file($fileinfo, $_FILES['profilePhoto']['tmp_name']), $allowedFiles)) {
                    $profilePhoto = $_FILES['profilePhoto'];
                }

                // Close the resource:
                finfo_close($fileinfo);
            } else {
                $errors['profilePhoto'] = true;
            }
        } else {
            $profilePhotoNotUploaded = true;
        }
    } else {
        $profilePhotoNotUploaded = true;
    }

    //Get the product title
    if (empty($trimmed['productTitle'])) {
        $errors['productTitle'] = true;
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
    }

    //Get the product SKU
    if (preg_match('/^[A-Z 0-9 \'.-]{2,40}$/i', $trimmed['productSKU'])) {
        $productSKU = $trimmed['productSKU'];
    } else {
        $errors['productSKU'] = true;
    }

    //Get product Qty
    if (isset($trimmed['productQty']) && $trimmed['productQty'] > 0) {
        $productQty = $trimmed['productQty'];
    } else {
        $errors['productQty'] = true;
    }
    echo 'entered';
    //Make sure everything is okay
    if ($productTitle && $productDescription && $productPrice && $productSKU && $productQty && ($profilePhoto || $profilePhotoNotUploaded)) {
        $createdUserID = $_SESSION['user_id'];

        //Only create photo if there is one
        if ($profilePhoto) {
            $newFileName = uniqid(rand(), true);
            $existingPhotoName = explode(".",  $profilePhoto['name']);
            $ext = end($existingPhotoName);
            $profilePhoto['name'] = $newFileName . '.' . $ext;
            $finalProfilePhotoName = $profilePhoto['name'];
        }
        //Begin Transaction
        mysqli_begin_transaction($dbc);

        $q = "INSERT INTO products (product_title, product_description, price, stock_quantity, sku, created_by_user_id) 
                VALUES ('$productTitle', '$productDescription', $productPrice, $productQty, '$productSKU', $createdUserID)";

        $res = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

        //Make sure row has been added
        echo 'First:' . mysqli_affected_rows($dbc) . '<br>';
        if (mysqli_affected_rows($dbc) == 1) {
            $InsertedProductId = mysqli_insert_id($dbc);
            //echo $InsertedProductId;
            //Only insert if photo
            if ($profilePhoto) {
                $profilePhotoSize = $profilePhoto['size'];
                $profilePhotoImageType = $profilePhoto['type'];
                $q = "INSERT INTO images (image_title, image_size, image_type, image_name, created_by_user_id)
                    VALUES ('Profile Photo', $profilePhotoSize, '$profilePhotoImageType', '$finalProfilePhotoName', $createdUserID)";

                $res = mysqli_query($dbc, $q);
                echo 'Second:' . mysqli_affected_rows($dbc);
                if (mysqli_affected_rows($dbc) != 1) {
                    $transactionSuccess = false;
                }

                if ($transactionSuccess == true) {
                    $insertedImageID = mysqli_insert_id($dbc);

                    $q = "INSERT INTO product_images (product_id, image_id, cover_image, created_by_user_id)
                    VALUES($InsertedProductId, $insertedImageID, 1, $createdUserID)";

                    $res = mysqli_query($dbc, $q);
                    echo 'Third:' . mysqli_affected_rows($dbc);
                    if (mysqli_affected_rows($dbc) != 1) {
                        $transactionSuccess = false;
                    }

                    if ($transactionSuccess == true) {

                        if (!move_uploaded_file($profilePhoto['tmp_name'], "../uploads/{$profilePhoto['name']}")) {
                            $transactionSuccess = false;
                        }
                    }
                }
            }
            if ($transactionSuccess == true) {
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
            echo '<div class="container"><p class="error">You could not be registered due to a system error. We apologize for any inconvenience.</p></div>';
        }
    }
    mysqli_close($dbc);
}

?>
<div class="container">
    <h1>Add Product</h1>
</div>
<form enctype="multipart/form-data" action="add_product.php" method="post">
    <div class="container">
        <div class="form-group">
            <label for="profilePhoto">Select Profile Photo:</label>
            <input type="file" class="form-control" name="profilePhoto" />
            <?php echo (array_key_exists('profilePhoto', $errors)) ? '<small class="error">Please upload a valid file!</small>' : ''; ?>
        </div>
        <div class="form-group">
            <label for="productTitle">Product Title:</label>
            <input type="text" class="form-control" name="productTitle" size="20" maxlength="20" value="<?php if (isset($trimmed['productTitle'])) echo $trimmed['productTitle']; ?>">
            <?php echo (array_key_exists('productTitle', $errors)) ? '<small class="error">Please enter a valid product title!</small>' : ''; ?>
        </div>
        <div class="form-group">
            <label for="productDesc">Product Description:</label>
            <input type="text" class="form-control" name="productDesc" size="20" value="<?php if (isset($trimmed['productDesc'])) echo $trimmed['productDesc']; ?>">
            <?php echo (array_key_exists('productDesc', $errors)) ? '<small class="error">Please enter a valid product description!</small>' : ''; ?>
        </div>
        <div class="form-group">
            <label for="productPrice">Product Price:</label>
            <input type="number" class="form-control" name="productPrice" step="any" size="30" maxlength="60" value="<?php if (isset($trimmed['productPrice'])) echo $trimmed['productPrice']; ?>">
            <?php echo (array_key_exists('productPrice', $errors)) ? '<small class="error">Please enter a valid product price!</small>' : ''; ?>
        </div>
        <div class="form-group">
            <label for="productSKU">Product SKU:</label>
            <input type="text" class="form-control" name="productSKU" size="20" value="<?php if (isset($trimmed['productSKU'])) echo $trimmed['productSKU']; ?>">
            <?php echo (array_key_exists('productSKU', $errors)) ? '<small class="error">Please enter a valid product SKU!</small>' : ''; ?>
        </div>
        <div class="form-group">
            <label for="productQty">Product Quantity:</label>
            <input type="number" class="form-control" name="productQty" size="20" value="<?php if (isset($trimmed['productQty'])) echo $trimmed['productQty']; ?>">
            <?php echo (array_key_exists('productQty', $errors)) ? '<small class="error">Please enter a valid product quantity!</small>' : ''; ?>
        </div>
        <div class="form-group row">
            <div class="col-sm-6">
                <button type="submit" class="btn btn-primary btn-block">Add Product</button>
            </div>
        </div>
    </div>
</form>

<?php include('../includes/footer.html'); ?>
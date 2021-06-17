<?php # Script 10.3 - edit_user.php
// This page is for editing a user record.
// This page is accessed through view_users.php.

$page_title = 'Edit User';
include('../includes/header.html');
require('../includes/config.inc.php');
include('../includes/navigation_bar.html');
echo '<div class="container"><h1>Edit User</h1></div>';


// Check for a valid user ID, through GET or POST:
if ((isset($_GET['id'])) && (is_numeric($_GET['id']))) { // From view_users.php
    $id = $_GET['id'];
} elseif ((isset($_POST['id'])) && (is_numeric($_POST['id']))) { // Form submission.
    $id = $_POST['id'];
} else { // No valid ID, kill the script.
    echo '<p class="error">This page has been accessed in error.</p>';
    include('../includes/footer.html');
    exit();
}

//Only admin or actual user can access this form
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_level'])) {
    echo '<p class="error">This page has been accessed in error.</p>';
    include('../includes/footer.html');
    exit();
}

if ($_SESSION['user_level'] != 1 && $_SESSION['user_id'] != $id) {
    echo '<p class="error">This page has been accessed in error.</p>';
    include('../includes/footer.html');
    exit();
}



require('../../mysqli_connect.php');

// Check if the form has been submitted:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $errors = [];

    // Check for a first name:
    if (empty($_POST['first_name'])) {
        $errors[] = 'You forgot to enter your first name.';
    } else {
        $fn = mysqli_real_escape_string($dbc, trim($_POST['first_name']));
    }

    // Check for a last name:
    if (empty($_POST['last_name'])) {
        $errors[] = 'You forgot to enter your last name.';
    } else {
        $ln = mysqli_real_escape_string($dbc, trim($_POST['last_name']));
    }

    // Check for an email address:
    if (empty($_POST['email'])) {
        $errors[] = 'You forgot to enter your email address.';
    } else {
        $e = mysqli_real_escape_string($dbc, trim($_POST['email']));
    }

    if (empty($errors)) { // If everything's OK.

        //  Test for unique email address:
        $q = "SELECT user_id FROM users WHERE email='$e' AND user_id != $id";
        $r = @mysqli_query($dbc, $q);
        if (mysqli_num_rows($r) == 0) {

            //Make sure user details are all unique
            $q = "  SELECT
                        user_id 
                    FROM users 
                    WHERE   email       ='$e' 
                    AND     user_id     = $id
                    AND     first_name  = '$fn'
                    AND     last_name   = '$ln'";

            $r = mysqli_query($dbc, $q);

            if (mysqli_num_rows($r) > 0) {
                mysqli_close($dbc);

                // Redirect the user:
                $url = BASE_URL . 'index.php'; // Define the URL.
                ob_end_clean(); // Delete the buffer.
                header("Location: $url");
                exit(); // Quit the script.
            }

            // Make the query:
            $q = "UPDATE users SET first_name='$fn', last_name='$ln', email='$e' WHERE user_id=$id LIMIT 1";
            $r = @mysqli_query($dbc, $q);
            if (mysqli_affected_rows($dbc) == 1) { // If it ran OK.

                // Print a message:
                echo '<p>The user has been edited.</p>';
            } else { // If it did not run OK.
                echo '<p class="error">The user could not be edited due to a system error. We apologize for any inconvenience.</p>'; // Public message.
                echo '<p>' . mysqli_error($dbc) . '<br>Query: ' . $q . '</p>'; // Debugging message.
            }
        } else { // Already registered.
            echo '<p class="error">The email address has already been registered.</p>';
        }
    } else { // Report the errors.

        echo '<p class="error">The following error(s) occurred:<br>';
        foreach ($errors as $msg) { // Print each error.
            echo " - $msg<br>\n";
        }
        echo '</p><p>Please try again.</p>';
    } // End of if (empty($errors)) IF.

} // End of submit conditional.

// Always show the form...

// Retrieve the user's information:
$q = "SELECT first_name, last_name, email FROM users WHERE user_id=$id";
$r = @mysqli_query($dbc, $q);

if (mysqli_num_rows($r) == 1) { // Valid user ID, show the form.

    // Get the user's information:
    $row = mysqli_fetch_array($r, MYSQLI_NUM);

    // Create the form:
    echo '
    
    <form action="edit_user.php"  method="post">
        <div class="container">
            <div class="form-group">
                <label for="first_name">First Name:</label> 
                <input class="form-control" type="text" name="first_name" size="15" maxlength="15" value="' . $row[0] . '">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name: </label> 
                <input class="form-control" type="text" name="last_name" size="15" maxlength="30" value="' . $row[1] . '">
            </div>
            <div class="form-group">
                <label for="email">Email Address: </label>
                <input class="form-control" type="email" name="email" size="20" maxlength="60" value="' . $row[2] . '">
            </div>
            <div class="form-group row">
                <div class="col-6">
                    <button class="btn btn-success btn-block btn-lg" type="submit"><i class="far fa-save"></i>  Save</button>
                </div>
                <div class="col-6">
                    <button class="btn btn-primary btn-block btn-lg" onclick="cancelEdit()" type="button"><i class="fas fa-times"></i>  Cancel</button>
                </div>
            </div>
                <input type="hidden" name="id" value="' . $id . '">
        </div>
    </form>
    <script>
        function cancelEdit() {
            window.location = "' . BASE_URL . 'index.php";
        }
    </script>
    ';
} else { // Not a valid user ID.
    echo '<p class="error">This page has been accessed in error.</p>';
}

mysqli_close($dbc);

include('../includes/footer.html');

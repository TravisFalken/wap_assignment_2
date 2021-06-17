<?php
// Set the page title and include the HTML header:
$page_title = 'Edit Post';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
require('../includes/config.inc.php');
include('../includes/header.html');
include('../includes/navigation_bar.html');
// Retrieve all the messages in this forum...
require('../../mysqli_connect.php');

$errors = array();

$body = $threadID = $postID = false;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_level']) && $_SESSION['user_level'] == 1) { //Make sure there is a logged in user and admin
    if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Handle the form.

        //Trim all incomming data
        $trimmed = array_map('trim', $_POST);

        // Validate the body:
        if (!empty($_POST['body'])) {
            $body = htmlentities($_POST['body']);
        } else {
            $body = FALSE;
            $errors['body'] = true;
        }

        //GET thread ID
        if ((isset($_POST['threadId'])) && (is_numeric($_POST['threadId']))) {
            $threadID = $_POST['threadId'];
        }
        //Get ID
        if ((isset($_POST['id'])) && (is_numeric($_POST['id']))) {
            $postID = $_POST['id'];
        } else {
            mysqli_close($dbc); //Disconnect db
            //REDIRECT the user because there is no product to show
            $url = BASE_URL . 'view_faq.php?id=' . $threadID; // Define the URL.
            ob_end_clean(); // Delete the buffer.
            header("Location: $url");
            exit(); // Quit the script.
        }



        //Make sure values are valid
        if ($body) {
            echo $body;
            $q = "  UPDATE posts 
                        SET 
                            message = '" . mysqli_real_escape_string($dbc, $body) . "'
                    WHERE post_id = $postID";
            $r = mysqli_query($dbc, $q);
            echo mysqli_affected_rows($dbc);
            if (mysqli_affected_rows($dbc) != 1) {
                mysqli_close($dbc); //Disconnect db
                echo '<p>Your message could not be updated. There was an unexpected internal issue.';
                include('../includes/footer.html');
                exit();
            } else {
                mysqli_close($dbc); //Disconnect db
                //REDIRECT the user
                $url = BASE_URL . 'view_faq.php?id=' . $threadID; // Define the URL.
                ob_end_clean(); // Delete the buffer.
                header("Location: $url");
                exit(); // Quit the script.
            }
        }
    }


    //Get thread ID
    if ((isset($_GET['threadId'])) && (is_numeric($_GET['threadId']))) {
        $threadID = $_GET['threadId'];
    }

    //Get ID
    if ((isset($_GET['id'])) && (is_numeric($_GET['id']))) {
        $postID = $_GET['id'];
    } else {
        mysqli_close($dbc); //Disconnect db
        //REDIRECT the user because there is no product to show
        $url = BASE_URL . 'view_faq.php?id=' . $threadID; // Define the URL.
        ob_end_clean(); // Delete the buffer.
        header("Location: $url");
        exit(); // Quit the script.
    }


    // If the user is logged in and has chosen a time zone,
    // use that to convert the dates and times:

    // The query for retrieving all the threads in this forum, along with the original user,
    // when the thread was first posted, when it was last replied to, and how many replies it's had:
    $q = "  SELECT 
                p.post_id
            ,   p.message
            ,   u.first_name
        FROM        posts AS p
        INNER JOIN  users   AS u ON p.created_by_user_id = u.user_id 
        WHERE p.post_id = $postID";
    $r = mysqli_query($dbc, $q);
    if (mysqli_num_rows($r) > 0) {
        $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
        // Create a table:
        echo '<div class="container container-lg">';

        if (isset($_SESSION['user_level']) && $_SESSION['user_level'] == 1) {
            echo '  <form action="edit_post.php" method="post">
                <div class="card" style="margin-top: 2rem; margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h3>Update ' . $row['first_name'] . '\'s Message</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-lg-4">
                                <label for="productDesc">Question:</label>
                                <textarea name="body" class="form-control" rows="10" cols="60">' . $row['message'] . '</textarea>
                                ' . ((array_key_exists('body', $errors)) ? '<small class="error">Please enter a body for this post.</small>' : '') . '
                            </div>
                            <div class="col-lg-12 d-flex justify-content-center">
                                <div class="col-md-6 col-xl-4">
                                    <button type="submit" name="submit" class="btn btn-success btn-lg btn-block"><i class="fas fa-save"></i>  Save Question</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="id" value="' . $postID . '">
                <input type="hidden" name="threadId" value="' . $threadID . '">
            </form>';
        }


        echo '</div>'; // Complete the table.

    }
    // Include the HTML footer file:
    include('../includes/footer.html');
} else {
    mysqli_close($dbc); //Disconnect db
    //REDIRECT the user
    $url = BASE_URL . 'view_faq.php?id=' . $threadID; // Define the URL.
    ob_end_clean(); // Delete the buffer.
    header("Location: $url");
    exit(); // Quit the script.
}

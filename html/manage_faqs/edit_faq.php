<?php
// Set the page title and include the HTML header:
$page_title = 'Edit FAQ';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
require('../includes/config.inc.php');
include('../includes/header.html');
include('../includes/navigation_bar.html');
// Retrieve all the messages in this forum...
require('../../mysqli_connect.php');

$errors = array();

$subject = false;
$threadID = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_level']) && $_SESSION['user_level'] == 1) { //Make sure there is a logged in user and admin
    if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Handle the form.

        //Trim all incomming data
        $trimmed = array_map('trim', $_POST);

        // Check for a subject:
        if (preg_match('/^[A-Z \'.-?!]{2,20}$/i', $trimmed['subject'])) {
            $subject = mysqli_real_escape_string($dbc, $trimmed['subject']);
        } else {
            $errors['subject'] = true;
        }

        //Get ID
        if ((isset($_POST['id'])) && (is_numeric($_POST['id']))) {
            $threadID = $_POST['id'];
        } else {
            mysqli_close($dbc); //Disconnect db
            //REDIRECT the user because there is no product to show
            $url = BASE_URL . 'faq.php'; // Define the URL.
            ob_end_clean(); // Delete the buffer.
            header("Location: $url");
            exit(); // Quit the script.
        }

        //Make sure values are valid
        if ($subject) {
            echo $subject;
            $q = "  UPDATE threads 
                        SET 
                            subject = '$subject'
                    WHERE thread_id = $threadID";
            $r = mysqli_query($dbc, $q);
            echo mysqli_affected_rows($dbc);
            if (mysqli_affected_rows($dbc) != 1) {
                mysqli_close($dbc); //Disconnect db
                echo '<p>Your question could not be submitted. There was an unexpected internal issue.';
                include('../includes/footer.html');
                exit();
            } else {
                mysqli_close($dbc); //Disconnect db
                //REDIRECT the user
                $url = BASE_URL . 'faq.php'; // Define the URL.
                ob_end_clean(); // Delete the buffer.
                header("Location: $url");
                exit(); // Quit the script.
            }
        }
    }

    //Get ID
    if ((isset($_GET['id'])) && (is_numeric($_GET['id']))) {
        $threadID = $_GET['id'];
    } else {
        mysqli_close($dbc); //Disconnect db
        //REDIRECT the user because there is no product to show
        $url = BASE_URL . 'faq.php'; // Define the URL.
        ob_end_clean(); // Delete the buffer.
        header("Location: $url");
        exit(); // Quit the script.
    }

    // If the user is logged in and has chosen a time zone,
    // use that to convert the dates and times:

    // The query for retrieving all the threads in this forum, along with the original user,
    // when the thread was first posted, when it was last replied to, and how many replies it's had:
    $q = "  SELECT 
                t.thread_id
            ,   t.subject
            ,   u.first_name
        FROM        threads AS t 
        INNER JOIN  users   AS u ON t.created_by_user_id = u.user_id 
        WHERE t.thread_id = $threadID";
    $r = mysqli_query($dbc, $q);
    if (mysqli_num_rows($r) > 0) {
        $row = mysqli_fetch_array($r, MYSQLI_ASSOC);
        // Create a table:
        echo '<div class="container container-lg">';

        if (isset($_SESSION['user_level']) && $_SESSION['user_level'] == 1) {
            echo '  <form action="edit_faq.php" method="post">
                <div class="card" style="margin-top: 2rem; margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h3>Update ' . $row['first_name'] . '\'s Question</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-lg-4">
                                <label for="productDesc">Question:</label>
                                <input class="form-control" type="text" name="subject" value="' . $row['subject'] . '" size="20">
                                ' . ((array_key_exists('subject', $errors)) ? '<small class="error">Please enter the comment!</small>' : '') . '
                            </div>
                            <div class="col-lg-12 d-flex justify-content-center">
                                <div class="col-md-6 col-xl-4">
                                    <button type="submit" name="submit" class="btn btn-success btn-lg btn-block"><i class="fas fa-save"></i>  Save Question</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="id" value="' . $row['thread_id'] . '">
            </form>';
        }


        echo '</div>'; // Complete the table.

    }
    // Include the HTML footer file:
    include('../includes/footer.html');
} else {
    mysqli_close($dbc); //Disconnect db
    //REDIRECT the user
    $url = BASE_URL . 'faq.php'; // Define the URL.
    ob_end_clean(); // Delete the buffer.
    header("Location: $url");
    exit(); // Quit the script.
}

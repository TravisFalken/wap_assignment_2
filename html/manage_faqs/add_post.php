<?php
// This page handles the message post.
// It also displays the form if creating a new thread.
require('../includes/config.inc.php');
include('../includes/header.html');



if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Handle the form.
    require('../../mysqli_connect.php');
    // Validate thread ID ($id), which may not be present:
    if (isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
        $id = $_POST['id'];
    } else {
        $id = FALSE;
    }

    // If there's no thread ID, a subject must be provided:
    if (!$id && empty($_POST['subject'])) {
        $subject = FALSE;
        echo '<p class="bg-danger">Please enter a subject for this post.</p>';
    } elseif (!$id && !empty($_POST['subject'])) {
        $subject = htmlspecialchars(strip_tags($_POST['subject']));
    } else { // Thread ID, no need for subject.
        $subject = TRUE;
    }

    // Validate the body:
    if (!empty($_POST['body'])) {
        $body = htmlentities($_POST['body']);
    } else {
        $body = FALSE;
        echo '<p class="bg-danger">Please enter a body for this post.</p>';
    }

    if ($subject && $body) { // OK!

        if ($id) { // Add this to the replies table:
            $q = "INSERT INTO posts (thread_id, created_by_user_id, message) VALUES ($id, {$_SESSION['user_id']}, '" . mysqli_real_escape_string($dbc, $body) . "')";
            $r = mysqli_query($dbc, $q);
            if (mysqli_affected_rows($dbc) == 1) {
                mysqli_close($dbc); //Disconnect db
                //REDIRECT the user
                $url = BASE_URL . 'view_faq.php?id=' . $id; // Define the URL.
                ob_end_clean(); // Delete the buffer.
                header("Location: $url");
                exit(); // Quit the script.
            } else {
                mysqli_close($dbc); //Disconnect db
                echo '<p class="bg-danger">Your post could not be handled due to a system error.</p>';
            }
        } // Valid $id.

    } else { // Include the form:
        mysqli_close($dbc); //Disconnect db
        include('../includes/post_form.php');
    }
} else { // Display the form:

    include('../includes/post_form.php');
}

include('../includes/footer.html');

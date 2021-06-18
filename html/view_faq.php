<?php
// Set the page title and include the HTML header:
$page_title = 'View FAQ';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
// This page shows the messages in a thread.
include('includes/header.html');
require('includes/config.inc.php');
include('includes/navigation_bar.html');

require(MYSQL);
// Check for a thread ID...
$id = FALSE;
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT, array('min_range' => 1))) {

    // Create a shorthand version of the thread ID:
    $id = $_GET['id'];


    // Run the query:
    $q = "SELECT t.subject, p.post_id, p.message, u.user_level,u.first_name, DATE_FORMAT(p.created_date, '%e %b %y at %l:%i %p') AS posted FROM threads AS t LEFT JOIN posts AS p USING (thread_id) LEFT JOIN users AS u ON p.created_by_user_id = u.user_id WHERE t.thread_id = $id ORDER BY p.created_date ASC";
    $r = mysqli_query($dbc, $q);

    if ((mysqli_num_rows($r) == 0)) {
        $id = FALSE; // Invalid thread ID!
    }
} // End of isset($_GET['tid']) IF.

if ($id) { // Get the messages in this thread...

    $printed = FALSE; // Flag variable.
    echo '<div class="container">';
    // Fetch each:
    while ($messages = mysqli_fetch_array($r, MYSQLI_ASSOC)) {

        // Only need to print the subject once!
        if (!$printed) {
            echo '<a href="faq.php" class="btn btn-primary custom-grid-button"><i class="fas fa-arrow-left"></i>  Back</a><h2>' . $messages['subject'] . '</h2>';
            $printed = TRUE;
        }

        // Print the message:
        if (!is_null($messages['message'])) {
            echo '  <div class="card" style="margin-top: 2rem; margin-bottom: 1.5rem;">
                    <div class="card-header">
                        ' . $messages['first_name'] . (($messages['user_level'] == 1) ? '(Admin)' : '') . ' - ' . $messages['posted'] . '
                    </div>
                    <div class="card-body">
                        <p class="card-text">' . $messages['message'] . '</p>
                        ' . ((isset($_SESSION['user_level']) && $_SESSION['user_level'] == 1) ? '<a href="manage_faqs/edit_post.php?id=' . $messages['post_id'] . '&threadId=' . $id . '" class="btn btn-primary custom-grid-button">Edit</a><a href="manage_faqs/delete_post.php?id=' . $messages['post_id'] . '&threadId=' . $id . '" class="btn btn-danger custom-grid-button">Delete</a>' : '') . '
                    </div>
                </div>';
        }
    } // End of WHILE loop.

    $r->free(); // Free up the resources.
    unset($r);
    // Show the form to post a message:
    include('includes/post_form.php');
    echo '</div>';
} else { // Invalid thread ID!
    echo '  <div class="container custom-error-div">
                <div class="row d-flex justify-content-center">
                    <h2 class="text-danger">This page has been accessed in error.</h2>
                </div>
                <div class="row d-flex justify-content-center">
                    <div class="col-md-8 col-xl-6">
                        <a class="btn btn-primary btn-block" href="' . BASE_URL . 'index.php">OK</a>
                    </div>
                </div>
            <div>';
}

include('includes/footer.html');

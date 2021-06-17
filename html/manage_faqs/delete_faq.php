<?php
include('../includes/header.html');
require('../includes/config.inc.php');

if (isset($_SESSION['user_level']) && $_SESSION['user_level'] == 1) {

    //Connect to the database
    require('../../mysqli_connect.php');

    if (isset($_GET['id'])) {
        $faqIDToDelete = mysqli_real_escape_string($dbc, $_GET['id']);
    } else {

        //REDIRECT the user because they do not have permission
        $url = BASE_URL . 'index.php'; // Define the URL.
        ob_end_clean(); // Delete the buffer.
        header("Location: $url");
        exit(); // Quit the script.

    }

    $q = "DELETE FROM threads WHERE thread_id = $faqIDToDelete";
    echo $q;
    mysqli_query($dbc, $q); //Run the query

    if (mysqli_affected_rows($dbc) >= 1) { // If it ran OK.

        $url = BASE_URL . 'faq.php'; // Define the URL.
        ob_end_clean(); // Delete the buffer.
        header("Location: $url");
        exit(); // Quit the script.
    } else { // If the query did not run OK.
        echo '<p class="error">The FAQ could not be deleted due to a system error.</p>'; // Public message.
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

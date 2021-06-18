<?php
// Set the page title and include the HTML header:
$page_title = 'FAQ';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
require('includes/config.inc.php');
include('includes/header.html');
include('includes/navigation_bar.html');
// Retrieve all the messages in this forum...
require(MYSQL);

$errors = array();

$subject = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Handle the form.

    if (isset($_SESSION['user_id'])) { //Make sure there is a logged in user

        //Trim all incomming data
        $trimmed = array_map('trim', $_POST);

        // Check for a subject:
        if (preg_match('/^[A-Z \'.-?!]{2,30}$/i', $trimmed['subject'])) {
            $subject = mysqli_real_escape_string($dbc, $trimmed['subject']);
        } else {
            $errors['subject'] = true;
        }
        //Get user
        $userID = $_SESSION['user_id'];

        //Make sure values are valid
        if ($subject) {
            $q = "INSERT INTO threads (subject, created_by_user_id, thread_type) VALUES ('$subject', $userID, 'faq')";
            $r = mysqli_query($dbc, $q);
            echo mysqli_affected_rows($dbc);
            if (mysqli_affected_rows($dbc) != 1) {
                echo '<p>Your question could not be submitted. There was an unexpected internal issue.';
                include('includes/footer.html');
                exit();
            }
        }
    }
}

// If the user is logged in and has chosen a time zone,
// use that to convert the dates and times:

// The query for retrieving all the threads in this forum, along with the original user,
// when the thread was first posted, when it was last replied to, and how many replies it's had:
$q = "  SELECT 
                t.thread_id
            ,   t.subject
            ,   u.first_name
            ,   CASE WHEN COUNT(post_id) < 0
                THEN
                    0
                ELSE
                    COUNT(post_id)
                END         AS responses
            ,   MAX(DATE_FORMAT(p.created_date, '%e-%b-%y %l:%i %p')) AS last
            ,   MIN(DATE_FORMAT(t.created_date, '%e-%b-%y %l:%i %p')) AS first 
        FROM        threads AS t 
        LEFT JOIN  posts   AS p USING (thread_id) 
        INNER JOIN  users   AS u ON t.created_by_user_id = u.user_id 
        WHERE t.thread_type = 'faq' 
        GROUP BY (t.thread_id) 
        ORDER BY last DESC";
$r = mysqli_query($dbc, $q);


// Create a table:
echo '<div class="container container-lg">';

if (isset($_SESSION['user_id'])) {
    echo '  <form action="faq.php" method="post">
                <div class="card" style="margin-top: 2rem; margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h3>Ask Us a Question!</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-lg-4">
                                <label for="productDesc">Question:</label>
                                <input class="form-control" type="text" name="subject" value="' . $subject . '" size="20">
                                ' . ((array_key_exists('subject', $errors)) ? '<small class="error">Please enter the comment!</small>' : '') . '
                            </div>
                            <div class="col-lg-12 d-flex justify-content-center">
                                <div class="col-md-6 col-xl-4">
                                    <button type="submit" name="submit" class="btn btn-primary btn-lg btn-block">Submit Question</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>';
}
if (mysqli_num_rows($r) > 0) {
    echo '<table class="table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Posted By</th>
                    <th>Posted Date</th>
                    <th>Replies</th>
                    <th>Latest Reply</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

    // Fetch each thread:
    while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {

        echo '<tr>
				<td><a href="view_faq.php?id=' . $row['thread_id'] . '"><h3>' . $row['subject'] . '</h3></a></td>
				<td>' . $row['first_name'] . '</td>
				<td>' . $row['first'] . '</td>
				<td>' . $row['responses'] . '</td>
				<td>' . $row['last'] . '</td>
                ' . ((isset($_SESSION['user_level']) && $_SESSION['user_level'] == 1) ? '<td><a class="btn btn-primary custom-grid-button" href="' . BASE_URL . 'manage_faqs/edit_faq.php?id=' . $row['thread_id'] . '">Edit</a><a class="btn btn-danger custom-grid-button" href="' . BASE_URL . 'manage_faqs/delete_faq.php?id=' . $row['thread_id'] . '">Delete</a></td>'  : '') . '
			</tr>';
    }

    $r->free(); // Free up the resources.
    unset($r);
    echo '</tbody></table></div>'; // Complete the table.

} else {
    echo '<p>There are currently no messages in this forum.</p>';
}

// Include the HTML footer file:
include('includes/footer.html');

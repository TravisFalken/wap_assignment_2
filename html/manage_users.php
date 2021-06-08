<?php # Script 16.3 - view_users.php #6
// This script retrieves all the records from the users table.
// This is an OOP version of the script from Chapter 10.

require('includes/config.inc.php');
$page_title = 'View the Current Users';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
include('includes/header.html');
include('includes/navigation_bar.html');

// Page header:
echo '<div class="container"><h1>Registered Users</h1>';

if (!isset($_SESSION['user_id'])) {

	$url = BASE_URL . 'index.php'; // Define the URL.
	ob_end_clean(); // Delete the buffer.
	header("Location: $url");
	exit(); // Quit the script.

}

//Get db connection:
require(MYSQL);

// Make the query:
$q = "SELECT CONCAT(last_name, ', ', first_name) AS name, DATE_FORMAT(registration_date, '%M %d, %Y') AS dr, user_id FROM users WHERE user_id <> " . $_SESSION['user_id'] . " ORDER BY registration_date ASC";
$r = mysqli_query($dbc, $q); // Run the query.

// Count the number of returned rows:
$num = $r->num_rows;

if ($num > 0) { // If it ran OK, display the records.

	// Print how many users there are:
	echo "<p>There are currently $num registered users.</p></div>\n";

	// Table header.
	echo '
	<div class="table-responsive container">
	<table class="table" width="60%">
	<thead>
	<tr><td align="left"><strong>Name</strong></td><td align="left"><strong>Date Registered</strong></td><td align="left"></td></tr>
	</thead>
	<tbody>
';

	// Fetch and print all the records:
	while ($row = $r->fetch_object()) {
		echo '<tr><td align="left">' . $row->name . '</td><td align="left">' . $row->dr . '</td>
		<td align="left"><button class="btn btn-primary" onclick="editUserClicked(' . $row->user_id . ')">Edit</button><button class="btn btn-danger" onclick="deleteUserClicked(' . $row->user_id . ')">Delete</button></td></tr>
		';
	}

	echo '</tbody></table></div>'; // Close the table.

	$r->free(); // Free up the resources.
	unset($r);
} else { // If no records were returned.

	echo '<p class="error">There are currently no registered users.</p>';
}

// Close the database connection.
mysqli_close($dbc);
unset($mysqli);
?>
<script>
	function deleteUserClicked(userID) {
		if (confirm('Are you sure you want to delete this user?')) {
			window.location = "<?php $baseUrl = BASE_URL;
								echo $baseUrl . "manage_users/delete_user.php?id=" ?>" + userID;
		}
	}

	function editUserClicked(userID) {
		window.location = "<?php $baseUrl = BASE_URL;
							echo $baseUrl . "manage_users/edit_user.php?id=" ?>" + userID;
	}
</script>
<?php
include('includes/footer.html');
?>
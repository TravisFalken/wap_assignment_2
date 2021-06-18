<?php # Script 18.11 - change_password.php
// This page allows a logged-in user to change their password.
require('includes/config.inc.php');
$page_title = 'Change Your Password';
include('includes/header.html');
include('includes/navigation_bar.html');

// If no user_id session variable exists, redirect the user:
if (!isset($_SESSION['user_id'])) {

	$url = BASE_URL . 'index.php'; // Define the URL.
	ob_end_clean(); // Delete the buffer.
	header("Location: $url");
	exit(); // Quit the script.

}
$errors = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	require(MYSQL);

	// Check for a new password and match against the confirmed password:
	$p = FALSE;
	if (strlen($_POST['password1']) >= 10) {
		if ($_POST['password1'] == $_POST['password2']) {
			$p = password_hash($_POST['password1'], PASSWORD_DEFAULT);
		} else {
			$errors['noMatch'] = true;
		}
	} else {
		$errors['password1'] = true;
	}

	if ($p) { // If everything's OK.

		// Make the query:
		$q = "UPDATE users SET pass='$p' WHERE user_id={$_SESSION['user_id']} LIMIT 1";
		$r = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));
		if (mysqli_affected_rows($dbc) == 1) { // If it ran OK.

			// Send an email, if desired.
			echo '<h3>Your password has been changed.</h3>';
			mysqli_close($dbc); // Close the database connection.
			include('includes/footer.html'); // Include the HTML footer.
			exit();
		} else { // If it did not run OK.

			echo '
			<div class="row d-flex justify-content-center">
				<p class="error">Your password was not changed. Make sure your new password is different than the current password. Contact the system administrator if you think an error occurred.</p>
			</div>';
		}
	}
	mysqli_close($dbc); // Close the database connection.

} // End of the main Submit conditional.
?>
<div class="container">
	<h1 align="center">Change Your Password</h1>
	<form action="change_password.php" method="post">
		<div class="form-group">
			<label for="password1"> New Password:</label>
			<input class="form-control" type="password" name="password1" size="20">
			<small>At least 10 characters long.</small>
			<?php echo ((array_key_exists('password1', $errors)) ? '<small class="error">Please enter a valid password!</small>' : ''); ?>
		</div>
		<div class="form-group">
			<label for="password">Confirm New Password:</label>
			<input class="form-control" type="password" name="password2" size="20"></p>
			<?php echo ((array_key_exists('noMatch', $errors)) ? '<small class="error">Your password did not match the confirmed password!</small>' : ''); ?>
		</div>
		<div class="row d-flex justify-content-center">
			<div class="col-md-8 col-xl-6" align="center"><input type="submit" name="submit" class="btn btn-primary btn-lg btn-block" value="Change My Password"></div>
		</div>
	</form>
</div>
<?php include('includes/footer.html'); ?>
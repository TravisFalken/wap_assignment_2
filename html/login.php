<?php # Script 18.8 - login.php
// This is the login page for the site.
require('includes/config.inc.php');
$page_title = 'Login';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
include('includes/header.html');
include('includes/navigation_bar.html');

$errors = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	require(MYSQL);

	// Validate the email address:
	if (!empty($_POST['email'])) {
		$e = mysqli_real_escape_string($dbc, $_POST['email']);
	} else {
		$e = FALSE;
		$errors['email'] = true;
	}

	// Validate the password:
	if (!empty($_POST['pass'])) {
		$p = trim($_POST['pass']);
	} else {
		$p = FALSE;
		$errors['pass'] = true;
	}

	if ($e && $p) { // If everything's OK.

		// Query the database:
		$q = "SELECT user_id, first_name, user_level, pass FROM users WHERE email='$e' AND active IS NULL";
		$r = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

		if (@mysqli_num_rows($r) == 1) { // A match was made.

			// Fetch the values:
			list($user_id, $first_name, $user_level, $pass) = mysqli_fetch_array($r, MYSQLI_NUM);
			mysqli_free_result($r);

			// Check the password:
			if (password_verify($p, $pass)) {

				// Store the info in the session:
				$_SESSION['user_id'] = $user_id;
				$_SESSION['first_name'] = $first_name;
				$_SESSION['user_level'] = $user_level;
				mysqli_close($dbc);

				// Redirect the user:
				$url = BASE_URL . 'index.php'; // Define the URL.
				ob_end_clean(); // Delete the buffer.
				header("Location: $url");
				exit(); // Quit the script.

			} else {
				$errors['login'] = true;
			}
		} else { // No match was made.
			$errors['login'] = true;
		}
	}

	mysqli_close($dbc);
} // End of SUBMIT conditional.
?>
<div class="container">
	<h1>Login</h1>
	<p>Your browser must allow cookies in order to log in.</p>
	<?php echo (array_key_exists('login', $errors)) ? '<small class="error">Either the email address and password entered do not match those on file or you have not yet activated your account.</small>' : ''; ?>
</div>
<form action="login.php" method="post">
	<div class="container">
		<div class="form-group">
			<label for="email">Email Address:</label>
			<input type="email" class="form-control" name="email" size="20" maxlength="60">
			<?php echo (array_key_exists('email', $errors)) ? '<small class="error">You forgot to enter your email address!</small>' : ''; ?>
		</div>
		<div class="form-group">
			<label for="password">Password:</label>
			<input type="password" class="form-control" name="pass" size="20">
			<?php echo (array_key_exists('pass', $errors)) ? '<small class="error">You forgot to enter your password!</small>' : ''; ?>
			<small><a href="<?php echo BASE_URL . 'forgot_password.php' ?>">Forgot Password</a></small>
		</div>
		<div class="form-group row">
			<div class="col-sm-6">
				<button type="submit" class="btn btn-primary btn-block">Login</button>
			</div>
		</div>
	</div>
</form>

<?php include('includes/footer.html'); ?>
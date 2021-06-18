<?php # Script 18.6 - register.php
// This is the registration page for the site.
require('includes/config.inc.php');
$page_title = 'Register';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page

include('includes/header.html');
include('includes/navigation_bar.html');

$errors = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Handle the form.

	//Get db connection:
	require(MYSQL);

	// Trim all the incoming data:
	$trimmed = array_map('trim', $_POST);

	// Assume invalid values:
	$firstName = $lastName = $email = $pass = FALSE;

	// Check for a first name:
	if (preg_match('/^[A-Z \'.-]{2,20}$/i', $trimmed['first_name'])) {
		$firstName = mysqli_real_escape_string($dbc, $trimmed['first_name']);
	} else {
		$errors['first_name'] = true;
	}

	// Check for a last name:
	if (preg_match('/^[A-Z \'.-]{2,40}$/i', $trimmed['last_name'])) {
		$lastName = mysqli_real_escape_string($dbc, $trimmed['last_name']);
	} else {
		$errors['last_name'] = true;
	}

	// Check for an email address:
	if (filter_var($trimmed['email'], FILTER_VALIDATE_EMAIL)) {
		$email = mysqli_real_escape_string($dbc, $trimmed['email']);
	} else {
		$errors['email'] = true;
	}

	// Check for a password and match against the confirmed password:
	if (strlen($trimmed['password1']) >= 10) {
		if ($trimmed['password1'] == $trimmed['password2']) {
			$pass = password_hash($trimmed['password1'], PASSWORD_DEFAULT);
		} else {
			$errors['password2'] = true;
		}
	} else {
		$errors['password1'] = true;
	}

	if ($firstName && $lastName && $email && $pass) { // If everything's OK...

		// Make sure the email address is available:
		$q = "SELECT user_id FROM users WHERE email='$email'";
		$res = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

		if (mysqli_num_rows($res) == 0) { // Available.

			// Create the activation code:
			//$activationCode = md5(uniqid(rand(), true));

			// Add the user to the database:
			$q = "INSERT INTO users (email, pass, first_name, last_name, registration_date) VALUES ('$email', '$pass', '$firstName', '$lastName', NOW() )";
			$res = mysqli_query($dbc, $q) or trigger_error("Query: $q\n<br>MySQL Error: " . mysqli_error($dbc));

			if (mysqli_affected_rows($dbc) == 1) { //Make sure user has been added

				// Send the email:
				//$body = "Thank you for registering at computersRUs. To activate your account, please click on this link:\n\n";
				//$body .= BASE_URL . 'activate.php?x=' . urlencode($email) . "&y=$activationCode";
				//mail($trimmed['email'], 'Registration Confirmation', $body, 'From: admin@sitename.com');

				// Finish the page:
				echo '	<div class="container">
							<div class="row d-flex justify-content-center">
								<h3>Thank you for registering ' . $firstName . '!</h3>
							</div>
							<div class="row d-flex justify-content-center">
								<p>Please click okay to proceed.</p>
							</div>
							<div class="row d-flex justify-content-center">
								<div class="col-md-8 col-xl-6">
									<a class="btn btn-primary btn-block" href="' . BASE_URL . 'index.php">OK</a>
								</div>
							</div>
						</div>';
				include('includes/footer.html'); // Include the HTML footer.
				exit(); // Stop the page.

			} else { // If it did not run OK.
				echo '<p class="error">You could not be registered due to a system error. We apologize for any inconvenience.</p>';
			}
		} else { // The email address is not available.
			$errors['duplicateEmail'] = true;
		}
	}

	mysqli_close($dbc);
} // End of the main Submit conditional.
?>
<div class="container">
	<h1>Register</h1>
</div>
<form action="register.php" method="post">
	<div class="container">
		<div class="form-group">
			<label for="first_name">First Name:</label>
			<input type="text" class="form-control" name="first_name" size="20" maxlength="20" value="<?php if (isset($trimmed['first_name'])) echo $trimmed['first_name']; ?>">
			<?php echo (array_key_exists('first_name', $errors)) ? '<small class="error">Please enter a valid first name!</small>' : ''; ?>
		</div>
		<div class="form-group">
			<label for="last_name">Last Name:</label>
			<input type="text" class="form-control" name="last_name" size="20" maxlength="40" value="<?php if (isset($trimmed['last_name'])) echo $trimmed['last_name']; ?>">
			<?php echo (array_key_exists('last_name', $errors)) ? '<small class="error">Please enter a valid last name!</small>' : ''; ?>
		</div>
		<div class="form-group">
			<label for="email"> Email Address:</label>
			<input type="email" class="form-control" name="email" size="30" maxlength="60" value="<?php if (isset($trimmed['email'])) echo $trimmed['email']; ?>">
			<?php echo (array_key_exists('email', $errors)) ? '<small class="error">Please enter a valid  email address!</small>' : ''; ?>
			<?php echo (array_key_exists('duplicateEmail', $errors)) ? '<small class="error">This email address is already registered to an account!</small>' : ''; ?>
		</div>
		<div class="form-group">
			<label for="password1"> Password:</label>
			<input type="password" class="form-control" name="password1" size="20" value="<?php if (isset($trimmed['password1'])) echo $trimmed['password1']; ?>">
			<small>At least 10 characters long.</small>
			<?php echo (array_key_exists('password1', $errors)) ? '<small class="error">Please enter a valid password!!</small>' : ''; ?>
		</div>
		<div class="form-group">
			<label for="password2">Confirm Password:</label>
			<input type="password" class="form-control" name="password2" size="20" value="<?php if (isset($trimmed['password2'])) echo $trimmed['password2']; ?>">
			<?php echo (array_key_exists('password2', $errors)) ? '<small class="error">Your password did not match the confirmed password!</small>' : ''; ?>
		</div>
		<div class="form-group row">
			<div class="col-sm-6">
				<button type="submit" class="btn btn-primary btn-block">Register</button>
			</div>
		</div>
	</div>



</form>

<?php include('includes/footer.html'); ?>
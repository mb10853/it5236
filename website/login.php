<?php
// Import the application classes
require_once('include/classes.php');
// Create an instance of the Application class
$app = new Application();
$app->setup();
// Declare a set of variables to hold the username and password for the user
$username = "";
$password = "";
// Declare an empty array of error messages
$errors = array();
// If someone has clicked their email validation link, then process the request
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	if (isset($_GET['id'])) {

		$success = $app->processEmailValidation($_GET['id'], $errors);
		if ($success) {
			$message = "Email address validated. You may login.";
		}
	}
}
// If someone is attempting to login, process their request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// Pull the username and password from the <form> POST
	$username = $_POST['username'];
	$password = $_POST['password'];
	// Attempt to login the user and capture the result flag
	$result = $app->login($username, $password, $errors);
	// Check to see if the login attempt succeeded
	if ($result == TRUE) {
		// Redirect the user to the topics page on success
		header("Location: list.php");
		exit();
	}
}
if (isset($_GET['register']) && $_GET['register']== 'success') {
	$message = "Registration successful. Please check your email. A message has been sent to validate your address.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
		<meta name="description" content="IT5236 - Marcus Butler">
		<meta name="author" content="Marcus Butler">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Marcus Butler</title>
    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/modern-business.css" rel="stylesheet">
</head>

<!--1. Display Errors if any exists
	2. Display Login form (sticky):  Username and Password -->

<body>
	<?php include 'include/header.php'; ?>
	<h2>Login</h2>
	<?php include('include/messages.php'); ?>
	<div>
		<form method="post" name="usernameForm" id="usernameForm" action="login.php">
			<input type="text" name="username" id="username" placeholder="Username" value="<?php echo $username; ?>" >
			<br/>
			<input type="password" name="password" id="password" placeholder="Password" value="<?php echo $password; ?>" >
			<br/><br/>
            <input type="checkbox" name="remember_username" id="remember_username"> Remember Username
            <br/>
			<input type="submit" value="Login" name="submit">
		</form>
	</div>
	<a href="register.php">Need to create an account?</a>
	<br/>
	<a href="reset.php">Forgot your password?</a>
	<?php include 'include/footer.php'; ?>

	<script src="js/site.js"></script>
	<!-- Bootstrap core JavaScript -->
	<script src="vendor/jquery/jquery.min.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

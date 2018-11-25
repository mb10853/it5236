<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Declare a set of variables to hold the username, password, question, and answer for the new user
$username = "";
$password = "";
$email = "";
$registrationcode = "";

// Declare a list to hold error messages that need to be displayed
$errors = array();

// If someone is attempting to register, process their request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Pull the username, password, question, and answer from the <form> POST
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $registrationcode = $_POST['registrationcode'];

    // Attempt to register the new user and capture the result flag
    $result = $app->register($username, $password, $email, $registrationcode, $errors);

    // Check to see if the register attempt succeeded
    if ($result == true) {

        // Redirect the user to the login page on success
        header("Location: login.php?register=success");
        exit();
    }
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
	2. Display Registration form (sticky):  Username, Password, Question, and Answer -->
<body>
	<?php include 'include/header.php'; ?>

	<h2>Register</h2>

	<?php include('include/messages.php'); ?>

	<div>
		<form action="register.php" method="post">
			<input type="text" name="username" id="username" placeholder="Pick a username" value="<?php echo $username; ?>" />
			<br/>
			<input type="password" name="password" id="password" placeholder="Provide a password" value="<?php echo $password; ?>" />
			<br/>
			<input type="text" name="email" id="email" placeholder="Enter your email address" size="50" value="<?php echo $email; ?>" />
			<br/>
			<input type="text" name="registrationcode" id="registrationcode" placeholder="Enter the registration code provided by your instructor" size="35" value="<?php echo $registrationcode; ?>" />
			<br/>
			<input type="submit" value="Register" />
		</form>
	</div>
	<a href="login.php">Already a member?</a>
	<?php include 'include/footer.php'; ?>

	<script src="js/site.js"></script>
	<!-- Bootstrap core JavaScript -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

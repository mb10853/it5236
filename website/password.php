<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

$errors = array();
$messages = array();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $passwordrequestid = $_GET['id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Grab or initialize the input values
    $password = $_POST['password'];
    $passwordrequestid = $_POST['passwordrequestid'];

    // Request a password reset email message
    $app->updatePassword($password, $passwordrequestid, $errors);

    if (sizeof($errors) == 0) {
        $message = "Password updated";
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

<body>
	<?php include 'include/header.php'; ?>
	<main id="wrapper">
		<h2>Reset Password</h2>
		<?php include('include/messages.php'); ?>
		<form method="post" action="password.php">
			New password:
			<input type="password" name="password" id="password" required="required" size="40" />
			<input type="submit" value="Submit" />
			<input type="hidden" name="passwordrequestid" id="passwordrequestid" value="<?php echo $passwordrequestid; ?>" />
		</form>
	</main>
	<?php include 'include/footer.php'; ?>

	<script src="js/site.js"></script>
	<!-- Bootstrap core JavaScript -->
	<script src="vendor/jquery/jquery.min.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

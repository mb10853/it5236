<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Declare an empty array of error messages
$errors = array();

// Check for logged in admin user since this page is "isadmin" protected
// NOTE: passing optional parameter TRUE which indicates the user must be an admin
$app->protectPage($errors, true);

// Attempt to obtain the list of users
$users = $app->getUsers($errors);


// If someone is adding a new attachment type
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['attachmenttype'] == "add") {
        $name = $_POST['name'];
        ;
        $extension = $_POST['extension'];
        ;

        $attachmenttypeid = $app->newAttachmentType($name, $extension, $errors);

        if ($attachmenttypeid != null) {
            $messages[] = "New attachment type added";
        }
    }
}

// Attempt to obtain the list of users
$attachmentTypes = $app->getAttachmentTypes($errors);

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
	<h2>Admin Functions</h2>
	<?php include 'include/messages.php'; ?>
	<h3>User List</h3>
	<ul class="users">
		<?php foreach ($users as $user) {
    ?>
			<li><a href="editprofile.php?userid=<?php echo $user['userid']; ?>"><?php echo $user['username']; ?></a></li>
		<?php
} ?>
	</ul>
	<h3>Valid Attachment Types</h3>
	<ul class="attachmenttypes">
		<?php foreach ($attachmentTypes as $attachmentType) {
        ?>
			<li><?php echo $attachmentType['name']; ?> [<?php echo $attachmentType['extension']; ?>]</li>
		<?php
    } ?>
		<?php if (sizeof($attachmentTypes) == 0) {
        ?>
			<li>No attachment types found in the database</li>
		<?php
    } ?>
	</ul>
	<div class="newattachmenttype">
		<h4>Add Attachment Type</h4>
		<form enctype="multipart/form-data" method="post" action="admin.php">
			<label for="name">Name</label>
			<input id="name" name="name" type="text">
			<br/>
			<label for="extension">Extension</label>
			<input id="extension" name="extension" type="text">
			<br/>
			<input type="hidden" name="attachmenttype" value="add" />
			<input type="submit" name="addattachmenttype" value="Add type" />
		</form>
	</div>
	<?php include 'include/footer.php'; ?>

	<script src="js/site.js"></script>
	<!-- Bootstrap core JavaScript -->
	<script src="vendor/jquery/jquery.min.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

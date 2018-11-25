<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Declare an empty array of error messages
$errors = array();

// Check for logged in user since this page is protected
$app->protectPage($errors);

$name = "";


// Check for url flag indicating that there was a "no thing" error.
if (isset($_GET["error"]) && $_GET["error"] == "nothing") {
  $errors[] = "Things not found.";
}

// Check for url flag indicating that a new thing was created.
if (isset($_GET["newthing"]) && $_GET["newthing"] == "success") {
  $message = "Thing successfully created.";
}

// If someone is attempting to create a new thing, the process the request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  // Pull the title and thing text from the <form> POST
  $name = $_POST['name'];
  $attachment = $_FILES['attachment'];

  // Attempt to create the new thing and capture the result flag
  $result = $app->addThing($name, $attachment, $errors);

  // Check to see if the new thing attempt succeeded
  if ($result == true) {

    // Redirect the user to the login page on success
    header("Location: list.php?newthing=success");
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

<body>
  <?php include 'include/header.php'; ?>

  <?php include('include/messages.php'); ?>



<button type="button" id="searchbtn" class="btn btn-primary">Add New Game</button>
<br><br>

<div class="container">
  <div class="row">
    <div class="col-sm">
      <div class="list-group">
        <button class="list-group-item list-group-item-action active">Nintendo</button>
        <a href="inventory/nes.php" class="list-group-item list-group-item-action">NES</a>
        <a href="inventory/snes.php" class="list-group-item list-group-item-action">SNES</a>
        <a href="inventory/n64.php" class="list-group-item list-group-item-action">N64</a>
      </div>
    </div>
    <div class="col-sm">
      <div class="list-group">
        <button class="list-group-item list-group-item-action active">Sony</button>
        <a href="inventory/ps1.php" class="list-group-item list-group-item-action">Playstation</a>
        <a href="inventory/ps2.php" class="list-group-item list-group-item-action">Playstation 2</a>
        <a href="inventory/ps3.php" class="list-group-item list-group-item-action">Playstation 3</a>
      </div>
    </div>
    <div class="col-sm">
      <div class="list-group">
        <button class="list-group-item list-group-item-action active">Microsoft</button>
        <a href="inventory/xbox.php" class="list-group-item list-group-item-action">Xbox Original</a>
        <a href="inventory/xbox360.php" class="list-group-item list-group-item-action">Xbox 360</a>
        <a href="inventory/xboxone.php" class="list-group-item list-group-item-action">Xbox One</a>
      </div>
    </div>
  </div>
</div>


<?php include 'include/footer.php'; ?>

<script src="js/site.js"></script>
<!-- Bootstrap core JavaScript -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

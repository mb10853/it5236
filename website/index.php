<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Declare an empty array of error messages
$errors = array();


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <base href="https://mtbutler.net/webapp/website/">
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

	<h2>Welcome to GameDex!</h2>
  <p>
    This website will allow users to create, track, manage their game collections.
  </p>


  <div class="container">

      <h1 class="my-4 text-center text-lg-left">Collection</h1>
      <div class="row text-center text-lg-left">

          <?php
          //  get users entire game inventory
          if ($loggedin) {
              $game_list = $app->getAllTheThings();
          }
          $i = 0;
          foreach($game_list as $game){
              echo '
              <div class="col-lg-3 col-md-4 col-xs-6">
                <a href="#" class="d-block mb-4 h-100">
                  <img class="img-responsive img-thumbnail" src="'.$game['thingcover'].'">
                </a>
              </div>';
              ++$i;
          }
          ?>

        </div>
</div>

	<?php include 'include/footer.php'; ?>

	<script src="js/site.js"></script>
	<!-- Bootstrap core JavaScript -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

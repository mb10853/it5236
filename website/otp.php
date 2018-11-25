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

  <div class="container be-detail-container">
    <div class="row">
      <div class="col-sm-6 col-sm-offset-3">
        <img src="css/images/otp_icon.png" class="img-responsive" style="width:200px; height:200px;margin:0 auto;">
        <h1 class="text-center">Verify your OTP</h1><br>
        <p class="lead" style="align:center"></p>
        <p>An OTP has been sent to your email address. Please enter the 6 digit OTP below to continue logging in.</p>
        <form method="post" id="verify_otp" action="list.php">
          <div class="row">
            <div class="form-group col-sm-8">
              <span style="color:red;"></span>
              <input type="text" class="form-control" name="otp" placeholder="Enter your OTP number" required="">
            </div>
            <button type="submit" class="btn btn-primary  pull-right col-sm-3">Verify</button>
          </div>
        </form>
        <br><br>
      </div>
    </div>
    <?php echo $app->user_otp ?>
  </div>


  <?php include 'include/footer.php'; ?>

  <script src="js/site.js"></script>
  <!-- Bootstrap core JavaScript -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

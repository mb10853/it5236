<footer class="py-5 bg-dark">
  <div class="container">
    <p class="m-0 text-center text-white">
  		This is a bare-bones "list-oriented" web application for use in IT 5236, to teach mobile web infrastructure concepts.
  		Students currently registered for the course may <a href="login.php">create an account</a> or proceed directly to the
  		<a href="login.php">login page</a>.
      <br><br>
      Copyright &copy; <?php echo date("Y"); ?> Marcus Butler
    </p>
  </div>
</footer>


<?php

if ($_COOKIE['debug'] == "true") {
    echo "<h3>Debug messages</h3>";
    echo "<pre>";
    foreach ($app->debugMessages as $msg) {
        var_dump($msg);
    }
    echo "</pre>";
}

?>

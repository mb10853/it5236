<?php

// Import the application classes
require_once('../include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Declare an empty array of error messages
$errors = array();

// Check for logged in user since this page is protected
$app->protectPage($errors);

//  build game inventory: Pass the platformID to identify the console.
$game_list = $app->getThings(8);

//  define the total number of PS2 games
$ps2_total = 1850;

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
    <?php include '../include/header.php'; ?>

    <div class="jumbotron jumbotron-fluid">
        <div class="container">
            <h1 class="display-4">Playstation 2</h1>
            <p class="lead">
                Games in inventory: <?php echo sizeof($game_list); ?>
                <br>
                Games not owned: <?php echo ($ps2_total - sizeof($game_list)); ?>
            </p>
            <a href = "search.php"><button type="button" class="btn btn-primary">Add New Game</button></a>
        </div>
    </div>
    </div>

    <div class="container-fluid">
        <table class="table">
            <thead class="thead-dark">
                <tr>
                    <th scope="col"></th>
                    <th scope="col">GameID</th>
                    <th scope="col">Cover</th>
                    <th scope="col" style="width: 15%" >Name</th>
                    <th scope="col">Summary</th>
                </tr>
            </thead>
            <tbody>

                <?php
                $i = 0;
                foreach($game_list as $game){
                    echo '<tr>
                    <th scope="row">
                    <td><input readonly type="hidden" name="gameid" value="'.$game['gameid'].'">'.$game['gameid'].'</td>
                    <td><input readonly type="hidden" name="gamecover" value="'.$game['thingcover'].'"><img src="'.$game['thingcover'].'"class="game-thumbnail"></td>
                    <td><input readonly type="hidden" name="gamename" value="'.$game['thingname'].'">'.$game['thingname'].'</td>
                    <td><input readonly type="hidden" name="gamesummary" value="'.$game['thingsummary'].'">'.$game['thingsummary'].'</td>
                    </th>
                    </tr>';
                    ++$i;
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php include '../include/footer.php'; ?>

    <script src="js/site.js"></script>
    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

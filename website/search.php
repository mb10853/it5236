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

//  run game search
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Pull the title and platform from the search from and search for the game
	if (isset($_POST['titleinput']) && isset($_POST['platform'])) {
		$game_title = $_POST['titleinput'];
		$game_platform = $_POST['platform'];
		$game_list = $app->searchgames($game_title, $game_platform);

		//  append the platform to each game in the game_list
		if($game_list){
			$i = 0;
			foreach($game_list as $game){
				$game_list[$i]["platform"] = $_POST['platform'];
				++$i;
			}
		}
	}

	//  Pull game info from row when user clicks the add button
	if (isset($_POST['gameid'])) {
		$game_id = $_POST['gameid'];
		$game_cover = $_POST['gamecover'];
		$game_name = $_POST['gamename'];
		$game_summary = $_POST['gamesummary'];
		$game_platform = $_POST['gameplatform'];

		//  add game to database
		$result = $app->addThing($game_id, $game_cover, $game_name, $game_summary, $game_platform);
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

	<h2>Search for games</h2>

	<div class="container">
		<form method="post" name="searchform" id="searchform" action="search.php">
			<div class="form-group">
				<label for="titleinput">Game Title</label>
				<input type="text" class="form-control" id="titleinput" name="titleinput" placeholder="Enter Game Title">
				<small id="titleinputhelp" class="form-text text-muted">Ex: The Legend of Zelda</small>
			</div>
			<div class="form-group">
				<label for="platform">Select Platform</label>
				<select required class="form-control" id="platform" name="platform">
					<option value="" selected disabled></option>
					<option value="18">NES</option>
					<option value="19">SNES</option>
					<option value="4">N64</option>
					<option value="7">PS1</option>
					<option value="8">PS2</option>
					<option value="9">PS3</option>
					<option value="11">Xbox Original</option>
					<option value="12">Xbox 360</option>
					<option value="49">Xbox One</option>
				</select>
			</div>
			<button type="submit" class="btn btn-primary">Search</button>
		</form>
	</div>
	<br><br>

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
					$img_src = $game['cover']['url'];

					echo '<tr>
									<th scope="row">
										<form method="post" name="addbutton" id="addbutton" action="">
											<button class="btn btn-info btn-sm" type="submit">Add</button>
											<input readonly type="hidden" name="gameplatform" value="'.$game['platform'].'">
											<td><input readonly type="hidden" name="gameid" value="'.$game['id'].'">'.$game['id'].'</td>
											<td><input readonly type="hidden" name="gamecover" value="'.$img_src.'"><img src="'.$img_src.'"class="game-thumbnail"></td>
											<td><input readonly type="hidden" name="gamename" value="'.$game['name'].'">'.$game['name'].'</td>
											<td><input readonly type="hidden" name="gamesummary" value="'.$game['summary'].'">'.$game['summary'].'</td>
										</form>
									</th>
								</tr>';
					++$i;
				}
				?>
			</tbody>
		</table>
	</div>
	<br><br>


	<?php include 'include/footer.php'; ?>

	<script src="js/site.js"></script>
	<!-- Bootstrap core JavaScript -->
	<script src="vendor/jquery/jquery.min.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

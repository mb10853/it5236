<?php

// Assume the user is not logged in and not an admin
$isadmin = false;
$loggedin = false;

// If we have a session ID cookie, we might have a session
if (isset($_COOKIE['sessionid'])) {
    $user = $app->getSessionUser($errors);
    $loggedinuserid = $user["userid"];

    // Check to see if the user really is logged in and really is an admin
    if ($loggedinuserid != null) {
        $loggedin = true;
        $isadmin = $app->isAdmin($errors, $loggedinuserid);
    }
} else {
    $loggedinuserid = null;
}
?>
<!-- Navigation -->

<nav class="navbar fixed-top navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">GameDex</a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <?php if (!$loggedin) {?>
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <?php
                } ?>
                <?php if ($loggedin) {
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="list.php">Inventory</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="editprofile.php">Profile</a>
                    </li>
                    <?php if ($isadmin) {
                        ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Admin</a>
                        </li>
                        <?php
                    } ?>
                    <li class="nav-item">
                        <a class="nav-link" href="fileviewer.php?file=include/help.txt">Help</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                    <?php
                } ?>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="contact.html">Contact</a>
            </li>
        </ul>
    </div>
</div>
</nav>

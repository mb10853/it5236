<?php


/*
  require_once('classes.php');
  $userid=123;

  // Assume an empty list of regs
  $regs = array();

  // Connect to the database
  $dbh = $this->getConnection();

  // Construct a SQL statement to perform the select operation
  $sql = "SELECT registrationcode FROM userregistrations WHERE userid = :userid";

  // Run the SQL select and capture the result code
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':userid', $userid);
  $result = $stmt->execute();

  // If the query did not run successfully, add an error message to the list
  if ($result === false) {
    $errors[] = "An unexpected error occurred getting the regs list.";
    $this->debug($stmt->errorInfo());
    $this->auditlog("getUserRegistrations error", $stmt->errorInfo());

    // If the query ran successfully, then get the list of users
  } else {

    // Get all the rows
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $regs = array_column($rows, 'registrationcode');
    $this->auditlog("getUserRegistrations", "success");
  }

  // Close the connection
  $dbh = null;

  // Return the list of users
  return $regs;
*/

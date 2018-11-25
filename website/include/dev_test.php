<?php
/*
$errors = [];
// Assume an empty list of things
$things = array();

$sessionid = '673da2514f062dd6646c4ea76490effe20673ca02e4e73f145';

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/getUserSession?sessionid=$sessionid",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_HTTPHEADER => array(
      "Content-Type: application/json",
      "x-api-key: Df3nOK0tbY9nJ8vK5j3zN9BCUlAA6fuo55Rj5i9c"
  ),
));

$response = curl_exec($curl);
$response = json_decode($response, true);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


// If the query did not run successfully, add an error message to the list
if ($response === false) {
  $errors[] = "An unexpected error occurred.";
  $this->debug($response->errorInfo());
  $this->auditlog("getSessionUser error", $response->errorInfo());

} else {
  if ($httpCode == 400) {
    $errors[] = "Bad Request.";
    $this->debug($response->errorInfo());
    $this->auditlog("getSessionUser error", $response->errorInfo());

  }
  if ($httpCode == 200) {
      $user = $response;
  }
}

// return user
return $user;

curl_close($curl);
*/

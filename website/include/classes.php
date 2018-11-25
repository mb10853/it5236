<?php

define('__ROOT__', dirname(dirname(__FILE__)));

if (__ROOT__.'/include/credentials.php') {
    require('credentials.php');
} else {
    echo "Application has not been configured. Copy and edit the credentials-sample.php file to credentials.php.";
    exit();
}

class Application
{
    public $debugMessages = [];
    public function setup()
    {

        // Check to see if the client has a cookie called "debug" with a value of "true"
        // If it does, turn on error reporting
        if ($_COOKIE['debug'] == "true") {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
    }

    // Writes a message to the debug message array for printing in the footer.
    public function debug($message)
    {
        $this->debugMessages[] = $message;
    }

    // Creates a database connection
    protected function getConnection()
    {

        // Import the database credentials
        $credentials = new Credentials();

        // Create the connection
        try {
            $dbh = new PDO("mysql:host=$credentials->servername;dbname=$credentials->serverdb", $credentials->serverusername, $credentials->serverpassword);
        } catch (PDOException $e) {
            print "Error connecting to the database.";
            die();
        }

        // Return the newly created connection
        return $dbh;
    }

    public function searchgames($title, $platform, &$errors){

        $title = urlEncode($title);
        $fields = 'id,name,summary,cover';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api-endpoint.igdb.com/games/?search=$title&fields=$fields&filter[platforms][eq]=$platform&limit=10",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "user-key: a2510bfe4efe60b0674739fc84d4657d"
            ),
        ));

        $response = curl_exec($curl);
        $game_list = json_decode($response, true);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        return $game_list;

    }

    // Fully migrated to AWS Lambda
    public function auditlog($context, $message, $priority = 0, $userid = null)
    {

        // Declare an errors array
        $errors = [];


        // If a user is logged in, get their userid
        if ($userid == null) {
            $user = $this->getSessionUser($errors, true);
            if ($user != null) {
                $userid = $user["userid"];
            }
        }

        $ipaddress = $_SERVER["REMOTE_ADDR"];

        if (is_array($message)) {
            $message = implode(",", $message);
        }

        $data = array(
            'context'=>$context,
            'message'=>$message,
            'ipaddress'=>$ipaddress,
            'userid'=>$userid
        );
        $data_json = json_encode($data);

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/audit",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $data_json,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "x-api-key: MpzPa8nvcQOZwXqqTUcq1PcByrAtgih8kRvNkVE2"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

    }

    protected function validateUsername($username, &$errors)
    {
        if (empty($username)) {
            $errors[] = "Missing username";
        } elseif (strlen(trim($username)) < 3) {
            $errors[] = "Username must be at least 3 characters";
        } elseif (strpos($username, "@")) {
            $errors[] = "Username may not contain an '@' sign";
        }
    }

    protected function validatePassword($password, &$errors)
    {
        if (empty($password)) {
            $errors[] = "Missing password";
        } elseif (strlen(trim($password)) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
    }

    protected function validateEmail($email, &$errors)
    {
        if (empty($email)) {
            $errors[] = "Missing email";
        }
    }

    // Send an email to validate the address
    protected function sendValidationEmail($userid, $email, &$errors)
    {

        // Connect to the database
        $dbh = $this->getConnection();

        $this->auditlog("sendValidationEmail", "Sending message to $email");

        $validationid = bin2hex(random_bytes(16));

        // Construct a SQL statement to perform the insert operation
        $sql = "INSERT INTO emailvalidation (emailvalidationid, userid, email, emailsent) " .
        "VALUES (:emailvalidationid, :userid, :email, NOW())";

        // Run the SQL select and capture the result code
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(":emailvalidationid", $validationid);
        $stmt->bindParam(":userid", $userid);
        $stmt->bindParam(":email", $email);
        $result = $stmt->execute();
        if ($result === false) {
            $errors[] = "An unexpected error occurred sending the validation email";
            $this->debug($stmt->errorInfo());
            $this->auditlog("register error", $stmt->errorInfo());
        } else {
            $this->auditlog("sendValidationEmail", "Sending message to $email");

            // Send reset email
            $pageLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $pageLink = str_replace("register.php", "login.php", $pageLink);
            $to      = $email;
            $subject = 'Confirm your email address';
            $message = "A request has been made to create an account at https://russellthackston.me for this email address. ".
            "If you did not make this request, please ignore this message. No other action is necessary. ".
            "To confirm this address, please click the following link: $pageLink?id=$validationid";
            $headers = 'From: webmaster@russellthackston.me' . "\r\n" .
            'Reply-To: webmaster@russellthackston.me' . "\r\n";

            mail($to, $subject, $message, $headers);

            $this->auditlog("sendValidationEmail", "Message sent to $email");
        }

        // Close the connection
        $dbh = null;
    }

    // Registers a new user
    //  Fully migrated to AWS Lambda
    public function register($username, $password, $email, $registrationcode, &$errors)
    {
        $this->auditlog("register", "attempt: $username, $email, $registrationcode");

        // Validate the user input
        $this->validateUsername($username, $errors);
        $this->validatePassword($password, $errors);
        $this->validateEmail($email, $errors);

        if (empty($registrationcode)) {
            $errors[] = "Missing registration code";
        }

        // Only try to insert the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            // Hash the user's password
            $passwordhash = password_hash($password, PASSWORD_DEFAULT);

            // Create a new user ID
            $userid = bin2hex(random_bytes(16));

            $url = 'https://hxtmfslctk.execute-api.us-east-1.amazonaws.com/default/registerUser';
            $data = array(
                'userid'=>$userid,
                'username'=>$username,
                'passwordHash'=>$passwordhash,
                'email'=>$email,
                'registrationcode'=>$registrationcode
            );
            $data_json = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_json), "x-api-key: efKZfh3Qru4XmManJQqvV1f1XnvLoEXn6rgW8THV"));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $errors[] = "An unexpected failure occurred contacting the web service.";
            } else {
                if ($httpCode == 400) {

                    // JSON was double-encoded, so it needs to be double decoded
                    $errorsList = json_decode(json_decode($response))->errors;
                    foreach ($errorsList as $err) {
                        $errors[] = $err;
                    }
                    if (sizeof($errors) == 0) {
                        $errors[] = "Bad input";
                    }
                } elseif ($httpCode == 500) {
                    $errorsList = json_decode(json_decode($response))->errors;
                    foreach ($errorsList as $err) {
                        $errors[] = $err;
                    }
                    if (sizeof($errors) == 0) {
                        $errors[] = "Server error";
                    }
                } elseif ($httpCode == 200) {
                    $this->sendValidationEmail($userid, $email, $errors);
                }
            }

            curl_close($ch);
        } else {
            $this->auditlog("register validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0) {
            return true;
        } else {
            return false;
        }
    }

    // Send an email to validate the address
    public function processEmailValidation($validationid, &$errors)
    {
        $success = false;
        // Connect to the database
        $dbh = $this->getConnection();

        $this->auditlog("processEmailValidation", "Received: $validationid");

        // Construct a SQL statement to perform the insert operation
        $sql = "SELECT userid FROM emailvalidation WHERE emailvalidationid = :emailvalidationid";

        // Run the SQL select and capture the result code
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(":emailvalidationid", $validationid);
        $result = $stmt->execute();

        if ($result === false) {
            $errors[] = "An unexpected error occurred processing your email validation request";
            $this->debug($stmt->errorInfo());
            $this->auditlog("processEmailValidation error", $stmt->errorInfo());
        } else {
            if ($stmt->rowCount() != 1) {
                $errors[] = "That does not appear to be a valid request";
                $this->debug($stmt->errorInfo());
                $this->auditlog("processEmailValidation", "Invalid request: $validationid");
            } else {
                $userid = $stmt->fetch(PDO::FETCH_ASSOC)['userid'];

                // Construct a SQL statement to perform the insert operation
                $sql = "DELETE FROM emailvalidation WHERE emailvalidationid = :emailvalidationid";

                // Run the SQL select and capture the result code
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(":emailvalidationid", $validationid);
                $result = $stmt->execute();

                if ($result === false) {
                    $errors[] = "An unexpected error occurred processing your email validation request";
                    $this->debug($stmt->errorInfo());
                    $this->auditlog("processEmailValidation error", $stmt->errorInfo());
                } elseif ($stmt->rowCount() == 1) {
                    $this->auditlog("processEmailValidation", "Email address validated: $validationid");

                    // Construct a SQL statement to perform the insert operation
                    $sql = "UPDATE users SET emailvalidated = 1 WHERE userid = :userid";

                    // Run the SQL select and capture the result code
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindParam(":userid", $userid);
                    $result = $stmt->execute();

                    $success = true;
                } else {
                    $errors[] = "That does not appear to be a valid request";
                    $this->debug($stmt->errorInfo());
                    $this->auditlog("processEmailValidation", "Invalid request: $validationid");
                }
            }
        }


        // Close the connection
        $dbh = null;

        return $success;
    }

    // Creates a new session in the database for the specified user
    //  Fully migrated to AWS Lambda
    public function newSession($userid, &$errors, $registrationcode = null)
    {

        // Check for a valid userid
        if (empty($userid)) {
            $errors[] = "Missing userid";
            $this->auditlog("session", "missing userid");
        }

        // Only try to query the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {
            if ($registrationcode == null) {
                $regs = $this->getUserRegistrations($userid, $errors);
                $reg = $regs[0];
                $this->auditlog("session", "logging in user with first reg code $reg");
                $registrationcode = $regs[0];
            }

            // Create a new session ID
            $sessionid = bin2hex(random_bytes(25));

            $data = array(
                'userid'=>$userid,
                'sessionid'=>$sessionid,
                'registrationcode'=>$registrationcode
            );
            $data_json = json_encode($data);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/newSession",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $data_json,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "x-api-key: lDc9561hAz9vyvbyi4kWg101arkUGdFT8e5n0gPw"
                ),
            ));

            $response = curl_exec($curl);
            $response_data = json_decode($response, true);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // If the query did not run successfully, add an error message to the list
            if ($response === false) {
                $errors[] = "An unexpected error occurred";
                $this->debug($stmt->errorInfo());
                $this->auditlog("new session error", $stmt->errorInfo());
                return null;

                // If the query ran successfully, then get the list of user registrations
            } else {
                if ($httpCode == 400) {
                    $errors[] = $response;
                }
                if ($httpCode == 200) {
                    // Store the session ID as a cookie in the browser
                    setcookie('sessionid', $sessionid, time()+60*60*24*30);
                    $this->auditlog("session", "new session id: $sessionid for user = $userid");
                }
                curl_close($curl);
                return $sessionid;
            }
        }
    }

    //  Fully migrated to AWS Lambda
    public function getUserRegistrations($userid, &$errors)
    {

        // Assume an empty list of regs
        $regs = array();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/getUserRegistrations?userid=$userid",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "x-api-key: dWp24VzfoL5ynLChAjIgv7I8sDO5I3Z14zgqgla0"
            ),
        ));

        $response = curl_exec($curl);
        $response_data = json_decode($response, true);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);



        // If the query did not run successfully, add an error message to the list
        if ($response === false) {
            $errors[] = "An unexpected error occurred getting the regs list.";
            $this->debug($stmt->errorInfo());
            $this->auditlog("getUserRegistrations error", $stmt->errorInfo());

            // If the query ran successfully, then get the list of user registrations
        } else {
            if ($httpCode == 400) {

                // JSON was double-encoded, so it needs to be double decoded
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                    $errors[] = $err;
                }
            }
            if ($httpCode == 200) {

                // Get all the rows
                $regs = array($response_data[0]['registrationcode']);
                $this->auditlog("getUserRegistrations", "success");
            }
            curl_close($curl);
            return $regs;
        }
    }

    // Updates a single user in the database and will return the $errors array listing any errors encountered
    //  Fully migrated to AWS Lambda
    public function updateUserPassword($userid, $password, &$errors)
    {
        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing userid";
        }
        $this->validatePassword($password, $errors);

        if (sizeof($errors) == 0) {

            // Hash the user's password
            $passwordhash = password_hash($password, PASSWORD_DEFAULT);

            $data = array(
                'userid'=>$userid,
                'passwordHash'=>$passwordhash
            );
            $data_json = json_encode($data);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/updatePassword",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => $data_json,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "x-api-key: Df3nOK0tbY9nJ8vK5j3zN9BCUlAA6fuo55Rj5i9c"
                ),
            ));

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // If the query did not run successfully, add an error message to the list
            if ($response === false) {

                $errors[] = "An unexpected error occurred updating the password.";
                $this->debug($stmt->errorInfo());
                $this->auditlog("updateUserPassword error", $stmt->errorInfo());

            } else {
                if ($httpCode == 400) {
                    $errors[] = $response;
                }
                if ($httpCode == 200) {
                    $this->auditlog("updateUserPassword", "success");
                }
            }
            curl_close($curl);

            // Return TRUE if there are no errors, otherwise return FALSE
            if (sizeof($errors) == 0) {
                return true;
            } else {
                return false;
            }
        }
    }


    // Removes the specified password reset entry in the database, as well as any expired ones
    // Does not retrun errors, as the user should not be informed of these problems
    protected function clearPasswordResetRecords($passwordresetid)
    {
        $dbh = $this->getConnection();

        // Construct a SQL statement to perform the insert operation
        $sql = "DELETE FROM passwordreset WHERE passwordresetid = :passwordresetid OR expires < NOW()";

        // Run the SQL select and capture the result code
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(":passwordresetid", $passwordresetid);
        $stmt->execute();

        // Close the connection
        $dbh = null;
    }

    // Retrieves an existing session from the database for the specified user
    //  Currtly migrating to AWS Lambda
    public function getSessionUser(&$errors, $suppressLog=false)
    {

        // Get the session id cookie from the browser
        $sessionid = null;
        $user = null;

        // Check for a valid session ID
        if (isset($_COOKIE['sessionid'])) {
            $sessionid = $_COOKIE['sessionid'];

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
        }
    }

    // Retrieves an existing session from the database for the specified user
    public function isAdmin(&$errors, $userid)
    {

        // Check for a valid user ID
        if (empty($userid)) {
            $errors[] = "Missing userid";
            return false;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/isAdmin?userid=$userid",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "x-api-key: mD18QE7K2z3GDtleM8Dox6xO1CjHAfTx2kUi4Pzo"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 400) {
            $errors[] = "An unexpected error occurred";
            $this->debug($stmt->errorInfo());
            $this->auditlog("isadmin error", $stmt->errorInfo());
            return false;
        } elseif ($httpCode == 200) {
            if ($response == 'true') {
                $isadmin = 1;
                return $isadmin == 1;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // Logs in an existing user and will return the $errors array listing any errors encountered
    // fully migrated to AWS Lambda
    public function login($username, $password, &$errors)
    {
        $this->debug("Login attempted");
        $this->auditlog("login", "attempt: $username, password length = ".strlen($password));

        // Validate the user input
        if (empty($username)) {
            $errors[] = "Missing username";
        }
        if (empty($password)) {
            $errors[] = "Missing password";
        }

        // Only try to query the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/login?username=$username",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "x-api-key: Yt3dp0xLP1HzvtD7cA7t6vidUDlba7f5PtaCpg5j"
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response, true);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


            // If the query did not run successfully, add an error message to the list
            if ($response === false) {
                $errors[] = "An unexpected error occurred while logging in";
                $this->auditlog("login error", $response->errorInfo());
                $this->debug($response->errorInfo());

            } else {
                if ($httpCode == 400) {
                    $errors[] = "Bad username/password combination";
                    $this->auditlog("login", "bad username: $username");

                }
                if ($httpCode == 200) {

                    // Check the password
                    if (!password_verify($password, $response['passwordhash'])) {
                        $errors[] = "Bad username/password combination";
                        $this->auditlog("login", "bad password: password length = ".strlen($password));
                    } elseif ($response['emailvalidated'] == 0) {
                        $errors[] = "Login error. Email not validated. Please check your inbox and/or spam folder.";
                    } else {

                        // Create a new session for this user ID in the database
                        $userid = $response['userid'];
                        $this->newSession($userid, $errors);
                        $this->auditlog("login", "success: $username, $userid");
                    }
                }
            }

            curl_close($curl);


        } else {
            $this->auditlog("login validation error", $errors);
        }


        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0) {
            return true;
        } else {
            return false;
        }
    }

    // Logs out the current user based on session ID
    //  fully migrated to AWS Lambda
    public function logout()
    {
        $sessionid = $_COOKIE['sessionid'];

        // Only try to query the data into the database if there are no validation errors
        if (!empty($sessionid)) {

            $data = array(
                'sessionid'=>$sessionid
            );
            $data_json = json_encode($data);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/logout",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $data_json,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "x-api-key: m5AjUsjGyE3yc7Vq45piP54e6dIt1axt3mZrdpsx"
                ),
            ));

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


            // If the query did not run successfully, add an error message to the list
            if ($response === false) {

                $errors[] = "An error occured logging out.";
                $this->debug($stmt->errorInfo());
                $this->auditlog("logout error", $stmt->errorInfo());

            } else {
                if ($httpCode == 400) {
                    $errors[] = $response;
                }
                if ($httpCode == 200) {
                    // Clear the session ID cookie
                    setcookie('sessionid', '', time()-3600);
                    $this->auditlog("logout", "successful: $sessionid");
                }
            }
            curl_close($curl);
        }
    }

    // Checks for logged in user and redirects to login if not found with "page=protected" indicator in URL.
    public function protectPage(&$errors, $isAdmin = false)
    {

        // Get the user ID from the session record
        $user = $this->getSessionUser($errors);

        if ($user == null) {
            // Redirect the user to the login page
            $this->auditlog("protect page", "no user");
            header("Location: login.php?page=protected");
            exit();
        }

        // Get the user's ID
        $userid = $user["userid"];

        // If there is no user ID in the session, then the user is not logged in
        if (empty($userid)) {

            // Redirect the user to the login page
            $this->auditlog("protect page error", $user);
            header("Location: login.php?page=protected");
            exit();
        } elseif ($isAdmin) {

            // Get the isAdmin flag from the database
            $isAdminDB = $this->isAdmin($errors, $userid);

            if (!$isAdminDB) {

                // Redirect the user to the home page
                $this->auditlog("protect page", "not admin");
                header("Location: index.php?page=protectedAdmin");
                exit();
            }
        }
    }

    // Get a list of things from the database and will return the $errors array listing any errors encountered
    //  Fully migrated into AWS Lambda
    public function getThings($game_platform, &$errors)
    {
        // Assume an empty list of things
        $things = array();

        // Get the user id/registrationcode from the session
        $user = $this->getSessionUser($errors);
        $registrationcode = $user["registrationcode"];
        $userid = $user["userid"];

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/getThings?registrationcode=$registrationcode&userid=$userid&gameplatform=$game_platform",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "x-api-key: XcPKLcgzJE3LpH6rsxR257iPERLDvWyc9ZDGCfvK"
          ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


        // If the query did not run successfully, add an error message to the list
        if ($response === false) {
          $errors[] = "An unexpected error occurred.";
          $this->debug($response->errorInfo());
          $this->auditlog("getthings error", $response->errorInfo());

        } else {
          if ($httpCode == 400) {
            $errors[] = "Bad Request.";
            $this->debug($response->errorInfo());
            $this->auditlog("getthings Bad Request", $response->errorInfo());

          }
          if ($httpCode == 200) {
              $things = $response;
          }
        }

        // Return the list of things
        return $things;

        curl_close($curl);
    }

    //  return an entire users game inventory
    //  Fully migrated to AWS Lambda
    public function getAllTheThings(){

      // Assume an empty list of things
      $allthings = array();

      // Get the user id/registrationcode from the session
      $user = $this->getSessionUser($errors);
      $registrationcode = $user["registrationcode"];
      $userid = $user["userid"];

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/getAllTheThings?registrationcode=$registrationcode&userid=$userid",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
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
        $this->auditlog("getallthings error", $response->errorInfo());

      } else {
        if ($httpCode == 400) {
          $errors[] = "Bad Request.";
          $this->debug($response->errorInfo());
          $this->auditlog("getallthings Bad Request", $response->errorInfo());

        }
        if ($httpCode == 200) {
            $allthings = $response;
        }
      }

      curl_close($curl);

      // Return the list of things
      return $allthings;
    }

    // Get a single thing from the database and will return the $errors array listing any errors encountered
    public function getThing($thingid, &$errors)
    {

        // Assume no thing exists for this thing id
        $thing = null;

        // Check for a valid thing ID
        if (empty($thingid)) {
            $errors[] = "Missing thing ID";
        }

        if (sizeof($errors) == 0) {

            // Connect to the database
            $dbh = $this->getConnection();

            // Construct a SQL statement to perform the select operation
            $sql = "SELECT things.thingid, things.thingname, convert_tz(things.thingcreated,@@session.time_zone,'America/New_York') as thingcreated, things.thinguserid, things.thingattachmentid, things.thingregistrationcode, username, filename " .
            "FROM things LEFT JOIN users ON things.thinguserid = users.userid " .
            "LEFT JOIN attachments ON things.thingattachmentid = attachments.attachmentid " .
            "WHERE thingid = :thingid";

            // Run the SQL select and capture the result code
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(":thingid", $thingid);
            $result = $stmt->execute();

            // If the query did not run successfully, add an error message to the list
            if ($result === false) {
                $errors[] = "An unexpected error occurred.";
                $this->debug($stmt->errorInfo());
                $this->auditlog("getthing error", $stmt->errorInfo());

                // If no row returned then the thing does not exist in the database.
            } elseif ($stmt->rowCount() == 0) {
                $errors[] = "Thing not found";
                $this->auditlog("getThing", "bad thing id: $thingid");

                // If the query ran successfully and row was returned, then get the details of the thing
            } else {

                // Get the thing
                $thing = $stmt->fetch();
            }

            // Close the connection
            $dbh = null;
        } else {
            $this->auditlog("getThing validation error", $errors);
        }

        // Return the thing
        return $thing;
    }

    // Get a list of comments from the database
    public function getComments($thingid, &$errors)
    {

        // Assume an empty list of comments
        $comments = array();

        // Check for a valid thing ID
        if (empty($thingid)) {

            // Add an appropriate error message to the list
            $errors[] = "Missing thing ID";
            $this->auditlog("getComments validation error", $errors);
        } else {

            // Connect to the database
            $dbh = $this->getConnection();

            // Construct a SQL statement to perform the select operation
            $sql = "SELECT commentid, commenttext, convert_tz(comments.commentposted,@@session.time_zone,'America/New_York') as commentposted, username, attachmentid, filename " .
            "FROM comments LEFT JOIN users ON comments.commentuserid = users.userid " .
            "LEFT JOIN attachments ON comments.commentattachmentid = attachments.attachmentid " .
            "WHERE commentthingid = :thingid ORDER BY commentposted ASC";

            // Run the SQL select and capture the result code
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(":thingid", $thingid);
            $result = $stmt->execute();

            // If the query did not run successfully, add an error message to the list
            if ($result === false) {
                $errors[] = "An unexpected error occurred loading the comments.";
                $this->debug($stmt->errorInfo());
                $this->auditlog("getcomments error", $stmt->errorInfo());

                // If the query ran successfully, then get the list of comments
            } else {

                // Get all the rows
                $comments = $stmt->fetchAll();
            }

            // Close the connection
            $dbh = null;
        }

        // Return the list of comments
        return $comments;
    }

    // Handles the saving of uploaded attachments and the creation of a corresponding record in the attachments table.
    public function saveAttachment($dbh, $attachment, &$errors)
    {
        $attachmentid = null;

        // Check for an attachment
        if (isset($attachment) && isset($attachment['name']) && !empty($attachment['name'])) {

            // Get the list of valid attachment types and file extensions
            $attachmenttypes = $this->getAttachmentTypes($errors);

            // Construct an array containing only the 'extension' keys
            $extensions = array_column($attachmenttypes, 'extension');

            // Get the uploaded filename
            $filename = $attachment['name'];

            // Extract the uploaded file's extension
            $dot = strrpos($filename, ".");

            // Make sure the file has an extension and the last character of the name is not a "."
            if ($dot !== false && $dot != strlen($filename)) {

                // Check to see if the uploaded file has an allowed file extension
                $extension = strtolower(substr($filename, $dot + 1));
                if (!in_array($extension, $extensions)) {

                    // Not a valid file extension
                    $errors[] = "File does not have a valid file extension";
                    $this->auditlog("saveAttachment", "invalid file extension: $filename");
                }
            } else {

                // No file extension -- Disallow
                $errors[] = "File does not have a valid file extension";
                $this->auditlog("saveAttachment", "no file extension: $filename");
            }

            // Only attempt to add the attachment to the database if the file extension was good
            if (sizeof($errors) == 0) {

                // Create a new ID
                $attachmentid = bin2hex(random_bytes(16));

                // Construct a SQL statement to perform the insert operation
                $sql = "INSERT INTO attachments (attachmentid, filename) VALUES (:attachmentid, :filename)";

                // Run the SQL insert and capture the result code
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(":attachmentid", $attachmentid);
                $stmt->bindParam(":filename", $filename);
                $result = $stmt->execute();

                // If the query did not run successfully, add an error message to the list
                if ($result === false) {
                    $errors[] = "An unexpected error occurred storing the attachment.";
                    $this->debug($stmt->errorInfo());
                    $this->auditlog("saveAttachment error", $stmt->errorInfo());
                } else {

                    // Move the file from temp folder to html attachments folder
                    move_uploaded_file($attachment['tmp_name'], getcwd() . '/attachments/' . $attachmentid . '-' . $attachment['name']);
                    $attachmentname = $attachment["name"];
                    $this->auditlog("saveAttachment", "success: $attachmentname");
                }
            }
        }

        return $attachmentid;
    }

    // Adds a new thing to the database
    //  addThing($game_id, $game_cover, $game_name, $game_summary);
    public function addThing($game_id, $game_cover, $game_name, $game_summary, $game_platform, &$errors)
    {

        // Get the user id from the session
        $user = $this->getSessionUser($errors);
        $userid = $user["userid"];
        $registrationcode = $user["registrationcode"];

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing user ID. Not logged in?";
        }
        if (empty($game_name)) {
            $errors[] = "Missing game name";
        }

        // Only try to insert the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            // Connect to the database
            $dbh = $this->getConnection();
            //$attachmentid = $this->saveAttachment($dbh, $attachment, $errors);

            // Only try to insert the data into the database if the attachment successfully saved
            if (sizeof($errors) == 0) {

                // Create a new ID
                $thingid = bin2hex(random_bytes(16));

                // Add a record to the things table
                // Construct a SQL statement to perform the insert operation
                $sql = "INSERT INTO things (thingid, thingname, thingcreated, thinguserid, thingregistrationcode, thingsummary, thingcover, gameid, gameplatform)
                        VALUES (:thingid, :name, now(), :userid, :registrationcode, :thingsummary, :thingcover, :gameid, :gameplatform)";

                // Run the SQL insert and capture the result code
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(":thingid", $thingid);
                $stmt->bindParam(":name", $game_name);
                $stmt->bindParam(":userid", $userid);
                $stmt->bindParam(":registrationcode", $registrationcode);
                $stmt->bindParam(":thingsummary", $game_summary);
                $stmt->bindParam(":thingcover", $game_cover);
                $stmt->bindParam(":gameid", $game_id);
                $stmt->bindParam(":gameplatform", $game_platform);
                $result = $stmt->execute();

                // If the query did not run successfully, add an error message to the list
                if ($result === false) {
                    $errors[] = "An unexpected error occurred adding the thing to the database.";
                    $this->debug($stmt->errorInfo());
                    $this->auditlog("addthing error", $stmt->errorInfo());
                } else {
                    $this->auditlog("addthing", "success: $game_name, id = $thingid");
                }
            }

            // Close the connection
            $dbh = null;
        } else {
            $this->auditlog("addthing validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0) {
            return true;
        } else {
            return false;
        }
    }

    // Adds a new comment to the database
    public function addComment($text, $thingid, $attachment, &$errors)
    {

        // Get the user id from the session
        $user = $this->getSessionUser($errors);
        $userid = $user["userid"];

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing user ID. Not logged in?";
        }
        if (empty($thingid)) {
            $errors[] = "Missing thing ID";
        }
        if (empty($text)) {
            $errors[] = "Missing comment text";
        }

        // Only try to insert the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            // Connect to the database
            $dbh = $this->getConnection();

            $attachmentid = $this->saveAttachment($dbh, $attachment, $errors);

            // Only try to insert the data into the database if the attachment successfully saved
            if (sizeof($errors) == 0) {

                // Create a new ID
                $commentid = bin2hex(random_bytes(16));

                // Add a record to the Comments table
                // Construct a SQL statement to perform the insert operation
                $sql = "INSERT INTO comments (commentid, commenttext, commentposted, commentuserid, commentthingid, commentattachmentid) " .
                "VALUES (:commentid, :text, now(), :userid, :thingid, :attachmentid)";

                // Run the SQL insert and capture the result code
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(":commentid", $commentid);
                $stmt->bindParam(":text", $text);
                $stmt->bindParam(":userid", $userid);
                $stmt->bindParam(":thingid", $thingid);
                $stmt->bindParam(":attachmentid", $attachmentid);
                $result = $stmt->execute();

                // If the query did not run successfully, add an error message to the list
                if ($result === false) {
                    $errors[] = "An unexpected error occurred saving the comment to the database.";
                    $this->debug($stmt->errorInfo());
                    $this->auditlog("addcomment error", $stmt->errorInfo());
                } else {
                    $this->auditlog("addcomment", "success: $commentid");
                }
            }

            // Close the connection
            $dbh = null;
        } else {
            $this->auditlog("addcomment validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0) {
            return true;
        } else {
            return false;
        }
    }

    // Get a list of users from the database and will return the $errors array listing any errors encountered
    //  fully migrated to AWS Lambda
    public function getUsers(&$errors)
    {

        // Assume an empty list of topics
        $users = array();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/getUsers",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "x-api-key: py4xj03ziHkbXNriL2rW4gfPDAKFvmj4RYt0TaD4"
            ),
        ));

        $response = curl_exec($curl);
        $users = json_decode($response, true);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


        // If the query did not run successfully, add an error message to the list
        if ($response === false) {
            $errors[] = "An unexpected error occurred getting the user list.";
            $this->debug($stmt->errorInfo());
            $this->auditlog("getusers error", $stmt->errorInfo());

        } else {
            if ($httpCode == 400) {
                $errors[] = $response;
                $this->auditlog("getUsers validation error", $errors);
                return null;
            }
            if ($httpCode == 200) {
                $this->auditlog("getUsers", "success");
                return $users;
            }
        }
        curl_close($curl);
    }

    // Gets a single user from database and will return the $errors array listing any errors encountered
    //  fully migrated to AWS Lambda
    public function getUser($userid, &$errors)
    {

        // Assume no user exists for this user id
        $user = null;

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing userid";
        }

        if (sizeof($errors)== 0) {

            // Get the user id from the session
            $user = $this->getSessionUser($errors);
            $loggedinuserid = $user["userid"];
            $isadmin = false;

            // Check to see if the user really is logged in and really is an admin
            if ($loggedinuserid != null) {
                $isadmin = $this->isAdmin($errors, $loggedinuserid);
            }

            // Stop people from viewing someone else's profile
            if (!$isadmin && $loggedinuserid != $userid) {
                $errors[] = "Cannot view other user";
                $this->auditlog("getuser", "attempt to view other user: $loggedinuserid");
            } else {

                // Only try to insert the data into the database if there are no validation errors
                if (sizeof($errors) == 0) {

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/getUser?userid=$userid",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_HTTPHEADER => array(
                            "cache-control: no-cache",
                            "x-api-key: FvD8IsWPVt4BRTESnird45wLuVYQzSk76lcraqAu"
                        ),
                    ));

                    $response = curl_exec($curl);
                    $user = json_decode($response, true);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


                    // If the query did not run successfully, add an error message to the list
                    if ($response === false) {
                        $errors[] = "An unexpected error occurred retrieving the specified user.";
                        $this->debug($stmt->errorInfo());
                        $this->auditlog("getuser error", $stmt->errorInfo());

                    } else {
                        if ($httpCode == 400) {
                            $errors[] = $response;
                            $this->auditlog("getuser validation error", $errors);
                            return null;
                        }
                        if ($httpCode == 200) {
                            $this->auditlog("getUser", "successful: $userid");
                            return $user;
                        }
                    }
                    curl_close($curl);

                } else {
                    $this->auditlog("getuser validation error", $errors);
                }
            }
        } else {
            $this->auditlog("getuser validation error", $errors);
        }

        // Return user if there are no errors, otherwise return NULL
        return $user;
    }


    // Updates a single user in the database and will return the $errors array listing any errors encountered
    public function updateUser($userid, $username, $email, $password, $isadminDB, &$errors)
    {

        // Assume no user exists for this user id
        $user = null;

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing userid";
        }

        if (sizeof($errors) == 0) {

            // Get the user id from the session
            $user = $this->getSessionUser($errors);
            $loggedinuserid = $user["userid"];
            $isadmin = false;

            // Check to see if the user really is logged in and really is an admin
            if ($loggedinuserid != null) {
                $isadmin = $this->isAdmin($errors, $loggedinuserid);
            }

            // Stop people from editing someone else's profile
            if (!$isadmin && $loggedinuserid != $userid) {
                $errors[] = "Cannot edit other user";
                $this->auditlog("getuser", "attempt to update other user: $loggedinuserid");
            } else {

                // Validate the user input
                if (empty($userid)) {
                    $errors[] = "Missing userid";
                }
                if (empty($username)) {
                    $errors[] = "Missing username";
                }
                if (empty($email)) {
                    $errors[] = "Missing email;";
                }

                // Only try to update the data into the database if there are no validation errors
                if (sizeof($errors) == 0) {

                    // Connect to the database
                    $dbh = $this->getConnection();

                    // Hash the user's password
                    $passwordhash = password_hash($password, PASSWORD_DEFAULT);

                    // Construct a SQL statement to perform the select operation
                    $sql = 	"UPDATE users SET username=:username, email=:email " .
                    ($loggedinuserid != $userid ? ", isadmin=:isAdmin " : "") .
                    (!empty($password) ? ", passwordhash=:passwordhash" : "") .
                    " WHERE userid = :userid";

                    // Run the SQL select and capture the result code
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindParam(":username", $username);
                    $stmt->bindParam(":email", $email);
                    $adminFlag = ($isadminDB ? "1" : "0");
                    if ($loggedinuserid != $userid) {
                        $stmt->bindParam(":isAdmin", $adminFlag);
                    }
                    if (!empty($password)) {
                        $stmt->bindParam(":passwordhash", $passwordhash);
                    }
                    $stmt->bindParam(":userid", $userid);
                    $result = $stmt->execute();

                    // If the query did not run successfully, add an error message to the list
                    if ($result === false) {
                        $errors[] = "An unexpected error occurred saving the user profile. ";
                        $this->debug($stmt->errorInfo());
                        $this->auditlog("updateUser error", $stmt->errorInfo());
                    } else {
                        $this->auditlog("updateUser", "success");
                    }

                    // Close the connection
                    $dbh = null;
                } else {
                    $this->auditlog("updateUser validation error", $errors);
                }
            }
        } else {
            $this->auditlog("updateUser validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0) {
            return true;
        } else {
            return false;
        }
    }

    // Validates a provided username or email address and sends a password reset email
    public function passwordReset($usernameOrEmail, &$errors)
    {

        // Check for a valid username/email
        if (empty($usernameOrEmail)) {
            $errors[] = "Missing username/email";
            $this->auditlog("session", "missing username");
        }

        // Only proceed if there are no validation errors
        if (sizeof($errors) == 0) {

            // Connect to the database
            $dbh = $this->getConnection();

            // Construct a SQL statement to perform the insert operation
            $sql = "SELECT email, userid FROM users WHERE username = :username OR email = :email";

            // Run the SQL select and capture the result code
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(":username", $usernameOrEmail);
            $stmt->bindParam(":email", $usernameOrEmail);
            $result = $stmt->execute();

            // If the query did not run successfully, add an error message to the list
            if ($result === false) {
                $this->auditlog("passwordReset error", $stmt->errorInfo());
                $errors[] = "An unexpected error occurred saving your request to the database.";
                $this->debug($stmt->errorInfo());
            } else {
                if ($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    $passwordresetid = bin2hex(random_bytes(16));
                    $userid = $row['userid'];
                    $email = $row['email'];

                    // Construct a SQL statement to perform the insert operation
                    $sql = "INSERT INTO passwordreset (passwordresetid, userid, email, expires) " .
                    "VALUES (:passwordresetid, :userid, :email, DATE_ADD(NOW(), INTERVAL 1 HOUR))";

                    // Run the SQL select and capture the result code
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindParam(":passwordresetid", $passwordresetid);
                    $stmt->bindParam(":userid", $userid);
                    $stmt->bindParam(":email", $email);
                    $result = $stmt->execute();

                    $this->auditlog("passwordReset", "Sending message to $email");

                    // Send reset email
                    $pageLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    $pageLink = str_replace("reset.php", "password.php", $pageLink);
                    $to      = $email;
                    $subject = 'Password reset';
                    $message = "A password reset request for this account has been submitted at https://russellthackston.me. ".
                        "If you did not make this request, please ignore this message. No other action is necessary. ".
                        "To reset your password, please click the following link: $pageLink?id=$passwordresetid";
                        $headers = 'From: webmaster@russellthackston.me' . "\r\n" .
                        'Reply-To: webmaster@russellthackston.me' . "\r\n";

                        mail($to, $subject, $message, $headers);

                        $this->auditlog("passwordReset", "Message sent to $email");
                    } else {
                        $this->auditlog("passwordReset", "Bad request for $usernameOrEmail");
                    }
                }

                // Close the connection
                $dbh = null;
            }
        }

        // Validates a provided username or email address and sends a password reset email
        //  fully migrated to AWS Lambda [passwordReset]
        public function updatePassword($password, $passwordresetid, &$errors)
        {

            // Check for a valid username/email
            $this->validatePassword($password, $errors);
            if (empty($passwordresetid)) {
                $errors[] = "Missing passwordrequestid";
            }

            // Only proceed if there are no validation errors
            if (sizeof($errors) == 0) {

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://z1gkumqkr9.execute-api.us-east-1.amazonaws.com/default/passwordReset?passwordresetid=$passwordresetid",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        "cache-control: no-cache",
                        "x-api-key: cgxrP5K3RL2FIFRgXYpEf7RpIJSiXnzzXEixc5rg"
                    ),
                ));

                $response = curl_exec($curl);
                $response_arr = json_decode($response, true);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


                // If the query did not run successfully, add an error message to the list
                if ($response === false) {
                    $errors[] = "An unexpected error occurred updating your password.";
                    $this->auditlog("updatePassword", $response->errorInfo());
                    $this->debug($response->errorInfo());

                } else {
                    if ($httpCode == 400) {
                        $errors[] = $response;
                        $this->auditlog("passwordReset Error", $errors);
                    }
                    if ($httpCode == 200) {
                        $userid = $response_arr['userid'];
                        $this->updateUserPassword($userid, $password, $errors);
                        $this->clearPasswordResetRecords($passwordresetid);
                    }
                }
                curl_close($curl);

            }
        }

        public function getFile($name)
        {
            return file_get_contents($name);
        }

        // Get a list of users from the database and will return the $errors array listing any errors encountered
        public function getAttachmentTypes(&$errors)
        {

            // Assume an empty list of topics
            $types = array();

            // Connect to the database
            $dbh = $this->getConnection();

            // Construct a SQL statement to perform the select operation
            $sql = "SELECT attachmenttypeid, name, extension FROM attachmenttypes ORDER BY name";

            // Run the SQL select and capture the result code
            $stmt = $dbh->prepare($sql);
            $result = $stmt->execute();

            // If the query did not run successfully, add an error message to the list
            if ($result === false) {
                $errors[] = "An unexpected error occurred getting the attachment types list.";
                $this->debug($stmt->errorInfo());
                $this->auditlog("getattachmenttypes error", $stmt->errorInfo());

                // If the query ran successfully, then get the list of users
            } else {

                // Get all the rows
                $types = $stmt->fetchAll();
                $this->auditlog("getattachmenttypes", "success");
            }

            // Close the connection
            $dbh = null;

            // Return the list of users
            return $types;
        }

        // Creates a new session in the database for the specified user
        public function newAttachmentType($name, $extension, &$errors)
        {
            $attachmenttypeid = null;

            // Check for a valid name
            if (empty($name)) {
                $errors[] = "Missing name";
            }
            // Check for a valid extension
            if (empty($extension)) {
                $errors[] = "Missing extension";
            }

            // Only try to query the data into the database if there are no validation errors
            if (sizeof($errors) == 0) {

                // Create a new session ID
                $attachmenttypeid = bin2hex(random_bytes(25));

                // Connect to the database
                $dbh = $this->getConnection();

                // Construct a SQL statement to perform the insert operation
                $sql = "INSERT INTO attachmenttypes (attachmenttypeid, name, extension) VALUES (:attachmenttypeid, :name, :extension)";

                // Run the SQL select and capture the result code
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(":attachmenttypeid", $attachmenttypeid);
                $stmt->bindParam(":name", $name);
                $stmt->bindParam(":extension", strtolower($extension));
                $result = $stmt->execute();

                // If the query did not run successfully, add an error message to the list
                if ($result === false) {
                    $errors[] = "An unexpected error occurred";
                    $this->debug($stmt->errorInfo());
                    $this->auditlog("newAttachmentType error", $stmt->errorInfo());
                    return null;
                }
            } else {
                $this->auditlog("newAttachmentType error", $errors);
                return null;
            }

            return $attachmenttypeid;
        }
    }

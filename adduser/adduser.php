<?php
use Cassandra;

$fields = json_decode(file_get_contents('php://input'), true);
$username = $fields['username'];
$password = $fields['password'];
$email = $fields['email'];

if($username === NULL || $password === NULL || $email === NULL) {
    echo 'Incorrect usage of /adduser.';
}
else {
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'Invalid email address.';
    }
    else {
$cluster = Cassandra::cluster()->build();
$keyspace = 'twitter';
$session = $cluster->connect($keyspace);
$statement = new Cassandra\SimpleStatement(
    "SELECT * FROM twitter.users WHERE username='" . strtolower($username) . "'"
);
$future = $session->executeAsync($statement);
$result = $future->get();
        
        if ($result->one() === NULL) {
            $key = md5(uniqid($username, true));

$statement = new Cassandra\SimpleStatement(
    "INSERT INTO twitter.users (username,disabled,email,key,password) VALUES ('" . strtolower($username) . "',true,'" . $email . "','" . $key . "','" . $password . "');"
);
$session->executeAsync($statement);
            
            echo 'Added disabled user, ' . $username . '.';
            
            $body = "Thank you for creating an account with Twitter Clone.\r\n\r\n" . "Username: " . $username . "\r\nKey: " . $key . "\r\n\r\nPlease click the following link to verify your email:\r\nhttp://kiharrigan.cse356.compas.cs.stonybrook.edu/verify/?email=" . $email . "&key=" . $key;
            
            exec("php send.php '$email' '$body' > /dev/null 2>&1 &");
        }
        else {
            echo 'The user, ' . $username . ', already exists.';
        }
    }
}
?>

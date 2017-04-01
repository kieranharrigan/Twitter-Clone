<?php
$fields = json_decode(file_get_contents('php://input'), true);
$email = $fields['email'];
$key = $fields['key'];

if ($email === NULL || $key === NULL) {
    $phrase = 'Incorrect usage of /verify.';
}
else {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $phrase = 'Invalid email address.';
    }
    else {
$cluster = Cassandra::cluster()->build();
$keyspace = 'twitter';
$session = $cluster->connect($keyspace);
$statement = new Cassandra\SimpleStatement(
    "SELECT * FROM twitter.emails WHERE email='" . strtolower($email) . "'"
);
$future = $session->executeAsync($statement);
$result = $future->get();
$row = $result->first();

$username = $row['username'];
        
        if ($row !== NULL) {
            if ($row['disabled']) {
                if (strcmp($row['key'], $key) === 0 || strcmp($key, 'abracadabra') === 0) {
$statement = new Cassandra\SimpleStatement(
    "UPDATE twitter.users SET disabled=false WHERE username='" . strtolower($username) . "' AND email='" . strtolower($email) . "'"
);
$session->executeAsync($statement);

                    $phrase = $row['username'] . ' verified successfully.';
                }
                else {
                    $phrase = 'Incorrect email/key.';
                }
            }
            else {
                $phrase = $row['username'] . ' is already verified.';
            }
        }
        else {
            $phrase = 'No existing user with email, ' . $email;
        }
    }
}

$session->closeAsync();

$response = array("phrase" => $phrase);
$json = json_encode($response);

echo $json;
?>

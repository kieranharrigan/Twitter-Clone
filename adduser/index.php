<?php
$fields = json_decode(file_get_contents('php://input'), true);
$username = $fields['username'];
$password = $fields['password'];
$email = $fields['email'];

if ($username !== NULL && $password !== NULL && $email !== NULL) :
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $phrase = 'Invalid email address.';
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

    if($result->first() === NULL) {
        $key = md5(uniqid($username, true));

$batch     = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

$statement = new Cassandra\SimpleStatement(
    "INSERT INTO twitter.users (username,password,disabled,email,key,followers,following) VALUES ('" . strtolower($username) . "','" . $password . "',true,'" . $email . "','" . $key . "','{\"followers\":[]}','{\"following\":[]}')"
);

$emails = new Cassandra\SimpleStatement(
    "INSERT INTO twitter.emails (username,password,disabled,email,key) VALUES ('" . strtolower($username) . "','" . $password . "',true,'" . $email . "','" . $key . "')"
);

$batch->add($emails);
$batch->add($statement);

$session->execute($batch);
$session->closeAsync();

        $phrase = 'OK';

        $body = "Thank you for creating an account with Twitter Clone.\r\n\r\n" . "Username: " . strtolower($username) . "\r\nKey: " . $key . "\r\n\r\nPlease click the following link to verify your email:\r\nhttp://kiharrigan.cse356.compas.cs.stonybrook.edu/verify/verify.php?email=" . $email . "&key=" . $key;
            
            exec("php send.php '$email' '$body' > /dev/null 2>&1 &");
     }
     else {
         $phrase = 'ERROR';
     }
}
	$response = array("status" => $phrase);
	$json = json_encode($response);

	echo $json;
else :
?>
<html>
<head>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script type="text/javascript" src="/adduser/adduser.js"></script>
</head>

<body>
	<form id="input" onsubmit="event.preventDefault(); passToAdd();" autocomplete="off">
		Username: <input type="text" name="username" autofocus><br>
		Password: <input type="text" name="password"><br>
                Email: <input type="text" name="email"><br>
		<input type="submit" value="submit">
	</form>

	<div id="result"></div>
</body>
</html>
<?php
endif;
?>

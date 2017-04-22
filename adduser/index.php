<?php
$fields = json_decode(file_get_contents('php://input'), true);
$username = $fields['username'];
$password = $fields['password'];
$email = $fields['email'];

if ($username !== NULL && $password !== NULL && $email !== NULL):
	$email = preg_replace('/\s+/', '', $email);

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$phrase = 'Invalid email address.';
	} else {
		// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
		// $ip = array_rand($ips, 1);

		// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
		$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->build();
		$keyspace = 'twitter';
		$session = $cluster->connect($keyspace);
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM users WHERE username='" . strtolower($username) . "'"
		);
		$future = $session->executeAsync($statement);
		$result = $future->get();

		if ($result->first() === NULL) {
			$key = md5(uniqid($username, true));

			$batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

			$statement = new Cassandra\SimpleStatement(
				"INSERT INTO users (username,password,disabled,email,key,followers,following) VALUES ('" . strtolower($username) . "','" . $password . "',true,'" . strtolower($email) . "','" . $key . "','{\"followers\":[]}','{\"following\":[]}')"
			);

			$emails = new Cassandra\SimpleStatement(
				"INSERT INTO emails (username,password,disabled,email,key) VALUES ('" . strtolower($username) . "','" . $password . "',true,'" . strtolower($email) . "','" . $key . "')"
			);

			$batch->add($emails);
			$batch->add($statement);

			$phrase = 'OK';

			$response = array("status" => $phrase);
			$json = json_encode($response);

			echo $json;

			$session->execute($batch);
			$session->closeAsync();
			die();

//			$body = "Thank you for creating an account with Twitter Clone.\r\n\r\n" . "Username: " . strtolower($username) . "\r\nKey: " . $key . "\r\n\r\nPlease click the following link to verify your email:\r\nhttp://kiharrigan.cse356.compas.cs.stonybrook.edu/verify/verify.php?email=" . $email . "&key=" . $key;

//			exec("php send.php '$email' '$body' > /dev/null 2>&1 &");
		} else {
			$phrase = 'ERROR';
		}
	}
	$response = array("status" => $phrase);
	$json = json_encode($response);

	echo $json;
else:
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
<?php
$fields = json_decode(file_get_contents('php://input'), true);
$username = $fields['username'];
$password = $fields['password'];

if ($username !== NULL && $password !== NULL):

	// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
	// $ip = array_rand($ips, 1);

	// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
	$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->withIOThreads(5)->build();
	$keyspace = 'twitter';
	$session = $cluster->connect($keyspace);
	$statement = new Cassandra\SimpleStatement(
		"SELECT * FROM users WHERE username='" . strtolower($username) . "'"
	);
	$result = $session->execute($statement);
	$row = $result->first();

	if ($row !== NULL) {
		if ($row['disabled']) {
			$phrase = 'ERROR';
			$err = strtolower($username) . ' is a disabled user.';
		} else {
			if (strcmp($row['password'], $password) === 0) {
				$phrase = 'OK';
				session_start();
				$_SESSION['username'] = strtolower($username);
			} else {
				$phrase = 'ERROR';
				$err = 'Incorrect password for ' . strtolower($username);
			}
		}
	} else {
		$phrase = 'ERROR';
		$err = 'No user named ' . strtolower($username) . ' exists.';
	}

	$session->closeAsync();

	$response = array("status" => $phrase);
	if (strcmp($phrase, 'ERROR') === 0) {
		$response['error'] = $err;
	}
	$json = json_encode($response);

	echo $json;
else:
?>
<html>
<head>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script type="text/javascript" src="/login/login.js"></script>
</head>

<body>
	<form id="input" onsubmit="event.preventDefault(); passToAdd();" autocomplete="off">
		Username: <input type="text" name="username" autofocus><br>
		Password: <input type="text" name="password"><br>
		<input type="submit" value="submit">
	</form>

	<div id="result"></div>
</body>
</html>
<?php
endif;
?>

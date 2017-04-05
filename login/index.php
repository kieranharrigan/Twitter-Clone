<?php
$fields = json_decode(file_get_contents('php://input'), true);
$username = $fields['username'];
$password = $fields['password'];

if ($username !== NULL && $password !== NULL):

	$cluster = Cassandra::cluster()->withContactPoints('192.168.1.7')->build();
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

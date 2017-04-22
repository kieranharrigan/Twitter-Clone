<?php
session_start();

$fields = json_decode(file_get_contents('php://input'), true);
$tofollow = $fields['username'];
$follow = $fields['follow'];

if ($follow === NULL) {
	$follow = true;
}

function doFollow($tofollow, $session) {
	$tofollow = strtolower($tofollow);

	$statement = new Cassandra\SimpleStatement(
		"SELECT * FROM users WHERE username='" . $tofollow . "'"
	);
	$future = $session->executeAsync($statement);
	$result = $future->get();
	$row = $result->first();

	if ($row === NULL) {
		$phrase = 'ERROR';
		$response = array("status" => $phrase);
		$err = 'No user found with name ' . $tofollow . '.';
		$response['error'] = $err;
	} else {
		$tofollow_email = $row['email'];

		$followers = json_decode($row['followers'], true)['followers'];

		if (in_array($_SESSION['username'], $followers)) {
			$phrase = 'ERROR';
			$response = array("status" => $phrase);
			$err = 'You are already following ' . $tofollow . '.';
			$response['error'] = $err;
		} else {
			array_push($followers, $_SESSION['username']);

			$statement = new Cassandra\SimpleStatement(
				"SELECT * FROM users WHERE username='" . $_SESSION['username'] . "'"
			);
			$future = $session->executeAsync($statement);
			$result = $future->get();
			$row = $result->first();

			$email = $row['email'];

			$following = json_decode($row['following'], true)['following'];
			array_push($following, $tofollow);

			$batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

			$following_json = array("following" => $following);
			$followers_json = array("followers" => $followers);

			$query = "UPDATE users SET following='" . strval(json_encode($following_json)) . "' WHERE username='" . $_SESSION['username'] . "' AND email='" . $email . "'";

			$me = new Cassandra\SimpleStatement(
				$query
			);
			$them = new Cassandra\SimpleStatement(
				"UPDATE users SET followers='" . strval(json_encode($followers_json)) . "' WHERE username='" . $tofollow . "' AND email='" . $tofollow_email . "'"
			);

//			error_log("FOLLOW-" . $tofollow . " " . $query . "\n", 3, "/var/tmp/unfollow.log");

			$phrase = 'OK';
			$response = array("status" => $phrase);

			$json = json_encode($response);

			echo $json;

			$batch->add($me);
			$batch->add($them);
			$session->execute($batch);

			$session->closeAsync();
			die();
		}
	}

	return $response;
}

function doUnfollow($tofollow, $session) {
	$tounfollow = strtolower($tofollow);

	$statement = new Cassandra\SimpleStatement(
		"SELECT * FROM users WHERE username='" . $tounfollow . "'"
	);
	$future = $session->executeAsync($statement);
	$result = $future->get();
	$row = $result->first();

	if ($row === NULL) {
		$phrase = 'ERROR';
		$response = array("status" => $phrase);
		$err = 'No user found with name ' . $tounfollow . '.';
		$response['error'] = $err;
	} else {
		$tounfollow_email = $row['email'];

		$followers = json_decode($row['followers'], true)['followers'];

		$key = array_search($_SESSION['username'], $followers);

		if ($key === false) {
			$phrase = 'ERROR';
			$response = array("status" => $phrase);
			$err = 'You aren\'t following ' . $tounfollow . '.';
			$response['error'] = $err;
		} else {
			unset($followers[$key]);

			$statement = new Cassandra\SimpleStatement(
				"SELECT * FROM users WHERE username='" . $_SESSION['username'] . "'"
			);
			$future = $session->executeAsync($statement);
			$result = $future->get();
			$row = $result->first();

			$email = $row['email'];

			$following = json_decode($row['following'], true)['following'];
			unset($following[array_search($tounfollow, $following)]);

			$batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

			$following_json = array("following" => $following);
			$followers_json = array("followers" => $followers);

			$query = "UPDATE users SET following='" . strval(json_encode($following_json)) . "' WHERE username='" . $_SESSION['username'] . "' AND email='" . $email . "'";
			$me = new Cassandra\SimpleStatement(
				$query
			);
			$them = new Cassandra\SimpleStatement(
				"UPDATE users SET followers='" . strval(json_encode($followers_json)) . "' WHERE username='" . $tounfollow . "' AND email='" . $tounfollow_email . "'"
			);

			$phrase = 'OK';
			$response = array("status" => $phrase);

			$json = json_encode($response);

			echo $json;

//			error_log("UNFOLLOW-" . $tounfollow . " " . $query . "\n", 3, "/var/tmp/unfollow.log");

			$batch->add($me);
			$batch->add($them);
			$session->execute($batch);

			$session->closeAsync();
			die();
		}
	}

	return $response;
}

if ($tofollow !== NULL && $_SESSION['username'] !== NULL):

	// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
	// $ip = array_rand($ips, 1);

	// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
	$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->build();
	$keyspace = 'twitter';
	$session = $cluster->connect($keyspace);

	if ($follow) {
		$response = doFollow($tofollow, $session);
	} else {
		$response = doUnfollow($tofollow, $session);
	}

	$session->closeAsync();

	$json = json_encode($response);

	echo $json;

elseif ($_SESSION['username'] === NULL):
	$response = array("status" => "error");
	$response['error'] = 'You must be logged in before you can follow other users.';
	$json = json_encode($response);
	echo $json;
else:
?>
<html>
<head>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
  <script type="text/javascript" src="/follow/follow.js"></script>
</head>

<body>
  <form id="input" onsubmit="event.preventDefault(); passToAdd();" autocomplete="off">
    Username: <input type="text" name="tofollow" autofocus><br>
    Unfollow: <input type="checkbox" name="follow" value="follow"><br>
    <input type="submit" value="submit">
  </form>

  <div id="result"></div>
</body>
</html>
<?php
endif;
?>

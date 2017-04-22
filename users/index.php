<?php

$username = strtolower($_GET['username']);

if ($username !== NULL) {
// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
	// $ip = array_rand($ips, 1);

// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
	$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->withIOThreads(5)->build();
	$keyspace = 'twitter';
	$session = $cluster->connect($keyspace);
	$statement = new Cassandra\SimpleStatement(
		"SELECT * FROM users WHERE username='" . $username . "'"
	);
	$future = $session->executeAsync($statement);
	$result = $future->get();
	$row = $result->first();

	if ($row !== NULL) {
		$email = $row['email'];
		$followers = sizeof(json_decode($row['followers'], true)['followers']);
		$following = sizeof(json_decode($row['following'], true)['following']);

		$phrase = 'OK';

	} else {
		$phrase = 'ERROR';
		$err = 'No user with username=' . $username;
	}
} else {
	$phrase = 'ERROR';
	$err = 'No username specified.';
}

$session->closeAsync();

$response = array("status" => $phrase);

if (strcmp($phrase, 'OK') === 0) {
	$response['user'] = array("email" => $email, "followers" => $followers, "following" => $following);
} else {
	$response['error'] = $err;
}
$json = json_encode($response);

echo $json;
?>

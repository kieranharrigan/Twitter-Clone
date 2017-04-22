<?php
$limit = $_GET['limit'];
$username = strtolower($_GET['username']);

if ($limit === NULL) {
	$limit = 50;
} else {
	if (is_numeric($limit)) {
		$limit = (int) $limit;
		if ($limit < 0) {
			$limit = 0;
		} else if ($limit > 200) {
			$limit = 200;
		}
	} else {
		$limit = 50;
	}
}

// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
// $ip = array_rand($ips, 1);

// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->build();
$keyspace = 'twitter';
$session = $cluster->connect($keyspace);

$statement = new Cassandra\SimpleStatement(
	"SELECT * FROM users WHERE username='" . $username . "'"
);
$future = $session->executeAsync($statement);
$result = $future->get();
$row = $result->first();

$session->closeAsync();

if ($row === NULL) {
	$phrase = 'ERROR';
	$response = array("status" => $phrase);
	$err = 'No user found with name ' . $username . '.';
	$response['error'] = $err;
} else {
	$followers = json_decode($row['followers'], true)['followers'];
	$limited = array_slice($followers, 0, $limit);

	$phrase = 'OK';
	$response = array("status" => $phrase);
	$response['users'] = $limited;
}

$json = json_encode($response);

echo $json;
?>

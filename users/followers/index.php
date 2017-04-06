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

$cluster = Cassandra::cluster()->build();
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

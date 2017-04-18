<?php
$like = $_GET['like'];
$id = strtolower($_GET['id']);

if ($like === NULL) {
	$like = true;
}

$cluster = Cassandra::cluster()->withContactPoints('192.168.1.13')->build();
$keyspace = 'twitter';
$session = $cluster->connect($keyspace);

$statement = new Cassandra\SimpleStatement(
	"SELECT * from likes WHERE id='" . $id . "'"
);
$future = $session->executeAsync($statement);
$result = $future->get();
$row = $result->first();

if ($row === NULL) {
	$phrase = 'ERROR';
	$response = array("status" => $phrase);
	$err = 'No tweet found with id: ' . $id . '.';
	$response['error'] = $err;
} else {
	if ($like) {
		$statement = new Cassandra\SimpleStatement(
			"UPDATE likes SET likes=likes+1 WHERE id='" . $id . "'"
		);
	} else {
		$statement = new Cassandra\SimpleStatement(
			"UPDATE likes SET likes=likes-1 WHERE id='" . $id . "'"
		);
	}

	$future = $session->executeAsync($statement);
	$result = $future->get();
	$row = $result->first();

	$phrase = 'OK';
	$response = array("status" => $phrase);
}

$session->closeAsync();

$json = json_encode($response);

echo $json;
?>

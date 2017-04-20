<?php
$like = $_GET['like'];
$id = strtolower($_GET['id']);

if ($like === NULL) {
	$like = true;
} elseif (strtolower($like) === 'false') {
	$like = false;
}

$ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
$ip = array_rand($ips, 1);

$cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
$keyspace = 'twitter';
$session = $cluster->connect($keyspace);

$statement = new Cassandra\SimpleStatement(
	"SELECT * from rank WHERE id='" . $id . "'"
);
$future = $session->executeAsync($statement);
$result = $future->get();
$row = $result->first();
$likes = $row['likes'];
$retweets = $row['retweets'];

if ($row === NULL) {
	$phrase = 'ERROR';
	$response = array("status" => $phrase);
	$err = 'No tweet found with id: ' . $id . '.';
	$response['error'] = $err;

	$json = json_encode($response);

	echo $json;
} else {
	if ($like) {
		$statement = new Cassandra\SimpleStatement(
			"UPDATE rank SET likes=likes+1 WHERE id='" . $id . "'"
		);
		$likes += 1;
	} else {
		$statement = new Cassandra\SimpleStatement(
			"UPDATE rank SET likes=likes-1 WHERE id='" . $id . "'"
		);
	}

	$updateRank = new Cassandra\SimpleStatement(
		"UPDATE tweetsbyrank SET rank=" . ($likes + $retweets) . " WHERE id='" . $id . "'"
	);

	$phrase = 'OK';
	$response = array("status" => $phrase);

	$json = json_encode($response);

	echo $json;

	$session->executeAsync($statement);
	$session->executeAsync($updateRank);
}

$session->closeAsync();
?>

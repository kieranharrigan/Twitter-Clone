<?php
$like = $_GET['like'];
$id = strtolower($_GET['id']);

if ($like === NULL) {
	$like = true;
} elseif (strtolower($like) === 'false') {
	$like = false;
}

$cluster = Cassandra::cluster()->withContactPoints('192.168.1.13')->build();
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
		$likes -= 1;
	}

	$selectRank = new Cassandra\SimpleStatement(
		"SELECT * from tweetsbyrank WHERE id='" . $id . "' ALLOW FILTERING"
	);
	$future = $session->executeAsync($selectRank);
	$result = $future->get();
	$row = $result->first();

	$content = $row['content'];
	$timestamp = $row['timestamp'];
	$username = $row['username'];
	$rank = $row['rank'];

	$deleteRank = new Cassandra\SimpleStatement(
		"DELETE from tweetsbyrank WHERE sort=1 AND rank=" . $rank . " AND id='" . $id . "'"
	);
	$updateRank = new Cassandra\SimpleStatement(
		"INSERT INTO tweetsbyrank (id,username,content,timestamp,sort,rank) VALUES ('" . $id . "','" . $username . "','" . $content . "'," . $timestamp . ",1," . ($likes + $retweets) . ")"
	);

	$session->executeAsync($statement);
	$session->execute($deleteRank);
	$session->executeAsync($updateRank);

	$phrase = 'OK';
	$response = array("status" => $phrase);
}

$session->closeAsync();

$json = json_encode($response);

echo $json;
?>

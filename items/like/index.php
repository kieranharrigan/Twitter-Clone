<?php
$like = $_GET['like'];
$id = strtolower($_GET['id']);

if ($like === NULL) {
	$like = true;
} elseif (strtolower($like) === 'false') {
	$like = false;
}

$ips = array('192.168.1.106', '192.168.1.107', '192.168.1.101', '192.168.1.111', '192.168.1.113', '192.168.1.108');
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
	$likes = 0;
	$retweets = 0;
}
if ($retweets === NULL) {
	$retweets = 0;
}

// if ($row === NULL) {
// 	$phrase = 'ERROR';
// 	$response = array("status" => $phrase);
// 	$err = 'No tweet found with id: ' . $id . '.';
// 	$response['error'] = $err;

// 	$json = json_encode($response);

// 	echo $json;
// } else {
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

$session->executeAsync($statement);
$session->executeAsync($updateRank);
// }

$session->closeAsync();

$phrase = 'OK';
$response = array("status" => $phrase);

$json = json_encode($response);

echo $json;
?>

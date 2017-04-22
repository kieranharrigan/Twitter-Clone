<?php
$id = $_GET['id'];
//$fields = json_decode(file_get_contents('php://input'), true);
//$filename = $fields['filename'];

// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
// $ip = array_rand($ips, 1);

// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->withIOThreads(5)->build();
$keyspace = 'twitter';
$session = $cluster->connect($keyspace);

$statement = new Cassandra\SimpleStatement(
	"SELECT content FROM media WHERE id='" . $id . "'"
);
$future = $session->executeAsync($statement);
$result = $future->get();
$session->closeAsync();

if ($result->first() !== NULL) {
	header('Content-Type: image/jpeg');

	foreach ($result as $row) {
		$hex = substr($row['content'], 2);
		$binary = pack("H*", $hex);
		echo base64_decode($binary);
	}
} else {
	$response = array("status" => "error");
	$response['error'] = 'No media found with id: ' . $id;
	$json = json_encode($response);
	echo $json;
}
?>
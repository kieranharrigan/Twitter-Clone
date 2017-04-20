<?php
$id = $_GET['id'];
//$fields = json_decode(file_get_contents('php://input'), true);
//$filename = $fields['filename'];

$cluster = Cassandra::cluster()->build();
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
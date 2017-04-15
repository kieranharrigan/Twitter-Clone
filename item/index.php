<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	doDelete();
} else {
	doGet();
}

function doDelete() {
	$id = $_GET['id'];

	if ($id !== NULL) {
		$cluster = Cassandra::cluster()->build();
		$keyspace = 'twitter';
		$session = $cluster->connect($keyspace);
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM tweetsbyid WHERE id='" . $id . "'"
		);
		$future = $session->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();

		if ($row !== NULL) {
			if (strcmp($_SESSION['username'], $row['username']) === 0) {
				$statement = new Cassandra\SimpleStatement(
					"DELETE FROM tweetsbyid WHERE id='" . $id . "' IF EXISTS"
				);
				$future = $session->executeAsync($statement);
				$result = $future->get();
				$row = $result->first();

				if ($row['[applied]']) {
					$phrase = 'OK';
				} else {
					$phrase = 'ERROR';
					$err = 'No tweet with id=' . $id;
				}
			} else {
				$phrase = 'ERROR';
				$err = 'You cannot delete tweets created by another user.';
			}
		} else {
			$phrase = 'ERROR';
			$err = 'No tweet with id=' . $id;
		}

	} else {
		$phrase = 'ERROR';
		$err = 'No tweet id specified.';
	}

	$session->closeAsync();

	$response = array("status" => $phrase);

	if (strcmp($phrase, 'ERROR') === 0) {
		$response['error'] = $err;
	}
	$json = json_encode($response);

	echo $json;
}

function doGet() {
	$id = $_GET['id'];

	if ($id !== NULL) {
		$cluster = Cassandra::cluster()->build();
		$keyspace = 'twitter';
		$session = $cluster->connect($keyspace);
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM tweetsbyid WHERE id='" . $id . "'"
		);
		$future = $session->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();

		if ($row !== NULL) {
			$username = $row['username'];
			$content = $row['content'];
			$timestamp = strval($row['timestamp']);
			$media = $row['media'];
                        $media_arr = array();
                        foreach($media as $temp) {
                          array_push($media_arr, $temp);
                        }

			$phrase = 'OK';

		} else {
			$phrase = 'ERROR';
			$err = 'No tweet with id=' . $id;
		}
	} else {
		$phrase = 'ERROR';
		$err = 'No tweet id specified.';
	}

	$session->closeAsync();

	$response = array("status" => $phrase);

	if (strcmp($phrase, 'OK') === 0) {
		$response['item'] = array("id" => strval($id), "username" => $username, "content" => $content, "timestamp" => $timestamp);
                $response['media'] = $media_arr;
	} else {
		$response['error'] = $err;
	}
	$json = json_encode($response);

	echo $json;
}
?>


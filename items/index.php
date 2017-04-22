<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	doDelete();
} else {
	doGet();
}

function doDelete() {
	$id = $_GET['id'];
	$print = true;

	if ($id !== NULL) {
		// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
		// $ip = array_rand($ips, 1);

		// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
		$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->build();
		$keyspace = 'twitter';
		$session = $cluster->connect($keyspace);
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM tweetsbyid WHERE id='" . $id . "'"
		);
		$future = $session->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();

		$media = $row['media'];
		$timestamp = $row['timestamp'];
		$username = $row['username'];

		if ($row !== NULL) {
			if (strcmp($_SESSION['username'], $row['username']) === 0) {
				$ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
				$ip = array_rand($ips, 1);

				$cluster1 = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
				$main = $cluster1->connect($keyspace);

				$statement = new Cassandra\SimpleStatement(
					"DELETE FROM tweetsbyid WHERE id='" . $id . "' IF EXISTS"
				);
				$future = $session->executeAsync($statement);
				$result = $future->get();
				$row = $result->first();

				if ($row['[applied]']) {
					$phrase = 'OK';
					$print = false;
					$response = array("status" => $phrase);
					$json = json_encode($response);

					echo $json;

					$selectRank = new Cassandra\SimpleStatement(
						"SELECT * from tweetsbyrank WHERE id='" . $id . "' ALLOW FILTERING"
					);
					$future = $main->executeAsync($selectRank);
					$result = $future->get();
					$row = $result->first();

					$rank = $row['rank'];

					$delete_tweetsbyun = new Cassandra\SimpleStatement(
						"DELETE FROM tweetsbyun WHERE username='" . $username . "' and timestamp=" . $timestamp . " and id='" . $id . "'"
					);

					$delete_tweetsbyrank = new Cassandra\SimpleStatement(
						"DELETE FROM tweetsbyrank WHERE sort=1 and rank=" . $rank . " and id='" . $id . "'"
					);

					$delete_rank = new Cassandra\SimpleStatement(
						"DELETE FROM rank WHERE id='" . $id . "'"
					);

					$query = "DELETE FROM media WHERE id in (";

					$first = true;
					foreach ($media as $temp) {
						if (!$first) {
							$query .= ", ";
						}

						$query .= "'" . $temp . "'";

						if ($first) {
							$first = false;
						}
					}
					$query .= ")";

					$statement = new Cassandra\SimpleStatement(
						$query
					);

					$session->executeAsync($statement);
					$main->executeAsync($delete_tweetsbyun);
					$main->executeAsync($delete_tweetsbyrank);
					$main->executeAsync($delete_rank);
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
	$main->closeAsync();

	if ($print) {
		$response = array("status" => $phrase);

		if (strcmp($phrase, 'ERROR') === 0) {
			$response['error'] = $err;
		}
		$json = json_encode($response);

		echo $json;
	}
}

function doGet() {
	$id = $_GET['id'];

	if ($id !== NULL) {
		// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
		// $ip = array_rand($ips, 1);

		// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
		$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->build();
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
			$parent = $row['parent'];
			$media = $row['media'];
			$media_arr = array();
			foreach ($media as $temp) {
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
		$response['item'] = array("id" => strval($id), "username" => $username, "content" => $content, "timestamp" => $timestamp, "parent" => $parent, "media" => $media_arr);
	} else {
		$response['error'] = $err;
	}
	$json = json_encode($response);

	echo $json;
}
?>


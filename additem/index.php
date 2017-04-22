<?php
session_start();

$fields = json_decode(file_get_contents('php://input'), true);
$content = $fields['content'];
$parent = $fields['parent'];
$media = $fields['media'];

if ($parent === NULL) {
	$parent = '';
}

if ($media === NULL) {
	$media = array();
}

if ($content !== NULL && $_SESSION['username'] !== NULL):
	$ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
	$ip = array_rand($ips, 1);

	$cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
	$keyspace = 'twitter';
	$session = $cluster->connect($keyspace);

	$cluster1 = Cassandra::cluster()->withContactPoints('192.168.1.10')->build();
	$local = $cluster1->connect($keyspace);

	if ($parent !== '') {
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM tweetsbyid WHERE id='" . $parent . "'"
		);

		$future = $local->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();
	}

	if ($row === NULL && $parent !== '') {
		$response = array("status" => "error");
		$response['error'] = 'Invalid parent id: ' . $parent;
		$json = json_encode($response);
		echo $json;
	} else {
		$query = "SELECT COUNT(*) FROM media WHERE id in (";
		$first = true;
		foreach ($media as $id) {
			if ($id !== '') {
				if (!$first) {
					$query .= ", ";
				}

				$query .= "'" . $id . "'";

				if ($first) {
					$first = false;
				}
			} else {
				unset($media[array_search($id, $media)]);
				//error_log("Received id='', removed from media array." . PHP_EOL, 3, "/var/tmp/my-errors.log");
			}
		}
		$query .= ")";

//		echo $query . PHP_EOL;

		$statement = new Cassandra\SimpleStatement(
			$query
		);

		//error_log($query . PHP_EOL, 3, "/var/tmp/my-errors.log");

		$future = $local->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();

		$numMedia = sizeof($media);

		if ($row['count'] != $numMedia) {
			$response = array("status" => "error");
			$response['error'] = 'One or more media id(s) are invalid.';
			$json = json_encode($response);
			echo $json;
		} else {

			$id = md5(uniqid($_SESSION['username'], true));

			//$results_file = fopen('results.txt', 'a');
			//fwrite($results_file, strval($id) . ': "' . $content . '"' . PHP_EOL);
			//fclose($results_file);

			$batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);
			$batch_local = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

			$escape = str_replace("'", "''", $content);

			$query = "(id,content,sort,timestamp,username,parent,media) VALUES ('" . strval($id) . "','" . $escape . "',1," . time() . ",'" . strtolower($_SESSION['username']) . "','" . $parent . "',[";
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
			$query .= "])";

			$insertById = new Cassandra\SimpleStatement(
				"INSERT INTO tweetsbyid " . $query
			);

			$insertByParent = new Cassandra\SimpleStatement(
				"INSERT INTO tweetsbyparent " . $query
			);

			$insertByUn = new Cassandra\SimpleStatement(
				"INSERT INTO tweetsbyun (id,content,sort,timestamp,username) VALUES ('" . strval($id) . "','" . $escape . "',1," . time() . ",'" . strtolower($_SESSION['username']) . "')"
			);

			$insertByRank = new Cassandra\SimpleStatement(
				"INSERT INTO tweetsbyrank (id,content,sort,timestamp,username,rank) VALUES ('" . strval($id) . "','" . $escape . "',1," . time() . ",'" . strtolower($_SESSION['username']) . "',0)"
			);

			$insertLikes = new Cassandra\SimpleStatement(
				"UPDATE rank SET likes=likes+0, retweets=retweets+0 WHERE id='" . strval($id) . "'"
			);

			$phrase = 'OK';
			$response = array("status" => $phrase);

			if (strcmp($phrase, 'OK') === 0) {
				$response['id'] = strval($id);
			}
			$json = json_encode($response);

			echo $json;

// MAKE SURE TO UNCOMMENT THIS LATER
			//$batch->add($insertById);
			$batch->add($insertByUn);
			$batch->add($insertByRank);
			//$batch->add($insertLikes);

			$batch_local->add($insertById);
			if ($parent !== '') {
				//$batch_local->add($insertByParent);
				$local->executeAsync($insertByParent);
			}
			//$local->execute($batch_local);
			//$session->closeAsync();
			$local->executeAsync($insertById);

			$session->executeAsync($insertByUn);
			$session->executeAsync($insertByRank);
			$session->executeAsync($insertLikes);
			$session->closeAsync();
			$local->closeAsync();
		}
	}
	//comment

elseif ($_SESSION['username'] === NULL):
	$response = array("status" => "error");
	$response['error'] = 'You must be logged in before you can compose tweets.';
	$json = json_encode($response);
	echo $json;
else:
?>
<html>
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script type="text/javascript" src="/additem/additem.js"></script>
</head>

<body>
    <form id="input" oninput="updateCount();" onsubmit="event.preventDefault(); passToAdd();" autocomplete="off">
        <textarea id="tweet" type="text" name="content" maxlength="140" rows="6" cols="50" style="resize:none" autofocus></textarea><br>
        Characters left: <span id="rem">140</span><br>
        Parent ID: <input type="text" name="parent"><br>
        Media IDs (comma separated): <input type="text" name="media">
        <input type="submit" value="submit">
    </form>

    <div id="result"></div>
</body>
</html>
<?php
endif;
?>


<?php
session_start();

$fields = json_decode(file_get_contents('php://input'), true);
$content = $fields['content'];
$parent = $fields['parent'];
$media = $fields['media'];

if ($content !== NULL && $_SESSION['username'] !== NULL):
	$local = Cassandra::cluster()->build();
	$keyspace = 'twitter';
	$local_sess = $local->connect($keyspace);

	$cluster = Cassandra::cluster()->withContactPoints('192.168.1.13')->build();
	$keyspace = 'twitter';
	$session = $cluster->connect($keyspace);

	if ($parent !== NULL) {
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM tweetsbyparent WHERE parent='" . $parent . "'"
		);

		$future = $local_sess->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();
	}

	if ($row === NULL && $parent !== NULL) {
		$response = array("status" => "error");
		$response['error'] = 'Invalid parent id: ' . $parent;
		$json = json_encode($response);
		echo $json;
	} else {
		$query = "SELECT COUNT(*) FROM media WHERE id in (";
		$first = true;
		foreach ($media as $id) {
			if (!$first) {
				$query .= ", ";
			}

			$query .= "'" . $id . "'";

			if ($first) {
				$first = false;
			}
		}
		$query .= ")";

		echo $query . PHP_EOL;

		$statement = new Cassandra\SimpleStatement(
			$query
		);

		$future = $local_sess->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();

		$numMedia = sizeof($media);

		if ($row['count'] !== $numMedia) {
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

			//$insertByTime = new Cassandra\SimpleStatement(
			//	"INSERT INTO tweets (id,content,sort,timestamp,username) VALUES ('" . strval($id) . "','" . $escape . "',1," . time() . ",'" . strtolower($_SESSION['username']) . "')"
			//);

			$query = "(id,content,sort,timestamp,username,parent) VALUES ('" . strval($id) . "','" . $escape . "',1," . time() . ",'" . strtolower($_SESSION['username']) . "','" . $parent . "',[";
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
			$query .= "]";

			$insertById = new Cassandra\SimpleStatement(
				"INSERT INTO tweetsbyid " . $query;
			);

			$insertByParent = new Cassandra\SimpleStatement(
				"INSERT INTO tweetsbyparent " . $query;
			);

			$insertByUn = new Cassandra\SimpleStatement(
				"INSERT INTO tweetsbyun (id,content,sort,timestamp,username) VALUES ('" . strval($id) . "','" . $escape . "',1," . time() . ",'" . strtolower($_SESSION['username']) . "')"
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

			$batch_local->add($insertById);
			$batch_local->add($insertByParent);
			$local_sess->execute($batch_local);
			$local_sess->closeAsync();

			$session->execute($batch);
			$session->closeAsync();
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
        Media ID(s) (comma separated): <input type="text" name="media">
        <input type="submit" value="submit">
    </form>

    <div id="result"></div>
</body>
</html>
<?php
endif;
?>


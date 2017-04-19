<?php
session_start();

$fields = json_decode(file_get_contents('php://input'), true);
$timestamp = $fields['timestamp'];
$limit = $fields['limit'];
$query = $fields['q'];
$username = $fields['username'];
$following = $fields['following'];
$filter = false;

if ($following === NULL) {
	$following = false;
}

if ($timestamp === NULL) {
	$timestamp = time();
}

if ($limit === NULL) {
	$limit = 25;
}

if ($timestamp !== NULL && $limit !== NULL && $_SESSION['username'] !== NULL):

//header('Content-Type: application/json');

	$ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
	$ip = array_rand($ips, 1);

	$cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
	$keyspace = 'twitter';
	$session = $cluster->connect($keyspace);

	$local = Cassandra::cluster()->build();
	$keyspace = 'twitter';
	$local_sess = $local->connect($keyspace);

	if ($following) {
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM users WHERE username='" . $_SESSION['username'] . "'"
		);
		$future = $local_sess->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();

		$local_sess->closeAsync();

		$who = json_decode($row['following'], true)['following'];

		if (sizeof($who) === 0) {
			$phrase = 'ERROR';
			$response = array("status" => $phrase);
			$response['error'] = 'You aren\'t following anyone!';
			$json = json_encode($response);

			echo $json;
			die();
		}

		if (strcmp($username, '') !== 0) {
			$key = array_search(strtolower($username), $who);

			if ($key === false) {
				$phrase = 'ERROR';
				$response = array("status" => $phrase);
				$response['error'] = 'You aren\'t following ' . strtolower($username) . '.';
				$json = json_encode($response);

				echo $json;
				die();
			}
		}

	}

	//$results_file = fopen('results.txt', 'a');
	//fwrite($results_file, 'Query: "' . $query . '"' . PHP_EOL);
	//fclose($results_file);

	//if (strcmp($query, '') !== 0) {
	//	$query = str_replace("'", "''", $query);

	//	$needle = strpos($query, ' ');

	//	if ($needle !== false) {
	//		$query = substr($query, 0, $needle);
	//	}
	//}

	if (strcmp($username, '') !== 0) {
		if ($following) {
			if (strcmp($query, '') !== 0) {
				$q = "SELECT * FROM tweetsbyun WHERE username='" . strtolower($username) . "' AND timestamp <= " . $timestamp . " AND content LIKE '%" . $query . "%' LIMIT " . $limit;

				// DONE
				$statement = new Cassandra\SimpleStatement(
					$q
				);
			} else {
				$q = "SELECT * FROM tweetsbyun WHERE username='" . strtolower($username) . "' AND timestamp <= " . $timestamp . " LIMIT " . $limit;

				// DONE
				$statement = new Cassandra\SimpleStatement(
					$q
				);
			}
		} else {
			if (strcmp($query, '') !== 0) {
				$q = "SELECT * FROM tweetsbyun WHERE username='" . strtolower($username) . "' AND timestamp <= " . $timestamp . " AND content LIKE '%" . $query . "%' LIMIT " . $limit;

				// DONE
				$statement = new Cassandra\SimpleStatement(
					$q
				);
			} else {
				$q = "SELECT * FROM tweetsbyun WHERE username='" . strtolower($username) . "' AND timestamp <= " . $timestamp . " LIMIT " . $limit;

				// DONE
				$statement = new Cassandra\SimpleStatement(
					$q
				);
			}
		}
	} else {
		if ($following) {
			if (strcmp($query, '') !== 0) {
				$q = "SELECT * FROM tweetsbyun WHERE timestamp <= " . $timestamp . " AND content LIKE '%" . $query . "%' ALLOW FILTERING";

				// NEED TO GET FOLLOWING AND LOOP
				$statement = new Cassandra\SimpleStatement(
					$q
				);

				$filter = true;
			} else {
				$q = "SELECT * FROM tweetsbyun WHERE timestamp <= " . $timestamp . " AND username in (";

				$first = true;
				foreach ($who as $name) {
					if (!$first) {
						$q .= ", ";
					}

					$q .= "'" . $name . "'";

					if ($first) {
						$first = false;
					}
				}

				$q .= ") LIMIT " . $limit;

				// NEED TO GET FOLLOWING AND LOOP
				$statement = new Cassandra\SimpleStatement(
					$q
				);
			}
		} else {
			if (strcmp($query, '') !== 0) {
				$q = "SELECT * FROM tweetsbyun WHERE timestamp <= " . $timestamp . " AND content LIKE '%" . $query . "%' LIMIT " . $limit . " ALLOW FILTERING";

				// DONE
				$statement = new Cassandra\SimpleStatement(
					$q
				);
			} else {
				$q = "SELECT * FROM tweetsbyun WHERE timestamp <= " . $timestamp . " LIMIT " . $limit . " ALLOW FILTERING";

				// DONE
				$statement = new Cassandra\SimpleStatement(
					$q
				);
			}
		}
	}

	//$results_file = fopen('results.txt', 'a');
	//fwrite($results_file, $q . PHP_EOL);
	//fclose($results_file);

	$future = $session->executeAsync($statement);
	$result = $future->get();

	//if ($result->first() === NULL) {
	//	$phrase = 'ERROR';
	//	$response = array("status" => $phrase);
	//	$err = 'No tweets found at ' . strval($timestamp) . ' or earlier.';
	//	$response['error'] = $err;
	//} else {
	$phrase = 'OK';
	$response = array("status" => $phrase);
	$items = array();
	$item = array();

	$count = 0;

	foreach ($result as $row) {
		if (!$filter) {
			array_push($items, array("id" => strval($row['id']), "username" => $row['username'], "content" => $row['content'], "timestamp" => strval($row['timestamp'])));
		} else {
			if (array_search($row['username'], $who) !== false) {
				array_push($items, array("id" => strval($row['id']), "username" => $row['username'], "content" => $row['content'], "timestamp" => strval($row['timestamp'])));
				$count++;

				if ($count >= $limit) {
					break;
				}
			}
		}
	}

	//if (sizeof($items) === 0) {
	//	$phrase = 'ERROR';
	//	$response = array("status" => $phrase);
	//	$err = 'No tweets found at ' . strval($timestamp) . ' or earlier.';
	//	$response['error'] = $err;
	//} else {
	$response['items'] = $items;
	//}
	//}

	$session->closeAsync();

	$json = json_encode($response);

	echo $json;

elseif ($_SESSION['username'] === NULL):
	$response = array("status" => "error");
	$response['error'] = 'You must be logged in before you can search tweets.';
	$json = json_encode($response);
	echo $json;
else:
?>
<html>
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script type="text/javascript" src="/search/search.js"></script>
</head>

<body>
    <form id="input" onsubmit="event.preventDefault(); passToAdd();" autocomplete="off">
        Tweets from this time and earlier: <input type="text" name="timestamp" value="<?php echo time() ?>" autofocus onfocus="this.value = this.value;"><br>
        Maximum number of results: <input type="text" name="limit" value="25"><br>
        Search term: <input type="text" name="q"><br>
        Only show tweets by this user: <input type="text" name="username"><br>
        Only show tweets by users you follow: <input type="checkbox" name="following" value="following" checked><br>
        <input type="submit" value="search">
    </form>

    <div id="result"></div>
</body>
</html>
<?php
endif;
?>
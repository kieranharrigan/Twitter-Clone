<?php
session_start();

error_log('Username: ' . $_SESSION['username'] . PHP_EOL, 3, "/var/tmp/my-errors.log");

$fields = json_decode(file_get_contents('php://input'), true);
$timestamp = $fields['timestamp'];
$limit = $fields['limit'];
$query = $fields['q'];
$username = $fields['username'];
$following = $fields['following'];
$filter = false;

if ($following === NULL) {
	$following = true;
} elseif (strtolower($following) === 'false') {
	$following = false;
}

if ($timestamp === NULL) {
	$timestamp = time();
}

if ($limit === NULL) {
	$limit = 25;
}

if ($timestamp !== NULL && $limit !== NULL && $_SESSION['username'] !== NULL && $_SERVER['REQUEST_METHOD'] === 'POST'):

//header('Content-Type: application/json');

	$ips = array('192.168.1.106', '192.168.1.107', '192.168.1.101', '192.168.1.111', '192.168.1.113', '192.168.1.108');
	$ip = array_rand($ips, 1);

	$cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->withDefaultTimeout(500)->build();
	$keyspace = 'twitter';
	$session = $cluster->connect($keyspace);

	$cluster1 = Cassandra::cluster()->withContactPoints('192.168.1.10')->build();
	$local = $cluster1->connect($keyspace);

	if ($following) {
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM users WHERE username='" . $_SESSION['username'] . "'"
		);
		$future = $local->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();

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

		array_push($who, $_SESSION['username']);

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
		$needstimesort = true;

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

	$times = array();
	$result_timesorted = array();

	if ($needstimesort) {
		foreach ($result as $row) {
			$timearr = $result_timesorted[$row['timestamp']];

			if ($timearr === NULL) {
				$result_timesorted[$row['timestamp']] = array($row);
			} else {
				array_push($result_timesorted[$row['timestamp']], $row);
			}
		}

		krsort($result_timesorted);

		foreach ($result_timesorted as $key => $time) {
			foreach ($time as $row) {
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
		}
	} else {

		foreach ($result as $row) {
			array_push($items, array("id" => strval($row['id']), "username" => $row['username'], "content" => $row['content'], "timestamp" => strval($row['timestamp'])));
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
	$local->closeAsync();

	$json = json_encode($response);

	echo $json;

elseif ($_SESSION['username'] === NULL):
	//error_log('Redirect to ' . $_SESSION . PHP_EOL, 3, "/var/tmp/my-errors.log");

	$response = array("status" => "error");
	$response['error'] = 'You must be logged in before you can search tweets.';
	$json = json_encode($response);
	echo $json;

	//header("Location: /login");
	//die();
else:
?>
<html>
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    	<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script type="text/javascript" src="/search/search.js"></script>
</head>

<body>

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand">Twitter Clone</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="/additem">New Tweet</a></li>
        <li><a href="/addmedia">Add Media</a></li>
        <li class="active"><a href="/search">Search <span class="sr-only">(current)</span></a></li>
      </ul>

      <ul class="nav navbar-nav navbar-right">
		<?php if ($_SESSION['username'] === NULL): ?>
		<li><a href="/adduser">Create Account</a></li>
        <li><a href="/login">Login</a></li>
		<?php else: ?>
		<li><a>Hello, <?php echo $_SESSION['username']; ?></a></li>
	    <li><a href="/logout">Logout</a></li>
	<?php endif;?>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<div class="container">
    <form id="input" onsubmit="event.preventDefault(); passToAdd();" autocomplete="off" class="form-horizontal">
    	<div class="form-group">
			<label class="control-label col-sm-4">Tweets from this time and earlier:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="timestamp" value="<?php echo time() ?>" autofocus onfocus="this.value = this.value;">
			</div>
		</div>
    	<div class="form-group">
			<label class="control-label col-sm-4">Maximum number of results:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="limit" value="25">
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-4">Search term:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="q">
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-4">Only show tweets by this user:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="username">
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-4">Only show tweets by users you follow:</label>
			<div class="col-sm-7">
				<select class="form-control" name="following" value="following">
    				<option value="true">True</option>
    				<option Value="false">False</option>
  				</select>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-4 col-sm-10">
				<button type="submit" class="btn btn-default">Submit</button>
			</div>
		</div>
    </form>
</div>

    <div id="result"></div>
</body>
</html>
<?php
endif;
?>

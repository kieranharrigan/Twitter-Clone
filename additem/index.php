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
	$ips = array('192.168.1.106', '192.168.1.107', '192.168.1.101', '192.168.1.111', '192.168.1.113', '192.168.1.108');
	$ip = array_rand($ips, 1);

	$cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->withDefaultTimeout(500)->build();
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

			$phrase = 'OK';
			$response = array("status" => $phrase);

			if (strcmp($phrase, 'OK') === 0) {
				$response['id'] = strval($id);
			}
			$json = json_encode($response);

			echo $json;
		}
	}
	//comment

elseif ($_SESSION['username'] === NULL):
	$response = array("status" => "error");
	$response['error'] = 'You must be logged in before you can compose tweets.';
	$json = json_encode($response);
	echo $json;

	header("Location: /login");
	die();
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
    <script type="text/javascript" src="/additem/additem.js"></script>
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
        <li class="active"><a href="/additem">New Tweet <span class="sr-only">(current)</span></a></li>
        <li><a href="/addmedia">Add Media</a></li>
        <li><a href="/search">Search</a></li>
      </ul>

      <ul class="nav navbar-nav navbar-right">
		<?php if ($_SESSION['username'] === NULL): ?>
		<li><a href="/adduser">Create Account</li>
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
    <form id="input" oninput="updateCount();" onsubmit="event.preventDefault(); passToAdd();" autocomplete="off" class="form-horizontal">
    	<div class="form-group">
    	    <label class="control-label col-sm-2"></label>
			<div class="col-sm-10">
        		<textarea class="form-control" id="tweet" type="text" name="content" maxlength="140" rows="6" cols="50" style="resize:none" placeholder="Your tweet goes here" autofocus></textarea>
        		Characters left: <span id="rem">140</span>
        	</div>
        </div>
        <div class="form-group">
        	<label class="control-label col-sm-2">Parent:</label>
			<div class="col-sm-10">
				<input class="form-control" type="text" name="parent" placeholder="Enter parent tweet ID">
			</div>
        </div>
        <div class="form-group">
        	<label class="control-label col-sm-2">Media:</label>
			<div class="col-sm-10">
        		<input class="form-control" type="text" name="media" placeholder="Enter media IDs (comma separated)">
        	</div>
        </div>
        <div class="form-group">
			<div class="col-sm-offset-2 col-sm-10">
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


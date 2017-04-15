<?php
session_start();

$fields = json_decode(file_get_contents('php://input'), true);
$content = $fields['content'];

if ($content !== NULL && $_SESSION['username'] !== NULL):
	$local = Cassandra::cluster()->build();
	$keyspace = 'twitter';
	$local_sess = $local->connect($keyspace);

	$cluster = Cassandra::cluster()->withContactPoints('192.168.1.13')->build();
	$keyspace = 'twitter';
	$session = $cluster->connect($keyspace);

	$id = md5(uniqid($_SESSION['username'], true));

//$results_file = fopen('results.txt', 'a');
	//fwrite($results_file, strval($id) . ': "' . $content . '"' . PHP_EOL);
	//fclose($results_file);

	$batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

	$escape = str_replace("'", "''", $content);

	//$insertByTime = new Cassandra\SimpleStatement(
	//	"INSERT INTO tweets (id,content,sort,timestamp,username) VALUES ('" . strval($id) . "','" . $escape . "',1," . time() . ",'" . strtolower($_SESSION['username']) . "')"
	//);

	$insertById = new Cassandra\SimpleStatement(
		"INSERT INTO tweetsbyid (id,content,sort,timestamp,username) VALUES ('" . strval($id) . "','" . $escape . "',1," . time() . ",'" . strtolower($_SESSION['username']) . "')"
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

	//$batch->add($insertByTime);
	$batch->add($insertById);
	$batch->add($insertByUn);

	$local_sess->execute($insertById);
	$local_sess->closeAsync();

	$session->execute($batch);
	$session->closeAsync();

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
        Media ID(s): <input type="text" name="media">
        <input type="submit" value="submit">
    </form>

    <div id="result"></div>
</body>
</html>
<?php
endif;
?>


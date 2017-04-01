<?php
session_start();

$fields = json_decode(file_get_contents('php://input'), true);
$timestamp = $fields['timestamp'];
$limit = $fields['limit'];

//if($timestamp === NULL) {
//    $timestamp = time();
//}

//if($limit === NULL) {
//    $limit = 25;
//}

if($timestamp !== NULL && $limit !== NULL && $_SESSION['username'] !== NULL) :

//header('Content-Type: application/json');

$cluster = Cassandra::cluster()->build();
$keyspace = 'twitter';
$session = $cluster->connect($keyspace);
$statement = new Cassandra\SimpleStatement(
    "SELECT * FROM tweets WHERE sort=1 AND timestamp <= " . $timestamp . " LIMIT " . $limit
);
$future = $session->executeAsync($statement);
$result = $future->get();

if($result->first() === NULL) {
    $phrase = 'ERROR';
    $response = array("status" => $phrase);
    $err = 'No tweets found at ' . strval($timestamp) . ' or earlier.';
    $response['error'] = $err;
}
else {
    $phrase = 'OK';
    $response = array("status" => $phrase);
    $items = array();
    $item = array();

    foreach($result as $row) {
        array_push($items, array("id" => strval($row['id']), "username" => $row['username'], "content" => $row['content'], "timestamp" => strval($row['timestamp'])));
    }
    $response['items'] = $items;
}

$session->closeAsync();

	$json = json_encode($response);

	echo $json;

elseif($_SESSION['username'] === NULL):
    $response = array("status" => "error");
    $response['error'] = 'You must be logged in before you can search tweets.';
    $json = json_encode($response);
    echo $json;
else :
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
        <input type="submit" value="search">
    </form>

    <div id="result"></div>
</body>
</html>
<?php
endif;
?>


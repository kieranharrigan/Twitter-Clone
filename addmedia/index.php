<?php
session_start();

$filename = basename($_FILES["content"]["name"]);
$content = '0x' . bin2hex(base64_encode(file_get_contents($_FILES["content"]["tmp_name"])));

if (strcmp($filename, '') !== 0):
	$local = Cassandra::cluster()->build();
	$keyspace = 'twitter';
	$local_sess = $local->connect($keyspace);

	$id = md5(uniqid($contents, true));

	$statement = new Cassandra\SimpleStatement(
		"INSERT INTO media (id,content) VALUES ('" . $id . "'," . $content . ");"
	);
	$local_sess->executeAsync($statement);
	$local_sess->closeAsync();

	$phrase = 'OK';
	$response = array("status" => $phrase);
	$response['id'] = strval($id);
	$json = json_encode($response);

	echo $json;
else:
?>
<html>
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script type="text/javascript" src="/addmedia/addmedia.js"></script>
</head>

<body>
    <form id="input" onsubmit="event.preventDefault(); passToDB();" autocomplete="off" enctype="multipart/form-data">
        Media: <input type="file" name="content"><br>
        <input type="submit" value="submit">
    </form>
</body>
</html>
<?php
endif;
?>


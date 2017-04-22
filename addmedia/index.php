<?php
session_start();

$filename = basename($_FILES["content"]["name"]);
$content = '0x' . bin2hex(base64_encode(file_get_contents($_FILES["content"]["tmp_name"])));

if (strcmp($filename, '') !== 0):
	// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
	// $ip = array_rand($ips, 1);

	// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
	$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->withIOThreads(5)->build();
	$keyspace = 'twitter';
	$local_sess = $cluster->connect($keyspace);

	$id = md5(uniqid($content, true));

	$phrase = 'OK';
	$response = array("status" => $phrase);
	$response['id'] = strval($id);
	$json = json_encode($response);

	echo $json;

	$statement = new Cassandra\SimpleStatement(
		"INSERT INTO media (id,content) VALUES ('" . $id . "'," . $content . ");"
	);
	$local_sess->executeAsync($statement);
	$local_sess->closeAsync();
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


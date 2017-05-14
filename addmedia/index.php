<?php
session_start();

$filename = basename($_FILES["content"]["name"]);
$content = '0x' . bin2hex(base64_encode(file_get_contents($_FILES["content"]["tmp_name"])));

if (strcmp($filename, '') !== 0):
	// 	$ips = array('192.168.1.106', '192.168.1.107', '192.168.1.101', '192.168.1.111', '192.168.1.113', '192.168.1.108');
	// $ip = array_rand($ips, 1);

	// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
	$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->withIOThreads(5)->build();
	$keyspace = 'twitter';
	$local_sess = $cluster->connect($keyspace);

	$id = md5(uniqid($content, true));

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
    	<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script type="text/javascript" src="/addmedia/addmedia.js"></script>
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
        <li class="active"><a href="/addmedia">Add Media <span class="sr-only">(current)</span></a></li>
        <li><a href="/search">Search</a></li>
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
    <form id="input" onsubmit="event.preventDefault(); passToDB();" autocomplete="off" enctype="multipart/form-data" class="form-horizontal">
    	<div class="form-group">
			<label class="control-label col-sm-2">Media:</label>
			<div class="col-sm-10">
        		<label class="btn btn-primary" for="my-file-selector">
    			<input id="my-file-selector" type="file" name="content" style="display:none;" onchange="$('#path').html($(this).val().split('\\').pop());">
    			Browse
				</label>
				<span class='label label-info' id="path"></span>
        	</div>
        </div>
    	<div class="form-group">
			<div class="col-sm-offset-2 col-sm-10">
				<button type="submit" class="btn btn-default">Submit</button>
			</div>
		</div>
    </form>
</div>
</body>
</html>
<?php
endif;
?>

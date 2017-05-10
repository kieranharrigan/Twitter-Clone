<?php
session_start();

$fields = json_decode(file_get_contents('php://input'), true);
$username = $fields['username'];
$password = $fields['password'];
$email = $fields['email'];

if ($username !== NULL && $password !== NULL && $email !== NULL):
	$email = preg_replace('/\s+/', '', $email);

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$phrase = 'Invalid email address.';
	} else {
		// $ips = array('192.168.1.40', '192.168.1.41', '192.168.1.42', '192.168.1.43', '192.168.1.44', '192.168.1.46', '192.168.1.79', '192.168.1.66', '192.168.1.38', '192.168.1.80', '192.168.1.22', '192.168.1.25', '192.168.1.28');
		// $ip = array_rand($ips, 1);

		// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
		$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->withIOThreads(5)->build();
		$keyspace = 'twitter';
		$session = $cluster->connect($keyspace);
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM users WHERE username='" . strtolower($username) . "'"
		);
		$future = $session->executeAsync($statement);
		$result = $future->get();

		if ($result->first() === NULL) {
			$key = md5(uniqid($username, true));

			$batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

			$statement = new Cassandra\SimpleStatement(
				"INSERT INTO users (username,password,disabled,email,key,followers,following) VALUES ('" . strtolower($username) . "','" . $password . "',true,'" . strtolower($email) . "','" . $key . "','{\"followers\":[]}','{\"following\":[]}')"
			);

			$emails = new Cassandra\SimpleStatement(
				"INSERT INTO emails (username,password,disabled,email,key) VALUES ('" . strtolower($username) . "','" . $password . "',true,'" . strtolower($email) . "','" . $key . "')"
			);

			$batch->add($emails);
			$batch->add($statement);

			$session->execute($batch);
			$session->closeAsync();

			$phrase = 'OK';

			$response = array("status" => $phrase);
			$json = json_encode($response);

			echo $json;
			die();

//			$body = "Thank you for creating an account with Twitter Clone.\r\n\r\n" . "Username: " . strtolower($username) . "\r\nKey: " . $key . "\r\n\r\nPlease click the following link to verify your email:\r\nhttp://kiharrigan.cse356.compas.cs.stonybrook.edu/verify/verify.php?email=" . $email . "&key=" . $key;

//			exec("php send.php '$email' '$body' > /dev/null 2>&1 &");
		} else {
			$phrase = 'ERROR';
		}
	}
	$response = array("status" => $phrase);
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
	<script type="text/javascript" src="/adduser/adduser.js"></script>
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
      <a class="navbar-brand" href="#">Twitter Clone</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="/additem">New Tweet</a></li>
        <li><a href="/addmedia">Add Media</a></li>
        <li><a href="/search">Search</a></li>
      </ul>

      <ul class="nav navbar-nav navbar-right">
		<?php if ($_SESSION['username'] === NULL): ?>
		<li class="active"><a href="/adduser">Create Account <span class="sr-only">(current)</span></a></li>
        <li><a href="/login">Login</a></li>
		<?php else: ?>
		<li>Hello, <?php echo $_SESSION['username']; ?></li>
	    <li><a href="/logout">Logout</a></li>
	<?php endif;?>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<div class="container">
	<form id="input" onsubmit="event.preventDefault(); passToAdd();" autocomplete="off" class="form-horizontal">
	    <div class="form-group">
			<label class="control-label col-sm-2">Username:</label>
			<div class="col-sm-10">
				<input class="form-control" type="text" name="username" placeholder="Enter username" autofocus>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2">Password:</label>
			<div class="col-sm-10">
				<input class="form-control" type="password" name="password" placeholder="Enter password">
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2">Email:</label>
			<div class="col-sm-10">
				<input class="form-control" type="text" name="email" placeholder="Enter email">
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

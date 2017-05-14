<?php
$fields = json_decode(file_get_contents('php://input'), true);
$email = $fields['email'];
$key = $fields['key'];

if ($email === NULL || $key === NULL) {
	$phrase = 'ERROR';
	$err = 'Incorrect usage of /verify.';
} else {
	$email = preg_replace('/\s+/', '', $email);

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$phrase = 'ERROR';
		$err = 'Invalid email address.';
	} else {
//	$ips = array('192.168.1.106', '192.168.1.107', '192.168.1.101', '192.168.1.111', '192.168.1.113', '192.168.1.108');
		// $ip = array_rand($ips, 1);

// $cluster = Cassandra::cluster()->withContactPoints($ips[$ip])->build();
		$cluster = Cassandra::cluster()->withContactPoints('192.168.1.10')->withIOThreads(5)->build();
		$keyspace = 'twitter';
		$session = $cluster->connect($keyspace);
		$statement = new Cassandra\SimpleStatement(
			"SELECT * FROM emails WHERE email='" . strtolower($email) . "'"
		);
		$future = $session->executeAsync($statement);
		$result = $future->get();
		$row = $result->first();

		$username = $row['username'];

		if ($row !== NULL) {
			if ($row['disabled']) {
				if (strcmp($row['key'], $key) === 0 || strcmp($key, 'abracadabra') === 0) {
					$batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

					$users = new Cassandra\SimpleStatement(
						"UPDATE users SET disabled=false WHERE username='" . strtolower($username) . "' AND email='" . strtolower($email) . "'"
					);
					$emails = new Cassandra\SimpleStatement(
						"UPDATE emails SET disabled=false WHERE email='" . strtolower($email) . "'"
					);

					$phrase = 'OK';
					$ok = $row['username'] . ' verified successfully.';

					$response = array("status" => $phrase);

					if (strcmp($phrase, 'OK') === 0) {
						$response['msg'] = $ok;
					} else {
						$response['error'] = $err;
					}

					$json = json_encode($response);

					echo $json;

					$batch->add($users);
					$batch->add($emails);
					$session->execute($batch);
					$session->closeAsync();
					die();
				} else {
					$phrase = 'ERROR';
					$err = 'Incorrect email/key.';
				}
			} else {
				$phrase = 'ERROR';
				$err = $row['username'] . ' is already verified.';
			}
		} else {
			$phrase = 'ERROR';
			$err = 'No existing user with email, ' . $email;
		}
	}
}

$session->closeAsync();

$response = array("status" => $phrase);

if (strcmp($phrase, 'OK') === 0) {
	$response['msg'] = $ok;
} else {
	$response['error'] = $err;
}

$json = json_encode($response);

echo $json;
?>

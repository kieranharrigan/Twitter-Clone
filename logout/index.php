<?php
session_start();
$phrase = 'ERROR';

if($_SESSION['username'] !== NULL) {
	$phrase = 'OK';
	session_unset();
	session_destroy();
}

$response = array("status" => $phrase);
$json = json_encode($response);

echo $json;
?>
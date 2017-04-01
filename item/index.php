<?php

if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    doDelete();
}
else {
    doGet();
}

function doDelete() {
    $id = $_GET['id'];

    if($id !== NULL) {
        $cluster = Cassandra::cluster()->build();
        $keyspace = 'twitter';
        $session = $cluster->connect($keyspace);
        $statement = new Cassandra\SimpleStatement(
            "DELETE FROM tweetsbyid WHERE id='" . $id . "' IF EXISTS"
            );
        $future = $session->executeAsync($statement);
        $result = $future->get();
        $row = $result->first();

        if ($row['[applied]']) {
            $phrase = 'OK';
        }
        else {
            $phrase = 'ERROR';
            $err = 'No tweet with id=' . $id;
        }
    }
    else {
     $phrase = 'ERROR';
     $err = 'No tweet id specified.';
 }

 $session->closeAsync();

 $response = array("status" => $phrase);

 if(strcmp($phrase, 'ERROR') === 0) {
    $response['error'] = $err;
}
$json = json_encode($response);

echo $json;
}

function doGet() {
    $id = $_GET['id'];

    if($id !== NULL) {
        $cluster = Cassandra::cluster()->build();
        $keyspace = 'twitter';
        $session = $cluster->connect($keyspace);
        $statement = new Cassandra\SimpleStatement(
            "SELECT * FROM twitter.tweetsbyid WHERE id='" . $id . "'"
            );
        $future = $session->executeAsync($statement);
        $result = $future->get();
        $row = $result->first();

        if ($row !== NULL) {
            $username = $row['username'];
            $content = $row['content'];
            $timestamp = strval($row['timestamp']);

            $phrase = 'OK';

        }
        else {
            $phrase = 'ERROR';
            $err = 'No tweet with id=' . $id;
        }
    }
    else {
     $phrase = 'ERROR';
     $err = 'No tweet id specified.';
 }

 $session->closeAsync();

 $response = array("status" => $phrase);

 if(strcmp($phrase, 'OK') === 0) {
    $response['item'] = array("id" => strval($id), "username" => $username, "content" => $content, "timestamp" => $timestamp);
}
else {
    $response['error'] = $err;
}
$json = json_encode($response);

echo $json;
}
?>

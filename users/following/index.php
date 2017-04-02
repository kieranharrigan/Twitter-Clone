<?php
$limit = $_GET['limit'];

if ($limit === NULL) {
$limit = 50;
}
else {
if(is_numeric($limit)) {
$limit = (int) $limit;
if ($limit < 0) {
$limit = 0;
}
else if($limit > 200) {
$limit = 200;
}
}
else {
$limit = 50;
}
}

echo $limit;
echo $_GET['username'];
?>

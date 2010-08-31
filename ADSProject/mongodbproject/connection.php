<?php
$connection = new Mongo();//connection to localhost
$db = $connection->selectDB('adsproject');
$collection=$db->Blogs;
?>

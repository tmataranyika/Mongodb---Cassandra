<?php
include 'connection.php';

$filter = array('Title'=>$_POST['Title'];
$cursor=$collection->find($filter);
foreach ($cursor as $tag)
{
	var_dump($tag);
}
?>

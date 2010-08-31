<?php
include 'connection.php';

$Tags => Array (
            'username' => $_POST['tags'],
               )

	
	$safe_insert=true;
	$collection->insert($Tags,$safe_insert);
	$Tags_identifier=$Tags['_id'];
?>

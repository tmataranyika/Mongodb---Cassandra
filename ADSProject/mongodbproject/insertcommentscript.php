<?php
include 'connection.php';

$Comments => Array (
        'Commentator1' => Array (
            'username' => $_POST['username'],
            'firstname' => $_POST['firstname'],
            'lastname' => $_POST['lastname'],
            'address' => $_POST['address'],
            'Comment' => $_POST['comment'],
        )

	
	$safe_insert=true;
	$collection->insert($Comments,$safe_insert);
	$Comments_identifier=$Comments['_id'];
?>

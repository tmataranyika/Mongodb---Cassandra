<?php
include 'connection.php';

$Blog-entry => Array (
    'author' => Array (
        'First Name' => $_POST['name'],
        'Last Name' => $_POST['lname'],
        'Address' => Array (
            'Street' => $_POST['street'],
            'Suburb' => $_POST['surburb'],
            'City' => $_POST['city'],
            'P.O. Box' => $_POST['box'],
        )
    )
    'Post' => Array (
        'Title' => $_POST['title'],
        'Text' => $_POST['text'],
        'Date' => $_POST['Date'],
    )
    'meta' => Array (
        'keywords' => $_POST['tags'],
    );
	
	$safe_insert=true;
	$collection->insert($Blog-entry,$safe_insert);
	$Blog-entry_identifier=$Blog-entry['_id'];
?>

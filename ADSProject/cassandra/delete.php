<?php
// Setup the path to the thrift library folder
$GLOBALS['THRIFT_ROOT'] = 'C:/xampp/htdocs/cassandra'; 
// Load up all the thrift stuff
require_once $GLOBALS['THRIFT_ROOT'].'/class.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/tmp.php'; 
require_once $GLOBALS['THRIFT_ROOT'].'/key.php'; 
$i =(isset($_GET['key']))?strip_tags($_GET['key']):'';
// Initialize Cassandra
$cassandra = new CassandraDB("ContentManagementSystem");
// Debug on
$cassandra->SetDisplayErrors(true);


if ($cassandra->DeleteRecord("cms",$i))
{
 echo" WRITE FUNCTION DELETE";
}
else
{
 echo"Failed to delete the message!";
}
?>
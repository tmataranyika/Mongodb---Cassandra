<?php
session_start();
// Setup the path to the thrift library folder
$GLOBALS['THRIFT_ROOT'] = '/var/www/html/cassandra'; 
// Load up all the thrift stuff
require_once $GLOBALS['THRIFT_ROOT'].'/class.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/tmp.php'; 

$action = (isset($_GET['do']))?strip_tags($_GET['do']):'';
// Initialize Cassandra
//$cassandra = new CassandraDB("ContentManagementSystem");

// Debug on
//$cassandra->SetDisplayErrors(true);

// Insert record array ("SuperColumns" in Cassandra)
//$record = array();
//$record["Mike Peters"] = array("name" => "Mike Peters", "email" => "Mike at Peters");
//$record["Jonathan Ellis"] = array("name" => "Jonathan Ellis", "email" => "Jonathan at Ellis");
//if ($cassandra->InsertRecordArray('Super1', "People", $record))
//{
//  echo "RecordArray (SuperColumns) inserted successfully.\r\n";
//}

// Print record array
//$record = $cassandra->GetRecordByKey('Super1', 'People');
//print_r($record); 

echo"<table border='0' cellspacing='0' cellpadding='2' width='80%' align='center'>";
echo"<tr>";
echo"<td colspan='1' valign='top' style='font-family:arial; font-size:14pt;color:#045FB4;'>";
if(!empty($action)&&(isset($action)))
{
require_once'default.php';
}
else if($action=='')
{
require_once'login.php';
}
echo"</td>";
echo"<td colspan='1' style='font-family:arial;font-size:10pt;color:#2E2E2E;'>";
if(isset($action) &&($action!=''))
{
 $OutPut = Cms($action);
 echo $OutPut;
}
echo"</td>";
echo"</tr>";
echo"</table>";

?>
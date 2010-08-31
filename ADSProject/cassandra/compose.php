<?php
session_start();
$actionz = (isset($_REQUEST['act']))?strip_tags($_REQUEST['act']):'';
$subject = (isset($_POST['subject']))?strip_tags($_POST['subject']):'';
$to = (isset($_POST['to']))?strip_tags($_POST['to']):'';
$from = (isset($_POST['from']))?strip_tags($_POST['from']):'';
$content = (isset($_POST['content']))?strip_tags($_POST['content']):'';
$emailD = $_SESSION['ema'] ;
if(isset($actionz)&&($actionz!=''))
{
// set the default timezone 
date_default_timezone_set('Africa/Windhoek');
$curr_date = date("F j, Y, g:i a");


// Setup the path to the thrift library folder
$GLOBALS['THRIFT_ROOT'] = '/var/www/html/cassandra'; 
// Load up all the thrift stuff
require_once $GLOBALS['THRIFT_ROOT'].'/class.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/tmp.php'; 
require_once $GLOBALS['THRIFT_ROOT'].'/key.php'; 

   // Initialize Cassandra
$cassandra = new CassandraDB("Keyspace1");
   // Debug on
$cassandra->SetDisplayErrors(true);

$keyC = key_cms();

$record = array("to"=>"$to","from"=>"$from","subject"=>"$subject","content"=>"$content",
                 "timestamp"=>"$curr_date","status"=>"unread");
    if ($cassandra->InsertRecordArray("cms", $keyC, $record))
    {
        $records = $cassandra->GetRecordByKey("cms",$keyC);
		echo"<span style='font-family:arial; font-size:10pt;color:#045FB4;'>Email sent!</span><br />";
		echo"<span style='font-family:arial; font-size:10pt;color:#045FB4;'>To:</span>
		<span style='font-family:arial; font-size:10pt;color:black;'>".
		$records['to'].'<span><br / >';

    }
}
else
{
echo"<form method='POST' action='default.php?do=compose&act=sent'>";
echo "<table border='0' cellspacing='0' cellpadding='0' width='100%'>";
echo"<tr>";
echo"<td colspan='1' width='8%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"To:";
echo"</td>";
echo"<td colspan='1' >";
echo"<input type='text' name='to' id='to' size='65'>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"Subject:";
echo"</td>";
echo"<td colspan='1'>";
echo"<input type='text' name='subject' id='subject' size='65'>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1'>";
echo"&nbsp";
echo"</td>";
echo"<td colspan='1'>";
echo"<textarea rows='12' cols='58' name='content' id='content' >";
echo"</textarea>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1'>";
echo"<input type='hidden' id='from' name='from' value='".$_SESSION['ema']."'>";
echo"</td>";
echo"<td colspan='1'>";
echo"<input type='submit' name='send' id='send' value='Send'>";
echo"</td>";
echo"</tr>";
echo"</table>";
echo"</form>";
}
?>

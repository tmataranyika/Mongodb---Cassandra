<?php
session_start();
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

$emailD = $_SESSION['ema'] ;
$usernameD = $_SESSION['usrn'];
$passwordD = $_SESSION['pass'];

// Initialize Cassandra
$cassandra = new CassandraDB("ContentManagementSystem");
// Debug on
$cassandra->SetDisplayErrors(true);

	
//if unread should be in white color,if read should be in grayish colour
echo"<table border='0' cellspacing='0' cellpadding='2' width='80%' align='center'>";
echo"<tr>";
echo"<td colspan='1' valign='top' width='30%' style='font-family:arial; font-size:12pt;color:black'>";
echo"From";
echo"</td>";
echo"<td colspan='1' valign='top' with='48%' style='font-family:arial; font-size:12pt;color:black'>";
echo "Subject";
echo"</td>";
echo"<td colspan='1' valign='top' width='25%' style='font-family:arial; font-size:12pt;color:black'>";
echo "Date";
echo"</td>";
echo"<td colspan='1' valign='top' width='1%' style='font-family:arial; font-size:12pt;color:black'>";
echo"&nbsp";
echo"</td>";
echo"<td colspan='1' valign='top' width='5%' style='font-family:arial; font-size:12pt;color:black'>";
echo"  Action";
echo"</td>";
echo"</tr>";

for($i=0;$i<=100;$i++)
{
    $records = $cassandra->GetRecordByKey("cms",$i);
           if($records['to']=="$emailD")
			{
              echo"<tr>";
              echo"<td colspan='1' valign='top' width='30%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
              echo $records['from'];
              echo"</td>";
              echo"<td colspan='1' valign='top' with='48%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
              echo "<a href='default.php?do=message&key=$i&type=read' style='font-family:arial; font-size:10pt;color:#045FB4;'>".
			        $records['subject']."</a>";
              echo"</td>";
              echo"<td colspan='1' valign='top' width='25%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
              echo $records['timestamp'];
              echo"</td>";
			  echo"<td colspan='1' valign='top'  style='font-family:arial; font-size:10pt;color:#045FB4;'>";

              echo"</td>";
			  echo"<td colspan='1' valign='top' >";
              echo"<a href='default.php?do=delete&key=$i' style='text-decoration:none;font-family:arial; font-size:8pt;color:red;'>[DELETE]</a>";
              echo"</td>";
              echo"</tr>";
			 }
}
echo"</table>";
?>
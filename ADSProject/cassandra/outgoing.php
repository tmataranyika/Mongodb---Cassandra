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


// Initialize Cassandra
$cassandra = new CassandraDB("ContentManagementSystem");
// Debug on
$cassandra->SetDisplayErrors(true);

echo"<table border='0' cellspacing='0' cellpadding='2' width='80%' align='center'>";
echo"<tr>";
echo"<td colspan='1' valign='top' width='30%' style='font-family:arial; font-size:12pt;color:black'>";
echo"To";
echo"</td>";
echo"<td colspan='1' valign='top' with='48%' style='font-family:arial; font-size:12pt;color:black'>";
echo"Subject";
echo"</td>";
echo"<td colspan='1' valign='top' width='25%' style='font-family:arial; font-size:12pt;color:black'>";
echo "Date";
echo"</td>";
echo"</tr>";
for($i=0;$i<=100;$i++)
{
    $records = $cassandra->GetRecordByKey("cms",$i);
           if($records['from']=="$emailD")
			{
              echo"<tr>";
              echo"<td colspan='1' valign='top' width='30%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
              echo $records['to'];
              echo"</td>";
              echo"<td colspan='1' valign='top' with='48%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
			  echo "<a href='default.php?do=message&key=$i&type=sent' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
              echo $records['subject'];
			  echo"</a>";
              echo"</td>";
              echo"<td colspan='1' valign='top' width='25%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
              echo $records['timestamp'];
              echo"</td>";
              echo"</tr>";
			 }
}
echo"</table>";
?>
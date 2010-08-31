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

$records = $cassandra->GetRecordByKey("cms",$i);
$type = ($_GET['type'])?strip_tags($_GET['type']) :'';

if($type=="read")
{
 $top = "From";
 $client = $records['from'];
}
else if($type=="sent")
{
 $top = "To";
 $client = $records['to'];
}
echo "<table border='0' cellspacing='0' cellpadding='0' width='100%'>";
echo"<tr>";
echo"<td colspan='1' valign='top' width='30%' style='font-family:arial; font-size:10pt;color:black'>";
echo $top.":<span style='color:#045FB4;'>".$client."</span>";
echo"</td>";
echo"<td colspan='1' valign='top' with='48%' style='font-family:arial; font-size:10pt;color:black'>";
echo"Subject:<span style='color:#045FB4;'>".$records['subject']."</span>";
echo"</td>";
echo"<td colspan='1' valign='top' width='25%' style='font-family:arial; font-size:10pt;color:black'>";
echo "Date[<span style='color:#045FB4;'>".$records['timestamp']."</span>]";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='3' valign='top'  style='font-family:arial; font-size:10pt;color:black'>";
echo "&nbsp";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' valign='top'  style='font-family:arial; font-size:10pt;color:black'>";
echo "&nbsp";
echo"</td>";
echo"<td colspan='1' valign='top' width='48%' style='font-family:arial; font-size:12pt;color:black'>";
echo $records['content'];
echo"</td>";
echo"<td colspan='1' valign='top'  style='font-family:arial; font-size:10pt;color:black'>";
echo "&nbsp";
echo"</td>";
echo"</tr>";
echo"</table>";
?>
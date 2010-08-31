<?php
$actionz = (isset($_REQUEST['act']))?strip_tags($_REQUEST['act']):'';
$search = (isset($_POST['inner']))?strip_tags($_POST['inner']):'';

if(isset($actionz)&&($actionz!=''))
{
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
echo"<table border='0' cellspacing='0' cellpadding='2' width='80%' align='center'>";
echo"<tr>";
echo"<td colspan='1' valign='top' width='30%' style='font-family:arial; font-size:12pt;color:black'>";
echo"Email[To|From]";
echo"</td>";
echo"<td colspan='1' valign='top' with='48%' style='font-family:arial; font-size:12pt;color:black'>";
echo "Subject";
echo"</td>";
echo"<td colspan='1' valign='top' width='25%' style='font-family:arial; font-size:12pt;color:black'>";
echo "Date";
echo"</td>";
echo"<td colspan='1' valign='top' width='3%'>";
echo"&nbsp";
echo"</td>";
echo"</tr>";

for($i=0;$i<=100;$i++)
{
    $records = $cassandra->GetRecordByKey("cms",$i);
        	if($records['to']=="$search" || $records['from']=="$search" || $records['subject']=="$search" || $records['content']=="$search")
			{
              echo"<tr>";
              echo"<td colspan='1' valign='top' width='30%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
			  if($records['to']=='')
			  {
			    echo"Empty]";
			  }
			  else
			  {
			   echo "[".$records['to'];
			  }
              echo "|";
			  if($records['from']=='')
			  {
			   echo"Empty]";
			  }
			  else
			  {
			     echo $records['from']."]";
			  }
              echo"</td>";
              echo"<td colspan='1' valign='top' with='48%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
              echo "<a href='default.php?do=message&key=$i&type=read' style='font-family:arial; font-size:10pt;color:#045FB4;'>".
			        $records['subject']."</a>";
              echo"</td>";
              echo"<td colspan='1' valign='top' width='25%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
              echo $records['timestamp'];
              echo"</td>";
			  echo"<td colspan='1' valign='top' >";
              echo"<a href='default.php?do=delete&key=$i' style='text-decoration:none;font-family:arial; font-size:8pt;color:red;'>[DELETE]</a>";
              echo"</td>";
              echo"</tr>";
			 }
}
echo"</table>";
}
else
{
echo"<form method='POST' action='default.php?do=search&act=search'>";
echo "<table border='0' cellspacing='0' cellpadding='0' width='100%'>";
echo"<tr>";
echo"<td colspan='1' width='8%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"Search:";
echo"</td>";
echo"<td colspan='1' >";
echo"<input type='text' name='inner'  id='inner' size='65'>";
echo"<input type='submit' name='search' id='search' value='Search'>";
echo"</td>";
echo"</tr>";
echo"</table>";
echo"</form>";
}
?>
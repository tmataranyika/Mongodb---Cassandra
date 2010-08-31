<?php
session_start();
$GLOBALS['THRIFT_ROOT'] = '/var/www/html/cassandra';
require_once $GLOBALS['THRIFT_ROOT'].'/tmp.php'; 

		        
$action=(isset($_REQUEST['do']))?strip_tags($_REQUEST['do']):'';
$emailD = $_SESSION['ema'] ;

if((isset($emailD)&&($emailD!='')))
{
echo"<table border='0' cellspacing='0' cellpadding='2' width='80%' align='center'>";
echo"<tr>";
echo"<td colspan='2' valign='top' style='font-family:arial; font-size:14pt;color:#045FB4;'>";
echo"Email Management System";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' width='15%' align='center' valign='bottom' style='border-top:1px solid gray;border-right:1px solid gray;border-left:1px solid gray;font-weight:bold;font-family:arial; font-size:10pt;color:#2E2E2E;'>";
echo"Mail Messages";
echo"</td>";
echo"<td colspan='1' width='75%' valign='top' >";
echo"<a href='default.php?do=search' style='text-decoration:none;font-family:arial; font-size:8pt;color:#2E2E2E;'>";
echo"Search";
echo"</a>";
echo" &nbsp &nbsp";
echo"<a href='default.php?do=compose' style='text-decoration:none;font-family:arial; font-size:8pt;color:#2E2E2E;'>";
echo"Create";
echo"</a>";
echo" &nbsp &nbsp";
echo"<a href='default.php?do=logout' style='text-decoration:none;font-family:arial; font-size:8pt;color:#2E2E2E;'>";
echo"Logout";
echo"</a>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' style='border-left:1px solid gray;border-right:1px solid gray;'>";
echo" &nbsp ";
echo"</td>";
echo"<td colspan='1' >";
echo"<span style='font-weight:bold;font-family:arial; font-size:14pt;color:#2E2E2E;'>";
if(isset($_REQUEST['do'])&&($_REQUEST['do']!=''))
{
 $actions = $_REQUEST['act'];
  echo $actions;
}
else if($_REQUEST['do']=='')
{
 echo"Click on Inbox/Sent Items to view your email messages received/sent!<br/>";
 echo"Search/Compose emails in your inbox.";
}
echo"</span>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' align='center' valign='top' style='border-bottom:1px solid gray;border-left:1px solid gray;border-right:1px solid gray;'>";
echo"<div style='margin:3px'>";
echo"<a href='default.php?do=incoming' style='text-decoration:none;font-family:arial; font-size:10pt;color:#2E2E2E;'>";
echo"Inbox";
echo"</a>";
echo"</div>";
echo"<div style='font-family:arial; font-size:10pt;color:#2E2E2E;margin:3px'>";
echo"<a href='default.php?do=outgoing' style='text-decoration:none;font-family:arial; font-size:10pt;color:#2E2E2E;'>";
echo"Sent Items";
echo"</a>";
echo"</div>";
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
}
?>

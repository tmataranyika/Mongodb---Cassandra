<script language="javascript">
function notEmpty()
{
if(document.getElementById('pwd1').value != document.getElementById('pwd2').value ) {
alert('Passwords do not match');

return false;
}
elseif(document.getElementById('pwd1').value == document.getElementById('pwd2').value)
{
 return document.foms.submit()
 return true;
}

return true;
}
</script>
<?php
echo"<form name='foms' method='POST' action='".$_SERVER['PHP_SELF']."'>";
echo"<table border='0' cellspacing='0' cellpadding='2' width='80%' align='center'>";
$submitted = $_POST['Sub'];
$create = $_POST['create'];
$acc = $_POST['acc'];
$username = (isset($_POST['username'])&&($_POST['username']!=''))?strip_tags($_POST['username']):'';
$email = (isset($_POST['email'])&&($_POST['email']!=''))?strip_tags($_POST['email']):'';
$password = (isset($_POST['pwd1'])&&($_POST['pwd1']!=''))?strip_tags($_POST['pwd1']):'';
if(isset($submitted)&&($submitted!=''))
{
// Setup the path to the thrift library folder
$GLOBALS['THRIFT_ROOT'] = '/var/www/html/cassandra'; 
// Load up all the thrift stuff
require_once $GLOBALS['THRIFT_ROOT'].'/class.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/tmp.php'; 
   // Initialize Cassandra
$cassandra = new CassandraDB("Keyspace1");
   // Debug on
$cassandra->SetDisplayErrors(true);

//get record
$records = $cassandra->GetRecordByKey("users","$username");	

     if(($records['email']=="$email")&&($records['password']=="$password"))
   {
        //email,pass and name 
         $_SESSION['ema'] = $records['email'];
		 
        //redirect after making sure that the user is registered and use sessions
        echo"<script language=\"javascript\" type=\"text/javascript\">";
        echo"window.location=\"default.php\"";
        echo"</script>";

 }
 else
 {
  echo"Please go back to the login page and register!<br />";
  echo"<a href='index.php'>Login</a>";
 }
}
else if(isset($create)&&($create!=''))
{

echo"<tr>";
echo"<td colspan='2' width='20%' style='font-family:arial; font-size:14pt;color:#045FB4;'>";
echo"Please create your account!";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' width='20%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"Email Address[unique]";
echo"</td>";
echo"<td colspan='1'>";
echo"<input type='text' name='email' id='email'>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' width='20%' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"username[unique]";
echo"</td>";
echo"<td colspan='1'>";
echo"<input type='text' name='username' id='username'>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"Password";
echo"</td>";
echo"<td colspan='1'>";
echo"<input type='password' name='pwd1' id='pwd1'>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"Confirm Password";
echo"</td>";
echo"<td colspan='1'>";
echo"<input type='password' name='pwd2' id='pwd2'>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='2'>";
echo"<input type='submit' onclick='notEmpty();' value='SUBMIT' id='acc' name='acc'>";
echo"</td>";
echo"</tr>";

}
else if(isset($acc)&&($acc!='')&&($_POST['pwd1']==$_POST['pwd2']))
{
// Setup the path to the thrift library folder
$GLOBALS['THRIFT_ROOT'] = '/var/www/html/cassandra'; 
// Load up all the thrift stuff
require_once $GLOBALS['THRIFT_ROOT'].'/class.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/tmp.php'; 


   // Initialize Cassandra
$cassandra = new CassandraDB("Keyspace1");
   // Debug on
$cassandra->SetDisplayErrors(true);

$record = array("email"=>"$email","password"=>"$password");
       if ($cassandra->InsertRecordArray("users", "$username", $record))
         {          
              
			  //get record
                $records = $cassandra->GetRecordByKey("users","$username");	
             //email,pass and name 
                $_SESSION['ema'] = $records['email'];

            //redirect after making sure that the user is registered and use sessions
            echo"<script language=\"javascript\" type=\"text/javascript\">";
            echo"window.location=\"default.php\"";
            echo"</script>";
		     
         }
}
else if($acc=='' || $create=='' || $submitted=='')
{
echo"<tr>";
echo"<td colspan='2' valign='top' style='font-family:arial; font-size:14pt;color:#045FB4;'>";
echo"Login or create an account to access the message management system:";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' width='15%' valign='top' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"Email:";
echo"</td>";
echo"<td colspan='1' valign='top' style='font-family:arial; font-size:14pt;color:#045FB4;'>";
echo"<input type='text' name='email' id='email'";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' width='10%' valign='top' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"Username:";
echo"</td>";
echo"<td colspan='1' valign='top' style='font-family:arial; font-size:14pt;color:#045FB4;'>";
echo"<input type='text' name='username' id='username'";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='1' valign='top' style='font-family:arial; font-size:10pt;color:#045FB4;'>";
echo"Password:";
echo"</td>";
echo"<td colspan='1' valign='top' style='font-family:arial; font-size:14pt;color:#045FB4;'>";
echo"<input type='password' name='pwd1' id='pwd1'>";
echo"</td>";
echo"</tr>";
echo"<tr>";
echo"<td colspan='2'>";
echo"<input type='submit' name='Sub' id='Sub' value='Login'>";
echo" &nbsp ";
echo"<input type='submit' name='create' id='create' value='Create Account'>";
echo"</td>";
echo"</tr>";
}
echo"</table>";
echo"</form>";
?>

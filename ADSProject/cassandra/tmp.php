<?php
function Cms($x)
{

switch ($x)
{
case'incoming':
       require_once'incoming.php';
  break;
  
case'outgoing':
     require_once'outgoing.php';
  break;
  
case'search':
      require_once'search.php';
  break; 
  
case'compose':
      require_once'compose.php';
  break; 
  
case'insert':
      require_once'compose.php';
  break; 
  
case'delete':
     require_once'delete.php';
  break; 
  
case'message':
     require_once'message.php';
  break; 
  
case'logout':
     //session_start();
	 unset($_SESSION['email']);
	 unset($emailD);
	 unset($_SESSION['pass']);
	 unset($passwordD);
	 unset($_SESSION['usrn']);
	 unset($usernameD);
	// session_destroy();
	       echo"<script language=\"javascript\" type=\"text/javascript\">";
           echo"window.location=\"index.php\"";
           echo"</script>";
	 break;
default:
   require_once"login.php";
}

}
?>
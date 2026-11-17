<?php
/* Notes
   - The login window can be called with a header statement or with javascript top.window.location.href
     The javascript method works always BUT gives Type errors in browser inspector
   - When the login file is called with the header statement then the calling
     link should have "target=_top" to replace all content including frames
*/
/* Modifications
20240514 fho4abcd Added alternative return script. When the standard index.php is forbidden
20251014 fho4abcd Cope with expired SESSION variables
2025-11-24 fho4abcd return page set for architecture with OPAC
20261117 fho4abcd open login window with header + better deletion of session
*/
session_start();
include("../config.php");
if (file_exists($db_path."logtrans/data/logtrans.mst") and isset($_SESSION["MODULO"]) and $_SESSION["MODULO"]=="loan"){
	include("../circulation/grabar_log.php");
	$datos_trans["operador"]=$_SESSION["login"];
	GrabarLog("Q",$datos_trans,$Wxis,$xWxis,$wxisUrl,$db_path);
}
if (isset($_SESSION["HOME"]))
	$retorno=$_SESSION["HOME"];
else {
	if (file_exists("../../login_abcd.php")){
		$retorno="../../login_abcd.php";
	} else if (file_exists("../../login.php")){
		$retorno="../../login.php";
	} else {
		$retorno="../../index.php";
	}
}
$_SESSION=array();
unset($_SESSION);
session_unset();
// It is desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
// Note: The session cookie name = PHPSESSID
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
// Give the system time to delete the cookie
sleep(1);
//Show the login page without any remains of the current session
//Creates a new cookie
header("Location: $retorno");
?>

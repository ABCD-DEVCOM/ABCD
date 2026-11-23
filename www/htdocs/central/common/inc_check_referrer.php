<?php
/* Modifications
** 20261123 fho4abcd Created
*/
/* Description
** Deny access if HTTP_REFERER is not set
** This is the case when the user types the URL in the same or other browser
** Denying this makes the test for being logged in less important.
** Messages are not dependent on a translations mechanism. Only hackers will see them
*/
function Check_Referrer(){
	global $_SERVER, $_SESSION;
	echo "<!DOCTYPE html>";
	$err_pref="<p style='color:red'>*** ";
	if ( !isset($_SERVER["HTTP_REFERER"]) ) {
		echo $err_pref."Direct attempts forbidden. Launch only by ABCD/OPAC</p>";
		die;
	}
	/*
	** Check referer
	** It is assumed that this file is launched from a limited number of root URL's
	*/
	$http_host=$_SERVER["HTTP_HOST"];
	$http_referrer=$_SERVER["HTTP_REFERER"];
	$refer_is_central=strstr($http_referrer,$http_host."/central/");
	$refer_is_opac=strstr($http_referrer,$http_host."/opac/");
	if ( $refer_is_central === false && $refer_is_opac === false ) {
		echo $err_pref."Unknown referer: ". $http_referrer."</p>";
		die;
	}
	/*
	** Check that user is logged in
	** OPAC does not login, so this test is skipped for OPAC
	** The check tests the existence of a filled permission and profile name.
	** Gives no error if used in the same browser with a logged in session.
	*/
	if ($refer_is_opac===false ) {
		if (!isset($_SESSION["permiso"]) || !isset($_SESSION["profile"])){
			header("Location: ../common/error_page.php") ;// logs out
		}
	}
}
Check_Referrer();
?>
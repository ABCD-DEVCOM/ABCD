<?php
/* Modifications
2021-01-05 guilda Removed login encryption
2021-01-05 LeerRegistro compares the password
2021-04-21 fho4abcd Show a an emergency user name if applicable
2021-04-30 fho4abcd Do not switch language if selection is empty
2021-06-14 fho4abcd Do not set password in $_SESSION + lineends
2021-08-12 fho4abcd Give message if profiles/adm is missing for emergency user
2022-01-19 fho4abcd Set default language if none supplied
2022-07-10 fho4abcd Prepare for .par file in database folder+ no password to llamar_wxis.php
2024-05-19 fho4abcd Added alternative return script. When the standard index.php is forbidden
2024-06-01 fho4abcd Check captcha
2025-02-04 fho4abcd Improve UTF-8 display if no databases is selected
2025-02-21 fho4abcd Make Change Password work, move some functions to inc_login_scripts.php, captcha error returns user
2025-10-14 fho4abcd Regenerate sessionid after login to reduce session fixation attacks
2025-10-15 fho4abcd Improve switch to error page in case of expired/lost session
2026-03-11 fho4abcd Added ip check
2026-03-12 fho4abcd Improved ip check
2026-03-17 fho4abcd IP check shows message (was in function)
2026-10-06 rogercgui Add check session status before starting a new session to avoid warnings in the log file. Fixes a bug where the script was trying to start a new session even if one was already active, causing a warning message to be logged.
*/
global $Permiso, $arrHttp,$valortag,$nombre;
$arrHttp=Array();
if(session_status() === PHP_SESSION_NONE) session_start();
unset( $_SESSION["TOOLBAR_RECORD"]);
include("get_post.php");
//echo "arrHttp_dbpath=". $arrHttp["db_path"]."<BR>";
if (isset($arrHttp["db_path"]))
	$_SESSION["DATABASE_DIR"]=$arrHttp["db_path"];
require_once ("../config.php");
include ("../common/inc_login_scripts.php");

if ($use_ldap=="1") require_once ("ldap.php");


//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";die;
$valortag = Array();

function CambiarPassword($Mfn,$new_password){
global $xWxis,$Wxis,$db_path,$wxisUrl,$MD5,$actparfolder;
	if (isset($MD5) and $MD5==1 ){
		$new_password=md5($new_password);
	}
	$ValorCapturado="d30<30 0>".$new_password."</30>";
	$ValorCapturado=urlencode($ValorCapturado);
	$IsisScript=$xWxis."actualizar.xis";
  	$query = "&base=acces&cipar=$db_path".$actparfolder."acces.par&login=".$_SESSION["login"]."&Mfn=".$Mfn."&Opcion=actualizar&ValorCapturado=".$ValorCapturado;
    include("wxis_llamar.php");
}
function VerificarCaptcha(){
Global $arrHttp,$msgstr,$retorno,$setcaptcha;
	$cap_code="";
	if (isset($_SESSION['6_letters_code']) && $_SESSION['6_letters_code']!=""){
		$cap_code=$_SESSION['6_letters_code'];
	}
	if ($setcaptcha=="N") return;
	// a faked case
	if ($setcaptcha=="Y" && $cap_code=="") {
		header("Location: ".$retorno."?login=C");
		die;
	}
	$testcaptcha="";
	if ( isset($arrHttp["captcha"])) $testcaptcha=$arrHttp["captcha"];
	$user="";
	if (isset($arrHttp["login"])) $user=$arrHttp["login"];
	if ($testcaptcha=="" || $testcaptcha!=$cap_code){
		header("Location: ".$retorno."?login=C&user=$user");
		die;
	}
	return;
}


function LeerRegistroLDAP() {
// la variable $llave permite retornar alguna marca que esté en el formato de salida
// identificada entre $$LLAVE= .....$$

$llave_pft="";
global $llamada, $valortag,$maxmfn,$arrHttp,$OS,$Bases,$xWxis,$Wxis,$Mfn,$db_path,$wxisUrl,$MD5,$actparfolder;
    $ic=-1;
	$tag= "";
	$IsisScript=$xWxis."loginLDAP.xis";


    if ($actparfolder=="/")$actparfolder="acces/"; // initial value can be empty
	$query = "&base=acces&cipar=$db_path".$actparfolder."acces.par&login=".$arrHttp["login"];
	include("wxis_llamar.php");


	 foreach ($contenido as $linea){

	 	if ($ic==-1){
	    	$ic=1;

	    	$pos=strpos($linea, '##LLAVE=');
	    	if (is_integer($pos)) {
	     		$llave_pft=substr($linea,$pos+8);
	     		$pos=strpos($llave_pft, '##');
	     		$llave_pft=substr($llave_pft,0,$pos);

			}

		}else{
			$linea=trim($linea);
			$pos=strpos($linea, " ");

			if (is_integer($pos)) {
				$tag=trim(substr($linea,0,$pos));
	//
	//El formato ALL envía un <br> al final de cada línea y hay que quitarselo
	//linea

				$linea=rtrim(substr($linea, $pos+2,strlen($linea)-($pos+2)-5));

				if (!isset($valortag[$tag])) $valortag[$tag]=$linea;
			}
		}

	}
	return $llave_pft;

}

function Session($llave)
{
   Global $arrHttp,$valortag,$Path,$xWxis,$session_id,$Permiso,$msgstr,$db_path,$nombre,$Per,$adm_login,$adm_password;

 		$res=explode('|',$llave);
		//si el usuario no tiene pass pq es un usuario de ldap
		if($res[2] == ""){
		   $llave = "clave|".$llave;
		   $res=explode('|',$llave);
		}
  		$userid=$res[0];
  		$_SESSION["mfn_admin"]=$res[1];
  		$mfn=$res[1];
		$nombre=$res[2];
		$arrHttp["Mfn"]=$mfn;
  		$Permiso="|";
  		$Per="";
  		$value=$valortag[40];
  		if (isset($valortag[60]))
  			$fecha=$valortag[60];
  		else
  			$fecha="";
  		$today=date("Ymd");
  		if (trim($fecha)!=""){
  			if ($today>$fecha){
  				header("Location: ".$retorno."login=N");
  				die;
  			}
  		}
  		$value=substr($value,2);
  		$ix=strpos($value,'^');
  		$Perfil=substr($value,0,$ix);
    	if (!file_exists($db_path."par/profiles/".$Perfil)){
    		echo "missing ". $db_path."par/profiles/".$Perfil;
    		die;
    	}
    	$profile=file($db_path."par/profiles/".$Perfil);
    	unset($_SESSION["profile"]);
    	unset($_SESSION["permiso"]);
    	unset($_SESSION["login"]);
    	$_SESSION["profile"]=$Perfil;
    	$_SESSION["login"]=$arrHttp["login"];
    	foreach ($profile as $value){
    		$value=trim($value);
    		if ($value!=""){
    			$key=explode("=",$value);
    			$_SESSION["permiso"][$key[0]]=$key[1];
    		}
    	}
        if (isset($valortag[70])){
        	$library=$valortag[70];
        	$_SESSION["library"]=$library;
        }else{
        	unset ($_SESSION["library"]);
        }

}


function LoginNLDAP()
{

Global $arrHttp,$valortag,$Path,$xWxis,$session_id,$Permiso,$msgstr,$db_path,$nombre,$Per,$adm_login,$adm_password;

   if ($arrHttp["login"]==$adm_login and $arrHttp["password"]==$adm_password){
 			$Perfil="adm";
 			unset($_SESSION["profile"]);
    		unset($_SESSION["permiso"]);
    		unset($_SESSION["login"]);
 			$profile=file($db_path."par/profiles/".$Perfil);
    		$_SESSION["profile"]=$Perfil;
    		$_SESSION["login"]=$arrHttp["login"];
    		foreach ($profile as $value){
    			$value=trim($value);

    			if ($value!=""){
    				$key=explode("=",$value);
    				$_SESSION["permiso"][$key[0]]=$key[1];
    			}
    		}
    	}else{
 			echo "<script>\n";
 				echo "self.location.href=\"".$retorno."?login=N\";\n";
 			echo "</script>\n";
  			die;
  		}
}

function VerificarUsuarioLDAP(){
    Global $arrHttp,$valortag,$Path,$xWxis,$session_id,$Permiso,$msgstr,$db_path,$nombre,$Per,$adm_login,$adm_password;


	//echo Auth($arrHttp["login"], $arrHttp["password"],false);
	try {

	         /*echo Auth($arrHttp["login"], $arrHttp["password"],false);
			 exit;*/

			$login = false;
			$llave=LeerRegistro();


			if($llave != ""){
		 		Session($llave);
				$login = true;
		    }
			else
				{

					//Auth($arrHttp["login"], $arrHttp["password"],false);
					if(Auth($arrHttp["login"], $arrHttp["password"],false)){
						  $llave= LeerRegistroLDAP();

						  if($llave != ""){
								 Session($llave);
								 $login = true;
							 }
					}
				}


			 if(!$login)
				 LoginNLDAP();

	 } catch (Exception $e) {
         echo $e->getMessage();
		 exit;
     }

}

/////
/////   INICIO DEL PROGRAMA
/////


$query="";



//foreach ($arrHttp as $var => $value) echo "$var = $value<br>";

if (isset($arrHttp["newindow"]))
	$_SESSION["newindow"]="Y";
else
	if (!isset($arrHttp["reinicio"])) unset($_SESSION["newindow"]);

if (isset($arrHttp["lang"]) and $arrHttp["lang"]!=""){
	$_SESSION["lang"]=$arrHttp["lang"];
	$lang=$arrHttp["lang"];

}else{
	if (!isset($_SESSION["lang"])) $_SESSION["lang"]=$lang;
}

if (!isset($arrHttp['base'])) {
    include("../common/inc_nodb_lang.php");
}
include("../lang/dbadmin.php");
include("../lang/admin.php");
include("../lang/prestamo.php");
include("../lang/lang.php");
include("../lang/acquisitions.php");

// Change password before other options
if (isset($arrHttp["Opcion"]) and $arrHttp["Opcion"]=="chgpsw" ){
	$user=$arrHttp["login"];
	VerificarUsuario();
	CambiarPassword($arrHttp["Mfn"],$arrHttp["new_password"]);
	header("Location: $retorno?login=P&user=$user");
	die;
}

if (!isset($_SESSION["Expresion"])) $_SESSION["Expresion"]="";

if (isset($arrHttp["login"])){
	VerificarCaptcha();
	global $use_ldap;
	if($use_ldap){
		VerificarUsuarioLDAP();
	} else {
		VerificarUsuario();
	}
	// Regenerate the session ID to cope session fixation attacks
	// More info https://owasp.org/www-community/attacks/Session_fixation
	// This is only one step in increasing security. More actions have to be added (sometime)
	if (!isset($_SESSION['login'])) {
		session_regenerate_id(true);
	}

	if (isset($arrHttp["lang"]) && $arrHttp["lang"]!="") {
		$_SESSION["lang"]=$arrHttp["lang"];
	} else {
		$_SESSION["lang"]=$lang;
	}

	$_SESSION["login"]=$arrHttp["login"];
	$_SESSION["nombre"]=$nombre;
}

if (!isset($_SESSION["permiso"])){
	/* permiso is always set, except
	**  - when the session is expired or lost
	** Expiration of the user is caught earlier
	** The error page takes care of destroying this session
	*/
	include( "../common/error_page.php") ;
	die;
}
$Permiso=$_SESSION["permiso"];
if (isset($_SESSION["meta_encoding"])) $meta_encoding=$_SESSION["meta_encoding"];
/*
Check Client IP if a database is set
*/
if ( isset($arrHttp['base'])) {
	include ("inc_ip_check.php");
	$clientIP = getClientIP();
	if ( checkClientIP( $clientIP, $arrHttp['base'] ) === false ) {?>
		<div id="ip_not_allowed" style="width: 100%; background-color: #ffc107; text-align: center;">
		<?php
		echo $msgstr["clientip"]." (".$clientIP.") ".$msgstr["invalidfordb"]." ".$arrHttp['base'] ."<br>";
		?>	
		</div><?php
		unset( $arrHttp['base'] );
	}
}
include("homepage.php");
?>

<?php
/* Modifications
2021-06-14 fho4abcd Do not set/get password in/from $_SESSION 
*/

session_start();

global $Permiso, $arrHttp,$valortag,$nombre,$userid,$db,$vectorAbrev;

$arrHttp=Array();

// Cookies variables
$abcd_cookie_options = array (
                'expires' => time() + 60*60*24*30,
                'path' => '/',
                'domain' => ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false, // leading dot for compatibility or use subdomain
                'secure' => true,     // or false
                'httponly' => true,    // or false
                'samesite' => 'None' // None || Lax  || Strict
                );
setcookie(session_name(), session_id(), $abcd_cookie_options);   

//var_dump($_COOKIE);
//var_dump($_SESSION);

require_once ("../../central/config.php");
require_once('../../isisws/nusoap.php');
require_once ("../../central/common/ldap.php");

$converter_path=$cisis_path."mx";
//echo "converter_path=".$converter_path."<BR>";

if (isset($arrHttp["lang"])){
	$_SESSION["lang"]=$arrHttp["lang"];
	$lang=$arrHttp["lang"];
}else{
	if (!isset($_SESSION["lang"]))
    $_SESSION["lang"]=$lang;
}


$page="";
if (isset($_REQUEST['GET'])){
	$page = $_REQUEST['GET'];
} else {
	if (isset($_REQUEST['POST'])) 
	$page = $_REQUEST['POST'];
}

if (!( preg_match('/^[a-z_.]*$/', $page)))  {
	 //Abort the script
	die("Invalid request");
}

$valortag = Array();




function LeerRegistro() {

// la variable $llave permite retornar alguna marca que está en el formato de salida
// identificada entre $$LLAVE= .....$$

$llave_pft="";
$myllave ="";
global $llamada,$valortag,$maxmfn,$arrHttp,$OS,$Bases,$xWxis,$Wxis,$Mfn,$db_path,$wxisUrl,$empwebservicequerylocation,$empwebserviceusersdb,$db,$EmpWeb,$MD5,$converter_path,$vectorAbrev;
	//echo "Central Loans used<BR>";  die;
	//USING the Central Module to login to MySite module
	//Get the user and pass
	



if (isset($_COOKIE["user"])) {
	$checkuser = $_COOKIE["user"];

    $mx = $converter_path . " " . $db_path . "users/data/users \"pft=if v600='" . $checkuser . "' then v20,'|',v30,'|',v10,'|',v10^a,'|',v10^b,'|',v18,'|',v620,fi\" now";

} elseif (isset($arrHttp["login"])) {
	$checkuser = $arrHttp["login"];

	if ($MD5 == 0) {
		$checkpass = $arrHttp["password"];
	} else {
		$checkpass = md5($arrHttp["password"]);
	}

	//Search the users database
	$mx = $converter_path . " " . $db_path . "users/data/users \"pft=if v600='" . $checkuser . "' then if v610='" . $checkpass . "' then v20,'|',v30,'|',v10,'|',v10^a,'|',v10^b,'|',v18,'|',v620 fi,fi\" now";

} else {
		echo "<script>
 		self.location.href=\"../index.php?login=N&lang=" . $_SESSION['lang'] . "\";
 		</script>";

}





//	echo "mxcommand=$mx<BR>";
	//mxcommand=/ABCD2/www/cgi-bin_Windows/ansi/mx /ABCD2/www/bases-examples_Windows/users/data/users "pft=if v600='rosa' then if v610='rosa' then v20,'|',v30,'|',v10,'|',v10^a,'|',v10^b,'|',v18,'|',v620 fi,fi" now
	//die;

	$outmx=array();
	exec($mx,$outmx,$banderamx);
	$textoutmx="";

	for ($i = 0; $i < count($outmx); $i++) {
		$textoutmx.=substr($outmx[$i], 0); 
		
	} 
		$currentdatem=date("Ymd");

	if ($textoutmx!="")  {
		$splittxt=explode("|",$textoutmx);
	//	$myuser = var_dump($checkuser);
		$db = "users";
		$valortag[40] = $splittxt[2]."\n";
		$vectorAbrev['id']=$splittxt[0];
		$vectorAbrev['name']=$splittxt[1];
		$vectorAbrev['userClass']=$splittxt[4]."(".$splittxt[3].")";
		$vectorAbrev['expirationDate']=$splittxt[5];
		$vectorAbrev['photo']=$splittxt[6];
		

		//Checks the expiration date of the user's registration
		if ($currentdatem < $vectorAbrev['expirationDate']) {
			$myllave = $splittxt[0]."|";
			$myllave .= "1|";
			$myllave .= $splittxt[1]."|";
	}

	} elseif ($currentdatem > $vectorAbrev['expirationDate']) {
		echo "<script>
 		self.location.href=\"../index.php?login=N&lang=" . $lang . "\";
 		</script>";

		$myllave="";
	}

//echo "myllave=$myllave<BR>";
	  return $myllave;

}// END LeerRegistro()




function VerificarUsuario(){
Global $arrHttp,$valortag,$Path,$xWxis,$session_id,$Permiso,$msgstr,$db_path,$nombre,$userid,$lang;
 	$llave=LeerRegistro();
//echo "llave = $llave<BR>";
 	if ($llave!=""){
  		$res=explode('|',trim($llave));
  		$userid=$res[0];
  		$_SESSION["mfn_admin"]=$res[1];
  		$mfn=$res[1];
  		$nombre=$res[2];
		$arrHttp["Mfn"]=$mfn;
  		$Permiso="|";
  		$P=explode("\n",$valortag[40]);
  		foreach ($P as $value){
  			$value=substr($value,2);
  			$ix=strpos($value,'^');
    		$Permiso.=substr($value,0,$ix)."|";
    	}		
 	}else{ 
		if ($userid!="") {
		
		echo "<script>
 		self.location.href=\"../index.php?id=".$userid."&cdb=".$arrHttp["cdb"]."&login=N&lang=".$lang."\";
 		</script>";
		 }else{
		echo "<script>
 		self.location.href=\"../index.php?login=N&lang=".$lang."\";
 		</script>";
		 }
  		die;
 	}
}



function ActualizarRegistro($variablesD,$opcion,$mfn){
$tabla = Array();

global $vars,$cipar,$from,$base,$ValorCapturado,$arrHttp,$ver,$valortag,$fdt,$tagisis,$cn,$msgstr,$tm,$lang_db,$MD5;
global $xtl,$xnr,$Mfn,$FdtHtml,$xWxis,$variables,$db_path,$Wxis,$default_values,$rec_validation,$wxisUrl,$validar,$tm;
global $max_cn_length;

	$variables_org=$variablesD;
	$ValorCapturado="";
	$VC="";
	
	//OJO
	$arrHttp["base"] = "users";
	$cipar = $arrHttp["base"].".par";
	
	
	
	if (isset($variablesD)){
	
		foreach ($variablesD as $key => $lin){
			$key=trim(substr($key,3));
			$k=$key;
			$ixPos=strpos($key,"_");
			if (!$ixPos===false) {
		    	$key=substr($key,0,$ixPos-1);
			}
			if (trim($key)!=""){
				if (strlen($key)==1) $key="000".$key;
				if (strlen($key)==2) $key="00".$key;
				if (strlen($key)==3) $key="0".$key;
				$lin=stripslashes($lin);
				$campo=array();
    			if ($dataentry!="xA")
						$campo=explode("\n",$lin);
					else
				$campo[]=str_replace("\n","",$lin);
				foreach($campo as $lin){
					$VC.=$k." ".$lin."\n";
					$ValorCapturado.=$key.$lin."\n";
					
				}
			}
		}
	}
	
	
	$valc=explode("\n",$ValorCapturado);
	
 	$ValorCapturado="";
 	$Eliminar="";
 	foreach ($valc as $v){
 		$v=trim($v);
		
 		if (trim(substr($v,0,4))!=""){
 		   $Eliminar.="d".substr($v,0,4);
		  
 		   if (trim(substr($v,4))!="")
				 $ValorCapturado.="<".substr($v,0,4)." 0>".substr($v,4)."</".substr($v,0,4).">";
		}
 	}
	
	$x=isset($default_values);
	$fatal_cn="";
	$fatal="";
	$error="";
	
            unset($validar);
					
			$file_val="";
			
			if ($file_val=="" or !file_exists($file_val)){
				$file_val=$db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["base"].".val";
				if (!file_exists($file_val))  $file_val=$db_path.$arrHttp["base"]."/def/".$lang_db."/".$arrHttp["base"].".val";
			}
			
			
 	
 		$ValorCapturado=urlencode($Eliminar.$ValorCapturado);
 	
	
	
	$IsisScript=$xWxis."actualizar.xis";
	
	if (file_exists($db_path."$base/data/stw.tab"))
		$stw="&stw=".$db_path."$base/data/stw.tab";
	else
		if (file_exists($db_path."stw.tab"))
			$stw="&stw=".$db_path."stw.tab";
		else
			$stw="";
			
 
	
  	$query = "&base=".$arrHttp["base"]."&cipar=$db_path"."par/".$cipar."&login=".$arrHttp["login"]."&Mfn=" .$mfn."&Opcion=".$opcion."$stw&ValorCapturado=".$ValorCapturado;
  	

	include("../../central/common/wxis_llamar.php");
   
}

function Session($llave){
 Global $arrHttp,$valortag,$Path,$xWxis,$Permiso,$msgstr,$db_path,$nombre,$userid,$lang;
 echo "<h1>".$res[2]."</h1>";
       
        $res=split("\|",$llave);
		$mfn=$res[2];
		$userid=$res[1];
  		$_SESSION["mfn_admin"]=$res[2];
  		$mfn=$res[2];
  		$nombre=$res[3];
		$arrHttp["Mfn"]=$mfn;
  		$Permiso="|";
  		$P=explode("\n",$valortag[40]);
  		
		foreach ($P as $value){
		    
  			$value=substr($value,2);			
  			$ix=strpos($value,'^');
    		$Permiso.=substr($value,0,$ix)."|";
			
    	}
		
}

function LeerRegistroLDAP() {

// la variable $llave permite retornar alguna marca que est� en el formato de salida
// identificada entre $$LLAVE= .....$$

$llave_pft="";
$myllave ="";
global $llamada,$valortag,$maxmfn,$arrHttp,$OS,$Bases,$xWxis,$Wxis,$Mfn,$db_path,$wxisUrl,$empwebservicequerylocation,$empwebserviceusersdb,$db,$EmpWeb,$MD5,$converter_path,$vectorAbrev;

$checkuser=$arrHttp["login"];

//Search the users database
$mx=$converter_path." ".$db_path."users/data/users \"pft=if v600='".$checkuser."' then mfn,'|',v20,'|',v30,'|',v10,'|',v10^a,'|',v10^b,'|',v18 fi\" now";

$outmx=array();
exec($mx,$outmx,$banderamx);
$textoutmx="";


for ($i = 0; $i < count($outmx); $i++) {
$textoutmx.=substr($outmx[$i], 0);
}


if ($textoutmx!="") {
	$splittxt=explode("|",$textoutmx);
	$myuser = $checkuser;

	//OJO
	$db = "users";
	$myllave = $splittxt[0]."|";
	$myllave .= $splittxt[1]."|";
	$myllave .= "1|";
	$myllave .= $splittxt[2]."|";
	$valortag[40] = $splittxt[3]."\n";
	$vectorAbrev['id']=$splittxt[1];
	$vectorAbrev['name']=$splittxt[2];
	$vectorAbrev['userClass']=$splittxt[5]."(".$splittxt[4].")";
	$vectorAbrev['expirationDate']=$splittxt[6];
	$currentdatem=date("Ymd");
	if ($splittxt[6]!="") if ($currentdatem>$splittxt[6]) $myllave=""; 
}
	 return $myllave;

} //LeerRegistroLDAP()

function VerificarUsuarioLDAP(){
    Global $arrHttp,$valortag,$Path,$xWxis,$session_id,$Permiso,$msgstr,$db_path,$nombre,$Per,$adm_login,$adm_password,$MD5,$lang,$ldap_usr_dom;
 	$variablesD = array();
	$login = false;
	$checkpass="";
	$checkpass  = ($MD5==0)? $arrHttp["password"]: md5($arrHttp["password"]);
	
	$login = false;
	try {
					if(Auth($arrHttp["login"], $arrHttp["password"],false))
					{	   
						$llave= LeerRegistroLDAP();
					   
						if($llave != ""){
									
							$variablesD["tag610"]=$checkpass;
							$mfn=explode("|",$llave);
							 
							ActualizarRegistro($variablesD,"actualizar",$mfn[0]);
							Session($llave);			
							$login = true;
							
						 }
						 else
							 {
								
                                $variablesD["tag20"]=$arrHttp["login"];									
								$variablesD["tag30"]=$arrHttp["login"];		
								$variablesD["tag160"]=$arrHttp["login"].$ldap_usr_dom;
								$variablesD["tag600"]=$arrHttp["login"];
								$variablesD["tag610"]=$checkpass;
								
								ActualizarRegistro($variablesD,"crear","New");
								Session("#|".$variablesD["tag600"]."|1|".$variablesD["tag600"]."|");								 
								$login = true;
								
							 }
					}
					else
						{
							$llave=LeerRegistro();
							if($llave != ""){
									Session($llave);
									$login = true;
								}  		   
						}
					
	 
	  } catch (Exception $e) {
         echo $e->getMessage();
		 exit;
     }
	 
	    if(!$login)
	    {
		  echo "<script>
 		  self.location.href=\"../indexmysite.php?login=N&lang=".$lang."\";
 		  </script>";
  		  die;
		}
	  
} //VerificarUsuarioLDAP()

/////
/////   INICIO DEL PROGRAMA
/////


$query="";
include("../../central/common/get_post.php");

//foreach ($arrHttp as $var => $value) echo "$var = $value<br>";die;



require_once("../../central/lang/mysite.php");
require_once("../../central/lang/lang.php");



if (isset($arrHttp["action"])) {
    if ($arrHttp["action"]!='clear')
    {
      $_SESSION["action"]=$arrHttp["action"];
      $_SESSION["recordId"]=$arrHttp["recordId"];
    }
    else
    {
      $_SESSION["action"]="";
      $_SESSION["recordId"]="";
    }
	if ($arrHttp["action"]=='gotosite')
    {
	$arrHttp["login"]=$_SESSION["login"];
	}
}
//if (!$_SESSION["userid"] || !$_SESSION["permiso"]=="mysite".$_SESSION["userid"]) {

      	if (isset($arrHttp["reinicio"])){
      		$arrHttp["login"]=$_SESSION["login"];
      		$arrHttp["startas"]=$_SESSION["permiso"];
      		$arrHttp["lang"]=$_SESSION["lang"];
            $arrHttp["db"]=$_SESSION["db"];

      	}	
		
        if($use_ldap) {		
		    VerificarUsuarioLDAP();
		} else	{ 
      	    VerificarUsuario();		
		}
      

      	
		if (!empty($arrHttp["id"])) {
		$_SESSION["action"]='reserve';
		$_SESSION["recordId"]=$arrHttp["id"];
		$_SESSION["cdb"]=$arrHttp["cdb"];
		} else {
        if (isset($lang)) $_SESSION["lang"]=$lang;
        if (isset($userid)) $_SESSION["userid"]=$userid;
      	if (isset($arrHttp["login"])) $_SESSION["login"]=$arrHttp["login"];
      	if (isset($userid)) $_SESSION["permiso"]="mysite".$userid;
      	if (isset($nombre)) $_SESSION["nombre"]=$nombre;
        $_SESSION["db"]=$db;
		}

//}

?>

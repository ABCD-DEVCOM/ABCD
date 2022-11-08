<?php
/* Modifications
20210311 fho4abcd Replaced helper code fragment by included file + minor html corrections + dont die always
20211216 fho4abcd Backbutton by included file, removed redundant help
20220711 fho4abcd Use $actparfolder as location for .par files
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
// Globales.
set_time_limit (0);
include("../common/get_post.php");
//foreach($arrHttp as $var=>$value) echo "$var=$value<br>";
include ("../config.php");
include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");
$backtoscript="../dataentry/administrar.php"; // The default return script

function MostrarPft(){
global $arrHttp,$xWxis,$Wxis,$db_path,$wxisUrl,$actparfolder;
	$IsisScript=$xWxis.$arrHttp["IsisScript"];
	if (!isset($arrHttp["from"])) $arrHttp["from"]="";
	if (!isset($arrHttp["count"])) $arrHttp["count"]="";
 	$query = "&base=".$arrHttp["base"]."&cipar=$db_path".$actparfolder.$arrHttp["cipar"]."&Opcion=".$arrHttp["Opcion"]."&from=".$arrHttp["from"]."&count=".$arrHttp["count"];
  	include("../common/wxis_llamar.php");
    return $contenido;

}

function VerStatus(){
	global $arrHttp,$xWxis,$OS,$Wxis,$db_path,$wxisUrl,$actparfolder;
	$IsisScript=$xWxis."administrar.xis";
	$query = "&base=".$arrHttp["base"] . "&cipar=$db_path".$actparfolder.$arrHttp["cipar"]."&Opcion=status";
 	include("../common/wxis_llamar.php");
	return $contenido;
}


include("../common/header.php");
?>
<body>
<div class="sectionInfo">
	<div class="breadcrumb">
<?php
switch ($arrHttp["Opcion"]){
	case "fullinv":
		echo $msgstr["mnt_gli"];
		break;
	case "unlockbd":
		echo $msgstr["mnt_desb"];
		break;
	case "unlock":
		echo $msgstr["mnt_dr"];
		break;
	case "inicializar":
		break;
	case "listar":
		echo $msgstr["mnt_rlb"];
		break;
	case "lisdelrec":
		echo $msgstr["mnt_lisdr"];
		break;
}
echo ": ".$arrHttp["base"];
?>
    </div>
	<div class="actions">
<?php
if ($arrHttp["Opcion"]!="fullinv"){
    include "../common/inc_back.php";
}
?>
	</div>
	<div class="spacer">&#160;</div>
	</div>
    <?php include "../common/inc_div-helper.php";?>
    <div class="middle form">
        <div class="formContent">
<?php
if ($wxisUrl!="") echo $wxisUrl."<br>";

switch ($arrHttp["Opcion"]) {
    case "inicializar":
    	$arrHttp["IsisScript"]="administrar.xis";
    	$contenido=VerStatus();
 		$ix=-1;
		foreach($contenido as $linea) {
			$ix=$ix+1;
			if ($ix>0) {
	   			$a=explode(":",$linea);
	   			$tag[$a[0]]=$a[1];
			}
		}
		if (!isset($arrHttp["borrar"])){
			if (isset($tag["BD"]) and $tag["BD"]!="N"){
				echo "<center><br><span class=td><h4>".$arrHttp["base"]."<br><font color=red>".$msgstr["bdexiste"]."</font><br>".$tag["MAXMFN"]." ".$msgstr["registros"]."<BR>";
				echo "<script>
					if (confirm(\"".$msgstr["elregistros"]." ??\")==true){
						borrarBd=true
					}else{
						borrarBd=false
					}
					if (borrarBd==true){
						if (confirm(\"".$msgstr["seguro"]." ??\")==true){
							borrarBd=true
						}else{
							borrarBd=false
						}
					}
					if (borrarBd==true)
						self.location=\"administrar_ex.php?base=".$arrHttp["base"]."&cipar=".$arrHttp["cipar"]."&Opcion=inicializar&borrar=true\"
					</script>";
			}else{

				$contenido=MostrarPft();
				foreach ($contenido as $linea){
				   	if (substr($linea,0,10)=='$$LASTMFN:'){
					     return $linea;
					}else{
			  			echo "$linea\n";
			  		}
			 	}
				$arrHttp["Opcion"]="unlockbd";
			}
		}else{
			$arrHttp["IsisScript"]="administrar.xis";
			$contenido=MostrarPft();
			foreach ($contenido as $linea){
			   	if (substr($linea,0,10)=='$$LASTMFN:'){
				     return $linea;
				}else{
		  			echo "$linea\n";
		  		}
		 	}
			$fp=fopen($db_path.$actparfolder.$arrHttp["base"].".par","r");
			if (!$fp){
				echo $arrHttp["base"].".par"." ".$msgstr["falta"];
				die;
			}
			$fp=file($db_path.$actparfolder.$arrHttp["base"].".par");
			foreach($fp as $value){
				$ixpos=strpos($value,'=');
				if ($ixpos===false){
				}else{
					if (substr($value,0,$ixpos)==$arrHttp["base"].".*"){
						$path=trim(substr($value,$ixpos+1));
						$ixpos=strrpos($path, '/');
						$path=substr($path,0,$ixpos)."/";
//						echo "<p>$path<p>";
						break;
					}
				}
			}
			$r=chmod ($path.$arrHttp["base"].".mst",0777);
			$r=chmod ($path.$arrHttp["base"].".xrf",0777);
			$r=chmod ($path.$arrHttp["base"].".cnt",0777);
			$r=chmod ($path.$arrHttp["base"].".ifp",0777);
			$r=chmod ($path.$arrHttp["base"].".n01",0777);
			$r=chmod ($path.$arrHttp["base"].".n02",0777);
			$r=chmod ($path.$arrHttp["base"].".l01",0777);
			$r=chmod ($path.$arrHttp["base"].".l02",0777);
			$arrHttp["Opcion"]="unlockbd";
		}
		break;
	case "fullinv":
		$contenido=VerStatus();
		$arrHttp["IsisScript"]="fullinv.xis";
		$contenido=MostrarPft();
		foreach ($contenido as $linea){
  			echo "$linea\n";
	 	}
	 	//DIE;
		break;
	case "listar":
	case "unlock":
	case "lisdelrec":
		$contenido=VerStatus();
		foreach ($contenido as $linea){
			if (substr($linea,0,7)=='MAXMFN:'){
				$maxmfn=trim(substr($linea,7));
				break;
			}
        }
        $arrHttp["from"]=$arrHttp["Mfn"];
		$arrHttp["count"]=$arrHttp["to"]-$arrHttp["from"]+1;
		$to=$arrHttp["to"]+$arrHttp["count"]+1;
		echo "<form name=forma1 method=post action=mfn_ask_range.php>";
		echo "<input type=hidden name=base value=".$arrHttp["base"].">";
		echo "<input type=hidden name=cipar value=".$arrHttp["cipar"].">";
		echo "<input type=hidden name=Opcion value=".$arrHttp["Opcion"].">";
		echo "<input type=hidden name=from value=".$arrHttp["from"].">";
		echo "<input type=hidden name=to value=".$arrHttp["to"].">";
		echo $msgstr["cg_from"]." = ".$arrHttp["from"]." - ".$msgstr["cg_to"]." = ".$arrHttp["to"]." (".$arrHttp["count"]." ".$msgstr["records"].")";
		echo "<table class=listTable>";
		switch ($arrHttp["Opcion"]){
			case "unlock":
				echo "<tr><th>Mfn</th><th>&nbsp;</th></tr>";
				break;
			case "listar":
				echo "<tr><th>Mfn</th><th>Locked by</th><th>Isis Status</th></tr>";
				break;
			case "lisdelrec":
				echo "<tr><th>Mfn</th><th></th></tr>";
				$opc_ant=$arrHttp["Opcion"];
				$arrHttp["Opcion"]="listar";
				break;
		}
		$arrHttp["IsisScript"]="administrar.xis";
		$contenido=MostrarPft();
        $nb=0;
        if (isset($opc_ant)) $arrHttp["Opcion"]=$opc_ant;
		foreach ($contenido as $value) {
			$value=trim($value);
			if ($value!=""){
				switch ($arrHttp["Opcion"]){
					case "unlock":
						$t=explode('|',$value);
						if (trim($t[1])=="UNLOCKED") $nb++;
						echo '<tr><td>'.$t[0]."</td><td>".$t[1]."</td></tr>\n";
						break;
					case "listar":
						$t=explode('|',$value);
						if (trim($t[2])!=""){
							$nb++;
							echo '<tr><td>'.$t[0]."</td><td>".$t[1]."</td><td>".$t[2]."</td></tr>\n";
						}
						break;
					case "lisdelrec":
						$t=explode('|',$value);
						if (trim($t[1])=="DELETED") {
							$nb++;
							echo '<tr><td>'.$t[0]."</td><td>".$t[1]."</td></tr>\n";
						}
						break;
				}
			}
		}
		echo "</table>";
		if ($arrHttp["Opcion"]!="lisdelrec"){
	        if ($nb==0){
	        	echo "<strong>".$msgstr["noblockedrecs"]."</strong>";
	        }else{
	        	echo $nb." ".$msgstr["blockedrecs"];
	        }
		}
		if ($arrHttp["to"]<$maxmfn){
			echo "<p><input type=submit value=".$msgstr["continuar"].">";
		}
		echo "</form>";
		break;
	case "unlockbd":
	   	$arrHttp["IsisScript"]="administrar.xis";
		$contenido=VerStatus();
		foreach ($contenido as $value) echo "$value<br>";
		echo "<p>".$msgstr["mnt_desb"];
		echo "<p>";
		$contenido=MostrarPft();
		foreach ($contenido as $value) echo "<dd>$value<br>";
		$contenido=VerStatus();
		foreach ($contenido as $value) echo "$value<br>";
		break;
}

echo "</div></div>";
include ("../common/footer.php");
?>

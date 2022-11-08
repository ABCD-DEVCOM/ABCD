<?php
/* Modifications
2021-03-02 fho4abcd Replaced helper code fragment by included file
2021-03-15 fho4abcd Replaced dbinfo code by included file
2021-04-15 fho4abcd use charset from config.php
2022-06-19 fho4abcd corrected html + translations + removed display of <base>/modulos.dat (unknown file)
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}

//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
include("../common/get_post.php");
include ('../config.php');
include("../lang/admin.php");
include("../lang/dbadmin.php");

unset($_SESSION["tagsel"]);
unset($_SESSION["count"]);

//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";
if (!isset($arrHttp["base"])) die;

include("../common/header.php");
?>
<body>
<script>
	function CreateFiles() {
	document.CreateFiles.action="../dbadmin/chk_dbdef.php"
	document.CreateFiles.encabezado.value="s"
	document.CreateFiles.target="_top"
	document.CreateFiles.submit();
}
</script>

<form name="CreateFiles" onSubmit="return false" method="post" accept-charset=utf-8>
	<input type=hidden name="encabezado" value="s">
	<input type=hidden name="base" value="<?php echo $arrHttp["base"]?>">
</form>	

<?php $wiki_help="Entrada_de_datos";include "../common/inc_div-helper.php"?>

<div class="middle" style="">
    <div class="formContent">

<?php

$archivo=$db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["base"].".fdt";
$cont="";
$warning="";


if (!file_exists($archivo)){
	$archivo=$db_path.$arrHttp["base"]."/def/".$lang_db."/".$arrHttp["base"].".fdt";
	if (!file_exists($archivo)){
		echo "<h4>".$msgstr["misfile"]." ".$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["base"].".fdt</h4>";
		echo "<p>".$msgstr["cf_notice"]."</p>";
		echo '<a class="button" href="javascript:CreateFiles();">'.$msgstr["cf_createfiles"].'</a>';
		$cont="N";
	}
}
$archivo=$db_path.$arrHttp["base"]."/data/".$arrHttp["base"].".fst";
if (!file_exists($archivo)){
	echo  "<h4>".$msgstr["fatal"].".. ".$msgstr["misfile"]."  ".$arrHttp["base"]."/data/".$arrHttp["base"].".fst";
	$cont="N";
}

$archivo=$db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/".$arrHttp["base"].".pft";
if (!file_exists($archivo)){
    $archivo=$db_path.$arrHttp["base"]."/pfts/".$lang_db."/".$arrHttp["base"].".pft";
    if (!file_exists($archivo))
		$warning="<br><h6>".$msgstr["warning"]."... ".$msgstr["misfile"]." ".$arrHttp["base"]."/pfts/".$_SESSION['lang']."/".$arrHttp["base"].".pft";
}


if ($cont=="N") die;

// Get info about the current database from the database
include("../common/inc_get-dbinfo.php");
echo "<script>top.maxmfn=".$arrHttp["MAXMFN"]."
	top.mfn=0
	top.wks=''
	top.Formato=''
	top.RegistrosSeleccionados=''
	top.RegSel_pos=0\n";

$i=-1;

//Se leen los formatos de salida disponibles
unset($fp);
if (!isset($arrHttp["inicio"])){   //indica que no se van a colocar los formatos en el toolbar
	if (file_exists($db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/formatos.dat")){
		$fp = file($db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/formatos.dat");
	}else{
		if (file_exists($db_path.$arrHttp["base"]."/pfts/".$lang_db."/formatos.dat")){
			$fp = file($db_path.$arrHttp["base"]."/pfts/".$lang_db."/formatos.dat");
		}
	}
	echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.formato.options.length=0\n";
	$i=-1;
	if (isset($fp)) {
		foreach($fp as $linea){
			if (trim($linea)!="") {
				$linea=trim($linea);
				$ll=explode('|',$linea);
				$cod=$ll[0];
				$nom=$ll[1];
				if (isset($_SESSION["permiso"][$arrHttp["base"]."_pft_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"]."_pft_".$ll[0]]) or isset($_SESSION["permiso"][$arrHttp["base"]."_CENTRAL_ALL"])
						or isset($_SESSION["permiso"]["CENTRAL_ALL"])){
					$i=$i+1;
					echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.formato.options[$i]=new Option('$nom','$cod')\n";
				}
			}
		}

	}
	$i=$i+1;
	if (isset($_SESSION["permiso"][$arrHttp["base"]."_pft_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or $_SESSION["permiso"][$arrHttp["base"]."_CENTRAL_ALL"]){
		echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.formato.options[$i]=new Option('".$msgstr["noformat"]."','')\n";
		echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.formato.options[$i+1]=new Option('".$msgstr["all"]."','ALL')\n";
	}
	unset($fp);
	echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.wks.options.length=0\n";
	//Se leen las hojas de entrada disponibles
	if (file_exists($db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/formatos.wks")){
		$fp = file($db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/formatos.wks");
	}else{
		if (file_exists($db_path.$arrHttp["base"]."/def/".$lang_db."/formatos.wks"))
			$fp = file($db_path.$arrHttp["base"]."/def/".$lang_db."/formatos.wks");
	}
	$i=0;
	$wks_p=array();
	if (isset($fp)) {
		foreach($fp as $linea){
			if (trim($linea)!="") {
				$linea=trim($linea);
				$l=explode('|',$linea);
				$cod=trim($l[0]);
				$nom=trim($l[1]);
				if (isset($_SESSION["permiso"][$arrHttp["base"]."_fmt_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"]."_fmt_".$cod] )
						or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"]."_CENTRAL_ALL"])){
					$i=$i+1;
					$wks_p[$cod]="Y";
					echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.wks.options[$i]=new Option('$nom','$cod')\n";
				}
			}
		}
	}
	$i=$i+1;
	//Se lee la tabla de tipos de registro
	unset($fp);
	if (file_exists($db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/typeofrecord.tab")){
		$fp = file($db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/typeofrecord.tab");
	}else{
		if (file_exists($db_path.$arrHttp["base"]."/def/".$lang_db."/typeofrecord.tab"))
			$fp = file($db_path.$arrHttp["base"]."/def/".$lang_db."/typeofrecord.tab");
	}
	$i=0;
	$typeofr="";
	if (isset($fp)) {
		foreach($fp as $linea){
	           if ($i==0){
	           	$l=explode(" ",$linea);
	           	echo "top.tl='".trim($l[0])."'\n";
	           	if (isset($l[1]))
	           		echo "top.nr='".trim($l[1])."'\n";
	           	else
	           	    echo "top.nr=''\n";
	           	$i=1;
	           }else{
				if (trim($linea)!="") {
					$l=explode('|',$linea);
					$cod=$l[0];
					$ix=strpos($cod,".");
					$cod=substr($cod,0,$ix);
					if (isset($wks_p[$cod]))
						$typeofr.=trim($linea)."$$$";
	    		}
			}
		}
		echo "top.typeofrecord=\"$typeofr\"\n";
	}else{
		echo "top.typeofrecord=\"\"\n";
	}
}

//Se lee la fdt para determinar el prefijo y el formato de extraccion del campo del indice de la entrada principal
unset($fp);
$archivo=$db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["base"].".fdt";
if (file_exists($archivo)){
	$fp=file($archivo);
}else{
	$archivo=$db_path.$arrHttp["base"]."/def/".$lang_db."/".$arrHttp["base"].".fdt";
	if (file_exists($archivo)) $fp=file($archivo);
}
if (!$fp){
	echo $msgstr["misfile"]. " ".$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["base"].".fdt";
	//die;
}
$pi="";
$fe="";
$HTML="";
$URL="";
foreach($fp as $linea){
	if (trim($linea)!="") {
		$fdt=explode('|',$linea);
		if ($fdt[7]=="B") $HTML=$fdt[1];  //LOAD EXTERNAL TEXT FILE  IN THIS TAG
		if ($fdt[7]=="H") $URL=$fdt[1];   //URL TO TH DOCUMENT LOADED IN $HTML
		if ($fdt[3]==1){                  //MAIN FIELD ALPHABETIC INDEX
			$pi=$fdt[12];
			$fe=$fdt[13];
			if (trim($fe=="")){
				$fe="v".trim($fdt[1]);
			}
		}
	}
}

echo "
html='$HTML'
url='$URL'
top.prefijo_indice='$pi'
top.formato_indice='".urlencode($fe)."'
if (html=='' && top.HTML==''){        //No changes in the toolbar

}else{
	top.HTML='$HTML'
	top.URL='$URL'
	top.lock_db=\"\"
	top.menu.location.href=\"menu_main.php?base=\"+top.base+\"&reload=N\"
}
</script>";


?>

<br><br><br>
<?php

echo "<center><b>".$msgstr["bd"].": ".$arrHttp["base"]."</b>";
echo "<br><strong>$charset</strong>" ;
echo "<br><b><font color=darkred>". $msgstr["maxmfn"].": ".$arrHttp["MAXMFN"]."</font></b>";

if ($arrHttp["BD"]=="N")
	echo "<p>".$msgstr["database"]." ".$msgstr["ne"];
if ($arrHttp["IF"]=="N")
	echo "<p>".$msgstr["if"]." ".$msgstr["ne"];
if ($arrHttp["EXCLUSIVEWRITELOCK"]!=0) {
	echo "<p>".$msgstr["database"]." ".$msgstr["exwritelock"]."=".$arrHttp["EXCLUSIVEWRITELOCK"].". ".$msgstr["contactdbadm"]."
	<script>top.lock_db='Y'</script>
	";

}
echo $warning;

if ($wxisUrl!=""){
	echo "<p>".$msgstr['showisisversion'].": ".$wxisUrl."</p>";
}else{
	$ix=strpos($Wxis,"cgi-bin");
	$wxs=substr($Wxis,$ix);
    echo "<p>CISIS version: ".$wxs."</p>";
}
?>
</center>
</div>
</div>
<?php include("../common/footer.php");?>

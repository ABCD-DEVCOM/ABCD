<?php
include ("conf_opac_top.php");
$wiki_help="OPAC-ABCD_Apariencia#Mensajes_del_sistema";
include "../../common/inc_div-helper.php";

?>

<div class="middle form">
   <h3><?php echo $msgstr["sys_msg"];?>
	</h3>
	<div class="formContent">

<div id="page">

<?php

if (!isset($_SESSION["db_path"])){
	echo "Session expired";die;
}
if (isset($_REQUEST["lang"])) $_SESSION["lang"]=$_REQUEST["lang"];

if (isset($msg_path) and $msg_path!="")
	$path=$msg_path;
else
	$path=$db_path;


if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"]=="Actualizar"){
	foreach ($_REQUEST as $var=>$value){
		if (trim($value)!=""){
			$code=explode("_",$var);
			if ($code[0]=="conf"){
				if ($code[1]=="lc"){
					if (!isset($cod_idioma[$code[2]])){
						$cod_msg[$code[2]]=$value;
					}
				}else{
					if (!isset($nom_idioma[$code[2]])){
						$nom_msg[$code[2]]=$value;
					}
				}
			}
		}
	}
   	$a=$path."lang/".$_REQUEST["lang"]."/opac.tab";
    $fout=fopen($path."lang/".$_REQUEST["lang"]."/opac.tab","w");
	foreach ($cod_msg as $key=>$value){
  	 	fwrite($fout,$value."=".$nom_msg[$key]."\n");
		echo $value."=".$nom_msg[$key]."<br>";
	}
	fclose($fout);
	echo "<h2>../lang/".$_REQUEST["lang"]."/opac.tab"." ".$msgstr["updated"]."</h2>";
	die;
}

$a=$path."lang/".$_SESSION["lang"]."/opac.tab";
echo $a."<br>";
if (file_exists($a)) {
	$fp=file($a);
	foreach($fp as $var=>$value){
		$value=str_replace('"',"'",$value);
		$value=str_replace("'","'",$value);
		if (trim($value)!="") {
			$m=explode('=',$value,2);
			$m[0]=trim($m[0]);
			if (!isset($msgstr[$m[0]])) $msgstr[$m[0]]=trim($m[1]);
		}
	}
}

$a=$path."lang/en/opac.tab";
if (file_exists($a)) {
	$fp=file($a);
	foreach($fp as $var=>$value){
		$value=str_replace('"',"'",$value);
		$value=str_replace("'","'",$value);
		if (trim($value)!="") {
			$m=explode('=',$value,2);
			$m[0]=trim($m[0]);
			if (!isset($msgstr[$m[0]])) $msgstr[$m[0]]=trim($m[1]);
		}
	}
}


?>
<form name=actualizar method=post>
<?php
$ix=0;
echo "<table>";
foreach ($msgstr as $key=>$value){
	$ix=$ix+1;
	echo "<tr><td><input type=hidden name=conf_lc_".$ix." size=20 value=\"".trim($key)."\">".trim($key)."</td>";
	echo "<td><input type=text name=conf_ln_".$ix." size=100 value=\"".trim($value)."\"></td>";
	echo "</tr>";
}

echo "</table>";
echo "<input type=submit value=\"".$msgstr["save"]."\">";
echo "<input type=hidden name=lang value=".$_REQUEST["lang"].">\n";
echo "<input type=hidden name=Opcion value=Actualizar>";
?>
</form>

</div>    
</div>    
</div>    

<?php include ("../../common/footer.php"); ?>


<?php
/*
20220107 Improve html
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include ("../config.php");
$lang= $_SESSION["lang"];

include("../lang/admin.php");
include("../lang/soporte.php");
$backtoscript="../dataentry/administrar.php"; // The default return script
if ( isset($arrHttp["backtoscript"])) $backtoscript=$arrHttp["backtoscript"];
include("../common/header.php");


//foreach ($arrHttp as $var=>$value) 	echo "$var = $value<br>";
?>
<div class="sectionInfo">
	<div class="breadcrumb">
    <?php echo $msgstr["cnv_".$arrHttp["accion"]]." ".$msgstr["cnv_".$arrHttp["tipo"]]?>
	</div>
	<div class="actions">
    <?php include "../common/inc_back.php";?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php $ayuda="txt2isis.html";include "../common/inc_div-helper.php" ?>
<div class="middle form">
<div class="formContent">
<?php
$file=$db_path.$arrHttp["base"]."/cnv/".$arrHttp["fn"].".cnv";

$fp = fopen($file,"w");
if (!$fp){
	echo "<center><br><br><h1><b><font color=red>admin/php/$file</font></b> ".$msgstr["revisarpermisos"]."</h1>";
	die;
}
$value=explode('!!',$arrHttp["tablacnv"]);
if (isset($arrHttp["headermode"]) and $arrHttp["headermode"]=="on")
	fwrite($fp,"[HEADER]\n");
elseif (isset($arrHttp["delimited"]) and $arrHttp["delimited"]=="on")
	fwrite($fp,"[TABS]\n");
else
	fwrite($fp,$arrHttp["separador"]."\n");
foreach ($value as $tab){
	$tab=stripslashes($tab);
	$tab=str_replace("'","`",$tab);
	fwrite($fp,$tab."\n");
}
fclose($fp);
?>
<center><br><br>
<h3><?php echo $msgstr["okactualizado"]." &rarr; ".$file?></h3>
<br>
<a href="carga_txt_cnv.php?base=<?php echo $arrHttp["base"]."&accion=".$arrHttp["accion"]."&tipo=".$arrHttp["tipo"]?>"
                        class="bt bt-green" title='<?php echo $msgstr["cnv_sel"]?>'>
                        <i class="fas fa-check"></i>&nbsp;<?php echo $msgstr["continuar"]?></a>
</div>
</div>
<?php
include "../common/footer.php";
?>
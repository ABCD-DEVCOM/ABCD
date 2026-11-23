<?php
/* Modifications
20211216 fho4abcd Backbutton & helper by included file. Improve html
20261119 fho4abcd Remove preloader gif. Improved user interface. Correct html
*/
/**
 * @program:	ABCD - ABCD-Central - http://reddes.bvsaude.org/projects/abcd
 * @copyright:	Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:		copy_db_ex.php
 * @desc:		Search form for z3950 record importing
 * @author:		Guilda Ascencio
 * @since:		20091203
 * @version:	1.0
 *
 * == BEGIN LICENSE ==
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Lesser General Public License as
 *	published by the Free Software Foundation, either version 3 of the
 *	License, or (at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Lesser General Public License for more details.
 *
 *	You should have received a copy of the GNU Lesser General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * == END LICENSE ==
*/
session_start();
set_time_limit(0);
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
//foreach ($arrHttp as $var=>$value)  echo "$var=$value<br>";
//die;
include("../config.php");
$lang=$_SESSION["lang"];
$backtoscript="../dbadmin/menu_mantenimiento.php";

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");
include("../common/inc_check_referrer.php");

include("../common/header.php");
$arrHttp["copyname"] =str_replace(".mst","",$arrHttp["copyname"]);

?>
<body>
<?php
include("../common/institutional_info.php");
if (isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or  isset($_SESSION["permiso"]["CENTRAL_ALL"])
	or isset($_SESSION["permiso"][$db."_CENTRAL_DBUTILS"]) or isset($_SESSION["permiso"][$db."_CENTRAL_ALL"])){
}else{
	echo $msgstr["invalidright"];
	die;
}
?>
<script>
function Confirmar(){
	document.continuar.confirmar.value="OK";
	document.continuar.submit()
}


function Regresar(){
	document.continuar.action="copy_db.php";
	document.continuar.submit()
}

function ActivarMx(){
	document.continuar.action="../utilities/mx_dbread.php";
	document.continuar.submit()
}
</script>
<div class="sectionInfo">
	<div class="breadcrumb">
	<?php echo $msgstr["db_cp"].": ".$arrHttp["base"]?>
	</div>
	<div class="actions">
	<?php include "../common/inc_back.php"; ?>
	<?php include "../common/inc_home.php";?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php
$ayuda="copy_db.html";
include "../common/inc_div-helper.php";
?>
<div class="middle form">
<div class="formContent">
<?php
$err="";
$from=$db_path.$arrHttp["base"]."/data/".$arrHttp["base"];
$to=$arrHttp["storein"];
$copyname=$arrHttp["copyname"];
if (substr($to,0,1)=="/") $to=substr($to,1);
if (substr($to,strlen($to)-1,1)=="/") $to=substr($to,0,strlen($to)-1);
$copyname=str_replace('/',"",$copyname);
$to=$db_path.$to."/".$arrHttp["copyname"];
?>
<h4><?php echo "$from .* => $to.*";?></h4>
<form name=continuar action=copy_db_ex.php method=post>
	<?php
	foreach ($_REQUEST as $var=>$value){
		echo "<input type=hidden name=$var value=\"$value\">\n";
	}
	if (!isset($arrHttp["confirmar"]) or (isset($arrHttp["confirmar"]) and $arrHttp["confirmar"]!="OK")){
		if ( $arrHttp["cpreorg"]=="cpreo" ) {
			echo "<h4>".$msgstr["confirm_reodb"]."</h4>";
		}
		else {
			echo "<h4>".$msgstr["confirm_copydb"]."</h4>";
		}
		?>
		<br>
		<input type=hidden name=confirmar>
		<input type=button class=bt-green name=continuar value="<?php echo $msgstr["continuar"];?>" onclick=Confirmar()>
		&nbsp; &nbsp;
		<input type=button class=bt-red   name=cancelar value="<?php echo $msgstr["cancelar"];?>" onclick=Regresar()>
		</form>
		</div>
		</div>
		<?php
		include("../common/footer.php");
		die;
	}
	?>
</form>
<?php
if ($arrHttp["cpreorg"]=="cpreo"){
	$mxcp_path=$cisis_path."mxcp".$exe_ext;
	if (!file_exists($mxcp_path)){
		echo "<font color=red><strong>".$msgstr["missing"]." ". $mxcp_path;
		die;
	}
	/* Command mxcp is described in CISIS Utilities - Reference Manual Version 5.2
	** Following options are accepted but give never any output here: "tell=1", "log=<file.log>"
	** Option "clean" cannot be tested as ABCD performs already a trim on save record
	*/
	$commandline=$mxcp_path." ".$from." create=".$to;
	echo "<font face='courier new'>Command line: ".$commandline."</font><br> ";
	$res=exec($commandline,$contenido,$resultado);
	foreach ($contenido as $var=>$value)  echo "$var=$value<br>";
	if ($resultado==0){
		echo "<p>".$from.".mst ".$msgstr["reorganized"]."</p>";
	}else{
		$err="Y";
	}
}else{
	$res=copy($from.".mst",$to.".mst");
	if ($res==1){
		echo $from.".mst => ".$to.".mst ".$msgstr["copied"]."<br>";
		$res=copy($from.".xrf",$to.".xrf");
		if ($res==1){
			echo $from.".xrf => ".$to.".xrf  ".$msgstr["copied"];
		}else
			$err="Y";
	}else{
		$err="Y";
	}
}
if ($err==""){
	?>
	<br><br>
	<input type=button class=bt-green name=mxread value="<?php echo $msgstr["mx_dbread"]?>" onclick=ActivarMx()>
	<?php
}

?>
</div>
</div>
<?php
include("../common/footer.php");
?>


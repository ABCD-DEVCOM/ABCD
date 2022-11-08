<?php
/* Modifications
20211216 fho4abcd Backbutton & helper by included file. Improve html
*/
/**
 * @program:   ABCD - ABCD-Central - http://reddes.bvsaude.org/projects/abcd
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      copy_db_ex.php
 * @desc:      Search form for z3950 record importing
 * @author:    Guilda Ascencio
 * @since:     20091203
 * @version:   1.0
 *
 * == BEGIN LICENSE ==
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Lesser General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
$arrHttp["copyname"] =str_replace(".mst","",$arrHttp["copyname"]);
include("../config.php");
$lang=$_SESSION["lang"];
$backtoscript="../dbadmin/menu_mantenimiento.php";

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");

include("../common/header.php");
include("../common/institutional_info.php");

function Confirmar(){
global $msgstr;
	echo "<h4>".$msgstr["confirm_copydb"]."</h4>";
	echo "<input type=button name=continuar value=\"".$msgstr["continuar"]."\" onclick=Confirmar()>";
	echo "&nbsp; &nbsp;<input type=button name=cancelar value=\"".$msgstr["cancelar"]."\" onclick=Regresar()>";
	echo "</body></html>";

}

?>
<script>
function Confirmar(){
	document.continuar.confirmar.value="OK";
	document.getElementById('loading').style.display='block';
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
<body>
<form name=continuar action=copy_db_ex.php method=post>
<?php
foreach ($_REQUEST as $var=>$value){
	echo "<input type=hidden name=$var value=\"$value\">\n";
}
?>
<input type=hidden name=confirmar>
</form>
<div id="loading">
  <img id="loading-image" src="../dataentry/img/preloader.gif" alt="Loading..." />
</div>
<div class="sectionInfo">
	<div class="breadcrumb">
    <?php echo $msgstr["db_cp"].": ".$arrHttp["base"]?>
	</div>
    <div class="actions">
    <?php include "../common/inc_back.php"; ?>
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
$OS=PHP_OS;

echo "<H4>" . $from .".mst => $to.mst</H4>";
if (isset($arrHttp["reorganize"])){
    $mxcp_path=$cisis_path."mxcp".$exe_ext;
   if (!file_exists($mxcp_path)){
    	echo "<font color=red><strong>".$msgstr["missing"]." ". $mxcp_path;
    	die;
    }
    echo "<font face='courier new'>Command line: ".$mxcp_path." $from create=$to</font><br> ";
 }
if (!isset($arrHttp["confirmar"]) or (isset($arrHttp["confirmar"]) and $arrHttp["confirmar"]!="OK")){
	Confirmar();
	die;
}
if (isset($arrHttp["reorganize"])){
	$res=exec($mxcp_path." $from create=$to tell=1 log=$to-res",$contenido,$resultado);
	if ($resultado==0){
		echo $from.".mst ".$msgstr["reorganized"]."<p>";
	}else{
		$err="Y";
	}
}else{
	$res=copy($from.".mst",$to.".mst");
	if ($res==1){
		echo $from.".mst => ".$to.".mst ".$msgstr["copied"]."<br>";
		$res=copy($from.".xrf",$to.".xrf");
		if ($res==1){
			echo $from.".xrf => ".$to.".xrf  ".$msgstr["copied"]."<P>";
		}else
			$err="Y";
	}else{
		$err="Y";
	}
}
if ($err==""){
	echo "<br><input type=button name=mxread value=\"".$msgstr["mx_dbread"]."\" onclick=ActivarMx()>\n";
}

?>
</div>
</div>
<?php
include("../common/footer.php");
?>


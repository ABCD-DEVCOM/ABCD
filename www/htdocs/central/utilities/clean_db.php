<?php
/* Modifications
20210414 fho4abcd Rewrite(helper code fragment,add confirm, correct error checks,html code..)
20210605 fho4abcd Remove isotag1. Improves operation for standard db's.Translations
20211215 fho4abcd Backbutton by included file
20221222 fho4abcd Add outisotag1=3000/isotag1=3000 to export/import to preserve leaderinfo
20250924 fho4abcd Honour backtoscript, translations, new buttons
20261124 fho4abcd Changed preloader by flush. Menu item for isotag. Button for inverted file generation
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"]="en";
include("../common/get_post.php");
include("../config.php");
$lang=$_SESSION["lang"];
include("../common/header.php");
include("../lang/dbadmin.php");
include("../lang/admin.php");
include("../lang/soporte.php");
include("../common/inc_check_referrer.php");
//====================== Functions =================
// Function to show a "confirm" and "cancel" button. Actions by corresponding script
function Confirmar(){
	global $msgstr;
	?>
	<br><br>
	<div align=center>
		<button type="submit" class="bt-green"  name=continuar onclick=Confirmar()>
		<i class="fa fa-plane-departure"></i> <?php echo $msgstr["procesar"]?>
	</button> 
	&nbsp; &nbsp;
		<button type="submit" class="bt-red"  name=continuar onclick=Regresar()>
		<i class="fa fa-ban"></i> <?php echo $msgstr["cancelar"]?>
	</button> 
	</div>
	<?php
}
//----------------------- End functions --------------------------------------------------
$backtoscript="../dbadmin/menu_mantenimiento.php";
if (isset($arrHttp["backtoscript"])) $backtoscript=$arrHttp["backtoscript"];
$base=$arrHttp["base"];
$bd=$db_path.$base;
$bd_mst=$bd."/data/".$base;
$bd_mst_full=$bd_mst.".mst";
$fullisoname=$db_path."wrk/".$base."_tmp.iso";
?>
<body>
<script>
var win
function Confirmar(){
	document.continuar.confirmcount.value++;
	document.continuar.submit()
}
function Regresar(){
	document.continuar.action='<?php echo $backtoscript;?>'
	document.continuar.submit()
}
</script>
<?php
// Show institutional info
include "../common/institutional_info.php";
include ("../common/inc_get-dbinfo.php");
include ("../common/inc_get-dblongname.php");
?>
<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["db_clean"].": ".$base;?>
	</div>
	<div class="actions">
	<?php include "../common/inc_back.php";?>
	<?php include "../common/inc_home.php";?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php include "../common/inc_div-helper.php" ?>
<div class="middle form">
	<div class="formContent">
		<div align=center><h3><?php echo $msgstr["db_clean"]." : ".$arrHttp["dblongname"]." (".$base.")";?></h3></div>
<?php
$initsize=number_format(filesize($bd_mst_full),0,',','.');
$init_status="<b><font color=darkred>".$msgstr["maxmfn"].": ".$arrHttp["MAXMFN"];
$init_status.="&rarr; ".$msgstr["file_size"].": ".$initsize."</font></b><br>";

// First screen: Show menu and ask for confirmation
if (!isset($arrHttp["confirmcount"])) {
	// Show the "loading" marker initially hidden and absolutely positioned in about the center
	// Create a form for submission and request confirmation
	?>
	<div align=center>
		<?php echo $msgstr["currentstatus"].": ".$init_status;?>
	</div><br>
	<form name=continuar  method=post >
		<input type=hidden name=confirmcount value=0>
		<?php
		foreach ($_REQUEST as $var=>$value){
			// some values may contain quotes or other "non-standard" values
			$value=htmlspecialchars($value);
			echo "<input type=hidden name=$var value=\"$value\">\n";
		}
		?>
		<table cellspacing=5 align=center>
			<tr>
			  <td><label for=importisotag><?php echo $msgstr["useimportisotag"]?></label></td>
			  <td><input type=checkbox name=importisotag id=importisotag value=importisotag></td>
			  <td><font color=blue><?php echo $msgstr["unch_noleaderimport"]?></font></td>
			</tr>
		</table>
		<?php
		Confirmar();
		?>
	</form>
	<?php
} else{
	// Second screen: execute the function
	ob_end_flush();
	ob_start();
	$numerr=0;
	echo $init_status;
	if (file_exists($fullisoname)) {
		unlink($fullisoname);
	}
	$outisotag1="";
	$isotag1="";
	if ( isset($arrHttp["importisotag"]) and $arrHttp["importisotag"] != "" ) {
		$outisotag1="outisotag1=3000";
		$isotag1="isotag1=3000";
	}
	$strINV=$mx_path." ".$bd_mst." iso=$fullisoname  $outisotag1 -all now 2>&1";
	echo $msgstr["commandline"].": $strINV<br>";
	$strINV=$mx_path." ".$bd_mst." iso=$fullisoname  $outisotag1 -all now 2>&1";
	ob_flush();flush();
	exec($strINV, $output,$status);
	$straux="";
	for($i=0;$i<count($output);$i++){
		$straux.=$output[$i]."<br>";
	}
	if($status==0) {
		echo ("<strong>".$msgstr["export_process_ok"]."</strong>");
		echo "$straux"."<br>";
	} else {
		echo ("<h3><font color='red'><br>".$msgstr["processfailed"]."</font></h3>");
		echo "<font color='red'>".$straux."</font>";
		$numerr++;
	}
	if ( file_exists($fullisoname)) {
		echo $msgstr["saved"]." ".$msgstr["cnv_iso"]." $fullisoname<br>";
	}
	echo "<br><hr>";
	ob_flush();flush();
	//importing iso
	if ($numerr==0){
		$strINV=$mx_path." iso=$fullisoname create=".$db_path.$base."/data/".$base." $isotag1 -all now 2>&1";
		echo "<br>".$msgstr["commandline"].": $strINV<br>";
		exec($strINV, $output,$status);
		$straux="";
		for($i=0;$i<count($output);$i++){
			$straux.=$output[$i]."<br>";
		}
		if($status==0) {
			echo ("<strong>".$msgstr["import_process_ok"]."</strong>");
			echo "$straux"."<br>";
		} else {
			echo ("<h3><font color='red'><br>".$msgstr["processfailed"]."</font></h3>");
			echo "<font color='red'>".$straux."</font>";
			$numerr++;
		}
	}
	if (file_exists($fullisoname)) {
		if ( unlink($fullisoname)==true) echo $msgstr["eliminados"]." ".$msgstr["cnv_iso"]." $fullisoname";
	}
	$aftersize=number_format(filesize($bd_mst_full),0,',','.');
	get_dbinfo();// declared in "../common/inc_get-dbinfo.php"
	$aftermsg="<br><b><font color=darkred>".$msgstr["maxmfn"].": ".$arrHttp["MAXMFN"];
	$aftermsg.="&rarr; Size: ".$aftersize."</font></b><br>";
	if ($numerr==0){
		echo $aftermsg;
		echo "<br><br>";
		echo "<a class='bt bt-green mb-2' href=fullinv.php?base=".$arrHttp["base"]."&inframe=0&backtoscript=".$backtoscript.">"."<i class='fas fa-database'></i> ". $msgstr["mnt_gli"]."</a>";
	}
}
?>
</div>
</div>
<?php
include("../common/footer.php");
?>

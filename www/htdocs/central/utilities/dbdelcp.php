<?php
/*
20261127 fho4abcd Created
*/
session_start();
set_time_limit(0);
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
//foreach ($arrHttp as $var=>$value)  echo "top: $var=$value<br>";
//die;

include("../config.php");
$lang=$_SESSION["lang"];

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");
include("../common/inc_check_referrer.php");

include("../common/header.php");
?>
<body>
<?php
include("../common/institutional_info.php");
include("inc_def_cprest.php");
$backtoscript="../dbadmin/menu_mantenimiento.php"; // The default return script
if (isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or  isset($_SESSION["permiso"]["CENTRAL_ALL"])
	or isset($_SESSION["permiso"][$db."_CENTRAL_DBUTILS"]) or isset($_SESSION["permiso"][$db."_CENTRAL_ALL"])){
}else{
	echo $msgstr["invalidright"];
	die;
}
///////////////////////////////////////////////
Function Explorar(){
global $msgstr, $arrHttp, $def_cprest_folder, $def_cprest_folder_sel, $db_path;
?>
	<h3><?php echo $msgstr["deldbcp_desc"]." : ".$arrHttp["base"].".mst + ".$arrHttp["base"].".xrf";?></h3>
	<form name="upload" method="post" >
	<?php
	foreach ($arrHttp as $var=>$value)  echo "<input type=hidden name=$var value=\"$value\">\n";
	?>
	<table>
	<tr>
		<td><label><?php echo $msgstr["mx_folder"]." ".$msgstr["resdb_with"];?></label></td>
		<td><input type="text" name="storein" size="30" value=<?php echo $def_cprest_folder?> onclick="javascript:blur()"></td>
				<?php
				if ($def_cprest_folder_sel==true) {
					?>
					<td><a class="bt bt-blue" href="javascript:Explorar()"> <?php echo $msgstr["explore"];?></a></td>
					<?php
				} else {
					?>
					<td><?php echo $msgstr["db_noselect"];?></td>
					<?php
				}
				?>
	</table>
	<input class="bt-blue" type="submit" value="<?php echo $msgstr["continuar"];?>">
 	</form>
	</div>
	</div>

<?php 
	include("../common/footer.php");
	die;
}

function ShowDatabases($storein,$db_path){
global $msgstr,$arrHttp;
	$Dir=$db_path.$storein;
	$handle = opendir($Dir);
	$ix=0;
	$nr_boxes=0;
	?>
	<table bgcolor=#cccccc border=0 cellpadding=8>
	<?php
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$f=$file;
			$file=$Dir."/".$file;
			$basefile=$arrHttp["base"];
			if(is_file($file) and pathinfo ( $file , PATHINFO_EXTENSION)=="mst" ){
				$ix=$ix+1;
				$ixpos=strpos($f,".");
				if ($ixpos>0){
					$fmst=substr($f,0,$ixpos);
					$dateFormat = "D d M Y g:i A";
					$ctime = filectime($file);
					/*
					** Disable selection of the target database by a wrong group name and disabled attribute
					** Can occur if the copies are in the same folder as the target database
					*/
					if ($basefile==pathinfo ( $file , PATHINFO_FILENAME)){
						?>
						<tr><td><input type=checkbox disabled > <?php echo $f;?>
						</td>
						<?php
					}else{
						$nr_boxes++;
						?>
						<tr bgcolor=white>
						<td><input type=checkbox id='<?php echo $fmst;?>' name=db_sel_<?php echo "(string)$nr_boxes";?> value='<?php echo $fmst;?>'>
						<label for='<?php echo $fmst;?>'> <?php echo $f;?></label>
						</td>
						<?php
					}
					?>
					<td><?php echo date($dateFormat, $ctime);?></td>
					<td><?php echo number_format(filesize($file))." ".$msgstr["cpdb_bytes"];?></td></tr>
					<?php
				}
 	   		}
		}
	}
	?>
	</table>
	<input type=hidden name=select_copies value=true>
	<?php
	if ($ix==0){
		echo "<h4>".$msgstr["mx_nodb"]."</h4>";
		die;
	}
	closedir($handle);
	?>
	<br><a class='bt bt-red' type=submit href="javascript:Confirmar()">
	<i class="fas fa-trash-alt"></i> <?php echo $msgstr["deldbcp_del"]?></a>
	<?php
}
?>
<script language="javascript" src="../dataentry/js/lr_trim.js"></script>
<script>
function Explorar(){
	msgwin=window.open("../dataentry/dirs_explorer.php?desde=dbcp&Opcion=explorar&base=<?php echo $db_path?>&mx=s&tag=document.forma1.dbfolder","explorar","width=400,height=600,top=0,left=0,resizable,scrollbars,menu")
	msgwin.focus()
}
function Confirmar(){
	document.continuar.submit()
}
function FullInv(){
	document.continuar.action="fullinv.php";
	document.continuar.submit()
}
</script>


<div class="sectionInfo">
	<div class="breadcrumb">
	<?php echo $msgstr["db_delcp"]; if (isset($arrHttp["base"])) echo ": ".$arrHttp["base"];?>
	</div>
	<div class="actions">
	<?php include "../common/inc_back.php";?>
	<?php include "../common/inc_home.php";?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php include "../common/inc_div-helper.php";?>
<div class="middle form">
<div class="formContent">
<?php
if (!isset($mx_path)){
	echo $msgstr["mis_mx_path"];
	die;
}

if (!isset($arrHttp["storein"])){
	Explorar();
}else{
	echo "<form name=continuar method=post >";

	foreach ($_REQUEST as $var=>$value){
		if (trim($var)!="" and !str_starts_with($value,"db_sel_"))
			echo "<input type=hidden name=$var value=\"$value\">\n";
	}

	if (isset($arrHttp["storein"]) and !isset($arrHttp["select_copies"])){
		echo "<h4>".$msgstr["deldbcp_select"]."</h4>";
		ShowDatabases($arrHttp["storein"],$db_path);
		echo "</form></div></div>";
		include("../common/footer.php");
		die;
	}
}


$err="";
$num_selected_dbs=0;
foreach ($arrHttp as $var=>$value) {
	if ( str_starts_with(trim($var),"db_sel_")){
		$num_selected_dbs++;
		$mstfile=$db_path.$def_cprest_folder."/".$value.".mst";
		$xrffile=$db_path.$def_cprest_folder."/".$value.".xrf";
		if (file_exists($mstfile)) {
			$fpmst=file($mstfile);
			if (unlink($mstfile)){
				echo "$mstfile => <strong>".$msgstr["eliminados"]."</strong><br>";
			}else {
				echo "$mstfile => <strong>".$msgstr["notdeleted"]."</strong><br>";
			}
		}
		if (file_exists($xrffile)) {
			$fpmst=file($xrffile);
			if (unlink($xrffile)){
				echo "$xrffile => <strong>".$msgstr["eliminados"]."</strong><br>";
			}else {
				echo "$xrffile => <strong>".$msgstr["notdeleted"]."</strong><br>";
			}
		}
		echo "<br>";
	}
}
if ( $num_selected_dbs==0 ) echo "<h4>".$msgstr["deldbcp_nosel"]."</h4>";
echo "</form></div></div>";
include("../common/footer.php");
?>
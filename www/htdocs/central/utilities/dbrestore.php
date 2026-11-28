<?php
/*
20211104 fho4abcd Replace vmx_fullinv.php by fullinv.php. Remove obsolete comment
20211215 fho4abcd Backbutton & helper by included file
20261123 fho4abcd Removed preloaded+prevent copy of target to target+improve html and user messages
20261124 fho4abcd Improved button Inverted file generation
20261127 fho4abcd Removed unused script, add Bytes in tablecells with filesize, improve button text
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
function Confirmar(){
global $msgstr,$arrHttp;
	?>
	<input type=hidden name=confirmar>
	<h4><?php echo $msgstr["db_restore"]?></h4>
	<h4><?php echo $arrHttp["copyname"]." => ".$arrHttp["base"];?></h4>
	<input class='bt-green' type=button name=continuar value="<?php echo $msgstr["continuar"];?>" onclick=Confirmar()>
	&nbsp; &nbsp;
	<input class='bt-red' type=button name=cancelar value="<?php echo $msgstr["cancelar"];?>" onclick=Regresar()>
	<?php
}

Function Explorar(){
global $msgstr, $arrHttp, $def_cprest_folder, $def_cprest_folder_sel, $db_path;
?>
	<h3><?php echo $msgstr["resdb_desc"]." : ".$arrHttp["base"].".mst + ".$arrHttp["base"].".xrf";?></h3>
	<form name="upload" method="post" >
	<input type=hidden name=base value=<?php echo $arrHttp["base"]?>>
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
	echo "<table bgcolor=#cccccc border=0 cellpadding=8>\n";
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
						echo "<tr><td ><input type=radio name=db_selnone disabled value='".$fmst."'> $f</td>";
					}else{
						echo "<tr bgcolor=white><td><input type=radio name=db_sel value='".$fmst."'> $f</td>";
					}
					echo "<td>".date($dateFormat, $ctime) . "</td>";
					echo "<td>".number_format(filesize($file))." ".$msgstr["cpdb_bytes"]."</td></tr>\n";
				}
 	   		}
		}
	}
	echo "</table>";
	echo "<input type=hidden name=db_sel>\n";
	echo "<input type=hidden name=copyname>\n";
	if ($ix==0){
		echo "<h4>".$msgstr["mx_nodb"]."</h4>";
		die;
	}
	closedir($handle);
    ?>
	<br><input class='bt-green' type=submit value="<?php echo $msgstr["resdb_res"];?>">
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
	document.continuar.confirmar.value="OK";
	document.continuar.submit()
}

function EnviarForma(){
	selected_db=""
	for (i=0;i<document.continuar.db_sel.length-1;i++){
		if(document.continuar.db_sel[i].checked){
			document.continuar.copyname.value=document.continuar.db_sel[i].value
			selected_db="OK"
		}
	}
	if (selected_db=="OK")
		document.continuar.submit()
	else
		alert("<?php echo $msgstr["mx_select"]?>")
}

function Regresar(){
	document.continuar.action="../dbadmin/menu_mantenimiento.php";
	document.continuar.submit()
}

function FullInv(){
	document.continuar.action="fullinv.php";
	document.continuar.submit()
}
</script>


<div class="sectionInfo">
	<div class="breadcrumb">
	<?php echo $msgstr["db_restore"]; if (isset($arrHttp["base"])) echo ": ".$arrHttp["base"];?>
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

if (file_exists($db_path.$arrHttp["base"]."/protect_status.def")){
	$fp=file($db_path.$arrHttp["base"]."/protect_status.def");
	foreach ($fp as $value){
		$value=trim($value);
		if ($value=="PROTECTED"){
			echo "<h4>".$msgstr["protect_active"]."</h4>";
			die;
		}
	}
}

if (!isset($arrHttp["storein"])){
	Explorar();
}else{
	echo "<form name=continuar method=post onsubmit=\"EnviarForma();return false;\">";

	foreach ($_REQUEST as $var=>$value){
		if (trim($value)!="")
			echo "<input type=hidden name=$var value=\"$value\">\n";
	}
	if (isset($arrHttp["storein"]) and !isset($arrHttp["copyname"])){
		echo "<h4>".$msgstr["resdb_select"]." ".$arrHttp["base"]."</h4>";
		ShowDatabases($arrHttp["storein"],$db_path);
		echo "</form></div></div>";
		include("../common/footer.php");
		die;
	}
}

if (!isset($arrHttp["confirmar"]) or $arrHttp["confirmar"]!="OK"){
	Confirmar();
	echo "</form></div></div>";
	include("../common/footer.php");
	die;
}

$err="";
$from=$db_path.$arrHttp["storein"]."/".$arrHttp["copyname"];
$to=$db_path.$arrHttp["base"]."/data/".$arrHttp["base"];
echo $from.".mst => ".$to.".mst";
$res=copy($from.".mst",$to.".mst");
if ($res==true){
	echo " <strong>".$msgstr["copied"]."</strong><br>";
	echo $from.".xrf => ".$to.".xrf";
	$res=copy($from.".xrf",$to.".xrf");
	if ($res==true){
		echo " <strong>".$msgstr["copied"]."</strong>";
		echo "<br><br>";
		echo "<a class='bt bt-green mb-2' href=fullinv.php?base=".$arrHttp["base"]."&inframe=0&backtoscript=".$backtoscript.">"."<i class='fas fa-database'></i> ". $msgstr["mnt_gli"]."</a>";
	}else
		echo " <strong style=background-color:red;>"." => ".$msgstr["not_copied"]."</strong><br>";
}else{
	echo " <strong style=background-color:red;>"." => ".$msgstr["not_copied"]."</strong><br>";
}
echo "</form></div></div>";
include("../common/footer.php");
?>
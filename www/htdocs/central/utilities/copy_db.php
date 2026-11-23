<?php
/*
20211215 fho4abcd Backbutton & helper by included file
20261123 fho4abcd Improved user interface, defaults by included file. Correct html
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include("../config.php");
$lang=$_SESSION["lang"];

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");
include("../common/inc_check_referrer.php");

$db=$arrHttp["base"];
$backtoscript="../dbadmin/menu_mantenimiento.php"; // The default return script
//foreach ($arrHttp as $var=>$value)  echo "$var=$value<br>";
//foreach ($_SERVER as $var=>$value)  echo "$var=$value<br>";// for HTTP_REFERER

if (isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or  isset($_SESSION["permiso"]["CENTRAL_ALL"])
	or isset($_SESSION["permiso"][$db."_CENTRAL_DBUTILS"]) or isset($_SESSION["permiso"][$db."_CENTRAL_ALL"])){
}else{
	echo $msgstr["invalidright"];
	die;
}
include("../common/header.php");
?>
<body>
<?php
include("../common/institutional_info.php");
include("inc_def_cprest.php");
?>
<script language="javascript" src="../dataentry/js/lr_trim.js"></script>
<script>

function Explorar(){
	msgwin=window.open("../dataentry/dirs_explorer.php?desde=dbcp&Opcion=explorar&base=<?php echo $arrHttp["base"]?>&tag=document.forma1.dbfolder","explorar","width=400,height=600,top=0,left=0,resizable,scrollbars,menu")
   	console.log("OK");

	msgwin.focus()
}
function EnviarForma(){
	if (Trim(document.upload.storein.value)==""){
		alert("<?php echo $msgstr["falta"]." ".$msgstr["folder_name"]?>")
		return
	}
	if (Trim(document.upload.copyname.value)==""){
		alert("<?php echo $msgstr["falta"]." ".$msgstr["cp_name"]?>")
		return
	}
	dbsrc="<?php echo $arrHttp["base"]?>"
	dbn=Trim(document.upload.copyname.value)
	if (dbsrc==dbn) {
		alert("<?php echo $msgstr["cp_name"]." ".$msgstr["cpdb_diff"]." ".$arrHttp["base"];?>")
		return
	}

	var alphaExp = /^[a-zA-Z_0123456789-]+$/;
	if(dbn.match(alphaExp)){

	}else{
		alert("<?php echo $msgstr["invalidfilename"]?>");
		return
	}

	Limpiar()
   	document.upload.submit()
}

function Limpiar(){
	fld=Trim(document.upload.storein.value)
	if (fld.substr(0,1)=="/"){
		fld=fld.substring(1)
		document.upload.storein.value=fld
	}
}
</script>
<div class="sectionInfo">
	<div class="breadcrumb">
<?php echo $msgstr["db_cp"].": ".$arrHttp["base"]?>
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
		<h3><?php echo $msgstr["cpdb_desc"]." : ".$arrHttp["base"].".mst + ".$arrHttp["base"].".xrf";?></h3>

		<form name="upload" method="post" action="copy_db_ex.php" onsubmit="EnviarForma();return false;">
			<input type="hidden" name="base" value="<?php echo $arrHttp["base"]?>">
			<table>
			<tr><td><?php echo $msgstr["cpdb_type"];?></td>
				<td><input type=radio id=cpraw name=cpreorg value=cpraw checked>
					<label for=cpraw> <?php echo $msgstr["cpdb_raw"];?></label><br>
					<input type=radio id=cpreo name=cpreorg value=cpreo>
					<label for=cpreo> <?php echo $msgstr["cpdb_reorg"];?></label>
				</td>
			<tr><td></td>
			</tr>
			<tr>
				<td><label><?php echo $msgstr["cp_folder"];?></label></td>
				<td><input type="text" name="storein" size="30" onclick="javascript:blur()" value="<?php echo $def_cprest_folder;?>"></td>
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
			</tr>
			<tr>
				<td><label><?php echo $msgstr["cp_name"];?></label></td>
				<td><input type="text" name="copyname" value=<?php echo "$def_cprest_file";?>></td>
			</table>
			<input class="bt-green" type="submit" value="<?php echo $msgstr["procesar"]?>">
		</form>
	</div>
</div>

<?php include("../common/footer.php"); ?>
<?php
session_start();
if (!isset($_SESSION["login"])) die;
if (!isset($_SESSION["lang"]))  $_SESSION["lang"]="en";
include("../common/get_post.php");
$arrHttp["base"]="users";
include("../config.php");
$lang=$_SESSION["lang"];

include("../lang/admin.php");
include("../lang/prestamo.php");

//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";
include("../common/header.php");



?>
<script language="JavaScript" type="text/javascript" src=../dataentry/js/lr_trim.js></script>
<script>
function checkSubmit(e) {
   if(e && e.keyCode == 13) {
   		EnviarForma()
   }
}
function EnviarForma(){
	if (Trim(document.inventorysearch.inventory.value)=="" ){
		alert("<?php echo $msgstr["falta"]." ".$msgstr["inventory"]." / ".$msgstr["usercode"]?>")
		return
	}
    document.inventorysearch.submit()
}



</script>
<?php
$encabezado="";
echo "<body onLoad=javascript:document.inventorysearch.inventory.focus()>\n";
include("../common/institutional_info.php");
$link_u="";
if (isset($arrHttp["usuario"]) and $arrHttp["usuario"]!="") $link_u="&usuario=".$arrHttp["usuario"];
?>

<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["co_history"];
		?>
	</div>
	<div class="actions">

	</div>
	<?php include("submenu_prestamo.php");?>
</div>

<?php
$ayuda="item_history.html";
include "../common/inc_div-helper.php";
?> 	


<form name=inventorysearch action=item_history_ex.php method=post onsubmit="javascript:return false">
<input type=hidden name=Opcion value=prestar>
<div class="middle list">
	<div class="formContent">
	<div class="searchBox">
	<table width=100% border=0>
		<td width=150>
		<label for="searchExpr">
			<strong><?php echo $msgstr["inventory"]?></strong>
		</label>
		</td><td>
		<input type="text" name="inventory" id="inventory" value="" class="textEntry" onfocus="this.className = 'textEntry';"  onblur="this.className = 'textEntry';"  onKeyPress="return checkSubmit(event,1)" />
		<input type="submit" name="reservar" value="<?php echo $msgstr["search"]?>" class="bt-green" onclick="javascript:EnviarForma()"/>
		</td>
	</table>
	</div>
</div>
</div>

</form>
<?php include("../common/footer.php");

?>
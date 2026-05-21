<?php
/* Modifications
2021-06-10 fho4abcd Remove password argument
2022-05-01 rogercgui Included new cancel and save buttons
*/
session_start();
include("../common/get_post.php");
include("../config.php");
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
$lang = $_SESSION["lang"];


include("../lang/admin.php");
include("../lang/acquisitions.php");
include("../dataentry/leerregistroisis.php");
//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";

include("../common/header.php");
include("../acquisitions/javascript.php");

//Se lee el registro que va a ser modificado
$arrHttp["lock"] = "S";
$arrHttp["opcion"] = "leer";
$maxmfn = 0;

$res = LeerRegistro($arrHttp["base"], $arrHttp["base"] . ".par", $arrHttp["Mfn"], $maxmfn, "editar", $_SESSION["login"], "");
if ($res == "LOCKREJECTED") {
	echo "<script>
	alert('" . $arrHttp["Mfn"] . ": " . $msgstr["reclocked"] . "')
	</script>";
	die;
	//	break;
}
?>
<script language=javascript>
	top.toolbarEnabled = "N"

	function Validar() {


	}

	function EnviarForma() {
		ret = Validar()
		if (ret == "N") return
		document.forma1.submit()
	}


	function RefrescarPicklist(tabla, Ctrl, valor) {
		ValorOpcion = valor
		document.refrescarpicklist.picklist.value = tabla
		document.refrescarpicklist.Ctrl.value = Ctrl
		document.refrescarpicklist.valor.value = valor
		msgwin = window.open("", "Picklist", "width=20,height=10,scrollbars, resizable")
		document.refrescarpicklist.submit()
		msgwin.focus()
	}

	function AgregarPicklist(tabla, Ctrl, valor) {
		ValorOpcion = valor
		document.agregarpicklist.picklist.value = tabla
		document.agregarpicklist.Ctrl.value = Ctrl
		document.agregarpicklist.valor.value = valor
		msgwin = window.open("", "Picklist", "width=600,height=500,scrollbars, resizable")
		document.agregarpicklist.submit()
		msgwin.focus()
	}

	//SE ACTUALIZA EL SELECT CON LA TABLA ACTUALIADA
	ValorTabla = ""
	SelectName = ""
	ValorOpcion = ""

	function AsignarTabla() {
		opciones = ValorTabla.split('$$$$')
		var Sel = document.getElementById(SelectName);
		Sel.options.length = 0;
		var newOpt = Sel.appendChild(document.createElement('option'));
		newOpt.text = "";
		newOpt.value = " ";
		for (x in opciones) {
			op = opciones[x].split('|')
			if (op[0] == "")
				op[0] = op[1]
			if (op[1] == "")
				op[1] = op[0]
			var newOpt = Sel.appendChild(document.createElement('option'));
			newOpt.text = op[1];
			newOpt.value = op[0];
			if (op[0] == ValorOpcion)
				newOpt.selected = true
		}
	}
</script>

<body>
	<?php
	if (isset($arrHttp["encabezado"]) and $arrHttp["encabezado"] == "s") {
		include("../common/institutional_info.php");
	}
	?>
	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php echo $msgstr["m_editcopy"]; ?>
		</div>
		<div class="actions">
			<a href="<?php echo $arrHttp["retorno"]; ?>" class="button_browse" title='<?php echo $msgstr["cancel"] ?>'>
				<i class="far fa-window-close bt-red"></i>&nbsp;<?php echo $msgstr["cancel"] ?>
			</a>

			<?php
			$savescript = "javascript:EnviarForma()";
			include "../common/inc_save.php";
			?>
		</div>
		<div class="spacer">&#160;</div>
	</div>


	<?php
	$ayuda = "acquisitions/copies_add.html";
	include "../common/inc_div-helper.php";
	?>

	<div class="formContent" id="formContent">

		<form method=post name=forma1 action=copies_update.php onSubmit="javascript:return false">
			<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
			<input type=hidden name=cipar value=<?php echo $arrHttp["base"] . ".par" ?>>
			<input type=hidden name=ValorCapturado value="">
			<input type=hidden name=check_select value="">
			<input type=hidden name=Indice value="">
			<input type=hidden name=Mfn value="<?php echo $arrHttp["Mfn"] ?>">
			<input type=hidden name=valor value="">
			<input type=hidden name=retorno value=<?php echo $arrHttp["retorno"] ?>>

			<div class="form">
				<?php
				$fmt_test = "S";
				$arrHttp["wks"] = "new.fmt";
				$wks_avail["new"] = 1;
				include("../dataentry/plantilladeingreso.php");
				ConstruyeWorksheetFmt();
				include("../dataentry/dibujarhojaentrada.php");
				PrepararFormato();

				?>
		</form>
	</div>
	<?php include("../common/footer.php"); ?>
</body>

</html>

<form name=agregarpicklist action=../dbadmin/picklist_edit.php method=post target=Picklist>
	<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
	<input type=hidden name=picklist>
	<input type=hidden name=Ctrl>
	<input type=hidden name=valor>
	<input type=hidden name=desde value=dataentry>
</form>

<form name=refrescarpicklist action=../dbadmin/picklist_refresh.php method=post target=Picklist>
	<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
	<input type=hidden name=picklist>
	<input type=hidden name=Ctrl>
	<input type=hidden name=valor>
	<input type=hidden name=desde value=dataentry>
</form>

<?php
// ==================================================================================

function LeerFdt($base)
{
	global $tag_ctl, $pref_ctl, $arrHttp, $db_path;
	// se lee la FDT para conseguir la etiqueta del campo donde se coloca la numeración automática y el prefijo con el cual se indiza el número de control

	$archivo = $db_path . $base . "/def/" . $_SESSION["lang"] . "/" . $base . ".fdt";
	if (file_exists($archivo)) {
		$fp = file($archivo);
	} else {
		$archivo = $db_path . $base . "/def/" . $lang_db . "/" . $base . ".fdt";
		if (file_exists($archivo)) {
			$fp = file($archivo);
		} else {
			echo "missing file " . $base . "/" . $base . ".fdt";
			die;
		}
	}
	$tag_ctl = "";
	$pref_ctl = "";
	foreach ($fp as $linea) {
		$l = explode('|', $linea);
		if ($l[0] == "AI" || $l[7] == "AI") {
			$tag_ctl = $l[1];
			$pref_ctl = $l[12];
		}
	}
	// Si no se ha definido el tag para el número de control en la fdt, se produce un error
	if ($tag_ctl == "" or $pref_ctl == "") {
		echo "<h2>" . $msgstr["missingctl"] . "</h2>";
		die;
	}
}

?>
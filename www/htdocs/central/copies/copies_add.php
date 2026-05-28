<?php
/*
20210908 fho4abcd div-helper, cleanup html
20220209 fho4abcd Improve helper+Backbutton+undefine variable
20220424 rogercgui add backbutton and save button
*/
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"];


include("../lang/admin.php");
include("../lang/acquisitions.php");
//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";

$db_addto = $arrHttp["base"];
$arrHttp["base"] = "copies";
include("../common/header.php");

echo "<body>";
include("../acquisitions/javascript.php");
?>

<script language=javascript>
	top.toolbarEnabled = "N"

	function Validar() {
		if (Trim(document.forma1.copies.value) == "" && Trim(document.forma1.tag10.value == "")) {
			alert("<?php echo $msgstr["err_copies"] ?>")
			return "N"
		}
		if (Trim(document.forma1.tag1.value) == "") {
			alert("<?php echo $msgstr["err_objectctl"] ?>")
			return "N"
		}
		mydiv = document.getElementById("INE");
		inv = document.forma1.tag30;
		if (inv.value == "") {
			mydiv.innerHTML = '</br>&nbsp; &nbsp;<?php echo $msgstr["errINE"]; ?></br></br>';
			mydiv.style.display = 'block';
			inv.focus();
			return "N";
		}
		invdup = 0;
		invnumbers = document.getElementById("INVA").value;
		if (invnumbers != "~") {
			mydiv.innerHTML = '</br>&nbsp; &nbsp;<?php echo $msgstr["errEXCopyS"]; ?></br></br></br>';
			mydiv.style.display = 'block';
			inv.focus();
			return "N";
		}
		check = -1;
		for (i = 0; i < document.forma1.tag200.length; i++) {
			if (document.forma1.tag200[i].checked) check = i;
		}
		if (check == -1) {
			mydiv.innerHTML = '</br>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $msgstr["errSE"]; ?></br></br>';
			mydiv.style.display = 'block';
			return "N";
		}
		document.getElementById('middleForm').style.display = 'none'
		document.getElementById('my_id').style.display = 'block'
		mydiv.style.display = 'none';
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
<?php
//READ THE FST OF THE BIBLIOGRAPHIC DATABASE IN ORDER TO GET THE TAG OF THE CONTROL FIELD
$tag_ctl = "";
$error = "";
$err_copies = "";
LeerFst($db_addto);
if ($tag_ctl != "") {
	$Formato = "v" . $tag_ctl;

	//READ THE BIBLIOGRAPHIC RECORD TO GET THE CONTROL NUMBER

	$query = "&base=" . $db_addto . "&cipar=$db_path" . "par/" . $db_addto . ".par" . "&from=" . $arrHttp["Mfn"] . "&to=" . $arrHttp["Mfn"] . "&Formato=$Formato&Opcion=rango";
	$IsisScript = $xWxis . "imprime.xis";
	include("../common/wxis_llamar.php");
	$valortag[1] = implode("", $contenido);
	//print 'valortag1='.$valortag[1].'<p>';
	if ($valortag[1] == "")     //CHECK IF THE RECORD HAS CONTROL NUMBER
		$err_copies = "Y";
	else
		$err_copies = "N";

	//READ THE FDT OF THE COPIES DATABASE TO SEE IF THE INVENTORY NUMBER IS AUTOINCREMENT
	LeerFdt("copies");
}
if (isset($arrHttp["encabezado"]) and $arrHttp["encabezado"] == "s") {
	include("../common/institutional_info.php");
}
$urlcopies = "";
if (isset($arrHttp["db_copies"])) $urlcopies = "&db_copies=Y";
?>
<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["m_addcopies"] ?>
	</div>
	<div class="actions">
		<?php
		if ($err_copies != "Y" and $error == "") {
			if (isset($arrHttp["encabezado"])) {
				include "../common/inc_cancel.php";
			}


			$backtocancelscript = "../dataentry/fmt.php?base=" . $db_addto . "&cipar=$db_addto.par&Opcion=ver&ver=S&Mfn=" . $arrHttp["Mfn"];
			if (isset($arrHttp["Formato"])) $backtocancelscript .= "&Formato=" . $arrHttp["Formato"];
			$backtocancelscript .= $urlcopies;
			include "../common/inc_cancel.php" ?>


			<?php
			$savescript = "javascript:EnviarForma()";
			include "../common/inc_save.php" ?>

		<?php
		} else {
			unset($arrHttp["base"]);
			$backtoscript = 'javascript:top.toolbarEnabled="";top.Menu("same")';
			include "../common/inc_back.php";
		}
		?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php
$ayuda = "copies/copies_add.html";
include "../common/inc_div-helper.php";

?>
<form method=post name=forma1 action=copies_add_add.php onSubmit="javascript:return false">
	<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
	<input type=hidden name=cipar value=<?php echo $arrHttp["base"] . ".par" ?>>
	<input type=hidden name=ValorCapturado value="">
	<input type=hidden name=check_select value="">
	<input type=hidden name=Indice value="">
	<input type=hidden name=Mfn value="<?php echo $arrHttp["Mfn"] ?>">
	<input type=hidden name=valor value="">

	<?php include("../common/inc_wait.php"); ?>

	<div class="middle form" id="middleForm">

		<div name="INE" id="INE" style="color:#990000; display:none; font-style:italic; font-weight:bold;"></div>

		<input type=hidden name=INVA id=INVA value="<?php echo $arrHttp["Mfn"] ?>~">

		<?php
		if ($error != "") {
			echo "<script>top.toolbarEnabled=\"\"</script>\n";
			echo "<dd><h4>" . $msgstr[$error] . "</h4>";
			die;
		}

		if ($err_copies == "Y") {
			echo "<script>top.toolbarEnabled=\"\"</script>\n";
			echo "<dd><h4>" . $msgstr["err_cannotaddcopies"] . "</h4>";
			die;
		}

		//GET LAST CONTROL NUMBER
		$archivo = $db_path . $arrHttp["base"] . "/data/control_number.cn";
		$last_cn = "0";
		if (!file_exists($archivo)) {
			$fp = fopen($archivo, "w");
			fwrite($fp, "0");
			fclose($fp);
		} else {
			$fp = file($archivo);
			$last_cn = trim(implode("", $fp));
			if ($last_cn === "") $last_cn = "0";
		}

		// Calculates the next number and adds leading zeros (if configured)
		$next_cn = intval($last_cn) + 1;
		$next_cn_padded = $next_cn;
		if (isset($max_inventory_length) && $max_inventory_length > 0) {
			$next_cn_padded = str_pad($next_cn, $max_inventory_length, '0', STR_PAD_LEFT);
		}

		?>
		<div class="searchBox">
			<label for="addCopies">
				<strong><?php echo $msgstr["numcopies"]; ?></strong>
			</label>
			<input type="text" size="11" maxlength="2" name="copies" id="copies" value="">
			&nbsp; &nbsp; &nbsp;

			<a class="bt-sm bt-default" href="javascript:Show('copies','CN_<?php echo $db_addto; ?>_<?php echo $valortag[1]; ?>')">
				<?php echo $msgstr["dispcopies"]; ?>
			</a>

			<!--	<input type=checkbox checked value=Y  name=createloans>
		<label for="regCopies">
			<strong><?php echo $msgstr["regcopies"] ?></strong>
		</label>-->
		</div>

		<div class="formContent" id="formContent">
			<?php



			$arrHttp["cipar"] = "copies.par";
			$fmt_test = "S";
			$arrHttp["wks"] = "new.fmt";
			$wks_avail["new"] = 1;

			$valortag[10] = $db_addto;
			$arrHttp["db_addto"] = $db_addto;

			// Modern widget with an autofill button
			echo "<div style='display:flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; padding: 15px; border: 1px solid #e0e4e8; border-radius: 4px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>";
			echo "   <div style='font-size: 1.1rem; color: #444;'>";
			echo "      <i class='fas fa-info-circle' style='color:#0056b3;'></i> " . $msgstr["cn_last_number"] . ": <strong style='color:#dc3545; font-size:1.3rem; margin-left:5px;'>" . $last_cn . "</strong>";
			echo "   </div>";
			echo "   <div>";
			echo "      <a href='javascript:void(0)' class='bt bt-green' onclick='document.getElementById(\"tag30\").value=\"" . $next_cn_padded . "\"; if(typeof CheckInventory === \"function\") { CheckInventory(); }' title='Preencher com o próximo inventário'>";
			echo "         <i class='fas fa-magic'></i> " . $msgstr["autofill"] . " (" . $next_cn_padded . ")";
			echo "      </a>";
			echo "   </div>";
			echo "</div>";

			include("../dataentry/plantilladeingreso.php");

			ConstruyeWorksheetFmt();
			include("../dataentry/dibujarhojaentrada.php");
			PrepararFormato();

			?></div>
</form>
</div>
</div>

<form name="agregarpicklist" action="../dbadmin/picklist_edit.php" method="post" target="Picklist">
	<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
	<input type="hidden" name="picklist">
	<input type="hidden" name="Ctrl">
	<input type="hidden" name="valor">
	<input type="hidden" name="desde" value="dataentry">
</form>

<form name="refrescarpicklist" action="../dbadmin/picklist_refresh.php" method="post" target="Picklist">
	<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
	<input type="hidden" name="picklist">
	<input type="hidden" name="Ctrl">
	<input type="hidden" name="valor">
	<input type="hidden" name="desde" value="dataentry">
</form>

<?php

function LeerFst($base)
{
	global $tag_ctl, $pref_ctl, $arrHttp, $db_path, $AI, $lang_db, $msgstr, $error;
	// GET THE FST TO FIND THE CONTROL NUMBER OF A BIBLIOGRAPHIC DATABASE
	$archivo = $db_path . $base . "/data/" . $base . ".fst";
	if (!file_exists($archivo)) {
		echo "missing file " . $base . "/data/" . $base . ".fst";
		die;
	}
	$fp = file($archivo);
	$tag_ctl = "";
	$pref_ctl = "CN_";
	foreach ($fp as $linea) {
		$linea = trim($linea);
		$ix = strpos($linea, "\"CN_\"");
		if ($ix === false) {
			$ix = strpos($linea, '|CN_|');
		}
		if ($ix === false) {
		} else {
			$ix = strpos($linea, " ");
			$tag_ctl = trim(substr($linea, 0, $ix));
			break;
		}
	}
	// Si no se ha definido el tag para el número de control en la fdt, se produce un error
	if ($tag_ctl == "") {
		$error = "missingctl";
	}
}


function LeerFdt($base)
{
	global $tag_ctl, $pref_ctl, $arrHttp, $db_path, $AI, $lang_db, $msgstr;
	// GET THE FDT TO FIND THE CONTROL NUMBER OF A BIBLIOGRAPHIC DATABASE
	// GET THE INVENTORY NUMBER OF THE COPIES DATABASE TO SEE IF IS AUTOINCREMENT
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
	$AI = "";
	foreach ($fp as $linea) {       //SEE IF THE INVENTORY NUMBER OF THE COPIES DATABASE IS AUTOINCREMENT
		$l = explode('|', $linea);
		if ($l[1] == "30") {
			if ($l[0] == "AI" || $l[7] == "AI") $AI = "Y";
		}
	}
}
?>

<script type="text/javascript">
	const textarea = document.querySelector('textarea')
	textarea.onkeypress = (event) => {
		const keyCode = event.keyCode
		if (keyCode === 13) {
			document.getElementById("copies").disabled = true;
			console.log('Field number of copies has been blocked!');
		}
	}

	document.forma1.tag200[2].disabled = true;

	function CheckInventory(tag) {
		// Function in the acquisitions/javascript.php file
		CheckInventoryDup(document.getElementById("tag30").value, 1);
	}
</script>
<?php include("../common/footer.php"); ?>
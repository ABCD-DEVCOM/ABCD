<?php
/* Modifications
2021-03-03 fho4abcd Replaced helper code fragment by included file
2021-06-14 fho4abcd remove password+lineends
2021-08-30 rogercg	Small adjustments+ point to community
2021-09-06 rogercg	New look - p1
2022-01-20 fho4abcd new look buttons+Remove some nested tables+cleanup html+repair bugs
2022-01-25 fho4abcd more new look buttons, shift Generate output to the bottom, improve generate output layout
2022-01-26 fho4abcd Open preview in larger window and after all checks passed.
2022-01-29 fho4abcd Improve setting of encabezado+create language folder if it does not exist
2022-0227 fho4abcd Always show backbutton. Other back if institutional info not shown
2022-09-18 fho4abcd Explode base before config.php (to get correct value for $actparfolder)
2022-11-02 fho4abcd Cancel button acts now the same as the back button
2024-01-02 fho4abcd Cleanup,better messages,correct handle output type, improved Edit, remove Expresion parameter, add Print option
2024-01-04 fho4abcd Correct creation of wrong folder. No longer browse pft files. Small corrections
2024-01-16 fho4abcd Improved content formats (smaller/functonality). Extra options for template generation. No trim for undefined FDT values(bug).
2026-05-20 rogercgui Modernization of layout (Tables to Divs) and Statistics Aesthetic (Accordion)
*/

session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}

include("../common/get_post.php");

$basearr = explode('|', $arrHttp["base"]);
$base = $basearr[0];
$arrHttp["base"] = $base;
$cipar = $base . ".par";
$Opcion = "";
if (isset($arrHttp["Opcion"])) $Opcion = $arrHttp["Opcion"];
$description = "";
if (isset($arrHttp["descripcion"])) $description = $arrHttp["descripcion"];
$pft_name = "";
$tipoacro = "";
$separacro = "VBAR";
$prefixsubfieldacro = 0;
$fieldquoteacro = 0;

if (isset($arrHttp["fgen"]) && $arrHttp["fgen"] != "") {
	$fgenar = explode('|', $arrHttp["fgen"]);
	if ($fgenar[0] != "") 							$pft_name = Trim($fgenar[0]);
	if (isset($fgenar[1]) && Trim($fgenar[1] != ""))	$description = Trim($fgenar[1]);
	if (isset($fgenar[2]) && Trim($fgenar[2] != ""))	$tipoacro = Trim($fgenar[2]);
	if (isset($fgenar[3]) && Trim($fgenar[3] != ""))	$separacro = Trim($fgenar[3]);
	if (isset($fgenar[4]) && Trim($fgenar[4] != ""))	$prefixsubfieldacro = Trim($fgenar[4]);
	if (isset($fgenar[5]) && Trim($fgenar[5] != ""))	$fieldquoteacro = Trim($fgenar[5]);
}
if ($tipoacro == "") $tipoacro = "T";
$pft_content = "";
$pft_h_content = "";

include("../config.php");
if (isset($_SESSION["UNICODE"])) {
	if ($_SESSION["UNICODE"] == 1)
		$meta_encoding = "UTF-8";
	else
		$meta_encoding = "ISO-8859-1";
}
include("../lang/dbadmin.php");
include("../lang/admin.php");
include("inc_pft_files.php");

$Expresion = "";
if (isset($arrHttp["encabezado"])) $encabezado = "s";
else $encabezado = "";

include("../common/header.php");
?>

<body>
	<script language="JavaScript" type="text/javascript" src="../dataentry/js/lr_trim.js"></script>
	<script language="Javascript" src="../dataentry/js/selectbox.js"></script>

	<style type="text/css">
		/* Utility flex classes for internal content layout */
		.flex-row {
			display: flex;
			gap: 15px;
			align-items: center;
			flex-wrap: wrap;
		}

		.flex-col {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}

		.form-group {
			margin-bottom: 10px;
		}
	</style>

	<script language="javascript">
		function Buscar() {
			base = '<?php echo $base ?>'
			cipar = '<?php echo $cipar ?>'
			Url = "../dataentry/buscar.php?Opcion=formab&prologo=prologoact&Target=s&Tabla=imprimir&base=" + base + "&cipar=" + cipar
			msgwin = window.open(Url, "Buscar", "menu=no, resizable,scrollbars,width=1000,height=500")
			msgwin.focus()
		}

		function CheckType() {
			var trimtext = document.forma1.headings.value.trim();
			document.forma1.headings.value = trimtext
			if ((document.forma1.tipof[0].checked || document.forma1.tipof[1].checked || document.forma1.tipof[2].checked) && (trimtext)) {
				alert("<?php echo $msgstr["r_noheading"] ?>")
				document.forma1.headings.focus();
				return false;
			}
			return true;
		}

		function ClearFormat() {
			document.forma1.headings.value = ""
			document.forma1.pft.value = ""
			moveAllOptions(document.forma1.list21, document.forma1.list11, false)
			for (i = 0; i < document.forma1.tipof.length; i++) document.forma1.tipof[i].checked = false;
			for (i = 0; i < document.forma1.separ.length; i++) document.forma1.separ[i].checked = false;
			document.forma1.separ[0].checked = true;
			document.forma1.prefixsubfield.checked = false;
			document.forma1.fieldquote.checked = false;
		}

		function ClearRange() {
			document.forma1.Mfn.value = ''
			document.forma1.to.value = ''
		}

		function CopiarExpresion() {
			Expr = document.forma1.Expr.options[document.forma1.Expr.selectedIndex].value
			document.forma1.Expresion.value = Expr
		}

		function CopySortKey() {
			Sort = document.forma1.sort.options[document.forma1.sort.selectedIndex].value
			document.forma1.sortkey.value = Sort
		}

		function CreateSortKey() {
			msgwin = window.open("", "sortkey", "resizable,scrollbars, width=700,height=600")
			document.sortkey.submit()
			msgwin.focus()
		}

		function DeleteExpression() {
			document.forma1.Expresion.value = ''
		}

		function DeleteFormat() {
			if (document.forma1.fgen.selectedIndex == 0 || document.forma1.fgen.selectedIndex == -1) {
				alert("<?php echo $msgstr["selpftdel"] ?>")
				return
			}
			ix = document.forma1.fgen.selectedIndex
			if (confirm("delete " + document.forma1.fgen.options[ix].text + "?")) {
				file = document.forma1.fgen.options[ix].value + '|'
				f = file.split('|')
				document.frmdelete.pft.value = f[0]
				document.frmdelete.submit()
			}
		}

		function DeleteSelected() {
			document.forma1.seleccionados.value = ''
		}

		function DeleteSrchExpr() {
			if (document.forma1.Expr.selectedIndex == 0 || document.forma1.Expr.selectedIndex == -1) {
				alert("<?php echo $msgstr["pftselsrchexprdel"] ?>")
				return
			}
			ix = document.forma1.Expr.selectedIndex
			srchdesc = document.forma1.Expr.options[ix].text;
			if (confirm("<?php echo $msgstr["pftdelsrchexpr"] ?>" + ": " + srchdesc + "?")) {
				document.forma1.Expresion.value = ''
				var winl = (screen.width - 300) / 2;
				var wint = (screen.height - 200) / 2;
				var select = document.getElementById('Expr')
				select.removeChild(select.options[ix])
				msgwin = window.open("", "removesearch", "menu=no,status=yes,width=300, height=200,left=" + winl + ",top=" + wint)
				msgwin.focus()
				document.removesearch.Descripcion.value = srchdesc;
				document.removesearch.submit()
			}
		}

		function GenerarFormato() {
			if (document.forma1.list21.options.length == 0) {
				alert("<?php echo $msgstr["selfieldsfmt"] ?>")
				return
			}
			<?php if ($Opcion != "new") echo "document.forma1.fgen.selectedIndex=-1;\n"; ?>
			formato = ""
			head = ""
			separstr = ""
			for (i = 0; i < document.forma1.separ.length; i++) {
				if (document.forma1.separ[i].checked) separstr = document.forma1.separ[i].value;
			}
			Tipo = ""
			for (i = 0; i < document.forma1.tipof.length; i++) {
				if (document.forma1.tipof[i].checked) Tipo = document.forma1.tipof[i].value;
			}
			prefixsubfield = document.forma1.prefixsubfield.checked;
			switch (Tipo) {
				case "T":
					bodystyle = "style=" + '"' + 'font-family:arial;font-size:smaller;vertical-align:top" '
					formato = "'<table border=0>'\n"
					formato += "'<tbody " + bodystyle + ">'\n"
					for (i = 0; i < document.forma1.list21.options.length; i++) {
						campo = document.forma1.list21.options[i].value;
						t = campo.split('|');
						xTag = t[1]
						xTipoE = t[0]
						if (xTag == "" || xTipoE == "L") {
							campo = "'<tr><th colspan=2>" + t[2] + "</th>'/\n"
						} else {
							if (typeof t[5] !== 'undefined' && Trim(t[5]) != "") {
								tag = SubFields(xTag, t[5], "<br>", prefixsubfield);
							} else {
								tag = "v" + xTag + "+|<br>|"
							}
							campo = "if p(v" + xTag + ") then '<tr><th width=20%>" + t[2] + "</th><td>'" + tag + ",'</td>' fi/\n"
						}
						formato += campo;
					}
					formato += "'</table>'";
					break
				case "PL":
				case "P":
					font = "'<font face=arial size=2>"
					for (i = 0; i < document.forma1.list21.options.length; i++) {
						campo = document.forma1.list21.options[i].value
						t = campo.split('|')
						xTag = t[1]
						xTipoE = t[0]
						if (xTag == "" || xTipoE == "L") {
							campo = "'<b>" + t[2] + "</b><br>'/\n"
						} else {
							if (Tipo == 'PL') label_f = "<b>" + t[2] + "</b>: ' "
							else label_f = "'"

							if (Trim(t[5]) != "") {
								tag = SubFields(xTag, t[5], ";", prefixsubfield)
							} else {
								tag = "v" + xTag + "+|; |"
							}
							campo = "if p(v" + xTag + ") then " + font + label_f + tag + "'</font><br>'" + ", fi/\n"
						}
						formato += campo
					}
					formato += "/\n"
					break
				case "CT":
					rowstyle = "style=" + '"' + 'font-family:arial;font-size:smaller;vertical-align:top" ';
					formato += "'<tr " + rowstyle + ">',"
					for (i = 0; i < document.forma1.list21.options.length; i++) {
						campo = document.forma1.list21.options[i].value
						t = campo.split('|')
						xTag = t[1]
						xTipoE = t[0]
						if (xTag != "") {
							res = ""
							if (Trim(t[5]) != "") {
								tag = SubFields(xTag, t[5], "<br>", prefixsubfield)
							} else {
								tag = "v" + xTag + "+|; |"
							}
							campo = "'<td>'" + tag + " if a(v" + xTag + ") then '&nbsp;' fi,'</td>'/\n"
							formato += campo
							head += t[2] + "\n"
						}
					}
					break;
				case "CD":
					quote = "";
					if (document.forma1.fieldquote.checked == true) quote = " '" + '"' + "' ";
					for (i = 0; i < document.forma1.list21.options.length; i++) {
						campo = document.forma1.list21.options[i].value
						t = campo.split('|')
						xTag = t[1]
						xTipoE = t[0]
						if (xTag != "") {
							if (Trim(t[5]) != "") {
								tag = SubFields(xTag, t[5], ";", prefixsubfield)
							} else {
								tag = "v" + xTag + "+|; |";
							}
							campo = quote + tag + "," + quote + "'" + separstr + "'\n"
							formato += campo
							head += t[2] + "\n"
						}
					}
					formato += "/"
					break;
				default:
					alert("<?php echo $msgstr["pftseltemplate"] ?>");
			}
			document.forma1.pft.value = formato
			document.forma1.headings.value = head
		}

		function SubFields(Tag, subfields, repsepar, prefix) {
			var subsepar = "";
			xtag = "(";
			for (ic = 0; ic < subfields.length; ic++) {
				subfield_id = subfields.substr(ic, 1)
				if (prefix == true) xtag += "|" + '^' + subfield_id + "|"
				else xtag += "| |"
				xtag += "v" + Tag + '^' + subfield_id + '|' + subsepar + '|,'
			}
			xtag += " if iocc<>nocc(v" + Tag + ") and p(v" + Tag + ") then '" + repsepar + "' fi"
			xtag += ")";
			return xtag
		}

		function SaveFormat() {
			document.forma1.fgen.selectedindex = -1
			if (Trim(document.forma1.pft.value) == "") {
				alert("<?php echo $msgstr["pftselgen"] ?>")
				return
			}
			if (Trim(document.forma1.nombre.value) == "") {
				alert("<?php echo $msgstr["r_fnomb"] ?>")
				return
			}
			if (Trim(document.forma1.descripcion.value) == "") {
				alert("<?php echo $msgstr["pftnodescri"] ?>")
				return
			}
			fn = document.forma1.nombre.value
			bool = /^[a-z][\w]+$/i.test(fn)
			if (!bool) {
				alert("<?php echo $msgstr["errfilename"] ?>");
				return
			}
			if (CheckType() == false) return;
			tipoacro = ""
			for (i = 0; i < document.forma1.tipof.length; i++) {
				if (document.forma1.tipof[i].checked) tipoacro = document.forma1.tipof[i].value
			}
			separacro = ""
			for (i = 0; i < document.forma1.separ.length; i++) {
				if (document.forma1.separ[i].checked) separacro = document.forma1.separ[i].id
			}
			prefixsubfieldacro = "0";
			fieldquoteacro = "0";
			if (document.forma1.prefixsubfield.checked == true) prefixsubfieldacro = "1";
			if (document.forma1.fieldquote.checked == true) fieldquoteacro = "1";

			document.guardar.pft.value = document.forma1.pft.value
			document.guardar.headings.value = document.forma1.headings.value
			document.guardar.tipoacro.value = tipoacro
			document.guardar.separacro.value = separacro
			document.guardar.prefixsubfieldacro.value = prefixsubfieldacro
			document.guardar.fieldquoteacro.value = fieldquoteacro
			document.guardar.nombre.value = document.forma1.nombre.value
			document.guardar.descripcion.value = document.forma1.descripcion.value
			document.guardar.base.value = document.forma1.base.value
			document.guardar.submit()
		}

		function SaveSearch() {
			document.savesearch.Expresion.value = Trim(document.forma1.Expresion.value)
			if (document.savesearch.Expresion.value == "") {
				alert("<?php echo $msgstr["faltaexpr"] ?>")
				return
			}
			Descripcion = document.forma1.Descripcion.value
			if (Trim(Descripcion) == "") {
				alert("<?php echo $msgstr["errsave"] ?>")
				return
			}
			document.savesearch.Descripcion.value = Descripcion
			var winl = (screen.width - 300) / 2;
			var wint = (screen.height - 200) / 2;
			msgwin = window.open("", "savesearch", "menu=no,status=yes,width=300, height=200,left=" + winl + ",top=" + wint)
			msgwin.focus()
			document.savesearch.submit()
		}

		function SelectExistingFormat() {
			document.forma1.headings.value = ""
			document.forma1.pft.value = ""
			document.forma1.nombre.value = ""
			document.forma1.descripcion.value = ""
			document.forma1.prefixsubfield.checked = false;
			document.forma1.fieldquote.checked = false;
			tipoacro = "T";
			for (i = 0; i < document.forma1.tipof.length; i++) document.forma1.tipof[i].checked = false;
			for (i = 0; i < document.forma1.separ.length; i++) document.forma1.separ[i].checked = false;

			ix = document.forma1.fgen.selectedIndex
			if (ix == 0) return
			format = document.forma1.fgen.options[ix].value

			formatar = format.split('|')
			separset = false;
			if (0 in formatar) document.forma1.nombre.value = formatar[0]
			if (1 in formatar) document.forma1.descripcion.value = formatar[1]
			if (2 in formatar) {
				for (i = 0; i < document.forma1.tipof.length; i++) {
					if (document.forma1.tipof[i].value === formatar[2]) {
						document.forma1.tipof[i].checked = true
						tipoacro = document.forma1.tipof[i].value
					}
				}
			}
			if (3 in formatar) {
				for (i = 0; i < document.forma1.separ.length; i++) {
					if (document.forma1.separ[i].id === formatar[3]) {
						document.forma1.separ[i].checked = true
						separset = true
					}
				}
			}
			if (!separset && tipoacro == 'CT') document.forma1.separ[0].checked = true;
			if (!separset && tipoacro == 'CD') document.forma1.separ[0].checked = true;
			if (4 in formatar && formatar[4] == "1") document.forma1.prefixsubfield.checked = true
			if (5 in formatar && formatar[5] == "1") document.forma1.fieldquote.checked = true
		}

		function SendForEdit() {
			if (document.forma1.fgen.selectedIndex == 0 || document.forma1.fgen.selectedIndex == -1) {
				alert("<?php echo $msgstr["r_self"] ?>")
				return
			}
			document.forma1.headings.value = ""
			document.forma1.pft.value = ""
			document.forma1.action = "pft.php"
			document.forma1.Opcion.value = "edit"
			document.forma1.target = "_self"
			document.forma1.submit()
		}

		function SubmitForm(vp) {
			document.forma1.vp.value = vp;
			document.forma1.target = "_self";
			document.forma1.headings.value = document.forma1.headings.value.trimEnd()
			de = Trim(document.forma1.Mfn.value)
			a = Trim(document.forma1.to.value)
			if (de != "" || a != "") {
				document.forma1.Opcion.value = "rango"
				Se = ""
				var strValidChars = "0123456789";
				blnResult = true
				for (i = 0; i < de.length; i++) {
					strChar = de.charAt(i);
					if (strValidChars.indexOf(strChar) == -1) {
						alert("<?php echo $msgstr["especificarvaln"] ?>")
						return
					}
				}
				for (i = 0; i < a.length; i++) {
					strChar = a.charAt(i);
					if (strValidChars.indexOf(strChar) == -1) {
						alert("<?php echo $msgstr["especificarvaln"] ?>")
						return
					}
				}
				de = Number(de)
				a = Number(a)
				if (de <= 0 || a <= 0 || de > a || a > top.maxmfn) {
					alert("<?php echo $msgstr["numfr"] ?>")
					return
				}
			}
			document.forma1.pft.value = document.forma1.pft.value.trim();
			if (document.forma1.pft.value == "" && document.forma1.fgen.selectedIndex < 1 && document.forma1.pft.value == "") {
				alert("<?php echo $msgstr["r_selgen"] ?>")
				return
			}
			if (Trim(document.forma1.Expresion.value) == "" && Trim(document.forma1.Mfn.value) == "" && Trim(document.forma1.seleccionados.value) == "") {
				alert("<?php echo $msgstr["r_selreg"] ?>")
				return
			}
			if (vp == "P") {
				msgwin = window.open("", "VistaPrevia", "width=600,top=0,left=0,resizable, status, scrollbars")
				document.forma1.target = "VistaPrevia"
				msgwin.focus()
			}
			if (vp == "PRINT") {
				msgwin = window.open("", "VistaPrevia", "width=800,top=0,left=0,resizable, status, scrollbars")
				document.forma1.target = "VistaPrevia"
				msgwin.focus()
			}
			document.forma1.submit()
		}

		function toggleLayer(whichLayer) {
			var elem = document.getElementById(whichLayer);
			if (!elem) return;

			if (elem.style.display == 'none' || elem.style.display == '') {
				elem.style.display = 'block';
			} else {
				elem.style.display = 'none';
			}
		}

		function ValidarFormato() {
			if (Trim(document.forma1.pft.value) == "") {
				alert("<?php echo $msgstr["genformat"] ?>")
				return
			}
			document.forma1.action = "crearbd_new_create.php"
			document.forma1.submit()
		}
	</script>

	<?php
	if ($encabezado != "") {
		include("../common/institutional_info.php");
	}
	?>

	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php echo $msgstr["pft"] . " &rarr; " . $msgstr["database"] . ": " . $base ?>
		</div>
		<div class="actions">
			<?php
			if ($Opcion == "new") {
				$ayuda = "pft_create.html";
				$backtoscript = "fst.php?Opcion=new";
				$backtocancelscript = "../dbadmin/menu_creardb.php";
				include "../common/inc_back.php";
				include "../common/inc_cancel.php";
			} else {
				$ayuda = "pft.html";
				if (isset($arrHttp["encabezado"])) {
					if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_MODIFYDB"]) or isset($_SESSION["permiso"][$base . "_CENTRAL_MODIFYDB"]) or isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])) {

						if (isset($arrHttp["retorno"]) && trim($arrHttp["retorno"]) != "") {
							$backtoscript = $arrHttp["retorno"];
						} elseif (isset($arrHttp["from"]) && $arrHttp["from"] == "statistics") {
							$backtoscript = "../statistics/tables_generate.php";
						} else {
							$backtoscript = "menu_modificardb.php";
						}

						include "../common/inc_back.php";
						include("../common/inc_home.php");
					} else {
						include("../common/inc_home.php");
					}
				} else {
					$backtoscript = "../dataentry/inicio_main.php";
					include "../common/inc_back.php";
				}
				$backtocancelscript = $backtoscript;
			}
			?>
		</div>
		<div class="spacer">&#160;</div>
	</div>

	<?php
	include "../common/inc_div-helper.php";

	if ($Opcion != "new") {
		$pftdir = "pfts/" . $_SESSION["lang"];
		$pftdirfull = $db_path . $base . "/" . $pftdir;
		if (!file_exists($pftdirfull)) mkdir($pftdirfull, 0770, true);
		if (!file_exists($pftdirfull)) {
			echo "<br><b style='color:red'>" . $msgstr["fatal"] . ". " . $msgstr["folderne"] . ": " . $pftdirfull . "</b>";
			die;
		}
		$fdtarchivo = $db_path . $base . "/def/" . $_SESSION["lang"] . "/" . $base . ".fdt";
		if (file_exists($fdtarchivo)) {
			$fpTm = file($fdtarchivo);
		} else {
			$fdtarchivo = $db_path . $base . "/def/" . $lang_db . "/" . $base . ".fdt";
			if (file_exists($fdtarchivo)) {
				$fpTm = file($fdtarchivo);
			} else {
				echo "<br><b style='color:red'>" . $msgstr["fatal"] . ". " . $msgstr["misfile"] . ": " . $fdtarchivo . "</b>";
				die;
			}
		}
	} else {
		$pftdir = "";
		$fpTm = explode("\n", $_SESSION["FDT"]);
	}

	foreach ($fpTm as $linea) {
		if (trim($linea) != "") {
			$t = explode('|', $linea);
			if ($t[0] != "S") $Fdt[] = rtrim($linea);
		}
	}

	if ($Opcion != "new") {
		include("../common/inc_get-dbinfo.php");
	} else {
		$arrHttp["MAXMFN"] = "";
	}
	?>

	<div class="middle form">
		<div class="formContent">

			<div class="formContent" style="padding: 20px;">

				<form name=forma1 method=post action=../dataentry/imprimir_g.php>
					<input type=hidden name=base value=<?php echo $base ?>>
					<input type=hidden name=cipar value=<?php echo $cipar ?>>
					<input type=hidden name=Modulo value='<?php if (isset($arrHttp["Modulo"])) echo $arrHttp["Modulo"] ?>'>
					<input type=hidden name=Opcion>
					<input type=hidden name=vp>
					<?php if ($encabezado != "") echo "<input type=hidden name=encabezado value=s>\n"; ?>

					<input type=hidden name=from value="<?php echo isset($arrHttp["from"]) ? $arrHttp["from"] : ""; ?>">
					<input type=hidden name=retorno value="<?php echo isset($arrHttp["retorno"]) ? $arrHttp["retorno"] : ""; ?>">
					<?php
					if ($Opcion != "new") {
						// ==== 1. USE AN EXISTING FORMAT ====
					?>
						<div class="stat-option-group">
							<div class="stat-option-header" onclick="toggleLayer('useexformat')">
								<strong><?php echo $msgstr["useexformat"] ?></strong>
								<i class="fas fa-chevron-down"></i>
							</div>
							<div id="useexformat" class="stat-option-content" style="<?php echo ($Opcion == 'edit') ? 'display:block;' : 'display:none;'; ?>">
								<div class="form-group">
									<label title="<?php echo $msgstr["pftexisting"] ?>"><?php echo $msgstr["r_formatos"] ?>:</label>
									<select name=fgen onchange='javascript:SelectExistingFormat()' class="form-control" title="<?php echo $msgstr["pftexisting"] ?>">
										<option value='' />
										<?php
										unset($fp);
										$archivo = $db_path . $base . "/pfts/" . $_SESSION["lang"] . "/formatos.dat";
										if (!file_exists($archivo)) $archivo = $db_path . $base . "/pfts/" . $lang_db . "/formatos.dat";
										if (file_exists($archivo)) $fp = file($archivo);
										if (isset($fp)) {
											foreach ($fp as $value) {
												$value = trim($value);
												if ($value != "") {
													$pp = explode('|', $value);
													if (!isset($pp[2])) $pp[2] = "";
													if (!isset($pp[3])) $pp[3] = "";
													if (!isset($pp[4])) $pp[4] = "";
													if (!isset($pp[5])) $pp[5] = "";
													if (
														isset($_SESSION["permiso"]["CENTRAL_ALL"])
														or isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])
														or isset($_SESSION["permiso"][$base . "_pft_ALL"])
														or isset($_SESSION["permiso"][$base . "_pft_" . $pp[0]])
													) {
														$selected = "";
														if ($pp[0] == $pft_name) $selected = "selected";
														echo "<option " . $selected . " value=\"" . $pp[0] . "|" . $pp[1] . "|" . $pp[2] . "|" . $pp[3] . "|" . $pp[4] . "|" . $pp[5] . "\">" . $pp[1] . " (" . $pp[0] . ")</option>\n";
													}
												}
											}
										}
										?>
									</select>
									<?php
									if (
										isset($_SESSION["permiso"]["CENTRAL_ALL"])
										or isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])
										or isset($_SESSION["permiso"][$base . "_CENTRAL_EDPFT"])
										or isset($_SESSION["permiso"]["_CENTRAL_EDPFT"])
									) {
									?>
										&nbsp;
										<button class="bt-green" type="button" title="<?php echo $msgstr["edit"] ?>:&nbsp;<?php echo $base . "/" . $pftdir . "/..." ?>" onclick='javascript:SendForEdit("")'>
											<i class="far fa-edit"></i> <?php echo $msgstr["edit"] ?>
										</button>
										<button class="bt-red" type="button" title="<?php echo $msgstr["delete"] ?>:&nbsp;<?php echo $base . "/" . $pftdir . "/..." ?>" onclick='javascript:DeleteFormat()'>
											<i class="fas fa-trash"></i> <?php echo $msgstr["delete"] ?>
										</button>
									<?php
									}
									?>
								</div>
							</div>
						</div>
					<?php
					} else {
						echo "<div id=useexformat></div>";
					}

					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])
						or isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])
						or isset($_SESSION["permiso"]["CENTRAL_EDPFT"])
						or isset($_SESSION["permiso"][$base . "_CENTRAL_EDPFT"])
						or isset($_SESSION["permiso"]["CENTRAL_MODIFYDB"])
						or isset($_SESSION["permiso"][$base . "_CENTRAL_MODIFYDB"])
					) {

						if ($Opcion == "edit") {
							if (ReadPFT($pft_name, $pft_content) != 0) die;
							if (ReadPFT_H($pft_name, $pft_h_content) != 0) die;
						}
					?>

						<div class="stat-option-group">
							<div class="stat-option-header" onclick="toggleLayer('createformat')">
								<strong><?php if ($Opcion == "edit") {
											echo $msgstr["editcreate"];
										} else {
											echo $msgstr["r_creaf"];
										} ?></strong>
								<i class="fas fa-chevron-down"></i>
							</div>

							<div id="createformat" class="stat-option-content" style="<?php if ($Opcion != "new" && $Opcion != "edit") echo "display:none;";
																						else echo "display:block;"; ?>">
								<p><?php echo $msgstr["r_incluirc"] ?>&nbsp; &nbsp;
									<a href=fdt_leer.php?base=<?php echo $arrHttp["base"] ?> target=_blank><i class="fas fa-eye"></i> <?php echo $msgstr["show"] . " " . $msgstr["fdt"] ?></a>
								</p>

								<div class="flex-row">
									<div>
										<select name=list11 class="form-control" style="width:250px" multiple size=10 onDblClick="moveSelectedOptions(document.forma1.list11, document.forma1.list21, false)">
											<?php
											foreach ($Fdt as $linea) {
												$t = explode('|', $linea);
												echo "<option value='" . $linea . "'>" . $t[2] . " (" . $t[1] . ")</option>\n";
											}
											?>
										</select>
									</div>
									<div class="flex-col">
										<a class="button_browse show" href="#" onClick="moveSelectedOptions(document.forma1.list11, document.forma1.list21, false); return false;"><i class="fas fa-angle-right"></i></a>
										<a class="button_browse show" href="#" onClick="moveAllOptions(document.forma1.list11, document.forma1.list21, false); return false;"><i class="fas fa-angle-double-right"></i></a>
										<a class="button_browse show" href="#" onClick="moveAllOptions(document.forma1.list21, document.forma1.list11, false); return false;"><i class="fas fa-angle-double-left"></i></a>
										<a class="button_browse show" href="#" onClick="moveSelectedOptions(document.forma1.list21, document.forma1.list11, false); return false;"><i class="fas fa-angle-left"></i></a>
									</div>
									<div>
										<select name="list21" class="form-control" MULTIPLE SIZE=10 style="width:250px" onDblClick="moveSelectedOptions(document.forma1.list21, document.forma1.list11, false)"></select>
									</div>
									<div class="flex-col">
										<button class="button_browse show" type="button" value="<?php echo $msgstr["up"] ?>" title="<?php echo $msgstr['up'] ?>" onClick="moveOptionUp(document.forma1.list21)"><i class="fas fa-caret-up"></i></button>
										<button class="button_browse show" type="button" value="<?php echo $msgstr["down"] ?>" title="<?php echo $msgstr["down"] ?>" onClick="moveOptionDown(document.forma1.list21)"><i class="fas fa-caret-down"></i></button>
									</div>
								</div>

								<hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

								<div class="flex-row" style="gap:30px;">
									<div class="form-group">
										<strong><?php echo $msgstr["pfttemplate"] ?></strong><br>
										<label><input type=radio name=tipof value=T <?php if ($tipoacro == "T")  echo "checked" ?>> <?php echo $msgstr["r_tabla"] ?></label><br>
										<label><input type=radio name=tipof value=P <?php if ($tipoacro == "P")  echo "checked" ?>> <?php echo $msgstr["r_parrafo"] ?></label><br>
										<label><input type=radio name=tipof value=PL <?php if ($tipoacro == "PL") echo "checked" ?>> <?php echo $msgstr["r_parrafowith"] ?></label><br>
										<label><input type=radio name=tipof value=CT <?php if ($tipoacro == "CT") echo "checked" ?>> <?php echo $msgstr["r_colstab"] ?></label><br>
										<label><input type=radio name=tipof value=CD <?php if ($tipoacro == "CD") echo "checked" ?>> <?php echo $msgstr["r_colsdelim"] ?></label>
									</div>
									<div class="form-group">
										<strong><?php echo $msgstr["pftcoldeloptions"] ?></strong><br>
										<label title='"..."'><input type=checkbox name=fieldquote id=fieldquote <?php if ($fieldquoteacro == "1") echo "checked" ?>> <?php echo $msgstr["pftfieldquote"] ?></label>
										<br><br>
										<strong><?php echo $msgstr["pftseparfield"] ?></strong><br>
										<label><input type=radio name=separ value="|" id=VBAR <?php if ($separacro == "VBAR") echo "checked" ?>> <b>|</b> </label> &nbsp;
										<label><input type=radio name=separ value="," id=COMMA <?php if ($separacro == "COMMA") echo "checked" ?>> <b>,</b> </label> &nbsp;
										<label><input type=radio name=separ value="~" id=TILDE <?php if ($separacro == "TILDE") echo "checked" ?>> <b>~</b> </label>
									</div>
									<div class="form-group">
										<strong><?php echo $msgstr["pftsubfoptions"] ?></strong><br>
										<label title='^a<value>'><input type=checkbox name=prefixsubfield id=prefixsubfield <?php if ($prefixsubfieldacro == "1") echo "checked" ?>> <?php echo $msgstr["pftprefsub"] ?></label>
									</div>
								</div>

								<div style="margin-top: 15px;">
									<button class="bt-blue" type="button" title="<?php echo $msgstr["pftgenformatplus"] ?>" onclick='javascript:GenerarFormato()'>
										<i class="far fa-plus-square"></i> <?php echo $msgstr["pftgenformat"] ?>
									</button>
									&nbsp;
									<button class="bt-gray" type="button" title="<?php echo $msgstr["pftresetcreate"] ?>" onclick='javascript:ClearFormat()'>
										<i class="fa fa-times"></i> <?php echo $msgstr["borrar"] ?>
									</button>
								</div>

								<hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

								<div class="flex-row" style="align-items: flex-start;">
									<div style="flex:2;">
										<strong><?php echo $msgstr["pftcontent"] ?></strong>
										<a href="https://abcd-community.org/cisis-formatting-language/" target="_blank" style="float:right; text-decoration:none;"><i class="far fa-life-ring"></i> <?php echo $msgstr["cisis"] ?></a>
										<textarea name=pft class="form-control" rows=10 style="font-family:courier new; width:100%;"><?php echo $pft_content ?></textarea>
									</div>
									<div style="flex:1;">
										<strong><?php echo $msgstr["pftheadings"] ?></strong>
										<textarea name=headings class="form-control" rows=10 style="font-family:courier new; width:100%;" onchange=CheckType()><?php echo $pft_h_content ?></textarea>
									</div>
								</div>
							</div>
						</div>
					<?php
					} else {
						echo "<div id=createformat></div>";
					}

					// ==== 3. SAVE FORMAT ====
					if (
						$Opcion != "new" and
						(isset($_SESSION["permiso"]["CENTRAL_ALL"])
							or isset($_SESSION["permiso"]["CENTRAL_EDPFT"])
							or isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])
							or isset($_SESSION["permiso"][$base . "_CENTRAL_EDPFT"]))
					) {
					?>
						<div class="stat-option-group">
							<div class="stat-option-header" onclick="toggleLayer('saveformat')">
								<strong><?php echo $msgstr["r_guardar"] ?></strong>
								<i class="fas fa-chevron-down"></i>
							</div>
							<div id="saveformat" class="stat-option-content" style="<?php if ($Opcion != "new") echo "display: none;";
																					else echo "display:block;"; ?>">
								<div class="flex-row">
									<div class="form-group">
										<label title="<?php echo $msgstr["pftnameenter"] ?>"><?php echo $msgstr["name"] ?></label><br>
										<input type=text name=nombre size=20 maxlength=30 class="form-control" value="<?php echo $pft_name ?>" title="<?php echo $msgstr["pftnameenter"] ?>">
									</div>
									<div class="form-group" style="flex: 1;">
										<label><?php echo $msgstr["description"] ?></label><br>
										<input type=text size=50 maxlength=50 name=descripcion class="form-control" value="<?php echo $description ?>">
									</div>
									<div class="form-group" style="margin-top: 20px;">
										<button class="bt-green" type="button" title="<?php echo $msgstr["saveinfolder"] ?>:&nbsp;<?php echo $base . "/" . $pftdir ?>/" onclick="javascript:SaveFormat()">
											<i class="far fa-save"></i> <?php echo $msgstr["r_guardar"]; ?>
										</button>
									</div>
								</div>
							</div>
						</div>
					<?php
					}

					// ==== 4. GENERATE OUTPUT ==== 
					if ($Opcion != "new") { ?>
						<div class="stat-option-group">
							<div class="stat-option-header" onclick="toggleLayer('generateoutput')">
								<strong><?php echo $msgstr["generateoutput"] ?></strong>
								<i class="fas fa-chevron-down"></i>
							</div>
							<div id="generateoutput" class="stat-option-content" style="display:none;">

								<div class="form-group" style="background: #fafafa; padding: 15px; border-radius: 4px; border: 1px dashed #ccc;">
									<strong style="font-size:1.1em; color:#333;"><?php echo $msgstr["r_recsel"] ?>: <?php echo $msgstr["r_mfnr"] ?></strong>
									<div class="flex-row" style="margin-top: 10px;">
										<div><label><?php echo $msgstr["r_desde"] ?>:</label> <input type=text name=Mfn size=10 class="form-control" style="display:inline-block; width:auto;"></div>
										<div><label><?php echo $msgstr["r_hasta"] ?>:</label> <input type=text name=to size=10 class="form-control" style="display:inline-block; width:auto;"></div>
										<div style="color:#777;"><small><?php echo $msgstr["maxmfn"] ?>: <?php echo $arrHttp["MAXMFN"] ?></small></div>
										<button class="bt-gray" type="button" title="<?php echo $msgstr["borrar"] ?>" onclick='javascript:ClearRange()'><i class="fa fa-times"></i></button>
									</div>
								</div>

								<?php if (isset($arrHttp["seleccionados"])) { ?>
									<div class="form-group" style="background: #fafafa; padding: 15px; border-radius: 4px; border: 1px dashed #ccc; margin-top: 15px;">
										<strong style="font-size:1.1em; color:#333;"><?php echo $msgstr["r_recsel"] ?>: <?php echo $msgstr["selected_records"] ?></strong>
										<?php $sel = str_replace("__", ",", trim($arrHttp["seleccionados"]));
										$sel = str_replace("_", "", $sel); ?>
										<div class="flex-row" style="margin-top: 10px;">
											<input type=text name=seleccionados size=80 class="form-control" value="<?php echo $sel ?>">
											<button class="bt-red" type="button" title="<?php echo $msgstr["borrar"] ?>" onclick='javascript:DeleteSelected()'><i class="fa fa-times"></i></button>
										</div>
									</div>
								<?php } else {
									echo "<input type=hidden name=seleccionados>";
								} ?>

								<div class="form-group" style="background: #fafafa; padding: 15px; border-radius: 4px; border: 1px dashed #ccc; margin-top: 15px;">
									<strong style="font-size:1.1em; color:#333;"><?php echo $msgstr["r_recsel"] ?>: <?php echo $msgstr["r_busqueda"] ?></strong>
									<div style="margin-top: 10px;">
										<?php
										unset($fp);
										$search_file = $db_path . $base . "/pfts/" . $_SESSION["lang"] . "/search_expr.tab";
										if (!file_exists($search_file)) $search_file = $db_path . $base . "/pfts/" . $lang_db . "/search_expr.tab";

										if (file_exists($search_file)) {
											$fp = file($search_file);
											echo "<label>" . $msgstr["copysearch"] . ":</label> ";
											echo "<select name=Expr id=Expr onChange=CopiarExpresion() class='form-control' style='display:inline-block; width:auto; margin-right:10px;'>";
											echo "<option value=''></option>";
											foreach ($fp as $value) {
												$value = trim($value);
												if ($value != "") {
													$pp = explode('|', $value);
													echo "<option value='" . $pp[1] . "'>" . $pp[0] . "</option>";
												}
											}
											echo "</select>";
										?>
											<button class="bt-green" type="button" title="<?php echo $msgstr["pftcreatesrcexpr"] ?>" onclick='javascript:Buscar()'><i class="far fa-plus-square"></i></button>
											<button class="bt-red" type="button" title="<?php echo $msgstr["pftdelsrchexpr"] ?>" onclick='javascript:DeleteSrchExpr()'><i class="fas fa-trash"></i></button>
										<?php } else { ?>
											<button class="bt-green" type="button" title="<?php echo $msgstr["pftcreatesrcexpr"] ?>" onclick='javascript:Buscar()'><i class="far fa-plus-square"></i> <?php echo $msgstr["pftcreatesrcexpr"] ?></button>
										<?php } ?>
									</div>

									<div class="flex-row" style="margin-top: 10px; align-items:flex-start;">
										<textarea class="form-control" rows=2 style="width: 80%;" name=Expresion><?php if ($Expresion != "") echo $Expresion ?></textarea>
										<button class="bt-gray" type="button" title="<?php echo $msgstr["borrar"] ?>" onclick='javascript:DeleteExpression()'><i class="fa fa-times"></i></button>
									</div>

									<?php
									if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_SAVEXPR"]) or isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"]) or isset($_SESSION["permiso"][$base . "_CENTRAL_SAVEXPR"])) {
									?>
										<div class="flex-row" style="margin-top: 10px;">
											<label><?php echo $msgstr["r_desc"] . ": " ?></label>
											<input type=text name=Descripcion size=40 class="form-control" style="width: auto;">
											<button class="bt-green" type="button" title="<?php echo $msgstr["savesearch"] ?>" onclick="javascript:SaveSearch()"><i class="far fa-save"></i> <?php echo $msgstr["savesearch"] ?></button>
										</div>
									<?php } ?>
								</div>

								<div class="form-group" style="background: #fafafa; padding: 15px; border-radius: 4px; border: 1px dashed #ccc; margin-top: 15px;">
									<strong style="font-size:1.1em; color:#333;"><?php echo $msgstr["sortkey"] ?></strong>
									<div style="margin-top: 10px;">
										<label><?php echo $msgstr["sortkeycopy"] ?>:</label>
										<select name=sort onChange=CopySortKey() class="form-control" style="display:inline-block; width:auto; margin-right:10px;">
											<option value=''></option>
											<?php
											unset($fp);
											$sort_file = $db_path . $base . "/pfts/" . $_SESSION["lang"] . "/sort.tab";
											if (!file_exists($sort_file)) $sort_file = $db_path . $base . "/pfts/" . $lang_db . "/sort.tab";
											if (file_exists($sort_file)) {
												$fp = file($sort_file);
												foreach ($fp as $value) {
													if (trim($value) != "") {
														$pp = explode('|', $value);
														echo "<option value=\"" . trim($pp[1]) . "\">" . $pp[0] . "</option>";
													}
												}
											}
											?>
										</select>
										<?php
										if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_EDSORT"]) or isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"]) or isset($_SESSION["permiso"][$base . "_CENTRAL_EDSORT"])) {
										?>
											<button class="bt-green" type="button" title="<?php echo $msgstr["sortkeycreeddel"] ?>" onclick='javascript:CreateSortKey()'><i class="far fa-plus-square"></i></button>
										<?php } ?>
									</div>
									<div style="margin-top:10px;">
										<input type=text name=sortkey size=70 class="form-control" style="width:80%;">
									</div>
								</div>

								<div class="form-group" style="margin-top: 25px; padding-top: 15px; border-top: 2px solid #eee;">
									<h4 style="margin-bottom: 15px; font-size: 14px; color: #333;"><?php echo $msgstr["sendto"] ?>:</h4>

									<button class="bt-blue" type="button" title="<?php echo $msgstr["vistap"] ?>" onclick="javascript:SubmitForm('P')">
										<i class="far fa-eye"></i> <?php echo $msgstr["vistap"] ?>
									</button> &nbsp;
									<button class="bt-blue" type="button" title="<?php echo $msgstr["word"] ?>" onclick="javascript:SubmitForm('WP')">
										<i class="far fa-file-word"></i> <?php echo $msgstr["word"] ?>
									</button> &nbsp;
									<button class="bt-blue" type="button" title="<?php echo $msgstr["wsproc"] ?>" onclick="javascript:SubmitForm('TB')">
										<i class="far fa-file-excel"></i> <?php echo $msgstr["wsproc"] ?>
									</button> &nbsp;
									<button class="bt-blue" type="button" title="<?php echo $msgstr["pftplaintxt"] ?>" onclick="javascript:SubmitForm('TXT')">
										<i class="far fa-file-alt"></i> <?php echo $msgstr["pftplaintxt"] ?>
									</button> &nbsp;
									<button class="bt-blue" type="button" title="<?php echo $msgstr["Print"] ?>" onclick="javascript:SubmitForm('PRINT')">
										<i class="fas fa-print"></i> <?php echo $msgstr["Print"] ?>
									</button>
								</div>

							</div>
						</div>
						<br>
					<?php
						include "../common/inc_back.php";
					} else {
					?>
						<div style="margin-top: 15px;">
							<button class="bt-green" type="button" title="<?php echo $msgstr["createdb"] ?>" onclick="javascript:ValidarFormato()">
								<i class="far fa-save"></i> <?php echo $msgstr["createdb"] ?>
							</button>
						</div>
					<?php
					}
					?>

				</form>
				<form name=guardar method=post action=pft_update.php>
					<input type=hidden name=base value=<?php echo $base ?>>
					<input type=hidden name=from value="<?php echo isset($arrHttp["from"]) ? $arrHttp["from"] : ""; ?>">
					<input type=hidden name=pft>
					<input type=hidden name=nombre>
					<input type=hidden name=descripcion>
					<input type=hidden name=tipoacro>
					<input type=hidden name=separacro>
					<input type=hidden name=prefixsubfieldacro>
					<input type=hidden name=fieldquoteacro>
					<input type=hidden name=headings>
					<input type=hidden name=pftname>
					<?php if ($encabezado == "s") echo "<input type=hidden name=encabezado value=s>"; ?>
					<input type=hidden name=Modulo value='<?php if (isset($arrHttp["Modulo"])) echo $arrHttp["Modulo"] ?>'>
				</form>
				<form name=frmdelete action=pft_delete.php method=post>
					<input type=hidden name=base value=<?php echo $base ?>>
					<input type=hidden name=pft>
					<?php if ($encabezado == "s") echo "<input type=hidden name=encabezado value=s>"; ?>
				</form>
				<form name=savesearch action=../dataentry/busqueda_guardar.php method=post target=savesearch>
					<input type=hidden name=base value=<?php echo $base ?>>
					<input type=hidden name=Expresion value="">
					<input type=hidden name=Descripcion value="">
				</form>
				<form name=removesearch action=../dataentry/busqueda_eleminar.php method=post target=removesearch>
					<input type=hidden name=base value=<?php echo $base ?>>
					<input type=hidden name=Descripcion value="">
				</form>
				<form name=sortkey method=post action=sortkey_edit.php target=sortkey>
					<input type=hidden name=base value=<?php echo $base ?>>
					<input type=hidden name=encabezado value=s>
				</form>
			</div>
		</div>
		<?php
		include("../common/footer.php");
		?>
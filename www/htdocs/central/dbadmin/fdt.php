<?php
/* Modifications
2021-02-09 fho4abcd Original name for dhtmlX.js
2021-11-25 fho4abcd Validate tags on int value, not on string (e.g. 1==001->error) + improve html
2024-04-01 fho4abcd More error messages. Improve layout,translations. Move dhtmlXGrid images to /assets. Remove unused code
2024-04-14 fho4abcd Translate function names. Enable Drag&drop + multiselect. Remove unused "moodle". Small updates.
2024-04-14 fho4abcd Code more equal to fdt_short_a.php + moved "common" code to included files
2024-04-16 fho4abcd Increase row link font, improve indent
2024-04-22 fho4abcd Send title to picklist edit
2025-02-09 fho4abcd Tests for Simple/Multiple picklist added. Moved displayed errors before the table display
2025-02-11 fho4abcd replace browse by translated message
2025-08-24 fho4abcd Add tagnumber and subfields for edit text table
2026-04-20 rogercgui Add TAB to field types 
/*

** See https://docs.dhtmlx.com/api__dhtmlxgrid_addrow.html / https://docs.dhtmlx.com/grid__styling.html for grid details
** fdt.php and fdt_short_a.php are nearly equal.
** - Common code is present in included files
** - Both files contain some code that can be considered as 'dummy'.
** - Please copy modifications between these files if anyway possible
*/
session_start();
include("../common/get_post.php");
include("../config.php");

$lang = $_SESSION["lang"];

include("../lang/admin.php"); // for indexes in institutionalinfo
include("../lang/dbadmin.php");

//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
	die;
}
if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"] = "";
if ($arrHttp["Opcion"] != "new") {
	if (!isset($_SESSION["permiso"]["CENTRAL_ALL"]) and !isset($_SESSION["permiso"][$arrHttp["base"] . "CENTRAL_MODIFYDEF"]) and !isset($_SESSION["permiso"][$arrHttp["base"] . "CENTRAL_ALL"])) {
		header("Location: ../common/error_page.php");
		die;
	}
} else {
	if (!isset($_SESSION["permiso"]["CENTRAL_ALL"]) and !isset($_SESSION["permiso"]["CENTRAL_CRDB"])) {
		header("Location: ../common/error_page.php");
		die;
	}
}
if ($arrHttp["Opcion"] == "new") {
	$fp = file_exists($db_path . $arrHttp["base"] . "/data/" . $arrHttp["base"] . ".mst");
	if ($fp) {
		echo "<h1>" . $msgstr["dbexists"] . "</h1>";
		die;
	}
	//OJO ARREGLAR ESTO PARA QUE SALGA LA DESCRIPCIÓN
	if (isset($arrHttp["desc"])) $_SESSION["DESC"] = $arrHttp["desc"];
	echo "<script>Opcion='new'</script>\n";
}
include("fdt_include.php");
include("../common/header.php");
?>

<body>
	<link rel="stylesheet" type="text/css" href="/assets/css/dhtmlXGrid.css">
	<script src="../dataentry/js/dhtml_grid/dhtmlX.js"></script>
	<script src="../dataentry/js/lr_trim.js"></script>
	<?php include("fdt_inc_script.php") ?>

	<?php include("../common/inc_wait.php") ?>

	<script>
		function EditRow(Fila, id) {
			Fila = mygrid.getRowIndex(mygrid.getSelectedId())
			tipoC = mygrid.cells2(Fila, 1).getValue()
			tagC = mygrid.cells2(Fila, 2).getValue()
			switch (tipoC) {
				case "MF": //Campo fijo Marc
					msgwin = window.open("", "WinRow", "menu=0,scrollbars=yes,resizable,width=600")
					document.MFedit.tag.value = tagC
					document.MFedit.submit()
					msgwin.focus()
					break
				case "LDR": // Leader Marc
					break
				default:
					cols = mygrid.getColumnCount()
					VC = ''
					for (j = 1; j < cols; j++) {
						cell = mygrid.cells2(Fila, j).getValue()
						if (j != 14) VC = VC + cell + '|'
					}
					document.rowedit.ValorCapturado.value = VC
					document.rowedit.row.value = Fila
					msgwin = window.open("", "WinRow", "menu=0,scrollbars=yes,resizable,width=600,status")
					document.rowedit.submit()
					msgwin.focus()
			}
		}

		function Actualizar() {
			cols = mygrid.getColumnCount()
			rows = mygrid.getRowsNum()
			VC = ""
			for (i = 0; i < rows; i++) {
				if (Trim(mygrid.cells2(i, 1).getValue()) != "") {
					if (VC != "") VC = VC + "\n"
					for (j = 1; j < cols; j++) {
						cell = mygrid.cells2(i, j).getValue()
						if (j != 14) VC = VC + cell + '|'
					}
				}
			}
			document.forma1.ValorCapturado.value = VC
			document.forma1.submit()
			return
		}

		function Validate(Opcion) {
			var width = screen.availWidth;
			var height = screen.availHeight
			msgwin = window.open("", "Fdt", "width=" + width + ", height=" + height + " resizable=yes, scrollbars=yes, menu=yes")

			// 1. Clone all CSS and FontAwesome styles from the main (parent) window
			var estilos = document.querySelectorAll('link[rel="stylesheet"], style');
			var headCSS = "";
			estilos.forEach(function(el) {
				headCSS += el.outerHTML;
			});

			// 2. Set the window header by inheriting the styles
			msgwin.document.writeln("<!DOCTYPE html><html><head><title>Validation Report</title>")
			msgwin.document.writeln(headCSS)
			msgwin.document.writeln("<style>")
			msgwin.document.writeln("  body { font-size: 10pt; padding: 25px; background-color: #f4f6f9; }")
			msgwin.document.writeln("  td { font-family: arial; font-size: 9pt; padding: 6px; }")
			msgwin.document.writeln("  table { border-collapse: collapse; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-top: 15px; width: 100%; }")
			msgwin.document.writeln("  table td { border: 1px solid #e0e0e0; }")
			msgwin.document.writeln("  a { color: #0056b3; text-decoration: none; font-weight: bold; }")
			msgwin.document.writeln("  a:hover { text-decoration: underline; }")
			msgwin.document.writeln("</style>")
			msgwin.document.writeln("</head><body>")

			cols = mygrid.getColumnCount()
			rows = mygrid.getRowsNum()
			msg = ""
			fdt_tag = ""
			mainentry = 0
			displayTag = " <?php echo $msgstr["tag"] ?>" + ": "
			displayRow = " <?php echo $msgstr["fdtrow"] ?>" + ": "
			// Loop over rows to test the content. The table is displayed later
			for (i = 0; i < rows; i++) {
				irow = i + 1
				fila = ""
				in_type = ""
				fld_repeatable = "false"
				pl_type = ""
				pl_name = ""
				pl_prefix = ""
				pl_format = ""
				pl_display = ""
				cell = ""
				displayRowfull = displayRow + irow

				for (j = 1; j < cols; j++) { // Se verifica que la línea no esté en blanco
					cell = ""
					if (j != 14) {
						cell = Trim(mygrid.cells2(i, j).getValue())
						if (cell == "undefined") cell = ""
						if (cell == "0") cell = ""
						fila += cell
					}
				}
				Leader = ""
				if (Trim(fila) != "") {
					cell_colums = ""
					cell_rows = ""
					for (j = 1; j < cols; j++) {
						if (j != 14) { // why this exception?
							cell = Trim(mygrid.cells2(i, j).getValue())
							if (cell == "undefined") cell = ""
							switch (j) {
								case 1: // Record Type. Shown as "type name(shortcut)"
									cell_type = cell
									if (cell != "") {
										cell = field_type[cell]
										cell = cell + " (" + cell_type + ")"
									}
									if (cell_type == "LDR") Leader = "S"
									break
								case 2: // Tag
									cell_tag = cell
									displayTagfull = displayTag + cell_tag + " &rarr; "
									break
								case 3: // Titel/description
									cell_desc = cell
									break
								case 4: // Principle entry
									cell_index = cell
									if (cell == 1) mainentry++
								case 5: // Repeatable
									if (cell != "") {
										if (cell == 1)
											fld_repeatable = "true"
									}
									break
								case 6: //Subfields
									cell_subc = cell
									break
								case 7: //Preliterals
									cell_delim = cell
									break
								case 8: // Entry type/Input type
									in_type = cell
									displayIn_type = ""
									if (cell != "") {
										cell = input_type[cell]
										cell = cell + " (" + in_type + ")"
										displayIn_type = cell + " &rarr; "
									}
									break
								case 9: //rows
									cell_rows = cell
									break
								case 10: // columns
									cell_columns = cell
									break
								case 11: // Picklist type
									if (Trim(cell) != "") {
										pl_type = cell
										cell = pick_type[cell]
									}
									break
								case 12: // Picklist name
									pl_name = cell
									break
								case 13: // Picklist prefix
									pl_prefix = cell
									break
									// case 14 is excluded from the loop
								case 15: // Display format(PFT)
									pl_format = cell
									break
								case 16: // Display as
									pl_display = cell
									break;
								case 17: // Extract as
									cell = ""
									break
								case 18: //Help
									if (cell != "") {
										if (cell == 1)
											cell = "true"
										else
											cell = ""
									}
									break
								case 19: // Help URL
									url_help = cell
									break;
								case 20: // Link FDT
									if (cell != "") {
										if (cell == 1)
											cell = "true"
										else
											cell = ""
									}
									break
								case 21: // Req?
									if (cell != "") {
										if (cell == 1)
											cell = "true"
										else
											cell = ""
									}
									break
								case 22: // Field validation (is a picklist)
									if (cell != "") {
										cell = validation[cell]
										cell = cell + " (" + cell_type + ")"
									}
									break
								case 23: // Validation pattern
									cell_pattern = cell
									break
							}
						}
					} // end of loop over columns in the current row
					// Check of all entries in the current row that require checking
					if (cell_type != "L" && cell_type != "MF" && cell_type != "LDR") { //Todos los campos deben poseer descripcion menos el tipo L y el tipo MF
						if (cell_desc == "") {
							msg += displayRowfull + displayTagfull + " <?php echo $msgstr["misfdttitle"] ?>" + "<br>"
						}
					}
					if (cell_type == "H" || cell_type == "L" || cell_type == "S" || cell_type == "IND" || cell_type == "LDR" || cell_type == "TAB") { //These fields do not require a tag
						if (cell_tag != "" && cell_tag < 1) {
							msg += displayRowfull + displayTagfull + " <?php echo $msgstr["tagnoreq"] ?>" + " " + cell_type + "<br>"
						}
					} else {
						if (cell_tag == "") {
							msg += displayRowfull + " &rarr; <?php echo $msgstr["tagreq"] ?>" + " '" + cell_type + "'<br>"
						}
						if (cell_tag != "") {
							if (IsNumeric(cell_tag) == false) {
								msg += displayRowfull + displayTagfull + " <?php echo $msgstr["invtag"] ?>" + "<br>"
							} else {
								tt = parseInt(cell_tag)
								if (tt < 1 || tt > 999) {
									msg += displayRowfull + displayTagfull + " <?php echo $msgstr["invtag"] ?>" + "<br>"
								}
								if (fdt_tag.indexOf("|" + tt + "|") == -1) {
									fdt_tag += "|" + tt + "|"
								} else {
									msg += displayRowfull + displayTagfull + " <?php echo $msgstr["duptag"] ?>" + "<br>"
								}
							}
						}
					}
					if (cell_type == "S" || cell_type == "IND") { // Verifica se S ou IND tem um pai válido
						res = false
						for (ix = i - 1; ix >= 0; ix--) {
							type = mygrid.cells2(ix, 1).getValue()
							if (type == "T" || type == "TB" || type == "M" || type == "LDR") {
								res = true
								ix = -1
							} else {
								if (type != "S" && type != "IND") ix = -1
							}
						}
						if (res == false) {
							msg += displayRowfull + displayTagfull + " <?php echo $msgstr["sfgroup"] ?>" + "<br>"
						}
					}
					if (cell_type == "T") {
						if (cell_subc == "") {
							msg += displayRowfull + displayTagfull + " <?php echo $msgstr["missubf"] ?>" + "<br>"
						} else {
							ix = i + 1
							type = mygrid.cells2(ix, 1).getValue()
							if (type != "S" && type != "IND" && FDT == "S") {
								msg += displayRowfull + displayTagfull + " <?php echo $msgstr["missubfge"] ?>" + "<br>"
							}
						}
						ixsc = 0
						for (ix = i + 1; ix < rows; ix++) {
							type = mygrid.cells2(ix, 1).getValue()
							if (type == "S") {
								ixsc = ixsc + 1 // Conta apenas os subcampos reais (S)
							} else if (type == "IND") {
								// É um Indicador. Não soma na contagem, mas permite o loop continuar!
							} else {
								ix = rows + 99
							}
						}
						nsc = cell_subc.length
						if (nsc != ixsc) {
							msg += displayRowfull + displayTagfull + " <?php echo $msgstr["sfcounterr"] ?>" + "<br>"
						}
					}
					// Tests for input type S(simple) and M(multiple
					if (in_type == "S" && fld_repeatable == "true") {
						msg += displayRowfull + displayTagfull + "R=true + " + displayIn_type + " <?php echo $msgstr["invalidcombi"] ?>" + "<br>"
					}
					if (in_type == "M" && fld_repeatable == "false") {
						msg += displayRowfull + displayTagfull + "R=false + " + displayIn_type + " <?php echo $msgstr["invalidcombi"] ?>" + "<br>"
					}
					// Tests for the picklist info
					switch (pl_type) { // se valida la consistencia de los datos del picklist asignado al campo
						case "XT":
							msg += displayRowfull + displayTagfull + " <?php echo $msgstr["notimplemented"] ?>" + "<br>"
							break
						case "D":
						case "T":
							if (pl_type == "T" && pl_format == "" && pl_display == "" && pl_prefix == "")
								break
							if (pl_format == "") {
								msg += displayRowfull + displayTagfull + " <?php echo $msgstr["misextformat"] ?>" + "<br>"
							}
							if (pl_display == "" && pl_format == "") {
								msg += displayRowfull + displayTagfull + " <?php echo $msgstr["misdispformat"] ?>" + "<br>"
							}
							i_type = Trim(mygrid.cells2(i, 8).getValue())
							if (i_type != "X" && i_type != "RO" && i_type != "TB" && i_type != "COMBO" && i_type != "COMBORO" && i_type != "SRO" && i_type != "MRO") {
								msg += displayRowfull + displayTagfull + " <?php echo $msgstr["invinputype"] ?>" + "<br>"
							}
							break;
						case "P":
							if (pl_name == "") {
								msg += displayRowfull + displayTagfull + " <?php echo $msgstr["insplname"] ?>" + "<br>"
							}
							i_type = Trim(mygrid.cells2(i, 8).getValue())
							break
					}
				}
				// Se valida si los campos tipo COMBO, COMBORO, SRO, MRO, C, R tienen asignada una picklits
				i_type = Trim(mygrid.cells2(i, 8).getValue())
				if (i_type == "C" || i_type == "R" || i_type == "COMBO" || i_type == "COMBORO" || i_type == "SRO" || i_type == "MRO" || i_type == "S" || i_type == "M") {
					if (pl_type == "")
						msg += displayRowfull + displayTagfull + " <?php echo $msgstr["picklist"] . " " . $msgstr["missing"] ?>" + "<br>"
				}
				// Check the value of "rows"
				if (in_type == "OD") {
					if (cell_rows == "" || IsNumeric(cell_rows) == false || Number(cell_rows) <= 0) {
						msg += displayRowfull + displayTagfull + displayIn_type + " <?php echo $msgstr["fdterr_int_req_od"] ?>" + "<br>"
					}
				} else if (in_type == "A" || in_type == "M" || in_type == "T" || in_type == "X") {
					if (cell_rows != "" && (IsNumeric(cell_rows) == false || Number(cell_rows) < 0)) {
						// This requires further investigation. There exists FDT's with value 1.5 and possibly 1/5
						msg += displayRowfull + displayTagfull + displayIn_type + " <?php echo $msgstr["fdterr_noint"] ?>" + "<br>"
					}
				}

			} // end check loop over Rows

			if (mainentry > 1) {
				msg += "<?php echo $msgstr["errmainentry"] ?>"
			}

			// Display the error messages or "no errors"
			if (msg != "") {
				msgwin.document.writeln('<p><a href=../documentacion/ayuda.php?help=<?php echo $_SESSION["lang"] ?>/fdt_err.html target=_blank><?php echo $msgstr["err_fdt"] ?></a>&nbsp; &nbsp;')
				msgwin.document.writeln('<a href=../documentacion/edit.php?archivo=<?php echo $_SESSION["lang"] ?>/fdt_err.html target=_blank>edit help file</a>')
				msgwin.document.writeln("<h3 style='color:red'>" + msg + "</h3>")
				msgwin.focus()
			} else {
				msgwin.document.writeln("<h3><i class='fas fa-check-circle' style='color:green;'></i> <?php echo $msgstr["noerrors"] ?></h3>")
				msgwin.focus()
			}
			// Display the table
			msgwin.document.writeln("<table bgcolor=#F5F5F5>")
			HeadRowsForValidate("row")
			for (i = 0; i < rows; i++) {
				irow = i + 1
				fila = ""
				in_type = ""
				pl_type = ""
				pl_name = ""
				pl_prefix = ""
				pl_format = ""
				pl_display = ""
				cell = ""
				displayRowfull = displayRow + irow

				for (j = 1; j < cols; j++) { // Se verifica que la línea no esté en blanco
					cell = ""
					if (j != 14) {
						cell = Trim(mygrid.cells2(i, j).getValue())
						if (cell == "undefined") cell = ""
						if (cell == "0") cell = ""
						fila += cell
					}
				}
				if (Trim(fila) != "") {
					msgwin.document.writeln("<tr><td>" + irow + "</td>")
					cell_colums = ""
					cell_rows = ""
					for (j = 1; j < cols; j++) {
						if (j != 14) { // why this exception?
							cell = Trim(mygrid.cells2(i, j).getValue())
							if (cell == "undefined") cell = ""
							// Only cases that modify the ceel content are required here
							switch (j) {
								case 5: // Repeatable
									if (cell != "") {
										if (cell == 1)
											cell = "true"
										else
											cell = ""
									}
									break
								case 8: // Entry type/Input type
									in_type = cell
									displayIn_type = ""
									if (cell != "") {
										cell = input_type[cell]
										cell = cell + " (" + in_type + ")"
										displayIn_type = cell + " &rarr; "
									}
									break
								case 11: // Picklist type
									if (Trim(cell) != "") {
										pl_type = cell
										cell = pick_type[cell]
									}
									break
								case 17: // Extract as
									cell = ""
									break
								case 18: //Help
									if (cell != "") {
										if (cell == 1)
											cell = "true"
										else
											cell = ""
									}
									break
								case 20: // Link FDT
									if (cell != "") {
										if (cell == 1)
											cell = "true"
										else
											cell = ""
									}
									break
								case 21: // Req?
									if (cell != "") {
										if (cell == 1)
											cell = "true"
										else
											cell = ""
									}
									break
								case 22: // Field validation (is a picklist)
									if (cell != "") {
										cell = validation[cell]
										cell = cell + " (" + cell_type + ")"
									}
									break
							}
							// Display the cell
							msgwin.document.write("<td bgcolor=white>" + cell + "&nbsp;</td>")
						}
					} // end of loop over columns in the current row
				}
			} // end loop over Rows
			msgwin.document.writeln("</table>")

			if (Opcion == "Actualizar") {
				if (msg == "") {
					msgwin.close()
					return true
				} else {
					msgwin.document.writeln("<h4><?php echo $msgstr["fdterr"] ?></h4>")
					msgwin.focus()
					alert("<?php echo $msgstr["fdterr"] ?>!!!")
				}
			}
			msgwin.document.writeln("</body></html>")
			msgwin.document.close()
			msgwin.focus()
		}
	</script>
	<?php
	if (isset($arrHttp["encabezado"])) {
		include("../common/institutional_info.php");
	} ?>
	<form>
		<?php
		$xarch = $arrHttp["base"] . ".fdt";
		unset($fp);
		$link_fdt = "";
		$link_fdt = "S";
		if ($arrHttp["Opcion"] == "new") {
			if (!isset($_SESSION["FDT"])) {
				$fp = array();
				for ($i = 0; $i < 20; $i++) {
					$fp[$i] = '|||||||||||||||||||||||';
				}
			} else {
				$fp = explode("\n", $_SESSION["FDT"]);
				$fp[] = '||||||||||||||||||||||||||||||||||||||||||||||';
				$fp[] = '||||||||||||||||||||||||||||||||||||||||||||||';
			}
			$archivo = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $arrHttp["base"] . ".fdt";
			if (!file_exists($archivo)) $archivo = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $arrHttp["base"] . ".fdt";
		} else {
			if (isset($arrHttp["type"]) and $arrHttp["type"] == "bd") {
				$archivo = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $arrHttp["base"] . ".fdt";
				if (!file_exists($archivo)) $archivo = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $arrHttp["base"] . ".fdt";
			} else {
				if (isset($arrHttp["fmt_name"])) {
					$arrHttp["type"] = $arrHttp["fmt_name"] . ".fmt"; //EDIT A DATAENTRY WORKSHEET, ELSE EDIT A MARC FIXED FIELD FDT
					$link_fdt = "S";
				}
				if (isset($arrHttp["Fixed_field"])) {
					$arrHttp["type"] = $arrHttp["fdt_name"];
				}
				if (isset($arrHttp["type"])) {
					$archivo = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $arrHttp["type"];
					if (!file_exists($archivo)) $archivo = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $arrHttp["type"];
					$xarch = $arrHttp["type"];
				}
			}
			unset($fp);
			if (file_exists($archivo))	$fp = file($archivo);
		}
		echo "<div class=\"sectionInfo\">
		<div class=\"breadcrumb\">";
		if (isset($arrHttp["fmt_desc"])) {
			echo $msgstr["fmt"];
		} else {
			echo $msgstr["fdt"];
		}

		echo ": " . $xarch;
		if (isset($arrHttp["fmt_desc"])) echo " (" . $arrHttp["fmt_desc"] . ")";

		echo "</div><div class=\"actions\">";
		if ($arrHttp["Opcion"] == "new") {
			if (isset($arrHttp["encabezado"])) {
				$backtoscript = "../common/inicio.php?reinicio=s";
				include "../common/inc_back.php";
			} else {
				$backtoscript = "menu_creardb.php";
				include "../common/inc_back.php";
			}
		} else {
			if (isset($arrHttp["encabezado"])) {
				$encabezado = "&encabezado=s";
			} else {
				$encabezado = "";
			}
			if (isset($arrHttp["Fixed_field"])) {
				$backtoscript = "fixed_marc.php?base=" . $arrHttp["base"] . $encabezado;
				include "../common/inc_back.php";
			} else {
				if (!isset($arrHttp["ventana"])) {
					$backtoscript = "menu_modificardb.php?base=" . $arrHttp["base"] . $encabezado;
					include "../common/inc_back.php";
				} else {
					$backtoscript = "javascript:self.close()";
					include "../common/inc_back.php";
				}
			}
		}
		include "../common/inc_home.php";
		?>
		</div>
		<div class="spacer">&#160;</div>
		</div>

		<?php
		$ayuda = "fdt.html";
		include "../common/inc_div-helper.php";
		?>
		<div class="middle form">
			<div class="formContent">
				<a class="bt bt-blue" href="javascript:void(0)" onclick="AddRow(mygrid.getRowIndex(mygrid.getSelectedId()),'BEFORE')"><?php echo $msgstr["addrowbef"] ?></a>
				<a class="bt bt-blue" href="javascript:void(0)" onclick="AddRow(mygrid.getRowIndex(mygrid.getSelectedId())+1,'AFTER')"><?php echo $msgstr["addrowaf"] ?></a>
				<a class="bt bt-red" href="javascript:void(0)" onclick="mygrid.deleteSelectedItem()"><?php echo $msgstr["remselrow"] ?></a>
				<br><span class="bt-disabled"><i class="fas fa-info-circle"></i> <?php echo $msgstr['double_click'] ?></span>
				<br><span class="bt-disabled"><i class="fas fa-info-circle"></i> <?php echo $msgstr['fdt_move1'] ?></span>
				<br><span class="bt-disabled"><i class="fas fa-info-circle"></i> <?php echo $msgstr['fdt_move2'] ?></span>

				<div class="row">
					<table style="width:100%; height:300px;" id="tblToGrid" class="dhtmlxGrid" dragAndDrop="true">
						<?php
						echo "<tr>";
						foreach ($rows_title as $cell) {
							echo "<td>" . $cell . "</td>\n";
						}
						echo "</tr>";

						$nfilas = 0;
						$i = -1;
						if (isset($fp)) {
							$t = array();
							foreach ($fp as $value) {
								$value = trim($value);
								if (trim($value) != "") {
									$value .= "|||||||||||||||||||||";
									$t = explode("|", $value);
									switch ($t[0]) {
										case "OD":
											$t[0] = "F";
											$t[7] = "OD";
											break;
										case "OC":
											$t[0] = "F";
											$t[7] = "OC";
											break;
										case "ISO":
											$t[0] = "F";
											$t[7] = "ISO";
											break;
										case "DC":
											$t[0] = "F";
											$t[7] = "DC";
											break;
										case "AI":
											$t[0] = "F";
											$t[7] = "AI";
											break;
									}
									$nfilas = $nfilas + 1;
									echo "\n<tr>\n";
									$i = $i + 1;
									$irow = $i + 1;
									$linkr = "<a href='javascript:EditRow(\"" . $irow . "\",$i)'>$irow</a>";
									echo "<td type=\"link\">$linkr</td>";
									if ($t[0] == "F" or $t[0] == "S") {
										if (trim($t[7]) == "") $t[7] = "X";
									}
									$pick = "";
									for ($ix = 0; $ix < 21; $ix++) if (!isset($t[$ix])) $t[$ix] = "";
									if (trim($t[0]) != "H" and trim($t[0]) != "L" and trim($t[0]) != "TAB") {
										if ($t[10] == "")
											$pick = "<a href='javascript:Picklist(\"" . $t[1] . ".tab\",$i)'><font size=1>" . $msgstr["edit"] . "</a>";
										else
											$pick = "<a href='javascript:Picklist(\"" . $t[10] . "\",$i)'><font size=1>" . $msgstr["edit"] . "</a>";
									}
									if (!isset($t[16])) $t[16] = "";
									$ixt = -1;
									//"link,coro,ed,ed,ch,ch,ed,ed,coro,ed,ed,coro,ed,ed,link,ed,ed,ed,ch,ed,ch,ch,coro,ed"
									foreach ($t as $fila) {
										$fila = trim($fila);
										$ixt = $ixt + 1;
										if ($ixt > 21) break;        //NUMERO DE COLUMNAS QUE TIENE LA FDT
										if ($ixt == 16 or $ixt == 18 or $ixt == 19)
											$align = " align=center";
										else
											$align = "";

										switch ($ixt) {
											case 0:
												echo "<td $align type=\"coro\">";
												echo $fila;
												$FT[$i] = $fila;
												break;
											case 3:
												echo "<td $align type=\"ch\">";
												echo $fila;
												$IN[$i] = $fila;
												break;
											case 4:
												echo "<td $align type=\"ch\">";
												echo $fila;
												$RE[$i] = $fila;
												break;
											case 7:
												echo "<td $align type=\"coro\">";
												echo $fila;
												$IT[$i] = $fila;
												break;
											case 10:
												echo "<td $align type=\"coro\">";
												echo $fila;
												$PL[$i] = $fila;
												break;
											case 13:
												echo "<td $align type=\"link\">";
												if ($pick == "") $pick = "&nbsp;";
												echo $pick;
												if ($fila == "") $fila = "&nbsp;";
												echo "</td><td type=\"ed\">$fila";
												break;
											case 16:
												echo "<td $align type=\"ch\">";
												$HP[$i] = $fila;
												echo $fila;
												break;
											case 18:
												echo "<td $align type=\"ch\">";
												$LKF[$i] = $fila;
												break;
											case 19:
												echo "<td $align type=\"ch\">";
												$MANDATORY[$i] = $fila;
												break;
											case 20:
												echo "<td $align type=\"coro\">";
												$VAL[$i] = $fila;
												break;
											default:
												echo "<td $align type=\"edtxt\">";
												if ($fila == "") $fila = "&nbsp;";
												echo $fila;
												break;
										}
										echo "</td>";
									}
									echo " </tr>";
								}
							}
						}
						?>
					</table>
				</div>
				<?php
				// Error message if no file found. Happens only in erroneous situations but saves a lot of investigations.
				if (!isset($fp)) echo "<p style='color:red'>" . $msgstr["file"] . ": " . $archivo . " &rarr; " . $msgstr["ne"] . "</p>";
				?>
				<a class="bt bt-blue" href=javascript:Test()><?php echo $msgstr["test"] ?></a>
				<a class="bt bt-blue" href=javascript:List()><?php echo $msgstr["list"] ?></a>
				<a class="bt bt-blue" href=javascript:Validate()><?php echo $msgstr["validate"] ?></a>
				<a class="bt bt-green" href=javascript:Enviar()><?php echo $msgstr["update"] ?></a>
				<?php include("fdt_inc_grid.php") ?>
				<script>
					mygrid.enableMultiselect(true); // To move multiple lines at once. Sequence depends on selection sequence
				</script>
				<br><br>
			</div>
		</div>
	</form>
	<form name=forma1 action=fdt_update.php method=post>
		<?php if (isset($arrHttp["fmt_name"])) {
			echo "<input type=hidden name=fmt_name value=" . $arrHttp["fmt_name"] . ">\n";
		}
		if (isset($arrHttp["fmt_desc"])) echo "<input type=hidden name=fmt_desc value=\"" . $arrHttp["fmt_desc"] . "\">\n";
		if (isset($arrHttp["UNICODE"]))  echo "<input type=hidden name=UNICODE value=\"" . $arrHttp["UNICODE"] . "\">\n";
		if (isset($arrHttp["CISIS_VERSION"]))  echo "<input type=hidden name=CISIS_VERSION value=\"" . $arrHttp["CISIS_VERSION"] . "\">\n";
		?>
		<input type=hidden name=ValorCapturado>
		<input type=hidden name=desc>
		<input type=hidden name=Opcion value=<?php echo $arrHttp["Opcion"] ?>>
		<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
		<input type=hidden name=archivo value=<?php echo $xarch ?>>
		<?php
		if (isset($arrHttp["ventana"])) echo "<input type=hidden name=ventana value=" . $arrHttp["ventana"] . ">\n";
		if (isset($arrHttp["encabezado"])) echo "<input type=hidden name=encabezado value=S>\n";
		if (isset($arrHttp["Fixed_field"]))  echo "<input type=hidden name=Fixed_field value=" . $arrHttp["Fixed_field"] . ">\n";
		if (isset($arrHttp["cisis"]))  echo "<input type=hidden name=cisis value=" . $arrHttp["cisis"] . ">\n";
		if (isset($arrHttp["dcimport"]))  echo "<input type=hidden name=dcimport value=" . $arrHttp["dcimport"] . ">\n";
		?>

	</form>
	<form name="rowedit" action="fdt_rowedit.php" method="post" target="WinRow">
		<input type="hidden" name="ValorCapturado">
		<input type="hidden" name="row">
		<?php if (isset($arrHttp["UNICODE"]))  echo "<input type=hidden name=UNICODE value=\"" . $arrHttp["UNICODE"] . "\">\n"; ?>
		<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
		<input type="hidden" name="Opcion" value="<?php echo $arrHttp["Opcion"] ?>">
	</form>
	<form name="MFedit" action="fdt.php" method="post" target="WinRow">
		<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
		<?php if (isset($arrHttp["UNICODE"]))  echo "<input type=hidden name=UNICODE value=\"" . $arrHttp["UNICODE"] . "\">\n"; ?>
		<input type="hidden" name="tag">
		<input type="hidden" name="subfield">
	</form>
	<form name="edit_picklist" method="post" target="PL" action="picklist.php">
		<?php if (isset($arrHttp["UNICODE"]))  echo "<input type=hidden name=UNICODE value=\"" . $arrHttp["UNICODE"] . "\">\n"; ?>
		<input type="hidden" name="base">
		<input type="hidden" name="pl_type">
		<input type="hidden" name="picklist">
		<input type="hidden" name="row">
		<input type="hidden" name="title">
		<input type="hidden" name="tag">
		<input type="hidden" name="subfield">
	</form>
	<script>
		<?php
		$xar = explode(".", $xarch);
		if (strtoupper($xar[0]) == strtoupper($arrHttp["base"]))
			echo "FDT='S';\n";
		else
			echo "FDT='N';\n";

		// Verifica a extensão do arquivo atual (FDT ou FMT)
		if (strtoupper(end($xar)) == "FDT") {
			echo "var isFDT = true;\n";
		} else {
			echo "var isFDT = false;\n";
		}
		?>

		if (isFDT) {
			// OPÇÃO 1: Ocultar a coluna 20 completamente (Melhor UX)
			mygrid.setColumnHidden(20, true);

			/* OPÇÃO 2: Se preferir apenas travar e deixar cinza, 
			   apague a linha acima e descomente o bloco abaixo:
			   
			for (var i = 0; i < mygrid.getRowsNum(); i++) {
				mygrid.cells2(i, 20).setDisabled(true); 
				mygrid.cells2(i, 20).setBgColor("#e9ecef");
			}
			mygrid.attachEvent("onCheck", function(rId, cInd, state) {
				if (cInd == 20) { 
				    mygrid.cells(rId, cInd).setValue(0); 
				    return false; 
				}
				return true;
			});
			*/
		}
	</script>
	<?php include("../common/footer.php"); ?>
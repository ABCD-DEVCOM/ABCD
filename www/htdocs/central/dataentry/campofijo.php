<?php
/*
20250109 fho4abcd Standard helper. Add breadcrumb. Replace all buttons by new style buttons, improve error message
20260601 rogercgui Generico RDA - Universal Fixed-Field Motor (007, 008, etc.) with Repeat Support
*/
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
include("../config.php");

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");
include("../common/header.php");

// Secure Parameter Reception
$Tag = isset($arrHttp["Tag"]) ? trim($arrHttp["Tag"]) : (isset($arrHttp["tag"]) ? trim($arrHttp["tag"]) : "");
$pl_tag = str_ireplace("tag", "", $Tag);
$fixed_tab = isset($arrHttp["fixed_tab"]) ? basename(trim($arrHttp["fixed_tab"])) : "ldr_06.tab";
$categoria = isset($arrHttp["categoria"]) ? trim($arrHttp["categoria"]) : "";
$occ = isset($arrHttp["occ"]) ? intval($arrHttp["occ"]) : 0; // Ocorrência da linha

?>

<body>
	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php echo $msgstr["edit"] . " " . $msgstr["ft_mf"] . ": " . $pl_tag ?>
		</div>
		<div class="actions">
			<a class="bt bt-green" href="javascript:ActualizarForma()" title="<?php echo $msgstr["updatedeform"] ?? 'Atualizar' ?>">
				<i class="fas fa-share"></i> <?php echo $msgstr["actualizar"] ?? 'Atualizar' ?></a>
			<a class="bt bt-red" href="javascript:self.close()">
				<i class="fas fa-times"></i> <?php echo $msgstr["cancelar"] ?? 'Cancelar' ?></a>
			&nbsp;
		</div>
		<div class="spacer">&#160;</div>
	</div>
	<?php
	include "../common/inc_div-helper.php";

	// Reading the Router File (.tab)
	$tab_file_content = false;
	$path_lang = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $fixed_tab;
	$path_lang_db = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $fixed_tab;

	if (file_exists($path_lang)) {
		$tab_file_content = file($path_lang);
	} elseif (file_exists($path_lang_db)) {
		$tab_file_content = file($path_lang_db);
	}

	if (!$tab_file_content) {
		echo "<font color=red>" . $msgstr["misfile"] . ": " . $fixed_tab . "</font>";
		die;
	}

	// Mapping of Categories
	$categorias_array = array();
	$titulo = "";
	$fdt_file = "";

	foreach ($tab_file_content as $value) {
		$value = trim($value);
		if ($value != "") {
			$tr = explode('|', $value);
			if (isset($tr[0]) && isset($tr[1]) && isset($tr[2])) {
				$categorias_array[$tr[0]] = array("desc" => $tr[1], "fdt" => $tr[2]);
				if ($categoria != "" && $tr[0] == $categoria) {
					$titulo = $tr[1] . " (" . $tr[0] . ") - " . $tr[2];
					$fdt_file = $tr[2];
				}
			}
		}
	}

	// Selection Section (Triggered when the field is new and the category is unknown)
	if ($categoria == "" || $fdt_file == "") {

		if ($fixed_tab == "ldr_06.tab") {
			reset($categorias_array);
			$categoria = key($categorias_array);
			$titulo = $categorias_array[$categoria]["desc"] . " (" . $categoria . ") - " . $categorias_array[$categoria]["fdt"];
			$fdt_file = $categorias_array[$categoria]["fdt"];
		} else {
			// For 007 and 006, the popup is required by the MARC standard.
			echo "<div class='middle form'><div class='formContent'>";
			echo "<h4>" . ($msgstr["edit"] ?? "Edit") . " " . ($msgstr["ft_mf"] ?? "Fixed Field") . ": " . htmlspecialchars($pl_tag) . "</h4>";
			echo "<p style='font-size: 14px; margin-bottom: 15px;'>" . ($msgstr["ff_select_category"] ?? "Please select the material category to start editing this field:") . "</p>";

			echo "<form method='GET' action='campofijo.php'>";
			echo "<input type='hidden' name='base' value='" . htmlspecialchars($arrHttp["base"]) . "'>";
			echo "<input type='hidden' name='fixed_tab' value='" . htmlspecialchars($fixed_tab) . "'>";
			echo "<input type='hidden' name='Tag' value='" . htmlspecialchars($Tag) . "'>";
			echo "<input type='hidden' name='occ' value='" . htmlspecialchars($occ) . "'>";

			echo "<select name='categoria' required style='padding: 8px; font-size: 14px; width: 350px;'>";
			echo "<option value=''>" . ($msgstr["ff_default_category"] ?? "-- Select a Category --") . "</option>";
			foreach ($categorias_array as $cod => $dados) {
				echo "<option value='" . htmlspecialchars($cod) . "'>" . $dados["desc"] . " (" . htmlspecialchars($cod) . ")</option>";
			}
			echo "</select><br><br><br>";

			echo "<button type='submit' class='bt bt-green' style='font-size: 14px;'><i class='fas fa-arrow-right'></i> " . ($msgstr["ff_continue"] ?? "Continue to the Form") . "</button>";
			echo "</form>";
			echo "</div></div>";
			include("../common/footer.php");
			die;
		}
	}

	// 5. Loading the Target FDT Matrix
	$fp = array();
	$fdt_path = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $fdt_file;
	$fdt_path_db = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $fdt_file;

	if (file_exists($fdt_path)) {
		$fp = file($fdt_path);
	} elseif (file_exists($fdt_path_db)) {
		$fp = file($fdt_path_db);
	}

	if (empty($fp)) {
		echo "<font color=red>" . $msgstr["misfile"] . ": " . $fdt_file . "</font>";
		die;
	}

	// Generating JavaScript Arrays
	echo "\n<script>\n";
	echo "picklist=new Array();\n";
	echo "namepick=new Array();\n";
	echo "SubCampos=new Array();\n";
	$ixpos = -1;

	foreach ($fp as $value) {
		$value = rtrim($value);
		if ($value != "") {
			$t = explode('|', $value);
			$pick = trim($t[11] ?? "");
			$ixpos++;
			echo "SubCampos[" . $ixpos . "]='" . addslashes($value) . "';\n";

			if ($pick != "") {
				$name = str_replace(".", "_", $pick);
				$fpick = array();
				$pick_path = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $pick;
				$pick_path_db = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $pick;

				if (file_exists($pick_path)) {
					$fpick = file($pick_path);
				} elseif (file_exists($pick_path_db)) {
					$fpick = file($pick_path_db);
				}

				$tt = "";
				foreach ($fpick as $pl) {
					$pl = rtrim($pl);
					if ($pl != "") $tt .= $pl . "!!!!";
				}

				if ($tt != "") {
					$tt = substr($tt, 0, strlen($tt) - 4);
					$tt = str_replace('"', "&quot;", $tt);
					$tt = str_replace("'", "\\'", $tt);
				}
				echo "picklist[" . $ixpos . "]='" . $tt . "';\n";
				echo "namepick[" . $ixpos . "]='" . addslashes($pick) . "';\n";
			}
		}
	}
	echo "</script>\n";
	?>

	<script language="JavaScript" type="text/javascript" src="js/lr_trim.js"></script>
	<script language=javascript>
		mod_picklist = "<?php echo $msgstr["mod_picklist"] ?? '' ?>";
		reload_picklist = "<?php echo $msgstr["reload_picklist"] ?? '' ?>";
		var valoresCampo = new Array();
		var lista_sc = Array();

		var Tag = "<?php echo $Tag ?>";
		var occ = <?php echo $occ ?>;
		var router = "<?php echo $fixed_tab ?>";
		var Contenido = "";

		try {
			var FullContent = eval("window.opener.document.forma1." + Tag).value || "";
			// Clears hidden formatting and isolates rows to load the correct matrix
			FullContent = FullContent.replace(/\r\n/g, "\n").replace(/\r/g, "\n");
			var lines = FullContent.split("\n");
			Contenido = lines[occ] || "";
		} catch (e) {
			Contenido = "";
		}

		// Auto-inject for this Category
		if (Contenido == "" && router !== "ldr_06.tab") {
			Contenido = "<?php echo $categoria ?>";
		}

		nSC = SubCampos.length;

		function ActualizarForma() {
			numvars = SubCampos.length;
			ValorCapturado = "";
			for (i = 1; i < numvars; i++) {
				len = SubCampos[i].split("|");
				len_fix = parseInt(len[9]);
				Ctrl = eval("document.forma1.tag" + i);
				Valor = "";

				if (Ctrl) {
					switch (Ctrl.type) {
						case "radio":
						case "checkbox":
							if (Ctrl.checked) Valor = Ctrl.value;
							break;
						case "select":
						case "select-one":
						case "select-multiple":
							for (ixsel = 0; ixsel < Ctrl.length; ixsel++) {
								if (Ctrl.options[ixsel].selected) {
									if (Valor.length < len_fix) Valor += Ctrl.options[ixsel].value;
								}
							}
							break;
						default:
							Valor = Ctrl.value;
					}
				}

				ixlen = Valor.length;
				for (iXx = ixlen; iXx < len_fix; iXx++) {
					Valor += " ";
				}
				ValorCapturado += Valor;
			}

			try {
				var Ctrl_opener = eval("window.opener.document.forma1." + Tag);
				var FullContent = Ctrl_opener.value || "";

				// Clean up the clutter in Windows
				FullContent = FullContent.replace(/\r\n/g, "\n").replace(/\r/g, "\n");
				var linesArray = FullContent.split("\n");

				// Ensures that the array grows if the target row is new
				while (linesArray.length <= occ) {
					linesArray.push("");
				}

				// Insert the exact data only in the requested instance
				linesArray[occ] = ValorCapturado;

				// Returns the text with clean line breaks
				Ctrl_opener.value = linesArray.join("\n");
				self.close();
			} catch (e) {
				alert("<?php echo $msgstr['ff_error_update'] ?? 'Falha ao atualizar o campo. O ecrã principal foi fechado.' ?>");
			}
		}
	</script>

	<div class="middle form">
		<div class="formContent">
			<?php echo "<h4>" . $titulo . "</h4>" ?>
			<form name=forma1>
				<script language=javascript>
					nSC = SubCampos.length;
					ixpos = 0;
					document.writeln("<table border=0 width=100% cellspacing=0 cellpadding=0 class=listTable>");
					ncols = 3;
					for (i = 1; i < nSC; i++) {
						s = SubCampos[i].split("|");
						if (ncols > 0) {
							document.writeln("<tr onmouseover=\"this.className = 'rowOver';\" onmouseout=\"this.className = '';\">\n");
							ncols = 0;
						}
						ncols++;
						document.writeln("<td class=td><font size=2>" + s[2] + "</td>");
						document.writeln("<td>");

						if (s[11] == "") {
							document.writeln("<input type=text name=tag" + i + " id=tag" + i + " size=" + s[9] + " maxlength=" + s[9] + " value='" + Contenido.substr(ixpos, parseInt(s[9])) + "'>");
						} else {
							if (s[4] == 1) multiple = " multiple";
							else multiple = " ";
							NombreCampo = "tag" + i;
							document.writeln("<select name=" + NombreCampo + multiple + " id=" + NombreCampo + ">");
							document.writeln("<option value=''></option>");

							if (picklist[i] && picklist[i].length != 0) {
								pl = picklist[i].split('!!!!');
								optx = pl[0].split('|', 2);
								len_opt = optx[0].length;

								for (ix_o in pl) {
									opc_output = pl[ix_o].split('|', 2);
									opcion = Contenido.substr(ixpos, parseInt(s[9]));
									sel = "";

									while (opcion.length > 0) {
										opt_data = opcion.substr(0, len_opt);
										opcion = opcion.substr(len_opt);
										if (opc_output[0] == opt_data) {
											sel = " selected";
											opcion = "";
										}
									}
									document.writeln("<option value='" + opc_output[0] + "' " + sel + ">" + opc_output[1] + " (" + opc_output[0] + ")" + "</option>\n");
								}
							}
							document.writeln("</select>\n");
							picklist_name = namepick[i];

							<?php
							$base = $arrHttp["base"];
							if (isset($_SESSION["permiso"])) {
								if (isset($_SESSION["permiso"]["db_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or  isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])  or  isset($_SESSION["permiso"][$base . "_CENTRAL_ACTPICKLIST"])) {
							?>
									document.writeln(" <a class='bt-fdt' href=\"javascript:AgregarPicklist('" + picklist_name + "','" + NombreCampo + "','')\"><i class='fas fa-edit' alt='" + mod_picklist + "' title='" + mod_picklist + "' ></i></a>");
							<?php }
							} ?>
							document.writeln(" <a class='bt-fdt' href=\"javascript:RefrescarPicklist('" + picklist_name + "','" + NombreCampo + "','')\"><i class='fas fa-redo' alt='" + reload_picklist + "' title='" + reload_picklist + "' ></i></a>");
						}
						document.writeln("</td>");
						Contenido = Contenido.substr(parseInt(s[9]));
					}
					document.writeln("</table>");

					function Ayuda(tag) {
						tagx = String(tag);
						while (tagx.length < 3) tagx = "0" + tagx;
						url = "../ayudas/<?php echo $arrHttp["base"] ?>/<?php echo $_SESSION["lang"] ?>/tag_" + tagx + ".html";
						msgwin = window.open(url, "Ayuda", "status=yes,resizable=yes,toolbar=no,menu=yes,scrollbars=yes,width=600,height=400,top=100,left=100");
						msgwin.focus();
					}

					function RefrescarPicklist(tabla, Ctrl, valor) {
						document.refrescarpicklist.picklist.value = tabla;
						document.refrescarpicklist.Ctrl.value = Ctrl;
						document.refrescarpicklist.valor.value = valor;
						msgwin = window.open("", "Picklist", "width=20,height=10,scrollbars, resizable");
						document.refrescarpicklist.submit();
						msgwin.focus();
					}

					function AgregarPicklist(tabla, Ctrl, valor) {
						document.agregarpicklist.picklist.value = tabla;
						document.agregarpicklist.Ctrl.value = Ctrl;
						document.agregarpicklist.valor.value = valor;
						msgwin = window.open("", "Picklist", "width=600,height=500,scrollbars, resizable");
						document.agregarpicklist.submit();
						msgwin.focus();
					}

					ValorTabla = "";
					SelectName = "";
					ValorOpcion = "";

					function AsignarTabla() {
						opciones = ValorTabla.split('$$$$');
						var Sel = document.getElementById(SelectName);
						Sel.options.length = 0;
						var newOpt = Sel.appendChild(document.createElement('option'));
						newOpt.text = "";
						newOpt.value = " ";
						for (x in opciones) {
							op = opciones[x].split('|');
							if (op[0] == "") op[0] = op[1];
							if (op[1] == "") op[1] = op[0];
							var newOpt = Sel.appendChild(document.createElement('option'));
							newOpt.text = op[1];
							newOpt.value = op[0];
							if (op[0] == ValorOpcion) newOpt.selected = true;
						}
					}
				</script>

			</form>

			<form name=agregarpicklist action=../dbadmin/picklist_edit.php method=post target=Picklist>
				<input type=hidden name=base value="<?php echo $arrHttp["base"] ?>">
				<input type=hidden name=picklist>
				<input type=hidden name=Ctrl>
				<input type=hidden name=valor>
				<input type=hidden name=desde value=dataentry>
			</form>

			<form name=refrescarpicklist action=../dbadmin/picklist_refresh.php method=post target=Picklist>
				<input type=hidden name=base value="<?php echo $arrHttp["base"] ?>">
				<input type=hidden name=picklist>
				<input type=hidden name=Ctrl>
				<input type=hidden name=valor>
				<input type=hidden name=desde value=dataentry>
			</form>

		</div>
	</div>
	<?php include("../common/footer.php") ?>
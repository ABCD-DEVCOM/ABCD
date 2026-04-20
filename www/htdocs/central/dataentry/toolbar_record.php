<?php
/* Modifications
2021-03-02 fho4abcd Replaced helper code fragment by included file
2021-08-29 fho4abcd Removed document import (the code made a new record)
2021-08-29 fho4abcd Restored edit button. lineends
2022-01-27 fho4abcd Do not show empty buttons for calls by test scripts
2022-02-07 fho4abcd buttons for default value option+ show buttons if applicable only
2022-02-14 fh04abcd small html improvements
2023-01-16 fho4abcd hover text for checkbox
2023-02-03 fho4abcd Mark checkbox if in list of checked records, Remove wrong script
2023-02-10 fho4abcd Move div-helper and javascript inside correct if.
2025-03-05 fho4abcd Remove barcode option here. (there is no single record specific code)
2025-12-08 fho4abcd Improve html & improve indent source code
2025-12-23 fho4abcd Upgrade html code to latest standard+ improve scrolling of button bar
*/

//echo $arrHttp["ventana"];
if (!isset($fmt_test) and !isset($arrHttp["ventana"])) {
	if (isset($default_values)) {
		$ayuda = "valdef.html";
	} else {
		if (isset($arrHttp["capturar"]) and $arrHttp["capturar"] == "S") {
			$ayuda = "copy_record.html";
		} else {
			$ayuda = "dataentry.html";
		}
	}
	$wiki_help = "Entrada_de_datos";
}
// The test options do not use buttons, nor the javascripts in this file
if (isset($fmt_test)) goto LAST;

if (!isset($arrHttp["ventana"])) {

	//CHECK IF THERE IS A VALIDATION FORMAT
	$archivo = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $arrHttp["base"] . ".val";
	if (!file_exists($archivo)) $archivo = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $arrHttp["base"] . ".val";
	if (file_exists($archivo)) {
		$rec_validation = "S";
	}
	$db = $arrHttp["base"];
	if (!isset($arrHttp["encabezado"])) {
		include "../common/inc_div-helper.php";
?>
		<script>
			function scrollingDetector() {
				if (navigator.appName == "Microsoft Internet Explorer") {
					//alert(document.documentElement.scrollTop);
					document.getElementById("myDiv").style.top = document.documentElement.scrollTop;
				} else {
					// For FireFox
					document.getElementById("myDiv").style.top = window.pageYOffset + "px";
				}
			}
			// ensure that page with myDiv is loaded and set interval
			window.onload = function() {
				clearInterval(1); //brute force ensures that previous interval is switched off.
				if (document.getElementById("myDiv")) {
					interval_id = setInterval(scrollingDetector, 1000);
				} else {
					alert("Unable to find div with id='myDiv'. Buttons will not scroll :(");
				}
			}
		</script>



		<div id="myDiv" style="position:absolute; top:0px; right: 0; z-index: 99999999999;">
			<a href="javascript:top.ToggleMenuManually()" class="button_browse show" style="float: right;  border-radius: 4px; padding: 4px 10px; margin-top: 4px; text-decoration: none;">
				<i class="fas fa-chevron-up" id="menu-toggle-icon"></i> <span id="menu-toggle-text"><?php echo $msgstr["hide_top_bar"]; ?></span>
			</a>

			<table class="toolbar-edit-dataentry">
				<tr>
					<td>

						<?php
						if (isset($arrHttp["toolbar_record"]) and strtoupper($arrHttp["toolbar_record"]) == "N") {
							$_SESSION["TOOLBAR_RECORD"] = "N";
						}
						switch ($arrHttp["Opcion"]) {
							case "ver":
							case "leer":
							case "cancelar":
							case "actualizar":
							case "buscar":
							case "presentar_captura":
							case "dup_record":
								if (isset($_SESSION["TOOLBAR_RECORD"]) and $_SESSION["TOOLBAR_RECORD"] == "N") {
									unset($_SESSION["TOOL BAR_RECORD"]);
									break;
								}

								// Prevents an error when the MFN does not exist.
								$mfn_val = $arrHttp["Mfn"] ?? "";
								if ($mfn_val != "") {
									$mfn_val = 0;
								}

						?>
								<label class="check_sec">
									<input type="checkbox" name="sel_mfn" id="sel_mfn" onclick="top.SeleccionarRegistro(this)" value="<?php echo $mfn_val; ?>">
									<span class="checkmark" title='<?php echo $msgstr["selected_records_add"] ?>'></span>
								</label>
								<script>
									var selecttop = top.main.document.getElementById("sel_mfn");
									var checkvalue = top.SeleccionarRegistroCheck(<?php echo $mfn_val; ?>);
									if (checkvalue == true) {
										selecttop.setAttribute("checked", true);
									}
								</script>

						<?php
								if (isset($_SESSION["permiso"]["CENTRAL_EDREC"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_EDREC"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"])) {
									echo " &nbsp;<a href=\"javascript:top.Menu('editar')\" title=\"" . $msgstr["m_editar"] . "\"><img src='../../assets/svg/catalog/ic_fluent_document_edit_24_regular.svg' alt=\"" . $msgstr["m_editar"] . "\" style=\"border:0;\"></a>  &nbsp;\n";
								}
								if (isset($_SESSION["permiso"]["CENTRAL_CREC"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_CREC"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"])) {
									echo " &nbsp;<a href=\"javascript:top.Menu('dup_record')\" title=\"" . $msgstr["m_copyrec"] . "\"><img src='../../assets/svg/catalog/ic_fluent_save_copy_24_regular.svg' alt=\"" . $msgstr["m_copyrec"] . "\" style=\"border:0;\"></a>  &nbsp;\n";
								}
								echo " &nbsp;<a href=\"javascript:top.Menu('same')\" title=\"" . $msgstr["refresh_db"] . "\"><img src='../../assets/svg/catalog/ic_fluent_arrow_sync_circle_24_regular.svg' alt=\"" . $msgstr["refresh_record"] . "\" title=\"" . $msgstr["refresh_record"] . "\" style=\"border:0;\"></a>  &nbsp;\n";

								if (isset($_SESSION["permiso"]["CENTRAL_DELREC"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_DELREC"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]))
									echo "<a href=\"javascript:top.Menu('eliminar')\" title=\"" . $msgstr["m_eliminar"] . "\"><img src='../../assets/svg/catalog/ic_fluent_delete_dismiss_24_regular.svg' alt=\"" . $msgstr["m_eliminar"] . "\" style=\"border:0;\"></a> &nbsp;\n";
								if (isset($_SESSION["permiso"]["CENTRAL_Z3950CAT"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_Z3950CAT"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]))
									echo "<a href=\"javascript:top.Menu('edit_Z3950')\" title=\"Z39.50\"><img src='../../assets/svg/catalog/ic_fluent_arrow_download_24_regular.svg' alt=\"Z39.50\" style=\"border:0;\"></a>\n";
								if (isset($_SESSION["permiso"]["CENTRAL_EDREC"])  or isset($_SESSION["permiso"][$db . "_CENTRAL_EDREC"]) or isset($_SESSION["permiso"]["CENTRAL_CREC"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]))
									if (isset($rec_validation))
										echo "<a href='javascript:top.Menu(\"recvalidation\")' title=\"" . $msgstr["rval"] . "\"><img src='../../assets/svg/catalog/ic_fluent_calendar_checkmark_24_regular.svg' alt=\"" . $msgstr["rval"] . "\" style=\"border:0;\"></a> &nbsp;\n";
								if (isset($arrHttp["db_copies"]) and $arrHttp["db_copies"] != "") {
									if (isset($_SESSION["permiso"]["CENTRAL_ADDCOP"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_ADDCOP"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"])  or isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"])) {  //THE DATABASES HAS COPIES DATABASE
										echo "<a href='javascript:top.toolbarEnabled=\"\";top.Menu(\"addcopies\")' title='" . $msgstr["m_addcopies"] . "'><img src='../../assets/svg/catalog/ic_fluent_collections_add_24_regular.svg' alt='" . $msgstr["m_addcopies"] . "'></a> &nbsp;\n";
										echo "<a href='javascript:top.toolbarEnabled=\"\";top.Menu(\"editdelcopies\")' title='" . $msgstr["m_editdelcopies"] . "'><img src='../../assets/svg/catalog/ic_fluent_collections_24_regular.svg' alt='" . $msgstr["m_editdelcopies"] . "' ></a> &nbsp;\n";
									}
									if (isset($_SESSION["permiso"]["CENTRAL_ADDLO"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_ADDLO"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]))
										echo "<a href='javascript:top.toolbarEnabled=\"\";top.Menu(\"addloanobjects\")' title='" . $msgstr["addloansdb"] . "'><img src='../../assets/svg/catalog/ic_fluent_reading_list_add_24_regular.svg' alt='" . $msgstr["addloansdb"] . "' ></a> \n";
								}
								echo " &nbsp;";
								break;
							case "editar":
							case "capturar":
							case "crear":
							case "reintentar":
								if ($OpcionDeEntrada != "captura_bd") {
									if (isset($arrHttp["toolbar_record"]) and strtoupper($arrHttp["toolbar_record"]) == "N") {
										echo " &nbsp; <a href='javascript:top.Menu(\"cancelar\")' title=\"" . $msgstr["m_cancelar"] . "\"><img src=img/toolbarCancelEdit.png alt='" . $msgstr["m_cancelar"] . "' ></a> &nbsp; \n";
									} else {
										echo " &nbsp; <a href='javascript:top.Menu(\"cancelar\")' title=\"" . $msgstr["m_cancelar"] . "\"><img src='../../assets/svg/catalog/ic_fluent_pane_close_24_regular.svg' alt='" . $msgstr["m_cancelar"] . "'  ></a>\n";
									}
									echo "<a href='javascript:EnviarForma()' title=\"" . $msgstr["m_guardar"] . "\"><img src='../../assets/svg/catalog/ic_fluent_document_save_24_regular.svg' alt=\"" . $msgstr["m_guardar"] . "\"></a> &nbsp; \n";
									if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/tesaurus.rel"))
										echo "<a href=javascript:RelacionesInversas('check') title=\"" . $msgstr["tes_chkinvrel"] . "\"><img src=img/import.gif alt=\"" . $msgstr["tes_chkinvrel"] . "\"></a>\n";
								}
								// echo "<input type=button name=capturar value=\"".$msgstr["m_capturar"]."\">\n";
								// echo "<input type=button name=capturar value=\"".$msgstr["m_z3950"]."\">\n";
								break;
							case "valdef":
								echo " &nbsp; <a href='javascript:top.Menu(\"cancelar\")' title=\"" . $msgstr["cancel"] . "\"><img src='../../assets/svg/catalog/ic_fluent_pane_close_24_regular.svg' alt='" . $msgstr["m_cancelar"] . "' ></a>\n";
								echo " &nbsp; <a href='javascript:EnviarValoresPorDefecto()' title='" . $msgstr["valdef_save"] . "'><img src='../../assets/svg/catalog/ic_fluent_document_save_24_regular.svg' alt=\"" . $msgstr["m_guardar"] . "\"></a>\n";
								break;
						}
						?>
					</td>
				</tr>
			</table>

		</div>
	<?php
	}
}
if (isset($arrHttp["Expresion"]) and $arrHttp["Expresion"] != "") {
	//$arrHttp["Expresion"]=str_replace('"','',$arrHttp["Expresion"]);
	?>

	<a class="button_browse show" href="javascript:toggle('Expresion','')">
		<i class="fas fa-search"></i> <?php echo $msgstr["expresion"]; ?>
	</a>
	<div id="Expresion" style="display:none; padding: 10px;">
		<textarea name="nueva_b" id="nueva_b" cols="130" rows="1"><?php echo stripslashes($arrHttp["Expresion"]); ?></textarea>
		<a class="button_browse edit" href="javascript:NuevaBusqueda()"><i class="fas fa-search"></i> <?php echo $msgstr["buscar"]; ?></a>
		<a class="button_browse show" href="javascript:top.Menu('refinar')"><i class="fas fa-search-plus"></i> <?php echo $msgstr["refine"]; ?></a>

		<?php echo InsertarEnlaces($arrHttp['base']) . "\n";
		if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_SAVEXPR"])) {
		?>
			<div id="headerDiv_1">
				<div id="titleText_1" class="titleText">
					<a class="button_browse edit" id="myHeader_1" href="javascript:toggle('contentDiv_1','myHeader_1');">
						<i class="far fa-save"></i> <?php echo $msgstr["savesearch"] ?></a>
				</div>
				<div id="contentDiv_1" style="display:none;">
					<?php echo $msgstr["r_desc"] ?>:
					<input type=text name=Descripcion size=40>
					&nbsp; &nbsp;
					<input type=button value="<?php echo $msgstr["savesearch"] ?>" onclick=GuardarBusqueda()>
				</div>
			</div>
		<?php
		}
		?>
	</div>

<?php
}

// here the including script continues 
LAST:

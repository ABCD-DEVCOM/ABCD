<?php
/*
20210914 fho4abcd modified size of popup window for upload
20220116 fho4abcd modified size of popup window for capturaclaves
20220202 fho4abcd modified size of popup window for picklist
20220207 fh04abcd Improve html & backbutton 
20220214 fh04abcd Add form selectarchivo & javascript function SelectArchivo
20230119 fho4abcd Removed commented style section
20230203 fho4abcd Removed unused function SeleccionarRegistro
20230210 fho4abcd Improve text in breadcrumb
20240103 fho4abcd Add leading zero's to tag if necessary in function Ayuda
20250221 fho4abcd Check password extended with check for wrong characters (like in change_password.php)
20250805 fho4abcd Removed unused statement in function toggle
20251201 rogercgui Added quotes to HTML attributes in forms
20251209 fho4abcd Closed form forma1, removed empty source code lines
20251211 fho4abcd No close of form forma1, is extended later
20251215 fho4abcd Upgrade html code to latest standard. Removed timestamp from file references to improve caching
*/
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
?>
<!-- calendar stylesheet -->
<link rel="stylesheet" type="text/css" media="all" href="/assets/calendar/calendar-win2k-cold-1.css" title="win2k-cold-1">
<!-- main calendar program -->
<script src="/assets/calendar/calendar.js"></script>
<!-- language for the calendar -->
<script src="/assets/calendar/lang/calendar-<?php echo $_SESSION["lang"] ?>.js"></script>
<!-- the following script defines the Calendar.setup helper function, which makes
       adding a calendar a matter of 1 or 2 lines of code. -->
<script src="/assets/calendar/calendar-setup.js"></script>
<script src="../dataentry/js/campos.js"></script>
<script src="../dataentry/js/windowdhtml.js"></script>
<script src="../dataentry/js/lr_trim.js"></script>
<script src="../dataentry/js/password_check.js"></script>
<script src="../ckeditor/ckeditor.js"></script>
<script src="../ckeditor/config.js"></script>
<script src="../dataentry/js/textcounter.js"></script>

<?php
global $Expresion;
if (file_exists("../dataentry/js/" . $arrHttp["base"] . ".js"))
	echo "<script src=" . $arrHttp["base"] . ".js></script>\n";

//Treatment of the search expression
if (isset($arrHttp["Expresion"])) {
	$arrHttp["Expresion"] = stripslashes($arrHttp["Expresion"]);
	$Expresion = trim($arrHttp["Expresion"]);
	$Expresion = str_replace("  ", " ", $Expresion);
	$Expresion = str_replace("  ", " ", $Expresion);
	$Expresion = str_replace('("', "", $Expresion);
	$Expresion = str_replace('")', "", $Expresion);
	$xor = "¬or¬";
	$xand = "¬and¬";
	$Expresion = str_replace(" {", "{", $Expresion);
	$Expresion = str_replace(" or ", $xor, $Expresion);
	$Expresion = str_replace("+", $xor, $Expresion);
	$Expresion = str_replace(" and ", $xand, $Expresion);
	$Expresion = str_replace("*", $xand, $Expresion);
	$nse = -1;
	while (is_integer(strpos($Expresion, '"'))) {
		$nse = $nse + 1;
		$pos1 = strpos($Expresion, '"');
		$xpos = $pos1 + 1;
		$pos2 = strpos($Expresion, '"', $xpos);
		$subex[$nse] = trim(substr($Expresion, $xpos, $pos2 - $xpos));
		if ($pos1 == 0) {
			$Expresion = "{" . $nse . "}" . substr($Expresion, $pos2 + 1);
		} else {
			$Expresion = substr($Expresion, 0, $pos1 - 1) . "{" . $nse . "}" . substr($Expresion, $pos2 + 1);
		}
	}
	$Expresion = str_replace(" ", "*", $Expresion);
	while (is_integer(strpos($Expresion, "{"))) {
		$pos1 = strpos($Expresion, "{");
		$pos2 = strpos($Expresion, "}");
		$ix = substr($Expresion, $pos1 + 1, $pos2 - $pos1 - 1);
		if ($pos1 == 0) {
			$Expresion = $subex[$ix] . substr($Expresion, $pos2 + 1);
		} else {
			$Expresion = substr($Expresion, 0, $pos1) . " " . $subex[$ix] . " " . substr($Expresion, $pos2 + 1);
		}
	}
	$Expresion = str_replace("¬", " ", $Expresion);
	$Expresion = urlencode($Expresion);
}
?>
<script>
	<?php
	if (isset($SECURE_PASSWORD_LEVEL))
		echo "secure_password_level='$SECURE_PASSWORD_LEVEL'\n";
	if (isset($SECURE_PASSWORD_LENGTH))
		echo "secure_password_length='$SECURE_PASSWORD_LENGTH'\n";
	?>
	tesaurus = "";
	var score = 0
	tag_password = ""
	mandatory_password = ""
	var max_l = new Array()

	function switchMenu(obj, ixsec) {
		var el = document.getElementById(obj);
		if (!el) return; // Segurança caso o elemento não exista

		var isVisible = (el.style.display != 'none');

		// 1. Lógica original de Visibilidade
		if (isVisible) {
			el.style.display = 'none';
		} else {
			el.style.display = '';
		}

		// 2. NOVA Lógica de Ícone (Automática)
		// Como o HTML é: Wrapper -> [Header(Link+Icon), Content(el)]
		// Pegamos o pai do elemento (Wrapper) e buscamos o ícone dentro dele
		var parent = el.parentNode;
		if (parent) {
			// Procura o ícone dentro do cabeçalho deste bloco
			var icon = parent.querySelector('.header-fdt i');

			// Se achou o ícone, faz a troca baseada na visibilidade ANTERIOR
			if (icon) {
				if (isVisible) {
					// Estava visível, agora ficou oculto -> Mostra MAIS (+)
					icon.classList.remove('fa-minus-square');
					icon.classList.add('fa-plus-square');
				} else {
					// Estava oculto, agora ficou visível -> Mostra MENOS (-)
					icon.classList.remove('fa-plus-square');
					icon.classList.add('fa-minus-square');
				}
			}
		}
	}

	function toggle(showHideDiv, switchTextDiv) {
		var ele = document.getElementById(showHideDiv);
		/* next variable is not used (since ABCD 1.4)
		var text = document.getElementById(switchTextDiv);*/
		if (ele.style.display == "block") {
			ele.style.display = "none";
		} else {
			ele.style.display = "block";
		}
	}

	function GuardarBusqueda() {
		Descripcion = document.forma1.Descripcion.value
		if (Trim(Descripcion) == "") {
			alert("<?php echo $msgstr["errsave"] ?>")
			return
		}
		document.guardar.Descripcion.value = Descripcion
		var winl = (screen.width - 300) / 2;
		var wint = (screen.height - 200) / 2;
		document.guardar.Expresion.value = '<?php if (isset($arrHttp["Expresion"])) echo $arrHttp["Expresion"] ?>'
		msgwin = window.open("", "guardar", "menu=no,status=yes,width=300, height=200,left=" + winl + ",top=" + wint)
		msgwin.focus()
		document.guardar.submit()
	}
</script>
<script>
	Tab_val = Array()
	xEliminar = ""
	url_indice = ""
	ira = ""
	Ctrl_activo = ""
	ValorCapturado = ""
	Val_text = ""

	function DateToIso(From, To) {
		if (Trim(From) == "") {
			To.value = ""
			return
		}
		d = new Array()
		d[0] = ""
		d[1] = ""
		d[2] = ""
		if (From.indexOf('-') > 1) {
			d = From.split('-')
		} else {
			if (From.indexOf('/') > 1) {
				d = From.split('/')
			} else {
				iso = From
				return
			}
		}
		<?php echo "dateformat=\"$config_date_format\"\n" ?>
		if (dateformat == "DD/MM/YY" || dateformat == "d/m/Y") {
			iso = d[2] + d[1] + d[0]
		} else {
			iso = d[2] + d[0] + d[1]
		}
		To.value = iso
	}

	function EliminarRegistro() {
		Mfn = document.forma1.Mfn.value
		if (Mfn == "New") {
			alert("<?php echo $msgstr["confirmnew"] ?>")
			return
		}
		if (xEliminar == "") {
			alert("<?php echo $msgstr["confirmdel"] ?>")
			xEliminar = "1"
		} else {
			xEliminar = ""
			if (top.window.frames.length > 1) top.xeditar = "";
			document.forma1.Opcion.value = 'eliminar'
			document.forma1.submit()
		}
	}

	function EnviarDocumento() {
		msgwin = window.open("", "Resultado")
		a = editor_getHTML('Body')
		msgwin.focus()
		document.Enviar.documento.value = a
		document.Enviar.submit()
	}
	<?php
	echo "
function CancelarActualizacion(){
";
	if (!isset($arrHttp["encabezado"])) {
		if (!isset($arrHttp["ventana"])) {
			if (isset($arrHttp["capturar"])) {
				echo "self.document.close()
                  self.document.writeln('')
				top.xeditar=\"\"\n";
			} else {
				echo "
			top.xeditar=\"\"
			top.mfn=top.mfn-1
			top.Menu(\"proximo\")
			top.ApagarEdicion()\n";
			}
		} else {
			echo "self.close()\n";
		}
	} else {
		echo 'self.location.href="browse.php"';
	}
	echo  "}";
	?>

	function AlmacenarTerminoEnCampo() {
		alert(Ctrl_activo.name)
	}

	function AbrirVentana(Nombre) {
		msgwin = window.open("", Nombre, "width=800,height=600,scrollbars,resizable")
		msgwin.focus()
	}

	function ConfirmarAccion(url, target) {
		if (confirm('<?php echo $msgstr["seguro"] ?>?')) {
			msgwin = window.open(url, target, "width=800,height=600,scrollbars,resizable")
			msgwin.focus()
		}
	}

	function DesplegarArchivo(Tag) {
		Ctrl = eval("document.forma1." + Tag + ".value")
		img = "../../../bases/<?php echo $arrHttp["base"] ?>/" + Ctrl
		msgimg = window.open(img, "")
	}

	function ChangeSeq(ix, prefix) {
		msgwin = window.open("", "CHANGE", "width=400, height=200, scrollbars=yes")
		msgwin.document.writeln("<b><?php echo $msgstr["bd"] . "</b>: " . $arrHttp["base"] ?>")
		msgwin.document.writeln("<form name=cn method=post action=../dataentry/changeseq.php>")
		msgwin.document.writeln("<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>")
		msgwin.document.writeln("<input type=hidden name=prefix value=" + prefix + ">")
		msgwin.document.writeln("<input type=hidden name=tag value=" + ix + ">")
		msgwin.document.writeln("new control number <input type=text size=20 name=cn value=''>")
		msgwin.document.writeln("<input class='btn btn-blue' type=submit value=send>")
		msgwin.document.writeln("</form>")
		msgwin.focus()
	}

	function RelacionesInversas(Accion) {
		rel_text = "";
		j = document.forma1.elements.length - 1
		termino = ""
		for (ielem = 0; ielem <= j; ielem++) {
			campo = document.forma1.elements[ielem]
			nombre = campo.name
			id = campo.id
			if (Trim(campo.value) != "") {
				if (nombre.substr(0, 3) == "tag") {
					nombre = nombre.substr(3)
					nn = nombre.split('_')
					tag = nn[0]
					if (tag == tag_termino) {
						termino = campo.value
					} else {
						if (tag == tag_refer) {
							termino = campo.value
						} else {
							if (tag in tag_rel) {
								c = campo.value
								field = c.replace(/(?:\r\n|\r|\n)/g, '$*');
								if (rel_text == "") {
									rel_text = tag + "_" + field
								} else {
									rel_text += '$$$' + tag + "_" + field
								}
							}
						}
					}
				}
			}
		}
		if (termino == "") {
			alert("<?php $msgstr["tes_missterm"] ?>")
			return
		}
		ant_text = ""
		for (tag in val_rel_ant) {
			ant_text += '$*$' + tag + "_" + val_rel_ant[tag]
		}
		switch (Accion) {
			case "check":
				msgwin = window.open("../tesaurus/actualizar_relaciones.php?base=" + top.base + "&Opcion=check&Mfn=1&rel_text=" + rel_text + "&ant_text=" + ant_text + "&termino=" + termino, "Tesaurus", "width=500,height=400")
				break
			case "update":
				return [rel_text, ant_text, termino]
				break
		}

	}

	function FormarValorCapturado() {
		if (tesaurus == "S") {
			tes = RelacionesInversas('update')
			rel_text = tes[0]
			ant_text = tes[1]
			termino = tes[2]
			msgwin = window.open("../tesaurus/actualizar_relaciones.php?base=" + top.base + "&Opcion=update&Mfn=1&rel_text=" + rel_text + "&ant_text=" + ant_text + "&termino=" + termino, "Tesaurus", "width=500,height=400")
		}
		j = document.forma1.elements.length - 1
		ValorCapturado = ""
		VC = new Array()
		Val_text = ""
		for (i = 0; i <= j; i++) {
			campo = document.forma1.elements[i]
			nombre = campo.name
			id = campo.id
			if (campo.value != "") {
				if (nombre.substr(0, 3) == "tag") {
					if (campo.type == "text" || campo.type == "textarea") {
						Val_text += campo.value
					} else {
						switch (campo.type) {
							case "password":
								pass = Trim(document.forma1.confirm.value)
								campo.value = Trim(campo.value)
								if (campo.value == "") {
									alert("<?php echo $msgstr["falta"] . ": " . $msgstr["password"] ?>")
									return false
								}
								if (pass != campo.value) {
									alert("<?php echo $msgstr["passconfirm"] ?>")
									return false
								}
								Val_text += Trim(campo.value)
								break
							case "radio":
							case "checkbox":
								if (campo.checked) {
									if (VC[nombre] == "undefined") {
										VC[nombre] = "S";
										ValorCapturado = nombre + "_" + campo.value + "\n";
									} else {
										ValorCapturado = ValorCapturado + "\n" + nombre + "_" + campo.value
									}
									campo.checked = false
								}
								break
							case "select":
							case "select-one":
								break;
							case "select-multiple":
								Ctrl = eval("document.forma1." + nombre)
								for (ixsel = 0; ixsel < Ctrl.length; ixsel++)
									if (Ctrl.options[ixsel].selected) {
										if (VC[nombre] == "undefined") {
											VC[nombre] = "S";
											ValorCapturado = nombre + "_" + Ctrl.options[ixsel].value;
											Ctrl.options[ixsel].selected = false
										} else {
											ValorCapturado = ValorCapturado + "\n" + nombre + "_" + Ctrl.options[ixsel].value;
											Ctrl.options[ixsel].selected = false
										}
									}
								break
						}
					}
				}
				if (campo.id == "O" && campo.value == "") {
					if (Msg == "") {
						Separador = "<?php echo $msgstr["specvalue"] ?>: "
					} else {
						Separador = ", "
					}
					Msg = Msg + Separador + NomList[i]
					enviar = false
				}
			} else {
				if (nombre.substr(0, 3) == "eti") campo.value = ""
			}
		}


		document.forma1.check_select.value = ValorCapturado
		return true
	}

	function EnviarForma() {
		if (tag_password != "") {
			Ctrl = eval("document.forma1." + tag_password)
			if (Ctrl.value != document.forma1.confirm.value) {
				alert("<?php echo $msgstr["passconfirm"] ?>")
				return
			}
			res = VerificarPassword(tag_password)
			pwd = document.getElementById(tag_password);

			if (!res && (secure_password_level != "" || secure_password_length != "")) {
				if (pwd.value.trim() != "" || mandatory_password == 1) {
					<?php
					if (!isset($SECURE_PASSWORD_LEVEL) || trim($SECURE_PASSWORD_LEVEL) == "" || $SECURE_PASSWORD_LEVEL == "0") {
						$SECURE_PASSWORD_LEVEL = "1";
					}
					$msg = $msgstr["pass_error"] . " " . $msgstr["pass_format_" . $SECURE_PASSWORD_LEVEL];
					if ($SECURE_PASSWORD_LENGTH > 0) {
						$msg .= ". " . $msgstr["pass_format_1"] . " " . $SECURE_PASSWORD_LENGTH . " " . $msgstr["characters"];
					}
					?>
					alert(<?php echo json_encode($msg); ?>);
					return;
				}
			}
			var allowedchars = /^[0-9a-zA-Z\.,!@#$%^&*?_~\\\-()]+$/;
			if (!allowedchars.test(pwd.value)) {
				alert("<?php echo $msgstr["validpwdchars"] ?>")
				return
			}
		}
		if (fdt_validation == 'Y') {
			mensaje = ValidarCampos_FDT()
			if (mensaje != "") {
				alert(mensaje)
				enviar = false
				return
			}
		}
		enviar = true
		Msg = ""
		Separador = ""
		var Repetibles = new Array();
		enviar = FormarValorCapturado()
		if (enviar == false) return
		if (ValorCapturado + Val_text == "") {
			alert("<?php echo $msgstr["specvalue"] ?>")
			return
		}
		document.forma1.Opcion.value = 'actualizar'
		<?php if (!isset($arrHttp["ventana"]) and !isset($arrHttp["encabezado"])) {
			echo "if (top.window.frames.length>1){
			         top.ApagarEdicion()
			         document.forma1.db_copies.value=top.db_copies  //DETECT IF THE DATABASE MANAGE COPIES
			         top.xeditar=\"\"
		}
		";
		}
		?>
		document.forma1.action = "../dataentry/fmt.php"
		document.forma1.submit()
	}

	function EnviarValoresPorDefecto() {
		enviar = true
		Msg = ""
		Separador = ""
		var Repetibles = new Array();
		FormarValorCapturado()
		document.forma1.action = "default_update.php"
		document.forma1.submit()
	}

	function CapturarRegistro() {
		top.basecap = "<?php if (isset($arrHttp["basecap"])) echo $arrHttp["basecap"] ?>"
		top.ciparcap = "<?php if (isset($arrHttp["ciparcap"])) echo $arrHttp["ciparcap"] ?>"
		top.base = "<?php echo $arrHttp["base"] ?>"
		top.cipar = "<?php echo $arrHttp["cipar"] ?>"
		top.Mfn = "<?php if (isset($arrHttp["Mfn"])) echo $arrHttp["Mfn"] ?>"
		top.CapturarRegistro()
	}

	function LlenarTabla(Tabla, Llenar) {
		ix = Tabla.selectedIndex - 1
		TT = eval('document.forma1.tag' + Llenar)
		valores = TT.length
		for (i = 0; i < valores; i++) TT.remove(0)
		variable = eval("tab" + Llenar + "[" + ix + "]")
		for (i = 0; i < variable.length; i++) {
			TT.options[i + 1] = new Option(variable[i], variable[i])
		}
	}

	function Ayuda(tag) {
		tagx = String(tag)
		if (tagx.length < 3) tagx = "0" + tagx
		if (tagx.length < 3) tagx = "0" + tagx
		url = "<?php echo "../documentacion/ayuda_db.php?base=" . $arrHttp["base"] ?>&campo=tag_" + tagx + ".html"
		msgwin = window.open(url, "Ayuda", "status=yes,resizable=yes,toolbar=no,menu=no,scrollbars=yes,width=600,height=400,top=100,left=100")
		msgwin.focus()
	}

	function EditarArchivo(Tag, Bd, Cipar) {
		msgwin = window.open("fckeditor_edit.php?base=traduc&Tag=" + Tag + "&archivo=" + eval("document.forma1." + Tag + ".value"), "Upload", "status=yes,resizable=yes,toolbar=no,menu=no,scrollbars=yes,width=750,height=500,top=10,left=5")
		msgwin.focus()
	}

	function AbrirTesauro(Tag) {
		Url = "../tesaurus/index.php?base=<?php echo $arrHttp["base"] ?>&Tag=" + Tag
		myleft = screen.width - 450
		msgwin = window.open(Url, "Tesauro", "width=450, height=530,  scrollbars, status, resizable location=no, left=" + myleft)
		msgwin.focus()
	}

	function AbrirIndiceAlfabetico(xI, Prefijo, Subc, Separa, db, cipar, tag, postings, Repetible, Formato) {
		Ctrl_activo = xI
		if (Prefijo.indexOf("[") != -1) {
			p = Prefijo.split("[")
			xlen = p[1].length
			p[1] = p[1].substring(0, xlen - 1)
			Sub_Prefijo = eval("document.forma1.tag" + p[1])
			if (Sub_Prefijo.value == "") {
				alert("Debe especificar el contenido del campo 100")
				return
			}
			Prefijo = p[0] + Sub_Prefijo.value + "-"
		}
		baseactiva = "<?php echo $arrHttp["base"] ?>"
		lang = "<?php echo $_SESSION["lang"] ?>"
		document.forma1.Indice.value = xI
		Separa = "&delimitador=" + Separa
		Prefijo = Separa + "&tagfst=" + tag + "&prefijo=" + Prefijo
		myleft = screen.width - 800
		url_indice = "../dataentry/capturaclaves.php?opcion=autoridades&base=" + db + "&cipar=" + cipar + "&Tag=" + tag + Prefijo + "&postings=" + postings + "&lang=" + lang + "&repetible=" + Repetible + "&Formato=" + Formato + "&subcampos=" + Subc + "&baseactiva=" + baseactiva
		msgwin = window.open(url_indice, "Indice", "width=680, height=690,  scrollbars, status, resizable, location=no, left=" + myleft)
		msgwin.focus()
		return
	}

	function AbrirIndice(ira) {
		url_indice = url_indice + ira
		ancho = screen.width - 500 - 20
		msgwin = window.open(url_indice, "Indice", "status=yes,resizable=yes,toolbar=no,menu=yes,scrollbars=yes,width=500,height=600,top=20,left=" + ancho)
		msgwin.focus()
	}

	function EnviarArchivo(Tag) {
		var img_dir = (typeof top.img_dir !== 'undefined') ? top.img_dir : '';
		var url = "upload_file.php?base=<?php echo $arrHttp["base"] ?>&Tag=" + Tag + "&storein=" + img_dir;
		msgwin = window.open(url, "Upload", "status=yes,resizable=yes,toolbar=no,menu=no,scrollbars=yes,width=600,height=750,top=300,left=5");
		msgwin.focus();
	}

	function SelectArchivo(Ctrl, Forma) {
		// Busca a base e o cipar prioritariamente da memória global (top)
		var baseVal = (typeof top.base !== 'undefined' && top.base !== "") ? top.base : (document.forma1 && document.forma1.base ? document.forma1.base.value : "");
		var ciparVal = (typeof top.cipar !== 'undefined' && top.cipar !== "") ? top.cipar : (document.forma1 && document.forma1.cipar ? document.forma1.cipar.value : "");

		// Monta a URL completa via GET
		var url = "dirs_explorer.php?Opcion=seleccionar" +
			"&base=" + encodeURIComponent(baseVal) +
			"&cipar=" + encodeURIComponent(ciparVal) +
			"&tag=" + encodeURIComponent(Ctrl) +
			"&targetForm=" + encodeURIComponent(Forma) +
			"&desde=dataentry";

		// Abre a janela já engatilhada com a URL certa
		var msgwin = window.open(url, "explorador", "width=800,height=600,scrollbars,resizable");
		msgwin.focus();
	}

	function NuevaBusqueda() {
		if (Trim(document.forma1.nueva_b.value) != "") {
			str = document.forma1.nueva_b.value;
			var res = str.replace(/"/g, "")
			top.Expresion = str
			top.Menu("ejecutarbusqueda")
		}

	}

	function CopiarHtml(Tag, Tipo, Mfn) {
		// tipo B=External HTM
		msgwin = window.open("", "Upload", "status=yes,resizable=yes,toolbar=no,menu=no,scrollbars=yes,width=750,height=180,top=100,left=5");
		msgwin.document.close();
		msgwin.document.writeln("<html><title><?php echo $msgstr["uploadfile"] ?></title><body link=black vlink=black bgcolor=white>\n");
		msgwin.document.writeln("<form name=upload action=upload_html.php method=POST enctype=multipart/form-data>\n");
		msgwin.document.writeln("<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>\n");
		msgwin.document.writeln("<input type=hidden name=Tag value=" + Tag + ">")
		msgwin.document.writeln("<input type=hidden name=Tipo value=" + Tipo + ">")
		msgwin.document.writeln("<input type=hidden name=Mfn value=" + Mfn + ">")
		msgwin.document.writeln("<br>");
		msgwin.document.writeln("<table style='width:100%'>");
		msgwin.document.writeln("<tr><td class=menusec1><?php echo $msgstr["archivo"] ?></td>\n");
		msgwin.document.writeln("<tr><td><input name=userfile[] type=file size=50></td></td>\n");
		msgwin.document.writeln("</table>\n");
		msgwin.document.writeln("  <input type=submit value='<?php echo $msgstr["uploadfile"] ?>'>\n");
		msgwin.document.writeln("</form>\n");
		msgwin.document.writeln("</body>\n");
		msgwin.document.writeln("</html>\n");
		msgwin.focus();
	}

	function Undelete(Mfn) {
		top.Menu("editar")
	}

	function SetFckeditor(Ctrl, html) {
		var oEditor = FCKeditorAPI.GetInstance(Ctrl);
		// Check the active editing mode.
		if (oEditor.EditMode == FCK_EDITMODE_WYSIWYG) {
			// Insert the desired HTML.
			oEditor.InsertHtml(html);
		} else
			alert('You must be on WYSIWYG mode!');
	}
</script>
<script>
	tagOrg = ""
	subcOrg = ""

	function RowClean(linea, subC) {
		var t = linea.split("_");
		var tag = t[0];
		var row = t[1];

		// Encontra a primeira caixa de texto desta linha para servir de âncora
		var firstInputName = "tag" + tag + "_" + row;
		if (subC && subC.length > 0) {
			firstInputName += "_" + subC.substr(0, 1);
		}

		var inputs = document.getElementsByName(firstInputName);
		var input = inputs.length > 0 ? inputs[0] : document.getElementById(firstInputName);

		if (input) {
			var tr = input.closest('tr');
			if (tr) {
				var tbody = tr.closest('tbody');
				// Se a tabela tiver mais que uma linha, apagamos a linha!
				if (tbody.getElementsByTagName('tr').length > 1) {
					tr.parentNode.removeChild(tr);
				} else {
					// Se for a última linha, apenas a esvaziamos
					var allInputs = tr.querySelectorAll('input[type="text"], input[type="hidden"], input[type="number"], textarea');
					for (var f = 0; f < allInputs.length; f++) {
						allInputs[f].value = '';
					}
					var selects = tr.querySelectorAll('select');
					for (var s = 0; s < selects.length; s++) {
						selects[s].selectedIndex = 0;
					}
					var checks = tr.querySelectorAll('input[type="checkbox"], input[type="radio"]');
					for (var c = 0; c < checks.length; c++) {
						checks[c].checked = false;
					}
				}
			}
		}
	}

	function addRow(tableID, subC, accion, valdef) {
		var vd = valdef.split("$$$");
		var va_def = new Array();
		for (var xvd in vd) {
			var ll0 = vd[xvd];
			if (Trim(ll0) != "") {
				var vd1 = ll0.split("|");
				va_def[vd1[0]] = vd1[1];
			}
		}

		var tag = tableID;
		var table = document.getElementById("id_" + tag);
		var tbody = table.getElementsByTagName('tbody')[0];
		if (!tbody) tbody = table;

		var rows = tbody.getElementsByTagName('tr');
		var lastRow = rows[rows.length - 1];

		// Descobre o índice da última linha dinamicamente
		var lastIndex = -1;
		var inputs = lastRow.querySelectorAll('input, select, textarea');
		for (var k = 0; k < inputs.length; k++) {
			var name = inputs[k].name;
			if (name && name.indexOf('tag' + tag + '_') !== -1) {
				var parts = name.split('_');
				lastIndex = parseInt(parts[1]);
				break;
			}
		}
		if (lastIndex === -1) lastIndex = rows.length - 1;
		var nextIndex = lastIndex + 1;

		// Clona a linha inteira
		var newRow = lastRow.cloneNode(true);
		newRow.style.background = (nextIndex % 2 == 0) ? '#ffffff' : '#f9f9f9'; // Zebra striping

		// Atualiza os Nomes e IDs na nova linha
		var newInputs = newRow.querySelectorAll('input, select, textarea');
		for (var i = 0; i < newInputs.length; i++) {
			var el = newInputs[i];
			var oldName = el.name;
			if (oldName) el.name = oldName.replace(tag + "_" + lastIndex, tag + "_" + nextIndex);
			var oldId = el.id;
			if (oldId) el.id = oldId.replace(tag + "_" + lastIndex, tag + "_" + nextIndex);

			var onkeyup = el.getAttribute('onkeyup');
			if (onkeyup) el.setAttribute('onkeyup', onkeyup.replace(tag + "_" + lastIndex, tag + "_" + nextIndex));

			var onkeydown = el.getAttribute('onkeydown');
			if (onkeydown) el.setAttribute('onkeydown', onkeydown.replace(tag + "_" + lastIndex, tag + "_" + nextIndex));

			// Se a ação for "add", esvazia os valores que foram clonados
			if (accion == "add") {
				if (el.tagName === 'INPUT' && (el.type === 'text' || el.type === 'hidden' || el.type === 'number')) {
					var isMatched = false;
					if (subC && subC.length > 0) {
						for (var s = 0; s < subC.length; s++) {
							var lsc = subC.substr(s, 1);
							if (el.name && el.name.indexOf("_" + lsc) !== -1) {
								el.value = (va_def[lsc] !== undefined) ? va_def[lsc] : "";
								isMatched = true;
								break;
							}
						}
					}
					if (!isMatched) el.value = "";
				} else if (el.tagName === 'TEXTAREA') {
					el.value = "";
				} else if (el.tagName === 'SELECT') {
					el.selectedIndex = 0;
				} else if (el.tagName === 'INPUT' && (el.type === 'checkbox' || el.type === 'radio')) {
					el.checked = false;
				}
			}
		}

		// Atualiza o botão da lixeira na nova linha para apontar para o novo índice
		var links = newRow.querySelectorAll('a');
		for (var i = 0; i < links.length; i++) {
			var href = links[i].getAttribute('href');
			if (href && href.indexOf('RowClean') !== -1) {
				links[i].setAttribute('href', href.replace(tag + "_" + lastIndex, tag + "_" + nextIndex));
			}
		}

		tbody.appendChild(newRow);
	}

	function deleteRow(tableID) {
		try {
			var table = document.getElementById(tableID);
			var rowCount = table.rows.length;
			for (var i = 0; i < rowCount; i++) {
				var row = table.rows[i];
				var chkbox = row.cells[0].childNodes[0];
				if (null != chkbox && true == chkbox.checked) {
					if (rowCount <= 1) {
						alert("Cannot delete all the rows.");
						break;
					}
					table.deleteRow(i);
					rowCount--;
					i--;
				}
			}
		} catch (e) {
			alert(e);
		}
	}

	function AgregarOperador(tag) {
		Ctrl = eval("document.forma1." + tag)
		Ctrl.value = '<?php echo $_SESSION['login'] ?>'
	}

	function AgregarFecha(tag) {
		Ctrl = eval("document.forma1." + tag)
		Ctrl.value = '<?php echo date('Ymd') ?>'
	}

	function CalendarSetup(i_inputField, i_ifFormat, i_button, i_align, i_singleClick) {
		Calendar.setup({
			inputField: i_inputField, // id of the input field
			ifFormat: i_ifFormat,
			button: i_button, // trigger for the calendar (button ID)
			align: i_align, // alignment (defaults to \"Bl\")
			singleClick: true
		});
	}
</script>

<script>
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
		msgwin = window.open("", "Picklist", "width=850,height=600,scrollbars, resizable")
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

	//MANEJO DEL COMBOBOX
	// This is the function that refreshes the list after a keypress.
	// The maximum number to show can be limited to improve performance with
	// huge lists (1000s of entries).
	// The function clears the list, and then does a linear search through the
	// globally defined array and adds the matches back to the list.

	function handleKeyUp(maxNumToShow, SelectName, InputName, FunctionList) {
		var selectObj, textObj, functionListLength;
		var i, searchPattern, numShown;
		// Set references to the form elements
		selectObj = SelectName;
		textObj = InputName;
		// Remember the function list length for loop speedup
		functionListLength = FunctionList.length;
		searchPattern = textObj.value;
		// Create a regulare expression
		re = new RegExp(searchPattern, "gi");
		// Clear the options list
		selectObj.length = 0;

		// Loop through the array and re-add matching options
		numShown = 0;
		for (i = 0; i < functionListLength; i++) {
			if (FunctionList[i].search(re) != -1) {
				selectObj[numShown] = new Option(FunctionList[i], "");
				numShown++;
			}
			// Stop when the number to show is reached
			if (numShown == maxNumToShow) {
				break;
			}
		}
		// When options list whittled to one, select that entry
		if (selectObj.length == 1) {
			selectObj.options[0].selected = true;
		}
	}

	function handleSelectClick(SelectName, InputName, Repetible) {
		ix = SelectName.selectedIndex
		switch (Repetible) {
			case 0:
				InputName.value = SelectName.options[ix].text
				break;
			case 1:
				termino = SelectName.options[ix].text + '\n'
				if (InputName.value.indexOf(termino) != -1) {
					return
				}
				InputName.value += SelectName.options[ix].text + '\n'
		}

	}

	function DeleteText(SelectName, tag) {
		var originalText = document.getElementById(tag).value;
		ix = SelectName.selectedIndex
		var selectedText = SelectName.options[ix].text;
		newText = originalText.replace(selectedText + '\n', "")
		document.getElementById(tag).value = newText
	}

	function Limpiar(Ctrl) {
		Ctrl.value = ""
	}


	//REVISA POR CLAVES DUPLICADAS ANTES DE ENVIAR LA FORMA. NO SE APLICA EN DATAENTRY
	function CheckInventory(tag, prefix) {}
</script>

<?php
if (isset($arrHttp["encabezado"])) {
	//IF A RECORD IS BEING CREATED FROM THE BROWSE.PHP SCRIPT   
	if (isset($arrHttp["Opcion"]) && $arrHttp["Opcion"] == "ver") {
		include("../common/institutional_info.php");
?>
		<div class="sectionInfo">
			<div class="breadcrumb">
				<?php echo $msgstr["update_rec"] ?>
			</div>
			<div class="actions">
				<?php
				$backtoscript = $retorno . $return;
				include "../common/inc_back.php";
				?>
			</div>
			<div class="spacer">&#160;</div>
		</div>
		<?php
	} else {
		if (!isset($fmt_test)) {
			include("../common/institutional_info.php");
		?>
			<div class="sectionInfo">
				<div class="breadcrumb">
					<?php
					if ($arrHttp["Mfn"] == "New") {
						echo $msgstr["newrecindb"] . " " . $arrHttp["base"];
					} else {
						echo $msgstr["updaterecindb"] . " " . $arrHttp["base"];
					}
					?>
				</div>
				<div class="actions">
					<?php
					$backtoscript = $retorno . $return;
					$savescript = "javascript:EnviarForma()";
					include "../common/inc_cancel.php";
					include "../common/inc_save.php";
					?>
				</div>
				<div class="spacer">&#160;</div>
			</div>
<?php
		}
	}
}

?>
<form name="enviararchivo" action="upload_file.php" method="post" target="Upload">
	<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
	<input type="hidden" name="Tag">
	<input type="hidden" name="storein">
</form>


<form name=agregarpicklist action="../dbadmin/picklist_edit.php" method="post" target="Picklist">
	<input type="hidden" name="base" value=<?php echo $arrHttp["base"] ?>>
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

<form name="guardar" action="busqueda_guardar.php" method="post" target="guardar">
	<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
	<input type="hidden" name="Expresion" value="<?php echo $Expresion; ?>">
	<input type="hidden" name="Descripcion" value="">
</form>

<form name="campos" method="post" action="campos.php" target="EditC">
	<input type="hidden" name="tag">
	<input type="hidden" name="base">
	<input type="hidden" name="is_marc">
	<input type="hidden" name="SubC">
</form>

<form method=post name=forma1 action=fmt.php onSubmit="javascript:return false" id=forma1>
	<input type="hidden" name="db_copies">
	<input type="hidden" name="IsisScript" value="ingreso.xis">
	<?php if (isset($arrHttp["toolbar_record"]))
		echo "<input type=hidden name=toolbar_record value=" . $arrHttp["toolbar_record"] . ">\n"; ?>
	<?php if (isset($arrHttp["footer"]))
		echo "<input type=hidden name=footer value=" . $arrHttp["footer"] . ">\n"; ?>
	<input type="hidden" name=base value=<?php echo $arrHttp["base"] ?>>
	<input type="hidden" name=cipar value=<?php echo $arrHttp["cipar"] ?>>
	<input type="hidden" name=wks value="<?php if (isset($arrHttp["wks"]))  echo $arrHttp["wks"] ?>">
	<?php
	if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"] = "";
	if ($arrHttp["Opcion"] == "capturar" || $arrHttp["Opcion"] == "nuevo") {
		$a = "crear";
	} else {
		$a = $arrHttp["Opcion"];
	}
	echo "<input type=hidden name=Opcion value=\"$a\">\n";
	$pft_a = "";
	if (isset($arrHttp["encabezado"])) {
		echo "<input type=hidden name=encabezado value=s>\n";
		$arrHttp["Formato"] = $arrHttp["base"];
	}
	if (isset($arrHttp["from"]))
		echo "<input type=hidden name=from value=" . $arrHttp["from"] . ">\n";
	?>
	<input type="hidden" name="ValorCapturado" value="">
	<input type="hidden" name="check_select" value="">
	<input type="hidden" name="Indice" value="">
	<input type="hidden" name="Mfn" value="<?php if (isset($arrHttp["Mfn"])) echo $arrHttp["Mfn"] ?>">
	<input type="hidden" name="ver" value="S">
	<input type="hidden" name="ventana" value="<?php if (isset($arrHttp["ventana"])) echo $arrHttp["ventana"] ?>">
	<?php
	if (isset($arrHttp["retorno"])) {
		echo "<input type=hidden name=retorno value=" . $arrHttp["retorno"] . ">\n";
	}
	if (isset($arrHttp["return"])) {
		echo "<input type=hidden name=return value=" . $arrHttp["return"] . ">\n";
	}
	if (isset($arrHttp["status"])) {
		echo "<input type=hidden name=return value=" . $arrHttp["status"] . ">\n";
	}
	?>
	<input type="hidden" name="valor" value="">
	<input type="hidden" name="occur" value="">
	<input type="hidden" name="conte" value="">
	<input type="hidden" name="NoVar" value="">
	<input type="hidden" name="SubC" value="">
	<input type="hidden" name="ep" value="">
	<input type="hidden" name="Occ" value="">
	<input type="hidden" name="NombreC" value="">
	<input type="hidden" name="TagActivo" value="">
	<input type="hidden" name="Repetible" value="">
	<input type="hidden" name="Formato_ex" value="">
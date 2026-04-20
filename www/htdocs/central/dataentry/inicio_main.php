<?php
/* Modifications
2021-03-03 fho4abcd Replaced header code by standard include
2021-04-21 fho4abcd Do not crash if emergency user logs in: allows update of databases by emergency user
2021-06-10 fho4abcd Remove undesired login code
2021-06-14 fho4abcd Remove unused function LeerRegistro + lineends
2021-08-29 fho4abcd Replaced document import
2021-12-09 fho4abcd Improved sizeof popup for alfa
2021-12-12 fho4abcd Improved sizeof popup for alfa (for breadcrumb)
2022-03-20 fho4abcd Cleanup barcode, new target bcl_labelshow.php
2022-06-19 fho4abcd Corrected html + removed unreachable js code + removed unreachable frameset
2023-01-22 fho4abcd Improved height of iframe main. Moved css of iframes to css file
2023-02-03 fho4abcd Make selected records work, sort selected records. Enter record number works now also for search and selected records
2024-01-29 fho4abcd Correct typo in iframe
2024-04-03 fho4abcd Larger window for thesaurus
2024-05-03 fho4abcd Less options for freesearch
2025-03-05 fho4abcd Remove unused barcode_this option (now in in Update database definitions)
2025-12-23 fho4abcd Upgrade html code to latest standard+ indented code
*/
//error_reporting(E_ALL);
session_start();
unset($_SESSION["TOOLBAR_RECORD"]);
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
global $Permiso;
$valortag = array();
$arrHttp = array();
global $arrHttp, $xFormato, $valortag, $nombre;
include("../common/get_post.php");
require_once("../config.php");

$SW = 1366;
$Menu_H = 85;
if (isset($_SESSION["screen_width"])) $arrHttp["screen_width"] = $_SESSION["screen_width"];
if (isset($arrHttp["screen_width"])) {
	$_SESSION["screen_width"] = $arrHttp["screen_width"];
	$SW = $arrHttp["screen_width"];
}



if (!isset($_SESSION["lang"])) {
	$_SESSION["lang"] = $lang;
}
include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/lang.php");

if (isset($arrHttp["newindow"]))
	$_SESSION["newindow"] = "Y";


/////   INICIO DEL PROGRAMA ////////
$query = "";
//foreach ($arrHttp as $var => $value) 	echo "$var = $value<br>";

if (isset($arrHttp["base"])) {
	$bdsel = trim($arrHttp["base"]);
	$base_x = explode('|', $bdsel);
	$db_copies = "";
	if (isset($base_x[3]) and $base_x[3] == "Y") $db_copies = "Y";
	if (isset($base_x[0])) $bd = $base_x[0];
	if (isset($base_x[1])) $bdright = $base_x[1];
	if (isset($base_x[2])) $bddesc = $base_x[2];
}
if (isset($arrHttp["lang"])) {
	$_SESSION["lang"] = $arrHttp["lang"];
} else {
	$arrHttp["lang"] = $_SESSION['lang'];
}
include("../lang/admin.php");
if (!isset($_SESSION["Expresion"])) $_SESSION["Expresion"] = "";
$Permiso = $_SESSION["permiso"];
if (!isset($_SESSION["permiso"])) {
	echo "Missing user rights";
	session_destroy();
	die;
}
foreach ($Permiso as $key => $value) {
	if (substr($key, 0, 3) == "db_") {
		$bases = substr($key, 3);
		break;
	}
}
$arrHttp["base"] = $bases;
if (!isset($bdright)) $bdright = "";
if (!isset($db_copies)) $db_copies = "";
if (!isset($bddesc)) $bddesc = "";
if (!isset($bd)) $bd = "";
include "../common/header.php";
?>
<!--effect of overflow here is 'no overall scrollbar', only for main area-->

<body style="overflow:hidden">
	<script src="js/lr_trim.js"></script>

	<script>
		self.resizeTo(screen.availWidth, screen.availHeight)
		self.moveTo(0, 0)
		self.focus();

		<?php
		if (isset($_SESSION["newindow"])) {
			echo "var neww=\"&newindow=y\"\n";
		} else {
			echo "var neww=\"\"\n";
		}
		?>
		var listabases = Array()
		var lock_db = ""
		var browseby = "mfn"
		var Expresion = ""
		var Expre_b = ""
		var typeofrecord = ""
		var mfn = 0
		var maxmfn = 0
		var chk_mfn = 0
		var Mfn_Search = 0
		var Max_Search = 0
		var Search_pos = 0
		var Listar_pos = -1
		var db_permiso = "<?php echo $bdright; ?>"
		var db_copies = "<?php echo $db_copies; ?>" // to check if the database uses the copies database
		var NombreBase = "<?php echo $bddesc; ?>"
		var ix_basesel = 0
		var ix_langsel = 0
		var Marc = ""
		var base = "<?php echo $bd; ?>"
		var cipar = "<?php echo $bd; ?>.par"
		var Formato = "ALL"
		var tl = ""
		var nr = ""
		var xeliminar = 0
		var xeditar = ""
		var ModuloActivo = "catalog"
		var CG_actual = ""
		var CG_nuevo = ""
		var prefijo_indice = ""
		var formato_indice = ""
		ValorCapturado = ""
		var HTML = "" //FIELD TAG FOR LOADING THE FULL TEXT
		var URL = "" //FIELD TAG FOR STORING THE URL OF THE DOCUMENT STORED IN HTML
		var NombreBaseCopiarde = ""
		var RegistrosSeleccionados = "" //para mostrar los registros seleccionados
		var RegSel_pos = 0
		var rs = new Array()
		var rs_length = 0
		var wks = ""
		buscar = ""
		refinar = ""
		lang = "<?php echo $_SESSION["lang"]; ?>"
		img_dir = "";
		ep = ""
		ConFormato = true
		Capturando = ""
		toolbarEnabled = "" //enable/disable the toolbar

		function AbrirVentanaAyuda() {
			insWindow = window.open('../documentacion/ayuda.php?help=' + lang + '/dataentry_toolbar.html', 'Ayuda', 'location=no,width=700,height=550,scrollbars=yes,top=10,left=100,resizable');
			insWindow.focus()
		}

		function Mail() {
			top.main.location.href = "../mail/index.php?base=" + base;

		}

		function SearchHistory() {
			top.main.location.href = "search_history.php?base=" + base;
		}

		function ApagarEdicion() {
			manualMenuOverride = null; // Reseta o clique manual
			RecalculateLayout();
		}

		function PrenderEdicion() {
			manualMenuOverride = null; // Reseta o clique manual
			RecalculateLayout();
		}

		function TipoDeRegistro() {
			top.main.location.href = "typeofrecs.php?base=" + base
			return
		}

		function AddCopies() {
			if (db_copies == "Y")
				urlcopies = "&db_copies=Y"
			else
				urlcopies = ""
			if (browseby == "search")
				Mfn_copy = Mfn_Search
			else
				Mfn_copy = mfn
			url = '../copies/copies_add.php?base=' + base + '&Mfn=' + Mfn_copy + '&Formato=' + Formato + urlcopies
			<?php
			//READ THE TYPES OF ACQUISITIONS
			$file = $db_path . "copies/def/" . $_SESSION["lang"] . "/acquiredby.tab";
			if (!file_exists($file)) $file = $db_path . "copies/def/" . $lang_db . "/acquiredby.tab";
			if (file_exists($file)) $tacq = file($file);
			?>
			parent.main.document.writeln("<html>")
			parent.main.document.writeln("<body>")
			parent.main.document.writeln("<center><br><br>")
			parent.main.document.writeln("<h4><?php $msgstr["typeofr"] ?></h4><table>\n")
			<?php
			if (isset($tacq)) {
				foreach ($tacq as $value) {
					$value = trim($value);
					$t = explode('|', $value);
					echo "parent.main.document.writeln('<tr><td><a href=\"'+url+'&wks=" . $t[0] . "\">" . $t[1] . "</a></td>')\n";
				}
			}
			?>
			parent.main.document.writeln("</table></body></html>")
			parent.main.document.close()

		}

		function Tesaurus() {
			left = screen.width - 450
			msgwintesau = window.open("../tesaurus/index.php?base=" + base, "tesaurus", "width=450,height=650, top=0,left=" + left + " menubar=yes, scrollbars=yes, resizable=yes")
			msgwintesau.document.close()
			msgwintesau.focus()
		}

		function ValidarIrA() {
			xmfn = top.menu.document.forma1.ir_a.value

			var strValidChars = "0123456789";
			if (xmfn.length == 0 || xmfn == 0) {
				alert("<?php echo $msgstr["especificarnr"] ?>")
				return false
			}
			blnResult = true
			//  test strString consists of valid characters listed above
			for (i = 0; i < xmfn.length; i++) {
				strChar = xmfn.charAt(i);
				if (strValidChars.indexOf(strChar) == -1) {
					blnResult = false;
				}
			}
			if (blnResult == false) {
				alert("<?php echo $msgstr["especificarvaln"] ?>")
				return false
			}
			if (browseby == "search") {
				if (xmfn > Max_Search) {
					alert("<?php echo $msgstr["numfr"] ?>" + " (" + xmfn + ">" + Max_Search + ")")
					return false
				}
			} else if (browseby == "selected_records") {
				RegSel = RegistrosSeleccionados.replace(/__/g, "_")
				if (RegSel.substr(0, 1) == "_") RegSel = RegSel.substr(1)
				SelLen = RegSel.length
				if (RegSel.substr(SelLen - 1, 1) == "_") RegSel = RegSel.substr(0, SelLen - 1)
				ss = RegSel.split("_")
				if (xmfn > ss.length) {
					alert("<?php echo $msgstr["numfr"] ?>" + " (" + xmfn + ">" + ss.length + ")")
					return false
				}
			} else {
				if (xmfn > maxmfn) {
					alert("<?php echo $msgstr["numfr"] ?>" + " (" + xmfn + ">" + maxmfn + ")")
					return false
				}
			}
			return xmfn
		}

		function SeleccionarRegistro(Ctrl) {
			chk_mfn = Ctrl.value
			select_Mfn = '_' + chk_mfn + '_'
			if (Ctrl.checked) {
				if (RegistrosSeleccionados.indexOf(select_Mfn) == -1)
					RegistrosSeleccionados += select_Mfn
			} else {
				RegistrosSeleccionados = RegistrosSeleccionados.replace(select_Mfn, "")
			}
			RegSel = RegistrosSeleccionados.replace(/__/g, "_")
			if (RegSel.substr(0, 1) == "_") RegSel = RegSel.substr(1)
			SelLen = RegSel.length
			if (RegSel.substr(SelLen - 1, 1) == "_") RegSel = RegSel.substr(0, SelLen - 1)
			ss = RegSel.split("_")
			ss.sort();
			RegistrosSeleccionados = "";
			for (let i = 0; i < ss.length; i++) {
				RegistrosSeleccionados += "_" + ss[i] + "_";
			}
		}

		function SeleccionarRegistroCheck(value) {
			chk_mfn = value
			select_Mfn = '_' + chk_mfn + '_'
			if (RegistrosSeleccionados.indexOf(select_Mfn) == -1) {
				return false;
			}
			return true;
		}


		function Menu(Opcion) {
			if (toolbarEnabled == "N") {
				alert("<?php echo $msgstr["cancelcopy"] ?>")
				return
			}
			if (db_copies == "Y")
				urlcopies = "&db_copies=Y"
			else
				urlcopies = ""
			if (lock_db == "Y") return
			switch (Opcion) {
				case "cancelar":
				case "actualizar":
					ApagarEdicion()
					break;
				case "editar":
					break;
			}

			if (Opcion != "eliminar") xeliminar = 0
			if (base == "") {
				alert("<?php echo $msgstr["seldb"] ?>")
				return
			}
			Capturando = ''
			ix = top.menu.document.forma1.formato.selectedIndex
			if (ix == -1) {
				ix = 0
			} else {
				Formato = top.menu.document.forma1.formato.options[ix].value
			}
			FormatoActual = "&Formato=" + Formato + "&Diferido=N"
			if (xeditar == "S" && Opcion != "cancelar" && Opcion != "eliminar" && Opcion != "z3950") {
				alert("<?php echo $msgstr["aoc"] ?>")
				return
			}
			if (Opcion == "tabla" || Opcion == "ira") {
				xmfn = top.menu.document.forma1.ir_a.value
				if (xmfn == "") {
					top.menu.document.forma1.ir_a.value = 1
				} else {
					t = xmfn.split("/")
					top.menu.document.forma1.ir_a.value = t[0]
				}
			}
			works = ""
			if (wks != "") works = "&wks=" + wks

			if (Opcion != "actualizar" && Opcion != "editar" && Opcion != "eliminar" && Opcion != "z3950") xeditar = ""

			if (Opcion != "eliminar") xeliminar = 0
			switch (Opcion) {

				case "importarDoc":
					Mfn = "New"
					top.main.location.href = "../utilities/docfiles_upload.php?base=" + base + "&Mfn=" + Mfn
					break
				case "edit_Z3950":
					Desplegar = "N"
					xError = "S"
					if (browseby == "search")
						Mfn_p = Mfn_Search
					else
						Mfn_p = mfn
					top.main.location.href = "z3950.php?Mfn=" + Mfn_p + "&Opcion=edit&base=" + base + "&cipar=" + cipar + FormatoActual
					break
				case "addloanobjects":
					if (browseby == "search")
						Mfn_copy = Mfn_Search
					else
						Mfn_copy = mfn
					top.main.location.href = "../copies/loan_objects_add.php?base=" + base + "&Mfn=" + Mfn_copy
					return
				case "addcopies": // add copies to the inventory database
					if (browseby == "search")
						Mfn_copy = Mfn_Search
					else
						Mfn_copy = mfn
					top.main.location.href = "../copies/copies_add.php?base=" + base + "&Mfn=" + Mfn_copy + "&Formato=" + Formato + urlcopies
					return
				case "editdelcopies": //edit/delete copies from the inventory database
					if (browseby == "search")
						Mfn_copy = Mfn_Search
					else
						Mfn_copy = mfn
					top.main.location.href = "../copies/copies_edit.php?base=" + base + "&Mfn=" + Mfn_copy + "&Formato=" + Formato + urlcopies
					return
				case 'home':
					if (base != "") url = "&base=" + base
					top.location.href = "../common/inicio.php?reinicio=s" + url + neww;
					break
				case 'stats':
					top.main.location.href = "../statistics/tables_generate.php?base=" + base + "&cipar=" + base + ".par"
					break
				case "editdv":
					top.main.location.href = "default_edit.php?Opcion=valdef&ver=N&Mfn=0&base=" + top.base
					top.xeditar = "valdef"
					break
				case "deletedv":
					top.main.location.href = "default_delete.php?Opcion=valdef&ver=N&Mfn=0&base=" + top.base
					break
				case "recvalidation":
					if (mfn == 0 && Mfn_Search == 0) {
						alert("<?php echo $msgstr["selmod"] ?>")
						return
					}
					if (browseby == "search")
						mfn_edit = Mfn_Search
					else
						mfn_edit = mfn
					url = "recval_display.php?&base=" + base + "&cipar=" + cipar + "&Mfn=" + mfn_edit
					recvalwin = window.open(url, "recval", "width=550,height=300,resizable,scrollbars")
					recvalwin.focus()
					break;
				case "ejecutarbusqueda":
					Mfn_Search = 1
					mfn = 1
					//Expresion='"'+Expresion+'"'
					top.main.document.location = "../dataentry/fmt.php?Opcion=buscar&Expresion=" + Expresion + "&base=" + base + "&cipar=" + cipar + "&from=1&ver=N" + FormatoActual + works + urlcopies
					break;
				case "busquedalibre":
					if (RegistrosSeleccionados != "")
						seleccion = "&seleccionados=" + RegistrosSeleccionados
					else
						seleccion = ""
					top.main.document.location = "freesearch.php?&base=" + base + "&cipar=" + cipar + seleccion
					break;
				case "administrar":
					if (RegistrosSeleccionados != "")
						seleccion = "&seleccionados=" + RegistrosSeleccionados
					else
						seleccion = ""
					top.main.location = "administrar.php?base=" + base + "&cipar=" + cipar + seleccion
					break;
				case "barcode":
					top.main.location = "../barcode/bcl_labelshow.php?base=" + base;
					break;
				case "copiar_archivo":
					top.main.document.location = "copiar_archivo.php?&base=" + base + "&cipar=" + cipar
					break
				case 'imprimir':
					if (RegistrosSeleccionados != "")
						seleccion = "&seleccionados=" + RegistrosSeleccionados
					else
						seleccion = ""
					top.main.document.location = "../dbadmin/pft.php?Modulo=dataentry&base=" + base + "&cipar=" + cipar + seleccion
					break
				case 'global':
					top.main.document.location = "c_global.php?&base=" + base + "&cipar=" + cipar
					return;
					break
				case 'tabla':
					xmfn = top.menu.document.forma1.ir_a.value
					res = ValidarIrA()
					mfn = Number(xmfn)
					if (res) {
						Opcion = "tabla"
						top.main.document.location.href = "actualizarportabla.php?Opcion=tabla&base=" + base + "&cipar=" + cipar + "&Mfn=" + mfn + "&ver=N" + FormatoActual + works
						buscar = ""
					}
					break
				case 'alfa':
					formato_ix = formato_indice
					Prefijo = "&prefijo=" + prefijo_indice + "&formato_e=" + formato_ix + "&bymfn=S"
					var width = screen.width - 650 - 100
					url = "alfa.php?Opcion=autoridades&base=" + base + "&cipar=" + cipar + Prefijo + "&Formato=" + Formato
					msgwin = window.open(url, "Indice", "status=yes,resizable=yes,toolbar=no,menu=yes,scrollbars=yes,width=650,height=580,top=10,left=" + width)
					msgwin.focus()
					break
				case 'ayuda':
					AbrirVentanaAyuda()
					break
				case 'z3950':
					Desplegar = "N"
					xError = "S"
					if (browseby == "search")
						Mfn_p = Mfn_Search
					else
						Mfn_p = mfn
					if (xeditar == "S") {
						top.main.location.href = "z3950.php?Mfn=" + Mfn_p + "&Opcion=edit&base=" + base + "&cipar=" + cipar + FormatoActual
					} else {
						top.main.location.href = "z3950.php?Opcion=new&base=" + base + "&cipar=" + cipar + FormatoActual
					}
					break
				case 'dup_record':
					if (mfn == 0 && Mfn_Search == 0) {
						alert("<?php echo $msgstr["selmod"] ?>")
						return
					}
					xeditar = "S"
					if (browseby == "search")
						mfn_edit = Mfn_Search
					else
						mfn_edit = mfn
					cnv = ""
					loc = "fmt.php?Opcion=presentar_captura&Mfn=" + mfn_edit + "&ver=N&base=" + base + "&cipar=" + base + ".par&basecap=" + base + "&ciparcap=" + base + ".par" + cnv
					top.main.location.href = loc
					break
				case 'capturar_bd':
					Capturando = 'S'
					Desplegar = "N"
					xError = "S"
					formato_ix = escape(formato_indice + "'$$$'f(mfn,1,0)")
					width = screen.width
					msgwin = window.open("capturar_main.php?base=" + base + "&cipar=" + cipar + "&formato_e=" + formato_ix + "&prefijo=" + prefijo_indice + "&formatoactual=" + FormatoActual + "&fc=cap&html=ayuda_captura.html", "capturar")
					msgwin.focus()
					break
				case 'proximo': //next record
					Opcion = "leer"
					buscar = ""
					switch (browseby) {
						case 'search':
							tope = Max_Search
							mfn = Search_pos
							Search_pos = Search_pos + 1
							if (Search_pos > tope)
								Search_pos = tope
							if (mfn <= 0) mfn = 0
							mfn++
							if (mfn > tope) mfn = tope
							break
						case 'selected_records':
							if (Trim(RegistrosSeleccionados) == "")
								return
							Listar_pos = Listar_pos + 1
							RegSel = RegistrosSeleccionados.replace(/__/g, "_")
							if (RegSel.substr(0, 1) == "_")
								RegSel = RegSel.substr(1)
							SelLen = RegSel.length
							if (RegSel.substr(SelLen - 1, 1) == "_")
								RegSel = RegSel.substr(0, SelLen - 1)
							ss = RegSel.split("_")
							if (Listar_pos >= ss.length) {
								Listar_pos = ss.length - 1
							}
							mfn = ss[Listar_pos]
							tope = ss.length
							break
						default:
							tope = maxmfn
							if (mfn <= 0) mfn = 0
							mfn++
							if (mfn > tope) mfn = tope
							break
					}
					top.menu.document.forma1.ir_a.value = mfn + "/" + tope
					break
				case 'anterior': //previous record
					Opcion = "leer"
					buscar = ""
					switch (browseby) {
						case 'search':
							tope = Max_Search
							mfn = Search_pos
							Search_pos = Search_pos - 1
							if (Search_pos <= 0) Search_pos = 1
							if (mfn <= 0) mfn = 1
							if (mfn > 1) mfn = mfn - 1
							break
						case 'selected_records':
							if (Trim(RegistrosSeleccionados) == "")
								return
							Listar_pos = Listar_pos - 1
							if (Listar_pos < 0) Listar_pos = 0
							RegSel = RegistrosSeleccionados.replace(/__/g, "_")
							if (RegSel.substr(0, 1) == "_") RegSel = RegSel.substr(1)
							SelLen = RegSel.length
							if (RegSel.substr(SelLen - 1, 1) == "_") RegSel = RegSel.substr(0, SelLen - 1)
							ss = RegSel.split("_")
							mfn = ss[Listar_pos]
							tope = ss.length
							break
						default:
							tope = maxmfn
							if (mfn > 1) mfn = mfn - 1
							if (mfn <= 0) mfn = 1
							if (mfn > tope) mfn = tope
							break
					}
					top.menu.document.forma1.ir_a.value = mfn + "/" + tope
					break
				case 'primero': // first record
					Opcion = "leer"
					buscar = ""
					switch (browseby) {
						case 'search':
							tope = Max_Search
							Search_pos = 1
							mfn = Search_pos
							break
						case 'selected_records':
							if (Trim(RegistrosSeleccionados) == "")
								return
							RegSel = RegistrosSeleccionados.replace(/__/g, "_")
							if (RegSel.substr(0, 1) == "_") RegSel = RegSel.substr(1)
							SelLen = RegSel.length
							if (RegSel.substr(SelLen - 1, 1) == "_") RegSel = RegSel.substr(0, SelLen - 1)
							ss = RegSel.split("_")
							Listar_pos = 0
							mfn = ss[0]
							tope = ss.length
							break
						default:
							tope = maxmfn
							mfn = 1
							if (mfn > 1) mfn = mfn - 1
							if (mfn <= 0) mfn = 1
							if (mfn > tope) mfn = tope
							break
					}
					top.menu.document.forma1.ir_a.value = mfn + "/" + tope
					break
				case 'ultimo': //last record
					Opcion = "leer"
					buscar = ""
					switch (browseby) {
						case 'search':
							tope = Max_Search
							Search_pos = Max_Search
							mfn = Max_Search
							break
						case 'selected_records':
							if (Trim(RegistrosSeleccionados) == "")
								return
							RegSel = RegistrosSeleccionados.replace(/__/g, "_")
							if (RegSel.substr(0, 1) == "_") RegSel = RegSel.substr(1)
							SelLen = RegSel.length
							if (RegSel.substr(SelLen - 1, 1) == "_") RegSel = RegSel.substr(0, SelLen - 1)
							ss = RegSel.split("_")
							Listar_pos = ss.length - 1
							mfn = ss[ss.length - 1]
							tope = ss.length - 1
							break
						default:
							tope = maxmfn
							mfn = tope
							break
					}
					top.menu.document.forma1.ir_a.value = mfn + "/" + tope
					break
				case "same":
					Opcion = "leer"
					buscar = ""
					if (browseby == 'search') { //Search_pos=Mfn_Search
						tope = Max_Search
						top.menu.document.forma1.ir_a.value = mfn + "/" + tope
					}
					break
				case 'eliminar':
					if (mfn == 0) {
						alert("<?php echo $msgstr["seleliminar"] ?>")
						return
					}
					if (xeliminar == 0) {
						alert("<?php echo $msgstr["confirmdel"] ?>")
						xeliminar = xeliminar + 1
					} else {
						if (xeditar == "S")
							Mfn_p = top.main.document.forma1.Mfn.value
						else
						if (browseby == "search")
							Mfn_p = Mfn_Search
						else
							Mfn_p = mfn

						if (Mfn_p == "New") {
							alert("<?php echo $msgstr["cancelnuevo"] ?>")
							return
						}
						if (Mfn_p == 0) {
							alert("<?php echo $msgstr["seleliminar"] ?>")
							return
						}
						if (xeliminar == "") {
							alert("<?php echo $msgstr["confirmdel"] ?>")
							xeliminar = "1"
						} else {
							xeliminar = ""
							xeditar = ""
							top.main.document.location = "../dataentry/fmt.php?Opcion=eliminar&base=" + base + "&cipar=" + cipar + "&Mfn=" + Mfn_p + "&ver=N" + FormatoActual + works + urlcopies
						}
					}
					return
					break
				case 'ira':
					xmfn = ValidarIrA()
					if (!xmfn) break;
					buscar = ""
					Opcion = "leer"
					if (browseby == "search") {
						tope = Max_Search
						Search_pos = Number(xmfn);
						mfn = Search_pos + Number(xmfn);
					} else if (browseby == "selected_records") {
						RegSel = top.RegistrosSeleccionados.replace(/__/g, "_")
						if (RegSel.substr(0, 1) == "_") RegSel = RegSel.substr(1)
						SelLen = RegSel.length
						if (RegSel.substr(SelLen - 1, 1) == "_") RegSel = RegSel.substr(0, SelLen - 1)
						ss = RegSel.split("_")
						sslength = ss.length
						mfn = ss[xmfn - 1]
						tope = ss.length
					} else {
						mfn = xmfn
						tope = xmfn
					}
					break
				case 'refresh_db':
					top.main.location.href = "../dataentry/inicio_base.php?base=" + base + "&cipar=" + base + ".par"
					break
			}

			if (Opcion == "editar") {
				if (mfn == 0 && Mfn_Search == 0) {
					alert("<?php echo $msgstr["selmod"] ?>")
					return
				}
				ix = top.menu.document.forma1.wks.selectedIndex
				if (ix == -1) {} else {
					works = "&wks=" + top.menu.document.forma1.wks.options[ix].value
				}

				xeditar = "S"
				if (browseby == "search")
					mfn_edit = Mfn_Search
				else
					mfn_edit = mfn
				top.main.document.location = "../dataentry/fmt.php?Opcion=editar&base=" + base + "&cipar=" + cipar + "&Mfn=" + mfn_edit + "&ver=N" + FormatoActual + works + urlcopies
				return
			}

			if (Opcion == "ver") {
				if (tope != 0) top.main.document.location = "../dataentry/fmt.php?Opcion=ver&base=" + base + "&cipar=" + cipar + "&Mfn=" + mfn + "&ver=S" + FormatoActual + urlcopies
				return
			}
			if (Opcion == "leer") {
				if (ConFormato == true) {
					Opcion = "ver"
				} else {
					Opcion = "leer"
				}

				if (mfn <= 0) mfn = 1
				if (typeof tope === 'undefined') return
				if (tope == 0) return
				if (browseby == "mfn" || browseby == "selected_records") {
					top.main.document.location.href = "../dataentry/fmt.php?Opcion=" + Opcion + "&base=" + base + "&cipar=" + cipar + "&Mfn=" + mfn + "&ver=S" + FormatoActual + works + urlcopies
				} else {
					url = "../dataentry/fmt.php?Opcion=buscar&Expresion=" + Expresion + "&base=" + base + "&cipar=" + cipar + "&from=" + Search_pos + FormatoActual + "&Mfn=" + Mfn_Search + urlcopies
					top.main.document.location.href = url
				}
				return
			}

			if (Opcion == "cancelar") {
				if (mfn <= 0) mfn = 1
				if (browseby == "mfn") {
					top.main.document.location.href = "../dataentry/fmt.php?Opcion=" + Opcion + "&base=" + base + "&cipar=" + cipar + "&Mfn=" + mfn + "&ver=S" + FormatoActual + works + "&unlock=S" + urlcopies
				} else {
					url = "../dataentry/fmt.php?Opcion=cancelar&base=" + base + "&cipar=" + cipar + "&from=" + Search_pos + "&Mfn=" + Mfn_Search + FormatoActual + urlcopies
					url += "&unlock=S";
					top.main.document.location.href = url
				}
				return
			}
			if (Opcion == "nuevo" || Opcion == "crear") {
				tipom = ""
				works = "";

				if (typeofrecord != "" && Opcion == "nuevo") {
					top.main.document.close()
					TipoDeRegistro()
				} else {
					ix = top.menu.document.forma1.wks.selectedIndex
					if (ix == -1) {} else {
						if (wks == "")
							works = "&wks=" + top.menu.document.forma1.wks.options[ix].value
					}
					if (works == "") works = "&wks=" + wks
					xeditar = "S"
					top.main.document.location = "../dataentry/fmt.php?Opcion=nuevo&base=" + base + "&cipar=" + cipar + "&Mfn=New&ver=N" + FormatoActual + "&tipom=" + tipom + works + urlcopies
				}
				return
			}

			if (Opcion == "actualizar") {
				if (xeditar != "S") {
					alert("<?php echo $msgstr["menu_edit"] ?>")
					return
				}

				xeditar = ''
				top.main.document.forma1.Opcion.value = "actualizar"
				top.main.document.forma1.submit()
			}

			if (Opcion == "buscar" || Opcion == "refinar") {
				top.buscar = 'S'
				top.Search_pos = 1
				if (Opcion == "refinar")
					refine = "&refine=y"
				else
					refine = ""
				top.main.document.location = "../dataentry/buscar.php?Opcion=formab" + refine + "&prologo=prologoact&desde=dataentry&base=" + base + "&cipar=" + cipar + FormatoActual
				return
			}
			if (Opcion == "re")
				if (Opcion == "cancelar")
					ApagarEdicion()
			else
				PrenderEdicion()

		}
	</script>

	<div id="body" style="height: 100%; overflow: hidden; position: relative;">
		<?php
		if (!isset($arrHttp["Mfn"])) $arrHttp["Mfn"] = 0;
		?>
		<iframe name="header" id="header" class="dataentry-header"
			src="menubases.php?inicio=s&Opcion=Menu_o&base=<?php echo $bd; ?>&cipar=<?php echo $bd; ?>.par&Mfn=<?php echo $arrHttp['Mfn']; ?>&base_activa=<?php echo $bd; ?>&per=<?php echo $bdright; ?>">
		</iframe>
		<iframe name="menu" id="menu" class="dataentry-menu">
		</iframe>
		<iframe name="main" id="main" class="dataentry-main">
		</iframe>
	</div>

	<script>
		var iframeHeader = document.getElementById("header");
		var iframeMenu = document.getElementById("menu");
		var iframeMain = document.getElementById("main");

		var manualMenuOverride = null;

		function RecalculateLayout() {
			var Todobody = window.innerHeight;
			var headerHeight = 45;

			var isEditing = (typeof xeditar !== 'undefined' && xeditar === "S");
			var shouldCollapse = (manualMenuOverride !== null) ? !manualMenuOverride : isEditing;

			if (!window.savedMenuHeight) window.savedMenuHeight = 77;
			try {
				var actualHeight = iframeMenu.contentWindow.document.body.scrollHeight;
				if (actualHeight > 20) window.savedMenuHeight = actualHeight;
			} catch (e) {}

			// --- ZERANDO O ESPAÇO EM BRANCO ---
			// Usamos display: none para remover completamente a barra cinza
			if (shouldCollapse) {
				iframeMenu.style.display = 'none';
				var currentMenuHeight = 0;
			} else {
				iframeMenu.style.display = 'block';
				var currentMenuHeight = window.savedMenuHeight;
			}

			var folga = Todobody - headerHeight - currentMenuHeight;
			iframeMain.style.height = folga + 'px';

			// Atualiza os ícones do botão que agora vive DENTRO do iframeMain
			try {
				var toggleIcon = iframeMain.contentWindow.document.getElementById('menu-toggle-icon');
				var toggleText = iframeMain.contentWindow.document.getElementById('menu-toggle-text');

				if (toggleIcon && toggleText) {
					if (shouldCollapse) {
						toggleIcon.className = "fas fa-chevron-down";
						toggleText.textContent = "<?php echo $msgstr["show_top_bar"]; ?>";
					} else {
						toggleIcon.className = "fas fa-chevron-up";
						toggleText.textContent = "<?php echo $msgstr["hide_top_bar"]; ?>";
					}
				}
			} catch (e) {
				// Ignora erros caso o iframeMain esteja carregando outra página
			}
		}

		function ToggleMenuManually() {
			var isEditing = (typeof xeditar !== 'undefined' && xeditar === "S");
			var currentlyCollapsed = (manualMenuOverride !== null) ? !manualMenuOverride : isEditing;

			manualMenuOverride = currentlyCollapsed;
			RecalculateLayout();
		}

		iframeMain.onload = function() {
			manualMenuOverride = null;
			RecalculateLayout();
		};

		window.addEventListener('resize', RecalculateLayout);
	</script>
</body>

</html>
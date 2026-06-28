<?php
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"] = "en";
include("../common/get_post.php");
$arrHttp["base"] = "users";
include("../config.php");
$lang = $_SESSION["lang"];
include("../lang/admin.php");
include("../lang/prestamo.php");

//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";
include("../common/header.php");



function LeerPft($pft_name, $base)
{
	global $arrHttp, $db_path, $lang_db;
	$pft = "";
	$archivo = $db_path . $base . "/loans/" . $_SESSION["lang"] . "/$pft_name";
	if (!file_exists($archivo)) $archivo = $db_path . $base . "/loans/" . $lang_db . "/$pft_name";
	$fp = file_exists($archivo);
	if ($fp) {
		$fp = file($archivo);
		foreach ($fp as $value) {
			$pft .= $value;
		}
	}
	return $pft;
}
//se lee la configuración del usuario
$us_tab = LeerPft("loans_uskey.tab", "users");
$t = explode("\n", $us_tab);
$prefijo_codigo = $t[0];
if (isset($t[1]))
	$prefijo_nombre = $t[1];
else
	$prefijo_nombre = "";
if (isset($t[2]))
	$formato_nombre = $t[2];
else
	$formato_nombre = "";
if ($prefijo_codigo == "") $prefijo_codigo = "CO_";
if ($prefijo_nombre == "") $prefijo_nombre = "NO_";
if ($formato_nombre == "") $formato_nombre = "v30";
# Se lee el formato para extraer el código de usuario
$codigo = LeerPft("loans_uskey.pft", "users");
?>
<script language="JavaScript" type="text/javascript" src=../dataentry/js/lr_trim.js></script>
<script>
	function checkSubmit(e, forma) {
		if (e && e.keyCode == 13) {
			switch (forma) {
				case 1:
					EnviarForma_status('U')
					break
				case 2:
					EnviarForma_status('I')
					break
			}
		}
	}

	function EnviarForma_status(Proceso) {
		if (Trim(document.usersearch.code.value) == "" && Trim(document.inventorysearch.inventory.value) == "") {
			alert("<?php echo $msgstr["falta"] . " " . $msgstr["usercode"] . "/" . $msgstr["inventory"] ?>")
			return
		}
		if (Proceso == "") {
			if (Trim(document.usersearch.code.value) != "") {
				document.EnviarFrm.usuario.value = document.usersearch.usercode.value
				document.EnviarFrm.action = "panel_loans.php"
			} else {
				if (Trim(document.inventorysearch.inventory.value) != "") {
					document.EnviarFrm.inventory.value = document.inventorysearch.inventory.value
					document.EnviarFrm.action = "numero_inventario.php"
				}
			}
		} else {
			switch (Proceso) {
				case "U":
					document.EnviarFrm.usuario.value = document.usersearch.usercode.value
					document.EnviarFrm.action = "panel_loans.php"
					break
				case "I":
					document.EnviarFrm.inventory.value = document.inventorysearch.inventory.value
					document.EnviarFrm.action = "numero_inventario.php"
					document.EnviarFrm.submit()
					break
			}
		}
		document.EnviarFrm.target = "_top"
		document.EnviarFrm.submit()
	}

	function AbrirIndiceAlfabetico(xI, Prefijo, Subc, Separa, db, cipar, tag, postings, repetible, Formato) {
		Ctrl_activo = xI
		lang = "en"
		Separa = "&delimitador=" + Separa
		Prefijo = Separa + "&tagfst=" + tag + "&prefijo=" + Prefijo
		ancho = 200
		url_indice = "capturaclaves.php?opcion=autoridades&base=" + db + "&cipar=" + cipar + "&Tag=" + tag + Prefijo + "&postings=" + postings + "&lang=" + lang + "&repetible=0&Formato=" + Formato
		msgwin = window.open(url_indice, "Indice", "width=480, height=425,scrollbars")
		msgwin.focus()
	}

	function AbrirIndice(TipoI, Ctrl) {

		switch (TipoI) {
			case "U":
				db = "users"
				Formato = "<?php echo trim($formato_nombre) ?>,`$$$`,<?php echo str_replace("'", "`", trim($codigo)) ?>"
				prefijo = "<?php echo trim($prefijo_nombre) ?>"
				AbrirIndiceAlfabetico(Ctrl, "<?php echo trim($prefijo_nombre) ?>", "", "", "users", "users.par", "30", 1, "0", Formato)
				break
			case "I":
				db = "trans"
				prefijo = "!Z"
				AbrirIndiceAlfabetico(Ctrl, "TR_P_", "", "", "trans", "trans.par", "10", 1, "", "ifp")
				break
		}
	}

	function Output(code, name) {
		//document.getElementById('preloader').style.display = 'block';
		if (Trim(document.usersearch.usercode.value) != "")
			document.output.user.value = document.usersearch.usercode.value
		for (i = 0; i < document.inventorysearch.sort.length; i++) {
			if (document.inventorysearch.sort[i].checked) {
				document.output.sort.value = document.inventorysearch.sort[i].value
			}
		}
		document.output.action = "../output_circulation/" + name + ".php"
		document.output.code.value = code
		document.output.name.value = name
		document.output.target = "receiver"
		document.output.submit()
	}
</script>
<?php
$encabezado = "";
echo "<body onLoad=javascript:document.usersearch.usercode.focus()>\n";
include("../common/institutional_info.php");
$link_u = "";
if (isset($arrHttp["usuario"]) and $arrHttp["usuario"] != "") $link_u = "&usuario=" . $arrHttp["usuario"];
if (isset($arrHttp["reserve"]) and $arrHttp["reserve"] == "S")
	$ayuda = "reserve.html";
else
	$ayuda = "user_statment.html"
?>

<?php include("../common/inc_wait.php"); ?>

<div class="sectionInfo">
	<div class="breadcrumb">
		<?php if (!isset($arrHttp["reserve"]))
			echo $msgstr["statment"];
		else
			echo $msgstr["reservas"];
		?>
	</div>
	<div class="actions">
	</div>
	<?php include("submenu_prestamo.php"); ?>
</div>


<?php
$ayuda = "loan.html";
include "../common/inc_div-helper.php";
?>

<div class="middle form row m-0">
	<div class="formContent col-2 m-2">

		<h4><?php echo $msgstr["usercode"] ?></h4>
		<form name="usersearch" action="" method="post" onsubmit="javascript:return false">
			<input type="hidden" name="Indice">

			<button type="button" name="index" title="<?php echo $msgstr["list"] ?>" class="bt-blue col-2 p-2 m-0" onClick="AbrirIndice('U',document.usersearch.usercode)" /><i class="fa fa-search"></i></button>

			<input type="text" name="usercode" id="code" value="<?php if (isset($arrHttp["usuario"])) echo $arrHttp["usuario"] ?>" class="w-8 p-2 m-0" />

			<button type="button" name="buscar" title="<?php echo $msgstr["search"] ?>" class="bt-green w-10 mt-2" onclick="EnviarForma_status('U')" /><?php echo $msgstr["search"] ?> <i class="fas fa-arrow-right"></i></button>

		</form>


		<form name=inventorysearch action=numero_inventario.php method=post onsubmit="javascript:return false">

			<?php if (!isset($arrHttp["reserve"]) and !isset($arrHttp["reserve_ex"])) { ?>

				<p><?php echo $msgstr["ec_inv"] ?></p>

				<h4><?php echo $msgstr["inventory"] ?></h4>

				<button type="button" name="index" title="<?php echo $msgstr["list"] ?>" class="bt-blue col-2 p-2 m-0" onClick="AbrirIndice('I',document.inventorysearch.inventory)" /><i class="fa fa-search"></i></button>

				<input type="text" name="inventory" id="searchExpr" value="" class="w-8 p-2 m-0" />

				<button type="button" name="buscar" title="<?php echo $msgstr["search"] ?>" class="bt-green w-10 mt-2" onclick="EnviarForma_status('I')" /><?php echo $msgstr["search"] ?> <i class="fas fa-arrow-right"></i></button>

			<?php } ?>
			<small><?php echo $msgstr["clic_en"] . " <i>" . $msgstr["search"] . "</i> " . $msgstr["para_c"] . "</small>";

					if (isset($WEBRESERVATION) and $WEBRESERVATION == "Y") {
						echo "<p>";
						echo "<input type=button name=rsv_p value=\"Reservas web\" style='font-size:27px;border-radius:20px;background color:#cccccc; font-color=black' onclick=\"javascript:Output('actives_web','rsweb')\"><p>";
					}
					if (isset($arrHttp["reserve"]) and $arrHttp["reserve"] == "S") {
						echo "<h3>" . $msgstr["reports"] . "</h3>";
						echo $msgstr["orderby"];
						echo "&nbsp; &nbsp;<input type=radio name=sort value=name>" . $msgstr["name"];
						echo "&nbsp; &nbsp;<input type=radio name=sort value=date_reservation>" . $msgstr["reserve_date"];
						echo "&nbsp; &nbsp;<input type=radio name=sort value=date_assigned>" . $msgstr["assigned_date"];
						echo "&nbsp; &nbsp;<input type=radio name=sort value=date_attended>" . $msgstr["loandate"];
						echo "<br>";
						echo "<input type=button class=bt-blue name=rs00 value=\"" . $msgstr["rs00"] . "\" onClick=\"javascript:Output('today','rsweb')\" style='width:400px'><p>";
						echo "<input type=button class=bt-blue name=rs01 value=\"" . $msgstr["rs01"] . "\" onClick=\"javascript:Output('actives','rsweb')\" style='width:400px'><p>";
						echo "<input type=button class=bt-blue name=rs02 value=\"" . $msgstr["rs02"] . "\" onClick=\"javascript:Output('assigned','rsweb')\" style='width:400px'><p>";
						echo "<input type=button class=bt-blue name=rs03 value=\"" . $msgstr["rs03"] . "\" onClick=\"javascript:Output('overdued','rs01')\" style='width:400px'><p>";
						echo "<input type=button class=bt-blue name=rs04 value=\"" . $msgstr["rs04"] . "\" onClick=\"javascript:Output('attended','rs01')\" style='width:400px'><p>";
						echo "<input type=button class=bt-blue name=rs05 value=\"" . $msgstr["rs05"] . "\" onClick=\"javascript:Output('cancelled','rs01')\" style='width:400px' ><p>";
						echo "<br><br>";
					}

					?>
		</form>
	</div>

	<div class="formContent col-9 m-2">
		<iframe class="iframe w-10" height="600px" name="receiver" id="receiver" frameborder="0"></iframe>
	</div>
</div>

<form name="EnviarFrm" method="post">
	<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
	<input type="hidden" name="usuario" value="">
	<input type="hidden" name="inventory">
	<input type="hidden" name="ecta" value="Y">
	<input type="hidden" name="lang" value="<?php echo $lang; ?>">
	<?php if (isset($arrHttp["reserve"])) {
		echo "<input type=hidden name=reserve value=S>\n";
	}
	?>
</form>
<form name=output method=post>
	<input type="hidden" name="base" value="reserve">
	<input type="hidden" name="code">
	<input type="hidden" name="name">
	<input type="hidden" name="user">
	<input type="hidden" name="sort">
	<input type="hidden" name="retorno" value="../circulation/estado_de_cuenta.php">
	<input type="hidden" name="lang" value="<?php echo $lang; ?>">
	<input type="hidden" name="reserva" value="S">
</form>
<?php 
echo "</body></html>";
if (isset($arrHttp["error"]) and $arrHttp["inventory"] != "") {
	echo "
	<script>
	alert('" . $arrHttp["inventory"] . ": El número de inventario no está prestado')
	</script>
	";
}

include("../common/footer.php");
?>
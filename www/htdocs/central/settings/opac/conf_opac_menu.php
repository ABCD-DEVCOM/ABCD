<?php
//foreach ($_REQUEST AS $var=>$value) echo "$var=$value<br>";
if (isset($_REQUEST["conf_level"])) unset($_REQUEST["conf_level"]);
if (isset($_REQUEST["lang_init"])) {
	$_SESSION["lang_init"] = $_REQUEST["lang_init"];
	unset($_REQUEST["lang_init"]);
}
$wiki_help = "OPAC-ABCD_Detalles_de_la_configuraci%C3%B3n#Men.C3.BA_de_configuraci.C3.B3n";

?>



<script>
	function EnviarCopia() {
		if (document.copiar_a.lang_to.options[document.copiar_a.lang_to.selectedIndex].value == "<?php echo $lang; ?>") {
			alert("<?php echo $msgstr["sel_o_l"] ?>")
			return false
		}
		if (document.copiar_a.replace_a[0].checked || document.copiar_a.replace_a[1].checked) {
			document.copiar_a.submit()
		} else {
			alert("<?php echo $msgstr["font_missing"] . " " . $msgstr["sustituir_archivos"]; ?>")
			return false
		}
	}
</script>

<h4 class="px-3"><?php echo $msgstr["general"] ?></h4>

<button class="button_def" onclick="javascript:EnviarForma('/central/settings/opac')"><i class="fa fa-home"></i> <?php echo $msgstr["inicio"]; ?></button>
<form name="form_lang" method="post">

	<button type="button" class="accordion" id="general"><i class="fas fa-cog"></i> <?php echo $msgstr["menu_2"]; ?></button>
	<div class="panel panel-menu">
		<li><a href="javascript:EnviarForma('/central/settings/opac/parametros.php')"><?php echo $msgstr["parametros"]; ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/adm_email.php')"><?php echo $msgstr["cfg_email"]; ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/view_search.php')"><?php echo $msgstr["abcd_analytics"]; ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/diagnostico.php')"><?php echo $msgstr["check_conf"]; ?></a></li>
	</div>





	<button type="button" class="accordion" id="apariencia"><i class="fas fa-paint-brush"></i> <?php echo $msgstr["apariencia"]; ?></button>
	<div class="panel panel-menu">
		<li><a href="javascript:EnviarForma('/central/settings/opac/presentacion.php')"><?php echo $msgstr["pagina_presentacion"]; ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/pagina_inicio.php')"><?php echo $msgstr["first_page"] ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/footer_cfg.php')"><?php echo $msgstr["cfg_footer"] ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/sidebar_menu.php')"><?php echo $msgstr["sidebar_menu"] ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/horizontal_menu.php')"><?php echo $msgstr["horizontal_menu"] ?></a></li>
	</div>
	<?php
	if (file_exists($db_path . "opac_conf/" . $lang . "/bases.dat") and file_exists($db_path . "opac_conf/" . $lang . "/lang.tab")) {
	?>



		<button type="button" class="accordion" id="db_configuration"><i class="fas fa-database"></i> <?php echo $msgstr["db_configuration"] ?></button>
		<div class="panel panel-menu">
			<li><a href="javascript:EnviarForma('/central/settings/opac/conf_databases.php')"><?php echo $msgstr["databases"]; ?></a></li>


			<?php
			if (!file_exists($db_path . "opac_conf/" . $lang . "/bases.dat")) {
				echo "<font color=red>" . $msgstr["missing"] . "opac_conf/" . $lang . "/bases.dat";
			} else {
				$fp = file($db_path . "opac_conf/" . $lang . "/bases.dat");
				$cuenta = 0;
				foreach ($fp as $value) {
					if (trim($value) != "") {
						$cuenta = $cuenta + 1;
						$x = explode('|', $value);
						echo "<li><a href=\"javascript:SeleccionarBase('" . $x[0] . "')\">" . $x[1] . " (" . $x[0] . ")</a></li>";
						$base = $x[0];
			?>

				<?php		}
				}
				?>
		</div>
	<?php

			}

			if ($cuenta > 1) {
	?>


	<?php }
	?>

	<button type="button" class="accordion" id="metasearch"><i class="fas fa-search"></i> <?php echo $msgstr["metasearch"]; ?></button>
	<div class="panel panel-menu">
		<li><a href="javascript:SeleccionarProceso('edit_form-search.php','META','libre')"><?php echo $msgstr["free_search"]; ?></a></li>
		<li><a href="javascript:SeleccionarProceso('facetas_cnf.php','META','')"><?php echo $msgstr["facetas"]; ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/record_toolbar.php')"><?php echo $msgstr["rtb"]; ?></a></li>
		<li><a href="javascript:SeleccionarProceso('alpha_ix.php','META','')"><?php echo $msgstr["indice_alfa"]; ?></a></li>
	</div>

	<button type="button" class="accordion" id="meta_schema"><i class="fas fa-code"></i> <?php echo $msgstr["meta_schema"]; ?> </button>
	<div class="panel panel-menu">
		<li><a href="javascript:EnviarForma('/central/settings/opac/marc_scheme.php')"><?php echo $msgstr["xml_marc"]; ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/dc_scheme.php')"><?php echo $msgstr["xml_dc"]; ?></a></li>
	</div>
	<button type="button" class="accordion" id="charset_cnf"><i class="fas fa-globe"></i> <?php echo $msgstr["charset_cnf"]; ?></button>
	<div class="panel panel-menu">
		<li><a href="javascript:EnviarForma('/central/settings/opac/lenguajes.php')"><?php echo $msgstr["available_languages"]; ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/db_langs.php')"><?php echo $msgstr["avail_db_lang"]; ?></a></li>
		<!--<li><a href="javascript:SeleccionarProceso('conf_databases.php','1')"><?php echo $msgstr["charset_db"]; ?></a></li>-->
	</div>

	<button type="button" class="accordion" id="loan_conf"><i class="fas fa-book-reader"></i> <?php echo $msgstr["loan_conf"] ?></button>
	<div class="panel panel-menu">
		<li><a href="javascript:EnviarForma('/central/settings/opac/statment_cnf.php')"><?php echo $msgstr["cfg_ONLINESTATMENT"] ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/renovation_cnf.php')"><?php echo $msgstr["cfg_WEBRENOVATION"] ?></a></li>
		<li><a href="javascript:EnviarForma('/central/settings/opac/reservations_cnf.php')"><?php echo $msgstr["cfg_WEBRESERVATION"] ?></a></li>
	</div>
	<br><br>
<?php } ?>

<div class="p-3">
	<label><?php echo $msgstr["lang"]; ?></label>
	<?php
	// Adicione esta linha para manter a base selecionada ao trocar de idioma
	if (isset($_REQUEST["base"])) {
		echo '<input type="hidden" name="base" value="' . htmlspecialchars($_REQUEST["base"]) . '">';
	}
	?>
	<select name="lang" onchange="document.form_lang.submit()" id="lang">
		<?php
		$archivo = $db_path . "opac_conf/$lang/lang.tab";
		if (file_exists($archivo)) {
			$fp = file($archivo);
			foreach ($fp as $value) {
				if (trim($value) != "") {
					$a = explode("=", $value);
					echo "<option value=" . $a[0];
					if ($lang == $a[0]) echo " selected";
					echo ">" . trim($a[1]) . "</option>";
				}
			}
			unset($fp);
		} else {
			echo "<option value=$lang selected>$lang</option>";
		}

		?>
	</select>
</div>
</form>


<form name="opciones_menu" method="post">
	<?php if (isset($_REQUEST["conf_level"])) {
		echo "<input type=hidden name=conf_level value=" . $_REQUEST["conf_level"] . ">\n";
	} ?>
	<input type="hidden" name="base">
	<input type="hidden" name="lang" value="<?php echo $lang; ?>">
	<input type="hidden" name="o_conf">
	<input type="hidden" name="db_path" value="<?php if (isset($_REQUEST["db_path"])) echo $_REQUEST["db_path"] ?>">
</form>

<script>
	// The headings are the main lines of the accordions
	var acc = document.getElementsByClassName("accordion");

	var id_active = window.idPage;
	var actid = document.getElementById(id_active);

	console.log(actid);

	window.addEventListener("load", function() {
		var panel = actid.nextElementSibling;
		panel.style.display = "block";
	});


	var i;

	for (i = 0; i < acc.length; i++) {
		acc[i].addEventListener("click", function() {
			this.classList.toggle("active");
			var panel = this.nextElementSibling;
			if (panel.style.display === "block") {
				panel.style.display = "none";
			} else {
				panel.style.display = "block";
			}
		});
	}
</script>
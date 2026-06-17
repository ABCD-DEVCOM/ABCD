<?php
/*
2022-03-07 rogercgui fixed index $prefijo=$x[1];
2025-09-28 rogercgui added check if indice.ix has fields with columnas to show button
2025-11-18 rogercgui corrected to use $hide_filter variable
2026-03-24 rogercgui Change the layout to make it look more balanced; Create a valid conditional for the search button.
2026-06-16 rogercgui Added a UX fix to allow the URL to open the index menu by default if requested.
*/
?>

<?php
// Definindo $base com segurança
$base = $_REQUEST["base"] ?? "";

// Título da página
if (!isset($titulo_pagina)) {
	if (isset($_REQUEST["modo"]) && $_REQUEST["modo"] == "integrado") {
?>
		<h6 class="text-dark"><?php echo $msgstr["front_todos_c"]; ?></h6>
		<input type="hidden" name="modo" value="integrado">
	<?php
	} else {
		if ($base != "") {
			if (isset($bd_list[$base])) {
				echo "<h6 class=\"text-dark\">" . $bd_list[$base]["titulo"] . "</h6>";
				$yaidentificado = "S";
				if (isset($_REQUEST["coleccion"]) && $_REQUEST["coleccion"] != "") {
					$_REQUEST["coleccion"] = urldecode($_REQUEST["coleccion"]);
					$cc = explode('|', $_REQUEST["coleccion"]);
					echo " > <i>" . $cc[1] . "</i>";
				}
			} else {
				// Se a base não existe no array, exibe uma mensagem de erro
				echo "<h6 class=\"text-danger\">Database '" . htmlspecialchars($base) . "' not available.</h6>";
			}
		}
	}
}

if (!isset($mostrar_libre) || $mostrar_libre != "N") {
	?>
	<div id="search">
		<form method="get" action="./" name="libre">
			<?php if (isset($actual_context) && $actual_context != "") { ?>
				<input type="hidden" name="ctx" value="<?php echo htmlspecialchars($actual_context); ?>">
			<?php } ?>
			<input type="hidden" name="page" value="startsearch">
			<input type="hidden" name="target_db" id="target_db_input" value="">
			<?php
			if (isset($_REQUEST["db_path"])) echo "<input type=hidden name=db_path value=" . $_REQUEST["db_path"] . ">\n";
			if (isset($lang)) echo "<input type=hidden name=lang value=" . $lang . ">\n";
			if (isset($_REQUEST["Formato"])) echo "<input type=hidden name=indice_base value=" . $_REQUEST["Formato"] . ">\n";
			if (isset($_REQUEST["indice_base"])) echo "<input type=hidden name=indice_base value=" . $_REQUEST["indice_base"] . ">\n";
			if ($base != "") echo "<input id=base type=hidden name=base value=" . $base . ">\n";
			if (isset($_REQUEST["modo"])) echo "<input type=hidden name=modo value=" . $_REQUEST["modo"] . ">\n";
			if (isset($_REQUEST['Sub_Expresion'])) $_REQUEST['Sub_Expresion'] = urldecode(str_replace('~', '', $_REQUEST['Sub_Expresion']));
			?>

			<div class="input-group input-group-lg abcd-search-bar shadow-sm mb-3">

				<input class="form-control px-4"
					type="text"
					name="Sub_Expresion"
					id="termo-busca"
					value="<?php if (isset($_REQUEST['Sub_Expresion'])) echo htmlentities($_REQUEST['Sub_Expresion']); ?>"
					placeholder="<?php echo $msgstr["front_search"] ?>  ..."
					autocomplete="off">

				<?php if ($hide_filter == "N") { ?>
					<?php include get_template_file("views/dropdown_db.php"); ?>
				<?php } ?>

				<button id="submit-busca-livre" type="submit" class="btn btn-submit btn-search-abcd">
					<i class="fa fa-search"></i> <?php echo $msgstr["front_search"] ?>
				</button>
			</div>
			<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">

				<div class="d-flex flex-column flex-sm-row gap-2 gap-md-3 align-items-start align-items-sm-center w-100">
					<label class="text-secondary mb-0 fw-bold small"><?php echo $msgstr["front_resultados_inc"] ?>:</label>
					<?php $alcance = $_REQUEST['alcance'] ?? 'and'; ?>

					<div class="d-flex gap-3">
						<div class="form-check mb-0">
							<input type="radio" value="and" name="alcance" id="and" class="form-check-input" <?php if ($alcance === 'and') echo 'checked'; ?>>
							<label class="form-check-label text-secondary small" for="and"><?php echo $msgstr["front_todas_p"] ?> </label>
						</div>
						<div class="form-check mb-0">
							<input type="radio" value="or" name="alcance" id="or" class="form-check-input" <?php if ($alcance === 'or') echo 'checked'; ?>>
							<label class="form-check-label text-secondary small" for="or"><?php echo $msgstr["front_algunas_p"] ?></label>
						</div>
					</div>
				</div>

				<div class="d-flex gap-2 w-100 w-md-auto justify-content-between justify-content-md-end mt-2 mt-md-0">
					<?php
					// MANTÉM A LÓGICA DO ÍNDICE
					if (!isset($_REQUEST["submenu"]) || $_REQUEST["submenu"] != "N") {
						$archivo_ix = "";
						if (isset($_REQUEST["modo"]) && $_REQUEST["modo"] == "integrado") {
							$archivo_ix = $db_path . "opac_conf/" . $lang . "/indice.ix";
						} elseif ($base != "") {
							$archivo_ix = $db_path . $base . "/opac/" . $lang . "/" . $base . ".ix";
						}

						$mostrar_botao_indice = false;
						if ($archivo_ix != "" && file_exists($archivo_ix)) {
							$fp_check = file($archivo_ix);
							foreach ($fp_check as $value_check) {
								$val_check = trim($value_check);
								if ($val_check != "") {
									$v_check = explode('|', $val_check);
									if (isset($v_check[2]) && is_numeric($v_check[2])) {
										if ((int)$v_check[2] >= 1) {
											$mostrar_botao_indice = true;
											break;
										}
									}
								}
							}
						}
						if ($mostrar_botao_indice) {
							// If you're in the integrated search, the button takes you back to the Home screen while preserving the search criteria
							if (isset($actualScript) && $actualScript == "buscar_integrada.php") {
								$url_home_indice = "index.php";
								$params = array();
								if ($base != "") $params['base'] = $base;
								if (isset($actual_context) && $actual_context != "") $params['ctx'] = $actual_context;
								$params['open_index'] = 'Y'; // It tells the Home page that the index should open right away!
								$url_home_indice .= (!empty($params)) ? "?" . http_build_query($params) : "";
					?>
								<button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1 flex-md-grow-0" onclick="window.location.href='<?php echo $url_home_indice; ?>'">
									<i class="fas fa-list"></i> <?php echo $msgstr["front_indice_alfa"]; ?>
								</button>
							<?php } else { ?>
								<button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1 flex-md-grow-0" onclick="showhide('sub_menu')">
									<i class="fas fa-list"></i> <?php echo $msgstr["front_indice_alfa"]; ?>
								</button>
					<?php }
						}
					} ?>

					<?php
					// VALIDATE ADVANCED SEARCH
					$BusquedaAvanzada = "N";
					$file_av = "";
					if (isset($_REQUEST["modo"]) && $_REQUEST["modo"] == "integrado") {
						$file_av = $db_path . "opac_conf/" . $lang . "/avanzada.tab";
					} elseif ($base != "") {
						$file_av = $db_path . $base . "/opac/" . $lang . "/" . $base . "_avanzada.tab";
					} else {
						$file_av = $db_path . "opac_conf/" . $lang . "/avanzada.tab";
					}

					if ($file_av != "" && file_exists($file_av)) {
						$fp_av = file($file_av);
						foreach ($fp_av as $line_av) {
							if (trim($line_av) != "") {
								$BusquedaAvanzada = "S";
								break;
							}
						}
					}

					if ($BusquedaAvanzada == "S") { ?>
						<button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1 flex-md-grow-0" onclick="javascript:document.detailed.submit();">
							<i class="fas fa-sliders-h"></i> <?php echo $msgstr["front_buscar_a"] ?>
						</button>
					<?php } ?>
				</div>
			</div>

			<?php if (!isset($_REQUEST["submenu"]) || $_REQUEST["submenu"] != "N") {
				// UX FIX: Checks whether the URL requested that the menu open by default
				$display_status = (isset($_REQUEST["open_index"]) && $_REQUEST["open_index"] == 'Y') ? 'block' : 'none';
			?>
				<div style="clear:both;"></div>
				<div id="sub_menu" style="display: <?php echo $display_status; ?>;" class="mt-2 p-3 bg-light border rounded">
					<?php
					if ($multiplesBases == "Y" && $base != "") {
						$dbname = $base;
					} else {
						$dbname = "";
					}

					if (isset($Home))
						echo "<a href='$Home' class='btn btn-outline-primary btn-sm m-1'>Home</a>\n";

					if (isset($_REQUEST["modo"]) && $_REQUEST["modo"] == "integrado") {
						$archivo = "indice.ix";
						$file_ix = $db_path . "opac_conf/" . $lang . "/" . $archivo;
						$base_ix = "";
					} else {
						if (isset($_REQUEST["coleccion"]) && $_REQUEST["coleccion"] != "") {
							$col = explode("|", $_REQUEST["coleccion"]);
							$archivo = $base . '_' . $col[0] . ".ix";
						} else {
							$archivo = $base . ".ix";
						}
						$file_ix = $db_path . $base . "/opac/" . $lang . "/" . $archivo;
					}

					if (file_exists($file_ix)) {
						$fp = file($file_ix);
						foreach ($fp as $value) {
							$val = trim($value);
							if ($val != "") {
								$v = explode('|', $val);
								if (isset($v[2])) {
									$columnas = $v[2];
									if ($columnas >= 1)
										echo "<a href='Javascript:ActivarIndice(\"" . str_replace("'", "", $v[0]) . "\",\"inicio\",90,1,\"" . $v[1] . "\",\"" . "$base\")'  class=\"btn btn-outline-primary btn-sm m-1\" >" . $v[0] . "</a>\n";
								}
							}
						}
					}

					$archivo = ($base != "") ? $base . "_libre.tab" : "libre.tab";
					$caminho_tab = $db_path . $base . "/opac/" . $lang . "/$archivo";

					if (!file_exists($caminho_tab)) {
						$prefijo = "TW_";
					} else {
						$fp = file($caminho_tab);
						foreach ($fp as $linea) {
							$linea = trim($linea);
							if ($linea != "") {
								$x = explode('|', $linea);
								$prefijo = $x[1] ?? "TW_";
								break;
							}
						}
					}
					?>
					<input type="hidden" name="Opcion" value="libre">
					<input type="hidden" name="prefijo" value="<?php echo $prefijo; ?>">
					<input type="hidden" name="resaltar" value="S">
					<?php if (isset($_REQUEST["coleccion"])) echo "<input type=hidden name=coleccion value=\"" . $_REQUEST["coleccion"] . "\">\n"; ?>
				</div><?php } ?>

			<?php
			if (isset($opac_gdef['CAPTCHA']) && $opac_gdef['CAPTCHA'] === 'Y' && isset($opac_gdef['CAPTCHA_SITE_KEY'])) {
			?>
				<div class="row g-3 justify-content-center py-2 mt-2">
					<div class="col-auto">
						<div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($opac_gdef['CAPTCHA_SITE_KEY']); ?>"></div>
					</div>
				</div>
			<?php
			}
			?>
		</form>
	</div>
	<form method="post" name="detailed">
		<input type="hidden" name="search_form" value="detailed">
		<input type="hidden" name="lang" value="<?php echo $lang; ?>">
		<?php
		// Injeta a Base se existir
		if (isset($_REQUEST["base"]) && $_REQUEST["base"] != "") {
			echo '<input type="hidden" name="base" value="' . htmlspecialchars($_REQUEST["base"]) . '">';
		}
		// Injeta o Contexto se existir (usa a global $actual_context definida no config)
		if (isset($actual_context) && $actual_context != "") {
			echo '<input type="hidden" name="ctx" value="' . htmlspecialchars($actual_context) . '">';
		}
		// Injeta o Modo (se não for base específica)
		if (isset($_REQUEST["modo"])) {
			echo '<input type="hidden" name="modo" value="' . htmlspecialchars($_REQUEST["modo"]) . '">';
		}
		?>

	</form>


	<?php
	// Add the AJAX validation script only if CAPTCHA is enabled
	if (isset($opac_gdef['CAPTCHA']) && $opac_gdef['CAPTCHA'] === 'Y') {
	?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const form = document.getElementById('form-busca-livre');
				const submitButton = document.getElementById('submit-busca-livre');

				if (form && submitButton) {
					submitButton.addEventListener('click', function(event) {
						event.preventDefault();

						const captchaTokenInput = form.querySelector('[name="cf-turnstile-response"]');
						if (!captchaTokenInput || !captchaTokenInput.value) {
							alert('Por favor, resolva o desafio de segurança antes de continuar.');
							return;
						}

						const validationData = new FormData();
						validationData.append('cf-turnstile-response', captchaTokenInput.value);

						const originalButtonText = submitButton.innerHTML;
						submitButton.innerHTML = 'Verificando...';
						submitButton.disabled = true;

						fetch('valid_captcha.php', {
								method: 'POST',
								body: validationData
							})
							.then(response => response.json())
							.then(data => {
								if (data.success) {
									const formData = new FormData(form);
									const params = new URLSearchParams();
									for (const pair of formData) {
										if (pair[0] !== 'cf-turnstile-response') {
											params.append(pair[0], pair[1]);
										}
									}
									window.location.href = form.action + '?' + params.toString();
								} else {
									alert('The security check has failed. Please try again.');
									// O erro "turnstile is not defined" foi resolvido na versão anterior
									submitButton.innerHTML = originalButtonText;
									submitButton.disabled = false;
								}
							})
							.catch(error => {
								console.error('Erro na validação do CAPTCHA:', error);
								alert('Ocorreu um erro ao verificar a segurança. Tente novamente.');
								submitButton.innerHTML = originalButtonText;
								submitButton.disabled = false;
							});
					});
				}
			});
		</script>
	<?php
	}
	?>


<?php

}

if ($actualScript == "index.php") {
	unset($_REQUEST["base"]);
}
?>
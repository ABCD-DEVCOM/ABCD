<?php
/*
* @file        parametros.php
* @author      Roger Craveiro Guilherme
* @date        2025-10-06
* @description Page for analyzing OPAC search logs.
*
* CHANGE LOG:
* 2025-10-06 rogercgui Added the option to enable CAPTCHA in OPAC.DEF
* 2025-11-10 rogercgui Added Clear Cache functionality and Expand/Collapse All buttons
* 2025-11-16 rogercgui Added file upload capability for logo and shortIcon fields
* 2025-11-16 rogercgui Added robust permission checking to processUpload()
* 2025-11-16 rogercgui Corrected upload logic to save relative paths (uploads/file.png) instead of absolute URLs
*/
include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/global/general";
include "../../common/inc_div-helper.php";

$update_message = ""; // Variável para feedback
$cache_dir_relative = "opac/cache/"; // Relativo ao htdocs (ABCD_scripts_path)
$cache_dir = $ABCD_scripts_path . $cache_dir_relative;

/**
 * Processes the upload of an image file (logo or icon).
 *
 * @param string $file_key The $_FILES key (e.g. “upload_logo”).
 * @param string $new_base_name The base name for saving the file (e.g. “opac_logo”).
 * @param string $upload_dir_physical The full physical path of the directory on the server (e.g. /var/www/htdocs/opac/uploads/).
 * @param string $upload_dir_relative_to_opac The relative path that will be saved in .def (e.g. “uploads/”).
 * @param string &$update_message Reference to the feedback message.
 * @return string|null The new relative URL of the file if the upload is successful, or null if it fails.
 */
function processUpload($file_key, $new_base_name, $upload_dir_physical, $upload_dir_relative_to_opac, &$update_message, $msgstr)
{
	if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != UPLOAD_ERR_OK) {
		return null; // Nenhum arquivo enviado ou erro de upload
	}

	$file = $_FILES[$file_key];
	$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	// Lista de tipos permitidos
	$allowed_types = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'ico'];

	// Validações
	if ($file['size'] > 5 * 1024 * 1024) { // 5MB
		$update_message .= "<div class='alert error'>" . htmlspecialchars($file['name']) . ": " . $msgstr['upload_err_size'] . "</div>";
		return null;
	}
	if (!in_array($file_extension, $allowed_types)) {
		$update_message .= "<div class='alert error'>" . htmlspecialchars($file['name']) . ": " . $msgstr['upload_err_type'] . "</div>";
		return null;
	}

	// 1. Tenta criar o diretório se não existir
	if (!is_dir($upload_dir_physical)) {
		// Suprime o warning do PHP com '@', pois vamos tratar o erro
		if (!@mkdir($upload_dir_physical, 0755, true)) {
			$msg = str_replace('%dir%', "<strong>$upload_dir_physical</strong>", $msgstr['upload_err_mkdir_failed']);
			$update_message .= "<div class='alert error'>$msg</div>";
			return null;
		}
	}

	// 2. Verifica se o diretório é gravável
	if (!is_writable($upload_dir_physical)) {
		$msg = str_replace('%dir%', "<strong>$upload_dir_physical</strong>", $msgstr['upload_err_not_writable']);
		$update_message .= "<div class='alert error'>$msg</div>";
		return null;
	}

	// Garante um nome de arquivo único e limpo
	$new_filename = $new_base_name . '.' . $file_extension;
	$target_path = $upload_dir_physical . $new_filename;

	// 3. Move o arquivo 
	if (move_uploaded_file($file['tmp_name'], $target_path)) {
		return $upload_dir_relative_to_opac . $new_filename;
	} else {
		$update_message .= "<div class='alert error'>" . htmlspecialchars($file['name']) . ": " . $msgstr['upload_err_move'] . "</div>";
		return null;
	}
}


// Lógica para LIMPAR O CACHE
if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "LimparCache") {
	if (is_dir($cache_dir)) {
		$files = glob($cache_dir . "*.cache");
		$count = 0;
		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					if (@unlink($file)) { // Adiciona @ para suprimir erros de permissão
						$count++;
					}
				}
			}
		}
		$update_message = "<div class='alert success'>" . $msgstr["cache_cleared"] . " ($count " . $msgstr["files_deleted"] . " " . $msgstr["de"] . " <strong>" . $cache_dir_relative . "</strong>)</div>";
	} else {
		$update_message = "<div class='alert error'>" . $msgstr["cache_dir_not_found"] . ": <strong>" . $cache_dir . "</strong></div>";
	}
}

// Lógica para SALVAR OS DADOS
if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "Guardar") {
	$file_update = $db_path . "opac_conf/opac.def";

	// Define os caminhos de upload
	// $opac_path vem de conf_opac_top.php (que inclui config_opac.php)

	$upload_dir_relative_to_opac = "uploads/"; // Caminho a ser salvo no .def
	$upload_dir_physical = $ABCD_scripts_path . $opac_path . $upload_dir_relative_to_opac; // Caminho físico no servidor

	// 1. Inicia com os valores dos campos de texto
	$logo_to_save = $_REQUEST['conf_logo'];
	$icon_to_save = $_REQUEST['conf_shortIcon'];

	// 2. Tenta processar upload do LOGO
	$new_logo_url = processUpload('upload_logo', 'opac_logo', $upload_dir_physical, $upload_dir_relative_to_opac, $update_message, $msgstr);
	if ($new_logo_url) {
		$logo_to_save = $new_logo_url; // Sobrescreve se o upload for bem-sucedido
	}

	// 3. Tenta processar upload do ÍCONE
	$new_icon_url = processUpload('upload_shortIcon', 'favicon', $upload_dir_physical, $upload_dir_relative_to_opac, $update_message, $msgstr);
	if ($new_icon_url) {
		$icon_to_save = $new_icon_url;
	}

	// 4. Abre o arquivo .def para escrever
	$fp = fopen($file_update, "w");

	// Garante que os radio buttons/checkboxes desmarcados sejam salvos como "N" ou com valor padrão
	if (!isset($_REQUEST["conf_ONLINESTATMENT"])) $_REQUEST["conf_ONLINESTATMENT"] = "N";
	if (!isset($_REQUEST["conf_WEBRENOVATION"])) $_REQUEST["conf_WEBRENOVATION"] = "N";
	if (!isset($_REQUEST["conf_WEBRESERVATION"])) $_REQUEST["conf_WEBRESERVATION"] = "N";
	if (!isset($_REQUEST["conf_SHOWHELP"])) $_REQUEST["conf_SHOWHELP"] = "N";
	if (!isset($_REQUEST["conf_CAPTCHA"])) $_REQUEST["conf_CAPTCHA"] = "N";
	if (!isset($_REQUEST["conf_RELEVANCE_GATE_ENABLED"])) $_REQUEST["conf_RELEVANCE_GATE_ENABLED"] = "Y";
	if (!isset($_REQUEST["conf_RELEVANCE_CACHE_ENABLED"])) $_REQUEST["conf_RELEVANCE_CACHE_ENABLED"] = "Y";

	$saved_content = "";
	foreach ($_REQUEST as $var => $value) {
		if (substr($var, 0, 5) == "conf_") {
			$param_key = substr($var, 5);

			// Lógica especial para os campos que podem ser upload
			if ($param_key == "logo") {
				$value = $logo_to_save;
			} elseif ($param_key == "shortIcon") {
				$value = $icon_to_save;
			} else {
				$value = trim($value); // Valor normal
			}

			if ($param_key == "OpacHttp" && !empty($value) && substr($value, -1) != "/") {
				$value .= "/";
			}
			// Não grava valores vazios, exceto para campos específicos
			if ($value != "" || in_array($param_key, ["footer", "logo", "link_logo", "shortIcon", "GANALYTICS", "CAPTCHA_SITE_KEY", "CAPTCHA_SECRET_KEY"])) {
				$saved_content .= $param_key . "=" . $value . "\n";
				fwrite($fp, $param_key . "=" . $value . "\n");
			}
		}
	}
	fclose($fp);

	// Se já houver uma msg de erro de upload, concatena. Senão, cria a de sucesso.
	if (empty($update_message)) {
		$update_message = "<div class='alert success'>" . $msgstr["updated"] . "<pre>" . $file_update . "</pre></div>";
	}
	$update_message .= "<pre><code>" . htmlspecialchars($saved_content) . "</code></pre>";

	// Não usamos exit() para permitir que a página recarregue com a mensagem
}

// Carrega as definições atuais do opac.def
$opac_gdef = array();
if (file_exists($db_path . "opac_conf/opac.def")) {
	$opac_gdef = parse_ini_file($db_path . "opac_conf/opac.def", true);
}

// Carrega a definição de UNICODE do abcd.def
$UNICODE = "";
if (file_exists($db_path . "abcd.def")) {
	$fp_abcd = file_get_contents_utf8($db_path . "abcd.def"); // Usa a função segura
	if ($fp_abcd) {
		foreach ($fp_abcd as $value) {
			if (strpos($value, "UNICODE") === 0) {
				$v = explode('=', $value);
				$UNICODE = isset($v[1]) ? trim($v[1]) : "";
				break;
			}
		}
	}
}

// Garante que $opac_path (de config_opac.php) termine com uma barra se não estiver vazio
$opac_path_url = rtrim($opac_path, '/') . (empty($opac_path) ? '' : '/');
// Constrói a URL raiz do OPAC
$opac_base_url = rtrim($server_url, '/') . '/' . $opac_path_url;
?>


<script>
	var idPage = "general";

	function toggleCaptchaFields() {
		var captcha_fields = document.getElementById('captcha_fields');
		if (document.getElementById('captcha_enabled').checked) {
			captcha_fields.style.display = 'block';
		} else {
			captcha_fields.style.display = 'none';
		}
	}

	/**
	 * Função para pré-visualizar a imagem selecionada no input file.
	 */
	function previewImage(event, previewElementId) {
		var reader = new FileReader();
		reader.onload = function() {
			var output = document.getElementById(previewElementId);
			output.src = reader.result;
			output.style.display = 'block'; // Garante que a tag img seja exibida
		}
		if (event.target.files[0]) {
			reader.readAsDataURL(event.target.files[0]);
		}
	}
</script>


<div class="middle form row m-0">
	<div class="formContent col-2 m-2 p-0">
		<?php include("conf_opac_menu.php"); ?>
	</div>
	<div class="formContent col-9 m-2">

		<h3><?php echo $msgstr["parametros"] . " (opac.def)"; ?></h3>

		<?php
		// Exibe a mensagem de feedback AQUI
		if (!empty($update_message)) echo $update_message;
		?>

		<div style="margin: 10px 0;">
			<button type="button" class="bt bt-gray" onclick="toggleAllAccordions(true)"><i class="fas fa-expand-arrows-alt"></i> <?php echo $msgstr["expand_all"]; ?></button>
			<button type="button" class="bt bt-gray" onclick="toggleAllAccordions(false)"><i class="fas fa-compress-arrows-alt"></i> <?php echo $msgstr["collapse_all"]; ?></button>
		</div>


		<form name="parametros" method="post" enctype="multipart/form-data">
			<input type="hidden" name="Opcion" value="Guardar">
			<input type="hidden" name="lang" value="<?php echo $lang; // Mantém o lang no post 
													?>">

			<button type="button" class="accordion"><?php echo $msgstr["id_seo"]; ?></button>
			<div class="panel">
				<div class="formRow">
					<label><?php echo $msgstr["cfg_title_page"]; ?></label>
					<input type="text" name="conf_TituloPagina" value="<?php echo isset($opac_gdef['TituloPagina']) ? htmlspecialchars($opac_gdef['TituloPagina']) : ''; ?>">
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_SITE_DESCRIPTION"]; ?></label>
					<input type="text" name="conf_SITE_DESCRIPTION" value="<?php echo isset($opac_gdef['SITE_DESCRIPTION']) ? htmlspecialchars($opac_gdef['SITE_DESCRIPTION']) : ''; ?>">
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_OpacHttp"]; ?></label>
					<input type="text" name="conf_OpacHttp" value="<?php echo isset($opac_gdef['OpacHttp']) ? htmlspecialchars($opac_gdef['OpacHttp']) : ''; ?>">
					<small><?php echo "Ex: " . (isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : 'http://localhost') . "/opac/"; ?></small>
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_g_analytics"]; ?></label>
					<input type="text" name="conf_GANALYTICS" value="<?php echo isset($opac_gdef['GANALYTICS']) ? htmlspecialchars($opac_gdef['GANALYTICS']) : ''; ?>">
					<small>Ex: G-XXXXXXXXXX</small>
				</div>
			</div>

			<button type="button" class="accordion"><?php echo $msgstr["online_services"]; ?></button>
			<div class="panel">
				<div class="formRow">
					<label><?php echo $msgstr["cfg_ONLINESTATMENT"]; ?></label>
					<input type="radio" name="conf_ONLINESTATMENT" value="Y" <?php if (isset($opac_gdef['ONLINESTATMENT']) && $opac_gdef['ONLINESTATMENT'] == "Y") echo " checked"; ?>> Y &nbsp;&nbsp;
					<input type="radio" name="conf_ONLINESTATMENT" value="N" <?php if (!isset($opac_gdef['ONLINESTATMENT']) || $opac_gdef['ONLINESTATMENT'] != "Y") echo " checked"; ?>> N
					<small><?php echo $msgstr["onlinestatment_isset"]; ?></small>
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_WEBRENOVATION"]; ?></label>
					<input type="radio" name="conf_WEBRENOVATION" value="Y" <?php if (isset($opac_gdef['WEBRENOVATION']) && $opac_gdef['WEBRENOVATION'] == "Y") echo " checked"; ?>> Y &nbsp;&nbsp;
					<input type="radio" name="conf_WEBRENOVATION" value="N" <?php if (!isset($opac_gdef['WEBRENOVATION']) || $opac_gdef['WEBRENOVATION'] != "Y") echo " checked"; ?>> N
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_WEBRESERVATION"]; ?></label>
					<input type="radio" name="conf_WEBRESERVATION" value="Y" <?php if (isset($opac_gdef['WEBRESERVATION']) && $opac_gdef['WEBRESERVATION'] == "Y") echo " checked"; ?>> Y &nbsp;&nbsp;
					<input type="radio" name="conf_WEBRESERVATION" value="N" <?php if (!isset($opac_gdef['WEBRESERVATION']) || $opac_gdef['WEBRESERVATION'] != "Y") echo " checked"; ?>> N
				</div>
			</div>

			<button type="button" class="accordion"><?php echo $msgstr["security"]; ?></button>
			<div class="panel">
				<div class="formRow">
					<label><?php echo $msgstr["enable_captcha"]; ?></label>
					<input type="checkbox" id="captcha_enabled" name="conf_CAPTCHA" value="Y" <?php if (isset($opac_gdef['CAPTCHA']) && $opac_gdef['CAPTCHA'] == "Y") echo " checked"; ?> onclick="toggleCaptchaFields()">
					<small><?php echo $msgstr["exp_captcha"]; ?></small>
				</div>
				<div id="captcha_fields" style="display: <?php echo (isset($opac_gdef['CAPTCHA']) && $opac_gdef['CAPTCHA'] == 'Y') ? 'block' : 'none'; ?>;">
					<div class="info-box">
						<p><?php echo $msgstr["ob_key_cloudflare"]; ?></p>
						<a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank"><?php echo $msgstr["obtain_turnstile"]; ?> &rarr;</a>
					</div>
					<div class="formRow">
						<label><?php echo $msgstr["turnstile_key"]; ?></label>
						<input type="text" name="conf_CAPTCHA_SITE_KEY" value="<?php echo isset($opac_gdef['CAPTCHA_SITE_KEY']) ? htmlspecialchars($opac_gdef['CAPTCHA_SITE_KEY']) : ''; ?>">
					</div>
					<div class="formRow">
						<label>Turnstile Secret Key</label>
						<input type="text" name="conf_CAPTCHA_SECRET_KEY" value="<?php echo isset($opac_gdef['CAPTCHA_SECRET_KEY']) ? htmlspecialchars($opac_gdef['CAPTCHA_SECRET_KEY']) : ''; ?>">
					</div>
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_restricted_opac"]; ?></label>
					<input type="checkbox" id="cfg_restricted_opac" name="conf_RESTRICTED_OPAC" value="Y" <?php if (isset($opac_gdef['RESTRICTED_OPAC']) && $opac_gdef['RESTRICTED_OPAC'] == "Y") echo " checked"; ?>>
					<small><?php echo $msgstr["cfg_exp_restricted_opac"]; ?></small>
				</div>
			</div>

			<button type="button" class="accordion"><?php echo $msgstr['apariencia'] ?></button>
			<div class="panel">
				<div class="formRow">
					<label><?php echo $msgstr["cfg_url_logo"]; ?></label>

					<input type="text" name="conf_logo" id="conf_logo_text" value="<?php echo isset($opac_gdef['logo']) ? htmlspecialchars($opac_gdef['logo']) : ''; ?>" placeholder="<?php echo $msgstr['cfg_logo_url_ph']; ?>">
					<small><?php echo $msgstr['cfg_logo_url_help']; ?></small>

					<?php
					$logo_src = isset($opac_gdef['logo']) ? htmlspecialchars($opac_gdef['logo']) : '';
					$logo_src_url = $logo_src;
					// Se for relativo, constrói a URL absoluta para o preview
					if (!empty($logo_src) && strpos($logo_src, 'http') !== 0) {
						$logo_src_url = $opac_base_url . $logo_src;
					}
					?>
					<div style="margin-top: 10px; padding: 10px; background: #f4f4f4; border: 1px solid #ddd; max-width: 300px; <?php if (empty($logo_src)) echo 'display:none;'; ?>" id="logo_preview_container">
						<strong><?php echo $msgstr['cfg_current_image_preview']; ?>:</strong><br>
						<img id="logo_preview" src="<?php echo $logo_src_url; ?>" alt="Logo Preview" style="max-width: 100%; height: auto; margin-top: 5px;" onerror="this.style.display='none'; document.getElementById('logo_preview_container').style.display='none';">
					</div>

					<label style="margin-top: 10px;"><?php echo $msgstr['cfg_logo_upload']; ?></label>
					<input type="file" name="upload_logo" accept="image/*" onchange="previewImage(event, 'logo_preview'); document.getElementById('logo_preview_container').style.display='block';">
					<small><?php echo $msgstr['cfg_logo_upload_help']; ?></small>
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_link_logo"]; ?></label>
					<input type="text" name="conf_link_logo" value="<?php echo isset($opac_gdef['link_logo']) ? htmlspecialchars($opac_gdef['link_logo']) : ''; ?>">
				</div>

				<div class="formRow">
					<label><?php echo $msgstr["cfg_shortIcon"]; ?></label>

					<input type="text" name="conf_shortIcon" value="<?php echo isset($opac_gdef['shortIcon']) ? htmlspecialchars($opac_gdef['shortIcon']) : ''; ?>" placeholder="<?php echo $msgstr['cfg_icon_url_ph']; ?>">
					<small><?php echo $msgstr['cfg_icon_url_help']; ?></small>

					<?php
					$icon_src = isset($opac_gdef['shortIcon']) ? htmlspecialchars($opac_gdef['shortIcon']) : '';
					$icon_src_url = $icon_src;
					// Se for relativo, constrói a URL absoluta para o preview
					if (!empty($icon_src) && strpos($icon_src, 'http') !== 0) {
						$icon_src_url = $opac_base_url . $icon_src;
					}
					?>
					<div style="margin-top: 10px; padding: 10px; background: #f4f4f4; border: 1px solid #ddd; width: 100px; <?php if (empty($icon_src)) echo 'display:none;'; ?>" id="icon_preview_container">
						<strong><?php echo $msgstr['cfg_current_image_preview']; ?>:</strong><br>
						<img id="icon_preview" src="<?php echo $icon_src_url; ?>" alt="Icon Preview" style="max-width: 100%; height: auto; margin-top: 5px;" onerror="this.style.display='none'; document.getElementById('icon_preview_container').style.display='none';">
					</div>

					<label style="margin-top: 10px;"><?php echo $msgstr['cfg_icon_upload']; ?></label>
					<input type="file" name="upload_shortIcon" accept="image/x-icon, image/png, image/svg+xml, image/jpeg" onchange="previewImage(event, 'icon_preview'); document.getElementById('icon_preview_container').style.display='block';">
					<small><?php echo $msgstr['cfg_icon_upload_help']; ?></small>
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_footer"]; ?></label>
					<input type="text" name="conf_footer" value="<?php echo isset($opac_gdef['footer']) ? htmlspecialchars($opac_gdef['footer']) : ''; ?>">
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_show_help"]; ?></label>
					<input type="radio" name="conf_SHOWHELP" value="Y" <?php if (isset($opac_gdef['SHOWHELP']) && $opac_gdef['SHOWHELP'] == "Y") echo " checked"; ?>> Y &nbsp;&nbsp;
					<input type="radio" name="conf_SHOWHELP" value="N" <?php if (!isset($opac_gdef['SHOWHELP']) || $opac_gdef['SHOWHELP'] != "Y") echo " checked"; ?>> N
				</div>
			</div>

			<button type="button" class="accordion"><?php echo $msgstr["technical_settings"]; ?></button>
			<div class="panel">
				<div class="formRow">
					<label><?php echo $msgstr["cfg_charset"]; ?></label>
					<?php
					$charset_val = isset($opac_gdef['charset']) ? $opac_gdef['charset'] : '';
					if ($charset_val == '' && $UNICODE == 1) {
						$charset_val = "UTF-8";
					}
					?>
					<input type=radio name=conf_charset value=UTF-8 <?php if ($charset_val == "UTF-8") echo " checked" ?>> UTF-8&nbsp; &nbsp;
					<input type=radio name=conf_charset value=ISO-8859-1 <?php if ($charset_val == "ISO-8859-1") echo " checked" ?>> ISO-8859-1
				</div>
			</div>

			<button type="button" class="accordion"><?php echo $msgstr["cfg_search_performance"]; ?></button>
			<div class="panel">
				<div class="formRow">
					<label><?php echo $msgstr["cfg_relevance_gate_enabled"]; ?></label>
					<input type="radio" name="conf_RELEVANCE_GATE_ENABLED" value="Y" <?php if (!isset($opac_gdef['RELEVANCE_GATE_ENABLED']) || $opac_gdef['RELEVANCE_GATE_ENABLED'] == "Y") echo " checked"; ?>> Y &nbsp;&nbsp;
					<input type="radio" name="conf_RELEVANCE_GATE_ENABLED" value="N" <?php if (isset($opac_gdef['RELEVANCE_GATE_ENABLED']) && $opac_gdef['RELEVANCE_GATE_ENABLED'] == "N") echo " checked"; ?>> N
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_relevance_max_scored"]; ?></label>
					<input type="number" name="conf_RELEVANCE_MAX_SCORED" value="<?php echo isset($opac_gdef['RELEVANCE_MAX_SCORED']) ? htmlspecialchars($opac_gdef['RELEVANCE_MAX_SCORED']) : '3500'; ?>" min="1">
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_relevance_cache_enabled"]; ?></label>
					<input type="radio" name="conf_RELEVANCE_CACHE_ENABLED" value="Y" <?php if (!isset($opac_gdef['RELEVANCE_CACHE_ENABLED']) || $opac_gdef['RELEVANCE_CACHE_ENABLED'] == "Y") echo " checked"; ?>> Y &nbsp;&nbsp;
					<input type="radio" name="conf_RELEVANCE_CACHE_ENABLED" value="N" <?php if (isset($opac_gdef['RELEVANCE_CACHE_ENABLED']) && $opac_gdef['RELEVANCE_CACHE_ENABLED'] == "N") echo " checked"; ?>> N
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["cfg_relevance_cache_ttl"]; ?></label>
					<input type="number" name="conf_RELEVANCE_CACHE_TTL" value="<?php echo isset($opac_gdef['RELEVANCE_CACHE_TTL']) ? htmlspecialchars($opac_gdef['RELEVANCE_CACHE_TTL']) : '300'; ?>" min="1">
				</div>
			</div>

			<input type="submit" class="bt-green mt-5" value="<?php echo $msgstr["save"]; ?>">
		</form>

		<div style="margin-top: 20px;">
			<button type="button" class="accordion"><?php echo $msgstr["cache_management"]; ?></button>
			<div class="panel">
				<form name="cacheForm" method="post">
					<input type="hidden" name="Opcion" value="LimparCache">
					<input type="hidden" name="lang" value="<?php echo $lang; ?>">
					<div class="formRow">
						<label><?php echo $msgstr["cache_clear"]; ?></label>
						<small><?php echo $msgstr["cache_clear_desc"]; ?> <strong><?php echo $cache_dir_relative; ?></strong></small>
						<br><br>
						<button type="submit" class="bt bt-red"><?php echo $msgstr["cache_clear_button"]; ?></button>
					</div>
				</form>
			</div>
		</div>

	</div>
</div>

<?php include("../../common/footer.php"); ?>
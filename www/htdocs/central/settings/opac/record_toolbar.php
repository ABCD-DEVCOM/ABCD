<?php
include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/toolbar";
include "../../common/inc_div-helper.php";

// =================================================================
// FUNÇÃO HELPER PARA ESCREVER ARQUIVOS .INI
// =================================================================
/**
 * Escreve um array PHP em um arquivo .ini, preservando seções.
 * @param string $file_path Caminho completo para o arquivo .ini
 * @param array $array Array associativo (com seções) para escrever
 */
function write_ini_file($file_path, $array)
{
	$content = "";
	foreach ($array as $section => $values) {
		$content .= "[" . $section . "]\n";
		if (is_array($values)) { // Garante que $values é um array
			foreach ($values as $key => $value) {
				// Remove aspas para evitar "dupla-aspagem"
				$value_clean = str_replace('"', '', $value);
				$content .= $key . " = \"" . $value_clean . "\"\n";
			}
		}
		$content .= "\n";
	}
	if (file_put_contents($file_path, $content) === false) {
		return false;
	}
	return true;
}
// =================================================================

// Variável para a mensagem de sucesso
$update_message = "";

// Determines the correct path of the file based on the 'base' parameter
if (isset($_REQUEST["base"]) and $_REQUEST["base"] != "" and $_REQUEST["base"] != "META") {
	// Specific database path
	$opac_dir = $db_path . $_REQUEST["base"] . "/opac/" . $lang . "/";
	if (!is_dir($opac_dir)) {
		mkdir($opac_dir, 0777, true);
	}
	$archivo_tab = $opac_dir . "record_toolbar.tab";
	$base = $_REQUEST["base"]; // Variável $base para uso global
} else {
	// Fallback or META search (less common for this config)
	$archivo_tab = $db_path . "opac_conf/" . $lang . "/record_toolbar.tab";
	$base = "META"; // Variável $base para uso global
}

// Function to read the configuration file and return an array
function leer_configuracion_botones($archivo)
{
	$config = [];
	if (file_exists($archivo)) {
		$lines = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			$parts = explode('|', $line);
			if (count($parts) >= 2) {
				$config[$parts[0]] = [
					'enabled' => $parts[1] == 'Y',
					'icon'    => isset($parts[2]) ? $parts[2] : '',
					'label'   => isset($parts[3]) ? $parts[3] : '',
					'extra'   => isset($parts[4]) ? $parts[4] : '' // Usado para a TAG (ex: v1)
				];
			}
		}
	}
	return $config;
}


// SAVE LOGIC
if (isset($_REQUEST["Opcion"]) && $_REQUEST["Opcion"] == "Guardar") {

	// --- 1. Salva o arquivo record_toolbar.tab (Lógica Original) ---
	$fout = fopen($archivo_tab, "w");
	$botones = ['print', 'iso', 'word', 'email', 'reserve', 'bookmark', 'permalink', 'copy'];

	foreach ($botones as $boton) {
		$enabled = (isset($_REQUEST['enabled_' . $boton]) && $_REQUEST['enabled_' . $boton] == 'Y') ? 'Y' : 'N';
		$icon    = $_REQUEST['icon_' . $boton] ?? $boton;
		$label   = $_REQUEST['label_' . $boton] ?? ucfirst($boton);

		// O $extra é a TAG (ex: v1) vinda do campo 'extra_permalink'
		$extra   = $_REQUEST['extra_' . $boton] ?? '';

		fwrite($fout, "$boton|$enabled|$icon|$label|$extra\n");
	}
	fclose($fout);
	// --- Fim da Lógica .tab ---


	// --- 2. Salva o prefixo do Permalink no relevance.def (Nova Lógica) ---
	if ($base != "META" && isset($_REQUEST['permalink_prefix'])) {
		$relevance_file = $db_path . $base . "/opac/relevance.def";

		// Garante que o diretório opac exista
		if (!is_dir(dirname($relevance_file))) {
			mkdir(dirname($relevance_file), 0777, true);
		}

		$config_ini = [];
		if (file_exists($relevance_file)) {
			// Lê o arquivo .ini existente
			$config_ini = parse_ini_file($relevance_file, true);
		}

		// Define ou atualiza o prefixo do permalink
		$config_ini['permalink']['prefix'] = $_REQUEST['permalink_prefix'];

		// Escreve o arquivo .ini de volta no disco
		write_ini_file($relevance_file, $config_ini);
	}
	// --- Fim da Lógica relevance.def ---

	// ===============================================
	// CORREÇÃO: Define a variável da mensagem em vez de dar echo
	// ===============================================
	$update_message = "<div class='alert alert-success'>" . ($msgstr["updated"] ?? "Atualizado com sucesso!") . "</div>";
} // Fim do Opcion=Guardar



// Function to render a row in the form
function render_form_row($button_name, $defaults, $config)
{
	global $msgstr, $db_path, $base; // Traz $db_path e $base para o escopo

	// ===============================================
	// CORREÇÃO: Lê a configuração específica do botão (ex: $config['print']['enabled'])
	// ===============================================
	$enabled = $config[$button_name]['enabled'] ?? false;
	$icon    = $config[$button_name]['icon'] ?? $defaults['icon'];
	$label   = $config[$button_name]['label'] ?? $defaults['label'];
	$extra   = $config[$button_name]['extra'] ?? $defaults['extra']; // Usado para a TAG (ex: v1)

	echo '<tr>';
	echo '<td valign="top"><strong>' . ucfirst($button_name) . '</strong></td>';
	echo '<td valign="top"><input type="checkbox" name="enabled_' . $button_name . '" value="Y" ' . ($enabled ? 'checked' : '') . '></td>';
	echo '<td valign="top"><input type="text" name="icon_' . $button_name . '" value="' . htmlspecialchars($icon) . '"></td>';
	echo '<td valign="top"><input type="text" name="label_' . $button_name . '" value="' . htmlspecialchars($label) . '"></td>';

	// =================================================================
	// LÓGICA MODIFICADA PARA PERMALINK
	// =================================================================
	if ($button_name == 'permalink') {

		// Busca o prefixo salvo no relevance.def
		$relevance_file = $db_path . $base . "/opac/relevance.def";
		$config_ini = [];
		if (file_exists($relevance_file)) {
			$config_ini = parse_ini_file($relevance_file, true);
		}
		$current_prefix = $config_ini['permalink']['prefix'] ?? 'CN_'; // Padrão 'CN_'

		// === CORREÇÃO: Adiciona valores padrão (??) para evitar warnings ===
		$msg_field_tag    = $msgstr["cfg_field_tag"] ?? "Tag do Campo";
		$msg_help_tag     = $msgstr["cfg_help_permalink"] ?? "Tag que contém o ID único. Ex: v1";
		$msg_field_prefix = $msgstr["cfg_field_prefix"] ?? "Prefixo de Índice";
		$msg_help_prefix  = $msgstr["cfg_help_prefix"] ?? "Prefixo do índice (FST) para a chave. Ex: CN_";

		echo '<td valign="top">';
		echo '<strong>' . $msg_field_tag . ':</strong><br>';
		echo '<input type="text" name="extra_permalink" value="' . htmlspecialchars($extra) . '" placeholder="Ex: v1">';
		echo '<br><small>' . $msg_help_tag . '</small>';
		echo '<hr style="margin: 10px 0;">';
		echo '<strong>' . $msg_field_prefix . ':</strong><br>';
		echo '<input type="text" name="permalink_prefix" value="' . htmlspecialchars($current_prefix) . '" placeholder="Ex: CN_">';
		echo '<br><small>' . $msg_help_prefix . '</small>';
		echo '</td>';
	} else {
		// Mantém campos ocultos para outros botões para o save funcionar
		echo '<td><input type="hidden" name="extra_' . $button_name . '" value="' . htmlspecialchars($extra) . '">-</td>';
	}
	// =================================================================
	// FIM DA LÓGICA MODIFICADA
	// =================================================================

	echo '</tr>';
}

// Function to display the button configuration table
function ver_tabla_botones($archivo_tab)
{
	global $msgstr;
	$config_botones = leer_configuracion_botones($archivo_tab);
?>
	<form name="toolbarForm" method="post">
		<input type="hidden" name="Opcion" value="Guardar">
		<input type="hidden" name="base" value="<?php echo $_REQUEST["base"]; ?>">
		<input type="hidden" name="lang" value="<?php echo $_REQUEST["lang"]; ?>">

		<table class="table table-striped table-bordered">
			<thead class="thead-light">
				<tr>
					<th><?php echo $msgstr["cfg_button"] ?? "Botão"; ?></th>
					<th><?php echo $msgstr["cfg_enabled"] ?? "Habilitado"; ?></th>
					<th><?php echo $msgstr["cfg_icon"] ?? "Ícone"; ?> (FontAwesome)</th>
					<th><?php echo $msgstr["cfg_label"] ?? "Rótulo"; ?></th>
					<th><?php echo $msgstr["cfg_extra_param"] ?? "Parâmetro Extra"; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				// Renderiza cada linha do formulário
				render_form_row('print',   ['icon' => 'print',     'label' => 'Imprimir',          'extra' => ''], $config_botones);
				render_form_row('iso',     ['icon' => 'download',  'label' => 'Baixar ISO',        'extra' => ''], $config_botones);
				render_form_row('word',    ['icon' => 'file-word', 'label' => 'MS Word',           'extra' => ''], $config_botones);
				render_form_row('email',   ['icon' => 'envelope',  'label' => 'Enviar por email',    'extra' => ''], $config_botones);
				render_form_row('reserve', ['icon' => 'book',      'label' => 'Reservar',          'extra' => ''], $config_botones);
				//render_form_row('bookmark', ['icon' => 'bookmark', 'label' => 'Salvar seleção',      'extra' => ''], $config_botones);
				//render_form_row('copy',    ['icon' => 'copy',      'label' => 'Copiar para...',    'extra' => ''], $config_botones);
				render_form_row('permalink', ['icon' => 'link',     'label' => 'Permalink',         'extra' => 'v1'], $config_botones);

				?>
			</tbody>
		</table>

		<button type="submit" class="bt bt-green"><?php echo $msgstr["save"]; ?></button>
		<a href="javascript:history.back()" class="bt bt-light"><?php echo $msgstr["cancel"]; ?></a>
	</form>
<?php
} // Fim da função ver_tabla_botones

?>
<script>
	var idPage = "db_configuration";
</script>

<div class="middle form row m-0">
	<div class="formContent col-2 m-2 p-0">
		<?php include("conf_opac_menu.php"); ?>
	</div>
	<div class="formContent col-9 m-2">
		<?php include("menu_dbbar.php"); ?>

		<h3><?php echo $msgstr["cfg_record_toolbar"] ?? "Barra de Ferramentas do Registro"; ?></h3>
		<p><?php echo $msgstr["cfg_record_toolbar_desc"] ?? "Habilitar/desabilitar botões e configurar parâmetros da barra de ferramentas do registro bibliográfico."; ?></p>

		<?php
		// ===============================================
		// CORREÇÃO: Imprime a mensagem de sucesso AQUI, dentro do layout
		// ===============================================
		if (!empty($update_message)) {
			echo $update_message;
		}

		if (isset($archivo_tab)) {
			// Mostra o formulário de edição
			ver_tabla_botones($archivo_tab);
		} else {
			// Mensagem de erro se nenhuma base estiver selecionada
			echo "<div class='alert alert-warning'>Por favor, selecione uma base de dados no menu acima primeiro.</div>";
		}
		?>
	</div>
</div>

<?php include("../../common/footer.php"); ?>
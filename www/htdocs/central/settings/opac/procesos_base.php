<?php
/**
 * Configuração da base de dados OPAC
 * 
 * 
 * 
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_db_config'])) {
	session_start(); 

	$base = $_POST["base"];
	$lang = $_POST["lang"];

	$db_opac_lang_path = $_SESSION["db_path"] . $base . "/opac/" . $lang . "/";
	if (!is_dir($db_opac_lang_path)) {
		mkdir($db_opac_lang_path, 0777, true);
	}

	$new_public_name = trim($_POST['public_name']);
	$bases_dat_file = $_SESSION["db_path"] . "opac_conf/" . $lang . "/bases.dat";
	if (file_exists($bases_dat_file) && is_writable($bases_dat_file)) {
		$lines = file($bases_dat_file, FILE_IGNORE_NEW_LINES);
		$new_lines = [];
		$found = false;
		foreach ($lines as $line) {
			// Compara a base de forma exata (ex: 'marc|' e não 'marc_as|')
			if (substr($line, 0, strlen($base) + 1) === $base . "|") {
				$new_lines[] = $base . "|" . $new_public_name;
				$found = true;
			} else {
				$new_lines[] = $line;
			}
		}
		if ($found) file_put_contents($bases_dat_file, implode("\n", $new_lines));
	}

	file_put_contents($db_opac_lang_path . $base . ".def", trim($_POST['description']));

	$selected_alphabets = isset($_POST['alphabets']) ? $_POST['alphabets'] : [];
	file_put_contents($db_opac_lang_path . $base . ".lang", implode("\n", $selected_alphabets));

	header("Location: procesos_base.php?base=" . urlencode($base) . "&lang=" . urlencode($lang) . "&status=updated");
	exit();
}


include("conf_opac_top.php");

$base = isset($_REQUEST["base"]) ? $_REQUEST["base"] : null;
$lang = isset($_REQUEST["lang"]) ? $_REQUEST["lang"] : null;

$db_opac_lang_path = $base ? $db_path . $base . "/opac/" . $lang . "/" : "";

if ($base) {
	// Carregar nome p?blico
	$public_name = "";
	$bases_dat_file = $db_path . "opac_conf/" . $lang . "/bases.dat";
	if (file_exists($bases_dat_file)) {
		$lines = file($bases_dat_file, FILE_IGNORE_NEW_LINES);
		foreach ($lines as $line) {
			if (substr($line, 0, strlen($base) + 1) === $base . "|") {
				$parts = explode('|', $line, 2);
				$public_name = $parts[1];
				break;
			}
		}
	}

	// Carregar descrição
	$description_file = $db_opac_lang_path . $base . ".def";
	$description = file_exists($description_file) ? file_get_contents($description_file) : "";

	// Carregar alfabetos
	$all_alphabets = get_available_alphabets($db_path, $charset);
	$enabled_alphabets_file = $db_opac_lang_path . $base . ".lang";
	$enabled_alphabets = file_exists($enabled_alphabets_file) ? file($enabled_alphabets_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

	// Lógica do Checklist
	$checklist = [];
	$checklist['free_search'] = file_exists($db_opac_lang_path . $base . '_libre.tab');
	$checklist['buscar_a'] = file_exists($db_opac_lang_path . $base . '_avanzada.tab');
	$checklist['facets'] = file_exists($db_opac_lang_path . $base . '_facetas.dat');
	$checklist['indexes'] = file_exists($db_opac_lang_path . $base . '.ix');
	$checklist['toolbar'] = file_exists($db_opac_lang_path . 'record_toolbar.tab');
	$checklist['collections'] = file_exists($db_opac_lang_path . $base . '_colecciones.tab');
	$formats_file = $db_opac_lang_path . $base . '_formatos.dat';
	$checklist['formats'] = file_exists($formats_file) && (strpos(file_get_contents($formats_file), '|Y') !== false);
	$dic_file = $db_path . $base . "/opac/" . $base . ".dic";
	$relevance_file = $db_path . $base . "/opac/relevance.def";
	$checklist['cfg_relevance_configuration'] = file_exists($relevance_file);
	$checklist['dictionary'] = file_exists($dic_file);
	$checklist['dictionary_date'] = $checklist['dictionary'] ? date("Y-m-d H:i:s", filemtime($dic_file)) : null;
}

$wiki_help = "OPAC-ABCD_Configuraci%C3%B3n_de_bases_de_datos";
include "../../common/inc_div-helper.php";
?>
<script>
	var idPage = "db_configuration";
</script>

<div class="middle form row m-0">
	<div class="formContent col-2 m-2 p-0">
		<?php include("conf_opac_menu.php"); ?>
	</div>
	<div class="formContent col-9 m-2">
		<?php
		if (!$base) {
			echo "<div class='alert alert-info'>" . $msgstr['cfg_db_select_db_to_start'] . "</div>";
		} else {
			include("menu_dbbar.php");
		?>
			<h3><?php echo $msgstr["db_configuration"] . ": " . $public_name . " (" . $base . ")"; ?></h3>

			<?php
			if (isset($_GET['status']) && $_GET['status'] == 'updated') {
				echo '<div class="alert success">' . $msgstr["updated"] . '</div>';
			}
			?>

			<form method="POST" action="procesos_base.php">
				<input type="hidden" name="base" value="<?php echo htmlspecialchars($base); ?>">
				<input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
				<h4><?php echo $msgstr['cfg_general_db_info']; ?></h4>
				<div class="formRow">
					<label><?php echo $msgstr['db_name']; ?></label>
					<input type="text" name="public_name" value="<?php echo htmlspecialchars($public_name); ?>" class="col-6">
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["db_desc"]; ?></label>
					<textarea name="description" class="col-6" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
				</div>
				<div class="formRow">
					<label><?php echo $msgstr["avail_db_lang"]; ?></label>
					<div class="col-6" style="display: flex; flex-wrap: wrap; gap: 15px;">
						<?php foreach ($all_alphabets as $alpha_file):
							$alpha_name = basename($alpha_file, ".tab");
						?>
							<div>
								<input class="my-4" type="checkbox" name="alphabets[]" value="<?php echo $alpha_name; ?>" id="alpha_<?php echo $alpha_name; ?>" <?php echo in_array($alpha_name, $enabled_alphabets) ? 'checked' : ''; ?>>
								<label for="alpha_<?php echo $alpha_name; ?>"><?php echo $alpha_name; ?></label>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<button type="submit" name="save_db_config" class="bt bt-green"><i class="far fa-save"></i> <?php echo $msgstr['save']; ?></button>
			</form>

			<hr>

			<h4><?php echo $msgstr['cfg_db_checklist']; ?></h4>
			<table class="table striped col-12">
				<thead>
					<tr>
						<th><?php echo $msgstr['cfg_item']; ?></th>
						<th><?php echo $msgstr['tit_status']; ?></th>
						<th><?php echo $msgstr['cfg_file_path']; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					function render_row($label, $status, $path, $link = "", $extra_info = "")
					{
						$status_icon = $status
							? '<i class="fas fa-check-circle color-green"></i> OK'
							: '<i class="fas fa-times-circle color-red"></i> ' . $GLOBALS['msgstr']['missing'];
						echo "<tr><td><a href=\"$link\"><i class=\"fas fa-eye\"></i> $label</a></td><td>$status_icon $extra_info</td><td><small><code>$path</code></small></td></tr>";
					}

					render_row($msgstr['free_search'], $checklist['free_search'], $db_opac_lang_path . $base . '_libre.tab', "javascript: SeleccionarProceso('edit_form-search.php', '$base', 'libre')");
					render_row($msgstr['buscar_a'], $checklist['buscar_a'], $db_opac_lang_path . $base . '_avanzada.tab', "javascript: SeleccionarProceso('edit_form-search.php', '$base', 'avanzada')");
					render_row($msgstr['facetas'], $checklist['facets'], $db_opac_lang_path . $base . '_facetas.dat', "javascript: SeleccionarProceso('facetas_cnf.php', '$base')");
					render_row($msgstr['select_formato'], $checklist['formats'], $db_opac_lang_path . $base . '_formatos.dat', "javascript: SeleccionarProceso('formatos_salida.php', '$base')");
					render_row($msgstr['indice_alfa'], $checklist['indexes'], $db_opac_lang_path . $base . '.ix', "javascript: SeleccionarProceso('alpha_ix.php', '$base')");
					render_row($msgstr['rtb'], $checklist['toolbar'], $db_opac_lang_path . 'record_toolbar.tab', "javascript: SeleccionarProceso('record_toolbar.php', '$base')");
					render_row($msgstr['tipos_registro'], $checklist['collections'], $db_opac_lang_path . $base . '_colecciones.tab', "javascript: SeleccionarProceso('tipos_registro.php', '$base')");
					render_row($msgstr['cfg_relevance_configuration'], $checklist['cfg_relevance_configuration'], $db_path . $base . '/opac/relevance.tab', "javascript: SeleccionarProceso('edit_relevance.php', '$base')");
					render_row($msgstr['static_dictionary_title'], $checklist['dictionary'], $db_path . $base . "/opac/" . $base . ".dic", "view_dic.php?base=" . $base, $checklist['dictionary_date'] ? "(" . $msgstr['updated_on'] . " " . $checklist['dictionary_date'] . ")" : "");
					?>
				</tbody>
			</table>

			<hr>

			<h4><?php echo $msgstr["cfg_links_to_db_config"] ?></h4>
			<ul>
				<li><a href="javascript:SeleccionarProceso('dbn_par.php','<?php echo $base ?>')"><?php echo $msgstr["dbn_par"]; ?></a></li>

				<li><a href="javascript:SeleccionarProceso('autoridades.php','<?php echo $base ?>')"><?php echo $msgstr["aut_opac"]; ?></a></li>
				<li><a href="javascript:SeleccionarProceso('presentacion_base.php','<?php echo $base ?>')"><?php echo $msgstr["base_home"]; ?></a></li>
				<li><?php echo $msgstr["export_xml"]; ?>
					<ul>
						<li><a href="javascript:SeleccionarProceso('xml_marc.php','<?php echo $base ?>')"><?php echo $msgstr["xml_marc"]; ?></a></li>
						<li><a href="javascript:SeleccionarProceso('xml_dc.php','<?php echo $base ?>')"><?php echo $msgstr["xml_dc"]; ?></a></li>
					</ul>
				</li>
			</ul>
		<?php } // Fim do if($base) 
		?>
	</div>
</div>

<?php include("../../common/footer.php"); ?>
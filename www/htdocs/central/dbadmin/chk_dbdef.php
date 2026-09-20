<?php

/**
 * Name: chk_dbdef.php
 * Description: Checks the database settings (PFT, DEF and Ayudas files).
 * Identifies missing language directories and manages the structural copying 
 * of templates from languages available in the system.
 * Maintainer: Roger Craveiro Guilherme
 * 
 * History:
 * - 2026-09-20 rogercgui IIntegration of the language workflow with LanguageManager,
 *   refactoring of directory reading and copying (replacing 'scandir' with 'glob'),
 *   removal of hard-coded values for internationalisation and modernisation of the modal interface.
 * - 2022-02-03 (fho4abcd): Inclusão de backbutton e div-helper.
 */


session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"] = "en";
include("../common/get_post.php");
if (!isset($arrHttp)) $arrHttp = array();
include("../config.php");
$lang = $_SESSION["lang"];
include("../common/header.php");
include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/dbadmin.php");
include("../lang/reports.php");



if (!isset($_SESSION["login"]) or $_SESSION["profile"] != "adm") {
	echo "<script>
	      alert('" . $msgstr["invalidright"] . "')
          history.back();
          </script>";
	die;
}
$Permiso = $_SESSION["permiso"];
?>


<form name="ReloadSite" method="post">
	<input type="hidden" name=encabezado value="s">
	<input type="hidden" name=base value="<?php echo $arrHttp["base"]; ?>">
</form>

<script>
	function ReloadSite() {
		document.ReloadSite.encabezado.value = 's';
		document.ReloadSite.base.value = '<?php echo $arrHttp["base"]; ?>';
		document.ReloadSite.submit();
	}

	function Update(Option) {
		switch (Option) {
			case "pft":
				document.ReloadSite.action = "pft.php"
				break;
		}
		document.ReloadSite.submit()
	}
</script>


<?php include("../common/institutional_info.php"); ?>

<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["chk_dbdef"] . ": " . $arrHttp["base"]; ?>
	</div>
	<div class="actions">
		<?php
		$backtoscript = "../dbadmin/menu_modificardb.php";
		include "../common/inc_back.php";
		?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php include("submenu_dbadmin.php"); ?>
<?php include "../common/inc_div-helper.php"; ?>

<div class="middle">
	<div class="formContent">

		<h2><?php echo $msgstr["chk_dbdef"]; ?></h2>

		<h3><?php echo $msgstr["def_lang"]; ?> (lang.tab)</h3>

		<?php
		//Checks the languages available in the installation
		$lang_tab = array();
		$total_files = 0;

		$centralPath = dirname(__DIR__);
		$contentPath = isset($msg_path) ? $msg_path : $db_path;

		$langManager = new \ABCD\Common\LanguageManager($centralPath, $contentPath);

		try {
			$a = $langManager->getLangListFile($_SESSION["lang"]);
			$fp = file($a, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

			foreach ($fp as $value) {
				$v = explode('=', $value);
				if (isset($v[0]) && isset($v[1])) {
					$lang_tab[trim($v[0])] = trim($v[1]);
					echo "<li>" . trim($v[1]) . "</li>";
				}
			}
		} catch (\Exception $e) {
			echo "<li>Aviso: " . $e->getMessage() . "</li>";
		}

		$ll_t = $lang_tab;

		// Verifica as PFTs existentes
		function pft_exist($db_path, $arrHttp)
		{
			global $msgstr, $lang_tab;
			$base_dir = $db_path . $arrHttp["base"] . "/pfts";
			$has_options = false;
			$options_html = "";

			if (isset($lang_tab) && is_array($lang_tab)) {
				foreach ($lang_tab as $k => $v) {
					$check_dir = $base_dir . "/" . $k;
					if (is_dir($check_dir)) {
						$files = glob($check_dir . '/*.*');
						if ($files !== false && count($files) > 0) {
							$options_html .= '<option value="/pfts/' . $k . '">' . $v . '</option>';
							$has_options = true;
						}
					}
				}
			}

			if ($has_options) {
				echo '<label>' . (isset($msgstr["copyfrom"]) ? $msgstr["copyfrom"] : "copyfrom") . '</label><br>';
				echo "<select name='source' required>";
				echo '<option value=""></option>';
				echo $options_html;
				echo '</select>';
				echo '<br><button id="ButtoncopyPFT" class="bt bt-blue" type="submit">' . $msgstr["cf_copyfolder"] . ' PFTs</button>';
			}
		}

		// Verifica as DEFs existentes
		function def_exist($db_path, $arrHttp)
		{
			global $msgstr, $lang_tab;
			$base_dir = $db_path . $arrHttp["base"] . "/def";
			$has_options = false;
			$options_html = "";

			if (isset($lang_tab) && is_array($lang_tab)) {
				foreach ($lang_tab as $k => $v) {
					$check_dir = $base_dir . "/" . $k;
					if (is_dir($check_dir)) {
						$files = glob($check_dir . '/*.*');
						if ($files !== false && count($files) > 0) {
							$options_html .= '<option value="/def/' . $k . '">' . $v . '</option>';
							$has_options = true;
						}
					}
				}
			}

			if ($has_options) {
				echo '<label>' . (isset($msgstr["copyfrom"]) ? $msgstr["copyfrom"] : "copyfrom") . '</label><br>';
				echo '<select name="source" required>';
				echo '<option value=""></option>';
				echo $options_html;
				echo '</select>';
				echo '<br><button id="ButtoncopyDEF" class="bt bt-blue" type="submit">' . $msgstr["cf_copyfolder"] . ' DEF</button>';
			}
		}

		// Verifica as ajudas existentes
		function help_exist($db_path, $arrHttp)
		{
			global $msgstr, $lang_tab;
			$base_dir = $db_path . $arrHttp["base"] . "/ayudas";
			$has_options = false;
			$options_html = "";

			if (isset($lang_tab) && is_array($lang_tab)) {
				foreach ($lang_tab as $k => $v) {
					$check_dir = $base_dir . "/" . $k;
					if (is_dir($check_dir)) {
						$files = glob($check_dir . '/*.*');
						if ($files !== false && count($files) > 0) {
							$options_html .= '<option value="/ayudas/' . $k . '">' . $v . '</option>';
							$has_options = true;
						}
					}
				}
			}

			if ($has_options) {
				echo '<label>' . (isset($msgstr["copyfrom"]) ? $msgstr["copyfrom"] : "copyfrom") . '</label><br>';
				echo '<select name="source" required>';
				echo '<option value=""></option>';
				echo $options_html;
				echo '</select>';
				echo '<br><button id="ButtoncopyHELP" class="bt bt-blue" type="submit">' . $msgstr["cf_copyfolder"] . ' Ayudas</button>';
			}
		}
		?>

		<br>

		<h3><?php echo $msgstr["cf_label_dir"]; ?></h3>
		<table class="listTable browse">
			<tr>
				<th><?php echo $msgstr["cf_prfix"]; ?></th>
				<th><?php echo $msgstr["cf_languages"]; ?></th>
				<th><?php echo $msgstr["cf_pftfiles"]; ?></th>
				<th><?php echo $msgstr["cf_deffiles"]; ?></th>
				<th><?php echo $msgstr["cf_ayudas"]; ?></th>
			</tr>

			<?php
			$ixid = 0;
			foreach ($lang_tab as $v => $value) {

				$folder_pfts = $db_path . $arrHttp["base"] . "/pfts/$v";
				$files_pfts = glob($folder_pfts . '/*.*');
				$files_pfts_count = ($files_pfts !== false) ? count($files_pfts) : 0;

				$folder_def = $db_path . $arrHttp["base"] . "/def/$v";
				$files_def = glob($folder_def . '/*.*');
				$files_def_count = ($files_def !== false) ? count($files_def) : 0;

				$folder_help = $db_path . $arrHttp["base"] . "/ayudas/$v";
				$files_help = glob($folder_help . '/*.*');
				$files_help_count = ($files_help !== false) ? count($files_help) : 0;

				echo "<tr><td>" . $v . " </td><td> " . $value . "</td>";

				$ixid = $ixid + 1;

				// PFTs
				if (is_dir($folder_pfts) && $files_pfts_count > 0) {
					echo "<td>";
					$total_files = $files_pfts_count;
					echo $total_files . " " . $msgstr["cf_files"];
					echo "</td>";
				} else {
			?>
					<td>
						<form name="maintenancePFT" method="post">
							<input type="hidden" name="encabezado" value="s">
							<input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
							<input type="hidden" name="folder" value="<?php echo $db_path . $arrHttp["base"]; ?>">
							<input type="hidden" name="destiny" value="/pfts/<?php echo $v; ?>">
							<label><?php echo $msgstr["falta"]; ?></label><br>
							<?php echo pft_exist($db_path, $arrHttp); ?>
						</form>
					</td>
				<?php
				}

				// DEFs
				if (is_dir($folder_def) && $files_def_count > 0) {
					echo "<td>";
					echo $files_def_count . " " . $msgstr["cf_files"];
					echo "</td>";
				} else {
				?>
					<td>
						<form name="maintenance" method="post">
							<input type="hidden" name="encabezado" value="s">
							<input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
							<input type="hidden" name="folder" value="<?php echo $db_path . $arrHttp["base"]; ?>">
							<input type="hidden" name="destiny" value="/def/<?php echo $v; ?>">
							<label><?php echo $msgstr["falta"]; ?></label><br>
							<?php echo def_exist($db_path, $arrHttp); ?>
						</form>
					</td>
				<?php
				}

				// Ayudas
				if (is_dir($folder_help) && $files_help_count > 0) {
					echo "<td>";
					echo $files_help_count . " " . $msgstr["cf_files"];
					echo "</td>";
				} else {
				?>
					<td>
						<form name="maintenance" method="post">
							<input type="hidden" name="encabezado" value="s">
							<input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
							<input type="hidden" name="folder" value="<?php echo $db_path . $arrHttp["base"]; ?>">
							<input type="hidden" name="destiny" value="/ayudas/<?php echo $v; ?>">
							<label><?php echo $msgstr["falta"]; ?></label><br>
							<?php echo help_exist($db_path, $arrHttp); ?>
						</form>
					</td>
			<?php
				}
			}
			?>
		</table>

		<h3><?php echo $msgstr["analyzing"] . " PFTs "; ?></h3>
		<?php
		//Analyses available formats in each language
		foreach ($lang_tab as $v => $value) {
			echo "<div>";
			if (is_dir($db_path . $arrHttp["base"] . "/pfts/$v")) {
				echo "<p><b>Arquivo: " . $arrHttp["base"] . "/pfts/$v";
				Analizar('pfts', $db_path, $arrHttp["base"], $v);
			}
			echo "</div>";
		}
		?>

		<br><br>

		<h3><?php echo $msgstr["analyzing"] . " DEFs "; ?></h3>
		<?php
		//Analyses available formats in each language
		foreach ($lang_tab as $v => $value) {
			if (is_dir($db_path . $arrHttp["base"] . "/def/$v")) {
				echo "<p><b>Arquivo: " . $arrHttp["base"] . "/def/$v";
				echo "<div style='max-height: 250px; overflow-y: scroll;'>";
				Analizar('def', $db_path, $arrHttp["base"], $v);
			}

			echo "</div>";
		}
		?>



	</div> <!--./formContent-->

</div> <!--./middle-->



<!-- Modal de Sucesso Customizado -->
<style>
	.modern-modal {
		display: none;
		position: fixed;
		z-index: 9999;
		left: 0;
		top: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.6);
		backdrop-filter: blur(4px);
	}

	.modern-modal-content {
		background-color: #ffffff;
		margin: 10vh auto;
		padding: 40px 30px;
		border-radius: 8px;
		width: 100%;
		max-width: 420px;
		box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
		text-align: center;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
	}

	.modern-modal-icon {
		background-color: #28a745;
		color: white;
		font-size: 32px;
		width: 64px;
		height: 64px;
		line-height: 64px;
		border-radius: 50%;
		margin: 0 auto 20px auto;
		display: inline-block;
	}

	.modern-modal-title {
		font-size: 22px;
		color: #2c3e50;
		margin: 0 0 10px 0;
		font-weight: 600;
	}

	.modern-modal-text {
		font-size: 15px;
		color: #6c757d;
		margin-bottom: 30px;
		line-height: 1.5;
	}

	.modern-modal-btn {
		background-color: #007bff;
		color: white;
		border: none;
		padding: 12px 30px;
		font-size: 16px;
		font-weight: 500;
		border-radius: 4px;
		cursor: pointer;
		transition: background-color 0.2s ease;
	}

	.modern-modal-btn:hover {
		background-color: #0056b3;
	}
</style>

<div id="confirm" class="modern-modal">
	<div class="modern-modal-content">
		<div class="modern-modal-icon">&#10003;</div>
		<h2 class="modern-modal-title"><?php echo isset($msgstr["process_ok"]) ? $msgstr["process_ok"] : "Processo concluído"; ?></h2>
		<p class="modern-modal-text"><?php echo isset($msgstr["files_copied"]) ? $msgstr["files_copied"] : "Total de arquivos copiados:"; ?> <b id="copied_count_text">0</b></p>
		<button class="modern-modal-btn" onclick="document.ReloadSite.submit();"><?php echo isset($msgstr["actualizar"]) ? $msgstr["actualizar"] : "Atualizar página"; ?></button>
	</div>
</div>

<form name=forma1 method=post action=../dataentry/imprimir_g.php onsubmit="Javascript:return false">
	<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
	<input type=hidden name=cipar value=<?php echo $arrHttp["base"] ?>.par>
	<input type=hidden name=Modulo value=<?php if (isset($arrHttp["Modulo"])) echo $arrHttp["Modulo"] ?>>
	<input type=hidden name=tagsel>
	<input type=hidden name=Opcion>
	<input type=hidden name=vp>
</form>

<?php
include("../common/footer.php");

function Analizar($tipo, $db_path, $base, $lang)
{
	global $msgstr;
	switch ($tipo) {
		case 'pfts':
			$file = "formatos.dat";
			echo "/$file</b></p>";
			if (!file_exists($db_path . $base . "/pfts/$lang/" . $file)) {
				echo "<font color=red>" . $msgstr["falta"] . " " . $file . "</font><br>";
			} else {
				$fp = file($db_path . $base . "/pfts/$lang/" . $file);
				foreach ($fp as $value) {
					$value = trim($value);
					if ($value != "") {
						echo $value;
						$v = explode('|', $value);
						$pft = trim($v[0]) . ".pft";
						if ($lang == $_SESSION["lang"]) {
							echo " Ver: <a href=javascript:LeerArchivo_$v[0](\"\")>" . $pft . "</a>";
						}
						if (!file_exists($db_path . $base . "/pfts/$lang/$pft"))
							echo '<font color=red> <a href=javascript:Update("pft")>' . $msgstr['falta'] . '</a></font>';
						echo "<br>";
?>
						<script type="text/javascript">
							function LeerArchivo_<?php echo $v[0]; ?>(Opcion) {
								msgwin = window.open("leertxt.php?base=<?php echo $base; ?>&cipar=<?php echo $base; ?>.par&lang=en&pft=s&archivo=<?php echo $pft; ?>", "editar", "menu=no,status=yes, resizable, scrollbars,width=790")
								msgwin.focus()
							}
						</script>
<?php
					}
				}
			}
			break;

		case 'def':
			$def = $db_path . $base . "/def/" . $lang . "/";
			echo "</b>";
			if ($handle = @opendir($def)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						echo "<li>$entry</li>";
					}
				}
				closedir($handle);
			}

			break;
	}
}

// Lógica de cópia e disparo do modal
if (isset($_POST['source']) != "" and isset($_POST['destiny']) != "" and isset($_POST['folder'])) {
	$src = $_POST['folder'] . $_POST['source'];
	$dst = $_POST['folder'] . $_POST['destiny'];
	$copied_count = copy_directory($src, $dst);
	reload_dbdef($copied_count);
}

function chmod_Recursive($path, $filemode)
{
	if (!is_dir($path)) {
		return chmod($path, $filemode);
	}
	$dh = opendir($path);
	while ($file = readdir($dh)) {
		if ($file != '.' && $file != '..') {
			$fullpath = $path . '/' . $file;
			if (!is_dir($fullpath)) {
				if (!chmod($fullpath, $filemode)) {
					return false;
				}
			} else {
				if (!chmod_Recursive($fullpath, $filemode)) {
					return false;
				}
			}
		}
	}
	closedir($dh);
	return chmod($path, $filemode);
}

// Função reescrita para resolver bug de diretórios aninhados e retornar contagem
function copy_directory($src, $dst)
{
	$dir = @opendir($src);
	if (!$dir) return 0;
	@mkdir($dst);
	chmod_Recursive($dst, 0777);
	$count = 0;

	while (false !== ($file = readdir($dir))) {
		if (($file != '.') && ($file != '..')) {
			if (is_dir($src . '/' . $file)) {
				$count += copy_directory($src . '/' . $file, $dst . '/' . $file);
			} else {
				if (copy($src . '/' . $file, $dst . '/' . $file)) {
					chmod($dst . '/' . $file, 0777);
					$count++;
				}
			}
		}
	}
	closedir($dir);
	return $count;
}

// Injeta o número real de arquivos copiados no HTML do Modal
function reload_dbdef($count)
{
	global $arrHttp;
	$_POST['base'] = $arrHttp["base"];
	$_POST['encabezado '] = "s";
	$_POST['folder'] = "";
	$_POST['source'] = "";
	$_POST['destiny'] = "";
	echo "<script type='text/JavaScript'> 
		document.getElementById('copied_count_text').innerText = '" . $count . "';
		document.getElementById('confirm').style.display='block'; 
	</script>";
	exit();
}
?>
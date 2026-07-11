<?php
/* Modifications
20210428 fho4abcd System info has latest release & date (not dynamic, so must be fixed every release)
20210610 fho4abcd update date. Remove wiki (done by URL1 and all pages
20210626 fho4abcd MOve logo from css to php +span to title.
20220316 fho4abcd remove duplicate target,empty lines, confusing spacing in html
20220322 fho4abcd date&release comment
20240519 fho4abcd Improve version info
20250316 fho4abcd Hover over responsible logo shows responsible text
20250901 rogercgui Added update notification bar if user is logged in and new version is available
20250902 rogercgui Removal of the version number from the htdocs/version.php file
20251223 fho4abcd Remove inclusion of css (done by includer)
20260325 rogercgui Place the language selector in the centre of the footer. The selector is hidden on subpages due to the current structure of ABCD; changing languages is not possible on all pages.
*/

require_once(dirname(__FILE__) . "/../config.php");
$def = parse_ini_file($db_path . "/abcd.def");
require_once(__DIR__ . '/../../version.php');
//print_r($def);

require_once 'version_checker.php';

// Call the function to check for updates
$update_info = checkForABCDUpdate(ABCD_VERSION);

// --- Lógica para o seletor de idiomas ---
if (isset($_REQUEST['base'])) {
	$selbase = $_REQUEST['base'];
} else {
	$selbase = "";
}

$config_show_lang = isset($def["SHOW_LANG_SELECTOR"]) ? $def["SHOW_LANG_SELECTOR"] : "Y";
$current_script = basename($_SERVER['PHP_SELF']);
$allowed_scripts = array('homepage.php', 'inicio.php');

if (in_array($current_script, $allowed_scripts) && $config_show_lang == "Y") {
	$show_lang_selector = "Y";
} else {
	$show_lang_selector = "N";
}
// ----------------------------------------
?>

<footer class="footer">
	<div class="systemInfo">
		<span class="institutionName">
			<a href="<?php
						if (isset($def["INSTITUTION_URL"])) {
							echo $def["INSTITUTION_URL"];
						} else {
							echo "//abcd-community.org";
						} ?>" target="_blank">
				<?php
				function randomName()
				{
					$names = array(
						'Automatisaci&oacute;n de Bibliot&eacute;cas y Centros de Documentaci&oacute;n',
						'Automation des Biblioth&eacute;ques et Centres de Documentacion',
						'Automatiza&ccedil;&atilde;o das Bibliotecas e dos Centros de Documenta&ccedil;&atilde;o',
						'Automatisering van Bibliotheken en Centra voor Documentatie',
						// and so on
					);
					return $names[rand(0, count($names) - 1)];
				}
				if (isset($def["INSTITUTION_NAME"])) {
					echo $def["INSTITUTION_NAME"];
				} else {
					echo "ABCD | " . randomName();
				} ?>
			</a>
		</span>
		<?php
		if ((isset($def["RESPONSIBLE_URL"])) && (isset($def["RESPONSIBLE_NAME"]))) {
			$url1 = $def["RESPONSIBLE_URL"];
			echo "<span><small><a href=" . $def["RESPONSIBLE_URL"] . " target=_blank>" . $def["RESPONSIBLE_NAME"] . "</a></small></span>";
		} elseif (isset($def["RESPONSIBLE_URL"])) {
			echo "<span><small><a href=" . $def["RESPONSIBLE_URL"] . " target=_blank>" . $def["RESPONSIBLE_URL"] . "</a></small></span>";
		} else {
			echo "<span><small><a href=\"https://github.com/ABCD-DEVCOM/ABCD2\" target=_blank>Developed by the ABCD Community</a></small></span>";
		}
		if (isset($def["URL2"])) {
			$url2 = $def["URL2"];
		} else {
			$url2 = "URL2";
		}
		if (isset($def["TEXT2"])) {
			$text2 = $def["TEXT2"];
		} else {
			$text2 = "TEXT2";
		}

		// Proteção adicionada para $msgstr
		$vers_label = isset($msgstr["version"]) ? $msgstr["version"] : "Version";
		$versioninfo = $vers_label . ": " . ABCD_VERSION . " + ... &rarr; " . DATE_VERSION;
		?>

		<span><small><a href="https://abcd-devcom.github.io/" target="_blank">ABCD Knowledge Base</a> - <?php echo $versioninfo ?> </small></span>
	</div>

	<div class="footer-center"></div>

	<div class="distributorLogo" style="display: flex;flex-direction: column;align-items: flex-end;justify-content: center;gap: 5px;margin-top: 5px;">
		<a href="//abcd-community.org" target="_blank">
			<img src='/assets/images/distributorLogo.png?" . time() . "' title='ABCD Community'>
		</a>
		<?php if ($show_lang_selector == "Y") { ?>
			<span class="footer-lang-wrapper">
				<i class="fas fa-globe"></i>
				<form name="cambiolang" style="display:inline-block; margin:0;" accept-charset=utf-8>
					<input type="hidden" name="base" value="<?php echo htmlspecialchars($selbase); ?>">
					<input type="hidden" name="cipar" value="">
					<input type="hidden" name="marc" value="">
					<input type="hidden" name="tlit" value="">
					<input type="hidden" name="nreg" value="">

					<select name="lenguaje" class="footer-lang-select" onchange="CambiarLenguaje()" title='<?php echo isset($msgstr["seleccionar"]) ? $msgstr["seleccionar"] : "Select"; ?> <?php echo isset($msgstr["lang"]) ? $msgstr["lang"] : "Language"; ?>'>
						<?php
						if (file_exists("inc_get-langtab.php")) {
							include_once "inc_get-langtab.php";
						}

						if (function_exists('get_langtab')) {
							$a = get_langtab();
							if ($a != "") {
								$fp = file($a);
								$selected = "";
								$bom = "\xef\xbb\xbf";
								echo "<option title='' value=''></option>";
								foreach ($fp as $value) {
									$value = trim($value);
									if ($value != "") {
										$larr = explode('=', $value);
										if ($larr[0] != "lang") {
											$langval = trim($larr[0]);
											$langval = str_replace($bom, "", $langval);
											$trvalue = trim($larr[1]);

											if (function_exists('mb_check_encoding') && mb_check_encoding($trvalue, 'UTF-8')) {
												$trvalue_display = htmlentities($trvalue, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
											} else {
												$trvalue_display = htmlentities($trvalue, ENT_COMPAT | ENT_IGNORE, 'ISO-8859-1');
											}

											$langses = isset($_SESSION["lang"]) ? $_SESSION["lang"] : "";
											if ($langval == $langses) $selected = " selected";

											echo "<option value=\"$langval\" $selected title=\"$trvalue_display\">" . $trvalue_display . "</option>\n";
											$selected = "";
										}
									}
								}
							}
						}
						?>
					</select>
				</form>
			</span>
		<?php } ?>

	</div>
	<div class="spacer">&#160;</div>

	<script src="/assets/js/main.js"></script>


</footer>

</body>

</html>

<?php

echo abcd_run_hook('abcd_footer_end', '');

if ((!isset($def["CHECK_VERSION"])) || ($def["CHECK_VERSION"] != "N")) {
	if (isset($_SESSION["permiso"])) { // Verifies if the user is logged in
		if (isset($update_info) && $update_info['update_available']): ?>

			<div id="update-notification" style="bottom: 0; margin: 0 0 0 0; width: 100%; background-color: #ffc107; color: #333; text-align: center; z-index: 9999; border-top: 1px solid #e0a800;">
				Update now (<strong><?php echo htmlspecialchars($update_info['new_version']); ?></strong>) ABCD is available!
				<a href="/update_manager.php" style="color: #0056b3; text-decoration: underline; font-weight: bold;">Update now</a>.
			</div>

<?php endif;
	}
}

?>
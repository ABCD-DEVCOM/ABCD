<?php
/*
20220123 fho4abcd buttons+div-helper
20220715 fho4abcd Use $actparfolder as location for .par files, note only acces databases
20240519 fho4abcd Correct $loc_actparfolder while deleting a profile
20260523 rogercgui Interface Refactoring - Dynamic Accordions, Administration Scope Correction, and UX
*/

global $arrHttp;
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}

include("../common/get_post.php");
include("../config.php");
include("../common/header.php");
$lang = $_SESSION["lang"];
include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/profile.php");

echo "<body>\n";
if (isset($arrHttp["encabezado"])) {
	include("../common/institutional_info.php");
	$encabezado = "&encabezado=s";
} else {
	$encabezado = "";
}
?>
<script language="JavaScript" type="text/javascript" src=../dataentry/js/lr_trim.js></script>
<script>
	// Main Tabs Switcher
	function switchProfileTab(tabId, btnElement) {
		document.querySelectorAll('.abcd-tab-btn').forEach(b => b.classList.remove('active'));
		document.querySelectorAll('.abcd-tab-pane').forEach(p => p.style.display = 'none');
		btnElement.classList.add('active');
		document.getElementById(tabId).style.display = 'block';
	}

	// Accordion Alternator for Databases
	function toggleAccordion(id) {
		const item = document.getElementById(id);
		if (item) {
			item.classList.toggle('open');
		}
	}

	// Select all permissions for a specific database and update the badge
	function toggleDbAll(dbName, isChecked) {
		const checkboxes = document.querySelectorAll(`input[data-db="${dbName}"]`);
		checkboxes.forEach(cb => {
			cb.checked = isChecked;
		});
		if (isChecked) {
			const dbMaster = document.querySelector(`input[name="db_${dbName}"]`);
			if (dbMaster) dbMaster.checked = true;
		}
		updateAccordionBadges();
	}

	// Select all options within a subgroup (e.g., All Formats of a database)
	function toggleGroup(groupClass, isChecked) {
		const checkboxes = document.querySelectorAll(`.${groupClass}`);
		checkboxes.forEach(cb => cb.checked = isChecked);
		updateAccordionBadges();
	}

	// Select all databases in the system
	function toggleGlobalAllBases(isChecked) {
		const checkboxes = document.querySelectorAll('.chk-is-db');
		checkboxes.forEach(cb => cb.checked = isChecked);
		updateAccordionBadges();
	}

	function toggleGlobalModule(modulePrefix, isChecked) {
		const checkboxes = document.querySelectorAll(`input[data-module="${modulePrefix}"]`);
		checkboxes.forEach(cb => cb.checked = isChecked);
	}

	// Calculate in real-time how many items are checked and update the collapsed accordion
	function updateAccordionBadges() {
		document.querySelectorAll('.abcd-accordion-item').forEach(item => {
			const content = item.querySelector('.abcd-accordion-content');
			const checkedCount = content.querySelectorAll('input[type="checkbox"]:checked').length;
			const badge = item.querySelector('.abcd-badge-count');

			if (checkedCount > 0) {
				item.classList.add('has-checked');
				if (badge) badge.textContent = checkedCount;
			} else {
				item.classList.remove('has-checked');
			}
		});
	}

	// Initializing scopes and listening for events
	document.addEventListener('DOMContentLoaded', function() {
		updateAccordionBadges();
		const tabDb = document.getElementById('tab-db');
		if (tabDb) {
			tabDb.addEventListener('change', updateAccordionBadges);
		}
	});

	function ValidateName(Name) {
		return /^[a-z][\w]+$/i.test(Name);
	}

	function SendForm() {
		let Name = Trim(document.profile.profilename.value);
		Name = Name.replace(/  /gi, ' ').replace(/ /gi, '_');
		document.profile.profilename.value = Name;

		if (Name == "") {
			alert("<?php echo $msgstr["MISSPROFNAME"] ?>");
			return;
		}
		if (!ValidateName(Name)) {
			alert("<?php echo $msgstr["INVPROFNAME"] ?>");
			return;
		}
		if (Trim(document.profile.profiledesc.value) == "") {
			alert("<?php echo $msgstr["MISSPROFDESC"] ?>");
			return;
		}
		document.profile.submit();
	}

	function DeleteProfile(Profile) {
		if (confirm("<?PHP echo $msgstr["DELETE"] ?> " + Profile)) {
			self.location.href = "profile_edit.php?profile=" + Profile + "&Opcion=delete&encabezado=<?php echo $encabezado ?>";
		}
	}
</script>

<?php
if (!isset($arrHttp["Opcion"])) {
	$ret = "../dataentry/browse.php?showdeleted=yes&encabezado=s&base=acces&cipar=acces.par" . $encabezado;
} else {
	switch ($arrHttp["Opcion"]) {
		case "edit":
		case "new":
		case "delete":
			$ret = "profile_edit.php?xx=s" . $encabezado;
			break;
	}
}
?>

<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["PROFILES"] ?>
	</div>
	<div class="actions">
		<?php
		if (isset($arrHttp["Opcion"]) and $arrHttp["Opcion"] != "delete") {
			$savescript = "javascript:SendForm()";
			include "../common/inc_save.php";
		}
		$backtoscript = $ret;
		include "../common/inc_back.php";
		include "../common/inc_home.php";
		?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php
$ayuda = "profiles.html";
include "../common/inc_div-helper.php";
?>
<div class="middle form">
	<div class="formContent">
		<form name="profile" action="profile_save.php" onsubmit="javascript:return false" method="post">
			<?php
			if (isset($arrHttp["encabezado"]))
				echo "<input type=hidden name=encabezado value=S>\n";

			if (!isset($arrHttp["Opcion"])) {
				DisplayProfiles();
			} else {
				switch ($arrHttp["Opcion"]) {
					case "edit":
						EditProfile();
						break;
					case "new":
						NewProfile("");
						break;
					case "delete":
						DeleteProfile();
						break;
				}
			}
			?>
		</form>
	</div>
</div>

<?php
include("../common/footer.php");

function DisplayProfiles()
{
	global $db_path, $msgstr, $encabezado;
	echo "<table>";
	$fp = file($db_path . "par/profiles/profiles.lst");
	foreach ($fp as $val) {
		$val = trim($val);
		if ($val != "") {
			$p = explode('|', $val);
			if ($p[0] != "adm") {
?>
				<tr>
					<td><?php echo $p[1] . " (" . $p[0] . ")"; ?></td>
					<td>
						<a class="bt bt-blue show" href="profile_edit.php?profile=<?php echo $p[0] . $encabezado; ?>&Opcion=edit">
							<i class="far fa-edit"></i> <?php echo $msgstr["EDIT"]; ?></a>
						<a class="bt bt-red delete" href="javascript:DeleteProfile('<?php echo $p[0]; ?>')">
							<i class="far fa-trash-alt"></i> <?php echo $msgstr["delete"]; ?></a>
					</td>
				</tr>
	<?php
			}
		}
	}
	echo "</table><br>";
	echo "<a class='bt bt-blue edit' href='profile_edit.php?Opcion=new&encabezado=s'><i class='fas fa-plus'></i> " . $msgstr["new"] . "</a>";
}

function DeleteProfile()
{
	global $actparfolder, $db_path, $msgstr, $lang_db, $arrHttp, $xWxis, $wxisUrl, $Wxis;
	$loc_actparfolder = $actparfolder;
	if ($actparfolder != "par/") {
		$loc_actparfolder = "acces/";
	}
	$IsisScript = $xWxis . "leer_mfnrange.xis";
	$query = "&base=acces&cipar=$db_path" . $loc_actparfolder . "acces.par" . "&Pft=v40^a/";
	include("../common/wxis_llamar.php");
	foreach ($contenido as $linea) {
		if (trim($linea) == $arrHttp["profile"]) {
			echo "<h2>" . $msgstr["INUSE"] . "<h2>";
			return;
		}
	}
	$fp = file($db_path . "par/profiles/profiles.lst");
	$new = fopen($db_path . "par/profiles/profiles.lst", "w");
	foreach ($fp as $prof) {
		$p = explode('|', $prof);
		if ($p[0] != trim($arrHttp["profile"]))
			fwrite($new, $prof);
	}
	fclose($new);
	$res = unlink($db_path . "par/profiles/" . $arrHttp["profile"]);
	if ($res == 0) {
		echo $arrHttp["profile"] . ": The file could not be deleted";
	} else {
		echo "<h2>" . $arrHttp["profile"] . " " . $msgstr["deleted"] . "</h2>";
		echo "<a class='button_browse show' href='profile_edit.php?base=&encabezado=s'>" . $msgstr["back"] . "</a>";
	}
}

function EditProfile()
{
	global $db_path, $msgstr, $lang_db, $arrHttp;
	NewProfile($arrHttp["profile"]);
}

function NewProfile($profile)
{
	global $db_path, $msgstr, $lang_db, $profiles_path, $_SESSION;

	$profile_usr = array();
	$profile_general = array();

	$fprofile = file("profiles.tab");
	$module = "CENTRAL"; // Padrão raiz mapeado para a aba ADMINISTRATION
	foreach ($fprofile as $p) {
		$p = trim($p);
		if ($p == "[CIRCULATION]") {
			$module = "CIRC";
		} elseif ($p == "[ACQUISITIONS]") {
			$module = "ACQ";
		} elseif ($p == "[ADMINISTRATION]") {
			$module = "ADM";
		} elseif ($p != "") {
			$p_el = explode("=", $p);
			$profile_usr[$module . "_" . $p_el[0]] = "";
			$profile_general[$module][$p_el[0]] = $p;
		}
	}

	if ($profile != "") {
		if (file_exists($db_path . "par/profiles/" . $profile)) {
			$fprofile = file($db_path . "par/profiles/" . $profile);
			foreach ($fprofile as $p) {
				$p = trim($p);
				if ($p != "") {
					$p_el = explode("=", $p);
					$profile_usr[$p_el[0]] = $p_el[1];
				}
			}
		}
	}
	?>
	<div class="profile-header-info">
		<div class="form-group">
			<label><?php echo $msgstr["PROFILENAME"]; ?></label>
			<input type="text" name="profilename" value="<?php echo isset($profile_usr["profilename"]) ? $profile_usr["profilename"] : ''; ?>">
		</div>
		<div class="form-group" style="flex: 3;">
			<label><?php echo $msgstr["PROFILEDESC"]; ?></label>
			<input type="text" name="profiledesc" value="<?php echo isset($profile_usr["profiledesc"]) ? $profile_usr["profiledesc"] : ''; ?>">
		</div>
	</div>

	<div class="abcd-tabs-nav">
		<div class="abcd-tab-btn active" onclick="switchProfileTab('tab-db', this)"><i class="fas fa-database"></i> <?php echo $msgstr["bd"] ?? "Databases"; ?></div>
		<div class="abcd-tab-btn" onclick="switchProfileTab('tab-sys', this)"><i class="fas fa-cogs"></i> <?php echo $msgstr["administracion"] ?? "Administration"; ?></div>
		<div class="abcd-tab-btn" onclick="switchProfileTab('tab-circ', this)"><i class="fas fa-sync"></i> <?php echo $msgstr["circulation"] ?? "Circulation"; ?></div>
		<div class="abcd-tab-btn" onclick="switchProfileTab('tab-acq', this)"><i class="fas fa-shopping-cart"></i> <?php echo $msgstr["acquisitions"] ?? "Acquisitions"; ?></div>
	</div>

	<div id="tab-db" class="abcd-tab-pane" style="display: block;">
		<div style="margin-bottom: 15px; background: #fff; padding: 12px 20px; border-radius: 6px; border: 1px solid #b9d8d9;">
			<label class="abcd-checkbox-item" style="font-weight: bold; margin: 0;">
				<input type="checkbox" name="db_ALL" value="ALL" onclick="toggleGlobalAllBases(this.checked)">
				<span style="color: darkred;"><?php echo $msgstr["ALL"]; ?> (Select all databases)</span>
			</label>
		</div>

		<?php
		$fp = file($db_path . "bases.dat");
		foreach ($fp as $dbs) {
			$dbs = trim($dbs);
			if ($dbs == "") continue;
			$dd = explode('|', $dbs);
			$dbn = $dd[0];

			if ($dbn == "acces") continue;
			$isChecked = isset($profile_usr["db_" . $dbn]) ? "checked" : "";
		?>
			<div class="abcd-accordion-item" id="acc-db-<?php echo $dbn; ?>">
				<div class="abcd-accordion-header" onclick="toggleAccordion('acc-db-<?php echo $dbn; ?>')">
					<h4>
						<i class="fas fa-chevron-down"></i>
						<input type="checkbox" class="chk-is-db" name="db_<?php echo $dbn; ?>" value="<?php echo $dbn; ?>" data-db="<?php echo $dbn; ?>" <?php echo $isChecked; ?> onclick="event.stopPropagation(); if(!this.checked) toggleDbAll('<?php echo $dbn; ?>', false)">
						<?php echo $dd[1] . " (" . $dbn . ")"; ?>
					</h4>
					<div style="display: flex; align-items: center; gap: 15px;" onclick="event.stopPropagation();">
						<span class="abcd-badge-count">0</span>
						<label class="abcd-checkbox-item" style="margin:0; font-size: 12px; color: #555; font-weight: 600;">
							<input type="checkbox" onclick="toggleDbAll('<?php echo $dbn; ?>', this.checked)"> <?php echo $msgstr["ALL"]; ?>
						</label>
					</div>
				</div>

				<div class="abcd-accordion-content">
					<div class="abcd-grid-3">

						<div class="abcd-checkbox-group">
							<h5><?php echo $msgstr["DATAENTRY"]; ?></h5>
							<?php
							$chk_all_cen = isset($profile_usr[$dbn . "_CENTRAL_ALL"]) ? "checked" : "";
							echo "<label class='abcd-checkbox-item'><input type='checkbox' name='{$dbn}_CENTRAL_ALL' data-db='{$dbn}' class='chk-cen-{$dbn}' onclick=\"toggleGroup('chk-cen-{$dbn}', this.checked)\" {$chk_all_cen}> <strong>" . $msgstr["ALL"] . "</strong></label>";

							foreach ($profile_usr as $key => $value) {
								if (substr($key, 0, 7) == "CENTRAL") {
									$k = explode("_", $key);
									if (in_array($k[1], ["CRDB", "TRANSLATE", "USRADM", "EDHLPSYS"])) continue;

									$chk = isset($profile_usr[$dbn . "_" . $key]) ? "checked" : "";
									echo "<label class='abcd-checkbox-item'><input type='checkbox' name='{$dbn}_{$key}' value='Y' data-db='{$dbn}' class='chk-cen-{$dbn}' {$chk}> " . $msgstr[$k[1]] . "</label>";
								}
							}
							?>
						</div>

						<div class="abcd-checkbox-group">
							<h5><?php echo $msgstr["DISPLAYFORMAT"]; ?></h5>
							<?php
							$file = $db_path . $dbn . "/pfts/" . $_SESSION["lang"] . "/formatos.dat";
							if (!file_exists($file)) $file = $db_path . $dbn . "/pfts/" . $lang_db . "/formatos.dat";

							$chk_all_pft = isset($profile_usr[$dbn . "_pft_ALL"]) ? "checked" : "";
							echo "<label class='abcd-checkbox-item'><input type='checkbox' name='{$dbn}_pft_ALL' data-db='{$dbn}' class='chk-pft-{$dbn}' onclick=\"toggleGroup('chk-pft-{$dbn}', this.checked)\" {$chk_all_pft}> <strong>" . $msgstr["ALL"] . "</strong></label>";

							if (file_exists($file)) {
								$pft = file($file);
								foreach ($pft as $val) {
									$val = trim($val);
									if ($val != "") {
										$p = explode('|', $val);
										$chk = isset($profile_usr[$dbn . "_pft_" . $p[0]]) ? "checked" : "";
										echo "<label class='abcd-checkbox-item'><input type='checkbox' name='{$dbn}_pft_{$p[0]}' value='{$p[0]}' data-db='{$dbn}' class='chk-pft-{$dbn}' {$chk}> {$p[1]} ({$p[0]})</label>";
									}
								}
							}
							?>
						</div>

						<div class="abcd-checkbox-group">
							<h5><?php echo $msgstr["WORKSHEET"]; ?></h5>
							<?php
							$file = $db_path . $dd[0] . "/def/" . $_SESSION["lang"] . "/formatos.wks";
							if (!file_exists($file)) $file = $db_path . $dd[0] . "/def/" . $lang_db . "/formatos.wks";

							$chk_all_fmt = isset($profile_usr[$dbn . "_fmt_ALL"]) ? "checked" : "";
							echo "<label class='abcd-checkbox-item'><input type='checkbox' name='{$dbn}_fmt_ALL' data-db='{$dbn}' class='chk-fmt-{$dbn}' onclick=\"toggleGroup('chk-fmt-{$dbn}', this.checked)\" {$chk_all_fmt}> <strong>" . $msgstr["ALL"] . "</strong></label>";

							if (file_exists($file)) {
								$pft = file($file);
								foreach ($pft as $val) {
									$val = trim($val);
									if ($val != "") {
										$p = explode('|', $val);
										$chk = isset($profile_usr[$dbn . "_fmt_" . $p[0]]) ? "checked" : "";
										echo "<label class='abcd-checkbox-item'><input type='checkbox' name='{$dbn}_fmt_{$p[0]}' value='{$p[0]}' data-db='{$dbn}' class='chk-fmt-{$dbn}' {$chk}> {$p[1]} ({$p[0]})</label>";
									}
								}
							}
							?>
						</div>

					</div>
				</div>
			</div>
		<?php
		}
		?>
	</div>

	<?php
	// Modular function for rendering the cards in the global system tabs
	function renderGlobalTab($tabId, $moduleKey, $moduleData, $profile_usr, $msgstr)
	{
		$moduloPrefix = "ADM";
		switch ($moduleKey) {
			case "ADMINISTRATION":
				$moduloPrefix = "CENTRAL";
				break;
			case "CIRCULATION":
				$moduloPrefix = "CIRC";
				break;
			case "ACQUISITIONS":
				$moduloPrefix = "ACQ";
				break;
		}

		echo "<div id='{$tabId}' class='abcd-tab-pane'>";
		echo "<div class='abcd-card'>";
		echo "<div class='abcd-card-header'>";
		echo "<h4><i class='fas fa-shield-alt'></i> " . ($msgstr[$moduleKey] ?? $moduleKey) . "</h4>";
		echo "<label class='abcd-checkbox-item'><input type='checkbox' onclick=\"toggleGlobalModule('{$moduloPrefix}', this.checked)\"> Select All</label>";
		echo "</div>";
		echo "<div class='abcd-card-body'><div class='abcd-grid-3'>";

		if (isset($moduleData)) {
			foreach ($moduleData as $usr_p => $val) {
				if ($moduloPrefix == "CENTRAL" && $usr_p == "ALL") {
					$chk = (isset($profile_usr["CENTRAL_ALL"]) && $profile_usr["CENTRAL_ALL"] == "Y") ? "checked" : "";
					echo "<label class='abcd-checkbox-item' style='grid-column: 1 / -1; font-weight:bold; border-bottom:1px solid #eee; padding-bottom:10px;'><input type='checkbox' name='CENTRAL_ALL' value='Y' data-module='CENTRAL' onclick=\"toggleGlobalModule('CENTRAL', this.checked)\" {$chk}> " . $msgstr["ALL"] . "</label>";
					continue;
				}
				$chk = (isset($profile_usr[$moduloPrefix . "_" . $usr_p]) && $profile_usr[$moduloPrefix . "_" . $usr_p] == "Y") ? "checked" : "";
				echo "<label class='abcd-checkbox-item'><input type='checkbox' name='{$moduloPrefix}_{$usr_p}' value='Y' data-module='{$moduloPrefix}' {$chk}> " . (isset($msgstr[$usr_p]) ? $msgstr[$usr_p] : $usr_p) . "</label>";
			}
		}
		echo "</div></div></div></div>";
	}
	?>

	<?php renderGlobalTab("tab-sys", "ADMINISTRATION", $profile_general["CENTRAL"] ?? null, $profile_usr, $msgstr); ?>

	<?php renderGlobalTab("tab-circ", "CIRCULATION", $profile_general["CIRC"] ?? null, $profile_usr, $msgstr); ?>

	<?php renderGlobalTab("tab-acq", "ACQUISITIONS", $profile_general["ACQ"] ?? null, $profile_usr, $msgstr); ?>

<?php
}
?>
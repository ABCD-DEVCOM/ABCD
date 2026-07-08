<?php
/*
20210315 fho4abcd The destination form no longer fixed to "upload" but specified by option &targetForm=...&..
20210914 fho4abcd Standard header+ use div_helper+better indicator dr_path+standard divs+move function to sanitize code
20220214 fho4abcd Change DOCUMENT_ROOT in $db_path, better indicator for ROOT, allow %path_database%, don't show dr_path for explorar
20220224 fho4abcd Function CopiarImagen: fixed form->supplied form + always close on trigger
20260522 rogercgui Critical fix: Added missing $db_path definition in the file, which is essential for determining the correct directory paths for file operations. This variable is typically defined in the included config.php, but it was not being set in this script, leading to errors when trying to access or manipulate files. The fix ensures that $db_path is properly initialized from the configuration, allowing the script to function correctly when handling file uploads and directory exploration.
20260627 rogercgui Enhancement: Improved the file upload process to handle Windows-style paths more robustly. The script now uses json_encode to safely encode the source path, preventing issues with backslashes and special characters in file paths. This change ensures that file uploads work correctly across different operating systems, particularly when dealing with Windows paths that may contain backslashes or other problematic characters.
*/

session_start();

if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
include "dirs_explorer.class.php";
include("../common/get_post.php");

include("../config.php");
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";


// =========================================================================
// BUILT-IN AJAX UPLOAD ENGINE (Path + Source Correction)
// =========================================================================
if (isset($_FILES['arquivo_upload_ajax'])) {

	$db_ajax = isset($_POST['base']) ? $_POST['base'] : (isset($_GET['base']) ? $_GET['base'] : "");
	$path_ajax = isset($_POST['path']) ? $_POST['path'] : (isset($_GET['path']) ? $_GET['path'] : "");
	$source_ajax = isset($_POST['source']) ? $_POST['source'] : (isset($_GET['source']) ? $_GET['source'] : "");

	$sub_pasta = $path_ajax;
	if ($source_ajax != "") {
		$sub_pasta .= "/" . $source_ajax;
	}

	$destino_base = "";
	if ($db_ajax != "" && file_exists($db_path . $db_ajax . "/dr_path.def")) {
		$def = parse_ini_file($db_path . $db_ajax . "/dr_path.def");
		if (isset($def["ROOT"])) $destino_base = trim($def["ROOT"]);
	}
	if ($destino_base == "") {
		$destino_base = getenv("DOCUMENT_ROOT") . "/bases/" . $db_ajax;
	}

	$destino_base = str_replace("%path_database%", $db_path, $destino_base);
	$destino_base = str_replace(array('\\', '//'), '/', $destino_base);
	if (substr($destino_base, -1) != "/") $destino_base .= "/";

	if ($sub_pasta != "" && substr($sub_pasta, 0, 1) == "/") $sub_pasta = substr($sub_pasta, 1);
	if ($sub_pasta != "" && substr($sub_pasta, -1) != "/") $sub_pasta .= "/";

	$pasta_final = $destino_base . $sub_pasta;
	$pasta_final = str_replace(array('\\', '//'), '/', $pasta_final);

	if (!is_dir($pasta_final)) @mkdir($pasta_final, 0777, true);

	$nome_arquivo = basename($_FILES['arquivo_upload_ajax']['name']);
	$nome_arquivo = preg_replace("/[^a-zA-Z0-9.\-_]/", "_", $nome_arquivo);

	echo "<abcd_ajax>";
	if (move_uploaded_file($_FILES['arquivo_upload_ajax']['tmp_name'], $pasta_final . $nome_arquivo)) {
		echo json_encode(['status' => 'success', 'file' => $nome_arquivo]);
	} else {
		echo json_encode(['status' => 'error', 'message' => ($msgstr["error_saving"] ?? 'Error saving to: ') . $pasta_final]);
	}
	echo "</abcd_ajax>";
	exit;
	exit;
}

//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";

include("../common/header.php");
if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"] = "";

include("../lang/admin.php");
include("../lang/dbadmin.php");
$db = $arrHttp["base"];
if (
	isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"])
	or isset($_SESSION["permiso"][$db . "_CENTRAL_CREC"]) or isset($_SESSION["permiso"][$db . "_CENTRAL_EDREC"])
) {
} else {
	echo $msgstr["invalidright"];
	die;
}
echo "<body>";
include "../common/inc_div-helper.php";
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";

// The default target form (where the selected folder will be set) for this script can be overridden by an an option
$targetForm = "upload"; // the default form used by other apps
if (isset($arrHttp["targetForm"]) and ($arrHttp["targetForm"] != "")) $targetForm = $arrHttp["targetForm"];
$expl = new php_dirs_explorer();

//Now it's needed set FULL path of directory which should be seen
//root_dir - name of variable and it should be static!
// /home/shared_dir - full path of shared directory for *nix
// c:/shared_dir - full path of shared direcotory for win
if (isset($arrHttp["desde"]) and $arrHttp["desde"] == "dbcp") {
	$img_path = $db_path;
	$expl->Set("root_dir", $img_path);
	$name_path = "";
} else { //=========== See equivalent code in upload_img.php==========//
	$arrHttp["desde"] = "dataentry";
	$img_path = "";
	if (file_exists($db_path . $arrHttp["base"] . "/dr_path.def")) {
		$def = parse_ini_file($db_path . $arrHttp["base"] . "/dr_path.def");
		if (isset($def["ROOT"]) && trim($def["ROOT"] != "")) {
			$img_path = trim($def["ROOT"]);
			$img_path = str_replace("%path_database%", $db_path, $img_path);
			$name_path = $msgstr["root_from_dr"];
			if (!file_exists($img_path)) mkdir($img_path, 0770, true);
		}
	}
	if ($img_path == "") {
		$img_path = $db_path . $arrHttp["base"] . "/";
		$name_path = "%path_database%" . $arrHttp["base"] . "/";
	}
	if (!is_dir($img_path)) {
		echo "<h3>" . $msgstr["dirne"] . " (" . $name_path . ")</h3> ";
		die;
	}
	$expl->Set("root_dir", $img_path);
}
if (!isset($arrHttp["source"])) {
	$source = "";
} else {
	$source = $arrHttp["source"];
}
if (!isset($arrHttp["path"])) {
	$path = "";
} else {
	$path = $arrHttp["path"];
}
$source = trim($path . $source);
if (trim($source) != "") {
	if (substr($source, 0, 1) == "/") $source = substr($source, 1);
	if (substr($source, strlen($source), 1) != "/") $source = $source . "/";
}
?>
<div class="middle form">
	<div class="formContent">
		<?php
		if ($arrHttp["Opcion"] == "explorar") {
		?>
			<input type=radio name=sel value=""
				onclick="window.opener.document.<?php echo $targetForm; ?>.storein.value='<?php echo $source; ?>'; window.opener.focus(); self.close()">
			<?php echo $name_path; ?>
		<?php
		}
		//Now it's needed set path of icons
		//icons_dir - name of variable and it should be static!
		//icons/ - directory of icons
		$expl->Set("icons_dir", "/assets/images/");


		//Now it's needed to set files of icons for various file types
		if (isset($arrHttp["mx"])) {
			$types["db_add.png"] = array("mst");
		} else {
			$types['image.gif'] = array('jpg', 'gif', 'png', 'tif', 'bmp');
			$types['txt.gif'] = array('txt', 'tab', 'dat', 'def', 'fdt', 'fmt', 'pft', 'fst', 'val', 'wks', 'beg', 'end', 'cfg', 'bat');
			$types['winamp.gif'] = array('mp3');
			$types['mov.gif'] = array('mov');
			$types['wmv.gif'] = array('avi', 'mpeg', 'mpg');
			$types['rar.gif'] = array('rar');
			$types['zip.gif'] = array('zip');
			$types['doc.gif'] = array('doc');
			$types['pdf.gif'] = array('pdf');
			$types['excel.gif'] = array('xls');
			$types['html.gif'] = array('htm', 'html');
			#$types['exe.gif'] = array ('exe');
			#$types['mdb.gif'] = array ('mdb');
			$types['ppt.gif'] = array('ppt');
			//types - name of variable and it should be static!
			//$types - array of icons files and file types
		}
		$expl->Set("types", $types);


		//Now it's needed set file of icon for undefined file types
		//un_icon - name of variable and it should be static!
		//file.gif file of icon for undefined file types
		$expl->Set("un_icon", "unident.gif");

		//Now it's needed to set file of icon for directory
		//dir_icon - name of variable and it should be static!
		//directory.gif file of icon for directories
		$expl->Set("dir_icon", "folder.gif");

		if (isset($arrHttp["tag"]))
			$tag = $arrHttp["tag"];
		else
			$tag = "";

		$expl->show_dirs($arrHttp["Opcion"], $img_path, $tag);
		// Show dirs calls function Encabezamiento

		reset($arrHttp);
		echo "\n\n<form name=newfolder action=newfolder.php method=post>\n";
		foreach ($arrHttp as $var => $value) {
			echo "<input type=hidden name=$var value=\"$value\">\n";
		}
		echo "<input type=hidden name=folder>\n";
		echo "</form>";

		// Close the HTML tags so that the screen doesn’t look broken
		echo "</div></div></body></html>";

		die;

		//==============function=========================
		function Encabezamiento()
		{
			global $tag, $msgstr, $arrHttp, $targetForm, $source;

		?>
			<title><?php echo $msgstr["explore"]; ?></title>
			<script>
				<?php if (isset($tag) and trim($tag) != "") {  ?>

					function CopiarImagen(Img) {
						var field = window.opener.document.<?php echo $targetForm; ?>.<?php echo $tag ?>;
						var campo = field.value;

						var relativePath = <?php echo json_encode($source); ?>;
						var finalImg = Img;

						if (relativePath !== "" && finalImg.indexOf(relativePath) !== 0) {
							finalImg = relativePath + finalImg;
						}

						// If it is a Textarea (Repeatable/Multiple), append with a line break
						if (field.tagName === 'TEXTAREA') {
							if (campo === "") field.value = finalImg;
							else field.value = campo + "\n" + finalImg;
						} else {
							// If it is an Input (Simple/Single), it simply replaces the value
							field.value = finalImg;
							self.close();
						}
					}
				<?php } ?>

				function SelectedFolder(Folder) {
					alert(Folder)
				}

				function CrearCarpeta() {
					folder = prompt("<?php echo $msgstr["folder_name"] ?>")
					folder = Trim(folder)
					if (folder == "")
						return
					document.newfolder.folder.value = folder
					document.newfolder.submit()
					return
				}

				function MostrarImagen(source, base) {
					// O source já vem como nome do arquivo final da lista.
					url = "../common/show_image.php?image=" + source + "&base=" + base;
					msgwin = window.open(url, "show", "width=600,height=600,scrollbars,resizable");
					msgwin.focus();
				}
			</script>
			<script language="JavaScript" type="text/javascript" src=js/lr_trim.js></script>


			<h4>
				<?php
				$path = "";
				$source = "";
				if (isset($arrHttp["path"]))  $path = $arrHttp["path"];
				if (isset($arrHttp["source"]))  $source = $arrHttp["source"];


				// =========================================================================
				//  VISUAL INTERFACE (TOOLBAR)
				// =========================================================================

				// Build the actual visual path by combining path and source
				$caminho_visual = "";
				if (isset($arrHttp["path"]) && $arrHttp["path"] != "") $caminho_visual .= $arrHttp["path"];
				if (isset($arrHttp["source"]) && $arrHttp["source"] != "") {
					$caminho_visual .= "/" . $arrHttp["source"];
				}

				// Remove any double slashes (//) generated by concatenation
				$caminho_visual = str_replace(array('\\', '//'), '/', $caminho_visual);
				$caminho_visual = trim($caminho_visual, '/');

				// Apply the parent directory (../) style or set it as the root
				if ($caminho_visual == "") {
					$caminho_visual = "/ (" . ($msgstr["root_dir"] ?? "Root") . ")";
				} else {
					$caminho_visual = "../" . $caminho_visual;
				}
				?>

				<div style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 12px 20px; border-radius: 6px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e0e6ed;">

					<div style="font-size: 14px; color: #333;">
						<span style="font-weight: 600; color: #005aa9;"><i class="fas fa-database"></i> <?php echo htmlspecialchars($arrHttp["base"]); ?></span>
						<span style="margin: 0 10px; color: #ccc;">|</span>
						<i class="far fa-folder-open" style="color: #f39c12;"></i>
						<strong style="color: #555;"><?php echo htmlspecialchars($caminho_visual); ?></strong>
					</div>

					<div style="display: flex; gap: 10px;">
						<button type="button" class="bt-green" onclick="CrearCarpeta()" style="padding: 6px 12px; font-size: 13px; border: none; border-radius: 4px; cursor: pointer; color: #fff; background-color: #28a745; transition: 0.2s;">
							<i class="fas fa-folder-plus"></i> <?php echo $msgstr["create_folder"] ?? "Create Folder"; ?>
						</button>

						<input type="file" id="arquivoUpload" style="display: none;" onchange="ExecutarUploadAqui()">
						<button type="button" class="bt-blue" onclick="document.getElementById('arquivoUpload').click()" style="padding: 6px 12px; font-size: 13px; border: none; border-radius: 4px; cursor: pointer; color: #fff; background-color: #005aa9; transition: 0.2s;">
							<i class="fas fa-cloud-upload-alt"></i> <?php echo $msgstr["upload_here"] ?? "Upload Here"; ?>
						</button>
					</div>
				</div>

				<div id="uploadProgressContainer" style="display:none; background: #fff; padding: 15px 20px; border-radius: 6px; border: 1px solid #b9d8d9; margin-bottom: 20px;">
					<div style="font-size: 13px; font-weight: bold; color: #005aa9; margin-bottom: 8px;" id="uploadStatus"><?php echo $msgstr["preparing_upload"] ?? "Preparing upload..."; ?></div>
					<div style="height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">
						<div id="uploadProgressBar" style="height: 100%; width: 0%; background: #28a745; transition: width 0.2s;"></div>
					</div>
				</div>

				<script>
					// =========================================================================
					// AJAX Upload JavaScript
					// =========================================================================
					function ExecutarUploadAqui() {
						var input = document.getElementById('arquivoUpload');
						if (!input.files || input.files.length === 0) return;

						var file = input.files[0];
						var formData = new FormData();
						formData.append('arquivo_upload_ajax', file);

						formData.append('base', <?php echo isset($arrHttp["base"]) ? json_encode($arrHttp["base"]) : '""'; ?>);
						formData.append('path', <?php echo isset($arrHttp["path"]) ? json_encode($arrHttp["path"]) : '""'; ?>);
						formData.append('source', <?php echo isset($arrHttp["source"]) ? json_encode($arrHttp["source"]) : '""'; ?>);

						var container = document.getElementById('uploadProgressContainer');
						var bar = document.getElementById('uploadProgressBar');
						var status = document.getElementById('uploadStatus');

						container.style.display = 'block';

						var xhr = new XMLHttpRequest();

						xhr.open('POST', window.location.href, true);

						xhr.upload.onprogress = function(e) {
							if (e.lengthComputable) {
								var percent = Math.round((e.loaded / e.total) * 100);
								bar.style.width = percent + '%';
								status.innerText = '<?php echo $msgstr["uploading_file"] ?? "Uploading file: "; ?>' + percent + '%';
							}
						};

						xhr.onload = function() {
							if (xhr.status === 200) {
								try {
									var text = xhr.responseText;
									var match = text.match(/<abcd_ajax>([\s\S]*?)<\/abcd_ajax>/);

									if (match) {
										var res = JSON.parse(match[1]);
										if (res.status === 'success') {
											status.innerText = '<?php echo $msgstr["upload_complete"] ?? "Upload complete! Updating folder..."; ?>';
											bar.style.background = '#005aa9';

											<?php if (isset($tag) and trim($tag) != "") { ?>
												if (typeof CopiarImagen === 'function') {
													CopiarImagen(res.file);
												}
											<?php } ?>

											setTimeout(function() {
												window.location.reload();
											}, 800);
										} else {
											alert("<?php echo $msgstr['server_error'] ?? 'Server Error: '; ?>" + res.message);
											container.style.display = 'none';
										}
									} else {
										alert("<?php echo $msgstr['php_fatal_error'] ?? 'PHP Fatal Error. Raw response: \n'; ?>" + text.substring(0, 250));
										container.style.display = 'none';
									}
								} catch (e) {
									alert("<?php echo $msgstr['error_processing'] ?? 'Error processing response. Please try again.'; ?>");
									container.style.display = 'none';
								}
							} else {
								alert("<?php echo $msgstr['http_error'] ?? 'HTTP Error: '; ?>" + xhr.status);
								container.style.display = 'none';
							}
							input.value = "";
						};
						xhr.send(formData);
					}
				</script>
			<?php
		}
			?>
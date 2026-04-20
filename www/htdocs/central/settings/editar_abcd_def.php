<?php
/* Modifications
2021-01-05 guilda Added $msgstr["regresar"]
2021-04-30 fho4abcd Error message if file is not writable. 
2021-04-30 fho4abcd Corrected html. Replaced helper code fragment by included file
2021-08-29 fho4abcd PDF-> Digital documents,no radiobutton. Digital documents-> 
linked documents
2022-01-18 rogercgui added new user-configurable classes
2022-01-24 rogercgui Included file renaming to avoid accumulation of images in the upload folder. Even without converting the extension the files will have fixed names.
2022-02-14 fho4abcd Texts for dr_path+ sequence for dr_path+improved table layout+removed redirect (too rigid for dr_path)
2022-03-10 fho4abcd Remove unused option barcode1reg from dr_path
2024-01-04 fho4abcd No typewriter behavior(suppressed errors), give error for wrong upload filetype, no error if nothing to upload
2025-03-05 fho4abcd Shuffle sections, modify some titles
2025-03-16 fho4abcd textarea for extensions, improve styling&clss typo, function to remove custom logo, code more readable, removed unused code
2025-09-24 fho4abcd Added default sort options
2026-03-03 rogercgui Updated UI to match parametros.php (Accordions) and added Header options
2026-03-11 fho4abcd Added VALID_IP in dr_path.def
*/


/*================= Functions ========================*/
/* ========== Function databases */
function databases()
{
	global $lista_bases, $arrHttp, $database_list_v, $database_list_n;
	$i = -1;
	$output = "";
	foreach ($lista_bases as $keydb => $value) {
		$xselected = "";
		$value = trim($value);
		$t = explode('|', $value);
		if (isset($Permiso["db_" . $keydb]) or isset($_SESSION["permiso"]["db_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"])) {
			if (isset($arrHttp["base"]) and $arrHttp["base"] == $keydb or count($lista_bases) == 1) $xselected = " selected";
			$output .= $keydb . ";";
		}
	}
	return $output;
}
//================= Function to generate html for abcd.def ==========
function LeerIniFile($ini_vars, $ini)
{
	global $msg_path, $msgstr, $target_dir, $def, $folder_logo;
	foreach ($ini_vars as $key => $Opt) {
		if (isset($Opt["default"])) {
			$defaultDef = $Opt["default"];
		} else {
			$defaultDef = "";
		}
		if (isset($def[$key])) {
			$valueDef = $def[$key];
		} elseif (isset($ini[$key])) {
			$valueDef = $ini[$key];
		} else {
			$valueDef = $defaultDef;
		}
		if ($Opt["it"] == "title") {
			// Close previous table/panel and start new accordion
			echo "</table></div><button type=\"button\" class=\"accordion\" id=\"$key\">" . $Opt["Label"] . "</button>";
			echo "\n<div class=\"panel\"><table class=\"striped\" >\n";
			continue;
		} else {
			echo "<tr><td>" . $msgstr['set_' . $key] . "</td>\n";
			echo "<td>";
		}
		switch ($Opt["it"]) {
			// Field type color
			case "color":
				$opc = explode(";", $Opt["Label"]);
				echo "<input type='color' class='m-1' placeholder='" . $Opt["default"] . "' data-value='" . $Opt["default"] . "' name=ini_$key id=ini_$key size=";
				if (isset($Opt["size"]))
					echo trim($Opt["size"]);
				else
					echo "100";
				echo " value=" . $valueDef . ">";
				echo "&nbsp;<button type=\"button\" class=\" bt bt-sm bt-red\" onclick=\"return cleanSet('" . $Opt["default"] . "','ini_" . $key . "')\" title=" . $Opt["Label"] . "><i class=\"fas fa-eraser\"></i></button> ";
				break;

			// Field type text
			case "text":
				echo "<input type=text name=ini_$key size=";
				if (isset($Opt["size"]))
					echo trim($Opt["size"]);
				else
					echo "100";
				echo " value='" . $valueDef . "'";
				if (isset($Opt["placeholder"])) {
					echo " placeholder='" . $Opt["placeholder"] . "'";
				}
				if (isset($Opt["disabled"])) echo " disabled";
				echo ">";
				break;

			// Field type textarea
			case "textarea":
				echo "<textarea name=ini_$key cols=";
				if (isset($Opt["size"]))
					echo trim($Opt["size"]);
				else
					echo "100";
				echo " value='" . $valueDef . "'";
				if (isset($Opt["placeholder"])) {
					echo " placeholder='" . $Opt["placeholder"] . "'";
				}
				if (isset($Opt["disabled"])) echo "disabled";
				echo ">" . $valueDef;
				echo "</textarea>";
				break;

			// Field type radio
			case "radio":
				$opc = explode(";", $Opt["Options"]);
				$label = explode(";", $Opt["Label"]);
				foreach (array_combine($opc, $label) as $o => $l) {
					echo "<input type=radio name=ini_$key value='$o' ";
					if ($valueDef == $o)
						echo " checked";
					echo ">";
					if (isset($Opt["Label"])) {
						echo  "<label>&nbsp;" . $l . "</label>&nbsp;&nbsp;";
					} else {
						echo "<label>&nbsp;" . $o . "</label>&nbsp;&nbsp;";
					}
				}
				break;

			// Field type select
			case "select":
				$opc = explode(";", $Opt["Options"]);
				$label = explode(";", $Opt["Label"]);
				echo "<select name='ini_$key' id='ini_$key' onchange=\"select('ini_" . $key . "')\">";
				echo "<option></option>";
				foreach (array_combine($opc, $label) as $o => $l) {
					echo "<option value='" . $o . "'";
					if (isset($ini[$key])) {
						if ($ini[$key] == $o)
							echo " selected";
					}
					echo ">";
					if (isset($Opt["Label"])) {
						echo $l . "</option>";
					} else {
						echo $o . "</option>";
					}
				}
				echo "</select>";
				break;

			// Field type checkbox
			case "check":
				$opc = explode(";", $Opt["Options"]);
				$label = explode(";", $Opt["Label"]);
				foreach (array_combine($opc, $label) as $o => $l) {
					echo "<input type=checkbox name=ini_$key value='$o' ";
					if ($valueDef == $o)
						echo " checked";
					echo ">";
					if (isset($Opt["Label"])) {
						echo  "<label>" . $l . "</label>&nbsp;";
					} else {
						echo "<label>" . $o . "</label>&nbsp;";
					}
				}
				break;

			// Field type hidden        
			case "hidden":
				echo "\n<input type=hidden name=ini_$key size=";
				if (isset($Opt["size"]))
					echo trim($Opt["size"]);
				else
					echo "100";
				echo " value=" . $valueDef . "'>\r\n";
				break;

			// Field type file
			case "file":
				// File uploads require the upload folder. Next entries only shown if target-dir is set
				if ($target_dir != "") {
					// show the file selector
					if ((isset($ini[$key])) && (!empty($ini[$key]))) {
						$file_value = $ini[$key];
					} else {
						$file_value = $Opt["default"];
					}
					echo "<input type=\"file\" name=ini_" . $key . " id=ini_" . $key;
					echo " accept=\"image/png, image/jpeg , image/jpg\" onchange=\"preview_image ('" . $key . "',event)\" class='bt bt-light' ";
					echo " value='";
					if (isset($ini[$key])) echo $ini[$key];
					echo "'><br>";
					echo "<small>  Max: 2MB </small>";

					// show the fileimage box with optionally the delete button (and related code)
					if ((!isset($key)) or (empty($ini[$key]))) {
						// if no file: show an empty box
						echo '<br><div class="mb-4"><img class="p-3 bg-gray-300 p-3 mt-3 " width="100" id="' . $Opt["ID"] . '"/>' . $file_value . '</div> ';
					} else {
						// Show the fileimagebox with button to delete it
						echo '<div class="mb-4 thiscontainer" >';
						echo '<img class="p-3 bg-gray-300 p-3 mt-3 " width="100" src=' . $folder_logo . $file_value . ' id="' . $Opt["ID"] . '"/>';
						echo '&nbsp';
						// The button with te erase symbol and onclick to javascript
						echo '<a class="bt bt-red thisvertical-center" onclick="return cleanLogo(\'clean_' . $key . '\',\'but_' . $Opt["ID"] . '\')"';
						echo ' id=but_' . $Opt["ID"] . ' title="Remove"><i class="fas fa-eraser"></i></a>';
						// Hidden checkbox, will be set by click on the button.
						echo '<input class="thisvertical-center" type=checkbox id=clean_' . $key . ' name=clean_' . $key . ' style="display:none" >';
						echo '</div>';
					}
				}
				break;
		}
		echo "</td>\n";
		echo "<td>";
		if (isset($Opt["Tip"])) {
			echo "<small>" . $Opt["Tip"] . "</small>";
		}

		echo "</td>";
		echo "</tr>\n";
	}
}
//============= end function ========
/* ========== Function uplodimages */
function uplodimages($fieldname, $fileimg)
{
	global $fieldname, $fp, $msgstr, $target_dir;

	if ($_FILES[$fileimg]["name"] == "") return; // no more action if no file to upload
	$target_file = $target_dir . basename($_FILES[$fileimg]["name"]);

	if (isset($target_file)) {
		if (!is_dir($target_dir)) {
			mkdir($target_dir);
		}
	}

	$temp = explode(".", $target_file);
	$newfilename = $fieldname . '.' . end($temp);
	$target_file = $target_dir . basename($newfilename);

	$uploadOk = 1;

	$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

	// Check if image file is a actual image or fake image
	if (isset($_POST["submit"])) {
		$check = getimagesize($_FILES[$fileimg]["tmp_name"]);
		if ($check !== false) {
			echo "File is an image - " . $check["mime"] . ".";
			$uploadOk = 1;
		} else {
			echo "File is not an image.";
			$uploadOk = 0;
		}
	}

	// Check file size
	if ($_FILES[$fileimg]["size"] > 2097152) {
		echo $msgstr["set_ERROR_SIZE"];
		$uploadOk = 0;
	}

	// Allow certain file formats
	if (
		$imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
		&& $imageFileType != "gif"
	) {
		echo "Sorry, only .jpg, .jpeg, .png & .gif files are allowed." . "<br>";
		$uploadOk = 0;
	}

	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		echo $msgstr["set_notsentfile"] . "<br>";;
		// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES[$fileimg]["tmp_name"], $target_file)) {
			echo $msgstr["set_thefile"] . "&nbsp;<b>" . htmlspecialchars(basename($_FILES[$fileimg]["name"])) . "</b>";
			echo "&nbsp;" . $msgstr["set_has_been_upload"] . " => <b>" . $target_file . "</b> <br>";
		} else {
			echo $msgstr["set_error_upload"] . "<br>";
			fwrite($fp, $fieldname . "=" . $fileimg . "\n");
		}
	}
}
/* ========== Function saveDef: write .def file ======*/
function saveDef()
{
	global $fieldname, $fp, $msg_path, $msgstr, $def, $target_dir, $file, $help, $arrHttp, $cleanLOGO, $cleanLOGO_RESPONSIBLE;

	echo '<pre>';
	echo "<b>" . $msgstr["set_APPLY"] . "</b><br>";
	$fp = @fopen($file, "w");
	if (!$fp) {
		//Checks for errors	
		$contents_error = error_get_last();
		echo "<font color=red><b>" . $msgstr["copenfile"] . " " . $help . "</b> : " . $contents_error["message"];
	} else {
		//Saves the parameters starting with "ini_" in the .def file
		foreach ($arrHttp as $key => $Opt) {
			if (substr($key, 0, 4) == "ini_") {
				$key = substr($key, 4);
				echo $key . "=" . $arrHttp["ini_" . $key] . "<br>";
				fwrite($fp, $key . "=" . trim($arrHttp["ini_" . $key]) . "\n");
			}
		}

		// Upload and save file names. 
		$fileslist = array("ini_LOGO");
		foreach ($fileslist as $fileimg) {
			$fieldname = substr($fileimg, strlen("ini_"));
			if (isset($_FILES[$fileimg]["name"])) {
				if ($_FILES[$fileimg]["name"]) {
					$temp = explode(".", $_FILES[$fileimg]["name"]);
					$newfilename = $fieldname . '.' . end($temp);
					$file_name  = $newfilename;
					if (isset($file_name)) {
						echo $fieldname . "=" . $file_name . "<br>";
						fwrite($fp, $fieldname . "=" . $file_name . "\n");
					}
				} else {
					//fwrite($fp,$fieldname."=".$def[$fieldname]."\n");
					if (isset($def[$fieldname])) {
						if ($fieldname != $cleanLOGO && $fieldname != $cleanLOGO_RESPONSIBLE) {
							// Only write logos with value
							echo $fieldname . "=" . $def[$fieldname] . "<br>";
							fwrite($fp, $fieldname . "=" . $def[$fieldname] . "\n");
						}
					}
				}
				uplodimages($fieldname, $fileimg);
			}
		}
		if (isset($arrHttp["mod_TITLE"])) {
			echo "[MODULOS]<BR>";
			fwrite($fp, "[MODULOS]\n");
			foreach ($arrHttp as $key => $Opt) {
				if (substr($key, 0, 4) == "mod_") {
					$key = substr($key, 4);
					echo $key . "=" . $arrHttp["mod_" . $key] . "<br>";
					fwrite($fp, $key . "=" . trim($arrHttp["mod_" . $key]) . "\n");
				}
			}
		}
		fclose($fp);

		echo '<span class="string-highlight">' . $help . " - " . $msgstr["updated"] . '! </span>';
	}
	echo "</pre>";
}
//============= End PHP functions  ==============
//============= Start session code ===============

session_start();

if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}

//foreach ($_REQUEST AS $var=>$value)  echo "$var=>$value<br>";
include("../common/get_post.php");
include("../config.php");

include("../common/header.php");
include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/dbadmin.php");
include("../lang/prestamo.php");


if (isset($_REQUEST['ini_DIRECTORY_SYSTEM_UPLOADS'])) {
	$target_dir = $_REQUEST['ini_DIRECTORY_SYSTEM_UPLOADS'];
} elseif (isset($def['DIRECTORY_SYSTEM_UPLOADS'])) {
	$target_dir = $def['DIRECTORY_SYSTEM_UPLOADS'];
} else {
	$target_dir = "";
}

//If option 1 = htdocs/uploads; If option 2 = bases/par/uploads
switch ($target_dir) {
	case "1":
		$target_dir = $ABCD_scripts_path . "uploads/";
		break;
	case "2":
		$target_dir = $db_path . "uploads/";
		break;
	default:
		// This disables LOGO settings
		$target_dir = "";
		break;
}

if (!isset($_SESSION["login"]) or $_SESSION["profile"] != "adm") {
	echo "<script>
	      alert('" . $msgstr["invalidright"] . "')
          history.back();
          </script>";
	die;
}
//$Permiso=$_SESSION["permiso"];

//SE LEE LA LISTA DE MENSAJES DISPONIBLEE
$l = $msg_path . 'lang/';
if ($handle = opendir($l)) {
	$lang_dir = "";
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." and $entry != ".." and is_dir($l . $entry)) {
			if ($lang_dir == "")
				$lang_dir = $entry;
			else
				$lang_dir .= ";" . $entry;
		}
	}
	closedir($handle);
}

// Databases list
$lista_bases = array();
if (file_exists($db_path . "bases.dat")) {
	$fp = file($db_path . "bases.dat");
	foreach ($fp as $linea) {
		$linea = trim($linea);
		if ($linea != "") {
			$ix = strpos($linea, "|");
			$llave = trim(substr($linea, 0, $ix));
			$lista_bases[$llave] = trim(substr($linea, $ix + 1));
		}
	}
}

$databases_codes = databases();
?>

<body>
	<link rel="stylesheet" type="text/css" href="/assets/css/text-cursor.css">
	<style type="text/css">
		.accordion {
			margin: auto;
			margin: auto !important;
			background-color: var(--abcd-light);
			color: var(--abcd-blue);
			cursor: pointer;
			padding: 8px;
			width: 100%;
			border: none;
			text-align: left;
			outline: none;
			font-size: 15px;
			transition: 0.4s;
			border-bottom: 1px solid #ccc;
		}

		.active,
		.accordion:hover {
			background-color: #bbb;
		}

		.accordion:after {
			content: '\002B';
			color: #777;
			font-weight: bold;
			float: right;
			margin-left: 5px;
		}

		.accordion.active:after {
			content: "\2212";
		}

		.panel {
			padding: 18px;
			display: none;
			background-color: white;
			overflow: hidden;
		}

		/* css to vertically align the erase button for the file/logo entries*/
		.thisvertical-center {
			margin: 0;
			position: absolute;
			top: 50%;
		}

		.thiscontainer {
			position: relative;
		}
	</style>

	<script language="javascript" type="text/javascript">
		function Enviar() {
			document.maintenance.submit()
		}

		//Function that resets the colour values
		function cleanSet(v, campo) {
			document.getElementById(campo).value = v;
			//document.getElementById(campo).fileupload.value=v;
			console.log(v);
			return false;
		}
		//Function that resets a logo value
		function cleanLogo(campo_check, campo_button) {
			document.getElementById(campo_check).checked = true;
			document.getElementById(campo_button).style = "background-color:#D3D3D3;border-color:#A0A0A0";
			return false;
		}

		function preview_image(campo, event) {
			var reader = new FileReader();
			reader.onload = function() {
				var output = document.getElementById(campo);
				output.src = reader.result;
			}
			reader.readAsDataURL(event.target.files[0]);
		}

		function select(campo) {
			var e = document.getElementById(campo);
			var strUser = e.value;
			console.log(strUser);
		}

		function toggleAllAccordions(expand) {
			var panels = document.getElementsByClassName("panel");
			var accordions = document.getElementsByClassName("accordion");
			for (var i = 0; i < panels.length; i++) {
				panels[i].style.display = expand ? "block" : "none";
				if (expand) {
					accordions[i].classList.add("active");
				} else {
					accordions[i].classList.remove("active");
				}
			}
		}
	</script>

	<?php
	include("../common/institutional_info.php");

	$set_mod = $arrHttp["Opcion"];

	global $database_list;

	switch ($set_mod) {
		case "abcd_styles":
			$ini_vars = array(
				// Folders
				"FOLDERS" => array("it" => "title", "Label" => $msgstr["set_folders"]),

				"MAIN_FOLDER" => array("it" => "text", "size" => "50", "placeholder" => $ABCD_scripts_path, "Tip" => $msgstr["set_TIP_MAIN_FOLDER"], "disabled" => "disabled",),
				"DATABASES_FOLDER" => array("it" => "text", "size" => "50", "placeholder" => $db_path, "Tip" => $msgstr["set_TIP_DATABASES_FOLDER"], "disabled" => "disabled",),
				"DIRECTORY_SYSTEM_UPLOADS"  => array("it" => "select", "Options" => "1;2", "Label" => $msgstr["set_MAIN_FOLDER"] . ";" . $msgstr["set_DATABASES_FOLDER"], "Tip" => $msgstr["set_TIP_DIRECTORY_SYSTEM_UPLOADS"]),

				// Identification
				"IDENTIFICATION" => array("it" => "title", "Label" => $msgstr["set_identification"]),

				"INSTITUTION_NAME" => array("it" => "text", "size" => "50", "placeholder" => $msgstr["set_INSTITUTION_NAME_PH"], "Tip" => $msgstr["set_TIP_INSTITUTION_NAME"]),
				"INSTITUTION_URL" => array("it" => "text", "size" => "50", "placeholder" => $msgstr["set_INSTITUTION_URL_PH"], "Tip" => $msgstr["set_TIP_INSTITUTION_URL"]),

				"RESPONSIBLE_NAME" => array("it" => "text", "Options" => "", "size" => "50", "placeholder" => $msgstr["set_RESPONSIBLE_NAME_PH"], "Tip" => $msgstr["set_TIP_RESPONSIBLE_NAME"]),
				"RESPONSIBLE_URL" => array("it" => "text", "Options" => "", "size" => "50", "placeholder" => $msgstr["set_RESPONSIBLE_URL_PH"], "Tip" => $msgstr["set_TIP_RESPONSIBLE_URL"]),

				"ADDITIONAL_LINK_TITLE" => array("it" => "text", "Options" => "", "size" => "50", "placeholder" => $msgstr["set_ADDITIONAL_LINK_TITLE_PH"], "Tip" => $msgstr["set_TIP_ADDITIONAL_LINK_TITLE"]),
				"URL_ADDITIONAL_LINK" => array("it" => "text", "Options" => "", "size" => "50", "placeholder" => $msgstr["set_URL_ADDITIONAL_LINK_PH"], "Tip" => $msgstr["set_TIP_URL_ADDITIONAL_LINK"]),

				// NOVO: HEADER SETTINGS
				"HEADER" => array("it" => "title", "Label" => $msgstr["header"]),
				"SHOW_DB_SELECTION" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "default" => "Y", "Tip" => "Show/Hide Database Selection"),
				"SHOW_CIRCULATION" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "default" => "Y", "Tip" => "Show/Hide Circulation Button"),
				"SHOW_ACQUISITION" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "default" => "Y", "Tip" => "Show/Hide Acquisition Button"),
				"SHOW_OPAC_BUTTON" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "default" => "Y", "Tip" => "Show/Hide OPAC Button"),
				"SHOW_CHARSET" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "default" => "Y", "Tip" => "Show/Hide Charset"),

				// Database settings
				"DATABASE_SET" => array("it" => "title", "Label" => $msgstr["set_database"]),
				"UNICODE" => array("it" => "radio", "Options" => "1;0", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_UNICODE"]),
				"CISIS_VERSION" => array("it" => "radio", "Options" => $cisis_versions_allowed, "Label" => $cisis_versions_allowed, "Tip" => $msgstr["set_TIP_CISIS_VERSION"]),
				"MAIN_DATABASE"  => array("it" => "select", "Options" => $databases_codes, "Label" => $databases_codes, "Tip" => $msgstr["set_TIP_MAIN_DATABASE"]),
				"DEFAULT_DBLANG"  => array("it" => "select", "Options" => $lang_dir, "Label" => $lang_dir, "Tip" => $msgstr["set_DEFAULT_DBLANG"]),
				"DATE_FORMAT" => array("it" => "text", "size" => "20", "placeholder" => $config_date_format, "Tip" => $msgstr["set_TIP_DATE_FORMAT"]),
				"REG_LOG" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_REG_LOG"]),

				// Logos
				"LOGOS" => array("it" => "title", "Label" => $msgstr["set_logos"]),
				"LOGO_DEFAULT" => array("it" => "check", "Options" => "Y", "Label" => "Yes", "Tip" => $msgstr["set_TIP_LOGO_DEFAULT"]),
				"LOGO" => array("it" => "file", "Options" => "", "Label" => "Reset", "default" => "", "ID" => "LOGO", "Tip" => $msgstr["set_TIP_LOGO"]),

				// Colours
				"COLORS" => array("it" => "title", "Label" => $msgstr["set_colors"]),
				"BODY_BACKGROUND" => array("it" => "color", "default" => "#ffffff", "Label" => " Default: #ffffff", "Tip" => $msgstr["set_TIP_BODY_BACKGROUND"]),
				"COLOR_LINK" => array("it" => "color", "default" => "#336699", "Label" => " Default #336699", "Tip" => $msgstr["set_TIP_COLOR_LINK"]),
				"HEADING" => array("it" => "color", "default" => "#003366", "Label" => " Default #003366", "Tip" => $msgstr["set_TIP_HEADING"]),
				"HEADING_FONTCOLOR" => array("it" => "color", "default" => "#f8f8f8", "Label" => " Default: #f8f8f8", "Tip" => $msgstr["set_TIP_HEADING_FONTCOLOR"]),
				"SECTIONINFO" => array("it" => "color", "default" => "#336699", "Label" => " Default #336699", "Tip" => $msgstr["set_TIP_SECTIONINFO"]),
				"SECTIONINFO_FONTCOLOR" => array("it" => "color", "default" => "#ffffff", "Label" => " Default #ffffff", "Tip" => $msgstr["set_TIP_SECTIONINFO_FONTCOLOR"]),
				"TOOLBAR" => array("it" => "color", "default" => "#f8f8f8", "Label" => " Default #f8f8f8", "Tip" => $msgstr["set_TIP_TOOLBAR"]),
				"HELPER" => array("it" => "color", "default" => "#f8f8f8", "Label" => " Default #f8f8f8", "Tip" => $msgstr["set_TIP_HELPER"]),
				"HELPER_FONTCOLOR" => array("it" => "color", "default" => "#666666", "Label" => " Default #666666", "Tip" => $msgstr["set_TIP_HELPER_FONTCOLOR"]),
				"FOOTER" => array("it" => "color", "default" => "#003366", "Label" => " Default: #003366", "Tip" => $msgstr["set_TIP_FOOTER"]),
				"FOOTER_FONTCOLOR" => array("it" => "color", "default" => "#f8f8f8", "Label" => " Default #f8f8f8", "Tip" => $msgstr["set_TIP_FOOTER_FONTCOLOR"]),
				"BG_WEB" => array("it" => "color", "Options" => "", "default" => "#ffffff", "Label" => " Default: #ffffff", "Tip" => $msgstr["set_TIP_BG_WEB"]),

				// Security
				"SECURITY" => array("it" => "title", "Label" => $msgstr["set_security"]),
				"CHANGE_PASSWORD" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_CHANGE_PASSWORD"]),
				"SECURE_PASSWORD_LENGTH" => array("it" => "text", "Options" => "", "size" => 1, "Tip" => $msgstr["set_TIP_SECURE_PASSWORD_LENGTH"]),
				"SECURE_PASSWORD_LEVEL" => array("it" => "radio", "Options" => "0;1;2;3;4;5", "Label" => "0;1;2;3;4;5", "Tip" => $msgstr["set_TIP_SECURE_PASSWORD_LEVEL"]),
				"CAPTCHA" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_CAPTCHA"]),

				// Other settings
				"OTHER_SET" => array("it" => "title", "Label" => $msgstr["set_other"]),

				"DIRTREE" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_DIRTREE"]),
				"DIRTREE_EXT" => array("it" => "textarea", "Options" => "", "size" => "50", "Tip" => $msgstr["set_TIP_DIRTREE_EXT"]),
				"LOGIN_ERROR_PAGE" => array("it" => "text", "size" => "50", "placeholder" => $msgstr["set_LOGIN_ERROR_PAGE"], "Tip" => $msgstr["set_TIP_LOGIN_ERROR_PAGE"]),
				"DEFAULT_LANG"  => array("it" => "select", "Options" => $lang_dir, "Label" => $lang_dir, "Tip" => $msgstr["set_DEFAULT_LANG"]),
				"NEW_WINDOW" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_NEW_WINDOW"]),
				"CHECK_VERSION" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_CHECK_VERSION"]),
				"SHOW_LANG_SELECTOR" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "default" => "Y", "Tip" => "Show/Hide Language Selector in Footer"),

				//Circulation module settings
				"CIRCULATION" => array("it" => "title", "Label" => $msgstr["loantit"]),
				"CALENDAR" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_CALENDAR"]),
				"RESERVATION" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_RESERVATION"]),
				"LOAN_POLICY" => array("it" => "check", "Options" => "BY_USER", "Label" => "By user", "Tip" => $msgstr["set_TIP_LOAN_POLICY"]),
				"EMAIL" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_EMAIL"]),
				"AC_SUSP" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_AC_SUSP"]),
				"ASK_LPN" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_AC_SUSP"]),
				"ILLOAN" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_ILLOAN"]),
			);
			$file = $db_path . "abcd.def";
			$help = "abcd.def";
			break;

		case "dr_path":
			$ini_vars = array(
				"GENERAL" => array("it" => "title", "Label" => $msgstr["set_database"]),
				"UNICODE" => array("it" => "radio", "Options" => "1;0", "Label" => $msgstr['set_yes'] . ";" . $msgstr['set_no'], "Tip" => $msgstr["set_TIP_UNICODE"]),
				"CISIS_VERSION" => array("it" => "radio", "Options" => $cisis_versions_allowed, "Label" => $cisis_versions_allowed, "Tip" => $msgstr["set_TIP_CISIS_VERSION"]),
				"SUBFIELDS_INLINE" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_SUBFIELDS_INLINE"]),
				"ROOT" => array("it" => "text", "size" => "50", "placeholder" => $db_path, "Tip" => $msgstr["set_TIP_ROOT"] . $arrHttp["base"] . "/root/"),
				"COLLECTION" => array("it" => "text", "size" => "50", "placeholder" => "", "Tip" => $msgstr["set_TIP_COLLECTION"] . $arrHttp["base"] . "/collection/"),
				"SORTLENGTH" => array("it" => "text", "size" => "3", "placeholder" => "", "Tip" => $msgstr["set_TIP_SORTLENGTH"]),
				"SORTBY" => array("it" => "radio", "Options" => "0;1", "Label" => $msgstr['tag'] . ";" . $msgstr['sort_format'], "Tip" => $msgstr["set_TIP_SORTBY"]),
				"SORTSPEC" => array("it" => "text", "size" => "50", "placeholder" => "", "Tip" => $msgstr["set_TIP_SORTSPEC"]),
				"VALID_IP" => array("it" => "text", "size" => "50", "placeholder" => "", "Tip" => $msgstr["set_TIP_VALID_IP"]),

				"BARCODESET" => array("it" => "title", "Label" => $msgstr["set_barcode_string"]),
				"barcode" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_barcode"]),
				"inventory_numeric" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_inventory_numeric"]),

				"TESAURUS" => array("it" => "title", "Label" => $msgstr["set_tesaurus"]),
				"tesaurus" => array("it" => "text", "size" => "50", "placeholder" => "", "Tip" => $msgstr["set_TIP_tesaurus"]),
				"prefix_search_tesaurus" => array("it" => "text", "size" => "50", "placeholder" => "", "Tip" => $msgstr["set_TIP_prefix_search_tesaurus"]),

				"OTHER" => array("it" => "title", "Label" => "Other"),
				"max_cn_length" => array("it" => "text", "size" => "1", "placeholder" => "", "Tip" => $msgstr["set_TIP_max_cn_length"]),
				"DIRTREE" => array("it" => "radio", "Options" => "Y;N", "Label" => "Yes;No", "Tip" => $msgstr["set_TIP_DIRTREE"]),
				"DIRTREE_EXT" => array("it" => "textarea", "Options" => "", "size" => "50", "Tip" => $msgstr["set_TIP_DIRTREE_EXT"]),
				"leader" => array("it" => "text", "size" => "50", "placeholder" => "", "Tip" => $msgstr["set_TIP_leader"]),

				"CIRCULATION" => array("it" => "title", "Label" => $msgstr["loantit"]),
				"max_inventory_length" => array("it" => "text", "size" => "1", "placeholder" => "", "Tip" => $msgstr["set_TIP_max_inventory_length"]),
			);
			$file = $db_path . $arrHttp["base"] . "/dr_path.def";
			$help = $arrHttp["base"] . ": dr_path.def";
			break;
	}
	?>
	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php
			echo $msgstr["editar"] . ": " . $help
			?>
		</div>
		<div class="actions">
			<?php

			switch ($arrHttp["Opcion"]) {
				case "abcd_styles":
					$backtoscript = "../settings/conf_abcd.php?reinicio=s";
					include "../common/inc_back.php";
					include "../common/inc_home.php";
					break;
				case "security":
					$backtoscript = "../settings/conf_abcd.php?reinicio=s";
					include "../common/inc_back.php";
					break;
				case "dr_path":
					$backtoscript = "../dbadmin/menu_modificardb.php?base=" . $arrHttp["base"] . "&encabezado=s";
					include "../common/inc_back.php";
					include "../common/inc_home.php";
					break;
			}
			if (!isset($arrHttp["Accion"]) or $arrHttp["Accion"] !== "actualizar") {
				$savescript = "javascript:Enviar()";
				include "../common/inc_save.php";
			}
			?>
		</div>
		<div class="spacer">&#160;</div>
	</div>

	<?php
	include "../common/inc_div-helper.php";

	$ini = array();
	$modulo = array();
	$mod = "";
	$cleanLOGO = "";
	if (isset($arrHttp["clean_LOGO"])) $cleanLOGO = "LOGO";
	// Read a possible existing abcd.def/dr_path.def file
	if (file_exists($file)) {
		$fp = file($file);
		foreach ($fp as $key => $value) {
			$value = trim($value);
			if ($value != "") {
				$x = explode('=', $value);
				$x[0] = trim($x[0]);
				$x[1] = trim($x[1]);
				if (!isset($ini_vars[$x[0]])) continue;
				#if ($x[0]=="LOGO" ) echo "logo=".$x[1]=""."<br>";
				#if ($x[0]=="LOGO" && $cleanLOGO!="") $x[1]="";
				if ($x[0] == "DIRTREE_EXT" and trim($x[1]) == "") $x[1] = "*.def,*.iso,*.png,*.gif,*.jpg,*.pdf,*.xrf,*.mst,*.n01,*.n02,*.l01,*.l02,*.cnt,*.ifp,*.fmt,*.fdt,*.pft,*.fst,*.tab,*.txt,*.par,*.html,*.zip,";
				if ($mod == "Y") {
					$modulo[$x[0]] = $x[1];
				} else {
					if (isset($x[1])) {
						$ini[$x[0]] = $x[1];
					} else {
						if (trim($x[0]) == "[MODULOS]") {
							$modulo[$x[0]] = $x[0];
							$mod = "Y";
						} else {
							$ini[$x[0]] = $x[0];
						}
					}
				}
			}
		}
	}

	if (!isset($ini["DIRTREE_EXT"]) and $arrHttp["Opcion"] != "css") {
		$ini["DIRTREE_EXT"] = "*.def,*.iso,*.png,*.gif,*.jpg,*.pdf,*.xrf,*.mst,*.n01,*.n02,*.l01,*.l02,*.cnt,*.ifp,*.fmt,*.fdt,*.pft,*.fst,*.tab,*.txt,*.par,*.html,*.zip,";
	}
	?>
	<div class="middle">
		<div class="formContent">

			<form name="maintenance" method="post" enctype="multipart/form-data" action="editar_abcd_def.php" onsubmit="return false">
				<input type=hidden name=Opcion value=<?php echo $arrHttp["Opcion"] ?>>

				<?php
				if (isset($arrHttp["base"]))
					echo "<input type=hidden name=base value=" . $arrHttp["base"] . ">\n";

				if (!isset($arrHttp["Accion"])) {
					echo "<input type=hidden name=Accion value=\"actualizar\">\n";

					// --- ADDED CONTROLS FOR EXPAND/COLLAPSE ALL ---
				?>
					<div style="margin: 10px 0;">
						<button type="button" class="bt bt-gray" onclick="toggleAllAccordions(true)"><i class="fas fa-expand-arrows-alt"></i> <?php echo isset($msgstr["expand_all"]) ? $msgstr["expand_all"] : "Expand All"; ?></button>
						<button type="button" class="bt bt-gray" onclick="toggleAllAccordions(false)"><i class="fas fa-compress-arrows-alt"></i> <?php echo isset($msgstr["collapse_all"]) ? $msgstr["collapse_all"] : "Collapse All"; ?></button>
					</div>
					<?php
					// ---------------------------------------------

					echo "<div class=\"panel\"  id=\"panel\">";
					echo "<table class=\"striped\" cellspacing=8 width=80% align=center >";

					LeerIniFile($ini_vars, $ini);
					foreach ($ini as $key => $val) {
						if (!isset($ini_vars[$key]) and trim($val) != "")
							echo "<tr><td>$key</td><td><input type=text name=ini_$key value=\"$val\"></td></tr>";
					}
					?>
					</table>
		</div>
		</form>

		<a class="bt bt-green" href="javascript:Enviar()"><i class="far fa-save"></i> <?php echo $msgstr["actualizar"] ?></a>
		<a class="bt bt-gray" href="<?php echo $backtoscript; ?>"><i class="far fa-window-close"></i> &nbsp;<?php echo $msgstr["cancel"] ?></a>

	<?php
				} else {
					saveDef();
				}

	?>
	</div>
	</div>

	<script>
		// Logic to handle Accordions (Single and All)
		var acc = document.getElementsByClassName("accordion");
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

		function toggleAllAccordions(expand) {
			var panels = document.getElementsByClassName("panel");
			var accordions = document.getElementsByClassName("accordion");
			for (var i = 0; i < panels.length; i++) {
				panels[i].style.display = expand ? "block" : "none";
				if (expand) {
					accordions[i].classList.add("active");
				} else {
					accordions[i].classList.remove("active");
				}
			}
		}
	</script>
	<?php include("../common/footer.php"); ?>
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
*/


session_start();

if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}

//foreach ($_REQUEST AS $var=>$value)  echo "$var=>$value<br>";
include("../common/get_post.php");
include ("../config.php");

include("../common/header.php");
include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/dbadmin.php");
include("../lang/prestamo.php");


if (isset($_REQUEST['ini_DIRECTORY_SYSTEM_UPLOADS'])) {
	$target_dir=$_REQUEST['ini_DIRECTORY_SYSTEM_UPLOADS'];
} elseif (isset($def['DIRECTORY_SYSTEM_UPLOADS'])) {
	$target_dir = $def['DIRECTORY_SYSTEM_UPLOADS'];
} else {
	$target_dir="";
}


//If option 1 = htdocs/uploads; If option 2 = bases/par/uploads
switch ($target_dir){
	case "1":
		$target_dir=$ABCD_scripts_path."uploads/";
		break;
	case "2":
		$target_dir=$db_path."uploads/";
		break;
	default:
		echo '<style>#STYLES { display: none; }</style>';
		break;
}


if (!isset($_SESSION["login"])or $_SESSION["profile"]!="adm" ){
	echo "<script>
	      alert('".$msgstr["invalidright"]."')
          history.back();
          </script>";
    die;
}
//$Permiso=$_SESSION["permiso"];

//SE LEE LA LISTA DE MENSAJES DISPONIBLEE
$l=$msg_path.'lang/';
if ($handle = opendir($l)) {
	$lang_dir="";
    while (false !== ($entry = readdir($handle))) {
        if ($entry!="." and $entry!=".." and is_dir($l.$entry)){
 			if ($lang_dir=="")
 				$lang_dir=$entry;
 			else
 				$lang_dir.=";".$entry;
        }
    }
    closedir($handle);
}


// Databases list
$lista_bases=array();
if (file_exists($db_path."bases.dat")){
	$fp = file($db_path."bases.dat");
	foreach ($fp as $linea){
		$linea=trim($linea);
		if ($linea!="") {
			$ix=strpos($linea,"|");
			$llave=trim(substr($linea,0,$ix));
			$lista_bases[$llave]=trim(substr($linea,$ix+1));
		}
	}
}
function databases() {
	global $lista_bases, $arrHttp, $database_list_v, $database_list_n;
	$i=-1;
	$output="";
	foreach ($lista_bases as $keydb => $value) {
		$xselected="";
		$value=trim($value);
		$t=explode('|',$value);
		if (isset($Permiso["db_".$keydb]) or isset($_SESSION["permiso"]["db_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"])){
			if (isset($arrHttp["base"]) and $arrHttp["base"]==$keydb or count($lista_bases)==1) $xselected=" selected";
			//print_r ($keydb.";");
			$output.=$keydb.";";
			//$database_list_n=$t[0].";";
		}
	}
	return $output;
}

$databases_codes=databases();


//================= Function to read the abcd.def file ==========
function LeerIniFile($ini_vars,$ini,$tipo){
global $msg_path, $msgstr, $target_dir, $def, $folder_logo;

	if ($tipo==1) 
		$pref="ini_"; 
	else $pref="mod_";
	foreach ($ini_vars as $key=>$Opt){

		if (isset($Opt["default"])) {
			$defaultDef = $Opt["default"];
		} else {
			$defaultDef="";
		}

		if (isset($def[$key])) {
			$valueDef = $def[$key]; 
		}	elseif  (isset($ini[$key])) {
			$valueDef = $ini[$key];
		} else {
			$valueDef = $defaultDef;
		}

		if ($Opt["it"]=="title"){
 		  echo "</table></div><button type=\"button\" class=\"accordion\" id=\"$key\">".$Opt["Label"]."</button>";
          echo "<div class=\"panel\"><table class=\"striped\"  align=center >\n";
 		    continue;
 		}else{
 			echo "<tr>
 			<td width=200px>
 			".$msgstr['set_'.$key]."
 			</td>
 			<td>";
		}
		switch ($Opt["it"]){

			// Field type color
			case "color":
				$opc=explode(";",$Opt["Label"]);
		   		echo "<input type='color' class='m-1' placeholder='".$Opt["default"]."' data-value='".$Opt["default"]."' name=ini_$key id=ini_$key size=";
		   		if (isset($Opt["size"]))
		   			echo trim($Opt["size"]);
				else
					echo "100";
		   		echo " value=".$valueDef.">";
				echo "<small></small> &nbsp;<button type=\"button\" clss=\" bt bt-sm bt-gray\" onclick=\"return cleanSet('".$Opt["default"]."','ini_".$key."')\" title=".$Opt["Label"]."><i class=\"fas fa-eraser\"></i></button> ";
				break;  			

			// Field type text
		   	case "text":
		   		echo "<input type=text name=ini_$key size=";
		   		if (isset($Opt["size"]))
		   			echo trim($Opt["size"]);
				else
					echo "100";
		   		echo " value='".$valueDef."'";
					if (isset($Opt["placeholder"])) {
						echo " placeholder='".$Opt["placeholder"]."'";
					}
				if (isset($Opt["disabled"])) echo "disabled";
		   		echo ">";
				break;

			// Field type radio
			case "radio":
				$opc=explode(";",$Opt["Options"]);
				$label=explode(";",$Opt["Label"]);				
				foreach (array_combine($opc, $label) as $o => $l) {
					echo "&nbsp;<input type=radio name=ini_$key value='$o' ";
						if ($valueDef==$o)
							echo " checked";
					echo ">";
						if (isset($Opt["Label"])) {  
						echo  "<label>".$l ."</label>&nbsp;";
						} else {
						echo "<label>".$o."</label>&nbsp;";
					 }
				}
				break;

			// Field type select
			case "select":
				$opc=explode(";",$Opt["Options"]);
				$label=explode(";",$Opt["Label"]);			
				echo "<select name='ini_$key' id='ini_$key' onchange=\"select('ini_".$key."')\">";	
				echo "<option></option>";
				foreach (array_combine($opc, $label) as $o => $l) {
					echo "<option value='".$o."'"; 
					if (isset($ini[$key]))  {
						if ($ini[$key]==$o)
							echo " selected";
					}
					echo ">";			
						if (isset($Opt["Label"])) {  
						echo $l."</option>";
						} else {
						echo $o."</option>";
					 }
				}
				echo "</select>";
				break;			
			
			// Field type checkbox
			case "check":
				$opc=explode(";",$Opt["Options"]);
				$label=explode(";",$Opt["Label"]);

				foreach (array_combine($opc, $label) as $o => $l) {
					echo "<input type=checkbox name=$pref$key value='$o' ";
						if ($valueDef==$o)
							echo " checked";
					echo ">";
						if (isset($Opt["Label"])) {  
						echo  "<label>".$l."</label>&nbsp;";
						} else {
						echo "<label>".$o."</label>&nbsp;";
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
		   		echo " value=".$valueDef."'>\r\n";
				break;                


				// Field type file
		   	case "file":
		   		if ((isset($ini[$key])) && (!empty($ini[$key])) ) {
					$file_value= $ini[$key];
				} else {
					$file_value= $Opt["default"];
				}
		   	echo "<input type=\"file\" name=ini_".$key." id=ini_".$key." accept=\"image/png, image/jpeg , image/jpg\" onchange=\"preview_image ('".$key."',event)\" class='bt bt-light' ";
		   		echo " value='";
		   		if (isset($ini[$key])) echo $ini[$key];
				echo "'>\r\n";
		   		echo "<small>  Max: 2MB </small>";

			 		if ( (!isset($key)) OR (empty($ini[$key])) ) {
				echo '<br><div class="mb-4"><img class="p-3 bg-gray-300 p-3 mt-3 " width="100" id="'.$Opt["ID"].'"/>'.$file_value.'</div> ';
				} else {
					echo '<br><div class="mb-4"><img class="p-3 bg-gray-300 p-3 mt-3 " width="100" src='.$folder_logo.$file_value.' id="'.$Opt["ID"].'"/> </div>';
				}
				break;						


		}
		echo "</td>";
		echo "<td>";
		if (isset($Opt["Tip"])) {
			echo "<small>".$Opt["Tip"]."</small>";
		}

		echo"</td>";
		echo "</tr>";
	}
}
//============= end function ========
?>

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
}

.active, .accordion:hover {
  background-color: #ccc; 
}

.panel {
  padding: 18px;
  display: none;
  background-color: white;
  overflow: hidden;
}

</style>

<body>
<link rel="stylesheet" type="text/css" href="/assets/css/text-cursor.css">
<script language="javascript" type="text/javascript">

function Enviar(){
	document.maintenance.submit()
}

//Function that resets the colour values
function cleanSet(v,campo){
    document.getElementById(campo).value = v;
    //document.getElementById(campo).fileupload.value=v;
    console.log(v);
    return false;
}

function preview_image(campo, event) {
 var reader = new FileReader();
 reader.onload = function()  {
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




</script>



<?php
include("../common/institutional_info.php");

$set_mod = $arrHttp["Opcion"];

global $database_list;

switch ($set_mod){

	case "abcd_styles":
		$ini_vars=array(
					// Identification and sites of the institution

					// Security
					"FOLDERS" => array("it"=>"title","Label"=>$msgstr["set_folders"]),

					"MAIN_FOLDER" => array("it"=>"text","size"=>"70","placeholder"=>$ABCD_scripts_path,"Tip"=>$msgstr["set_TIP_MAIN_FOLDER"],"disabled"=>"disabled",),					
					"DATABASES_FOLDER" => array("it"=>"text","size"=>"70","placeholder"=>$db_path,"Tip"=>$msgstr["set_TIP_DATABASES_FOLDER"],"disabled"=>"disabled",),
					"DIRECTORY_SYSTEM_UPLOADS"  => array("it"=>"select","Options"=>"1;2","Label"=>$msgstr["set_MAIN_FOLDER"].";".$msgstr["set_DATABASES_FOLDER"],"Tip"=>$msgstr["set_TIP_DIRECTORY_SYSTEM_UPLOADS"]),							
					"IDENTIFICATION" => array("it"=>"title","Label"=>$msgstr["set_identification"]),

					"INSTITUTION_NAME" => array("it"=>"text","size"=>"70","placeholder"=>$msgstr["set_INSTITUTION_NAME_PH"],"Tip"=>$msgstr["set_TIP_INSTITUTION_NAME"]),
					"INSTITUTION_URL" => array("it"=>"text","size"=>"70","placeholder"=>$msgstr["set_INSTITUTION_URL_PH"],"Tip"=>$msgstr["set_TIP_INSTITUTION_URL"]),

					"RESPONSIBLE_NAME" => array("it"=>"text","Options"=>"","size"=>"70","placeholder"=>$msgstr["set_RESPONSIBLE_NAME_PH"],"Tip"=>$msgstr["set_TIP_RESPONSIBLE_NAME"]),
					"RESPONSIBLE_URL" => array("it"=>"text","Options"=>"","size"=>"70","placeholder"=>$msgstr["set_RESPONSIBLE_URL_PH"],"Tip"=>$msgstr["set_TIP_RESPONSIBLE_URL"]),					

					"ADDITIONAL_LINK_TITLE" => array("it"=>"text","Options"=>"","size"=>"70","placeholder"=>$msgstr["set_ADDITIONAL_LINK_TITLE_PH"],"Tip"=>$msgstr["set_TIP_ADDITIONAL_LINK_TITLE"]),
					"URL_ADDITIONAL_LINK" => array("it"=>"text","Options"=>"","size"=>"70","placeholder"=>$msgstr["set_URL_ADDITIONAL_LINK_PH"],"Tip"=>$msgstr["set_TIP_URL_ADDITIONAL_LINK"]),

					// Local settings
					"LOCAL_SET" => array("it"=>"title","Label"=>$msgstr["set_local"]),
					"MAIN_DATABASE"  => array("it"=>"select","Options"=>$databases_codes,"Label"=>$databases_codes,"Tip"=>$msgstr["set_TIP_MAIN_DATABASE"]),
					"DEFAULT_LANG"  => array("it"=>"select","Options"=>$lang_dir,"Label"=>$lang_dir,"Tip"=>$msgstr["set_DEFAULT_LANG"]),
					"DEFAULT_DBLANG"  => array("it"=>"select","Options"=>$lang_dir,"Label"=>$lang_dir,"Tip"=>$msgstr["set_DEFAULT_DBLANG"]),
					"DATE_FORMAT" => array("it"=>"text","size"=>"50","placeholder"=>"DD/MM/YY","Tip"=>$msgstr["set_TIP_DATE_FORMAT"]),

					"NEW_WINDOW" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_NEW_WINDOW"]),


					// Logos
					"STYLES" => array("it"=>"title","Label"=>$msgstr["set_logo_css"],"Tip"=>$msgstr["set_TIP_INSTITUTION_NAME"]),
					"LOGO_DEFAULT" => array("it"=>"check","Options"=>"Y","Label"=>"Yes","Tip"=>$msgstr["set_TIP_LOGO_DEFAULT"]),
					"LOGO" => array("it"=>"file","Options"=>"","Label"=>"Reset","default"=>"","ID"=>"LOGO","Tip"=>$msgstr["set_TIP_LOGO"]),

					"RESPONSIBLE_LOGO_DEFAULT" => array("it"=>"check","Options"=>"Y","Label"=>"Yes","Tip"=>$msgstr["set_TIP_RESPONSIBLE_LOGO_DEFAULT"]),
					"RESPONSIBLE_LOGO" => array("it"=>"file","Options"=>"","Label"=>"Reset","default"=>"","ID"=>"RESPONSIBLE_LOGO","Tip"=>$msgstr["set_TIP_RESPONSIBLE_LOGO"]),	

					// Colours
		     	"COLORS" => array("it"=>"title","Label"=>$msgstr["set_colors"]),	 			
					"BODY_BACKGROUND" => array("it"=>"color","default"=>"#ffffff","Label"=>" Default: #ffffff or (R: 255, G: 255, B: 255)","Tip"=>$msgstr["set_TIP_BODY_BACKGROUND"]),
					"COLOR_LINK" => array("it"=>"color","default"=>"#336699","Label"=>" Default #336699 or (R: 51, G: 102, B: 153)","Tip"=>$msgstr["set_TIP_COLOR_LINK"]),
					"HEADING" => array("it"=>"color","default"=>"#003366","Label"=>" Default #003366 or (R: 0, G: 51, B: 102)","Tip"=>$msgstr["set_TIP_HEADING"]),
					"HEADING_FONTCOLOR" => array("it"=>"color","default"=>"#f8f8f8","Label"=>" Default: #f8f8f8 or (R: 248, G: 248, B: 248)" ,"Tip"=>$msgstr["set_TIP_HEADING_FONTCOLOR"]),
					"SECTIONINFO" => array("it"=>"color","default"=>"#336699","Label"=>" Default #336699 or (R: 51, G: 102, B: 153)","Tip"=>$msgstr["set_TIP_SECTIONINFO"]),
					"SECTIONINFO_FONTCOLOR" => array("it"=>"color","default"=>"#ffffff","Label"=>" Default #ffffff or (R: 255, G: 255, B: 255)","Tip"=>$msgstr["set_TIP_SECTIONINFO_FONTCOLOR"]),
					"TOOLBAR" => array("it"=>"color","default"=>"#f8f8f8","Label"=>" Default #f8f8f8 or (R: 248, G: 248, B: 248)","Tip"=>$msgstr["set_TIP_TOOLBAR"]),
					"HELPER" => array("it"=>"color","default"=>"#f8f8f8","Label"=>" Default #f8f8f8 or (R: 248, G: 248, B: 248)","Tip"=>$msgstr["set_TIP_HELPER"]),
					"HELPER_FONTCOLOR" => array("it"=>"color","default"=>"#666666","Label"=>" Default #666666 or (R: 102, G: 102, B: 102)","Tip"=>$msgstr["set_TIP_HELPER_FONTCOLOR"]),
					"FOOTER" => array("it"=>"color","default"=>"#003366","Label"=>" Default: #003366 or (R: 0, G: 51, B: 102)","Tip"=>$msgstr["set_TIP_FOOTER"]),
					"FOOTER_FONTCOLOR" => array("it"=>"color","default"=>"#f8f8f8","Label"=>" Default #f8f8f8 or (R: 248, G: 248, B: 248)","Tip"=>$msgstr["set_TIP_FOOTER_FONTCOLOR"]),
					"BG_WEB" => array("it"=>"color","Options"=>"","default"=>"#ffffff","Label"=>" Default: #ffffff or (R: 255, G: 255, B: 255)","Tip"=>$msgstr["set_TIP_BG_WEB"]),
					
					//Circulation module settings
					"CIRCULATION"=>array("it"=>"title","Label"=>$msgstr["loantit"]),
					"CALENDAR" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_CALENDAR"]),
					"RESERVATION" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_RESERVATION"]),
					"LOAN_POLICY" => array("it"=>"check","Options"=>"BY_USER","Label"=>"By user","Tip"=>$msgstr["set_TIP_LOAN_POLICY"]),
					"EMAIL" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_EMAIL"]),
					"AC_SUSP" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_AC_SUSP"]),
					"ASK_LPN" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_AC_SUSP"]),
					"ILLOAN" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_ILLOAN"]),		

					// Security
					"SECURITY" => array("it"=>"title","Label"=>$msgstr["set_security"]),
  				"UNICODE" => array("it"=>"radio","Options"=>"1;0","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_UNICODE"]),
					"CISIS_VERSION" => array("it"=>"radio","Options"=>$cisis_versions_allowed,"Label"=>$cisis_versions_allowed,"Tip"=>$msgstr["set_TIP_CISIS_VERSION"]),

					"REG_LOG" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_REG_LOG"]),
					"DIRTREE" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_DIRTREE"]),
					"DIRTREE_EXT"=> array("it"=>"text","Options"=>"","size"=>"70","Tip"=>$msgstr["set_TIP_DIRTREE_EXT"]),
					"CHANGE_PASSWORD" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_CHANGE_PASSWORD"]),					
					"SECURE_PASSWORD_LENGTH" => array("it"=>"text","Options"=>"","size"=>1,"Tip"=>$msgstr["set_TIP_SECURE_PASSWORD_LENGTH"]),
					"SECURE_PASSWORD_LEVEL" => array("it"=>"radio","Options"=>"0;1;2;3;4;5","Label"=>"0;1;2;3;4;5","Tip"=>$msgstr["set_TIP_SECURE_PASSWORD_LEVEL"]),

					);
	/*
	          "MODULES" => array("it"=>"title","Label"=>"<HR size=2>"),
	$mod_vars=array("TITLE" => array("it"=>"text","Options"=>""),
						"SCRIPT" => array("it"=>"text","Options"=>""),
						"BUTTON" => array("it"=>"text","Options"=>""),
						"SELBASE" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No")
						);*/
		$file=$db_path."abcd.def";
		$help="abcd.def";
		break;

	case "dr_path":
		$ini_vars=array(
						"GENERAL" => array("it"=>"title","Label"=>$msgstr["set_general_db"]),
						"UNICODE" => array("it"=>"radio","Options"=>"1;0","Label"=>$msgstr['set_yes'].";".$msgstr['set_no'],"Tip"=>$msgstr["set_TIP_UNICODE"]),
						"CISIS_VERSION" => array("it"=>"radio","Options"=>$cisis_versions_allowed,"Label"=>$cisis_versions_allowed,"Tip"=>$msgstr["set_TIP_CISIS_VERSION"]),
						"ROOT" => array("it"=>"text","size"=>"70","placeholder"=>$db_path,"Tip"=>$msgstr["set_TIP_ROOT"].$arrHttp["base"]."/root/"),											
						"COLLECTION" => array("it"=>"text","size"=>"70","placeholder"=>"","Tip"=>$msgstr["set_TIP_COLLECTION"].$arrHttp["base"]."/collection/"),
						


						"INVENTORY"=>array("it"=>"title","Label"=>"Inventory"),
						"barcode" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_barcode"]),
						"inventory_numeric" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_inventory_numeric"]),						
						"max_inventory_length" => array("it"=>"text","size"=>"70","placeholder"=>"","Tip"=>$msgstr["set_TIP_max_inventory_length"]),
						"max_cn_length" => array("it"=>"text","size"=>"70","placeholder"=>"","Tip"=>$msgstr["set_TIP_max_cn_length"]),
						
						
						"TESAURUS"=>array("it"=>"title","Label"=>"Tesaurus"),
						"tesaurus" => array("it"=>"text","size"=>"70","placeholder"=>"","Tip"=>$msgstr["set_TIP_tesaurus"]),
						"prefix_search_tesaurus" => array("it"=>"text","size"=>"70","placeholder"=>"","Tip"=>$msgstr["set_TIP_prefix_search_tesaurus"]),
						
						"OTHER"=>array("it"=>"title","Label"=>"Other"),
						"DIRTREE" => array("it"=>"radio","Options"=>"Y;N","Label"=>"Yes;No","Tip"=>$msgstr["set_TIP_DIRTREE"]),					
						"DIRTREE_EXT"=> array("it"=>"text","Options"=>"","size"=>"70","Tip"=>$msgstr["set_TIP_DIRTREE_EXT"]),
						"leader"=> array("it"=>"text","size"=>"70","placeholder"=>"","Tip"=>$msgstr["set_TIP_leader"]),

						);
		$file=$db_path.$arrHttp["base"]."/dr_path.def";
		$help=$arrHttp["base"].": dr_path.def";
		break;
}



?>


<div class="sectionInfo">
	<div class="breadcrumb">
<?php 
        echo $msgstr["editar"].": ".$help
?>
	</div>
	<div class="actions">
<?php


switch ($arrHttp["Opcion"]){
	case "abcd_styles":
		$backtoscript="../settings/conf_abcd.php?reinicio=s";
		include "../common/inc_back.php";

		break;
	case "security":

		$backtoscript="../settings/conf_abcd.php?reinicio=s";
		include "../common/inc_back.php";

		break;
	case "dr_path":

		$backtoscript="../dbadmin/menu_modificardb.php?base=".$arrHttp["base"]."&encabezado=s";
		include "../common/inc_back.php";
		include "../common/inc_home.php";

		break;
}



if (!isset($arrHttp["Accion"]) or $arrHttp["Accion"]!=="actualizar"){
$savescript="javascript:Enviar()";
include "../common/inc_save.php";	

}

?>
  </div>
	<div class="spacer">&#160;</div>
	</div>

<?php
include "../common/inc_div-helper.php";

$ini=array();
$modulo=array();
$mod="";
// Read a possible existing abcd.def/dr_path.def file
if (file_exists($file)){
	$fp=file($file);
	foreach ($fp as $key=>$value){
		$value=trim($value);
		if ($value!=""){
			$x=explode('=',$value);
			$x[0]=trim($x[0]);
			$x[1]=trim($x[1]);
			if (!isset($ini_vars[$x[0]])) continue;
			if ($x[0]=="DIRTREE_EXT" and trim($x[1])=="") $x[1]="*.def,*.iso,*.png,*.gif,*.jpg,*.pdf,*.xrf,*.mst,*.n01,*.n02,*.l01,*.l02,*.cnt,*.ifp,*.fmt,*.fdt,*.pft,*.fst,*.tab,*.txt,*.par,*.html,*.zip,";
			if ($mod=="Y"){
				$modulo[$x[0]]=$x[1];
			}else{
				if (isset($x[1])){
					$ini[$x[0]]=$x[1];
				}else{
					if (trim($x[0])=="[MODULOS]"){
						$modulo[$x[0]]=$x[0];
						$mod="Y";
					}else{
						$ini[$x[0]]=$x[0];
					}
				}
			}
		}
	}
}

/* UPLOAD IMAGE */
function uplodimages($fieldname,$fileimg) {
global $fieldname, $fp, $msg_path, $msgstr,$def,$target_dir;

if (!is_dir($target_dir)) {
    mkdir($target_dir);
}
	
$target_file = $target_dir.basename($_FILES[$fileimg]["name"]);

$temp = explode(".", $target_file);
$newfilename = $fieldname.'.'. end($temp);

$target_file = $target_dir.basename($newfilename);

$uploadOk = 1;

$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
  $check = getimagesize($_FILES[$fileimg]["tmp_name"]);
  if($check !== false) {
    echo "File is an image - " . $check["mime"] . ".";
    $uploadOk = 1;
  } else {
    echo "File is not an image.";
    $uploadOk = 0;
  }
}

// Check if file already exists
/*
if (file_exists($target_file)) {
	fwrite($fp,$fieldname."=".$def[$fieldname]."\n");	
  //echo "Sorry, file already exists.";
  //$uploadOk = 0;
}
*/

// Check file size
if ($_FILES[$fileimg]["size"] > 2097152) {
  echo $msgstr["set_ERROR_SIZE"];
  $uploadOk = 0;
}

// Allow certain file formats
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
&& $imageFileType != "gif" ) {
 // echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed."."<br>";
  $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
  echo $msgstr["set_notsentfile"]."<br>";;
// if everything is ok, try to upload file
} else {

  if (move_uploaded_file($_FILES[$fileimg]["tmp_name"], $target_file)) {
    echo $msgstr["set_thefile"]."&nbsp;<b>".htmlspecialchars( basename( $_FILES[$fileimg]["name"]))."</b>&nbsp;".$msgstr["set_has_been_upload"]."<br>";

  } else {
    echo $msgstr["set_error_upload"]."<br>";
       fwrite($fp,$fieldname."=".$fileimg."\n");
  }
}	

}
/* END IMAGE UPLOAD */

if (!isset($ini["DIRTREE_EXT"]) and $arrHttp["Opcion"]!="css")
	$ini["DIRTREE_EXT"]="*.def,*.iso,*.png,*.gif,*.jpg,*.pdf,*.xrf,*.mst,*.n01,*.n02,*.l01,*.l02,*.cnt,*.ifp,*.fmt,*.fdt,*.pft,*.fst,*.tab,*.txt,*.par,*.html,*.zip,";




function saveDef() {
	global $fieldname, $fp, $msg_path, $msgstr,$def,$target_dir, $file, $help, $arrHttp;

    echo '<pre  id="typewriter">';
    echo "<b>".$msgstr["set_APPLY"]."</b><br>";
    $fp=@fopen($file,"w");
    if (!$fp) {
        //Checks for errors	
        $contents_error= error_get_last();
        echo "<font color=red><b>".$msgstr["copenfile"]." ".$help."</b> : ".$contents_error["message"];
    } else { 
        //Saves the parameters in the .def file
        foreach ($arrHttp as $key=>$Opt){
           if (substr($key,0,4)=="ini_"){
                $key=substr($key,4);
                echo $key."=".$arrHttp["ini_".$key]."<br>";
                fwrite($fp,$key."=".trim($arrHttp["ini_".$key])."\n");
            }
        }
        echo '<p id="cursor-line" class="visible">&gt;&gt; <span class="typed-cursor">&#9608;</span></p><br>';

        // Upload and save file names. 
        $fileslist=array("ini_LOGO", "ini_RESPONSIBLE_LOGO");
        foreach ($fileslist as $fileimg){
            $fieldname=substr($fileimg, strlen("ini_"));
            if (isset($_FILES[$fileimg]["name"])) {
                if ($_FILES[$fileimg]["name"]) {
                    $temp = explode(".", $_FILES[$fileimg]["name"]);
                    $newfilename = $fieldname.'.'. end($temp);
                    $file_name  = $newfilename;
                    if (isset($file_name))
                        fwrite($fp,$fieldname."=".$file_name ."\n");
                } else {
                    //fwrite($fp,$fieldname."=".$def[$fieldname]."\n");
                    if (isset($def[$fieldname]))
                    fwrite($fp,$fieldname."=".$def[$fieldname]."\n");
                }
                uplodimages($fieldname,$fileimg);  			
            }
        }
        if (isset($arrHttp["mod_TITLE"])){
            echo "[MODULOS]<BR>";
            fwrite($fp,"[MODULOS]\n");
            foreach ($arrHttp as $key=>$Opt){
                if (substr($key,0,4)=="mod_"){
                    $key=substr($key,4);
                    echo $key."=".$arrHttp["mod_".$key]."<br>";
                    fwrite($fp,$key."=".trim($arrHttp["mod_".$key])."\n");
                }
            }
        }
        fclose($fp);
        
        echo '<span class="string-highlight">'.$help." - ".$msgstr["updated"].'! </span>';
        //echo "<a href=editar_abcd_def.php?Opcion=".$_REQUEST["Opcion"]."&base=".$_REQUEST["base"].">".$msgstr["edit"]."</a>";
    }
    echo "</pre>";
}


 function page_redirect()  {
   echo '<meta http-equiv="refresh" content="5; URL=editar_abcd_def.php?Opcion=abcd_styles">';
   exit; 
 }

?>
<div class="middle">
	<div class="formContent" >


<form name="maintenance" method="post" enctype="multipart/form-data" action="editar_abcd_def.php" onsubmit="return false">
<input type=hidden name=Opcion value=<?php echo $arrHttp["Opcion"]?>>

<?php
if (isset($arrHttp["base"]))
	echo "<input type=hidden name=base value=".$arrHttp["base"].">\n";

if (!isset($arrHttp["Accion"])){ 
 	echo "<input type=hidden name=Accion value=\"actualizar\">\n";

	echo "<div class=\"panel\"  id=\"panel\">";
	echo "<table class=\"striped\" cellspacing=8 width=80% align=center >";

	LeerIniFile($ini_vars,$ini,"1");
	foreach ($ini as $key=>$val){
		if (!isset($ini_vars[$key]) and trim($val)!="") 
			echo "<tr><td>$key</td><td><input type=text name=ini_$key value=\"$val\"></td></tr>";
	}
?>
	</table>
</div>
</form>

<a class="bt bt-green" href="javascript:Enviar()" ><i class="far fa-save"></i> <?php echo $msgstr["actualizar"]?></a>
<a class="bt bt-gray" href="<?php echo $backtoscript;?>"><i class="far fa-window-close"></i> &nbsp;<?php echo $msgstr["cancel"]?></a>

<?php	
} else {
	saveDef();
	echo '<script type="text/javascript" src="/assets/js/typing.js"></script>';
	//page_redirect();// Redirection is too rigid
}

?>
	</div>	
</div>
 
<script>
// The headings are the main lines of the accordions
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
</script>

<?php include("../common/footer.php");?>

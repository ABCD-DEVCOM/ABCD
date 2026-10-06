<?php
/* Modifications
20210314 fho4abcd html + improved hovering to submenu and subsubmenu
20210314 fho4abcd removed css xbox/xtext declarations (attempt without x was disappointing)
20210314 fho4abcd Menu: removed duplicate READ DB/ISO, removed empty button
20210315 fho4abcd Menu: Other code for Export ISO whith MX.Code to frame and back button
20210317 fho4abcd Initilalize DB uses now code inicio_bd.php
20210325 fho4abcd Reorganized exports: all exports by 1 menu item. Others removed
20210411 fho4abcd unlock_db_retag_check -> unlock_db_retag
20210418 fho4abcd vmxISO_load -> vmx_import_iso
20210421 fho4abcd Commented mxdbread (dangerous and currently not working correct)
20210421 fho4abcd Improved text Import ISO (no longer with MX in the title)
20210516 fho4abcd Add function upload file
20210526 fho4abcd function for convert replaced. Update convert menu text (translated text) add option to convert language
20210607 fho4abcd Add function to convert an iso to utf-8
20210702 fho4abcd Add function to match an iso with an fdt
20210705 fho4abcd Uncomment mxdbread, move to db maintenance and make it work for current mst file (should be safe)
20210718 fho4abcd Add function DSpace bridge
20210829 fho4abcd Modified Import Documents
20210903 fho4abcd Add options to configure Digital documents functionality and Repair Digital Documents
20211029 edsz14 Replace vmx_fullinv.php by fullinv.php
20211114 fho4abcd Add create_gizmo
20211214 fho4abcd Digital documents to top menu
20211215 fho4abcd lock_bd.php->protect_db.php
20211216 fho4abcd Remove duplicate copiesocurrenciesreport
20211217 fho4abcd Remove duplicate addcopiesdatabase,addloanobject,addloanobjectcopies
20211228 fho4abcd Separate duplicate barcode entries
20220203 fho4abcd Improve dirtree calls
20220206 fho4abcd Remove dirtree to top folder (function is in Adminstration part)
20220609 fho4abcd Add module to configure digital document types
20240627 fho4abcd Add Manage gizmo's, removed create gizmo (covered my Manage gizmo's)
20250924 fho4abcd Add sort option, translate cleandb
20260315 fho4abcd Add convert csv to iso option
*/
$lang = $_SESSION["lang"];
unset($_SESSION["Browse_Expresion"]);
//PARA ELIMINAR LAS VARIABLES DE SESSION DEL DIRTREE
unset($_SESSION["root_base"]);
unset($_SESSION["dir_base"]);
unset($_SESSION["Folder_Name"]);
unset($_SESSION["Folder_Type"]);
unset($_SESSION["Opened_Folder"]);
unset($_SESSION["Father"]);
unset($_SESSION["Numfile"]);
unset($_SESSION["File_Date"]);
unset($_SESSION["Last_Node"]);
unset($_SESSION["Level_Tree"]);
unset($_SESSION["Levels_Fixed_Path"]);
unset($_SESSION["Numbytes"]);
unset($_SESSION["Children_Files"]);
unset($_SESSION["Children_Subdirs"]);
unset($_SESSION["Maxfoldersize"]);
unset($_SESSION["Last_Level_Node"]);
unset($_SESSION["Total_Time"]);
unset($_SESSION["Server_Path"]);
?>
<style>


</style>
<script language="javascript" type="text/javascript">
	function EnviarFormaMNT(Opcion, Mensaje) {

		base = "<?php echo $arrHttp["base"] ?>"
		if (Opcion == "eliminarbd" || Opcion == "inicializar") {
			if (base == "") {
				alert("<?php echo $msgstr["seldb"] ?>")
				return
			}

		}
		switch (Opcion) {
			case "dbcp":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/copy_db.php"
				break;
			case "mxdbread":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.storein.value = base + "/data"
				document.admin.copyname.value = base + ".mst"
				document.admin.action = "../utilities/mx_dbread.php"
				break;
			case "sort_db_records":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/sort_db_records.php"
				break;
			case "manage_gizmo":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/manage_gizmo.php"
				break;
			case "dbrestore":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/dbrestore.php"
				break;
			case "protect":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/protect_db.php"
				break;
			case "eliminarbd":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "eliminarbd.php"
				break;
			case "inicializar":
				document.admin.base.value = base
				document.admin.action = "inicio_bd.php"
				break;
			case "cn": //assign control number
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "assign_control_number.php"
				break
			case "resetcn": //RESET LAST CONTROL NUMBER IN THE BIBLIOGRAPHIC DATABASE
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "reset_control_number.php"
				break;
			case "linkcopies": //LINK BIBLIOGRAPHIC DATABASE WITH COPIES DATABASE
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "copies_linkdb.php"
				break;
			case "fullinv": //INVERTED FILE GENERATION WITH MX
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/fullinv.php"
				break;
			case "importiso": //ISO load
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/vmx_import_iso.php"
				break;
			case "exportiso":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../dataentry/exporta_iso.php"
				document.admin.tipo.value = "iso"
				break;
			case "barcodesearch": //Marino barcode search
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/barcode_search.php"
				break;
			case "dirtree": //EXPLORE DATABASE DIRECTORY
				document.admin.folder.value = Mensaje
				document.admin.base.value = base
				document.admin.action = "dirtree.php";
				break;
			case "addcopiesdatabase": //Marino Script
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/addcopiesdatabase.php"
				break;
			case "sync_copies_to_loanobjects": //Marino Script
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/sync_copies_to_loanobjects.php"
				break;
			case "copiesocurrenciesreport": //Marcos Script
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/copiesdupreport.php"
				break;
			case "addloanobjectcopies": //Marcos Script
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/addloanobjectcopies.php"
				break;
			case "importdoc":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/docfiles_upload.php"
				break;
			case "cleandb": //Marino clean DB
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/clean_db.php"
				break;
			case "unlock": // unlock db
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/unlock_db_retag.php"
				break;
			case "addloanobj": //Marino addloanobj
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/addloanobject.php"
				break;
			case "convertdbutf":
				document.admin.base.value = base
				document.admin.targetcode.value = "UTF-8"
				document.admin.function.value = "database"
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/convert_txt.php"
				break;
			case "convertdbiso":
				document.admin.base.value = base
				document.admin.targetcode.value = "ISO-8859-1"
				document.admin.function.value = "database"
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/convert_txt.php"
				break;
			case "convertlangutf":
				document.admin.base.value = base
				document.admin.targetcode.value = "UTF-8"
				document.admin.function.value = "language"
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/convert_txt.php"
				break;
			case "convertlangiso":
				document.admin.base.value = base
				document.admin.targetcode.value = "ISO-8859-1"
				document.admin.function.value = "language"
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/convert_txt.php"
				break;
			case "convertiso2utf": //convert ISO
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/cnv_iso_2_utf.php"
				break;
			case "matchisofdt": //Match ISO with fdt
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/match_iso_with_fdt.php"
				break;
			case "cnvcsvtoiso": //Convert csv to ISO file
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/cnv_csv_to_iso.php"
				break;
			case "barcodecheck": //Marino barcode check
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/barcode_check.php"
				break;
			case "docbatchimport": //was Marcos docbatchimport
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/docfiles_import.php"
				break;
			case "docfilesconfig":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/docfiles_config.php"
				break;
			case "docfilesconfigrct":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/docfiles_config_rct.php"
				break;
			case "docfilesrepair":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/docfiles_repair.php"
				break;
			case "uploadfile":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/upload_wrkfile.php"
				break;
			case "crunchmf": //CrunchMF - convert database for another OS
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/db_convert_crunchmf.php"
				break;
			case "dcdspace":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/dcdspace.php"
				break;
			case "conversor_prc":
				document.admin.base.value = base
				document.admin.cipar.value = base + ".par"
				document.admin.action = "../utilities/conversor_prc.php"
				break;
			default:
				alert(Opcion + " Not Available")
				return;

		}
		document.admin.Opcion.value = Opcion
		document.admin.cipar.value = base + ".par"
		document.admin.submit()
	}
</script>
<?php

//foreach ($_REQUEST AS $var=>$value) echo "$var=$value<br>"; die;

?>
<nav>

	<ul class="nav">
		<li><a href="#"><?php echo $msgstr["dbmaintenance"] ?></a>
			<ul>
				<li><a href='javascript:EnviarFormaMNT("fullinv","<?php echo $msgstr["mnt_gli"] ?>")'><?php echo $msgstr["mnt_gli"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("unlock","<?php echo $msgstr["mnt_unlock"] ?>")'><?php echo $msgstr["mnt_unlock"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("cn","<?php echo $msgstr["assigncn"] ?>")'><?php echo $msgstr["assigncn"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("protect","<?php echo $msgstr["protect_db_title"] ?>")'><?php echo $msgstr["protect_db_title"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("inicializar","<?php echo $msgstr["mnt_ibd"] ?>")'><?php echo $msgstr["mnt_ibd"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("eliminarbd","<?php echo $msgstr["mnt_ebd"] ?>")'><?php echo $msgstr["mnt_ebd"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("mxdbread","<?php echo $msgstr["mx_dbread"] ?>")'><?php echo $msgstr["mx_dbread"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("manage_gizmo","<?php echo $msgstr["gizmo_manage"] ?>")'><?php echo $msgstr["gizmo_manage"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("sort_db_records","<?php echo $msgstr["sort_db_records"] ?>")'><?php echo $msgstr["sort_db_records"] ?></a></li>
			</ul>
		</li>
		<li><a href="#"><?php echo $msgstr["cnv_export"] . "/" . $msgstr["cnv_import"] ?></a>
			<ul>
				<li><a href='Javascript:EnviarFormaMNT("exportiso","<?php echo $msgstr["cnv_export"] . " " . $msgstr["cnv_iso"] ?>")'><?php echo $msgstr["cnv_export"] . " " . $msgstr["cnv_iso"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("importiso","<?php echo $msgstr["cnv_import"] . " ISO " . $msgstr["archivo"] ?>")'><?php echo $msgstr["cnv_import"] . " ISO " . $msgstr["archivo"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("matchisofdt","<?php echo $msgstr["matchisofdt"] ?>")'><?php echo $msgstr["matchisofdt"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("uploadfile","<?php echo $msgstr["uploadfile"] ?>")'><?php echo $msgstr["uploadfile"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("crunchmf","<?php echo $msgstr["conv_so"] ?>")'><?php echo $msgstr["conv_so"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("cnvcsvtoiso","<?php echo $msgstr["cnv_csv_to_iso"] ?>")'><?php echo $msgstr["cnv_csv_to_iso"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("conversor_prc","<?php echo $msgstr["prc_title"] ?>")'><?php echo $msgstr["prc_title"] ?></a></li>
				<?php if ($arrHttp["base"] == ("dcdspace")) { ?>
					<li><a href='Javascript:EnviarFormaMNT("dcdspace","<?php echo $msgstr["dspace_title"] ?>")'><?php echo $msgstr["dspace_title"] ?></a></li>
				<?php } ?>
			</ul>
		</li>
		<li><a href="#"><?php echo $msgstr["dd_documents"] ?></a>
			<ul>
				<li><a href='Javascript:EnviarFormaMNT("importdoc","<?php echo $msgstr["dd_upload"] ?>")'><?php echo $msgstr["dd_upload"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("docbatchimport","<?php echo $msgstr["dd_batchimport"] ?>")'><?php echo $msgstr["dd_batchimport"] ?></a></li>
				<li><a href='#'>&nbsp;</a></li>
				<li><a href='Javascript:EnviarFormaMNT("docfilesconfig","<?php echo $msgstr["dd_config"] ?>")'><?php echo $msgstr["dd_config"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("docfilesconfigrct","<?php echo $msgstr["dd_configrct"] ?>")'><?php echo $msgstr["dd_configrct"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("docfilesrepair","<?php echo $msgstr["dd_repair"] ?>")'><?php echo $msgstr["dd_repair"] ?></a></li>
			</ul>
		</li>
		<li><a href="#"><?php echo $msgstr["dbbackrest"] ?></a>
			<ul>
				<li><a href='javascript:EnviarFormaMNT("dbcp","<?php echo $msgstr["db_cp"] ?>")'><?php echo $msgstr["db_cp"] ?></a></li>
				<li><a href='javascript:EnviarFormaMNT("dbrestore","<?php echo $msgstr["db_restore"] ?>")'><?php echo $msgstr["db_restore"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("cleandb","<?php echo $msgstr["db_clean"] ?>")'><?php echo $msgstr["db_clean"] ?></a></li>
			</ul>
		</li>
		<li><a href="#"><?php echo $msgstr["copyloanobjects"] ?></a>
			<ul>
				<li><a href='javascript:EnviarFormaMNT("linkcopies","<?php echo $msgstr["linkcopies"] ?>")'><?php echo $msgstr["linkcopies"] ?></a></li>
				<?php if ($arrHttp["base"] != "copies" and $arrHttp["base"] != "providers" and $arrHttp["base"] != "suggestions" and $arrHttp["base"] != "purchaseorder" and $arrHttp["base"] != "users" and $arrHttp["base"] != "loanobjects" and $arrHttp["base"] != "trans" and $arrHttp["base"] != "suspml" and $arrHttp["base"] != "reserve") {
					if ($copies == "Y" or $arrHttp["base"] == "copies" or $arrHttp["base"] == "loanobjects") {
				?>

						<li><a href='Javascript:EnviarFormaMNT("addloanobj","<?php echo $msgstr["addLOfromDB_mx"] ?>")'><?php echo $msgstr["addLOfromDB_mx"] ?></a></li>
						<li><a href='Javascript:EnviarFormaMNT("addloanobjectcopies","<?php echo $msgstr["addLOwithoCP_mx"] ?>")'><?php echo $msgstr["addLOwithoCP_mx"] ?></a></li>
						<li><a href='Javascript:EnviarFormaMNT("sync_copies_to_loanobjects", "<?php echo $msgstr["sync_copies_to_lo"]; ?>")'><?php echo $msgstr["sync_copies_to_lo"]; ?></a></li>
						<li><a href='Javascript:EnviarFormaMNT("addcopiesdatabase","<?php echo $msgstr["addCPfromDB_mx"] ?>")'><?php echo $msgstr["addCPfromDB_mx"] ?></a></li>
						<li><a href='Javascript:EnviarFormaMNT("barcodesearch","<?php echo $msgstr["barcode_search"] ?>")'><?php echo $msgstr["barcode_search"] ?></a></li>
						<!--<li><a href='Javascript:EnviarFormaMNT("barcodecheck","<?php echo "Barcode check" ?>")'><?php echo "Barcode check" ?></a></li>-->
						<li><a href='Javascript:EnviarFormaMNT("copiesocurrenciesreport","<?php echo $msgstr["CPdupreport_mx"] ?>")'><?php echo $msgstr["CPdupreport_mx"] ?></a></li>
				<?php }
				} ?>
			</ul>
		</li>

		<li><a href="#"><?php echo $msgstr["convert"]; ?> ISO &harr; UTF-8</A>
			<ul>
				<li><a href='Javascript:EnviarFormaMNT("convertdbutf","<?php echo $msgstr["convert_txtutf"] ?>")'><?php echo $msgstr["convert_txtutf"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("convertiso2utf","<?php echo $msgstr["cnv_iso2utf"] ?>")'><?php echo $msgstr["cnv_iso2utf"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("convertlangutf","<?php echo $msgstr["convert_langutf"] ?>")'><?php echo $msgstr["convert_langutf"] ?></a></li>
				<li><a href='#'>&nbsp;</a></li>
				<li><a href='Javascript:EnviarFormaMNT("convertdbiso","<?php echo $msgstr["convert_txtiso"] ?>")'><?php echo $msgstr["convert_txtiso"] ?></a></li>
				<li><a href='Javascript:EnviarFormaMNT("convertlangiso","<?php echo $msgstr["convert_langiso"] ?>")'><?php echo $msgstr["convert_langiso"] ?></a></li>
			</ul>
		</li>
		<?php
		if (($_SESSION["profile"] == "adm" or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
				isset($_SESSION["permiso"]["CENTRAL_EXDBDIR"]) or
				isset($_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_EXDBDIR"]))
			and isset($dirtree) and $dirtree == "Y"
		) {

		?>
			<li><a href="#"><?php echo $msgstr["explore"]; ?></A>
				<ul>

					<li><a href='Javascript:EnviarFormaMNT("dirtree","<?php echo $arrHttp['base'] ?>")'><?php echo $msgstr["db_explorefldr"] . " " . $arrHttp['base'] ?></a></li>
					<li><a href="#"><?php echo $msgstr["explore_sys_folders"] ?></a>
						<ul>
							<li><a href='Javascript:EnviarFormaMNT("dirtree","par")'><?php echo "par" ?></a></li>
							<li><a href='Javascript:EnviarFormaMNT("dirtree","www")'><?php echo "www" ?></a></li>
							<li><a href='Javascript:EnviarFormaMNT("dirtree","wrk")'><?php echo "wrk" ?></a></li>
						</ul>
					</li>

				</ul>
			</li>
	</ul>
<?php } ?>
</nav>
<!--administrar_default.php is never used see document.admin.action in javascript above -->
<form name=admin method=post action=administrar_default.php onSubmit="Javascript:return false">
	<input type=hidden name=base value=<?php if (isset($_REQUEST["base"])) echo $_REQUEST["base"] ?>>
	<input type=hidden name=cipar value=<?php if (isset($_REQUEST["base"])) echo $_REQUEST["base"] ?>.par>
	<input type=hidden name=Opcion>
	<input type=hidden name=storein>
	<input type=hidden name=copyname>
	<input type=hidden name=encabezado value=s>
	<input type=hidden name=folder>
	<input type=hidden name=backtoscript value="/central/dbadmin/menu_mantenimiento.php">
	<input type=hidden name=inframe value=0>
	<input type=hidden name=tipo>
	<input type=hidden name=function>
	<input type=hidden name=targetcode>
	<input type=hidden name=activa value=<?php echo $_REQUEST["base"] ?>>
	<input type=hidden name=lang value=<?php echo $_SESSION["lang"] ?>>
</form>
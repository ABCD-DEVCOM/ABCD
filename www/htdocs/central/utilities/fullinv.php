<?php
/* Modifications
20210304 fho4abcd Replaced helper code fragment by included file
20210304 fho4abcd Move html tags, php code indented and reordered
20210304 fho4abcd Send mx executable to test button. Test button also on first form
20210305 fho4abcd Check process status. Catch error output. Menu as real table. Menu option to control number of standard messages
20210316 fho4abcd Work inside/ouside frame, improved backbutton
20210317 fho4abcd Show correct heading and backbutton for second invocation (was not corect from menu_mantenimiento)
20210409 fho4abcd Read actab,uctab,stw from .par file (no fixed files, equal to incremental update procedure)
20210409          The stw file comes from tag STW (present in many .par files).
20210527 fho4abcd Check existence and permissions uctab&actab. Translations
20210923 fho4abcd option to specify fstfile by URL
20211018 eds added created from vmx_fullinv.php+options for stripHTML, incremental indexing
20211101 fho4abcd Check for digital document+use cipar for gizmo+form layout+defaults to enable processing of "normal" databases
20211103 fho4abcd Enable gizmo for htmlfields+hint if gizmo is wrong+simplify interface
20211108 fho4abcd Show parameters and commandline before processing,replaced wait pop-up by "working". Slashm default checked.
20211110 fho4abcd Reordered commandline parameters, add extra flush at end of page 
20211111 fho4abcd Location of metadataConfig in database root. Allow comment lines
20211122 fho4abcd test actab/uctab/stw files from dbn.par-> <dbpath>/<base>/data -> <dbpath>. + small enhancements
20211123 fho4abcd Show error if lineendings of stw files are not correct (most mx exe's require this)
20211202 fho4abcd Incremental not for Digdoc.Set /m dependent on DigDoc (remove from menu).Tag selection menu from dropdown to checkboxes.
20211202          Improved check and messages for gizmo. Check that tag 9876 is in FST
20211215 fho4abcd Backbutton by included file
20220103 fho4abcd Use relative path for digital documents in stead of full path,othe config file name
20220108 fho4abcd Add home button
20220117 fho4abcd Add blue message if /m is used
20220620 fho4abcd Accept charset=utf-8
20220711 fho4abcd Use $actparfolder as location for .par files
20220127 fho4abcd Improve gizmo help text
20240626 fho4abcd Check line endings isisuc.tab, add gizmo support, improve output
20260523 rogercgui Refactor interface, add %path_database% support for actab/uctab/stw, add option to control verbosity of messages, add option to control number of records between messages, add test buttons for mx and .par file, add hints for htmlgizmo, add check for 9876 tag in FST
*/

/**
 * @desc:      Create database index
 * @author:    Marino Borrero Sánchez, Cuba. marinoborrero@gmail.com
 * @since:     20140203
 * @version:   1.0
 *
 * == BEGIN LICENSE ==
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Lesser General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * == END LICENSE ==
 */
set_time_limit(0);
session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"] = "en";
include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"];
include("../common/header.php");
include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/dbadmin.php");
include("../lang/importdoc.php");
//foreach ($arrHttp as $var=>$value) echo "$var=".htmlspecialchars($value)."<br>";//die;
/*
** Old code might not send specific info.
** Set defaults for the return script and frame info
*/
$backtoscript = "../dataentry/administrar.php"; // The default return script
$inframe = 1;                      // The default runs in a frame
$presetfstfile = "";               // No default fst file
if (isset($arrHttp["backtoscript"])) $backtoscript = $arrHttp["backtoscript"];
if (isset($arrHttp["inframe"]))      $inframe = $arrHttp["inframe"];
if (isset($arrHttp["fstfile"]))      $presetfstfile = $arrHttp["fstfile"];
?>

<body>
    <script src=../dataentry/js/lr_trim.js></script>
    <script>
        function OpenWindow() {
            msgwin = window.open("", "testshow", "width=800,height=250");
            msgwin.focus()
        }

        function RemoveSpan(id) {
            var workingspan = document.getElementById(id);
            workingspan.remove();
        }
    </script>

    <?php
    // If outside a frame: show institutional info
    if ($inframe != 1) include "../common/institutional_info.php";
    $base = $arrHttp["base"];
    $bd = $db_path . $base;

    ?>
    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr["maintenance"] . ": " . $arrHttp["base"]; ?>
        </div>
        <div class="actions">
            <?php
            include "../common/inc_back.php";
            include("../common/inc_home.php");
            ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>
    <?php include "../common/inc_div-helper.php" ?>
    <style>
        /* =======================================================
       ESTILOS DA INTERFACE DE INVERSÃO TOTAL (COMPACTA)
       ======================================================= */
        .fullinv-card {
            max-width: 850px;
            margin: 0 auto 15px auto;
            background: #fff;
            border: 1px solid #e0e6ed;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
        }

        .fullinv-card-header {
            background: #f0f4f8;
            padding: 12px 20px;
            border-bottom: 1px solid #e0e6ed;
            border-radius: 6px 6px 0 0;
        }

        .fullinv-card-header h3 {
            margin: 0;
            color: #003366;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fullinv-card-body {
            padding: 15px 20px;
        }

        .fullinv-card-footer {
            background: #f8f9fa;
            padding: 12px 20px;
            border-top: 1px solid #e0e6ed;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 6px 6px;
        }

        .fullinv-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 15px;
        }

        .fullinv-col-left {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .fullinv-form-group {
            display: flex;
            flex-direction: column;
        }

        .fullinv-form-group label {
            font-weight: 600;
            color: #005aa9;
            margin-bottom: 4px;
            font-size: 13px;
        }

        .fullinv-form-control,
        .fullinv-checkbox-group select {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 13px;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .fullinv-form-control:focus,
        .fullinv-checkbox-group select:focus {
            border-color: #005aa9;
            outline: none;
        }

        .fullinv-checkbox-group {
            background: #fafcfc;
            border: 1px solid #eee;
            padding: 12px 15px;
            border-radius: 6px;
            height: 100%;
            box-sizing: border-box;
        }

        .fullinv-checkbox-group h5 {
            margin: 0 0 10px 0;
            color: #444;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .fullinv-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            margin-bottom: 8px;
            color: #333;
            cursor: pointer;
        }

        .fullinv-checkbox-item input[type="checkbox"] {
            cursor: pointer;
            width: 14px;
            height: 14px;
            accent-color: #005aa9;
            margin: 0;
        }

        .fullinv-hint {
            font-size: 11px;
            color: #777;
            margin-left: 22px;
            display: block;
            margin-top: -6px;
            margin-bottom: 10px;
        }

        .fullinv-alert {
            background: #fff3f3;
            color: #d32f2f;
            padding: 10px 15px;
            border-radius: 4px;
            text-align: center;
            margin: 0 auto 15px auto;
            max-width: 850px;
            border: 1px solid #ef9a9a;
            font-weight: 500;
            font-size: 13px;
        }
    </style>

    <div class="middle form">
        <div class="formContent pt-4">
            <?php
            // Ensure that the parameter file exists
            $ciparfile = $arrHttp["base"] . ".par";
            $fullciparpath = $db_path . $actparfolder . $ciparfile;
            if (!file_exists($fullciparpath)) {
                echo "<div class='fullinv-alert'><i class='fas fa-exclamation-triangle'></i> " . $fullciparpath . ": " . $msgstr["notreadable"] . "</div>";
            }

            get_htmlfiletag($htmlfiletag);
            get_htmltags($htmlfiletag, $htmlfileTitle, $htmlTitles, $htmlTags);

            if (isset($_REQUEST['fst'])) $fst = $_REQUEST['fst'];
            if (!isset($fst)) { // The form sets the fst: the first action of this php
            ?>
                <form name="maintenance" action="" method="post" accept-charset="utf-8">
                    <input type="hidden" name="backtoscript" value="<?php echo htmlspecialchars($backtoscript); ?>">
                    <input type="hidden" name="inframe" value="<?php echo htmlspecialchars($inframe); ?>">
                    <input type="hidden" name="base" value="<?php echo htmlspecialchars($arrHttp["base"]); ?>">

                    <div class="fullinv-card">
                        <div class="fullinv-card-header">
                            <h3><i class="fas fa-sync-alt"></i> <?php echo $msgstr["mnt_gli"]; ?></h3>
                        </div>

                        <div class="fullinv-card-body">
                            <p style="color: #666; margin-top:0; margin-bottom: 15px; font-size: 13px;"><i class="fas fa-info-circle"></i> <?php echo $msgstr["adjustparms"]; ?></p>

                            <div class="fullinv-grid">
                                <div class="fullinv-col-left">
                                    <div class="fullinv-form-group">
                                        <label><i class="far fa-file-code"></i> <?php echo $msgstr["select"]; ?> FST</label>
                                        <select name="fst" class="fullinv-form-control">
                                            <?php
                                            $handle = opendir($bd . "/data/");
                                            while ($file = readdir($handle)) {
                                                if ($file != "." && $file != ".." && (strpos($file, ".fst") || strpos($file, ".FST"))) {
                                                    if ($file == $presetfstfile) {
                                                        echo "<option selected value='$file'>$file</option>";
                                                    } else {
                                                        echo "<option value='$file'>$file</option>";
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="fullinv-checkbox-group" style="height: auto;">
                                        <h5><i class="fas fa-terminal"></i> <?php echo $msgstr["log_exec"] ?? "Log de Execução"; ?></h5>
                                        <label class="fullinv-checkbox-item">
                                            <input type="checkbox" name="tell"> <?php echo $msgstr["showexecinfo"]; ?>
                                        </label>
                                        <div style="margin-left: 22px; margin-top: 5px;">
                                            <select name="tellnumber">
                                                <option value="10000000"><?php echo $msgstr["minimal"]; ?></option>
                                                <option value="1000"><?php echo $msgstr["every"]; ?> 1000 <?php echo $msgstr["records"]; ?></option>
                                                <option value="100"><?php echo $msgstr["every"]; ?> 100 <?php echo $msgstr["records"]; ?></option>
                                                <option value="10"><?php echo $msgstr["every"]; ?> 10 <?php echo $msgstr["records"]; ?></option>
                                                <option value="1"><?php echo $msgstr["allrecords"]; ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="fullinv-col-right">
                                    <div class="fullinv-checkbox-group">
                                        <h5><i class="fas fa-cogs"></i> <?php echo $msgstr["index_params"] ?? "Parâmetros de Indexação"; ?></h5>

                                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 5px;">
                                            <?php if ($htmlfiletag == "") { ?>
                                                <label class="fullinv-checkbox-item">
                                                    <input type='checkbox' name='incr'> <?php echo $msgstr["incremental"]; ?>
                                                </label>
                                            <?php } ?>

                                            <?php if ($htmlfiletag != "") { ?>
                                                <div style="margin-bottom: 4px;">
                                                    <label class="fullinv-checkbox-item" style="margin-bottom:2px;">
                                                        <input type='checkbox' name='<?php echo $htmlfiletag; ?>' checked>
                                                        <?php echo $msgstr["striphtml"] . " - " . $htmlfileTitle . " (v" . $htmlfiletag . ")"; ?>
                                                    </label>
                                                    <span class="fullinv-hint"><?php echo $msgstr["sourceis"] . " " . $msgstr["dd_documents"]; ?></span>
                                                </div>
                                            <?php } ?>

                                            <?php for ($i = 0; $i < count($htmlTags); $i++) { ?>
                                                <div style="margin-bottom: 4px;">
                                                    <label class="fullinv-checkbox-item" style="margin-bottom:2px;">
                                                        <input type='checkbox' name='<?php echo $htmlTags[$i]; ?>' checked>
                                                        <?php echo $msgstr["striphtml"] . " - " . $htmlTitles[$i] . " (v" . $htmlTags[$i] . ")"; ?>
                                                    </label>
                                                    <span class="fullinv-hint"><?php echo $msgstr["sourceis"] . " FDT"; ?></span>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <?php
                                        include("inc_select_gizmo.php");
                                        count_gizmo($numgizmos);
                                        if ($numgizmos > 16) $numgizmos = 16;
                                        ?>
                                        <input type="hidden" name="numgizmos" value="<?php echo $numgizmos; ?>">

                                        <?php if ($numgizmos > 0) { ?>
                                            <div style="margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 10px;">
                                                <label style="font-size: 12px; font-weight: 600; color: #555; display:block; margin-bottom:6px;"><i class="fas fa-random"></i> <?php echo $msgstr["gizmoapply"]; ?></label>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 5px;">
                                                    <?php for ($i = 1; $i <= $numgizmos; $i++) { ?>
                                                        <div><?php select_gizmo($i); ?></div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fullinv-card-footer">
                            <button type="submit" class="bt bt-blue" title="<?php echo $msgstr["cg_execute"]; ?>" style="font-size: 13px; padding: 6px 15px;">
                                <i class="fas fa-play"></i> <?php echo $msgstr["ejecutar"]; ?>
                            </button>

                            <div style="display: flex; gap: 8px;">
                                <a href="mx_test.php?mx_path=<?php echo $mx_path; ?>" target="testshow" onclick="OpenWindow()" class="bt bt-gray" style="font-size: 12px;">
                                    <i class="fas fa-vial"></i> <?php echo $msgstr["test"]; ?> MX
                                </a>
                                <a href="show_par_file.php?par_file=<?php echo $fullciparpath; ?>" target="testshow" onclick="OpenWindow()" class="bt bt-gray" style="font-size: 12px;">
                                    <i class="fas fa-file-code"></i> <?php echo $msgstr["show"]; ?> &lt;dbn&gt;.par
                                </a>
                            </div>
                        </div>
                    </div>
                </form>


            <?php
            } else {
                // This is the second part of this script. The fst is set by the menu
                if (!file_exists($cisis_path)) {
                    echo $cisis_path . ": " . $msgstr["misfile"];
                    die;
                }
                // Default for parameters $actab and $uctab and $stw
                $actab = "";
                $uctab = "";
                $stw = "";
                $stwat = "";
                // Default filenames for actab/uctab dependent on unicode.
                // These names are defined by history
                $actabdeffile = "isisac.tab";
                $uctabdeffile = "isisuc.tab";
                if ($unicode == "utf8") {
                    $actabdeffile = "isisactab_utf8.tab";
                    $uctabdeffile = "isisuctab_utf8.tab";
                }
                /*
    ** Read parameters from $actparfolder<basename>.par file.
    ** The existence of the file is already checked at script start : error message here only an if to prevent many errors
    */
                $fullciparpath = $db_path . $actparfolder . $arrHttp["base"] . ".par";
                if (file_exists($fullciparpath)) {
                    echo "<div style=color:blue>" . $msgstr["checking"] . " " . $actparfolder . $arrHttp["base"] . ".par" . "</div>";
                    $def_cipar = parse_ini_file($fullciparpath);
                    /*
        ** Get parameters $actab and $uctab and $stw from the .par file.
        ** Replace %path_database% by actual value
        ** The best keywords are actab/uctab but for historical reasons we check first isisac.tab/isisuc.tab
        ** The best keyword for stw is "stw" but for historical reasons we check first STW
        */
                    if (isset($def_cipar["isisac.tab"])) $actab = str_replace("%path_database%", $db_path, $def_cipar["isisac.tab"]);
                    if (isset($def_cipar["isisuc.tab"])) $uctab = str_replace("%path_database%", $db_path, $def_cipar["isisuc.tab"]);
                    if (isset($def_cipar["actab"]))     $actab = str_replace("%path_database%", $db_path, $def_cipar["actab"]);
                    if (isset($def_cipar["uctab"]))     $uctab = str_replace("%path_database%", $db_path, $def_cipar["uctab"]);
                    if (isset($def_cipar["STW"]))       $stw = str_replace("%path_database%", $db_path, $def_cipar["STW"]);
                    if (isset($def_cipar["stw"]))       $stw = str_replace("%path_database%", $db_path, $def_cipar["stw"]);
                    /*
        ** Show a non-fatal error if these files do not exist
        */
                    if ($actab != "" and !is_readable($actab)) {
                        echo "<div style='color:red'>" . $actab . " <b>" . $msgstr["notreadable"] . "</b></div>";
                        $actab = "";
                    }
                    if ($uctab != "" and !is_readable($uctab)) {
                        echo "<div style='color:red'>" . $uctab . " <b>" . $msgstr["notreadable"] . "</b></div>";
                        $uctab = "";
                    }
                    if ($stw != "" and !is_readable($stw)) {
                        echo "<div style='color:red'>" . $stw . " <b>" . $msgstr["notreadable"] . "</b></div>";
                        $stw = "";
                    }
                }
                /*
    ** If actab/uctab/stw still empty try the default file in bases/data
    */
                if ($actab == "" || $uctab == "" || $stw == "") {
                    echo "<div style=color:blue>" . $msgstr["checking"] . " " . $arrHttp["base"] . "/data/*" . "</div>";
                }
                if ($actab == "") {
                    $actab = $db_path . $base . "/data/" . $actabdeffile;
                    if (!is_readable($actab)) {
                        echo "<div >" . $actab . " " . $msgstr["notreadable"] . "</div>";
                        $actab = "";
                    }
                }
                if ($uctab == "") {
                    $uctab = $db_path . $base . "/data/" . $uctabdeffile;
                    if (!is_readable($uctab)) {
                        echo "<div >" . $uctab . " " . $msgstr["notreadable"] . "</div>";
                        $uctab = "";
                    }
                }
                if ($stw == "") {
                    $stw = $db_path . $base . "/data/" . $base . ".stw";
                    if (!is_readable($stw)) {
                        echo "<div >" . $stw . " " . $msgstr["notreadable"] . "</div>";
                        $stw = "";
                    }
                }
                /*
    ** If actab/uctab still empty try the default file in bases
    */
                if ($actab == "" || $uctab == "") {
                    echo "<div style=color:blue>" . $msgstr["checking"] . " " . $db_path . "</div>";
                }
                if ($actab == "") {
                    $actab = $db_path . $actabdeffile;
                    if (!is_readable($actab)) {
                        echo "<div >" . $actab . " " . $msgstr["notreadable"] . "</div>";
                        $actab = "";
                    }
                }
                if ($uctab == "") {
                    $uctab = $db_path . $uctabdeffile;
                    if (!is_readable($uctab)) {
                        echo "<div >" . $uctab . " " . $msgstr["notreadable"] . "</div>";
                        $uctab = "";
                    }
                }
                echo "<br>";
                /*
    ** If actab/uctab still empty set ansi
    */
                if ($actab == "") $actab = "ansi";
                if ($uctab == "") $uctab = "ansi";
                /*
	** Process the gizmo entries (if any)
	*/
                $numgizmos = $arrHttp["numgizmos"];
                $gizmostr = "";
                $gizmoparameter = "";
                for ($i = 1; $i <= $numgizmos; $i++) {
                    if (isset($arrHttp["gizmo" . $i])) {
                        $gizmostr = $gizmostr . " gizmo=" . $db_path . $base . "/data/" . $arrHttp["gizmo" . $i];
                        if ($gizmoparameter != "") $gizmoparameter .= "<br>";
                        $gizmoparameter .= $db_path . $base . "/data/" . $arrHttp["gizmo" . $i];
                    }
                }
                $gizmostr = $gizmostr . " ";
                /*
    ** Determine if the htmlgizmo is required
    */
                $gizmorequired = 0;
                if ($htmlfiletag != "") $gizmorequired = 1;
                for ($i = 0; $i < count($htmlTags); $i++) {
                    $tag = $htmlTags[$i];
                    if (isset($_POST[$tag]) and strlen($_POST[$tag]) > 0) $gizmorequired = 1;
                }
                /*
    ** Process the entry for the htmlgizmo
    */
                $htmlgizmopar = "";
                $htmlgizmoerr = "";
                if ($gizmorequired == 1) {
                    $htmlgizmoexample = "<br><span style='color:blue'>&nbsp;&nbsp;&nbsp;&nbsp;" . $msgstr["examplefor"] . ": ";
                    $htmlgizmoexample .= $actparfolder . $base . ".par &rArr;&nbsp;htmlgizmo.*=";
                    $htmlgizmoexample .= $db_path . $base . "/data/htmlgizmo.*</span>";
                    if (isset($def_cipar["htmlgizmo.*"])) {
                        // The .par exists and there is a gizmo entry
                        $htmlgizmopar = $def_cipar["htmlgizmo.*"];
                        // Check that %path_database% is not used
                        $htmlgizmopar1 = str_replace("%path_database%", $db_path, $htmlgizmopar);
                        if ($htmlgizmopar1 != $def_cipar["htmlgizmo.*"]) {
                            $htmlgizmoerr = "<span style='color:red'>" . $msgstr["error_gizmofp"] . "</span>";
                            $htmlgizmoerr .= $htmlgizmoexample;
                        }
                        // Check that the gizmo mst exists
                        else {
                            $htmlgizmomst = str_replace(".*", ".mst", $htmlgizmopar, $count);
                            if ($count == 0 or !file_exists($htmlgizmomst)) {
                                $htmlgizmoerr = "<span style='color:red'>" . $msgstr["error_gizmodb"] . "</span>";
                                $htmlgizmoerr .= $htmlgizmoexample;
                            }
                        }
                    } else {
                        // The .par does not exist or has no gizmo entry
                        $htmlgizmoerr = "<span style='color:red'>" . $msgstr["error_gizmospec"] . " " . $db_path . $actparfolder . $base . ".par</span>";
                        $htmlgizmoerr .= $htmlgizmoexample;
                    }
                }
                if ($htmlgizmoerr != "") {
                    echo "<div>" . $htmlgizmoerr . "</div>";
                }
                $parameters = "\n<table style=' margin-left:auto;margin-right:auto;'>";
                $parameters .= "<tr><td>" . $msgstr["database"] . "</td><td>: </td><td>" . $bd . "/data/" . $base . "</td>";
                $parameters .= "<tr><td>" . "fst" . "</td><td>: </td><td>@" . $bd . "/data/" . $base . ".fst</td>";
                if ($stw  != "") $parameters .= "<tr><td>" . "stw" . "</td><td>: </td><td>@" . $stw . "</td>";
                if ($actab != "") $parameters .= "<tr><td>" . "actab" . "</td><td>: </td><td>" . $actab . "</td>";
                if ($uctab != "") $parameters .= "<tr><td>" . "uctab" . "</td><td>: </td><td>" . $uctab . "</td>";
                if ($gizmoparameter != "") $parameters .= "<tr><td>" . "gizmo" . "</td><td>: </td><td>" . $gizmoparameter . "</td>";
                if ($htmlgizmopar != "") $parameters .= "<tr><td>" . "htmlgizmo" . "</td><td>: </td><td>" . $htmlgizmopar . "</td>";

    /*
    ** Process slashm parameter for omitting positions in postings
    ** Not controlled by the menu: Must be set for Digital documents and omitted for all others
    */
                $slashm_var = "";
                if ($htmlfiletag != "") $slashm_var = "/m";
                //$slashm_var="/m";
                if ($slashm_var != "") {
                    echo "<div style='color:blue'>" . $msgstr['inv_slashm'] . "</div>";
                }
                // Process incr parameter : incremental or full inversion
                $incr_var = "";
                if (isset($_POST['incr']) and strlen($_POST['incr']) > 0) $incr_var = " ifupd";
                else $incr_var = "fullinv" . $slashm_var;

                //process tell parameter
                unset($tell);
                $tellvar = "";
                $tellnumbervar = "";
                if (isset($_POST['tell'])) $tell = $_POST['tell'];
                if (isset($tell)) {
                    $tellvar = "tell=";
                    //process tellnumber parameter
                    unset($tellnumber);
                    if (isset($_POST['tellnumber'])) $tellnumber = $_POST['tellnumber'];
                    $tellnumbervar = "1000000";
                    if (isset($tellnumber)) $tellnumbervar = $tellnumber;
                }
                $tellvar .= $tellnumbervar;

                /*
    ** Processing for Digital Documents
    ** The htmlfiletag parameter defines in which field the HTML-file name for Gload is stored.
    ** The HTML filename is an URL: it starts with /docs/.. The load procedure requires a full path
    ** The fst should contain parameter 9876
    */
                $strip_var = "";
                if ($htmlfiletag != "") {
                    // The extra quotes surrounding the procs are required for the linux version
                    $strip_var .= '"' . "proc='Gload/9876='replace(v" . $htmlfiletag . ",'/docs/','$db_path')" . '"';
                    $parameters .= "<tr><td>" . $msgstr["load_htmldata"] . "</td><td>: </td><td>" . $htmlfiletag . " &rarr; 9876</td>";
                    $fullfst = $bd . "/data/" . $fst;
                    $fstcontent = file_get_contents($fullfst);
                    if (stripos($fstcontent, "v9876") === false) {
                        echo "<div style='color:red'>FST " . $fst . " " . $msgstr["doesnotcontaintag"] . " v9876. " . $msgstr["invertincomplete"] . "</div>";
                        echo "<div style='color:blue'>" . $msgstr["invertloads"] . " v" . $htmlfiletag . " " . $msgstr["intotag"] . " v9876</div>";
                    }
                }
                /*
    ** Strip the html tags from the selected fields
    ** Controlled by checkboxes
    */
                // Digital documents
                if (isset($_POST[$htmlfiletag]) and strlen($_POST[$htmlfiletag]) > 0) {
                    $strip_var .= " \"proc='Ghtmlgizmo,9876'\"";
                    $parameters .= "<tr><td>" . $msgstr["striphtml"] . "</td><td>: </td><td>9876</td>";
                }
                // The fields from the FDT are processed with their own tag
                for ($i = 0; $i < count($htmlTags); $i++) {
                    $tag = $htmlTags[$i];
                    if (isset($_POST[$tag]) and strlen($_POST[$tag]) > 0) {
                        $strip_var .= " \"proc='Ghtmlgizmo," . $tag . "'\"";
                        $parameters .= "<tr><td>" . $msgstr["striphtml"] . "</td><td>: </td><td>" . $tag . "</td>";
                    }
                }
                // Check that the lineends fit with the current OS (mx requirement for stw files)
                include "inc_check-line-end.php";
                if ($stw != "") {
                    $result = check_line_end($stw);
                }
                // Check that the lineends fit with the current OS (mx requirement for ansi i files)
                if ($uctab != "" && $uctab != "ansi" && $unicode != "utf8") {
                    $result = check_line_end($uctab);
                }
                /*
	** finish parameters list
	*/
                $parameters .= "<tr><td style='font-weight:600; color:#555;'><i class='fas fa-terminal' style='color:#005aa9;'></i> mx</td><td>: </td><td style='font-family:monospace;'>" . $mx_path . "</td>";
                $parameters .= "</table>";

// ===================================================================================
// THE MAGIC OF %path_database% FOR EXTERNAL DATABASES
// The mx utility does not recognize the macro, which causes an error when cross-referencing data from other 
// databases read in the .par file (e.g., copies.*). It intercepts the original file, 
// translates the path, and creates a temporary file (cipar) that is safe for MX.
// ===================================================================================

                $cipar_seguro = $fullciparpath;
                if (file_exists($fullciparpath)) {
                    $par_content = file_get_contents($fullciparpath);

                    if (strpos($par_content, '%path_database%') !== false) {
                        $par_content = str_replace("%path_database%", $db_path, $par_content);
                        $cipar_seguro = $db_path . "wrk/temp_mx_" . $arrHttp["base"] . ".par";
                        if (!is_dir($db_path . "wrk")) @mkdir($db_path . "wrk", 0777, true);
                        file_put_contents($cipar_seguro, $par_content);
                    }
                }

                $strINV = $mx_path;
                $strINV .= " cipar=" . $cipar_seguro;
                $strINV .= " db=" . $bd . "/data/" . $base;
                $strINV .= " fst=@" . $bd . "/data/" . $fst;
                $strINV .= $gizmostr;
                $strINV .= " " . $strip_var;
                if ($actab != "ansi") $strINV .= " actab=" . $actab;
                if ($uctab != "ansi") $strINV .= " uctab=" . $uctab;
                if ($stw != "") $strINV .= " stw=@" . $stw;
                $strINV .= " " . $incr_var . "=" . $bd . "/data/" . $base . " -all now " . $tellvar . " 2>&1";

                // ===================================================================================
                // NEW LAYOUT OF THE EXECUTION PROCESS (Conditional Integrated Terminal)
                // ===================================================================================
            ?>
                <style>
                    .execution-container {
                        max-width: 850px;
                        margin: 0 auto 30px auto;
                        background: #fff;
                        border-radius: 6px;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
                        border: 1px solid #e0e6ed;
                        overflow: hidden;
                    }

                    .execution-header {
                        background: #f0f4f8;
                        padding: 12px 20px;
                        border-bottom: 1px solid #e0e6ed;
                    }

                    .execution-header h3 {
                        margin: 0;
                        font-size: 15px;
                        color: #003366;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }

                    .execution-params {
                        padding: 15px 20px;
                        background: #fafcfc;
                        border-bottom: 1px solid #e0e6ed;
                    }

                    .execution-params table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 13px;
                        color: #444;
                    }

                    .execution-params td {
                        padding: 4px 8px;
                        border-bottom: 1px dashed #eee;
                    }

                    .execution-params td:first-child {
                        font-weight: 600;
                        width: 220px;
                        color: #555;
                    }

                    .execution-cmd {
                        padding: 12px 20px;
                        background: #fdfdfd;
                        font-family: monospace;
                        font-size: 12px;
                        color: #d63384;
                        word-wrap: break-word;
                        border-bottom: 1px solid #e0e6ed;
                    }

                    .execution-cmd strong {
                        color: #333;
                        font-family: sans-serif;
                        display: block;
                        margin-bottom: 4px;
                        font-size: 13px;
                    }

                    .execution-console {
                        background: #1e1e1e;
                        color: #00ff00;
                        padding: 15px 20px;
                        font-family: 'Courier New', Courier, monospace;
                        font-size: 13px;
                        line-height: 1.4;
                        max-height: 400px;
                        overflow-y: auto;
                        box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.5);
                    }

                    .execution-console.error {
                        color: #ff5555;
                    }

                    .execution-footer {
                        padding: 12px 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        background: #f8f9fa;
                    }

                    .execution-status-ok {
                        color: #28a745;
                        font-weight: bold;
                        display: flex;
                        align-items: center;
                        gap: 5px;
                        font-size: 14px;
                    }

                    .execution-status-fail {
                        color: #dc3545;
                        font-weight: bold;
                        display: flex;
                        align-items: center;
                        gap: 5px;
                        font-size: 14px;
                    }
                </style>

                <div class="execution-container">
                    <div class="execution-header">
                        <h3><i class="fas fa-microchip"></i> <?php echo $msgstr["mx_exec_report"] ?? "Relatório de Execução do MX"; ?></h3>
                    </div>

                    <div class="execution-params">
                        <?php echo $parameters; ?>
                    </div>

                    <div class="execution-cmd">
                        <strong><i class="fas fa-terminal"></i> <?php echo $msgstr["commandline"]; ?>:</strong>
                        <?php echo htmlspecialchars($strINV); ?>
                    </div>

                    <div id="execution-status" style="padding: 15px 20px; text-align: center; color: #ff9800; font-weight: bold; background:#fff8e1;">
                        <i class="fas fa-spinner fa-spin"></i> <?php echo $msgstr["system_working"]; ?> ...
                    </div>

                    <?php
                    ob_flush();
                    flush();

                    // Executa o MX
                    exec($strINV, $output, $status);

                    $straux = "";
                    foreach ($output as $line) {
                        $straux .= htmlspecialchars($line) . "<br>";
                    }
                    ?>

                    <script>
                        document.getElementById('execution-status').style.display = 'none';
                    </script>

                    <?php
                    // If the MX doesn't output any log (e.g., tell=off and no error), don't show the black screen
                    if (trim(strip_tags($straux)) !== ""):
                    ?>
                        <div class="execution-console <?php echo ($status == 0) ? '' : 'error'; ?>">
                            <?php echo $straux; ?>
                        </div>
                    <?php endif; ?>

                    <div class="execution-footer">
                        <?php if ($status == 0): ?>
                            <div class="execution-status-ok"><i class="fas fa-check-circle"></i> <?php echo $msgstr["processok"]; ?></div>
                        <?php else: ?>
                            <div>
                                <div class="execution-status-fail"><i class="fas fa-times-circle"></i> <?php echo $msgstr["processfailed"]; ?></div>
                                <?php if (strpos($straux, "fatal: recread/check/base") !== false): ?>
                                    <div style="font-size: 13px; color: #dc3545; margin-top: 5px;">
                                        <strong><?php echo $msgstr["possiblecause"]; ?>:</strong><br>
                                        htmlgizmo.mst <?php echo $msgstr["isnotcreated"]; ?> <?php echo $mx_path; ?><br>
                                        <?php echo $msgstr["isismustmatch"]; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <a href="fullinv.php?base=<?php echo $arrHttp["base"]; ?>" class="bt bt-blue"><i class="fas fa-undo"></i> <?php echo $msgstr["back"] ?? "Voltar"; ?></a>
                    </div>
                </div>

            <?php
                ob_flush();
                flush();
            }
            ?>

        </div>
    </div>

    <?php
    include("../common/footer.php");

// ======================================================
// This the end of main script. Only functions follow now
// =========================== Functions ================
//  - get_htmlfiletag   : returns the tag for the html file for digital documents
//  - get_htmltags      : returns the tags with html content from the FDT
//
// ====== get_htmlfiletag =============================

/*
** Check if there is a collection for this database
** Read the configuration data and determine the tag for "htmlSrcURL"
** If nothing is found the returned tag value is empty.
**
** Return : 0=OK, 1=NOT-OK
*/
    function get_htmlfiletag(&$htmlfiletag)
    {
        global $msgstr, $arrHttp, $db_path, $def_db;
        $htmlfiletag = "";
        if (!isset($def_db["COLLECTION"])) return (0);
        $fullcolpath = $def_db["COLLECTION"];
        $fullcolpath = str_replace("%path_database%", $db_path, $fullcolpath);
        $fullcolpath = rtrim($fullcolpath, "/ ");
        if (!file_exists($fullcolpath)) return (0);
        $tagConfig = "docfiles_tagconfig.tab";
        $tagConfigFull = $fullcolpath . "/" . $tagConfig;
        if (!file_exists($tagConfigFull)) return (0);
        $fp = file($tagConfigFull);
        foreach ($fp as $value) {
            $value = trim($value);
            // Lines with // and lines with # are skipped
            // Lines that cannot contain valid information are skipped
            if (strlen($value) < 4) continue;
            if (stripos($value, '//') !== false) continue;
            if (stripos($value, '#') !== false) continue;
            $table = explode("|", $value);
            if ($table[0] == "htmlSrcURL" and isset($table[1]) and strlen($table[1]) > 0) {
                $htmlfiletag = $table[1];
                // the values in the table have a leading "v"
                if (($htmlfiletag[0] == 'v') or ($htmlfiletag[0] == 'V')) {
                    $htmlfiletag = str_replace('v', '', strtolower($htmlfiletag));
                }
                return (0);
            }
        }
        return (0);
    }
    // ====== get_htmltags =============================
/*
** Reads the current FDT and returns:
** - The Title of the supplied tag for the digitral document html file
** - The Titles and Tags of all fields with Input Type=HTML Area
** Return : 0=OK, 1=NOT-OK
*/
    function get_htmltags($htmlfiletag, &$htmlfileTitle, &$htmlTitles, &$htmlTags)
    {
        global $msgstr, $arrHttp, $db_path, $lang_db;
        $tagindex = 1;
        $titleindex = 2;
        $inputtypeindex = 7;
        $htmlfileTitle = $msgstr["dd_term_htmlSrcURL"];
        $htmlTitles = array();
        $htmlTags = array();
        // Open the language dependent fdt and if not present the default language fdt
        $fdtfile = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $arrHttp["base"] . ".fdt";
        if (!file_exists($fdtfile)) $fdtfile = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $arrHttp["base"] . ".fdt";
        $fp = file($fdtfile);
        foreach ($fp as $value) {
            $value = trim($value);
            if (trim($value) != "") {
                $table = explode("|", $value);
                if ($table[$inputtypeindex] == "A") {
                    $htmlTags[] = $table[$tagindex];
                    $htmlTitles[] = $table[$titleindex];
                }
                if ($table[$tagindex] == $htmlfiletag) {
                    $htmlfileTitle = $table[$titleindex];
                }
            }
        }
        return (0);
    }
// ======================= End functions/End =====
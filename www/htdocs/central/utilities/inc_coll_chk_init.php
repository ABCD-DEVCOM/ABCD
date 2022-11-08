<?php
/* Modifications
20210807 fho4abcd Created
20210831 fho4abcd Enable %path_database% in COLLECTION. Check dat collection is in the base
20210903 fho4abcd Add name of configuration file
20211111 fho4abcd configfile to database root
20220103 fho4abcd new names standard subfolders and config file
20220110 fho4absd write default worksheet indicator file
*/
/*
** Function: Initial check of collection.
** This file covers
** - The presence of a COLLECTION entry in dr_path
** - The presence of the collection folder and standard subfolders.
**   ../<collection>/ImportRepo/
**                  /SourceRepo/
**                  /DocRepo/
** - Missing folders are created, existing folders are checked for writability
** - The name and location of the config file. Note must match fullinv.php
*/
// Check existence of dr_path.def
// Note that dr_path is already parsed in config.php
$fulldrpath=$db_path.$arrHttp["base"]."/dr_path.def";
if (!file_exists($fulldrpath)){
    echo "<div style='font-weight:bold;color:red'>".$fulldrpath.": ".$msgstr["notreadable"]."</div>";
    die;
}
// The collection must be specified in dr_path.
// The location can be anywhere
if (!isset($def_db["COLLECTION"]) or $def_db["COLLECTION"]=="") {
    echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_nocollection"]."</div>";
    die;
}
$fullcolpath=$def_db["COLLECTION"];
$fullcolpath=str_replace("%path_database%",$db_path,$fullcolpath);
$fullcolpath=rtrim($fullcolpath,"/ ");
// The collection can be anywhere (in theory)
// However: we want urls' starting with /docs/  (alias define in the virtual host file)
// To make things a bit more testable we require now that the collection is in the current base
if (substr($fullcolpath, 0, strlen($db_path)) != $db_path) {
    echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_colfolderbase"]." '".$db_path."' ";
    echo $msgstr["dd_colfolderpref"]." '".$fullcolpath."'  </div>";
    die;
}
// Check if collection folder exists and is writable
if (!file_exists($fullcolpath) or !is_dir($fullcolpath)) {
    echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_colfolder"]." '".$fullcolpath."' ".$msgstr["notreadable"]."</div>";
    die;
} else if (!is_writable($fullcolpath) ) {
    echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_colfolder"]." '".$fullcolpath."' ".$msgstr["dd_nowrite"]."</div>";
    die;
}
// Check if the upload (=import) folder exists (if not create it) and is writable
$colupl="ImportRepo";
$coluplfull=$fullcolpath."/".$colupl;
if ( !file_exists($coluplfull)) {
    $result=@mkdir($coluplfull);
    if ($result === false ) {
        $file_get_contents_error= error_get_last();
        $err_mkdir="&rarr; ".$file_get_contents_error["message"];
        echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_coluplfoldererr"]." '".$coluplfull."' ".$err_mkdir."</div>";
        die;
    }
}
if (!is_dir($coluplfull) ) {
    echo "<div style='font-weight:bold;color:red'> '".$coluplfull."' ".$msgstr["dd_nofolder"]."</div>";
    die;
}
if (!is_writable($coluplfull) ) {
    echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_coluplfolder"]." '".$coluplfull."' ".$msgstr["dd_nowrite"]."</div>";
    die;
}

// Check if the source folder exists (if not create it) and is writable
$colsrc="SourceRepo";
$colsrcfull=$fullcolpath."/".$colsrc;
if ( !file_exists($colsrcfull)) {
    $result=@mkdir($colsrcfull);
    if ($result === false ) {
        $file_get_contents_error= error_get_last();
        $err_mkdir="&rarr; ".$file_get_contents_error["message"];
        echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_colsrcfoldererr"]." '".$colsrcfull."' ".$err_mkdir."</div>";
        die;
    }
}
if (!is_dir($colsrcfull) ) {
    echo "<div style='font-weight:bold;color:red'> '".$colsrcfull."' ".$msgstr["dd_nofolder"]."</div>";
    die;
}
if (!is_writable($colsrcfull) ) {
    echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_colsrcfolder"]." '".$colsrcfull."' ".$msgstr["dd_nowrite"]."</div>";
    die;
}

// Check if the docs folder exists (if not create it) and is writable
$coldoc="DocRepo";
$coldocfull=$fullcolpath."/".$coldoc;
if ( !file_exists($coldocfull)) {
    $result=@mkdir($coldocfull);
    if ($result === false ) {
        $file_get_contents_error= error_get_last();
        $err_mkdir="&rarr; ".$file_get_contents_error["message"];
        echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_coldocfoldererr"]." '".$coldocfull."' ".$err_mkdir."</div>";
        die;
    }
}
if (!is_dir($coldocfull) ) {
    echo "<div style='font-weight:bold;color:red'> '".$coldocfull."' ".$msgstr["dd_nofolder"]."</div>";
    die;
}
if (!is_writable($coldocfull) ) {
    echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_coldocfolder"]." '".$coldocfull."' ".$msgstr["dd_nowrite"]."</div>";
    die;
}
// The filename of the file with configuration info
// Note that initially this file does not exist. Created by a configuration script
// If this variable is modified: modify also fullinv.php
$tagConfig="docfiles_tagconfig.tab";
$tagConfigFull=$fullcolpath."/".$tagConfig;

// The filename of the file with record type information
// The initial version is created here too
// Content: some comment + lines with "content-type|record_type"
$recConfig="docfiles_rctconfig.tab";
$recConfigFull=$fullcolpath."/".$recConfig;
if ( !file_exists($recConfigFull)) {
    $fp=@fopen($recConfigFull,"w");
    if ($fp === false ) {
        $file_get_contents_error= error_get_last();
        $err_mkfil="&rarr; ".$file_get_contents_error["message"];
        echo "<div style='font-weight:bold;color:red'>".$msgstr["dd_coldocfoldererr"]." '".$recConfigFull."' ".$err_mkfil."</div>";
        die;
    }
    $wksTextRecordtype="text";
    $wksImageRecordtype="image";
    fwrite($fp,"text"."|".$wksTextRecordtype.PHP_EOL);// includes text/plain text/html and others
    fwrite($fp,"image"."|".$wksImageRecordtype.PHP_EOL);// includes image/jpeg and others
    fwrite($fp,"application/epub+zip"."|".$wksTextRecordtype.PHP_EOL);
    fwrite($fp,"application/pdf"."|".$wksTextRecordtype.PHP_EOL);
    fwrite($fp,"application/vnd.ms-powerpoint"."|".$wksTextRecordtype.PHP_EOL);
    fwrite($fp,"application/vnd.openxmlformats-officedocument.presentationml.presentation"."|".$wksTextRecordtype.PHP_EOL);
    fwrite($fp,"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"."|".$wksTextRecordtype.PHP_EOL);
    fwrite($fp,"application/vnd.openxmlformats-officedocument.wordprocessingml.document"."|".$wksTextRecordtype.PHP_EOL);
    fwrite($fp,"application/msword"."|".$wksTextRecordtype.PHP_EOL);
    fwrite($fp,"application/xhtml+xml"."|".$wksTextRecordtype.PHP_EOL);
   
    fclose($fp);
}


/* and here the including file continues processing */

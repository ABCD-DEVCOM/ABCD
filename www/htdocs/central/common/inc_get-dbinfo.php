<?php
/* Modifications
20210312 fho4abcd Created
20210319 fho4abcd Print error if par/<dbname>.par is missing (to understand the error thrown by wxis_llamar.php
20210322 fho4abcd Comment
20220711 fho4abcd Use $actparfolder as location for .par files
20261118 fho4abcd Trim returned value, improve comment
*/
/*
** Execute function get_dbinfo
*/

/*--------------------------------------------------------------
** Function  : Get info about the current database from the database
**             The info is requested by wxis_llamar.php (Opcion=status)
** Returns   : All info is returned in $arrHttp
**             - $arrHttp["MAXMFN"]             (always, default=0)
**             - $arrHttp["BD"]                 (if applicable) => Y= database does exist
**             - $arrHttp["IF"]                 (if applicable) => Y= inverted file does exist
**             - $arrHttp["DENTRYLOCK"]         (if applicable) => 0= ??
**             - $arrHttp["EXCLUSIVEWRITELOCK"] (if applicable) => 0= not exclusive write locked
** Globals   : This function uses wxis_llamar.php (with many globals)
**   $arrHttp: I/O specifies at least the current database
**   $xWxis  : I   path to the wxis scripts .xis for Central
**   $db_path: I   path where the databases are located
*/
// 
function get_dbinfo () {
    global $arrHttp, $xWxis, $db_path, $actparfolder;
    if ( !isset($arrHttp["base"]) or $arrHttp["base"]=="undefined" or $arrHttp["base"]==""){
    ?>
    <H3><font color=red>This script: <br><?php echo __FILE__;?><br>
    included by <b><?php echo pathinfo(debug_backtrace()[0]['file'])['basename'];?></b><br>
    requires the name of the database via $arrHttp["base"]<br>
    This is a coding error</font></H3>
    <?php
         die;
    }
    $IsisScript=$xWxis."administrar.xis";
    $fullciparpath=$db_path.$actparfolder.$arrHttp["base"].".par";
    if (!file_exists($fullciparpath)){
        echo "<h3><font color=red>".$fullciparpath.": Does not exist</font></h3>";
    }
    $query = "&base=".$arrHttp["base"] . "&cipar=".$fullciparpath. "&Opcion=status";
    include("../common/wxis_llamar.php");
    if ( $err_wxis!="" ) die;

    $dbinfo_ix=-1;
    if (isset($contenido )) {
        foreach($contenido as $dbinfo_linea) {
            $dbinfo_ix=$dbinfo_ix+1;
            if ($dbinfo_ix>0) {
                if (trim($dbinfo_linea)!=""){
                    $dbinfo_a=explode(":",$dbinfo_linea);
                    if (isset($dbinfo_a[1])) {
                        $dbinfo_a[1]=trim($dbinfo_a[1]);
                        $arrHttp[$dbinfo_a[0]]=$dbinfo_a[1];
                    }
                }
            }
        }
    }
    // Return at least one value
    if (!isset($arrHttp["MAXMFN"]))   $arrHttp["MAXMFN"]=0;
return;
}
get_dbinfo();

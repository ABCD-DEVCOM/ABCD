<?php
if (isset($_REQUEST["Opcion"])) $_REQUEST["Opcion_Original"]=$_REQUEST["Opcion"];
//foreach ($_REQUEST as $var=>$value)  echo "$var=$value<br>";//die;
include($_SERVER['DOCUMENT_ROOT'] . "/".$opac_path."/common/opac-head.php");
$Actual_path=$db_path;
//include("inc/leer_bases.php");
$indice_alfa="N";
//$sidebar="N";
$ActualDir=getcwd();
//include("common/opac-head.php");
$Web_Dir=$CentralPath;
$desde_web="Y";
$desde_opac="Y";
$_REQUEST["vienede"]="ORBITA";
chdir($CentralPath."reserve");

// SIDEBAR
if ((!isset($_REQUEST["existencias"]) or $_REQUEST["existencias"] == "") and !isset($sidebar)) include($_SERVER['DOCUMENT_ROOT'] . "/".$opac_path."/components/sidebar.php");
?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
          </div>
        </div>
      </div>

<?php
include($_SERVER['DOCUMENT_ROOT'] . "/mysite/reserve/index.php");
include($_SERVER['DOCUMENT_ROOT'] . "/".$opac_path."/common/opac-footer.php");



?>
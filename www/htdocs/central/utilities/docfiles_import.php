<?php
/* Modifications
20210807 fho4abcd Created from several docbatchimport.php files
20210831 fho4abcd Improved URL fields
20210903 fho4abcd Moved configuration code to separate files.
20210929 fho4abcd Delete .proc file+ get html file size&db record size+split record
20211007 fho4abcd Search tika's, add tika selection+chunk size in menu+improved name cleaunup+proc file with pid
20211011 fho4abcd Tika uses stdin/stdout. Set locale to UTF-8 (required for function pathinfo. Improve filename sanitation
20211012 fho4abcd Error message if import subfolder not writable+ short timestamp for filenames>5 characters
20211015 fho4abcd Sanitize all metaterms+sanitize & check import section folders
20211017 fho4abcd Remove empty files before presenting the list of files to be processed + option to log to file
20211101 fho4abcd replace vmx_fullinv.php by fullinv.php
20211108 fho4abcd Improved split: file limit lower, split on </p>, time limit for each file, sanitize html before split
20211110 fho4abcd Reserve record space for inverted file generation, remove img clauses, chunk may and at </table>
20201110 fho4abcd Remove debug file + corrected splittarget: menu value is noew used
20201123 fho4abcd Html header for split/unsplit is now equal. unicode filenames to hex (required for fullinv:Gload)
20211201 fho4abcd Splittarget by dropdown+call it granularity. Apply split also if filesize > granularity.
20211215 fho4abcd Backbutton by included file
20220103 fho4abcd Revised collection structure.Improved tag processing
20220104 fho4abcd Added exif
20220110 fho4abcd Added record type indicator+remove maxparts and exifimagedesc (duplicate of dc:description)
20220126 fho4abcd Added option to use ID to make filenames unique
**
** The field-id's in this file have a default, but can be configured
** Effect is that this code can be used for databases with other field-id's
** Note that this module is not aware of the actual database fdt,fst,...
*/
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include("../config.php");
include("../common/header.php");
$lang=$_SESSION["lang"];
include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");
include("../lang/importdoc.php");
// ==================================================================================================
// INICIO DEL PROGRAMA
// ==================================================================================================
/*
** Set defaults
*/
$backtoscript="../dataentry/incio_main.php"; // The default return script
$inframe=1;                      // The default runs in a frame
$impdoc_cnfcnt=0;
$splittarget=80;
$splitmax=85;
$logmode="web";
if ( isset($arrHttp["backtoscript"]))  $backtoscript=$arrHttp["backtoscript"];
if ( isset($arrHttp["inframe"]))       $inframe=$arrHttp["inframe"];
if ( isset($arrHttp["impdoc_cnfcnt"])) $impdoc_cnfcnt=$arrHttp["impdoc_cnfcnt"];
if ( isset($arrHttp["splittarget"]))   $splittarget=$arrHttp["splittarget"];
if ( isset($arrHttp["splitmax"]))      $splitmax=$arrHttp["splitmax"];
if ( isset($arrHttp["logmode"]))       $logmode=$arrHttp["logmode"];
$backtourl=$backtoscript."?base=".$arrHttp["base"]."&inframe=".$inframe;
/*
** The maximum recordsize is given in cisis.h by variable MAXMFRL
** We have 3 cisis versions in ABCD
** - empty = 16-60: max recordsize=   32768 (linux+windows)
** - ffi          : max recordsize= 1048576 (linux+windows, indexing methods for more static databases)
** - bigisis      : max recordsize= 1048576 (linux, no images compiled for windows (at the time this code is written)
** Safety margin 1 (zero) byte
*/
$isis_record_size=32767; // This also for 16-60
if ($cisis_ver=="ffi")     $isis_record_size=1048575;
if ($cisis_ver=="bigisis") $isis_record_size=1048575;

?>
<body>
<script language="javascript1.2" src="../dataentry/js/lr_trim.js"></script>
<script>
var win;
function OpenWindow(){
	msgwin=window.open("","testshow","width=800,height=250");
	msgwin.focus()
}
function Eliminar(docfile,filename){
    /* docfile is the fullpath, filename is the user friendly name */
	if (confirm("<?php echo $msgstr["cnv_deltab"]?>"+" "+filename)==true){
	    document.continuar.deletedocfile.value=docfile
        document.continuar.impdoc_cnfcnt.value=1;
        document.continuar.submit()
	}
}
function Reselect(){
	document.continuar.upldoc_cnfcnt.value='0';
    document.continuar.action='../utilities/docfiles_upload.php?&backtoscript=<?php echo $backtoscript?>'
	document.continuar.submit()
}

function SetImportOptions(){
    document.continuar.impdoc_cnfcnt.value=2;
    document.continuar.submit()
}
function Import(){
    document.continuar.impdoc_cnfcnt.value=3;
    document.continuar.submit()
}
function Invert(){
    document.continuar.action='../utilities/fullinv.php?&backtoscript=<?php echo $backtoscript?>';
    document.continuar.impdoc_cnfcnt.value=4;
    document.continuar.submit()
}
function ShowDetails(){
    document.getElementById('importactiondiv').style.display='inherit'
}
</script>

<?php
// If outside a frame: show institutional info
if ($inframe!=1) include "../common/institutional_info.php";
?>
<div class="sectionInfo">
	<div class="breadcrumb">
<?php   echo $msgstr["mantenimiento"].": ".$msgstr["dd_batchimport"];
?>
	</div>
	<div class="actions">
    <?php
    include "../common/inc_back.php";
    include "../common/inc_home.php";
    ?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php
include "../common/inc_div-helper.php";
?>
<div class="middle form">
<div class="formContent">
    <div align=center><h3><?php echo $msgstr["dd_batchimport"] ?></h3>
<?php
// Set collection related parameters and create folders if not present
include "../utilities/inc_coll_chk_init.php";
// The function to list a folder and initial parameters
include "../utilities/inc_list-folder.php";
$fileList=array();
// Include configuration functions
include "inc_coll_read_cfg.php";

/* =======================================================================
/* ----- First screen: Give info and check existence of uploaded files -*/
if ($impdoc_cnfcnt<=1) {
    echo "<p>".$msgstr["dd_imp_init"]."</p>";
    // If this is the first time that this code runs: Read & check the configuration file
    if ($impdoc_cnfcnt==0) {
        $actTagMap=array();
        $retval= read_dd_cfg("operate", $tagConfigFull, $actTagMap );
        if ($retval!=0) die;
    }
    echo "<h3>".$msgstr["dd_imp_step"]." ".($impdoc_cnfcnt+1).": ".$msgstr["dd_imp_step_check_files"]."</h3>";
    // If the request was to delete a file (second and subsequent runs)
    if ( isset($arrHttp["deletedocfile"]) && $arrHttp["deletedocfile"]!="")  {
        //delete the file
        $delindex=$arrHttp["deletedocfile"];
        $retval = list_folder("files", $coluplfull, $fileList);
        if ($retval!=0) die;
        sort($fileList);
        $numfiles=count($fileList);
        if ($numfiles>0 && $delindex<$numfiles) {
            if (unlink ($fileList[$delindex])===true){
                echo "<div>".$msgstr["archivo"]." ".$fileList[$delindex]." ".$msgstr["deleted"]."</div>";
            }
        }
        $fileList=[];
    }
    /*
    ** The upload folder may contain subfolders with malicious folder names
    ** This corrected first
    */
    clearstatcache();
    $retval = sanitize_tree($coluplfull);
    if ($retval!=0) die;
    /*
    ** Remove empty files (will crash tika)
    ** List all files
    ** Deletion is possible as sanitize tree has checked for writability
    */
    $retval = list_folder("files", $coluplfull, $fileList);
    if ($retval!=0) die;
    for ( $index=0; $index<count($fileList); $index++) {
        if (filesize($fileList[$index])==0){
            unlink($fileList[$index]);
            echo "<span style='color:orange'>".$msgstr["dd_error_empty"]."&rarr; ".$fileList[$index]."</span><br>";
        }
    }
    /*
    ** show all files to the user in a table with a "delete" button
    */
    $fileList=[];
    $retval = list_folder("files", $coluplfull, $fileList);
    if ($retval!=0) die;
    $numfiles=count($fileList);
    if ($numfiles==0) {
        echo "<p style='color:red'>".$msgstr["dd_imp_nofiles"].$colupl."<p>";
    } else {
        echo "<p style='color:blue'>".$numfiles." ".$msgstr["dd_imp_numfiles"].$colupl."</p>";
        // build a table with filename, section and delete option
        sort($fileList);
        ?>
        <table bgcolor=#e7e7e7 cellspacing=1 cellpadding=1>
        <tr>
            <th><?php echo $msgstr["archivo"]?> </th>
            <th><?php echo $msgstr["dd_section"]?> </th>
        </tr>
        <?php
        for ( $index=0;$index<$numfiles;$index++) {
            split_path($fileList[$index], $filename, $sectionname);
            /*
            ** Note that the delete button works on the index in the list
            ** Parameters with embedded quotes result in js errors, so the quote is removed from the shown name
            ** The user won't notice this normally or think of a strange error
            */
        ?> 
        <tr>
            <td bgcolor=white><?php echo $filename?></td>
            <td bgcolor=white><?php echo $sectionname?></td>
            <td><button class="button_browse delete" type="button"
                onclick='javascript:Eliminar("<?php echo $index?>","<?php echo str_replace("'"," ",$filename)?>")'
                alt="<?php echo $msgstr['eliminar']?>" title="<?php echo $msgstr['eliminar'].":".$fileList[$index]?>">
                <i class="far fa-trash-alt"> <?php echo $msgstr['eliminar']?></i></button></td>
        </tr>
        <?php
        }
        echo "</table>";
    }
    // Create a form
    ?>
    <form name=continuar  method=post >
        <input type=hidden name=impdoc_cnfcnt>
        <input type=hidden name=deletedocfile>
        <input type=hidden name=upldoc_cnfcnt>
        <?php
        foreach ($_REQUEST as $var=>$value){
            if ( $var!= "deletedocfile" && $var!="impdoc_cnfcnt" && $var!="upldoc_cnfcnt"){
                // some values may contain quotes or other "non-standard" values
                $value=htmlspecialchars($value);
                echo "<input type=hidden name=$var value=\"$value\">\n";
            }
        }
        ?>
        <br>
        <input type=button value='<?php echo $msgstr["dd_upload"];?>' onclick=Reselect()>
        
        <?php if ($numfiles>0 ) {
            // Display continuation button only if any files are present
            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            echo $msgstr["dd_continuewith"]?>&nbsp;&rarr;
            <input type=button value='<?php echo $msgstr["dd_imp_options"];?>' onclick=SetImportOptions()>
        <?php } ?>
    </form>
    <?php
}
/* =======================================================================
/* ----- Second screen: Set import options -*/
else if ($impdoc_cnfcnt==2) {
    echo "<h3>".$msgstr["dd_imp_step"]." 2: ".$msgstr["dd_imp_step_options"]."</h3>";
    $pretty_cisis_recsize=number_format($isis_record_size/1024,0,",",".")." Kb";
    // Find all tika jars
    $tikanamepattern="*tika*.jar";
    $tikajars=glob($cgibin_path.$tikanamepattern);
    if (sizeof($tikajars)==0 OR $tikajars===false) {
        echo "<p style='color:red'>".$msgstr["dd_imp_notika1"]."<br>";
        echo $msgstr["dd_imp_tikasrc"]." &rarr;<b>".$tikanamepattern."</b>&larr;<br>";
        echo $msgstr["dd_imp_tikadown"]." <a href='https://tika.apache.org/download.html'>Download Apache Tika</a><br>";
        echo $msgstr["dd_imp_tikainst"]." ".$cgibin_path."</p>";
        die;
    }

    ?>
    <form name=continuar  method=post >
        <input type=hidden name=mapoption>
        <?php
        foreach ($_REQUEST as $var=>$value){
            if ( $var!="upldoc_cnfcnt" && $var!="mapoption"){
                // some values may contain quotes or other "non-standard" values
                $value=htmlspecialchars($value);
                echo "<input type=hidden name=$var value=\"$value\">\n";
            }
        }
        ?>
        <div style="color:green"><?php echo $msgstr["dd_optionmsg"];?></div>
        <table cellspacing=2 cellpadding=2>
        <tr>
            <td><?php echo $msgstr["dd_addid"];?></td>
            <td><input type=checkbox id=addid name="addid" value=1 checked></td>
            <td style='color:blue'><?php echo $msgstr["dd_imp_unique"];?></td>
        </tr><tr>
            <td><?php echo $msgstr["dd_addtimestamp"];?></td>
            <td><input type=checkbox id=addtimestamp name="addtimestamp" value=1 ></td>
            <td style='color:blue'><?php echo $msgstr["dd_imp_unique"];?></td>
        </tr><tr>
            <td><?php echo $msgstr["dd_truncfilename"];?></td>
            <td><select name=truncsize id=truncsize>
                    <option value=""   ><?php echo $msgstr["dd_imp_nolimit"];?></option>
                    <option value="90" >&nbsp;90 <?php echo $msgstr["dd_imp_chars"];?></option>
                    <option value="60" >&nbsp;60 <?php echo $msgstr["dd_imp_chars"];?></option>
                    <option value="30" selected>&nbsp;30 <?php echo $msgstr["dd_imp_chars"];?></option>
                    <option value="0"  >&nbsp;&nbsp;0 <?php echo $msgstr["dd_imp_chars"];?></option>
                </select>
            </td>
            <td style='color:blue'><?php echo $msgstr["dd_truncmsg"];?></td>
        </tr><tr><td colspan=3><hr></td>
        </tr><tr>
            <td><?php echo $msgstr["dd_imp_tikajar"];?></td>
            <td><select name=tikajar id=tikajar>
                <?php
                $tikatags="";
                $numtags=0;
                foreach( $tikajars as $fulltikapath) {
                    $tikajar = substr($fulltikapath, strlen($cgibin_path));
                    echo "<option value=$tikajar>$tikajar</option>";
                    // tikatags is part of the URL to test the found tikas
                    if ($numtags>0) $tikatags.="&";
                    $tikatags.="tikajar".$numtags."=".$tikajar;
                    $numtags++;
                }
                ?>  
                </select>
            </td>
            <td style='color:blue'><?php
                $testbutton='<a href="test_tika.php?'.$tikatags.'"  target=testshow onclick=OpenWindow()>'.$msgstr["dd_imp_tikaver"].'</a>';
                echo "$testbutton"."<br>".$msgstr["dd_imp_tikasrc"]." &rarr;<b>".$tikanamepattern."</b>&larr;<br>";
                ?>
            </td>
        </tr><tr>
            <td><?php echo $msgstr["dd_textformat"];?></td>
            <td><select name=textmode id=textmode>
                    <option value="m"><?php echo $msgstr["dd_tmode_meta"];?></option>
                    <option value="t"><?php echo $msgstr["dd_tmode_text"];?></option>
                    <option value="h" selected><?php echo $msgstr["dd_tmode_html"];?></option>
                    <option value="x"><?php echo $msgstr["dd_tmode_xhtml"];?></option>
                </select>
            </td>
        </tr><tr>
            <td><?php echo $msgstr["dd_imp_usablerecsize"];?></td>
            <td><input name='splitmax' type=number min=10 max=95 value=<?php echo $splitmax;?> ></td>
            <td style='color:blue'><?php echo $msgstr["dd_imp_splitperc"]." (".$pretty_cisis_recsize.")";?></td>
        </tr>
        </tr><tr>
            <td><?php echo $msgstr["dd_imp_granularity"];?></td>
            <td><select name='splittarget' id='splitarget'>
                    <option value="80" selected>80</option>
                    <option value="60">60</option>
                    <option value="40">40</option>
                    <option value="20">20</option>
               </select>
            </td>
            <td style='color:blue'><?php echo "% of ".$msgstr["dd_imp_usablerecsize"];?></td>
        </tr><tr><td colspan=3><hr></td>
        </tr><tr>
            <td><?php echo $msgstr["dd_logto"];?></td>
            <td><select name=logmode id=logmode>
                    <option value="web" selected><?php echo $msgstr["dd_logtoweb"];?></option>
                    <option value="file"><?php echo $msgstr["dd_logtofile"];?></option>
                    <option value="both"><?php echo $msgstr["dd_logtoboth"];?></option>
                </select>
            </td>
            <td style='color:blue'><?php echo $msgstr["dd_logfolder"].": <b>wrk";?></td>
        </tr>
        </table>
        <br>
        <?php echo $msgstr["dd_continuewith"]?>&nbsp;&rarr;
        <input type=button value='<?php echo $msgstr["dd_imp_exec"];?>' onclick=Import()>
    </form>
    <?php   
}
/* =======================================================================
/* ----- Third screen: Actual import -*/
else if ($impdoc_cnfcnt==3) {
    echo "<h3>".$msgstr["dd_imp_step"]." 3: ".$msgstr["dd_imp_step_exec"]."</h3>";
    $retval=0;
    $addtimestamp=0;
    $addid=0;
    if (isset($arrHttp["addtimestamp"])) $addtimestamp=$arrHttp["addtimestamp"];
    if (isset($arrHttp["addid"]))        $addid       =$arrHttp["addid"];
    $tikajar=$arrHttp["tikajar"];
    $textmode=$arrHttp["textmode"];
    $truncsize="";
    if (isset($arrHttp["truncsize"])) $truncsize=$arrHttp["truncsize"];
    $starttime = microtime(true);
    // List all files in the upload folder
    // The content is checked in the initial screen
    $retval = list_folder("files", $coluplfull, $fileList);
    if ($retval==0) {
        $numfiles=count($fileList);
        $numfilesOK=0;
        $numrecsOK=0;
        /*
        ** Before the import:
        ** Open a progress bar/loading gift + show the number of files to do
        ** Open a div for all imports
        */
        ?>
        <div id=progresdiv>
        <table width="850">
          <tr>
            <td style="font-size:12px" height="30"><!-- Progress bar holder -->
                <div id="progress" style="width:800px;border:1px solid #ccc;">&nbsp;</div>
            </td>
          </tr>
          <tr>
            <td style="font-size:12px" height="30"><!-- Progress information -->
                <div id="information" style="width"><?php echo $msgstr["dd_imp_toimport"]." ".$numfiles;?></div>
            </td>
          </tr>
          <tr><td><?php tolog("",$logmode);?></td></tr>
        </table>
        </div>
        <div id=importactiondiv align=left>
        <?php
        /*
        ** Loop over the files to import 
        ** Stop looping in case of errors
        ** This process may take a long time, so we ensure that each file gets a new (proper) timelimit
        ** Experience: a large file takes about 1 minute on a windows laptop.
        */
        $minutes_per_file=5;
        $seconds_per_file=$minutes_per_file*60;
        $ini_max_seconds=ini_get('max_execution_time');
        if ( $ini_max_seconds!==false AND $ini_max_seconds>$seconds_per_file) $seconds_per_file=$ini_max_seconds;
        for ($i=0; $i<$numfiles && $retval==0; $i++) {
            tolog("<hr><ul><li>".$msgstr["dd_imp_actfile"]." #".($i+1)." &rarr; ".$fileList[$i]."</li>");
            set_time_limit($seconds_per_file);
            tolog("<li>".$msgstr["dd_imp_proctimelimit"]." ".$seconds_per_file." ".$msgstr["dd_imp_seconds"]."</li>");
            // Import file
            $retval=import_action($fileList[$i], $addtimestamp, $addid, $truncsize, $tikajar, $textmode, $splitmax, $splittarget,
                                  $arrHttp["base"], $numrecsOK);
            $processed=$i;
            if ($retval==0) {
                $numfilesOK++;
                $processed=$i+1;
            }
            tolog("</ul>");
            //Update the progress bar/loading gift
            $percent = intval($processed/$numfiles * 100)."%";
            $inner1='<div style="width:'.$percent.';background-color:#364c6c;"><div style="color:white" align=center>'.$percent.'</div></div>';
            $inner2=$processed." ".$msgstr["dd_imp_processed"]." ".$numfiles." ".$msgstr["dd_imp_files"];
            ?>
            <script language="javascript">
              document.getElementById("progress").innerHTML='<?php echo $inner1?>';
              document.getElementById("information").innerHTML='<?php echo $inner2?>';
              </script>
            <?php
            ob_flush();flush();
        }
        /*
        ** After import: remove import output (only if no errors) and loader icon (always)
        ** Note that this text is still present in the page source
        */
        $endtime    = microtime(true);
        $timediff   = $endtime - $starttime;
        $numfilesNOK= $numfiles - $numfilesOK;
        ?>
        <hr>
        </div>
        <script language=javascript>
            <?php if ($retval==0) {?>
            document.getElementById('importactiondiv').style.display='none'
            <?php } ?>
        </script>
        <br>
        <form name=continuar  method=post >
        <?php
        foreach ($_REQUEST as $var=>$value){
            if ( $var!="upldoc_cnfcnt" && $var!="mapoption"){
                // some values may contain quotes or other "non-standard" values
                $value=htmlspecialchars($value);
                echo "<input type=hidden name=$var value=\"$value\">\n";
            }
        }
        ?>
        <table style=color:blue  cellspacing=1 cellpadding=4>
        <tr><td><?php echo $msgstr["dd_imp_importok"]?></td>
            <td><?php echo $numfilesOK?></td>
            <td></td>
        </tr>
        <tr><td><?php echo $msgstr["dd_imp_numrec"]?></td>
            <td><?php echo $numrecsOK?></td>
            <td></td>
        </tr>
        <tr><td><?php echo $msgstr["dd_imp_importrem"]?></td>
            <td><?php echo $numfilesNOK?></td>
            <td></td>
        </tr>
        <tr>
            <td><?php echo $msgstr["dd_imp_proctime"];?></td>
            <td><?php echo secondsToTime($timediff)?></td>
            <td><input type=button value='<?php echo $msgstr["dd_imp_details"];?>' onclick=ShowDetails()></td>
        </tr>
        <tr>
            <td></td>
            <td><?php echo $msgstr["dd_continuewith"]?>&nbsp;&rarr;</td>
            <td><input type=button value='<?php echo $msgstr["dd_imp_invert"];?>' onclick=Invert()></td>
        </tr>
        </table>
        </form>
        <?php
    }
}
?>
</div>
</div>
</div>
<?php
include "../common/footer.php";
// ======================================================
// This the end of main script. Only functions follow now
//
// =========================== Functions ================
//  - import_action     : imports an uploaded file
//  - next_cn_number    : returns the next control number(=ID)
//  - convert_component : returns sanitized path component
//  - convert_field     : returns sanitized mx field
//  - convert_name      : returns sanitized filename
//  - convert_tika_html : convert tika generated html
//  - sanitize_tree     : sanitizes foldername(s)
//  - secondsToTime     : return H:mm:ss
//  - set_rectypind     : set record type indicator
//  - split_path        : returns the "section" of the filename in ImportRepo
//  - split_html        : splits an html file into smaller files and returns the number of parts.
//  - tolog             : writes information to screen and/or logfile
//
// ====== import_action =============================
function import_action($full_imp_path, $addtimestamp, $addid, $truncsize, $tikajar, $textmode, $splitmax, $splittarget, $basename, &$numrecsOK) {
/*
** Imports the given file in ImportRepo/... into the collection
** The metadata of this file is stored in an ABCD record.
** Normally a new record
**
** In: $full_imp_path = Full filename in <collection>/ImportRepo/...
** In: $addtimestamp  = Indicator to add a timestamp to the filename (0/1)
** In: $addid         = Indicator to add the record id to the filename (0/1)
** In: $truncsize     = Number of characters in core file name (""=unlimited)
** In: $tikajar       = Name of actual tika jar in cgi-bin
** In: $textmode      = Indicator for tika: m=meta, t=text, h=html, x=xhtml
** In: $splitmax      = Percentage of recordsize as maximum of the split chunk size
** In: $splittarget   = Percentage of splitmax as target of the split chunk size
** In: $basename      = short name of the database (e.g. dubcore)
** Other variables via 'Global'.
** Return : 0=OK, 1=NOT-OK
*/
global $cisis_ver, $cgibin_path, $coldocfull, $colsrcfull, $db_path, $msgstr, $mx_path,$isis_record_size,$tagConfigFull;
    $retval=1;
    clearstatcache(true);
    $debug=false; // false=production mode
    /*
    ** Check if a section is required
    ** Create section (may be multiple subfolders) if not present in collection
    ** Check if the section destination folder is a writeable folder
    */
    $orgfilename="";
    $sectionname="";
    $destdocpath=$coldocfull."/";
    $destsrcpath=$colsrcfull."/";
    split_path( $full_imp_path, $orgfilename, $sectionname);
    if ( $sectionname!="" ) {
        tolog("<li>".$msgstr["dd_chk_section"]." &rarr; ".$sectionname."</li>");
        $destdocpath.=$sectionname."/";
        if (!file_exists($destdocpath)) {
            if (!mkdir ($destdocpath,0777,true)){
                tolog('<div style="color:red">Failed to create section folder(s).... (e.g. &rarr;'.$destdocpath.'&larr;)>');
                return(1);
            }
        }
        if (!is_dir($destdocpath) || !is_writable($destdocpath) ){
             tolog('<div style="color:red">Section is not a writeable folder (e.g. &rarr;'.$destdocpath.'&larr;)>');
             return(1);
       }
        $destsrcpath.=$sectionname."/";
        if (!file_exists($destsrcpath)) {
            if (!mkdir ($destsrcpath,0777,true)){
                tolog('<div style="color:red">Failed to create section folder(s).... (e.g. &rarr;'.$destsrcpath.'&larr;)>');
                return(1);
            }
        }
        if (!is_dir($destsrcpath) || !is_writable($destsrcpath) ){
             tolog('<div style="color:red">Section is not a writeable folder (e.g. &rarr;'.$destsrcpath.'&larr;)>');
             return(1);
       }
    }
    /*
    ** Get parameters for the filename conversion
    */
    if ( next_cn_number($basename,$c_id)!=0 ){
        return(1);
    }

    /*
    ** Modify the filename. Name and extension 
    ** - Replace characters to enable usage in url (name+ext)
    ** - Split in name and extension (more extensions will be created)
    ** - Add timestamp and/or ID for uniqueness (only name)
    ** - Truncate excessive long names(only name)
    */
    if ( convert_name($orgfilename, $addtimestamp, $addid, $c_id, $truncsize, $docname, $docext) !=0 ) return(1);
    if ( convert_field($orgfilename, $def_c_source) !=0 ) return(1);
    /*
    ** - Construct the name of the tika generated target (html) file
    */
    $tikafile    = $db_path."wrk/".$docname.'.html';
    /*
    ** Move the uploaded file to the collection
    */
    $docpath=$destdocpath.$docname;
    if ($docext!="") $docpath.=".".$docext;
    tolog("<li>".$msgstr["dd_imp_moving"].": ".$orgfilename." &rarr; ".$docpath."</li>");
    if (@rename($full_imp_path, $docpath)===false){
        $contents_error= error_get_last();
        tolog("<div style='color:red'><b>".$msgstr["fatal"].": &rarr; </b>".$contents_error["message"]."<br>");
        tolog("&rarr; ".$msgstr["dd_error_moveto"]."</div>");
        $orglocale=setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'C.UTF-8');
        $importdir=dirname($full_imp_path);
        setlocale(LC_CTYPE, $orglocale);
        if (!is_writable($importdir) ) {
            tolog("<div style='font-weight:bold;color:red'>&rarr; '".$importdir."' ".$msgstr["dd_nowrite"]."</div>");
        }
        return(1);
    }
    /*
    ** Construct & execute the tika command to detect the metadata
    ** option -r: For JSON, XML and XHTML outputs, adds newlines and whitespace, for better readability
    ** Read from stdin so tika will not complain about the filename (or filename encoding).
    ** Using < in the command implies that the log message must contain &lt; in stead of <
    */
    $tikacommand='java -jar '.$cgibin_path.$tikajar.' -r -'.$textmode.' <'.$docpath.' 2>&1 >'.$tikafile;
    $tikashowcmd='java -jar '.$cgibin_path.$tikajar.' -r -'.$textmode.' &lt;'.$docpath.' 2>&1 >'.$tikafile;
    tolog ("<li>".$msgstr['procesar'].": ".$tikashowcmd."</li>");
    exec( $tikacommand, $output, $status);
    if ($status!=0) {
        tolog("<div style='color:red'><b>".$msgstr["fatal"]."<br>");
        for ($i=0; $i<count($output);$i++) {
            tolog($output[$i]."<br>");
        }
        tolog("</b></div>");
        if (file_exists($tikafile) ) unlink($tikafile);
        rename($docpath, $full_imp_path);
        return(1);
    } else {
        tolog ("<li style='color:green;font-weight:bold'>&rArr; ".$msgstr["dd_metadata_detect_ok"]."</li>");
    }
    $c_docpathsize=filesize($docpath);
    $c_tikafilesize=filesize($tikafile);
    $pretty_docp_filesize=number_format($c_docpathsize/1024,2,",",".")." Kb";
    $pretty_tika_filesize=number_format($c_tikafilesize/1024,2,",",".")." Kb";
    tolog("<li>".$msgstr["dd_docpfilesize"].$pretty_docp_filesize." ".$msgstr["dd_htmlfilesize"]." ".$pretty_tika_filesize."</li>");

    /*
    ** Sanitize html tika originating from all files
    ** Do not yet replace the header as this is needed to detect the metadata
    */
    if ( $textmode=="h"  ) {
        convert_tika_html($tikafile,"initial", "");
    }
    /*
    ** Read metadata from the tika generated html file
    ** Lines like 
    ** <meta name="dc:format" content="application/pdf; version=1.5"/>
    ** Actual files may contain hundreds of such lines.
    ** PHP function get_meta_tags handles this efficiently
    ** A missing file is considered a fatal error
    ** This file can be empty OR very large
    */
    tolog("<li>".$msgstr["dd_detect_meta"]." ".$tikafile."</li>");
    ob_flush();flush();
    $metatab=array();
    $metatab=get_meta_tags($tikafile);
    if ($metatab===false) {
        tolog("<div style='color:red'>".$msgstr["dd_error_get_meta"]." (".$tikafile.")</div>");
        unlink($tikafile);
        rename($docpath, $full_imp_path);
        return(1);
    }
    /*
    ** Sanitize the strings found by tika: They will be used by mx and mx does not like some characters
    */
    reset($metatab);
    for ($i=0; $i<count($metatab); $i++ ) {
        $key=key($metatab);
        $val=current($metatab);
        $newval="";
        convert_field($val,$newval);
        $metatab[$key]=$newval;
        if ($debug) echo "key=".$key." value=".$newval."<br>";
        next($metatab);
    }
    /*
    ** Get metadata value/content from dc attributes
    ** Ensure that each dc element has a value
    */
    if (array_key_exists("dc:title",$metatab))      {$c_title=$metatab["dc:title"];}             else {$c_title="";}
    if (array_key_exists("dc:creator",$metatab))    {$c_creator=$metatab["dc:creator"];}         else {$c_creator="";}
    if (array_key_exists("dc:subject",$metatab))    {$c_subject=$metatab["dc:subject"];}         else {$c_subject="";}
    if (array_key_exists("dc:description",$metatab)){$c_description=$metatab["dc:description"];} else {$c_description="";}
    if (array_key_exists("dc:publisher",$metatab))  {$c_publisher=$metatab["dc:publisher"];}     else {$c_publisher="";}
    if (array_key_exists("dc:contributor",$metatab)){$c_contributor=$metatab["dc:contributor"];} else {$c_contributor="";}
    if (array_key_exists("dc:date",$metatab))       {$c_date=$metatab["dc:date"];}               else {$c_date="";}
    if (array_key_exists("dc:type",$metatab))       {$c_type=$metatab["dc:type"];}               else {$c_type="";}
    if (array_key_exists("dc:format",$metatab))     {$c_format=$metatab["dc:format"];}           else {$c_format="";}
    if (array_key_exists("dc:identifier",$metatab)) {$c_identifier=$metatab["dc:identifier"];}   else {$c_identifier="";}
    if (array_key_exists("dc:source",$metatab))     {$c_source=$metatab["dc:source"];}           else {$c_source="";}
    if (array_key_exists("dc:language",$metatab))   {$c_language=$metatab["dc:language"];}       else {$c_language="";}
    if (array_key_exists("dc:relation",$metatab))   {$c_relation=$metatab["dc:relation"];}       else {$c_relation="";}
    if (array_key_exists("dc:coverage",$metatab))   {$c_coverage=$metatab["dc:coverage"];}       else {$c_coverage="";}
    if (array_key_exists("dc:rights",$metatab))     {$c_rights=$metatab["dc:rights"];}           else {$c_rights="";}
    /*
    ** If dc term did not reveal a value try dcterms (the successor definition)
    */
    if ($c_title==""      && array_key_exists("dcterms:title",$metatab))      {$c_title=$metatab["dcterms:title"];}
    if ($c_creator==""    && array_key_exists("dcterms:creator",$metatab))    {$c_creator=$metatab["dcterms:creator"];}
    if ($c_subject==""    && array_key_exists("dcterms:subject",$metatab))    {$c_subject=$metatab["dcterms:subject"];}
    if ($c_description==""&& array_key_exists("dcterms:description",$metatab)){$c_description=$metatab["dcterms:description"];}
    if ($c_publisher==""  && array_key_exists("dcterms:publisher",$metatab))  {$c_publisher=$metatab["dcterms:publisher"];}
    if ($c_contributor==""&& array_key_exists("dcterms:contributor",$metatab)){$c_contributor=$metatab["dcterms:contributor"];}
    if ($c_date==""       && array_key_exists("dcterms:date",$metatab))       {$c_date=$metatab["dcterms:date"];}
    if ($c_type==""       && array_key_exists("dcterms:type",$metatab))       {$c_type=$metatab["dcterms:type"];}
    if ($c_format==""     && array_key_exists("dcterms:format",$metatab))     {$c_format=$metatab["dcterms:format"];}
    if ($c_identifier=="" && array_key_exists("dcterms:identifier",$metatab)) {$c_identifier=$metatab["dcterms:identifier"];}
    if ($c_source==""     && array_key_exists("dcterms:source",$metatab))     {$c_source=$metatab["dcterms:source"];}
    if ($c_language==""   && array_key_exists("dcterms:language",$metatab))   {$c_language=$metatab["dcterms:language"];}
    if ($c_relation==""   && array_key_exists("dcterms:relation",$metatab))   {$c_relation=$metatab["dcterms:relation"];}
    if ($c_coverage==""   && array_key_exists("dcterms:coverage",$metatab))   {$c_coverage=$metatab["dcterms:coverage"];}
    if ($c_rights==""     && array_key_exists("dcterms:rights",$metatab))     {$c_rights=$metatab["dcterms:rights"];}
    /*
    ** Fill dc: values if not given dc: or dcterms:
    */
    if ($c_date=="" && array_key_exists("dcterms:created",$metatab)) {$c_date=$metatab["dcterms:created"];}
    if ($c_type=="" && array_key_exists("content-type",$metatab))    {$c_type=$metatab["content-type"];}
    if ($c_type=="")   $c_type   = $docext;
    if ($c_source=="") $c_source = $def_c_source;
    /*
    ** Get metadata value/content from exif attributes
    ** Ensure that each dc element has a value
    */
    if (array_key_exists("exif_ifd0:image_height",$metatab))     {$c_exifheight=$metatab["exif_ifd0:image_height"];}       else {$c_exifheight="";}
    if (array_key_exists("exif_ifd0:image_width",$metatab))      {$c_exifwidth=$metatab["exif_ifd0:image_width"];}         else {$c_exifwidth="";}
    if (array_key_exists("exif_ifd0:x_resolution",$metatab))     {$c_exifxresol=$metatab["exif_ifd0:x_resolution"];}       else {$c_exifxresol="";}
    if (array_key_exists("exif_ifd0:y_resolution",$metatab))     {$c_exifyresol=$metatab["exif_ifd0:y_resolution"];}       else {$c_exifyresol="";}
    if (array_key_exists("exif_ifd0:scene_type",$metatab))       {$c_exifscenetyp=$metatab["exif_ifd0:scene_type"];}       else {$c_exifscenetyp="";}
    if (array_key_exists("exif_ifd0:user_comment",$metatab))     {$c_exifusercom=$metatab["exif_ifd0:user_comment"];}      else {$c_exifusercom="";}
    if (array_key_exists("exif_ifd0:artist",$metatab))           {$c_exifartist=$metatab["exif_ifd0:artist"];}             else {$c_exifartist="";}
    if (array_key_exists("exif_ifd0:copyright",$metatab))        {$c_exifcopyrght=$metatab["exif_ifd0:copyright"];}        else {$c_exifcopyrght="";}
    if (array_key_exists("exif_ifd0:make",$metatab))             {$c_exifmake=$metatab["exif_ifd0:make"];}                 else {$c_exifmake="";}
    if (array_key_exists("exif_ifd0:model",$metatab))            {$c_exifmodel=$metatab["exif_ifd0:model"];}               else {$c_exifmodel="";}
    if (array_key_exists("gps:gps_altitude_ref",$metatab))       {$c_gpsaltref=$metatab["gps:gps_altitude_ref"];}          else {$c_gpsaltref="";}
    if (array_key_exists("gps:gps_altitude",$metatab))           {$c_gpsalt=$metatab["gps:gps_altitude"];}                 else {$c_gpsalt="";}
    if (array_key_exists("gps:gps_latitude_ref",$metatab))       {$c_gpslatref=$metatab["gps:gps_latitude_ref"];}          else {$c_gpslatref="";}
    if (array_key_exists("gps:gps_latitude",$metatab))           {$c_gpslat=$metatab["gps:gps_latitude"];}                 else {$c_gpslat="";}
    if (array_key_exists("gps:gps_longitude_ref",$metatab))      {$c_gpslongref=$metatab["gps:gps_longitude_ref"];}        else {$c_gpslongref="";}
    if (array_key_exists("gps:gps_longitude",$metatab))          {$c_gpslong=$metatab["gps:gps_longitude"];}               else {$c_gpslong="";}
    /*
    ** if ifd0 did not succeed: try subifd (with sometimes another term)
    */
    if ($c_exifheight==""   && array_key_exists("exif_subifd:exif_image_height",$metatab)) {$c_exifheight=$metatab["exif_subifd:exif_image_height"];}
    if ($c_exifwidth==""    && array_key_exists("exif_subifd:exif_image_width",$metatab))  {$c_exifwidth=$metatab["exif_subifd:exif_image_width"];}
    if ($c_exifscenetyp=="" && array_key_exists("exif_subifd:scene_type",$metatab))        {$c_exifscenetyp=$metatab["exif_subifd:scene_type"];}
    if ($c_exifusercom==""  && array_key_exists("exif_subifd:user_comment",$metatab))      {$c_exifusercom=$metatab["exif_subifd:user_comment"];}
    /*
    ** Construct other metadata content:
    ** - c_htmlSrcURL  : computed after split
    ** - c_sections    : by the section name
    ** - c_url         : by /docs/<collection>/<sectionname>/<docname>.<doc_ext>
    ** - c_id          : by next_cn_number (already set at filename conversion)
    ** - c_dateadded   : by current data&time
    ** - c_htmlfilesize: computed after split
    ** - c_doctext     : Reserved for future actions
    */
    $c_sections=$sectionname;
    $c_url="/docs/";
    $c_url.=substr($coldocfull, strlen($db_path));
    if ($sectionname!="") $c_url.="/".$sectionname;
    $c_url.="/".$docname;
    if ($docext!="") $c_url.=".".$docext;
    $c_dateadded=date("Y-m-d H:i:s");
    $c_doctext="";
    /*
    ** Determine the record type
    */
    set_rectypind($metatab, $c_rectypind);
    /*
    ** Get the tags to be processed
    */
    $actTagMap= array();
    $retval   = read_dd_cfg("operates", $tagConfigFull, $actTagMap );
    $actterms = array_column($actTagMap,"term");
    $actfields= array_column($actTagMap,"field");
    if ($debug){
        echo "<br>configuration table<br>";
        for ($i=0;$i<count($actterms);$i++ ) {
            echo $actterms[$i]."  -  ".$actfields[$i]."<br>";
        }
    }
    // Note that array_search MAY return false. This is not checked here:TODO
    $vtitle       = remove_v( $actfields[array_search("title",$actterms)] );
    $vcreator     = remove_v( $actfields[array_search("creator",$actterms)] );
    $vsubject     = remove_v( $actfields[array_search("subject",$actterms)] );
    $vdescription = remove_v( $actfields[array_search("description",$actterms)] );
    $vpublisher   = remove_v( $actfields[array_search("publisher",$actterms)] );
    $vcontributor = remove_v( $actfields[array_search("contributor",$actterms)] );
    $vdate        = remove_v( $actfields[array_search("date",$actterms)] );
    $vtype        = remove_v( $actfields[array_search("type",$actterms)] );
    $vformat      = remove_v( $actfields[array_search("format",$actterms)] );
    $videntifier  = remove_v( $actfields[array_search("identifier",$actterms)] );
    $vsource      = remove_v( $actfields[array_search("source",$actterms)] );
    $vlanguage    = remove_v( $actfields[array_search("language",$actterms)] );
    $vrelation    = remove_v( $actfields[array_search("relation",$actterms)] );
    $vcoverage    = remove_v( $actfields[array_search("coverage",$actterms)] );
    $vrights      = remove_v( $actfields[array_search("rights",$actterms)] );
    $vhtmlSrcURL  = remove_v( $actfields[array_search("htmlSrcURL",$actterms)] );
    $vrectypind   = remove_v( $actfields[array_search("rectypind",$actterms)] );
    $vsections    = remove_v( $actfields[array_search("sections",$actterms)] );
    $vurl         = remove_v( $actfields[array_search("url",$actterms)] );
    $vdoctext     = remove_v( $actfields[array_search("doctext",$actterms)] );
    $vidpart      = remove_v( $actfields[array_search("idpart",$actterms)] );
    $vid          = remove_v( $actfields[array_search("id",$actterms)] );
    $vdateadded   = remove_v( $actfields[array_search("dateadded",$actterms)] );
    $vhtmlfilesize= remove_v( $actfields[array_search("htmlfilesize",$actterms)] );

    $vexifheight  = remove_v( $actfields[array_search("exifheight",$actterms)] );
    $vexifwidth   = remove_v( $actfields[array_search("exifwidth",$actterms)] );
    $vexifxresol  = remove_v( $actfields[array_search("exifxresol",$actterms)] );
    $vexifyresol  = remove_v( $actfields[array_search("exifyresol",$actterms)] );
    $vexifscenetyp= remove_v( $actfields[array_search("exifscenetyp",$actterms)] );
    $vexifusercom = remove_v( $actfields[array_search("exifusercom",$actterms)] );
    $vexifartist  = remove_v( $actfields[array_search("exifartist",$actterms)] );
    $vexifcopyrght= remove_v( $actfields[array_search("exifcopyrght",$actterms)] );
    $vexifmake    = remove_v( $actfields[array_search("exifmake",$actterms)] );
    $vexifmodel   = remove_v( $actfields[array_search("exifmodel",$actterms)] );
    $vgpsaltref   = remove_v( $actfields[array_search("gpsaltref",$actterms)] );
    $vgpsalt      = remove_v( $actfields[array_search("gpsalt",$actterms)] );
    $vgpslatref   = remove_v( $actfields[array_search("gpslatref",$actterms)] );
    $vgpslat      = remove_v( $actfields[array_search("gpslat",$actterms)] );
    $vgpslongref  = remove_v( $actfields[array_search("gpslongref",$actterms)] );
    $vgpslong     = remove_v( $actfields[array_search("gpslong",$actterms)] );
    /*
    ** Sanitize html tika originating from all files
    ** Replace the header to conform with split files
    */
    if ( $textmode=="h"  ) {
        convert_tika_html($tikafile,"header",$c_title);
    }
    /*
    ** The generated file may be too large for the current database
    ** Next function call will split this file and return a list of files to be imported
    */
    $split_files=Array();
    if ( split_html($tikafile,$textmode,$c_title, $isis_record_size, $splitmax, $splittarget, $split_files)!=0 ) {
        unlink($tikafile);
        rename($docpath, $full_imp_path);
        return(1);
    }
    $maxparts=sizeof($split_files);
    for ($ix=0; $ix<$maxparts; $ix++ ) {
        $act_split_file=$split_files[$ix];
        /*
        ** - Construct the name of the mx proc input file.
        **   Use pid because mx does not like utf characters in this filename
        **   Always add a timestamp because the pid is the server pid, not unique for this run
        */
        $procfile    = getmypid()."_".date("ymdHis")."_".$ix;//add always timestamp and sequence number!!
        $procfile    = $db_path."wrk/".$procfile.'.proc';

        /*
        ** Construct the proc file with metadata for mx
        ** Note that the commandline has limitations (length,allowed character) so a file is better
        ** The actual terms depend on the part: see comments
        */
        $fpproc=fopen($procfile,"w");
        $fields="'";
        // The ID field is always required to perform the lookup to the first record with this ID
        if (($c_id!="")          and ($vid!=""))          $fields.="<".$vid.">".$c_id."</".$vid.">".PHP_EOL;
        // Next fields are for the first record only
        if ($ix==0) {
            if (($c_title!="")       and ($vtitle!=""))       $fields.="<".$vtitle.">".$c_title."</".$vtitle.">".PHP_EOL;
            if (($c_creator!="")     and ($vcreator!=""))     $fields.="<".$vcreator.">".$c_creator."</".$vcreator.">".PHP_EOL;
            if (($c_subject!="")     and ($vsubject!=""))     $fields.="<".$vsubject.">".$c_subject."</".$vsubject.">".PHP_EOL;
            if (($c_description!="") and ($vdescription!="")) $fields.="<".$vdescription.">".$c_description."</".$vdescription.">".PHP_EOL;
            if (($c_publisher!="")   and ($vpublisher!=""))   $fields.="<".$vpublisher.">".$c_publisher."</".$vpublisher.">".PHP_EOL;
            if (($c_contributor!="") and ($vcontributor!="")) $fields.="<".$vcontributor.">".$c_contributor."</".$vcontributor.">".PHP_EOL;
            if (($c_date!="")        and ($vdate!=""))        $fields.="<".$vdate.">".$c_date."</".$vdate.">".PHP_EOL;
            if (($c_type!="")        and ($vtype!=""))        $fields.="<".$vtype.">".$c_type."</".$vtype.">".PHP_EOL;
            if (($c_format!="")      and ($vformat!=""))      $fields.="<".$vformat.">".$c_format."</".$vformat.">".PHP_EOL;
            if (($c_identifier!="")  and ($videntifier!=""))  $fields.="<".$videntifier.">".$c_identifier."</".$videntifier.">".PHP_EOL;
            if (($c_source!="")      and ($vsource!=""))      $fields.="<".$vsource.">".$c_source."</".$vsource.">".PHP_EOL;
            if (($c_language!="")    and ($vlanguage!=""))    $fields.="<".$vlanguage.">".$c_language."</".$vlanguage.">".PHP_EOL;
            if (($c_relation!="")    and ($vrelation!=""))    $fields.="<".$vrelation.">".$c_relation."</".$vrelation.">".PHP_EOL;
            if (($c_coverage!="")    and ($vcoverage!=""))    $fields.="<".$vcoverage.">".$c_coverage."</".$vcoverage.">".PHP_EOL;
            if (($c_rights!="")      and ($vrights!=""))      $fields.="<".$vrights.">".$c_rights."</".$vrights.">".PHP_EOL;
            if (($c_sections!="")    and ($vsections!=""))    $fields.="<".$vsections.">".$c_sections."</".$vsections.">".PHP_EOL;
            if (($c_url!="")         and ($vurl!=""))         $fields.="<".$vurl.">".$c_url."</".$vurl.">".PHP_EOL;
            if (($c_dateadded!="")   and ($vdateadded!=""))   $fields.="<".$vdateadded.">".$c_dateadded."</".$vdateadded.">".PHP_EOL;
            if (($c_doctext!="")     and ($vdoctext!=""))     $fields.="<".$vdoctext.">".$c_doctext."</".$vdoctext.">".PHP_EOL;
            
            if (($c_exifheight!="")  and ($vexifheight!=""))  $fields.="<".$vexifheight.">".$c_exifheight."</".$vexifheight.">".PHP_EOL;
            if (($c_exifwidth!="")   and ($vexifwidth!=""))   $fields.="<".$vexifwidth.">".$c_exifwidth."</".$vexifwidth.">".PHP_EOL;
            if (($c_exifxresol!="")  and ($vexifxresol!=""))  $fields.="<".$vexifxresol.">".$c_exifxresol."</".$vexifxresol.">".PHP_EOL;
            if (($c_exifyresol!="")  and ($vexifyresol!=""))  $fields.="<".$vexifyresol.">".$c_exifyresol."</".$vexifyresol.">".PHP_EOL;
            if (($c_exifscenetyp!="")and ($vexifscenetyp!=""))$fields.="<".$vexifscenetyp.">".$c_exifscenetyp."</".$vexifscenetyp.">".PHP_EOL;
            if (($c_exifusercom!="") and ($vexifusercom!="")) $fields.="<".$vexifusercom.">".$c_exifusercom."</".$vexifusercom.">".PHP_EOL;
            if (($c_exifartist!="")  and ($vexifartist!=""))  $fields.="<".$vexifartist.">".$c_exifartist."</".$vexifartist.">".PHP_EOL;
            if (($c_exifcopyrght!="")and ($vexifcopyrght!=""))$fields.="<".$vexifcopyrght.">".$c_exifcopyrght."</".$vexifcopyrght.">".PHP_EOL;
            if (($c_exifmake!="")    and ($vexifmake!=""))    $fields.="<".$vexifmake.">".$c_exifmake."</".$vexifmake.">".PHP_EOL;
            if (($c_exifmodel!="")   and ($vexifmodel!=""))   $fields.="<".$vexifmodel.">".$c_exifmodel."</".$vexifmodel.">".PHP_EOL;
            if (($c_gpsaltref!="")   and ($vgpsaltref!=""))   $fields.="<".$vgpsaltref.">".$c_gpsaltref."</".$vgpsaltref.">".PHP_EOL;
            if (($c_gpsalt!="")      and ($vgpsalt!=""))      $fields.="<".$vgpsalt.">".$c_gpsalt."</".$vgpsalt.">".PHP_EOL;
            if (($c_gpslatref!="")   and ($vgpslatref!=""))   $fields.="<".$vgpslatref.">".$c_gpslatref."</".$vgpslatref.">".PHP_EOL;
            if (($c_gpslat!="")      and ($vgpslat!=""))      $fields.="<".$vgpslat.">".$c_gpslat."</".$vgpslat.">".PHP_EOL;
            if (($c_gpslongref!="")  and ($vgpslongref!=""))  $fields.="<".$vgpslongref.">".$c_gpslongref."</".$vgpslongref.">".PHP_EOL;
            if (($c_gpslong!="")     and ($vgpslong!=""))     $fields.="<".$vgpslong.">".$c_gpslong."</".$vgpslong.">".PHP_EOL;
        }

        // some variables are dependent on the actual processed file: valid for all parts
        $htmlfilesec   = substr($act_split_file, strlen($db_path."wrk/"));
        // Gload on Windows does not like unicode characters in the file name
        if ( strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ) {
            // urlencode converts all unicode to %hex.
            // The % is stripped as this is not accepted in the url
            $htmlfilesec=str_replace("%","",urlencode( $htmlfilesec));
        }
        $htmlSrcPath   = $destsrcpath.$htmlfilesec;
        $c_htmlSrcURL  = "/docs/".substr($htmlSrcPath, strlen($db_path));
        if (($c_htmlSrcURL!="")  and ($vhtmlSrcURL!=""))  $fields.="<".$vhtmlSrcURL.">".$c_htmlSrcURL."</".$vhtmlSrcURL.">".PHP_EOL;
        $c_htmlfilesize= filesize($act_split_file);
        if (($c_htmlfilesize!="")and ($vhtmlfilesize!=""))$fields.="<".$vhtmlfilesize.">".$c_htmlfilesize."</".$vhtmlfilesize.">".PHP_EOL;
        /*
        ** The partnumber is computed and added if there are multiple parts
        ** Valid for all parts
        */
        if ($maxparts>1) {
            $fields.="<".$vidpart.">".($ix+1)."</".$vidpart.">".PHP_EOL;
        }
        /*
        ** The recordtype indicator is always required, but the content differs from single/continuation page
        */
        if ($vrectypind!="" ) {
            $actual_rectypind=$c_rectypind;
            if ($maxparts>1 && ($ix+1)>1  ) $actual_rectypind.="_c";
            $fields.="<".$vrectypind.">".$actual_rectypind."</".$vrectypind.">".PHP_EOL;
        }
        $fields.="'";
        fwrite($fpproc,$fields);
        fclose($fpproc);
        /*
        ** run mx to create the record
        */
        $mxcommand= $mx_path." null proc=@".$procfile. " append=".$db_path.$basename."/data/".$basename. " count=1 now -all 2>&1";
        $mxpretty=str_replace("<","&lt;",$mxcommand);
        $mxpretty=str_replace(">","&gt;",$mxpretty);
        tolog("<li>".$msgstr['procesar'].": ".$mxpretty."</li>");
        exec( $mxcommand, $output, $status);
        if ($status!=0) {
            tolog("<div style='color:red'><b>".$msgstr["fatal"]."<br>");
            for ($i=0; $i<count($output);$i++) {
                tolog($output[$i]."<br>");
            }
            tolog("</b></div>");
            if (file_exists($act_split_file) ) unlink($act_split_file);
            if (file_exists($procfile) ) unlink($procfile);
            rename($docpath, $full_imp_path);
            return(1);
        } else {
            tolog("<li style='color:green;font-weight:bold'>&rArr; ".$msgstr["dd_record_created"]);
            tolog(": <i>".$docname.".".$docext."</i></li>");
        }
        if (file_exists($act_split_file) ) rename($act_split_file,$htmlSrcPath);
        if (file_exists($procfile) ){
            if(!$debug) unlink($procfile);
        }
        $numrecsOK++;
   }

    return(0);
}
// ====== next_cn_number ============================
function next_cn_number($basename, &$cn){
/*
** Returns the next control number.
** The control number is administrated in <basename>/data/control_number.cn
** This file is created if it does not exist.
**
** In : $basename = Short database name
** Out: $cn       = Next control number or "" in case of errors
**
** Return : 0=OK, 1=NOT-OK
*/
global $db_path,$max_cn_length, $msgstr;
    $cn="";
    $retval=0;
    $archivo=$db_path.$basename."/data/control_number.cn";
    $archivobak=$db_path.$basename."/data/control_number.bak";
    // ensure that a .cn file exists
	if (!file_exists($archivo)){
        $fp=@fopen($archivo,"w");
        if ($fp===false) {
            $contents_error= error_get_last();
            tolog("<div style='color:red'><b>".$msgstr["fatal"].": &rarr; </b>".$contents_error["message"]."<br>");
            tolog("&rarr; ".$msgstr["dd_error_init_cnfile"]."</div>");
            return(1);
        }
        fwrite($fp,"0");
        fclose($fp);
    }
    // Read the cn number from the .cn file and increment it
    $fp=file($archivo);
    $cn=implode("",$fp);
    $cn=$cn+1;
    // Remove an existing .bak file and make the current file the .bak
    if (file_exists($archivobak)) unlink($archivobak);
    rename($archivo,$archivobak);
    // Write a new .cn file
    $fp=@fopen($archivo,"w");
    if ($fp===false) {
        $contents_error= error_get_last();
        tolog("<div style='color:red'><b>".$msgstr["fatal"].": &rarr; </b>".$contents_error["message"]."<br>");
        tolog("&rarr; ".$msgstr["dd_error_next_cnfile"]."</div>");
        return(1);
    }
    fwrite($fp,$cn);
    fclose($fp);
    if (isset($max_cn_length)) $cn=str_pad($cn, $max_cn_length, '0', STR_PAD_LEFT);
    return($retval);
}
// ====== convert_component =============================
/*
** Converts a path component into a sanitized component
** Leading parent folders should not be present:
** The / and \ are converted to the replacemtn character
** 
** - Replace characters to enable usage in url and windows/linux filename restrictions
**
** In/Out: $path    = Partial filename
** Out   : $encoding= encoding (detected from the path
**
** Return : 0=OK, 1=NOT-OK
*/
function convert_component(&$path, &$name_encoding){
    global $msgstr;
    $replacechar= "_";
    /*
    ** Detect most probable filename encoding:
    ** Set the detection order. Not the default PHP (is too simple)
    ** Order is important: UTF must be before ISO !!
    ** No way to distinguish mechanically ISO and Windows-1252
    */
    $ary[] = "ASCII";
    $ary[] = "UTF-8";
    $ary[] = "ISO-8859-1";
    mb_detect_order($ary);
    $name_encoding = mb_detect_encoding($path,null,true);
    if ($name_encoding===false) {
        tolog("<div style='color:red'>".$msgstr["dd_filnamerrenc"]." &rarr;".$path."&larr;</div>");
        return 1;
    }
    /*
    ** Note that filenames may occur in the
    ** - "path component"of the URI  : Requires name_encoding or substitution
    ** - "query component" of the URI: This can be encoded by PHP function: htmlspecialchars. No action here
    ** From rfc 3986: "reserved" characters. Protected from normalization and 
    ** safe to be used for delimiting data subcomponents within a URI
    ** - reserved gen-delims/sub-delims            ==> : / ? # [ ] @ ! $ & ' ( ) * + , ; =
    ** - windows filename restrictions             ==>   / ?                     *         " \ < > |
    ** - linux filename restrictions               ==>   /
    ** Other characters that upset this script. (Marked !! if general filename rules also advise against usage)
    ** !!space upsets uri check in tika            ==>
    ** - Circumflex Accent upsets redirect(windows)==> ^
    ** !!Backtick upsets redirect (Linux)          ==> `
    ** !!Percent upsets (links,redirect)           ==> %
    ** ==> None of the characters above should appear in filenames
    ** From rfc 3986: unreserved chars    ==> A-Z a-z 0-9 - . _ ~
    */
    // Replace gen-delims/subdelims
    $path = mb_ereg_replace("[:/\?#\[\]@!$&'\(\)\*\+,;=]",$replacechar,$path);
    // Replace windows restrictions (includes Linux)
    $path = mb_ereg_replace("[\"\\<>\|]",$replacechar,$path);
    // Replace space
    $path = mb_ereg_replace(" ",$replacechar,$path);
    // Replace Circumflex accent
    $path = mb_ereg_replace("\^",$replacechar,$path);
    // Replace backtick
    $path = mb_ereg_replace("\`",$replacechar,$path);
    // Replace %. Percent encoded space is done extra. Others not
    $path = mb_ereg_replace("%20",$replacechar,$path);
    $path = mb_ereg_replace("%",$replacechar,$path);
  
    // Replace all non-unreserved : No. Gives wrong effect !
    //$path = mb_ereg_replace("[^a-z0-9\-._~]","-",$path);echo "4&rarr;".$path."<br>";
    return(0);
}
// ======= convert_field =====================================
/*
** Converts the content of a database field in such a way that
** mx accepts it in a proc (file)
** Characters "'", "<", ">" are replaced by their html code
** CRLF and LF are replaced by a space
**
** In : $orgtext = original text with malicious text
** Out: $cnvtext = converted text
*/
function convert_field($orgtext, &$cnvtext){
    global $msgstr;
    $cnvtext="";
    if ($orgtext=="") return 0;
    /*
    ** Detect most probable filename encoding:
    ** Set the detection order. Not the default PHP (is too simple)
    ** Order is important: UTF must be before ISO !!
    ** No way to distinguish mechanically ISO and Windows-1252
    */
    $ary[] = "ASCII";
    $ary[] = "UTF-8";
    $ary[] = "ISO-8859-1";
    mb_detect_order($ary);
    $name_encoding = mb_detect_encoding($orgtext,null,true);
    if ($name_encoding===false) {
        tolog("<div style='color:red'>".$msgstr["dd_fielderrenc"]." &rarr;".$orgtext."&larr;</div>");
        return 1;
    }
    $cnvtext = mb_ereg_replace("'","&apos;",$orgtext);
    $cnvtext = mb_ereg_replace("<","&lt;",$cnvtext);
    $cnvtext = mb_ereg_replace(">","&gt;",$cnvtext);
    $cnvtext = mb_ereg_replace("\r\n"," ",$cnvtext);
    $cnvtext = mb_ereg_replace("\n"," ",$cnvtext);
    return 0;
}
// ====== convert_name =============================
/*
** Converts filename and extension so they will be valid in windows AND linux
** 
** - Names in lowercase to be valid for windows/linux (and exports/imports)
** - Replace characters to enable usage in url and windows/linux filename restrictions
** - Add timestamp and/or ID for uniqueness (only for the filename)
** - Truncate excessive long names(only for the filename)
** - Note that on windows unicode names to process by Gload will get an extra conversion
**
** In  : $orgfilename  = Full or partial filename
** In  : $addtimestamp = 0:no stamp, 1: add stamp
** In  : $addid        = 0:no id, 1: add id
** In  : $c_id         = The value of the ID (supposed to be control number)
** In  : $truncsize    = Number of characters in base name (""=unlimited)
** Out : $filename     = PATHINFO_FILENAME  (processed)
** Out : $extension    = PATHINFO_EXTENSION (processed)
**
** Return : 0=OK, 1=NOT-OK
*/
function convert_name($orgfilename, $addtimestamp, $addid, $c_id, $truncsize, &$filename, &$extension){
    global $msgstr;
    $filename="";
    $extension="";
    if ($orgfilename=="") return 0;
    $time_sep="__";
    $id_sep="__";
    $replacechar= "_";
    /*
    ** Convert and cleanup of the filename
    */
    if ( convert_component($orgfilename,$name_encoding)!=0) return 1;
    /*
    ** Cleanup of the name : to lower case
    */
    $orgfilename =  mb_strtolower($orgfilename,$name_encoding);
    /*
    ** Function path_info is used to extract the desired components.
    ** Function is locale aware: to parse a path with multibyte characters, the matching locale must be set
    */
    $orglocale=setlocale(LC_CTYPE, 0);
    setlocale(LC_CTYPE, 'C.'.$name_encoding);
    $path_parts  = pathinfo($orgfilename);
    setlocale(LC_CTYPE, $orglocale);
    $filename     = $path_parts['filename'];
    if (isset($path_parts['extension'])) $extension = $path_parts['extension'];
    
    // truncate before adding the timestamp 
    if ( $truncsize!="" ) {
        $itruncsize=intval($truncsize);
        if( mb_strlen($filename,$name_encoding)> $itruncsize) {
            $filename=mb_substr($filename,0,$itruncsize,$name_encoding);
        }
    }
    if ($addtimestamp==1) {
        // Add timestamp. Time is in seconds, date is larger but readable
        // Time format = "ymdHis" (default, always a unique filename even if truncated to 0)
        // Time format = "His" if filename >5 characters. Assumed sufficient uniqueness
        $timeformat="ymdHis";
        if( mb_strlen($filename,$name_encoding)>5)$timeformat="His";
        $filename=$filename.$time_sep.date($timeformat);
    }
    if ($addid==1) {
        $filename.=$id_sep.$c_id;
    }
    return(0);
}
// ====== convert_tika_html ============================
function convert_tika_html($tikafile, $pass, $c_title){
/*
** Update the content of the html file generated by tiki
** Controlled by variable $pass
**  $pass="initial":
**      - skip inline content ( <p>&lt;&lt;     ... </p> )
**      - skip images (<img .... /> )
**  $pass="header":
**      - replace header content
**
** In : $tikafile = full name of the file generated by tika
** In : $pass     = parameter to control the content update
**
** Return : 0=OK, 1=NOT-OK
*/
global $msgstr;
    // open a the tika file and a new temporary tika file
    $fptika=fopen($tikafile,"r");
    $tmptikafile=$tikafile.".tmp";
    $fptmptika=fopen($tmptikafile,"w");
    /*
    ** Read the tikafile and copy almost everything to the tmp file
    */
    $index="dd_".$pass;
    tolog ("<li>".$msgstr["dd_clean_pdf"]." (".$msgstr["$index"]."). ");
    $numlinestika=0;
    $numlinestmp=0;
    $skip=false;
    $headadded=false;
    while (!feof($fptika)) { // main loop over all lines of the source file
        $thisline = trim(fgets($fptika)); // get line contents trimmed
        $numlinestika++;
        // tika has also at least an empty line at the end of the file
        if( $thisline=="") continue;
        if( $pass=="initial") {
            // The initial cleaunup removes several raw things, except the header
            // pdf history lines
            $metapos=strpos($thisline,'<meta name="xmpMM:');
            if( $metapos!==false  AND $metapos==1 ) continue;
            // tika generates many lines for inline content (not valid to show or index)
            if( $thisline=="<p>&lt;&lt;") $skip=true;
            if( $thisline=="</p>") {
                $skip=false;
                continue;
            }
            if( $skip==true) continue;
            // some files (e.g. epub) show embedded images. They corrupt the index
            $imgposbeg=stripos($thisline,'<img');// start of image
            if( $imgposbeg!==false AND strlen($thisline)>2) {
                $imgposend=stripos($thisline,'/>',-2);// start of image
                if( $imgposend!==false) continue;
            }
            // Some lines need adjustment
            $thisline = str_ireplace('<p/>' , '<br>', $thisline);//tika generates invalid tag <p/> (windows)
            $thisline = preg_replace('/\t/' , ' '   , $thisline);//tika keeps too much spaces (linux,windows)
            $thisline = preg_replace('/  +/', ' '   , $thisline);//tika keeps too much spaces (linux,windows)
        } 
        else if( $pass=="header") {
            // The head cleanup  replaces the tika header by a minimal header
            if ( strpos($thisline,"<html")      !==false) continue; //skip html tag
            if ( strpos($thisline,"<head")      !==false) continue; //skip head tag
            if ( strpos($thisline,"</head")     !==false) continue; //skip head tag
            if ( strpos($thisline,"<title")     !==false) continue; //skip title tag, processed before
            if ( strpos($thisline,"</title")    !==false) continue; //skip title tag, processed before
            if ( strpos($thisline,"<meta")      !==false) continue; //tika generates many of these, valid ones already processed before
            if ( strpos($thisline,"<body")      !==false) continue; //skip body tag, done by code below
            if ( $headadded==false ) {
                $headadded = true;
                fwrite($fptmptika,"<!DOCTYPE HTML>".PHP_EOL);
                fwrite($fptmptika,"<html>".PHP_EOL);
                fwrite($fptmptika,"<head>".PHP_EOL);
                fwrite($fptmptika,"<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />".PHP_EOL);
                fwrite($fptmptika,"<title>".$c_title."</title>".PHP_EOL);
                fwrite($fptmptika,"</head>".PHP_EOL);
                fwrite($fptmptika,"<body>".PHP_EOL);
                $numlinestmp=$numlinestmp+7;
            }
        } else {
            echo "WRONG PARAMETER VALUE:".$pass." for PARAMETER \$pass<br>";die;
        }
        // Write the line to the temp file
        $numlinestmp++;
        fwrite($fptmptika,$thisline.PHP_EOL);
    }
    // Correct for the last empty line
    $numlinestmp++;
    // Close the temporary tikafile and rename it to the actual tikafile
    fclose($fptmptika);
    fclose($fptika);
    rename($tmptikafile,$tikafile);
    tolog($numlinestika." ".$msgstr["dd_lines"]." &rarr; ".$numlinestmp." ".$msgstr["dd_lines"]."</li>");
    return(0);
}
// ====== sanitize_tree =============================
/*
** Sanitizes sub foldername(s) of the given root folder recursively
** In : $rootDir  = actual foldername (not converted)
*/
function sanitize_tree($rootDir){
    global $msgstr;
    /*
    ** The root directory can be present with wrong permissions (direct upload by admin)
    ** Required= read access to list + write access to rename the folder and rename files in the folder
    */
    if (!is_writable($rootDir)) {
        tolog("<div style='font-weight:bold;color:red'>'".$rootDir."' ".$msgstr["dd_nowrite"]."</div>");
        return 1;
    }
    //// run through content of root directory
    $dirContent = scandir($rootDir);
    foreach( $dirContent as $key => $content) {
        $path = $rootDir.'/'.$content;
        if ( $content!="." && $content!=".."&& is_dir($path)) {
            $newcontent=$content;
            $name_encoding="";
            if ( convert_component($newcontent, $name_encoding)!=0) return 1;
            if ( $newcontent!=$content){
                // rename the folder
                tolog($msgstr["dd_movesecfolder"]." '".$content."' &rarr;  '".$newcontent."'<br>");
                $newpath=$rootDir.'/'.$newcontent;
                if (@rename($path, $newpath)===false){
                    $contents_error= error_get_last();
                    tolog("<div style='color:red'><b>".$msgstr["fatal"].": &rarr; </b>".$contents_error["message"]."<br>");
                    tolog("&rarr; ".$msgstr["dd_error_moveto"]."</div>");
                    return 1;
                }
                $newpath=$rootDir.'/'.$newcontent;
                if ( sanitize_tree($newpath)!=0) return 1;
            } else {
                if ( sanitize_tree($path)!=0) return 1;
            }
        }
    }
    return 0;
}
// ====== secondsToTime =============================
/* 
** In : $s  = Seconds
** returns string <hours>:<minutes>:<seconds>
*/
function secondsToTime($s) {
    round($s);
    $h = floor($s / 3600);
    $s -= $h * 3600;
    $m = floor($s / 60);
    $s -= $m * 60;
    return $h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
}
// ====== set_rectypind ============================
function set_rectypind($metatab, &$rectypind){
/*
** Returns the most probable record type
** The mapping of content-types to record types is by configuration file $recConfigFull
** the existence of the file is checked at script startup.
** In case of errors the record type "unknown" is returned
**
** Base for this record type is metatag "content-type". Detected by tika (very good)
** Known content types are specified by http://www.iana.org/assignments/media-types/media-types.xhtml
** Main types: application, audio, font, example, image, message, model, multipart, text, video
**
** In : $metatab   = Table with tika generate meta data
** Out: $rectypind = Name of the record type
** Return : 0=OK, 1=NOT-OK
*/
global $recConfigFull, $msgstr;
    $rectypind = "unknown";
    $c_type="";
    if ( array_key_exists("content-type",$metatab))    {$c_type=$metatab["content-type"];}
    // strip the part after the semicolon (if any)
    $seppos=strpos($c_type, ";");
    if ( $seppos!==false && $seppos>1) $c_type = trim(substr($c_type, 0, $seppos));
    if ($c_type=="") return (0);
    // Read te configuration file
    $content=array();
    $rectypindind=array();
    $fp=fopen($recConfigFull,"r");
    while ( ($line=fgets($fp))!=false){
        $line=rtrim($line); // remove trailing white space(inc cr/lf)
        // Lines with // and lines with # are skipped
        // Lines that cannot contain valid information are skipped
        if ( strlen($line)<3 ) continue;
        if ( stripos($line,'//') !== false ) continue;
        if ( stripos($line, '#') !== false ) continue;
        $linecontent=explode("|",$line);
        $linecontent[0]=trim($linecontent[0]);
        if ($linecontent[0]=="") continue;
        if (!isset($linecontent[1])) continue;
        $linecontent[1]=trim($linecontent[1]);
        if ($linecontent[1]=="") continue;
        array_push($content,$linecontent[0]);
        array_push($rectypindind,$linecontent[1]);
    }
    // search first for an exact match
    $index=array_search($c_type,$content);
    if ( $index!==false) {
        $rectypind=$rectypindind[$index];
        return (0);
    }
    // search with c_type truncated after the /
    $seppos=strpos($c_type, "/");
    if ( $seppos!==false && $seppos>1) $c_type = trim(substr($c_type, 0, $seppos));
    $index=array_search($c_type,$content);;
    if ( $index!==false) {
        $rectypind=$rectypindind[$index];
        return (0);
    }
    return(0);
}
// ====== split_path =============================
function split_path($full_path, &$filename, &$sectionname){
/* 
** IN : $full_path  = Full filename in ImportRepo
** Out: $filename   = The filename (last part of the name)
** Out: $sectionname= The section (optional subdirectories)
** returns always 0 (OK)
*/
    global $coluplfull;
    $path=substr($full_path,strlen($coluplfull)+1);
    $slashpos=strrpos($path,'/',0);
    if ($slashpos==false) {
        $filename=$path;
        $sectionname="";
        return(0);
    } else {
        $sectionname=substr($path,0,$slashpos);
        $filename=substr($path, $slashpos+1 );
    }
    return(0);
}
// ====== split_html =============================
function split_html($tikafile, $textmode, $c_title, $isis_record_size, $splitmax, $splittarget, &$split_files) {
/*
** Splits the given (html) file into smaller parts
** Controlled by the database recordsize.
** In : $tikafile   = Source file name generated by tika
** In : $textmode   = Indicator for tika: m=meta, t=text, h=html, x=xhtml
** In : $c_title    = Title extracted by tika. May be ""
** In : $isis_record_size = maximum size of isisrecord
** In: $splitmax      = Percentage of recordsize as maximum of the split chunk size
** In: $splittarget   = Percentage of splitmax as target of the split chunk size
** Out: $split_files= Array with the names of the resulting files
*/
    global $cisis_ver, $msgstr;
    /*
    ** Before creating the database record the html filesize & database recordsize are shown
    */
    $c_htmlfilesize=filesize($tikafile);
    $pretty_cisis_recsize=number_format($isis_record_size/1024,2,",",".")." Kb";
    $pretty_html_filesize=number_format($c_htmlfilesize/1024,2,",",".")." Kb";
    tolog("<li>".$msgstr["dd_htmlfilesize"]." ".$pretty_html_filesize.". ".$msgstr["dd_recordsize"]." ".$pretty_cisis_recsize."</li>");

    /*
    ** The maximum recordsize cannot be used (for some odd unknown reason)
    ** The reduction value can be influenced by the form (splitmax)
    ** - Gload              errors are shown if the reduction is much too small
    ** - recgizmo/fldupdat  errors are shown with a moderate reduction
    ** - fullinv/ifload     errors are shown if /m is unset and the reduction is still too small
    */
    $maxsize    = $isis_record_size*($splitmax/100); // maximum used recordsize. Is a percentage
    /*
    ** Split html is preferred at a logical boundary
    ** If actual recordsize exceeds the target size the system searches for logical boundary
    */
    $targetsize = $maxsize*(intval($splittarget)/100); //splittarget is a percentage
    if ($targetsize<5000) {// Just in case  a corrupt value is supplied
        tolog("<span style='color:red'>PROGRAM ERROR:variable targetsize=$targetsize : too small to be credible</span>");die;
    }
    /*
    ** If the file is less then the target size a split is not necessary
    */
    if (intval($c_htmlfilesize) < $targetsize) {
        // no split required
        $split_files[]=$tikafile;
        return(0);
    }
    /*
    ** Splitting required. Show initial message
    */
    $pretty_maxsize=number_format($maxsize/1024,2,",",".")." Kb";
    $pretty_targetsize=number_format($targetsize/1024,2,",",".")." Kb";
    tolog("<li>".$msgstr["dd_splitting"]." ".$pretty_targetsize.". ".$msgstr["dd_splitmax"]." ".$pretty_maxsize."</li>");
    /*
    ** Split the file into chunks
    ** Set variables required during the split
    */
    $path_parts  = pathinfo($tikafile);
    $chunkfixfil = $path_parts['dirname'].'/'.$path_parts['filename'].'_'; // filename without chunknr
    $chunkfixext = '.'.$path_parts['extension'];
    $chunknr     = 1;
    $chunkisopen = false;
    $chunkexceed = false;
    $filetrail   = "</body></html>";
    $maxsize     = $maxsize-strlen($filetrail)-2;// absolute working maximum adjusted for trailer+crlf
    
    if(($handle = fopen($tikafile, "r"))===false) {
        return(1);
    }
    while (!feof($handle)) { // main loop over all lines of the source file
        if ( !$chunkexceed    ) $thisline = fgets($handle); // get line contents 
        // Some lines can be skipped
        if ( $thisline==""    ) continue; //skip empty line
        if ( $thisline=="\n"  ) continue; //skip empty line
        if ( $thisline=="\r\n") continue; //skip empty line
        if ( strpos($thisline,"<!DOCTYPE")  !==false) continue; //skip DOCTYPE tag
        if ( strpos($thisline,"<html")      !==false) continue; //skip html tag
        if ( strpos($thisline,"<head")      !==false) continue; //skip head tag
        if ( strpos($thisline,"</head")     !==false) continue; //skip head tag
        if ( strpos($thisline,"<title")     !==false) continue; //skip title tag, processed before
        if ( strpos($thisline,"</title")    !==false) continue; //skip title tag, processed before
        if ( strpos($thisline,"<meta")      !==false) continue; //tika generates many of these, valid ones already processed before
        if ( strpos($thisline,"<body")      !==false) continue; //skip body tag, done by code below
        // Check if a file is open to write to
        if ( $chunkisopen==false ) {
            // open the chunkfile and write a header
            $chunkactfile= $chunkfixfil.$chunknr.$chunkfixext;
            $chunkhandle = fopen($chunkactfile, "w");
            $split_files[]=$chunkactfile;
            $chunkisopen = true;
            $chunksize   = 0;
            $chunksize+=fwrite($chunkhandle,"<!DOCTYPE HTML>".PHP_EOL);
            $chunksize+=fwrite($chunkhandle,"<html>".PHP_EOL);
            $chunksize+=fwrite($chunkhandle,"<head>".PHP_EOL);
            $chunksize+=fwrite($chunkhandle,"<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />".PHP_EOL);
            $chunksize+=fwrite($chunkhandle,"<title>".$c_title." #".$chunknr."</title>".PHP_EOL);
            $chunksize+=fwrite($chunkhandle,"</head>".PHP_EOL);
            $chunksize+=fwrite($chunkhandle,"<body>".PHP_EOL);
            $chunknr++;
        }
        // If the chunksize approaches the limit. Take care if over the safety limit
        if ($chunksize > $targetsize) {
            // Check for exceeding the recordsize
            $thislinelen  = strlen($thisline);
            $chunkexceed  = false;
            if ( ($chunksize + $thislinelen) >= $maxsize){
                $chunkexceed=true;
            }
            // Check for  "</div>" or "</table>" at the end of the line
            $thistestline = trim($thisline,PHP_EOL);
            $divendfound  = false;
            $tabendfound  = false;
            if (strlen($thistestline)>=6){
                $divendfound = stripos($thistestline,"</div>",-6);
            }
            if (strlen($thistestline)>=8){
                $tabendfound = stripos($thistestline,"</table>",-8);
            }
            // Check for "</p>" at the end of the line
            $pendfound=false;
            if ( $chunksize > ($targetsize+$maxsize)/2 AND strlen($thistestline)>=4) {
                $pendfound = stripos($thistestline,"</p>",-4);
            }
            /*
            ** Close the file if there is a good reason
            ** Over recordsize OR textmode=t OR </div>  or </p>  found
            */
            if ($chunkexceed  OR $textmode=='t' OR $divendfound!==false OR $tabendfound!==false OR $pendfound!==false) {
                // write the current line before closing the chunk if there is space left
                if (!$chunkexceed) {
                    $chunksize+=fwrite($chunkhandle,$thisline);
                }
                // Add the filetrailer (space was reserved) and close the chunk
                fwrite($chunkhandle,$filetrail);
                fclose($chunkhandle);
                if (filesize($chunkactfile)>= $isis_record_size){
                    echo "<br><span style='color:blue'>File size=".filesize($chunkactfile)." Isis record=".$isis_record_size."</span>";
                }
                $chunkisopen=false;
                $pretty_filesize=number_format(filesize($chunkactfile)/1024,2,",",".")." Kb";
                tolog("<li>".$msgstr["dd_partnr"]." ".($chunknr-1)." ".$msgstr["dd_size"]." ".$pretty_filesize."</li>");
                continue;
            }
        }
        $chunksize+=fwrite($chunkhandle,$thisline);
        $chunkexceed=false;
    }
    // The end of the input is found. Write a trailer and close the chunk if necessary
    if ( $chunkisopen==true ) {
        fwrite($chunkhandle,$filetrail);
        fclose($chunkhandle);
    }
    $pretty_filesize=number_format(filesize($chunkactfile)/1024,2,",",".")." Kb";
    tolog("<li>".$msgstr["dd_partnr"]." ".($chunknr-1)." ".$msgstr["dd_size"]." ".$pretty_filesize."</li>");
    // Close the input file
    fclose($handle);
    unlink($tikafile); // original no longer needed
    return(0);
}
// ====== tolog =============================
/*
** Writes informational messages to the client and/or a logfile
** In: $message : The message to be logged
** In: $dest    : Sets the destination. A NULL value uses the static status
**      $dest="web" : echo to the webpage
**      $dest="file": write to file. The first call opens the log file
**      $dest="both": writes to web and log
** Note: The logfile name is set as static variable
*/
function tolog($message,$dest=NULL){
    static $actualdest="web";
    static $logfile=NULL;
    global $db_path,$msgstr;
    if ( isset($dest) ) {
        if ( ($dest=="file" or $dest=="both") && $logfile==NULL) {
            // open a log file
            $logname="wrk/import_document_log_".date("ymdHis").".html";
            $logfile=$db_path.$logname;
            $logurl="/docs/".$logname;
            echo "<div style='color:blue'>".$msgstr["dd_logfile"]." &rarr; ";
            echo "<a href=".$logurl." target='_blank' >".$logname."</a></div>";
            touch ($logfile);
            ob_flush();flush();
        }
        if ( $dest=="file" OR $dest=="web" OR $dest=="both") {
            $actualdest=$dest;
        } else {
            echo "Wrong parameter for function tolog:".$dest;
            die;
        }
    }
    if ($actualdest=="web" OR $actualdest=="both") {
        echo $message;
        ob_flush();flush();
    }
    if ($actualdest=="file" OR $actualdest=="both") file_put_contents($logfile,$message,FILE_APPEND);
}
// ======================= End functions/End =====

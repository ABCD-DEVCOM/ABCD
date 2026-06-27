<?php
/*
Script: show_image.php (CENTRAL/common)
Modifications:
	2023-01-04 rogercgui Uncommented line 19 "$filename=strtolower.."Reason: files with the uppercase extension are not working on updated Linux servers.
	2026-02-28 rogercgui Added security checks to prevent path traversal attacks. The script now verifies that the requested file is within the allowed base directory before serving it.
	2026-03-03 fho4abcd Detect not logged in, improve error display, improve variable check, improve logic
	2026-03-08 fho4abcd Add trailing / if omitted in the root, check existence of root
	2026-03-08 fho4abcd Process HTTP_REFERRER to allow OPAC and deny self-typed URL and some injected URL's 
	2026-03-08 fho4abcd Better filename checks, improved comments and messages
	2026-03-24 fho4abcd Clear output buffer before sending image data
	2026-06-27 rogercgui Added permission to space in the filename, to allow for filenames with spaces. This is important for compatibility with Windows systems and user-friendly file naming.

Function:
	Display media in a safe way, controlled by the application.
	For this purpose the media are located outside the DOCUMENT_ROOT tree, and this script
	and a significant number of checks to cope with all kinds of misuse/hacking attempts.
	Some might seem rather far-fetched, but rather safe then sorry.

	Following examples use database "nvbsodr" and field "160" for the media filename.

	The root location of the media can be set in dr_path.def , parameter "ROOT".
	Examples:
ROOT=%path_database%nvbsodr/images
		   The second example allows for a vsftp user to upload media to 'files'
		   The home folder for this user is ftp_nvbsodr and for safety nobody has write access to this folder
ROOT=%path_database%nvbsodr/ftp_nvbsodr/files

Usage in pft:
	This code can be used in Display files (.pft files in ISIS Formatting Language)
	Example 1: Show a link in the Display file to display media.
		   The media content is shown in the current window.
if p(v160) then '<tr><td valign=top><font face=arial size=2><b>File</b></td>
<td valign=top><font face=arial size=2>','<a href="/central/common/show_image.php?image='v160'&base=nvbsodr">'v160'</a></td></tr>' fi/

	Example 2: Show media in the Display file, different for PDF and other files.
		   The media content is shown in a small box next to the textual parameters.
		   In case the field has no content a default picture is shown (no_image_nl.jpg)
'<tr><td valign=top style="text-align:center" width=250px;>'
(if right(v160,4)='.pdf' then  '<a href="/central/common/show_image.php?image='v160'&base=nvbsodr">Show PDF</a><embed src="/central/common/show_image.php?image='v160'&base=nvbsodr" width=350 height=300>'
else if p(v160) then '<a href="/central/common/show_image.php?image='v160'&base=nvbsodr"><embed src="/central/common/show_image.php?image='v160'&base=nvbsodr" width=225></a>' / fi/ /fi/)

(if a(v160) then '<img src=/assets/images/no_image_nl.jpg width=120>' / fi/)
'</td>'
*/
session_start();
include("../common/get_post.php");
include("../config.php");
include ("../lang/admin.php");
include ("../lang/dbadmin.php");
//foreach ($arrHttp as $var=>$value)  echo "$var=$value<br>";
//foreach ($_SERVER as $var=>$value)  echo "$var=$value<br>";// for HTTP_REFERER
//foreach ($_SESSION as $var=>$value)  echo "$var=$value<br>";
//die;
$err_pref="<p style='color:red'>*** ";
$conf_pref="<p style='color:red'>*** Configuration error: ";
/*
** Deny access if HTTP_REFERER is not set
** This is the case when the user types the URL in the browser
** Denying this makes the test for being logged in less important.
*/
if ( !isset($_SERVER["HTTP_REFERER"]) ) {
	echo $err_pref."Direct attempts forbidden. Launch only by ABCD/OPAC";
	die;
}
/*
** Check referer
** It is assumed that this file is launched from a limited number of root URL's
*/
$http_host=$_SERVER["HTTP_HOST"];
$http_referrer=$_SERVER["HTTP_REFERER"];
$refer_is_central=strstr($http_referrer,$http_host."/central/");
$refer_is_opac=strstr($http_referrer,$http_host."/opac/");
if ( $refer_is_central === false && $refer_is_opac === false ) {
	echo $err_pref."Unknown referer: ". $http_referrer."</p>";
	die;
}
/*
** Check that user is logged in
** The check tests the existence of a filled captcha and profile name.
** COMPAC does not login, so this test is skipped for COMPAC
*/
if ($refer_is_opac===false && (!isset($_SESSION["permiso"]) || !isset($_SESSION["profile"]))){
	header("Location: ../common/error_page.php") ;
	die;
}
/*
** Check url parameter "base"
*/
if ( !isset($arrHttp["base"]) ) {
	echo $err_pref.$msgstr["missing"]." URL parameter: 'base'"."<\p>";
	die;
} else {
	$databasename = trim($arrHttp["base"]);
	$databasename = trim($arrHttp["base"],"\"\'");
	if ( $databasename == "" ) {
		echo $err_pref."Invalid URL parameter: 'base'"."</p>";
		die;
	}
	// more sanitation not required (will be checked by valid database name)
}
/*
** Check that this is a valid databasename
*/
$bases_file = $db_path . "/bases.dat";
if (!file_exists($bases_file)) {
	echo $conf_pref.$msgstr["missing"].": $bases_file"."</p>";
	die;
}
$fp=file($bases_file);
$dbfound = false;
foreach ($fp as $value){
	if (trim($value)!=""){
		$b=explode('|',$value);
		if ( $b[0] == $databasename ) $dbfound = true;
	}
}
if ( !$dbfound ) {
	echo $err_pref.$msgstr["dblist"].": ".$msgstr["missing"].": $databasename"."</p>";
	die;
}
/*
** Check url parameter "image"
*/
if ( !isset($arrHttp["image"]) ) {
	echo $err_pref.$msgstr["missing"]." URL parameter: 'image'"."</p>";
	die;
} else {
	// Check for de facto empty value
	$imagefilename = trim($arrHttp["image"]);
	$imagefilename = trim($arrHttp["image"],"\"\'");
	if ( $imagefilename == "" ) {
		echo $err_pref."De facto empty URL parameter: 'image'"."</p>";
		die;
	}
	// Check double dot
	if (strpos($imagefilename, '..') !== false) {
		echo $err_pref."Directory browsing attempt (..) detected."."</p>";
		die;
	}
	// SANITISATION
	// Removes dangerous characters, but ALLOWS spaces ( ), slash (/), dot (.), underscore (_) and minus (-).
	$testimagefilename = preg_replace('/[^a-zA-Z0-9\/._ -]/', '', $imagefilename, -1, $count);
	if ( $imagefilename != $testimagefilename ) {
		echo $err_pref."URL parameter 'image' contains $count not allowed character(s)"."</p>";
		die;
	}
	$imagefile_extension = strtolower(pathinfo($imagefilename,PATHINFO_EXTENSION));
	$imagefile_filename  = pathinfo($imagefilename,PATHINFO_FILENAME);
	if ( $imagefile_extension == "" ||
	     $imagefile_filename  == "" ) {
		echo $err_pref."Invalid URL parameter: 'image'<br>Non-empty filename required. Non-empty extension required"."</p>";
		die;
	}
}
/*
** Check that the extension has a listed content type
*/
$cont_type="";
switch ($imagefile_extension) {
	case "jpg": $cont_type="image/jpeg"; break;
	case "jpeg": $cont_type="image/jpeg"; break;
	case "gif": $cont_type="image/gif"; break;
	case "png": $cont_type="image/png"; break;
	case "txt": $cont_type="text/plain"; break;
	case "html": $cont_type="text/html"; break;
	case "htm": $cont_type="text/html"; break;
	case "doc": $cont_type="application/msword; charset=windows-1252 ";break;
	case "exe": $cont_type="application/octet-stream";break;
	case "pdf": $cont_type="application/pdf;";break;
	case "ai": $cont_type="application/postscript";break;
	case "eps": $cont_type="application/postscript";break;
	case "ps": $cont_type="application/postscript";break;
	case "xls": $cont_type="application/vnd.ms-excel";break;
	case "xlsx": $cont_type="application/vnd.ms-excel";break;
	case "ppt": $cont_type="application/vnd.ms-powerpoint";break;
	case "zip": $cont_type="application/zip";break;
	case "mid": $cont_type="audio/midi";break;
	case "kar": $cont_type="audio/midi";break;
	case "mp3": $cont_type="audio/mpeg";break;
	case "wav": $cont_type="audio/x-wav";break;
	case "bmp": $cont_type="image/bmp";break;
	case "tiff": $cont_type="image/tiff";break;
	case "tif": $cont_type="image/tiff";break;
	case "asc": $cont_type="text/plain";break;
	case "rtf": $cont_type="text/rtf; charset=windows-1252 ";break;
	case "mpeg": $cont_type="video/mpeg";break;
	case "mpg": $cont_type="video/mpeg";break;
	case "mpe": $cont_type="video/mpeg";break;
	case "avi": $cont_type="video/x-msvideo";break;
}
if ( $cont_type == "" ) {
	echo $err_pref."Unknown extension: ".$imagefile_extension."</p>";
	die;
}
/*
** Compute the full path from file dr_path.def
*/
$dr_path_file = $db_path . $databasename . "/dr_path.def";
if (file_exists($dr_path_file)) {
	$def = parse_ini_file($dr_path_file);
	// STRICT VALIDATION: If the file exists, ROOT is mandatory.
	if (!$def || !isset($def["ROOT"]) || trim($def["ROOT"]) == "") {
		die($conf_pref."The ROOT parameter is mandatory and has not been defined in: " . $dr_path_file."</p>");
	}
	$image_path = trim($def["ROOT"]);
	// Replacing the %path_database% variable
	$image_path = str_replace("%path_database%", $db_path, $image_path);
} else {
	// If dr_path.def does not exist, use the default
	// Default is the top folder of the database
	$image_path = $db_path . $databasename;
}
/*
** Add a / to the image path if not present
*/
if ($image_path[strlen($image_path)-1] != "/") {
	$image_path = $image_path . "/";
}
/*
** Check that target path exists
*/
if (!file_exists($image_path)) {
	echo $conf_pref.$msgstr["folderne"].": ".$image_path."</p>";
	die;
}
/*
** Check that the file exists
*/
$full_imagefilename = $image_path . $imagefilename;
if (!file_exists($full_imagefilename)) {
	echo $err_pref.$msgstr["copenfile"].":<br>".$imagefilename."</p>";
	die;
}
/*
** Code to find potential path injections
** Uses php 'realpath'. This function requires that the file exists
*/
$base_dir = realpath($image_path);
$requested_path = $image_path . $imagefilename;
$real_requested_path = realpath($requested_path);
// SECURITY: We verify that the actual path still begins with the base directory.
if ($real_requested_path === false || strpos($real_requested_path, $base_dir) !== 0) {
	die($err_pref."Access denied: Path Traversal attempt detected."."</p>");
}
/*
** ob_end_clean is used to remove possible buffered characters.
** These may result in browser message
** --> The image cannot be displayed because it contains errors.<--
** This precaution is necessary in some (old) installations
*/
ob_end_clean();
/*
** Display the file
*/header("Content-type: $cont_type");
header('Content-Disposition: inline; filename="'.$full_imagefilename.'"');
header("Content-Length: " . filesize($full_imagefilename));
header("Cache-Control: private, max-age=0"); // Prevent public caching
readfile($full_imagefilename);
?>
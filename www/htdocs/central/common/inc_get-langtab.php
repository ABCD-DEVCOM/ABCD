<?php
/* Modifications
20210430 fho4abcd Created
20210520 fho4abcd New location 00 file
20210521 fho4abcd typo
20220119 fho4abcd correct path to absolute default language table
*/
/*--------------------------------------------------------------
** Function  : Select the best available language table file
**             Tries a number of language files.
**             Attempts - language of $SESSION
**                      - language set by program (first set by config.php)
**                      - language of language 00
**             Does NOT use bases/lang.tab.
**             Dies with a message if all attempts fail
** Returns   : The language table filename
** Usage     :    include "../common/inc_get-langtab.php";
**                $langtabfile=get_langtab();
** Globals   : Set by config.php or later modified
**    $msg_path: I   path where the message-files are stored
**    $db_path : I   path where the databases are located
**    $lang    : I   default language
**    $msgstr  : I   array with translation
*/
// 
function get_langtab () {
    global $msg_path, $db_path, $lang, $msgstr;
	if (isset($msg_path)) {
		$path_this=$msg_path;
    } else {
		$path_this=$db_path;
    }
	$langtab_file=$path_this."lang/".$_SESSION["lang"]."/lang.tab";
 	if (!file_exists($langtab_file)) {
        // Try the default language. Note that the homepage can change the default language
 		$langtab_file=$path_this."lang/".$lang."/lang.tab";
 	}
 	if (!file_exists($langtab_file)) {
        // Try absolute default language. 
 		$langtab_file=dirname(__FILE__)."/../lang/en/lang.tab";
 	}
 	if (!file_exists($langtab_file)){
		echo "</select></form></table><div><font color=red>".$msgstr["flang"]." ".$langtab_file."</font></div>";
		die;
	}
    return $langtab_file;
}


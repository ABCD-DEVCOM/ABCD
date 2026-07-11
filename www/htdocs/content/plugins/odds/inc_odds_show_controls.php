<?php
/*
Contains functions for odds request form
Calling sequence:
- read_odds_show_controls
     +- _remove_comments
     +- _process_level
          +- _build_input
               +- _add_source
                    +- _remove_comments

2022-11-19 fho4abcd Created from show-controls.php
*/
/************ _remove_comments */
function _remove_comments($text) {
    $text = preg_replace('!/\*.*?\*/!s', '', $text);    //skip comments
    $text = str_replace("\r", '', $text);               //remove possible \r
    $text = preg_replace('/\n\s*\n/', "\n", $text);     //remove concatenated \n
    return $text;
}
/************ function _process_level */
function _process_level($level,$odds_show_file,$file_contents, $variable_fields,&$optional_inputs){
    global $msgstr,$db_path;
    // split at the abbreviation of the level enclosed in double quotes
    $labelparts = explode("\"$level\"", $file_contents, 2);
    if (count($labelparts)<=1) {
         echo "<div style='color:red'>".$msgstr["archivo"]." ".$odds_show_file." ".$msgstr["odds_nolevelentry"].": \"$level\"";
         return false;
    }
    // Split the string after the abbreviation into lines
    $lines = explode("\n", trim($labelparts[1]));
    $optional_inputs="";
    foreach ($lines as $line) {
        $values = explode("|", $line);//// debug echo "<br>".$line;
        if (count($values) > 1) {
            $values = array_map('trim', $values);
            $optional_inputs .= _build_input($odds_show_file, $line, $values, $variable_fields);
        } else {
            break;
        }
    }    
}
/************ function _build_input */
function _build_input($odds_show_file, $line, array $values, array $variable_fields)
{
    global $msgstr, $db_path;

    if (count($values) < 4 || count($values) > 5) {
        $error_msg = $msgstr["odds_inv_values"] ?? "Invalid values";
        echo "<div style='color:red'>" . ($msgstr["archivo"] ?? "File") . " {$odds_show_file} {$error_msg} &rarr;{$line}&larr;</div>";
        return "";
    }

    $input = "";
    $input_name     = $values[0];
    $input_label    = str_replace('*', '<font color="red">*</font>', $values[1]);
    $input_type     = $values[2] ?? '';
    $input_length   = $values[3] ?? '';
    $input_validate = $values[4] ?? '';

    $input_value = $variable_fields[$input_name] ?? "";

    $validate_entry = "";
    if ($input_validate !== '') {
        $validate_entry = "data-jv='" . trim($input_validate) . "'";
    }

    $referer = $variable_fields['referer'] ?? "";

    if ($input_name === "tag900") {
        $input .= _add_source($values[1], $input_length, $referer, $variable_fields);
    } else {
        // PHP 8.2+ safe alternative to utf8_decode()
        $safe_label = mb_convert_encoding($input_label, 'ISO-8859-1', 'UTF-8');
        $safe_value = mb_convert_encoding($input_value, 'ISO-8859-1', 'UTF-8');

        $input .= "<label class='lbl' for='{$input_name}'>{$safe_label}</label>\n";
        $input .= "<input value='{$safe_value}' type='{$input_type}' id='{$input_name}' name='{$input_name}' size='{$input_length}' maxlength='{$input_length}' {$validate_entry} />\n";
        $input .= "<div class='subtitle_blank'></div>\n";
    }

    return $input;
}

/************ function _add_source */
function _add_source($source_label, $input_length, $referer, array $variable_fields) {
    global $msgstr, $db_path, $lang;
    $odds_source_file_name="source.tab";
    $odds_source_file=$db_path ."odds/def/".$lang."/".$odds_source_file_name;
    if (!file_exists($odds_source_file)) {
        echo "<div>".$msgstr["archivo"]." ".$odds_source_file." ".$msgstr["odds_doesnotexist"]."</div>";
        echo "<div>".$msgstr["odds_try_default"]."</div>";
        $odds_source_file=$db_path ."/odds/def/"."en"."/".$odds_source_file_name;
    }
    if (!file_exists($odds_source_file)) {
        echo "<div style='color:red'> ".$msgstr["archivo"]." ".$odds_source_file." ".$msgstr["odds_doesnotexist"]."</div>";
        return "";
    }
    $file_contents = trim(file_get_contents($odds_source_file));
    $file_contents = _remove_comments($file_contents);

    $a_tmp = explode("\n", $file_contents);
    $k = 0;
    for ($j=0; $j < count($a_tmp); $j++){
        if (trim($a_tmp[$j]) != "") {
            $a[$k] = $a_tmp[$j];
            $k++;
        }
    }
    $source = "<label class='lbl' id='lbl_source'>$source_label</label>\n";
    $aux_last = explode('|',  $a[(count($a)-1)]); 
    $last_value = $aux_last[0];
    
    $input_value = "";
    if ( isset($variable_fields[$source_label])) $input_value=$variable_fields[$source_label];
    $source .= "<select id='select_source' name='tag900' onChange=\"check('".$last_value."')\"> \n";
    if ($referer == 'sa') {
        $selected='SAR';
    } elseif ($referer == 'iah') {
        $selected= 'BAEU';
    } elseif (isset($variable_fields["tag900"])) {
        $selected=$variable_fields["tag900"];
    } else {
        $selected="";
    }
    $i=0;
    foreach ($a as $values) {
        $line = explode("|", $values);
        if (trim($line[0]) == $selected) {
            $source .= "<option selected value=\"".trim($line[0])."\">". trim($line[1])."</option>\n";
        } else {
            $source .= "<option value=\"".trim($line[0])."\">". trim($line[1])."</option>\n";
        }
        $i++;
    }
    $source .= "</select>";
    // Add a text field in case of tag900
    if ($i == count($a)) {
        if (trim($line[0]) == $selected) {
            $tag900_visibility="visible";
        } else {
            $tag900_visibility="hidden";
        }
        $other_value="";
        if (isset($variable_fields["tag900_other"])) $other_value=$variable_fields["tag900_other"];

        $source .="&nbsp;&nbsp;";
        $source .="<input id='tag900_other' name='tag900_other' type='text' size='".$input_length."' maxlength='".$input_length."'";
        $source .=" style='visibility:".$tag900_visibility."'";
        $source .=" value='".$other_value."'>\n<br>";
    }
    return $source;
}


/************ function read_odds_show_controls */
/*
2022-11-19 fho4abcd Created
Function  : Read file $db_path."/odds/def/$lang/odds_show_controls.tab"
    The file is read and controlled for syntax (see example files).
    In case the level is a valid value in the file a set of html input statements is returned
Usage     : <?php include "library/read_odds_show_controls.php ?>
Inputs:
    $lang:   set to the current language
    $level:  bibliografic level. Empty implies:not set
    $variable_fields: array with tag names/tag values
Outputs:
    $optional_inputs:    html statements. Empty in case of errors
Returns:
    True(no errors) / False (errors)
*/

function read_odds_show_controls($lang, $level, $variable_fields, &$optional_inputs){
    global $db_path, $msgstr;
    $retval=false;
    $optional_inputs="";
    $filecontents="";
    $odds_show_file_name="odds_show_controls.tab";
    $odds_show_file=$db_path ."odds/def/".$lang."/".$odds_show_file_name;
    if (!file_exists($odds_show_file)) {
        echo "<div> ".$msgstr["archivo"]." ".$odds_show_file." ".$msgstr["odds_doesnotexist"]."</div>";
        echo "<div>".$msgstr["odds_try_default"]."</div>";
        $odds_show_file=$db_path ."/odds/def/"."en"."/".$odds_show_file_name;
    }
    if (!file_exists($odds_show_file)) {
        echo "<div style='color:red'> ".$msgstr["archivo"]." ".$odds_show_file." ".$msgstr["odds_doesnotexist"]."</div>";
        $retval=true;
    } else if ($level!="") {
        // The filecontents can only be processed if there is a value for level
        $file_contents = trim(file_get_contents($odds_show_file));
        $file_contents = _remove_comments($file_contents);
        if (substr($file_contents,0,1)=="\n") { $file_contents=substr($file_contents,1);}
        $retval=_process_level($level,$odds_show_file,$file_contents,$variable_fields,$optional_inputs);
    }
    return $retval;
}

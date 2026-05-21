<?php

class ConfigHelper {
    /**
     * Checks whether the SUBFIELDS_INLINE parameter exists and is enabled (Y) in the dr_path.def file for the current database.
     */
    public static function isInlineSubfieldsEnabled(): bool {
        global $db_path, $arrHttp;
        
        if (!isset($arrHttp["base"]) || $arrHttp["base"] == "") return false;
        
        $dr_path_file = $db_path . $arrHttp["base"] . "/dr_path.def";
        
        if (file_exists($dr_path_file)) {
            $def = parse_ini_file($dr_path_file);
            if (isset($def["SUBFIELDS_INLINE"]) && strtoupper(trim($def["SUBFIELDS_INLINE"])) == "Y") {
                return true;
            }
        }
        return false;
    }
}
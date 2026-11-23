<?php
/* Modifications
20261122 fho4abcd Created
*/
/*
** Function: set defaults for Copy database/Restore database
** Intended to be controlled by dr_path.def. (not yet implemented)
*/
// Default for copy/target folder
$def_cprest_folder="/".$arrHttp["base"]."/data";
// Default for copied filename
$def_cprest_file=$arrHttp["base"]."_backup";
// Default for presenting folder selection button
// true=present button, false=no button, implies that default cannot be changed
$def_cprest_folder_sel=false;

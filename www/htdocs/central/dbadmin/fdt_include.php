<?php
/*
2021-12-25 rogercgui include input type number
2024-03-27 fho4abcd Update some translations
2024-04-12 fho4abcd Comment/translations
2026-04-19 rogercgui Add support for TAB field type in the FDT and data entry editor
*/
	$rows_title=array();
	$rows_title[0]=$msgstr["fdtrow"];
	$rows_title[1]=$msgstr["type"];
	$rows_title[2]=$msgstr["tag"];
	$rows_title[3]=$msgstr["title"];
	$rows_title[4]="I";
	$rows_title[5]="R";
	$rows_title[6]=$msgstr["subfields"];
	$rows_title[7]=$msgstr["preliteral"];
	$rows_title[8]=$msgstr["inputtype"];
	$rows_title[9]=$msgstr["rows"];
	$rows_title[10]=$msgstr["cols"];
	$rows_title[11]=$msgstr["type"];
	$rows_title[12]=$msgstr["name"];
	$rows_title[13]=$msgstr["prefix"];
	$rows_title[14]=$msgstr["picklist_detail"];
	$rows_title[15]=$msgstr["listas"];
	$rows_title[16]=$msgstr["extractas"];
	$rows_title[17]=$msgstr["valdef"];
	$rows_title[18]=$msgstr["help"];
	$rows_title[19]=$msgstr["url_help"];
	$rows_title[20]=$msgstr["link_fdt"];
	$rows_title[21]=$msgstr["mandatory"];
	$rows_title[22]=$msgstr["field_validation"];
	$rows_title[23]=$msgstr["pattern"];

	$field_type=array();
	$field_type["F"]=$msgstr["ft_f"];		//Field
	$field_type["S"]=$msgstr["ft_sub"];		//Subfield
	$field_type["M"]=$msgstr["ft_m"];		//Fixed Field
	$field_type["M5"]=$msgstr["ft_m5"];		//Date(MARC 005)
	$field_type["LDR"]=$msgstr["ft_ldr"];	//MARC-Leader
	$field_type["IND"]=$msgstr["ft_ind"];	//MARC-Indicator
	$field_type["TAB"] = $msgstr["ft_tab"];	//TAB
	$field_type["T"]=$msgstr["ft_t"];		//Group
	$field_type["L"]=$msgstr["ft_l"];		//Line
	$field_type["H"]=$msgstr["ft_h"];		//Heading

	$input_type=array();
	$input_type["AI"]=$msgstr["ft_ai"];
	$input_type["X"]=$msgstr["it_x"];
	$input_type["XF"]=$msgstr["it_xf"];
	$input_type["TB"]=$msgstr["it_tb"];
	$input_type["P"]=$msgstr["it_p"];
	$input_type["D"]=$msgstr["it_d"];
	$input_type["ISO"]=$msgstr["it_iso"];
	$input_type["S"]=$msgstr["it_s"];
	$input_type["SRO"]=$msgstr["it_sro"];
	$input_type["M"]=$msgstr["it_m"];
	$input_type["MRO"]=$msgstr["it_mro"];
	$input_type["N"]=$msgstr["it_n"];
	$input_type["C"]=$msgstr["it_c"];
	$input_type["R"]=$msgstr["it_r"];
	$input_type["A"]=$msgstr["it_a"];
	$input_type["B"]=$msgstr["it_b"];
	$input_type["U"]=$msgstr["it_u"];
	$input_type["RO"]=$msgstr["it_ro"];
	$input_type["I"]=$msgstr["it_i"];
	$input_type["OC"]=$msgstr["ft_oper"];
	$input_type["DC"]=$msgstr["ft_date"];
	$input_type["OD"]=$msgstr["ft_od"];
	$input_type["H"]=$msgstr["it_h"];
	$input_type["PR"]=$msgstr["ft_protect"];
	$input_type["RP"]=$msgstr["ft_protect_record"];
	$input_type["COMBO"]=$msgstr["it_combo"];
	$input_type["COMBORO"]=$msgstr["it_comboro"];

	asort($input_type);

	$pick_type["D"]=$msgstr["plt_d"];	//DB
	$pick_type["T"]=$msgstr["plt_t"];	//Thesaurus
	if (isset($arrHttp["Opcion"]))
		if ($arrHttp["Opcion"]!="new") $pick_type["P"]=$msgstr["plt_p"];	// Table

	$validation["X"]=$msgstr["validation_X"];
	$validation["A"]=$msgstr["validation_A"];
	$validation["I"]=$msgstr["validation_I"];
	$validation["C"]=$msgstr["validation_C"];
	$validation["D"]=$msgstr["validation_D"];
	$validation["T"]=$msgstr["validation_T"];
	$validation["P"]=$msgstr["validation_P"];
	$validation["F"]=$msgstr["validation_F"];
	$validation["U"]=$msgstr["validation_U"];
	asort($validation);
?>
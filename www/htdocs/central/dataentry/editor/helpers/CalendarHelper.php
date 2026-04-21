<?php

class CalendarHelper
{
    /**
     * Renders the HTML for the calendar input.
     * * @param string $field Current value of the field.
     * @param string $type_de Type (D or ISO).
     * @param string $iso_tag ISO tag for conversion (if applicable).
     * @param string $Etq Field label/name in the form.
     * @return void
     */
    public static function render(string $campo, string $type_de, string $iso_tag, string $Etq): void
    {
        global $config_date_format;
        $date_format = ConvertDateSpec($config_date_format); // format of the input field

        echo "<input tabindex='0' type=text size=10 name=tag$Etq id=tag$Etq value='";

        if (trim($campo) != "") echo $campo;
        echo "'";

        if ($type_de == "D") {
            if ($iso_tag != "")
                echo " onChange='Javascript:DateToIso(this.value,document.forma1.tag$iso_tag)'";
        }
        if ($type_de == "ISO")
            echo " onChange='Javascript:DateToIso(this.value,document.forma1.tag$Etq)'";

        echo "><a class=\"bt-fdt\"  href='javascript:CalendarSetup(\"tag$Etq\",\"$date_format\",\"f_tag$Etq\", \"\",true )'><i class=\"far fa-calendar-alt\" id=\"f_tag$Etq\" title=\"Date selector\"
			align=top></i></a>";
    }
}

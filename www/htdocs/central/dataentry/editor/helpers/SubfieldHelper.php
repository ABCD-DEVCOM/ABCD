<?php

class SubfieldHelper
{
    /**
     * Extracts the value of a specific subfield from a structured ABCD field.
     * * @param string $field The full value of the ABCD field.
     * @param string $ksc The code of the subfield to extract (e.g. “a”, “b”).
     * @return string
     */
    public static function extract(string $campo, string $ksc): string
    {
        $ixpos = strpos($campo, '^' . $ksc);
        if ($ixpos === false) {
            return "";
        } else {
            $campo = substr($campo, $ixpos + 2);
            $ixpos = strpos($campo, '^');
            if ($ixpos === false) {
                // Never mind, it’s the last subfield
            } else {
                $campo = substr($campo, 0, $ixpos);
            }
        }
        return $campo;
    }

    public static function decode(string $campo, $numsubc, string $subc, string $delimsc): string
    {
        $salida = "";
        if (trim($delimsc) == "") return $salida; // Retains the original, rigorous behaviour

        $valores = explode("\n", $campo);
        foreach ($valores as $lin) {
            for ($isc = 0; $isc < strlen($subc); $isc++) {
                $delim = substr($subc, $isc, 1);
                $pos = strpos($lin, "^" . $delim);
                if (is_integer($pos)) {
                    if ($isc == 0)
                        $delim = "";
                    else
                        $delim = substr($delimsc, $isc, 1) . " ";
                    $lin = substr($lin, 0, $pos) . $delim . substr($lin, $pos + 2);
                }
            }
            $salida = $salida . "\n" . $lin;
        }
        return $salida;
    }
}


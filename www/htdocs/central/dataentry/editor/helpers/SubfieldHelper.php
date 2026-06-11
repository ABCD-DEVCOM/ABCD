<?php

/**
 * Name: SubfieldHelper.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * Description: Helper functions for handling subfields in structured ABCD fields.
 * 
 * Change Log:
 * - 2026-04-19: Initial creation of the SubfieldHelper class with methods for extracting subfield values and decoding structured fields.
 * - 2026-05-01: Added comments and documentation for the methods.
 * - 2026-06-10: Refactored the extract method to handle implicit subfields more accurately.
 * - 2026-06-15: Added the decode method to convert structured fields into a more readable format based on specified subfield delimiters.
 */

class SubfieldHelper
{
    /**
     * Extracts the value of a specific subfield from a structured ABCD field.
     * * @param string $field The full value of the ABCD field.
     * @param string $ksc The code of the subfield to extract (e.g. "a", "b").
     * @return string
     */
    public static function extract(string $campo, string $ksc): string
    {
        if ($ksc === "_") {
            if (substr($campo, 0, 1) === '^') {
                return ""; // There is no implicit data; it starts with explicit data
            }
            $ixpos = strpos($campo, '^');
            if ($ixpos === false) {
                return $campo; // There are no circumflexes; the entire field is the default
            } else {
                return substr($campo, 0, $ixpos); // Returns everything up to the first ^
            }
        }

        // Default logic for the other subfields (a, t, u, etc.)
        $ixpos = strpos($campo, '^' . $ksc);
        if ($ixpos === false) {
            return "";
        } else {
            $campo = substr($campo, $ixpos + 2);
            $ixpos = strpos($campo, '^');
            if ($ixpos === false) {
                // Last subfield
            } else {
                $campo = substr($campo, 0, $ixpos);
            }
        }
        return $campo;
    }

    public static function decode(string $campo, $numsubc, string $subc, string $delimsc): string
    {
        $salida = "";
        if (trim($delimsc) == "") return $salida;

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


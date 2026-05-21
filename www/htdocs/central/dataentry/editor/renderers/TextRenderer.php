<?php
/**
 * Name: TextRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * 
 * Description: Renderer for text fields in the ABCD data entry editor.
 * This class generates the HTML for rendering text input fields based on the field configuration and options provided. It supports various input types, including single-line text, multi-line textareas, password fields, and auto-increment fields, with appropriate handling for each type.
 */


class TextRenderer {
    public static function render($linea, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $rep, $delimrep, $ayuda): void {
        global $ixicampo, $valortag, $arrHttp, $Path, $Marc, $db_path, $lang_db, $msgstr, $MD5, $SECURE_PASSWORD_LEVEL, $SECURE_PASSWORD_LENGTH;
        
        $maxlength = 0;
        $linea .= "|||||||||";
        $t = explode('|', $linea);
        switch ($t[0]) {
            case "OD":
                $t[0] = "F";
                $t[7] = "OD";
                break;
            case "OC":
                $t[0] = "F";
                $t[7] = "OC";
                break;
            case "ISO":
                $t[0] = "F";
                $t[7] = "ISO";
                break;
            case "DC":
                $t[7] = "DC";
                break;
        }
        $tipo = rtrim($t[7]);
        if ($t[0] == "AI") {
            $tipo = "AI";
            if ($arrHttp["Mfn"] == "New")
                $valortag[$tag] = "";
        }
        $rep = $t[4];
        $subc = rtrim($t[5]);
        $ksc = strlen($subc);
        $delimsc = rtrim($t[6]);
        $mandatory = trim($t[19]);
        $numl = $t[8];
        $cols = $t[9];
        if ($cols == 0 || $cols == "") $cols = 100;
        $len = $cols;
        $tag = $t[1];
        $pref = $t[12];
        $help = "";
        if (isset($t[16])) $help = $t[16];
        if ($tipo != "I") {
            echo "<td class=\"table-fdt-three\">";
            $titulo = trim($t[2]);
            echo $titulo;
            echo "</td>";
            echo "<td class='table-fdt-four input-fdt'>";
        }
        if ($rep == 1 and $numl == 0) $numl = 1;
        if ($numl == 0) $numl = 1;
        $valortag[$tag] = rtrim($valortag[$tag]);
        $dummy = explode("\n", $valortag[$tag]);
        $occurs = count($dummy);
        if ($ver) {
            foreach ($dummy as $lin) {
                if ($ksc > 0 and trim($delimsc) != "") $lin = DecodificaSubCampos($lin, $ksc, $subc, $delimsc);
                if ($tipo != "I") echo $lin . "";
            }
        } else {
            $campo = rtrim($valortag[$tag]);
            if ($numl < count($dummy)) $numl = count($dummy);
            if ($numl > 30) {
                $numl = 30;
            }
            $arrow = "";
            if ($rep == "1") {
            }
            if ($tipo == "XF") $len = $cols . " maxlength=$cols";

            // --- START OF UPLOAD INTERCEPTION ---
            if ($tipo == "U") {
                UploadRenderer::renderInput($tag, $campo, $numl, $cols, "", $maxlength);
            } else {
                if (($numl > 1 or $rep == "1") and $tipo != "AI") {
                if ($len == 0) $len = "100%";
                if ($tipo == "RO" or $tipo == "SRO" or $tipo == "MRO")
                    $it = "text\" onfocus=blur()";
                else
                    $it = "";
                $maxlength = 0;
                if ($t[9] == 0 || $t[9] == "") {
                    $t[9] = 100;
                }
                if ($t[9] != "") {
                    $lenf = explode('/', $t[9]);
                    $len = $lenf[0];
                    if (isset($lenf[1]))
                        $maxlength = $lenf[1];
                }
        ?>
                <textarea rows="<?php echo $numl; ?>" cols="<?php echo $len; ?>" name="tag<?php echo $tag; ?>"
                    id="tag<?php echo $tag; ?>" <?php echo $arrow; ?> <?php echo $it; ?>
                    <?php
                    if (isset($_REQUEST[$campo])) {
                        $campo = $_REQUEST[$campo];
                    } else {
                        $campo = $campo;
                    }
                    if ($maxlength > 0) {
                        echo " onKeyDown=\"textCounter(document.forma1.tag" . $tag . ", document.forma1.rem$tag, $maxlength)\"
                   onKeyUp=\"textCounter(document.forma1.tag" . $tag . ", document.forma1.rem$tag, $maxlength)\"";
                    }
                    echo ' onKeyUp="CheckInventory()" >' . $campo . "</textarea>";
                    if ($maxlength > 0)
                        echo "<input aaa readonly type=\"text\" name=\"rem$tag\" size=\"3\" maxlength=\"$maxlength\" value=\"$maxlength\" class=charCount>" . $msgstr["avalchars"] . "\n";
                } else {
                    $onfocus = "";
                    if ($len == 0 || $len == "") $len = "100";
                    switch ($tipo) {
                        case "P":
                        case "PR":
                            $it = "password\"";
                            if ((isset($SECURE_PASSWORD_LEVEL) and $SECURE_PASSWORD_LEVEL != "") or
                                (isset($SECURE_PASSWORD_LENGTH) and $SECURE_PASSWORD_LENGTH != "")
                            )
                                $it .= " onblur=\"" . "pwd_Validation('tag$tag')"; 
                            $len = 20;
                            if ($MD5 == 1) $campo = "";
                            break;
                        case "AI":
                            echo "<input type=hidden name=autoincrement value=$tag>";
                            $it = "text";
                            $onfocus = "onfocus=blur()";
                            break;
                        case "RO":
                        case "SRO":
                            $it = "text";
                            $onfocus = "onfocus=blur()";
                            break;
                        case "N":
                            $it = "text";
                            $onfocus = "onfocus=blur()";
                            break;
                        case "I":
                            $it = " hidden";
                            break;
                        default:
                            $maxlength = 0;
                            if ($t[9] != "") {
                                $lenf = explode('/', $t[9]);
                                $len = $lenf[0];
                                if (isset($lenf[1]))
                                    $maxlength = $lenf[1];
                            }
                            $it = "text";
                            break;
                    }
                    if ($maxlength != 0)
                        echo "<a style=\"text-decoration:none\" onMouseover=\"ddrivetip(document.forma1.tag" . $tag . ".value,'linen',300 )\"; onMouseout=\"hideddrivetip()\"; onclick=\"hideddrivetip()\">";
                    if ($tipo != "AI") {
                        echo '<input type="' . $it . '" ' . $onfocus . ' name=tag' . $tag . ' id="tag' . $tag . '" size="' . $len . '"';
                        if ($maxlength > 0) {
                            echo ' maxlength="' . $maxlength . '" "';
                        }
                        echo ' value="' . $campo . '" ' . $arrow . '>';
                    }
                    if ($maxlength != 0)
                        echo "</a>";
                    if ($tipo == "AI") {
                        $archivo = $db_path . $arrHttp["base"] . "/data/control_number.cn";
                        if (!file_exists($archivo)) {
                            $fp = fopen($archivo, "w");
                            $res = fwrite($fp, "");
                            fclose($fp);
                        } else {
                            $fp = file($archivo);
                            $last_cn = implode("", $fp) + 1;
                        }
                        echo '<input type="' . $it . '" ' . $onfocus . ' name=tag' . $tag . ' id="tag' . $tag . '" size="' . $len . '"';
                        if ($maxlength > 0) {
                            echo ' maxlength="' . $maxlength . '" "';
                        }
                        echo ' placeholder="' . $last_cn . '" value="' . $campo . '" ' . $arrow . '>';
                        if (
                            isset($_SESSION["permiso"]["CENTRAL_RESETLCN"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"])  or
                            isset($_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_ALL"]) or
                            isset($_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_RESETLCN"]) or
                            isset($_SESSION["permiso"]["ACQ_ALL"]) or
                            isset($_SESSION["permiso"]["ACQ_RESETCN"])
                        ) {
                            echo "\n <a class='bt-fdt-green' href='javascript:ChangeSeq($tag,\"$pref\")'><i class=\"fas fa-plus\"></i> " . $msgstr["assign"] . "</a>   ";
                            echo "\n<a class='bt-fdt-help' href='../documentacion/ayuda.php?help=" . $_SESSION["lang"] . "/autoincrement.html' target=_blank><i class=\"far fa-life-ring\"></i> " . $msgstr["help"] . "</a>   ";
                            if (isset($_SESSION["permiso"]["CENTRAL_EDHLPSYS"])) {
                                echo "\n<a class='bt-fdt-blue' href='../documentacion/edit.php?archivo=" . $_SESSION["lang"] . "/autoincrement.html' target=_blank><i class=\"far fa-edit\"></i> " . $msgstr["edhlp"] . "</a>";
                            }
                        }
                    }
                } 
            }

            if ($tipo == "P" or $tipo == "PR") {
                    ?>
                    <script>tag_password='tag<?php echo $tag; ?>'
            mandatory_password='<?php echo $mandatory; ?>'
            </script>
            <a class="bt-fdt w-2" href="javascript:DisplayPassword('tag<?php echo $tag ?>')">
            <i class="far fa-eye"></i> <?php echo $msgstr["ver"] ?></a>
            <?php
                    if ((isset($SECURE_PASSWORD_LEVEL) and $SECURE_PASSWORD_LEVEL != "") or
                        (isset($SECURE_PASSWORD_LENGTH) and $SECURE_PASSWORD_LENGTH != "")
                    ) {
            ?>
            <br><small class="bt-disabled">
            <?php
                        if (isset($SECURE_PASSWORD_LENGTH) and $SECURE_PASSWORD_LENGTH != "")
                            echo $msgstr["pass_format_1"] . " " . $SECURE_PASSWORD_LENGTH . " " . $msgstr["characters"] . ". ";
                        if (isset($SECURE_PASSWORD_LEVEL) and $SECURE_PASSWORD_LEVEL != "" and $SECURE_PASSWORD_LEVEL > 1)
                            echo $msgstr["pass_format_" . $SECURE_PASSWORD_LEVEL];
            ?>
            <span id="spnPwd" class="pwd_Strength"></span>
            </small><br>
            <?php
                    }
            ?>
            </td>
            <tr><td colspan=2></td>
            <td class='table-fdt-three'><?php echo $msgstr["confirmpass"]; ?></td>
            <td><input tabindex='0' type=password size=<?php echo $len ?> name=confirm id=confirmpwd  value="<?php echo $campo ?>"
            <?php
                    if ((isset($SECURE_PASSWORD_LEVEL) and $SECURE_PASSWORD_LEVEL != "")  or
                        (isset($SECURE_PASSWORD_LENGTH) and $SECURE_PASSWORD_LENGTH != "")
                    ) {
                        echo " onfocus=\"VerificarPassword('tag$tag')\"";
                    }
            ?>
            >
            <a class="bt-fdt w-2" href="javascript:DisplayPassword('confirmpwd')">
            <i class="far fa-eye"></i> <?php echo $msgstr["ver"]; ?></a>
            <?php
                }
            }
            if ($tipo == "SRO" or $tipo == "MRO") {
                echo "<a href=\"javascript:Limpiar(document.forma1.tag$tag)\">borrar</a>";
            }
            if ($tipo != "I") echo "</td></tr>\n";
        }
    }
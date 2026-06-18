<?php
/* Modifications
20210430 fho4abcd Removed duplicate header setting.Lineends
20211206 fho4abcd Full rewrite, include ifpro.php (with workaround from eds for truncated utf-8 last character) and ifepil.php
20211207 fho4abcd Improved buttons + comment "broken multi-byte end-character (semi-)solution" + html errors + remove <table>' + add working message
20211208 fho4abcd First depends on quick search. Search button not for quicksearch
20211212 fho4abcd Buttons like alfa.php:new Up button, goto field moved down, added enter button,shorter text in buttons, added hover text+removed footer
20211222 fho4abcd Correct search after redisplay for next list
20220116 fho4abcd ifp.xis-> ifp_slashm. other "enter" button
20220711 fho4abcd Use $actparfolder as location for .par files
20240402 fho4abcd close button+ message if nothing selected
20251008 fho4abcd removed ob_flush
*/
// Show the dictionary of terms in the database

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
//foreach ($arrHttp as $var => $value)     echo "$var = $value<br>";
include("../config.php");
include("../lang/admin.php");
include("../lang/dbadmin.php");
include("leerregistroisispft.php");

// ------------------------------------------------------
// INICIO DEL PROGRAMA
// ------------------------------------------------------

include("../common/header.php");
$prefijo = $arrHttp["prefijo"];

?>

<body>
    <script>
        function EjecutarBusqueda(desde) {
            // Função auxiliar para converter caracteres especiais em entidades seguras
            // Impede a quebra da URL e do array $_REQUEST no PHP
            function encodeToEntity(str) {
                return str.replace(/[^\x00-\x7F]/g, function(c) {
                    return '&#' + c.charCodeAt(0) + ';';
                });
            }

            Opcion = "<?php echo $arrHttp["Opcion"] ?>"
            switch (desde) {
                case 1:
                    /* buscar los terminos de este diccionario*/
                    TerminosSeleccionados = ""
                    document.forma1.Opcion.value = "buscar_en_este"
                    for (i = 0; i < document.forma1.terminos.length; i++) {
                        if (document.forma1.terminos.options[i].selected == true) {

                            Termino = document.forma1.terminos.options[i].value;

                            // 1. Mantém a solução para caracteres UTF-8 cortados (65533)
                            var index = Termino.indexOf(String.fromCharCode("65533"));
                            if (index >= 0) {
                                Termino = Termino.substring(0, index - 1);
                                Termino += "$";
                            }

                            // 2. CONVERSÃO CRÍTICA: Transforma "???????" em entidades "&#1041;..."
                            // Isso garante que a string viaje como ASCII puro e não quebre o PHP
                            Termino = encodeToEntity(Termino);

                            document.forma1.terminos.options[i].value = Termino;
                            Termino = "\"" + Termino + "\"";

                            if (TerminosSeleccionados == "") {
                                TerminosSeleccionados = Termino
                            } else {
                                TerminosSeleccionados = TerminosSeleccionados + " or " + Termino
                            }
                        }
                    }

                    if (TerminosSeleccionados == "") {
                        alert(<?php echo '"' . $msgstr["src_noselect"] . '"' ?>)
                        break
                    }

                    // Atribui a expressão convertida ao campo do formulário
                    document.forma1.Expresion.value = TerminosSeleccionados;

                    // Configurações de segurança para o envio
                    document.forma1.acceptCharset = "UTF-8";
                    document.forma1.enctype = "application/x-www-form-urlencoded";
                    document.forma1.action = "buscar.php"

                    <?php
                    if (isset($arrHttp["Tabla"])) {
                        if ($arrHttp["Tabla"] == "cGlobal")
                            echo "document.forma1.action=\"c_global.php\"\n";
                        if ($arrHttp["Tabla"] == "imprimir")
                            echo "document.forma1.action=\"imprimir.php\"\n";
                    }
                    ?>

                    document.forma1.submit()

                    <?php
                    if (isset($arrHttp["prestamo"]) or isset($arrHttp["Target"])) {
                        echo " self.close()\n";
                    }
                    ?>
                    break
                case 2:
                    /* Pasar los términos seleccionados */
                    TerminosSeleccionados = ""
                    for (i = 0; i < document.forma1.terminos.length; i++) {
                        if (document.forma1.terminos.options[i].selected == true) {
                            Termino = document.forma1.terminos.options[i].value
                            len_t = <?php echo strlen($prefijo) . "\n" ?>
                            Termino = Termino.substr(len_t)
                            Termino = encodeToEntity(Termino);
                            Termino = "\"" + Termino + "\""
                            if (TerminosSeleccionados == "") {
                                TerminosSeleccionados = Termino
                            } else {
                                TerminosSeleccionados = TerminosSeleccionados + " or " + Termino
                            }
                        }
                    }
                    <?php
                    if (!isset($arrHttp["toolbar"])) {
                    ?>
                        a = window.opener.document.forma1.expre[document.forma1.Diccio.value].value
                        if (a == "") {
                            window.opener.document.forma1.expre[document.forma1.Diccio.value].value = TerminosSeleccionados
                        } else {
                            window.opener.document.forma1.expre[document.forma1.Diccio.value].value = a + " or " + TerminosSeleccionados
                        }
                    <?php } else { ?>
                        Prefijo = "<?php echo $prefijo ?>"
                        TerminosSeleccionados = TerminosSeleccionados.replace(/"/g, "")
                        window.opener.document.forma1.busqueda_palabras.value = TerminosSeleccionados

                        // AJUSTE PARA TOOLBAR: Forçar codificação no envio da busca
                        if (window.opener.Buscar) window.opener.Buscar(Prefijo);
                    <?php } ?>
                    self.close()
                    break
                case 4:
                    /* Más términos */
                    document.forma1.Opcion.value = "mas_terminos"
                    document.forma1.submit()
                    break
                case 3:
                    /* Continuar */
                    document.forma1.Opcion.value = "mas_terminos"
                    document.forma1.LastKey.value = document.forma1.prefijo.value + document.forma1.IR_A.value
                    document.forma1.submit()
                    break
            }
        }

        function RemoveSpan(id) {
            var workingspan = document.getElementById(id);
            workingspan.remove();
        }
    </script>
    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr["indicede"] . ": " . urldecode($arrHttp["campo"]) ?>
        </div>
        <div class="actions">
            <?php
            $smallbutton = true;
            include "../common/inc_close.php";
            ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>
    <?php
    include "../common/inc_div-helper.php";
    ?>
    <div class="middle form">
        <div class="formContent">

            <FORM METHOD="Post" name=forma1 action=diccionario.php onSubmit="Javascript:return false">
                <input type=hidden name=Opcion value='<?php echo $arrHttp["Opcion"] ?>'>
                <input type=hidden name=session_id value='<?php if (isset($arrHttp["session_id"])) echo $arrHttp["session_id"] ?>'>
                <input type=hidden name=base value='<?php echo $arrHttp["base"] ?>'>
                <input type=hidden name=cipar value='<?php echo $arrHttp["cipar"] ?>'>
                <input type=hidden name=Expresion value=''>
                <input type=hidden name=prefijo value='<?php echo $prefijo ?>'>
                <input type=hidden name=Diccio value='<?php if (isset($arrHttp["Diccio"])) echo $arrHttp["Diccio"] ?>'>
                <input type=hidden name=desde value='<?php if (isset($arrHttp["desde"])) echo $arrHttp["desde"] ?>'>
                <input type=hidden name=toolbar value='<?php if (isset($arrHttp["toolbar"])) echo $arrHttp["toolbar"] ?>'>
                <input type=hidden name=campo value="<?php echo urldecode($arrHttp["campo"]) ?>">
                <?php
                if (isset($arrHttp["prestamo"])) {
                    echo "<input type=hidden name=prestamo value=" . $arrHttp["prestamo"] . ">";
                }
                if (isset($arrHttp["Tabla"])) {
                    echo "<input type=hidden name=Tabla value=" . $arrHttp["Tabla"] . ">";
                }
                if (isset($arrHttp["Target"])) {
                    echo "<input type=hidden name=Target value=" . $arrHttp["Target"] . ">";
                }
                $showsend = true;
                if (isset($arrHttp["toolbar"])) $showsend = false;
                $showsearch = true;
                if (isset($arrHttp["Target"])) $showsearch = false; // Si existe $arrHttp["Target"] no se realiza la búsqueda directamente
                if (isset($arrHttp["toolbar"])) $showsearch = false;
                if ($showsend or $showsearch) {
                ?>
                    <span style="color:blue"><i class="fas fa-info-circle"></i> <?php echo $msgstr['selmultiple'] ?></span><br>
                <?php
                } else {
                ?>
                    <span style="color:blue"><i class="fas fa-info-circle"></i> <?php echo $msgstr['src_selterm'] ?></span><br>
                <?php
                }
                ?>
                <div>
                    <span id="working" style="color:red"><b>.... <?php echo $msgstr["src_system_working"] ?> ....</b></span>
                    <?php flush(); ?>
                    <select name=terminos size=13 multiple
                        <?php
                        // When the function is initiated from the toolbar a hit will search immediately
                        if (isset($arrHttp["toolbar"])) {
                            echo 'onclick="EjecutarBusqueda(1)"';
                        }
                        echo ">";
                        PresentarDiccionario();
                        ?>
                        </select>
                        <script>
                            RemoveSpan("working");
                        </script>
                        <INPUT TYPE=HIDDEN VALUE="<?php echo $arrHttp["LastKey"] ?>" NAME="LastKey">
                </div>
                <div>
                    <a href="javascript:EjecutarBusqueda(3)" class="bt bt-gray" title='<?php echo $msgstr["src_top"] ?>'>
                        <i class="fas fa-chevron-circle-up"></i></a>
                    <a href="javascript:EjecutarBusqueda(4)" class="bt bt-gray" title='<?php echo $msgstr["masterms"] ?>'>
                        <i class="fas fa-chevron-circle-down"></i></a>
                    &nbsp;
                    <?php echo $msgstr["avanzara"] ?>
                    <input type=text name="IR_A" size=5 title='<?php echo $msgstr["src_advance"]; ?>'>
                    <a href="javascript:EjecutarBusqueda(3)" class="bt bt-gray" title='<?php echo $msgstr["src_enter"] ?>'>
                        <i class="fas as fa-angle-right"></i></a>
                    <input type="submit" style="display:none;" onclick="EjecutarBusqueda(3)" />
                    <?php
                    if ($showsend) {
                    ?>
                        <a href="javascript:EjecutarBusqueda(2)" class="bt bt-blue" title=' <?php echo $msgstr["src_send"] ?>'>
                            <i class="fas fa-share-square"></i> <?php echo $msgstr["src_sendto"] ?></a>
                    <?php
                    }
                    if ($showsearch) {
                    ?>
                        <a href="javascript:EjecutarBusqueda(1)" class="bt bt-green">
                            <i class="fas fa-search"></i> <?php echo $msgstr["buscar"] ?></a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
<?php
// ======================================================
// This the end of main script. Only functions follow now
//
// ====== PresentarDiccionario ==========================
// Para presentar el diccionario de términos
// To present the dictionary of terms

function PresentarDiccionario()
{
    global $arrHttp, $terBd, $xWxis, $db_path, $Wxis, $wxisUrl, $cisis_ver, $prefijo, $actparfolder;

    if ($arrHttp["Opcion"] == "ir_a") {
        $arrHttp["LastKey"] = $prefijo . $arrHttp["IrA"];
    }
    $arrHttp["Opcion"] = "diccionario";
    if (isset($arrHttp["LastKey"]))
        $LastKey = $arrHttp["LastKey"];
    else
        $LastKey = "";
    $IsisScript = $xWxis . "ifp_slashm.xis";
    $query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"] . "&Opcion=" . $arrHttp["Opcion"] . "&prefijo=" . $prefijo . "&LastKey=" . $LastKey;
    $contenido = array();
    include("../common/wxis_llamar.php");
    $mayorclave = "";
    foreach ($contenido as $linea) {
        $pre = trim(substr($linea, 0, strlen($prefijo)));
        if ($pre == $prefijo) {
            $l = explode('|', $linea);
            $ter = substr($l[0], strlen($prefijo));
            $ter = trim($ter);
            $ttll = trim($l[0]);
            echo "<option value=\"" . $ttll . "\">" . $ter;
            if (isset($l[1])) echo " (" . trim($l[1]) . ")";
            echo "</option>\n";
            $mayorclave = $l[0];
        }
    }
    $arrHttp["LastKey"] = $mayorclave;
}
?>
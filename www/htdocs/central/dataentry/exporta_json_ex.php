<?php

/**
 * Name: exporta_json_ex.php
 * Author: Roger C. Guilherme
 * Description: Executes WXIS CLI to dump XML and uses XMLReader stream to generate Pretty JSON
 * 
 * Created on: 2026-009-15
 */

global $arrHttp;
set_time_limit(0);
ini_set('memory_limit', '512M');

session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"];

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");

$backtoscript = "../dataentry/administrar.php";
$inframe = 1;
if (isset($arrHttp["backtoscript"])) $backtoscript = $arrHttp["backtoscript"];
if (isset($arrHttp["inframe"]))      $inframe = $arrHttp["inframe"];
if (!isset($arrHttp["tipo"]))        $arrHttp["tipo"] = "json";
if (!isset($arrHttp["archivo"]))     $arrHttp["archivo"] = "export";
if (!isset($arrHttp["storein"]))     $arrHttp["storein"] = "/wrk";
if (!isset($arrHttp["format"]))      $arrHttp["format"] = "native";

$fileinfo = pathinfo($arrHttp["archivo"]);
if (!isset($fileinfo['extension']) || $fileinfo['extension'] != $arrHttp["tipo"]) {
    $arrHttp["archivo"] = $fileinfo['filename'] . "." . $arrHttp["tipo"];
}
$filename = $arrHttp["archivo"];

include("../common/inc_file-delete.php");

function Confirmar()
{
    global $msgstr;
?>
    <br><br>
    <input class="bt bt-green" type=button name=continuar value="<?php echo $msgstr["continuar"] ?? ''; ?>" onclick=Confirmar()>
    &nbsp; &nbsp;<input class="bt bt-red" type=button name=cancelar value="<?php echo $msgstr["cancelar"] ?? ''; ?>" onclick=Regresar()>
    </div>
    </div>
    </body>

    </html>
    <?php
}

// Strip HTTP headers injected by WXIS CGI execution in a memory-safe stream
function StripWxisHeaders(string $filePath): bool
{
    if (!file_exists($filePath)) return false;

    $handle = fopen($filePath, 'r');
    if (!$handle) return false;

    $tempClean = $filePath . '.clean';
    $outHandle = fopen($tempClean, 'w');
    if (!$outHandle) {
        fclose($handle);
        return false;
    }

    $foundXml = false;
    while (($line = fgets($handle)) !== false) {
        if (!$foundXml) {
            $xmlPos = strpos($line, '<?xml');
            if ($xmlPos !== false) {
                $foundXml = true;
                fwrite($outHandle, substr($line, $xmlPos));
            }
        } else {
            fwrite($outHandle, $line);
        }
    }
    fclose($handle);
    fclose($outHandle);

    if ($foundXml) {
        rename($tempClean, $filePath);
        return true;
    } else {
        @unlink($tempClean);
        return false;
    }
}

function StreamXMLtoJSON($xmlFile, $jsonFile, $format, $encoding, $mappingFile = null)
{
    $map = [];
    if ($format === 'dc' && !empty($mappingFile) && file_exists($mappingFile)) {
        $fileContent = file_get_contents($mappingFile);
        if (pathinfo($mappingFile, PATHINFO_EXTENSION) === 'i2x') {
            $lines = explode("\n", $fileContent);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#' || $line[0] === ';') continue;
                $parts = preg_split('/[=:]/', $line, 2);
                if (count($parts) === 2) {
                    $key = preg_replace('/[^0-9]/', '', $parts[0]);
                    if (!empty($key)) {
                        $map[$key] = str_replace('dc:', '', trim($parts[1]));
                    }
                }
            }
        } else {
            // Regular expressions for Visual Mappe mappings
            if (preg_match_all('/<([a-zA-Z0-9_:]+)>.*?v(\d+)/i', $fileContent, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $key = $matches[2][$i];
                    $map[$key] = str_replace('dc:', '', $matches[1][$i]);
                }
            }
        }
    }

    $in = fopen($xmlFile, 'r');
    if (!$in) return false;

    $out = fopen($jsonFile, 'w');
    if (!$out) {
        fclose($in);
        return false;
    }

    fwrite($out, "{\n    \"records\": [\n");

    $first = true;
    $inRecord = false;
    $recordXml = '';

    // =========================================================================
    // EXTRACTION BLOC PAR BLOC
    // =========================================================================
    while (($line = fgets($in)) !== false) {
        if (strpos($line, '<record') !== false) {
            $inRecord = true;
            $recordXml = $line;
        } elseif ($inRecord) {
            $recordXml .= $line;
            if (strpos($line, '</record>') !== false) {
                $inRecord = false;

                // Normalisation en UTF-8 : si les octets ne sont pas au format UTF-8 valide, la base utilisée est ANSI (Windows-1252)
                if (!mb_check_encoding($recordXml, 'UTF-8')) {
                    $recordXml = mb_convert_encoding($recordXml, 'UTF-8', 'Windows-1252');
                }

                // Removing invisible control characters
                $recordXml = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $recordXml);

                // Protection against unescaped '&' characters (e.g. Norton & Company)
                $recordXml = preg_replace('/&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)/', '&amp;', $recordXml);

                // Protection for native subfields of CISIS (e.g. <^>bLey 16.788</^>)
                $recordXml = str_replace(['<^>', '</^>'], ['<sub_hat>', '</sub_hat>'], $recordXml);

                // Try the default fast parser
                libxml_use_internal_errors(true);
                $node = simplexml_load_string($recordXml);

                // If it fails (e.g. due to invalid tags such as <Vaz Eduardo Ferreira,>)
                if ($node === false) {
                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->recover = true; // Force a restore without using the global constant
                    $dom->strictErrorChecking = false;
                    @$dom->loadXML($recordXml);
                    $node = simplexml_import_dom($dom);
                }
                libxml_clear_errors();

                // Only ignore this if the log is completely corrupted and beyond repair
                if ($node === false || !isset($node['mfn'])) {
                    continue;
                }

                $record = [
                    'mfn' => (string)$node['mfn'],
                    'format' => $format,
                    'fields' => []
                ];

                foreach ($node->children() as $child) {
                    $tag = $child->getName();
                    $cleanTag = preg_replace('/^(\w+):/', '', $tag);
                    $numericTag = preg_replace('/[^0-9]/', '', $tag);

                    if (empty($numericTag)) continue;

                    if ($format === 'dc') {
                        if (!isset($map[$numericTag])) continue;
                        $mappedTag = $map[$numericTag];

                        // DUBLIN CORE: Extracts raw XML/HTML whilst preserving tags such as <br>, <p>, <b>
                        $xmlString = $child->asXML();
                        $innerXML = trim(preg_replace('/^<[^>]+>|<\/[^>]+>$/', '', $xmlString));
                        // Restores subfields with a circumflex if they have been printed in the raw text
                        $innerXML = str_replace(['<sub_hat>', '</sub_hat>'], ['<^>', '</^>'], $innerXML);

                        $record['fields'][$mappedTag][] = $innerXML;
                    } else {
                        $mappedTag = $cleanTag;
                        $children = $child->children();

                        // NATIVE FORMAT
                        if (count($children) == 0) {
                            $record['fields'][$mappedTag][] = trim((string)$child);
                        } else {
                            $data = [];
                            $fullText = (string)$child;
                            $childrenText = '';
                            foreach ($children as $subChild) {
                                $subTag = $subChild->getName();
                                if ($subTag === 'sub_hat') $subTag = '^'; // Returns the correct key
                                $subVal = trim((string)$subChild);
                                $childrenText .= $subVal;
                                $data[$subTag] = $subVal;
                            }
                            $indicators = trim(str_replace($childrenText, '', $fullText));
                            if (!empty($indicators)) $data['_'] = $indicators;
                            $record['fields'][$mappedTag][] = $data;
                        }
                    }
                }

                $jsonStr = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                if ($jsonStr === false) {
                    error_log("export_json: mfn " . $record['mfn'] . " json_encode falhou: " . json_last_error_msg());
                    continue;
                }
                if (!$first) fwrite($out, ",\n");
                $jsonStr = "        " . str_replace("\n", "\n        ", $jsonStr);
                fwrite($out, $jsonStr);

                $first = false;
            }
        }
    }

    fwrite($out, "\n    ]\n}");
    fclose($in);
    fclose($out);
    return true;
}

function ExportarBatchJSON($fullpath)
{
    global $xWxis, $mx_path, $db_path, $arrHttp, $msgstr;

    $scriptPath = $xWxis . 'export_batch.xis';
    $tempXmlFile = dirname($fullpath) . '/temp_export_' . time() . '.xml';

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $exe_ext = $isWindows ? '.exe' : '';
    $wxis_cli = str_replace("mx" . $exe_ext, "wxis" . $exe_ext, $mx_path);

    // Detect Encoding from WXIS Path (UTF-8 or ANSI)
    $encoding = (strpos(strtolower($wxis_cli), 'utf8') !== false) ? 'UTF-8' : 'ISO-8859-1';

    // Careful construction of the query string for WXIS to work around
    // shell escape issues that break path parsing or wildcard characters.
    $query = "database=" . urlencode($arrHttp["base"]);

    $parSrc = $db_path . "par/" . $arrHttp["base"] . ".par";
    $parTmp = dirname($fullpath) . '/temp_export_' . time() . '.par';
    file_put_contents($parTmp, str_replace("%path_database%", $db_path, file_get_contents($parSrc)));
    $query .= "&cipar=" . urlencode($parTmp);

    if (isset($arrHttp["Mfn"]) and trim($arrHttp["Mfn"]) != "") {
        $query .= "&from=" . urlencode($arrHttp["Mfn"]);
        $count = (int)$arrHttp["to"] - (int)$arrHttp["Mfn"] + 1;
        $query .= "&count=" . urlencode((string)$count);
        $query .= "&expression=$";
    } else {
        $expresion = str_replace('"', '', trim($arrHttp["Expresion"]));
        $query .= "&expression=" . urlencode($expresion);
        $query .= "&from=1&count=100000";
    }

    if ($arrHttp["format"] === 'dc' && !empty($arrHttp["mapping_file"])) {
        $query .= "&metadata_format=dc&mapping_file=" . urlencode($arrHttp["mapping_file"]);
    }

    putenv('REQUEST_METHOD=GET');
    putenv('QUERY_STRING=' . $query);

    $command = escapeshellarg($wxis_cli) . " IsisScript=" . escapeshellarg($scriptPath) . " > " . escapeshellarg($tempXmlFile) . " 2>&1";

    exec($command, $output, $status);

    if ($status == 0 && file_exists($tempXmlFile)) {

        if (!StripWxisHeaders($tempXmlFile)) {
            $wxisError = file_get_contents($tempXmlFile);
            echo "<h3><font color='red'><br>" . ($msgstr["processfailed"] ?? 'Process failed') . "</font></h3>";
            echo "<div><b>WXIS Error (No XML generated):</b></div>";
            echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc;'>" . htmlspecialchars($wxisError) . "</pre>";
            // @unlink($tempXmlFile);
            return 1;
        }

        $mapping_file = isset($arrHttp["mapping_file"]) ? $arrHttp["mapping_file"] : null;
        $success = StreamXMLtoJSON($tempXmlFile, $fullpath, $arrHttp["format"], $encoding, $mapping_file);
        // @unlink($tempXmlFile);

        if ($success) {
    ?>
            <div align=center><br><br>
                <h4><?php echo $fullpath ?> &nbsp; <?php echo $msgstr["export_json_ok"] ?? 'Generated Successfully'; ?> &nbsp;
                    <a class="bt bt-green" href=javascript:Download()> <i class="fas fa-download"></i> <?php echo $msgstr["download"] ?? 'Download'; ?></a>
                </h4>
            </div>
<?php
        } else {
            echo "<div><font color=red>" . ($msgstr["export_json_err_stream"] ?? "Error streaming JSON formatting. Target file might not be writable or XML is malformed.") . "</font></div>";
            return 1;
        }
    } else {
        echo "<b>Status:</b> " . $status . "<br>";
        if (file_exists($tempXmlFile)) {
            echo "<b>WXIS output:</b><pre>" . htmlspecialchars(substr(file_get_contents($tempXmlFile), 0, 3000)) . "</pre>";
        }
        echo ("<h3><font color='red'><br>" . ($msgstr["processfailed"] ?? 'Process failed') . "</font></h3>");
        echo "<b>Command Executed:</b><br> <pre>" . htmlspecialchars($command) . "</pre><br>";
        echo "<b>Error Output:</b><br> <font color='red'>" . implode("<br>", $output) . "</font>";
        //@unlink($tempXmlFile);
        return 1;
    }
    return 0;
}

include("../common/header.php");
?>

<body>
    <script>
        function Download() {
            document.download.submit()
        }

        function Confirmar() {
            document.continuar.confirmar.value = "OK";
            document.getElementById('preloader').style.visibility = 'visible';
            document.continuar.submit()
        }

        function Regresar() {
            document.continuar.action = "exporta_json.php";
            document.continuar.submit()
        }
    </script>
    <?php
    echo "<form name=continuar action=exporta_json_ex.php method=post>\n";
    foreach ($_REQUEST as $var => $value) {
        $value = htmlspecialchars($value);
        echo "<input type=hidden name=$var value=\"$value\">\n";
    }
    if (!isset($arrHttp["confirmar"])) {
        $arrHttp["confirmar"] = "";
        echo "<input type=hidden name=confirmar value=\"\">\n";
    }
    echo "</form>\n";
    ?>

    <div id="preloader" style="visibility:hidden; text-align:center; margin-top:20px;">
        <i class="fas fa-circle-notch fa-spin fa-3x color-blue"></i>
        <h4 class="mt-2 color-blue"><?php echo $msgstr["export_json_wait"] ?? ''; ?></h4>
    </div>

    <?php if ($inframe != 1) include "../common/institutional_info.php"; ?>

    <div class="sectionInfo">
        <div class="breadcrumb"><?php echo $msgstr["export_json_title"] ?? ''; ?></div>
        <div class="actions">
            <?php if ($arrHttp["Accion"] != "P") include "../common/inc_back.php"; ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>

    <?php
    $ayuda = "exportiso.html";
    include "../common/inc_div-helper.php"
    ?>

    <div class="middle form">
        <div class="formContent">
            <?php
            echo "<table>";
            if (isset($arrHttp["Expresion"]) and trim($arrHttp["Expresion"]) != "") {
                echo "<tr><td>" . ($msgstr["buscar"] ?? '') . ":<td><td>" . htmlspecialchars($arrHttp["Expresion"]) . "</td></tr>";
            }
            if (isset($arrHttp["Mfn"]) and trim($arrHttp["Mfn"]) != "") {
                echo "<tr><td>" . ($msgstr["r_mfnr"] ?? '') . ":<td><td>" . htmlspecialchars($arrHttp["Mfn"]) . " &rarr; " . htmlspecialchars($arrHttp["to"]) . "</td></tr>";
            }

            $storein = $arrHttp["storein"];
            if ((strpos($storein, "/") === 0)) $storein = substr($storein, 1);
            $full_storein = $db_path . $storein;
            $fullpath = $full_storein . "/" . $filename;

            echo "<tr><td>" . ($msgstr["export_json_format"] ?? '') . "<td><td><strong>" . strtoupper($arrHttp['format']) . "</strong></td></tr>";
            echo "<tr><td>" . ($msgstr["export_json_output"] ?? '') . "<td><td>" . $fullpath . "</td></tr>";
            echo "</table><br>";

            clearstatcache();
            $isexec = (PHP_OS_FAMILY == "Linux") ? is_executable($full_storein) : true;

            if (!is_writable($full_storein) or !$isexec) {
                echo "<div style='color:red'>" . $full_storein . " <b>" . ($msgstr["notreadable"] ?? '') . "</b></div>";
                die;
            }

            if (!isset($arrHttp["confirmar"]) or (isset($arrHttp["confirmar"]) and $arrHttp["confirmar"] != "OK")) {
                Confirmar();
            } else {
                $errors = DeleteFile($fullpath, 0);
                if ($errors > 0) {
                    echo "<div><font color=red><b>" . ($msgstr["export_json_aborted"] ?? '') . "</b></font></div>";
                    echo "<div><h2><font color=red><b>" . sprintf(($msgstr["export_json_not_modified"] ?? ''), $fullpath) . "</b></font></h2></div>";
                    echo "<a href='javascript:Regresar()'> " . ($msgstr["cancelar"] ?? '') . "</a>";
                    die;
                }

                ExportarBatchJSON($fullpath);
            ?>
        </div>
    </div>
    <form name=download action="../utilities/download.php">
        <input type=hidden name=archivo value="<?php echo $filename ?>">
    </form>
<?php
            }
            include("../common/footer.php");
?>
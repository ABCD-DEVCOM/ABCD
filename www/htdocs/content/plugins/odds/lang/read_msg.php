<?php

declare(strict_types=1);

global $msg_tab, $msgstr, $charset;

if (!isset($msgstr) || !is_array($msgstr)) {
    $msgstr = [];
}

if (!isset($msg_tab) || empty($msg_tab)) {
    return;
}

$pluginLangDir = __DIR__ . DIRECTORY_SEPARATOR;
$currentLang = $_SESSION['lang'] ?? 'en';
$baseLang = 'en';
$systemCharset = $charset ?? 'ISO-8859-1';

// Prepara a lista de arquivos para ler (Garante o fallback para inglês)
$filesToLoad = [
    $pluginLangDir . "{$baseLang}/{$msg_tab}",
    $currentLang !== $baseLang ? $pluginLangDir . "{$currentLang}/{$msg_tab}" : ''
];

foreach ($filesToLoad as $filePath) {
    if (empty($filePath) || !file_exists($filePath)) {
        continue;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        continue;
    }

    foreach ($lines as $line) {
        // ASSASSINO DE BOM: Remove caracteres invisíveis do início da string (UTF-8 BOM)
        $line = preg_replace('/^[\xef\xbb\xbf]+/', '', $line);
        $line = trim($line);

        // Ignora comentários e linhas vazias
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }

        // Verifica o delimitador (| ou =)
        if (str_contains($line, '|')) {
            $delimiter = '|';
        } elseif (str_contains($line, '=')) {
            $delimiter = '=';
        } else {
            continue;
        }

        $parts = explode($delimiter, $line, 2);
        if (count($parts) < 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        $value = str_replace('"', '&quot;', $value);
        $value = str_replace("'", '&apos;', $value);

        if ($systemCharset === 'UTF-8' && !mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }

        $msgstr[$key] = $value;
    }
}

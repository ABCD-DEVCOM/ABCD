<?php
/**
 * ABCD Plugin Builder & Database Sanitizer
 * Automatically captures the live database structure, sanitizes it (removes user data),
 * prepares the install template, and packages everything into a distribution .zip.
 * 
 * @package ABCD_Plugin_System
 * @requires PHP 8.1+
 */

// 1. Ensure ZipArchive is available
if (!extension_loaded('zip')) {
    die("Error: ZipArchive extension is required to build the package.\n");
}

// 2. Bootstrap ABCD Core to access PluginBridge and paths
$configPath = realpath(__DIR__ . '/../../../central/config.php');
if (!file_exists($configPath)) {
    die("Error: Could not locate ABCD central config.php.\n");
}
require_once $configPath;

$bridge = PluginBridge::getInstance();
$dbPath = rtrim($bridge->get('db_path'), '/\\') . DIRECTORY_SEPARATOR;
$pluginDir = __DIR__;
$manifestPath = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.json';

// 3. Read the manifest
if (!file_exists($manifestPath)) {
    die("Error: plugin.json manifest not found in {$pluginDir}\n");
}

$manifest = json_decode(file_get_contents($manifestPath), true);
$slug = $manifest['slug'] ?? 'unknown-plugin';
$version = $manifest['version'] ?? '1.0.0';

// =========================================================================
// STEP A: SANITIZE AND PREPARE THE DATABASE TEMPLATE
// =========================================================================
echo "Starting build process for {$manifest['name']} v{$version}...\n";

$liveBaseDir = $dbPath . $slug;
$installTemplateDir = $pluginDir . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . $slug;

// ... (as funções build_recursive_delete e build_recursive_copy permanecem) ...

// Wipe the old template if it exists
if (is_dir($installTemplateDir)) {
    build_recursive_delete($installTemplateDir);
}
mkdir($installTemplateDir, 0775, true);

// 1. Copy structural folders (Def and Pfts) from the live database
$foldersToCopy = ['def', 'pfts'];
foreach ($foldersToCopy as $folder) {
    if (is_dir($liveBaseDir . DIRECTORY_SEPARATOR . $folder)) {
        build_recursive_copy($liveBaseDir . DIRECTORY_SEPARATOR . $folder, $installTemplateDir . DIRECTORY_SEPARATOR . $folder);
        echo "- Structure '{$folder}/' copied successfully.\n";
    }
}

// 2. Capture the global parameter (.par) file
$liveParFile = $dbPath . 'par' . DIRECTORY_SEPARATOR . $slug . '.par';
$installParDir = $installTemplateDir . DIRECTORY_SEPARATOR . 'par';
if (file_exists($liveParFile)) {
    mkdir($installParDir, 0775, true);
    copy($liveParFile, $installParDir . DIRECTORY_SEPARATOR . $slug . '.par');
    echo "- Parameter file 'par/{$slug}.par' copied successfully.\n";
}

// 3. Create OS-specific virgin data folders (CISIS/WXIS requirement)
$installDataWinDir = $installTemplateDir . DIRECTORY_SEPARATOR . 'data-win';
$installDataLinDir = $installTemplateDir . DIRECTORY_SEPARATOR . 'data-lin';

mkdir($installDataWinDir, 0775, true);
mkdir($installDataLinDir, 0775, true);

file_put_contents($installDataWinDir . DIRECTORY_SEPARATOR . 'control_number.cn', "0");
file_put_contents($installDataLinDir . DIRECTORY_SEPARATOR . 'control_number.cn', "0");

echo "- OS-specific Data folders (data-win, data-lin) prepared and sanitized.\n";

// =========================================================================
// STEP B: PACKAGE THE ZIP FILE
// =========================================================================
$zipFileName = "{$slug}-v{$version}.zip";
// Place the ZIP one level above the plugin folder (in content/plugins/)
$zipFilePath = dirname($pluginDir) . DIRECTORY_SEPARATOR . $zipFileName;

$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Error: Cannot create ZIP file at {$zipFilePath}\n");
}

$ignoredItems = [
    '.git',
    '.DS_Store',
    'build_release.php', // Ignore the builder script itself
    'node_modules',
    '.gitignore'
];

$shouldIgnore = function(string $path) use ($ignoredItems): bool {
    foreach ($ignoredItems as $ignored) {
        if (str_contains($path, DIRECTORY_SEPARATOR . $ignored)) {
            return true;
        }
    }
    return false;
};

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$fileCount = 0;
foreach ($iterator as $file) {
    $realPath = $file->getRealPath();
    
    if ($shouldIgnore($realPath)) {
        continue;
    }

    // The ZIP must have a root folder named after the slug
    $relativePath = $slug . str_replace($pluginDir, '', $realPath);
    $relativePath = str_replace('\\', '/', $relativePath);

    if ($file->isDir()) {
        $zip->addEmptyDir($relativePath);
    } elseif ($file->isFile()) {
        $zip->addFile($realPath, $relativePath);
        $fileCount++;
    }
}

$zip->close();

echo "=========================================\n";
echo " Plugin Packaged Successfully!\n";
echo "=========================================\n";
echo " Plugin : {$manifest['name']} ({$slug})\n";
echo " Version: {$version}\n";
echo " Files  : {$fileCount} files added.\n";
echo " Output : {$zipFilePath}\n";
echo "=========================================\n";
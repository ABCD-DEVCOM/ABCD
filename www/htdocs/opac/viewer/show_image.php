<?php

/**
 * Script: show_image.php (OPAC)
 * 2023-01-04 rogercgui Uncommented line 19 "$filename=strtolower.."Reason: files with the uppercase extension are not working on updated Linux servers.
 * 2026-02-28 rogercgui Added security checks to prevent path traversal attacks. The script now verifies that the requested file is within the allowed base directory before serving it.
 * 2026-11-18 rogercgui Introduce 'if (ob_get_length()) ob_clean();' immediately before any 'header()' and image output functions ('readfile', 'imagejpeg', etc.) to guarantee a pristine binary stream.
 * **/

session_start();
include("get_post.php");
include("../../central/config.php");

// Sets the default path
$img_path = $db_path . $arrHttp["base"] . "/";

// Reading and replacing the %path_database% variable if it exists
if (file_exists($db_path . $arrHttp["base"] . "/dr_path.def")) {
    $def = parse_ini_file($db_path . $arrHttp["base"] . "/dr_path.def");

    // Checks whether ROOT exists and is not null
    if ($def && isset($def["ROOT"])) {
        $root_trim = trim($def["ROOT"]);
        if (!empty($root_trim)) {
            $img_path = $root_trim;
            $img_path = str_replace("%path_database%", $db_path, $img_path);
        }
    }
}
// Anti-Path Traversal Security
$base_dir = realpath($img_path);
$requested_path = $img_path . $arrHttp["image"];
$real_requested_path = realpath($requested_path);

// Blocks if the actual path is not within the base folder
if ($real_requested_path === false || strpos($real_requested_path, $base_dir) !== 0) {
    header("HTTP/1.0 403 Forbidden");
    // Updated to use system language variables
    $error_msg = isset($msgstr["img_access_denied"]) ? $msgstr["img_access_denied"] : "Access denied.";
    die($error_msg);
}

$img_file = $real_requested_path;

if (!file_exists($img_file)) {
    header("HTTP/1.0 404 Not Found");
    // Updated to use system language variables
    $error_msg = isset($msgstr["img_file_not_found"]) ? $msgstr["img_file_not_found"] : "File not found!";
    die($error_msg);
}

$filename = basename($arrHttp["image"]);
$f_ext = strtolower(pathinfo($img_file, PATHINFO_EXTENSION));

// 3. Check whether to process (GD) or just deliver
// Files that are not images or if GD fails should just be read
$process_image = false;
$content_type = "";

switch ($f_ext) {
    case "jpg":
    case "jpeg":
        $content_type = "image/jpeg";
        $process_image = true;
        break;
    case "png":
        $content_type = "image/png";
        $process_image = true;
        break;
    case "gif":
        $content_type = "image/gif";
        $process_image = true;
        break;
    case "pdf":
        $content_type = "application/pdf";
        break;
    case "doc":
    case "docx":
        $content_type = "application/msword";
        break;
    case "mp3":
        $content_type = "audio/mpeg";
        break;
    case "mp4":
        $content_type = "video/mp4";
        break;
    case "svg":
        $content_type = "image/svg+xml";
        break;
    default:
        // Attempts to automatically detect mime type for other files
        if (function_exists('mime_content_type')) {
            $content_type = mime_content_type($img_file);
        } else {
            $content_type = "application/octet-stream";
        }
}

// If it is not a processable image (or if the user requested the original “raw” file), deliver directly
if (!$process_image || !extension_loaded('gd') || isset($_REQUEST['raw'])) {
    if (ob_get_length()) ob_clean(); // Flushes any leaked whitespace/warnings
    header("Content-type: $content_type");
    header("Content-Length: " . filesize($img_file));
    readfile($img_file);
    exit;
}

// Image Processing (Resize + Watermark)
try {
    switch ($f_ext) {
        case "jpg":
        case "jpeg":
            $img = @imagecreatefromjpeg($img_file);
            break;
        case "png":
            $img = @imagecreatefrompng($img_file);
            break;
        case "gif":
            $img = @imagecreatefromgif($img_file);
            break;
    }

    if (!$img) {
        // If opening fails (e.g. corrupted image), deliver the original file
        header("Content-type: $content_type");
        readfile($img_file);
        exit;
    }

    // Resizing Logic
    if (!isset($_REQUEST['full'])) {
        $max_dim = 800;
        $width = imagesx($img);
        $height = imagesy($img);

        // Only resize if it is larger than the limit
        if ($width > $max_dim || $height > $max_dim) {
            $scale = min($max_dim / $width, $max_dim / $height, 1);
            $new_width = intval($width * $scale);
            $new_height = intval($height * $scale);

            $resized = imagecreatetruecolor($new_width, $new_height);

            // Preserves transparency for PNG/GIF
            if ($f_ext == "png" || $f_ext == "gif") {
                imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        } else {
            $resized = $img;
        }
    } else {
        $resized = $img;
    }

    // Watermark
    $font_file = "../assets/webfonts/alexandria.ttf";
    if (file_exists($font_file)) {
        $text = $_SERVER['HTTP_HOST'] . " - " . date('Ymd H:i');
        $text_color = imagecolorallocatealpha($resized, 255, 255, 255, 60); // Ajustei transparência
        // Adds simple shadow for readability
        $shadow_color = imagecolorallocatealpha($resized, 0, 0, 0, 60);
        imagettftext($resized, 15, 0, 11, 26, $shadow_color, $font_file, $text);
        imagettftext($resized, 15, 0, 10, 25, $text_color, $font_file, $text);
    }

    // Exit
    if (ob_get_length()) ob_clean(); // Ensures pristine binary output
    header("Content-type: $content_type");
    if ($f_ext == "png") imagepng($resized);
    elseif ($f_ext == "gif") imagegif($resized);
    else imagejpeg($resized, null, 85);

    if ($resized !== $img && $img instanceof GdImage) imagedestroy($img);
    if ($resized instanceof GdImage) imagedestroy($resized);
} catch (Exception $e) {
    // Exit Point 3: Security fallback
    if (ob_get_length()) ob_clean(); // Ensures original file doesn't get corrupted
    header("Content-type: $content_type");
    readfile($img_file);
}
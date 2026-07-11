<?php
// SECURITY LOCK: Prevents direct URL access
if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

return array (
  'host' => '127.0.0.1',
  'port' => 25,
  'username' => '',
  'password' => '',
  'from_email' => 'admin@abcdhost.net',
  'from_name' => 'ABCD Document Delivery',
);

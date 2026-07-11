<?php
/**
 * Name: hooks.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Hook system for the ABCD application.
 * This file handles the registration and execution of hooks, allowing plugins to modify core behavior without changing core files.
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */


// Global array to store registered hooks
global $_ABCD_HOOKS;
$_ABCD_HOOKS = [];

/**
 * Registers a callback function to a specific hook.
 *
 * @param string   $hook     The name of the hook.
 * @param callable $cb       The callback function to execute.
 * @param int      $priority Execution priority (lower runs earlier). Default is 10.
 */
function abcd_add_hook(string $hook, callable $cb, int $priority = 10): void
{
    global $_ABCD_HOOKS;
    $_ABCD_HOOKS[$hook][$priority][] = $cb;
}

/**
 * Executes all callback functions registered to a specific hook.
 *
 * @param string $hook The name of the hook.
 * @param mixed  $data Optional data to pass to the callbacks.
 * @return mixed The modified data after all callbacks have processed it.
 */
function abcd_run_hook(string $hook, mixed $data = null): mixed
{
    global $_ABCD_HOOKS;

    // If no hooks are registered for this event, return the original data unchanged
    if (!isset($_ABCD_HOOKS[$hook])) {
        return $data;
    }

    // Sort by priority (ascending order: 1 runs before 10)
    ksort($_ABCD_HOOKS[$hook]);

    foreach ($_ABCD_HOOKS[$hook] as $callbacks) {
        foreach ($callbacks as $cb) {
            // Ensure the callback is valid before execution to prevent fatal errors
            if (is_callable($cb)) {
                $data = $cb($data);
            }
        }
    }

    return $data;
}


/**
 * Loads all active plugins based on the central registry (plugins.json).
 */
function abcd_load_active_plugins(): void
{
    // Determine content path. Fallback to default directory structure if constant is missing
    $contentPath = defined('ABCD_CONTENT_PATH') ? ABCD_CONTENT_PATH : realpath(__DIR__ . '/../../content');
    $registryPath = $contentPath . '/plugins/plugins.json';

    if (!file_exists($registryPath)) {
        return;
    }

    $registryData = file_get_contents($registryPath);
    if ($registryData === false) {
        return;
    }

    $plugins = json_decode($registryData, true);
    if (!is_array($plugins)) {
        return;
    }

    foreach ($plugins as $slug => $info) {
        if (!isset($info['active']) || $info['active'] !== true) {
            continue;
        }

        // Sanitize slug to prevent directory traversal attacks
        $safeSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$slug);
        $bootstrap = $contentPath . "/plugins/{$safeSlug}/plugin-bootstrap.php";

        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }
}

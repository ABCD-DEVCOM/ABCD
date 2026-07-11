<?php
/**
 * Name: PluginBridge.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Plugin bridge for the ABCD application.
 * This file provides a safe interface for plugins to access ABCD core functionality without polluting their scope.
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

class PluginBridge
{
    private static ?self $instance = null;

    /**
     * Immutable configuration state using PHP 8.1 readonly properties.
     * @var array<string, mixed>
     */
    private readonly array $config;

    private function __construct()
    {
        // Read global variables defined by ABCD's central config.php
        global $db_path, $lang_db, $wxis, $xWxis, $server_url, $ABCD_scripts_path;

        $sessionLang = $_SESSION['lang'] ?? null;

        $this->config = [
            'db_path'           => $db_path ?? '',
            'lang'              => $sessionLang ?? ($lang_db ?? 'en'),
            'wxis_path'         => $wxis ?? '',
            'wxis_scripts_path' => $xWxis ?? '',
            'server_url'        => $server_url ?? '',
            'root_path'         => $ABCD_scripts_path ?? '',
            'base'              => $_REQUEST['base'] ?? '',
        ];
    }

    /**
     * Returns the Singleton instance of the PluginBridge.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retrieves a configuration value safely.
     *
     * @param string $key     The configuration key.
     * @param mixed  $default Default value if the key does not exist.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Helper to check if a specific database is currently active.
     *
     * @param string $baseName The database name to check.
     * @return bool
     */
    public function isBaseActive(string $baseName): bool
    {
        return $this->get('base') === $baseName;
    }
}

<?php

/**
 * Name: LanguageManager.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Language management class for the ABCD application.
 * This class handles the loading and management of translation strings for different languages, implementing a cascading translation system that allows for user overrides without modifying the core files.
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */



declare(strict_types=1);

namespace ABCD\Common;

/**
 * Language Manager for ABCD System
 *
 * Handles the cascading translation system allowing user overrides without
 * modifying the core files.
 * * Priority: 
 * 1. content/lang/{lang} (Custom Overrides)
 * 2. central/lang/{lang} (Core Translations)
 * 3. central/lang/en     (Core English Fallback)
 */
class LanguageManager 
{
    private string $centralPath;
    private string $contentPath;
    private string $defaultLang = 'en';

    /**
     * @param string $centralPath Path to the core engine directory (central)
     * @param string $contentPath Path to the user data directory (content)
     */
    public function __construct(string $centralPath, string $contentPath) 
    {
        // Ensure paths end with a directory separator
        $this->centralPath = rtrim($centralPath, '/\\') . DIRECTORY_SEPARATOR;
        $this->contentPath = rtrim($contentPath, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Parses a .tab translation file into an associative array.
     *
     * @param string $filePath Full path to the .tab file.
     * @return array<string, string> Dictionary of translation keys and values.
     */
    private function parseTabFile(string $filePath): array 
    {
        $translations = [];
        
        if (!file_exists($filePath)) {
            return $translations;
        }

        // Read file ignoring empty lines and new lines at the end
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines === false) {
            return $translations;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments or invalid lines without '='
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            // Split only on the first '=' character
            [$key, $value] = explode('=', $line, 2);
            $translations[trim($key)] = trim($value);
        }

        return $translations;
    }

    /**
     * Loads and merges translations for a specific module/file using the cascade logic.
     *
     * @param string $fileName The name of the tab file (e.g., 'admin.tab')
     * @param string $userLang The selected language code (e.g., 'pt', 'es')
     * @return array<string, string> Merged translations
     */
    public function loadTranslations(string $fileName, string $userLang): array 
    {
        // 1. Fallback: Core English ensures no blank strings
        $baseFile = $this->centralPath . "lang/{$this->defaultLang}/{$fileName}";
        $baseTranslations = $this->parseTabFile($baseFile);

        // 2. Core Translation: Factory default for requested language
        $coreTranslations = [];
        if ($userLang !== $this->defaultLang) {
            $coreFile = $this->centralPath . "lang/{$userLang}/{$fileName}";
            $coreTranslations = $this->parseTabFile($coreFile);
        }

        // 3. Custom Override: User modified translations in content directory
        $customFile = $this->contentPath . "lang/{$userLang}/{$fileName}";
        $customTranslations = $this->parseTabFile($customFile);

        // array_merge overwrites keys that already exist with the ones from the later arrays
        return array_merge($baseTranslations, $coreTranslations, $customTranslations);
    }

    /**
     * Maintains backward compatibility for getting the general languages list file.
     * * @param string $userLang The selected language code
     * @return string Valid file path to the lang.tab file
     * @throws \RuntimeException If no lang.tab is found anywhere
     */
    public function getLangListFile(string $userLang): string 
    {
        $attempts = [
            $this->contentPath . "lang/{$userLang}/lang.tab",
            $this->centralPath . "lang/{$userLang}/lang.tab",
            $this->centralPath . "lang/{$this->defaultLang}/lang.tab"
        ];

        foreach ($attempts as $filePath) {
            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        throw new \RuntimeException("Critical Error: Missing core language list (lang.tab).");
    }
}
<?php

/**
 * Name: LanguageManager.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Language management class for the ABCD application.
 * This class handles the loading and management of translation strings for different languages, 
 * implementing a cascading translation system that allows for user overrides without modifying the core files.
 * 
 * Priority for Core:
 * 1. content/lang/{lang}/{fileName} (User Overrides)
 * 2. central/lang/{lang}/{fileName} (Core Translations)
 * 3. central/lang/en/{fileName}     (Core English Fallback)
 * 
 * Priority for Plugins:
 * 1. content/lang/{lang}/{fileName}          (User Overrides)
 * 2. plugins/{pluginSlug}/lang/{lang}/{fileName} (Core Plugin Translations)
 * 3. plugins/{pluginSlug}/lang/en/{fileName}     (Plugin English Fallback)
 */

declare(strict_types=1);

namespace ABCD\Common;

class LanguageManager
{
    private string $centralPath;
    private string $contentPath;
    private string $defaultLang = 'en';

    public function __construct(string $centralPath, string $contentPath)
    {
        $this->centralPath = rtrim($centralPath, '/\\') . DIRECTORY_SEPARATOR;
        $this->contentPath = rtrim($contentPath, '/\\') . DIRECTORY_SEPARATOR;
    }

    private function parseTabFile(string $filePath): array
    {
        $translations = [];

        if (!file_exists($filePath)) {
            return $translations;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return $translations;
        }

        foreach ($lines as $line) {
            // Remove UTF-8 BOM if present
            $line = preg_replace('/^[\xef\xbb\xbf]+/', '', $line);
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $delimiter = str_contains($line, '|') ? '|' : '=';
            $parts = explode($delimiter, $line, 2);

            if (count($parts) === 2) {
                $translations[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $translations;
    }

    /**
     * Loads Core Translations with cascading logic.
     */
    public function loadTranslations(string $fileName, string $userLang): array
    {
        // 3. Core English Fallback
        $baseFile = $this->centralPath . "lang/{$this->defaultLang}/{$fileName}";
        $baseTranslations = $this->parseTabFile($baseFile);

        // 2. Core Translation: Factory language
        $coreTranslations = [];
        if ($userLang !== $this->defaultLang) {
            $coreFile = $this->centralPath . "lang/{$userLang}/{$fileName}";
            $coreTranslations = $this->parseTabFile($coreFile);
        }

        // 1. User Custom Override (Inside Content Vault)
        $customFile = $this->contentPath . "lang/{$userLang}/{$fileName}";
        $customTranslations = $this->parseTabFile($customFile);

        // Merge: Custom overrides Core, Core overrides Base
        return array_merge($baseTranslations, $coreTranslations, $customTranslations);
    }

    /**
     * Loads Plugin Translations with cascading logic.
     */
    public function loadPluginTranslations(string $pluginDir, string $pluginSlug, string $fileName, string $userLang): array
    {
        $pluginDir = rtrim($pluginDir, '/\\') . DIRECTORY_SEPARATOR;

        // 3. Plugin English Fallback
        $baseFile = $pluginDir . "lang/en/{$fileName}";
        $baseTranslations = $this->parseTabFile($baseFile);

        // 2. Plugin Core Translation: Factory language
        $coreTranslations = [];
        if ($userLang !== 'en') {
            $coreFile = $pluginDir . "lang/{$userLang}/{$fileName}";
            $coreTranslations = $this->parseTabFile($coreFile);
        }

        // 1. User Custom Override (Inside Content Vault)
        $customFile = $this->contentPath . "lang/{$userLang}/{$fileName}";
        $customTranslations = $this->parseTabFile($customFile);

        // Merge: Custom overrides Plugin Core, Plugin Core overrides Plugin Base
        return array_merge($baseTranslations, $coreTranslations, $customTranslations);
    }

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

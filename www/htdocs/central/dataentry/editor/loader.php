<?php

/**
 * Name: loader.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * 
 * Description: Loader for the new modular data entry editor.
 * Includes the classes in a secure manner.
 */

// Helpers 
require_once __DIR__ . '/helpers/ConfigHelper.php';
require_once __DIR__ . '/helpers/SubfieldHelper.php';
require_once __DIR__ . '/helpers/CalendarHelper.php';

// Renderers
require_once __DIR__ . '/renderers/HtmlAreaRenderer.php';
require_once __DIR__ . '/renderers/CheckRenderer.php';
require_once __DIR__ . '/renderers/SelectRenderer.php';
require_once __DIR__ . '/renderers/TextRenderer.php';
require_once __DIR__ . '/renderers/RepeatableRenderer.php';
require_once __DIR__ . '/renderers/TableRenderer.php';
require_once __DIR__ . '/renderers/GroupRenderer.php';
require_once __DIR__ . '/renderers/TabRenderer.php';
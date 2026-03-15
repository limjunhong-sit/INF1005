<?php
/**
 * Central path configuration. Include this once at the top of each entry script.
 * Defines ROOT as the project root (directory containing index.php).
 */
if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/..'));
}

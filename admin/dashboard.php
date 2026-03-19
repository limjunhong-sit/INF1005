<?php
require_once __DIR__ . '/../config/paths.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ROOT . '/config/admin_timeout.php';

header("Location: index.php");
exit;

<?php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// serve real files normally
if (is_file(__DIR__ . $path)) {
    return false;
}

// route everything else to index.php
$_GET["page"] = trim($path, "/");

require __DIR__ . "/index.php";
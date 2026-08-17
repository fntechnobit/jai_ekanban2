<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * Entry point for subdirectory deployment in XAMPP
 */

// Get the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// If accessing via /jai_ekanban2/ (without public), redirect internally
if (str_contains($requestUri, '/jai_ekanban2/') && !str_contains($requestUri, '/jai_ekanban2/public/')) {
    // Remove /jai_ekanban2/ prefix and route through Laravel
    $_SERVER['SCRIPT_NAME'] = '/jai_ekanban2/public/index.php';
    $pathInfo = str_replace('/jai_ekanban2/', '/', $requestUri);
    $_SERVER['PATH_INFO'] = $pathInfo;
    $_SERVER['REQUEST_URI'] = '/jai_ekanban2/public' . $pathInfo;
}

// Forward to the public folder
require_once __DIR__.'/public/index.php';

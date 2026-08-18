<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * Entry point for subdirectory deployment in XAMPP
 */

// Get the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Subdirectory this app is deployed under, e.g. "/ekanban2/"
$baseDir = '/'.basename(__DIR__).'/';

// If accessing via the subdirectory (without public), redirect internally
if (str_contains($requestUri, $baseDir) && !str_contains($requestUri, $baseDir.'public/')) {
    // Remove the subdirectory prefix and route through Laravel
    $_SERVER['SCRIPT_NAME'] = $baseDir.'public/index.php';
    $pathInfo = str_replace($baseDir, '/', $requestUri);
    $_SERVER['PATH_INFO'] = $pathInfo;
    $_SERVER['REQUEST_URI'] = rtrim($baseDir, '/').'/public'.$pathInfo;
}

// Forward to the public folder
require_once __DIR__.'/public/index.php';

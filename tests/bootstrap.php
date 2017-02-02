<?php
/**
 * Configure autoloading
 */
require_once 'vendor/autoload.php';

$baseDir = realpath(__DIR__ . '/src/');

$loader = new \Composer\Autoload\ClassLoader;
$loader->add('MP\\', $baseDir);
$loader->register();

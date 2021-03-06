<?php

use Phalcon\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

if (!class_exists(TestCase::class) && class_exists(PHPUnit_Framework_TestCase::class)) {
    class_alias(PHPUnit_Framework_TestCase::class, TestCase::class);
}

error_reporting(-1);
$_SERVER['PHWOOLCON_ENV'] = 'testing';

if (!extension_loaded('phalcon')) {
    echo $error = 'Extension "phalcon" not detected, please install or activate it.';
    throw new RuntimeException($error);
}

define('TEST_ROOT_PATH', __DIR__ . '/root');

// The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
$di = new FactoryDefault();
$_SERVER['PHWOOLCON_ROOT_PATH'] = TEST_ROOT_PATH;
$_SERVER['PHWOOLCON_CONFIG_PATH'] = TEST_ROOT_PATH . '/app/config';

// Register class loader
include __DIR__ . '/../vendor/autoload.php';

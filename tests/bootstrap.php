<?php

define('SRC_DIR', __DIR__ . '/../src/');
define('BRIDGES_DIR', __DIR__ . '/../bridges/');
define("TEMP_DIR", __DIR__ . "/tmp/");

require __DIR__ . "/../vendor/autoload.php";

if (!class_exists('Tester\Assert')) {
    echo "Install Nette Tester using `composer update --dev`\n";
    exit(1);
}
@mkdir(__DIR__ . "/log");
@rmdir(TEMP_DIR);
@mkdir(TEMP_DIR);

Tester\Helpers::purge(TEMP_DIR);

$configurator = new Nette\Configurator;
$configurator->enableDebugger(__DIR__ . "/log");
$configurator->setDebugMode(FALSE);
$configurator->setTempDirectory(TEMP_DIR);
$configurator->createRobotLoader()
    ->addDirectory(SRC_DIR)
    ->addDirectory(__DIR__ . '/classes')
    ->addDirectory(BRIDGES_DIR)
    ->register();

$configurator->addConfig(__DIR__ . '/config.neon');
$container = $configurator->createContainer();

Tester\Environment::setup();

return $container;
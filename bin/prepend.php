<?php
/**
 * Sets up the environment for the command line tools.
 *
 * @package VortexModExporter
 * @subpackage Command Line
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

$autoloadFile = __DIR__.'/../vendor/autoload.php';
$configFile = __DIR__.'/../config.php';

if(!file_exists($configFile)) {
    die('Please copy config.dist.php to config.php and set the necessary settings.'.PHP_EOL);
}

if(!file_exists($autoloadFile)) {
    die('Please run "composer install" to install the required dependencies.'.PHP_EOL);
}

require_once $autoloadFile;
require_once $configFile;

if(!is_dir(VORTEX_APPDATA_FOLDER)) {
    die('Vortex AppData folder not found, please check that the setting points to the correct path.'.PHP_EOL);
}

const OUTPUT_FOLDER = __DIR__.'/../output';


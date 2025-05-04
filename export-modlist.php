<?php
/**
 * Main export script.
 *
 * # Usage
 *
 * 1. Open Vortex
 * 2. Go to Settings > Workarounds
 * 3. In the "Database Backup" section, click "Create Backup"
 * 4. Run the script
 *
 * @package VortexModExporter
 * @subpackage Core
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\FileHelper\JSONFile;
use AppUtils\Microtime;

$autoloadFile = __DIR__.'/vendor/autoload.php';
$configFile = __DIR__.'/config.php';

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

$file = JSONFile::factory(VORTEX_APPDATA_FOLDER.'/temp/state_backups_full/manual.json');

if(!$file->exists()) {
    die('Vortex backup file not found. Have you made a manual database export from the interface?'.PHP_EOL);
}

$data = $file->getData();

if(!isset($data['persistent']['mods'])) {
    die('The mods storage key way not found in the database backup file.' . PHP_EOL);
}

$games = EXPORT_GAMES;
if(empty($games)) {
    $games = array_keys($data['persistent']['mods']);
}

foreach($games as $game) {
    echo 'Game: ' . $game . PHP_EOL;
    if(!isset($data['persistent']['mods'][$game])) {
        die(sprintf('ERROR: The game [%s] was not found in the database backup file.', $game) . PHP_EOL);
    }

    exportGame($game, $data['persistent']['mods'][$game], $data['persistent']['categories'][$game] ?? array());
}

function exportGame(string $game, array $modsData, array $categoriesData) : void
{
    echo sprintf('  - Exporting mod list for [%s] mods...', count($modsData)) . PHP_EOL;

    $mods = array();
    $tags = array();
    $categories = array();
    foreach ($modsData as $modData)
    {
        $attribs = $modData['attributes'];
        $name = $attribs['customFileName'] ?? $attribs['fileName'] ?? $attribs['modName'] ?? $attribs['name'] ?? 'Unnamed' ;
        $category = $attribs['category'] ?? 0;

        if (str_starts_with($name, 'Z -') || str_starts_with($name, 'Y -')) {
            continue;
        }

        preg_match_all('/\[([^]]+)]/', $name, $matches);
        $modTags = array();
        $cleanName = $name;
        if (!empty($matches[1])) {
            $modTags = $matches[1];
            sort($modTags);
            foreach ($matches[1] as $tag) {
                $tag = trim($tag);
                if (!isset($tags[$tag])) {
                    $tags[$tag] = array();
                }
                $tags[$tag][] = $name;
            }

            foreach ($matches[0] as $match) {
                $cleanName = str_replace($match, '', $cleanName);
            }

            $cleanName = trim($cleanName);
        }

        $category = $categoriesData[$category]['name'] ?? 'Unknown';

        if (!isset($categories[$category])) {
            $categories[$category] = array();
        }

        $categories[$category][] = $name;

        $mods[$cleanName] = array(
            'taggedName' => $name,
            'officialName' => $attribs['modName'] ?? '',
            'homepage' => $attribs['homepage'] ?? '',
            'category' => $category,
            'endorsed' => $attribs['endorsed'] ?? 'Undecided',
            'tags' => $modTags,
        );
    }

    uksort($categories, 'strnatcasecmp');

    foreach (array_keys($categories) as $category) {
        usort($categories[$category], 'strnatcasecmp');
    }

    uksort($tags, 'strnatcasecmp');

    foreach (array_keys($tags) as $tag) {
        usort($tags[$tag], 'strnatcasecmp');
    }

    uksort($mods, 'strnatcasecmp');

    $fileName = $game.'.json';

    JSONFile::factory(__DIR__ . '/output/'.$fileName)
        ->setEscapeSlashes(false)
        ->setTrailingNewline(true)
        ->setPrettyPrint(true)
        ->putData(array(
            'game' => $game,
            'exportDate' => Microtime::createNow()->getISODate(true),
            'categories' => $categories,
            'tags' => $tags,
            'mods' => $mods
        ));

    echo "  - DONE, saved to " . $fileName . PHP_EOL;
    echo PHP_EOL;
}

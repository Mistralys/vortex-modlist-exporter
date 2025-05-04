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

require_once __DIR__.'/prepend.php';

use AppUtils\FileHelper\JSONFile;
use AppUtils\Microtime;
use DateTime;

$file = JSONFile::factory(VORTEX_APPDATA_FOLDER.'/temp/state_backups_full/manual.json');

if(!$file->exists()) {
    die('Vortex backup file not found. Have you made a manual database export from the interface?'.PHP_EOL);
}

$date = $file->getModifiedDate();
if($date === null) {
    die('The backup file does not have a valid date. Please make sure you are using the correct file.' . PHP_EOL);
}

$data = $file->getData();

if(!isset($data['persistent']['mods'])) {
    die('The mods storage key way not found in the database backup file.' . PHP_EOL);
}

foreach(Games::getInstance()->getAll() as $game) {
    $gameID = $game->getVortexID();
    echo 'Game: ' . $gameID . PHP_EOL;
    if(!isset($data['persistent']['mods'][$gameID])) {
        die(sprintf('ERROR: The game [%s] was not found in the database backup file.', $gameID) . PHP_EOL);
    }

    exportGame(
        $game,
        $date,
        $data['persistent']['mods'][$gameID],
        $data['persistent']['categories'][$gameID] ?? array()
    );
}

function exportGame(Game $game, DateTime $databaseDate, array $modsData, array $categoriesData) : void
{
    $gameID = $game->getVortexID();

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
        if (!empty($matches[1]))
        {
            foreach ($matches[0] as $match) {
                $cleanName = str_replace($match, '', $cleanName);
            }

            $cleanName = str_replace(array('[', ']', '?'), '', $cleanName);

            $cleanName = trim($cleanName);

            while(str_contains($cleanName, '  ')) {
                $cleanName = str_replace('  ', ' ', $cleanName);
            }

            $modTags = $matches[1];
            sort($modTags);
            foreach ($matches[1] as $tag) {
                $tag = trim($tag);
                if (!isset($tags[$tag])) {
                    $tags[$tag] = array();
                }
                $tags[$tag][] = $cleanName;
            }
        }

        $category = $categoriesData[$category]['name'] ?? Games::UNKNOWN_CATEGORY_NAME;

        if (!isset($categories[$category])) {
            $categories[$category] = array();
        }

        $categories[$category][] = $cleanName;

        $mods[$cleanName] = array(
            Mod::KEY_TAGGED_NAME => $name,
            Mod::KEY_OFFICIAL_NAME => $attribs['modName'] ?? '',
            Mod::KEY_HOMEPAGE => $attribs['homepage'] ?? '',
            Mod::KEY_CATEGORY => $category,
            Mod::KEY_ENDORSED => $attribs['endorsed'] ?? 'Undecided',
            Mod::KEY_TAGS => $modTags,
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

    $fileName = $gameID.'-modlist.json';

    JSONFile::factory(OUTPUT_FOLDER . '/'.$fileName)
        ->setEscapeSlashes(false)
        ->setTrailingNewline(true)
        ->setPrettyPrint(true)
        ->putData(array(
            Game::KEY_DATA_GAME => $gameID,
            Game::KEY_DATA_DATABASE_DATE => Microtime::createFromDate($databaseDate)->getISODate(true),
            Game::KEY_DATA_EXPORT_DATE => Microtime::createNow()->getISODate(true),
            Game::KEY_DATA_CATEGORIES => $categories,
            Game::KEY_DATA_TAGS => $tags,
            Game::KEY_DATA_MODS => $mods
        ));

    echo "  - DONE, saved to " . $fileName . PHP_EOL;
    echo PHP_EOL;
}

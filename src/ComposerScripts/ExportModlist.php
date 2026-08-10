<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter\ComposerScripts;

use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper\JSONFile;
use AppUtils\Microtime;
use DateTime;
use Mistralys\VortexModExporter\Game;
use Mistralys\VortexModExporter\Games;
use Mistralys\VortexModExporter\Mod;
use Mistralys\VortexModExporter\ModLint\ModLintContext;
use Mistralys\VortexModExporter\ModLint\ModLintIssue;
use Mistralys\VortexModExporter\ModLint\ModLinter;
use Mistralys\VortexModExporter\TagDef;
use Mistralys\VortexModExporter\TagDefs;
use const Mistralys\VortexModExporter\OUTPUT_FOLDER;
use const Mistralys\VortexModExporter\VORTEX_APPDATA_FOLDER;

class ExportModlist
{
    public function export() : void
    {
        $file = JSONFile::factory(VORTEX_APPDATA_FOLDER . '/temp/state_backups_full/manual.json');

        if (!$file->exists()) {
            die('Vortex backup file not found. Have you made a manual database export from the interface?' . PHP_EOL);
        }

        $date = $file->getModifiedDate();
        if ($date === null) {
            die('The backup file does not have a valid date. Please make sure you are using the correct file.' . PHP_EOL);
        }

        $data = $file->getData();

        if (!isset($data['persistent']['mods'])) {
            die('The mods storage key way not found in the database backup file.' . PHP_EOL);
        }

        foreach (Games::getInstance()->getAll() as $game) {
            $gameID = $game->getVortexID();
            echo 'Game: ' . $gameID . PHP_EOL;
            if (!isset($data['persistent']['mods'][$gameID])) {
                die(sprintf('ERROR: The game [%s] was not found in the database backup file.', $gameID) . PHP_EOL);
            }

            $this->exportGame(
                $game,
                $date,
                $data['persistent']['mods'][$gameID],
                $data['persistent']['categories'][$gameID] ?? array()
            );
        }
    }

    private function exportGame(Game $game, DateTime $databaseDate, array $modsData, array $categoriesData) : void
    {
        $gameID = $game->getVortexID();

        echo sprintf('  - Exporting mod list for [%s] mods...', count($modsData)) . PHP_EOL;

        $ignoreDates = $game->getOptions()->areDateTagsIgnored();
        $ignoreUnknown = $game->getOptions()->isUnknownCategoryIgnored();
        $includeUnused = $game->getOptions()->areUnusedModsIncluded();
        $includeUnusedTemp = $game->getOptions()->areTemporarilyUnusedModsIncluded();
        $outputFolder = $game->getOptions()->getOutputFolder();
        $definedTagMap = $game->getDefinedTagNameMap();
        $grantsMap = $game->getGrantsMap();
        $linter = ModLinter::createFromGame($game);

        $mods = array();
        $tags = array();
        $categories = array();
        $lintIssues = array();
        foreach ($modsData as $modData)
        {
            $attribs = $modData['attributes'];
            $name = $attribs['customFileName'] ?? $attribs['fileName'] ?? $attribs['modName'] ?? $attribs['name'] ?? 'Unnamed' ;
            $category = $attribs['category'] ?? 0;

            $unused = str_starts_with($name, Games::PREFIX_UNUSED);

            if($unused && !$includeUnused) {
                continue;
            }

            if ($unused) {
                $name .= ' ['.TagDefs::TAG_UNUSED.']';
            }

            $unusedTemp = str_starts_with($name, Games::PREFIX_AWAIT_UPDATE);

            if($unusedTemp && !$includeUnusedTemp) {
                continue;
            }

            if ($unusedTemp) {
                $name .= ' ['.TagDefs::TAG_UNUSED_TEMPORARILY.']';
            }

            preg_match_all('/\[([^]]+)]/', $name, $matches);
            $keepTags = array();
            $modTagParams = array();
            $cleanName = $name;
            $comments = '';

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

                // Strip parenthetical comments before recording the name in tags so that
                // the tag list and the mods object use the same key.
                $comments = $this->extractParenComments($cleanName);
                if($comments !== '') {
                    preg_match_all('/\([^)]+\)/', $cleanName, $parenMatches);
                    foreach ($parenMatches[0] as $match) {
                        $cleanName = str_replace($match, '', $cleanName);
                    }
                    $cleanName = trim($cleanName);
                    while(str_contains($cleanName, '  ')) {
                        $cleanName = str_replace('  ', ' ', $cleanName);
                    }
                }

                $modTags = $matches[1];

                sort($modTags);
                foreach ($matches[1] as $tag) {
                    $tag = trim($tag);

                    // Parse parameterized tags like [UPD:1.1] into base tag "UPD" and param "1.1".
                    $tagParam = null;
                    if(str_contains($tag, ':')) {
                        [$tag, $tagParam] = explode(':', $tag, 2);
                        $tag = trim($tag);
                        $tagParam = trim($tagParam);
                    }

                    // Normalise tag case to the canonical form from the game definition.
                    $tag = $definedTagMap[strtolower($tag)] ?? $tag;

                    if($ignoreDates && $this->isDate($tag)) {
                        continue;
                    }

                    if(!in_array($tag, $keepTags, true)) {
                        $keepTags[] = $tag;
                    }

                    if($tagParam !== null) {
                        $modTagParams[$tag] = $tagParam;
                    }

                    if (!isset($tags[$tag])) {
                        $tags[$tag] = array();
                    }

                    if(!in_array($cleanName, $tags[$tag], true)) {
                        $tags[$tag][] = $cleanName;
                    }
                }
            }

            // Expand any tags that grant additional tags to this mod.
            // Collect grants from all current tags first to avoid modifying the
            // loop array mid-iteration, then apply them in a second pass.
            $grantedTags = array();
            foreach ($keepTags as $tag) {
                foreach ($grantsMap[$tag] ?? array() as $grantedTag) {
                    $grantedTag = $definedTagMap[strtolower($grantedTag)] ?? $grantedTag;
                    if (!in_array($grantedTag, $keepTags, true) && !in_array($grantedTag, $grantedTags, true)) {
                        $grantedTags[] = $grantedTag;
                    }
                }
            }

            foreach ($grantedTags as $grantedTag) {
                $keepTags[] = $grantedTag;
                if (!isset($tags[$grantedTag])) {
                    $tags[$grantedTag] = array();
                }
                if (!in_array($cleanName, $tags[$grantedTag], true)) {
                    $tags[$grantedTag][] = $cleanName;
                }
            }

            // For mods without tags, parenthetical comments have not been stripped yet.
            if(empty($matches[1])) {
                $comments = $this->extractParenComments($cleanName);
                if($comments !== '') {
                    preg_match_all('/\([^)]+\)/', $cleanName, $parenMatches);
                    foreach ($parenMatches[0] as $match) {
                        $cleanName = str_replace($match, '', $cleanName);
                    }
                    $cleanName = trim($cleanName);
                    while(str_contains($cleanName, '  ')) {
                        $cleanName = str_replace('  ', ' ', $cleanName);
                    }
                }
            }

            $category = $categoriesData[$category]['name'] ?? Games::UNKNOWN_CATEGORY_NAME;

            if($ignoreUnknown && $category === Games::UNKNOWN_CATEGORY_NAME) {
                continue;
            }

            if (!isset($categories[$category])) {
                $categories[$category] = array();
            }

            $categories[$category][] = $cleanName;

            $modEntry = array(
                Mod::KEY_TAGGED_NAME => $name,
                Mod::KEY_OFFICIAL_NAME => $attribs['modName'] ?? '',
                Mod::KEY_HOMEPAGE => $attribs['homepage'] ?? '',
                Mod::KEY_CATEGORY => $category,
                Mod::KEY_ENDORSED => $attribs['endorsed'] ?? 'Undecided',
                Mod::KEY_TAGS => $keepTags,
            );

            if(!empty($modTagParams)) {
                $modEntry[Mod::KEY_TAG_PARAMS] = $modTagParams;
            }

            if($comments !== '') {
                $modEntry[Mod::KEY_COMMENTS] = $comments;
            }

            $mods[$cleanName] = $modEntry;

            array_push($lintIssues, ...$linter->checkMod(new ModLintContext(
                $cleanName,
                $category,
                $keepTags,
                $name
            )));
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

        $gameFolder = FolderInfo::factory(OUTPUT_FOLDER.'/'.$gameID)->create();
        $fileName = 'modlist.json';

        $file = JSONFile::factory($gameFolder.'/'.$fileName)
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

        $this->outputLintIssues($lintIssues);

        $undescribedTags = array_map(
            fn(TagDef $tagDef) => $tagDef->getName(),
            $game->getTagDefs()->getUndescribedTags()
        );

        if (!empty($undescribedTags)) {
            usort($undescribedTags, 'strnatcasecmp');
            echo "  - WARNING: Undescribed tags found: " . implode(', ', $undescribedTags) . PHP_EOL;
        }

        if($outputFolder !== null) {
            $file->copyTo($outputFolder.'/'.$fileName);
            echo "  - Also copied to output folder: ".$outputFolder->getPath().PHP_EOL;
        }

        echo PHP_EOL;
    }

    /**
     * Outputs all lint issues grouped by severity to the CLI.
     *
     * Issues are sorted by type priority (ERROR → WARNING → NOTICE) and then
     * alphabetically by mod name within each group.
     *
     * @param ModLintIssue[] $issues
     */
    private function outputLintIssues(array $issues) : void
    {
        if(empty($issues)) {
            return;
        }

        $order = array(
            ModLintIssue::TYPE_ERROR   => 0,
            ModLintIssue::TYPE_WARNING => 1,
            ModLintIssue::TYPE_NOTICE  => 2,
        );

        usort($issues, static function(ModLintIssue $a, ModLintIssue $b) use ($order) : int {
            $typeCmp = ($order[$a->getType()] ?? 99) <=> ($order[$b->getType()] ?? 99);
            if($typeCmp !== 0) {
                return $typeCmp;
            }
            return strnatcasecmp($a->getModName(), $b->getModName());
        });

        echo sprintf('  - LINT: %d issue(s) found:', count($issues)) . PHP_EOL;
        foreach($issues as $issue) {
            echo $issue->format() . PHP_EOL;
        }
    }

    private function isDate(string $tag) : bool
    {
        return strtotime($tag) !== false;
    }

    /**
     * Extracts text from all parenthesised groups in a mod name and returns
     * them as a normalised, sentence-cased comment string with trailing dots.
     * Returns an empty string when no parentheses are found.
     *
     * Example: "Mod name (Vanilla) (Adds new items)"
     *   → "Vanilla. Adds new items."
     */
    private function extractParenComments(string $name) : string
    {
        preg_match_all('/\(([^)]+)\)/', $name, $matches);

        if(empty($matches[1])) {
            return '';
        }

        $parts = array();
        foreach($matches[1] as $raw) {
            $part = trim($raw);
            if($part === '') {
                continue;
            }
            $part = ucfirst($part);
            if(!str_ends_with($part, '.') && !str_ends_with($part, '!') && !str_ends_with($part, '?')) {
                $part .= '.';
            }
            $parts[] = $part;
        }

        return implode(' ', $parts);
    }
}

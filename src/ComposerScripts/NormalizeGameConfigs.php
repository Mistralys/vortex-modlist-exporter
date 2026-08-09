<?php
/**
 * @package VortexModExporter
 * @subpackage Composer Scripts
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter\ComposerScripts;

use Mistralys\VortexModExporter\Game;
use Mistralys\VortexModExporter\Games;

/**
 * Normalizes all game configuration files in `games/` for VCS stability:
 * sorts options, tag definitions, requires, and grants alphabetically,
 * and strips empty requires arrays.
 *
 * @package VortexModExporter
 * @subpackage Composer Scripts
 */
class NormalizeGameConfigs
{
    public function normalize(): void
    {
        foreach (Games::getInstance()->getAll() as $game) {
            $this->normalizeGame($game);
        }
    }

    private function normalizeGame(Game $game): void
    {
        $gameID = $game->getVortexID();
        echo sprintf('Normalizing [%s] game config...', $gameID) . PHP_EOL;

        $file = $game->getDefinitionFile();
        $data = $file->getData();

        if (isset($data[Game::KEY_DEF_OPTIONS]) && is_array($data[Game::KEY_DEF_OPTIONS])) {
            ksort($data[Game::KEY_DEF_OPTIONS]);
        }

        if (isset($data[Game::KEY_DEF_TAG_DEFINITIONS]) && is_array($data[Game::KEY_DEF_TAG_DEFINITIONS])) {
            ksort($data[Game::KEY_DEF_TAG_DEFINITIONS]);

            foreach ($data[Game::KEY_DEF_TAG_DEFINITIONS] as &$tagDef) {
                if (array_key_exists('requires', $tagDef)) {
                    if (empty($tagDef['requires'])) {
                        unset($tagDef['requires']);
                    } else {
                        sort($tagDef['requires']);
                    }
                }

                if (!empty($tagDef['grants'])) {
                    sort($tagDef['grants']);
                }
            }
            unset($tagDef);
        }

        $file
            ->setPrettyPrint(true)
            ->setEscapeSlashes(false)
            ->setTrailingNewline(true)
            ->putData($data);

        echo '  Done.' . PHP_EOL;
    }
}

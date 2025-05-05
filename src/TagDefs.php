<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\Collections\BaseStringPrimaryCollection;

/**
 * @package VortexModExporter
 * @subpackage Tag Definitions
 *
 * @method TagDef getByID(string $id)
 * @method TagDef getDefault()
 * @method TagDef[] getAll()
 */
class TagDefs extends BaseStringPrimaryCollection
{
    private Game $game;
    private array $tagDefs;

    public function __construct(Game $game, $tagDefs)
    {
        $this->game = $game;
        $this->tagDefs = $tagDefs;
    }

    public function getDefaultID(): string
    {
        return $this->getAutoDefault();
    }

    protected function registerItems(): void
    {
        foreach ($this->tagDefs as $tagName => $tagDef) {
            if (is_array($tagDef)) {
                $this->registerTag((string)$tagName, $tagDef);
            }
        }

        foreach($this->game->getTagNames() as $tagName) {
            if(!$this->idExists($tagName)) {
                $this->registerTag($tagName, array());
            }
        }

        foreach($this->game->getModNamesByTags() as $tagName => $modNames) {
            $tagName = (string)$tagName;
            $this->getByID($tagName)->registerMods($modNames);
        }
    }

    private function registerTag(string $name, array $tagDef) : void
    {
        $this->registerItem(new TagDef(
            $this,
            $name,
            $tagDef['label'] ?? '',
            $tagDef['description'] ?? '',
            $tagDef['requires'] ?? array(),
            $tagDef['url'] ?? null
        ));
    }
}

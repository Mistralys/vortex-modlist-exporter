<?php
/**
 * @package VortexModExporter
 * @subpackage Tag Definitions
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\Collections\BaseStringPrimaryCollection;

/**
 * Stores all tag definitions that have been added in
 * a game's configuration file.
 *
 * @package VortexModExporter
 * @subpackage Tag Definitions
 *
 * @method TagDef getByID(string $id)
 * @method TagDef getDefault()
 * @method TagDef[] getAll()
 */
class TagDefs extends BaseStringPrimaryCollection
{
    public const TAG_UNUSED = 'Unused';
    public const TAG_UNUSED_TEMPORARILY = 'UnusedTemp';

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
        $this->registerTagDefs();
        $this->registerCannedTags();
        $this->registerGameTags();
        $this->registerTagMods();
    }

    private function registerTagDefs() : void
    {
        foreach ($this->tagDefs as $tagName => $tagDef) {
            if (is_array($tagDef)) {
                $this->registerTag((string)$tagName, $tagDef);
            }
        }
    }

    private function registerGameTags() : void
    {
        foreach($this->game->getTagNames() as $tagName) {
            if(!$this->idExists($tagName)) {
                $this->registerTag($tagName, array());
            }
        }
    }

    private function registerCannedTags() : void
    {
        foreach($this->getCannedTags() as $tagName => $tagDef) {
            if(!$this->idExists($tagName)) {
                $this->registerTag($tagName, $tagDef);
            }
        }
    }

    /**
     * Connects tags with the mods that have been assigned to them.
     */
    public function registerTagMods(): void
    {
        foreach ($this->game->getModNamesByTags() as $tagName => $modNames) {
            $tagName = (string)$tagName;
            $this->getByID($tagName)->registerMods($modNames);
        }
    }

    private function getCannedTags() : array
    {
        return array(
            self::TAG_UNUSED => array(
                'description' => "These mods are unused for a variety of reasons,\nfrom being broken to not matching expected quality standards."
            ),
            self::TAG_UNUSED_TEMPORARILY => array(
                'label' => 'Unused Temporarily',
                'description' => "These mods are temporarily unused for a variety of\nreasons. For example, because they are waiting for an update."
            )
        );
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

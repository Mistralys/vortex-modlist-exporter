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

    /**
     * Case-insensitive lookup map: lowercase tag name => canonical tag name.
     * Built from explicitly defined tags (config) and canned tags.
     *
     * @var array<string,string>
     */
    private array $canonicalTagMap = array();

    public function __construct(Game $game, $tagDefs)
    {
        $this->game = $game;
        $this->tagDefs = $tagDefs;
    }

    public function getDefaultID(): string
    {
        return $this->getAutoDefault();
    }

    /**
     * Returns all tags that are not defined in the game configuration file
     * or canned tags.
     *
     * @return TagDef[]
     */
    public function getUndescribedTags() : array
    {
        $result = array();

        foreach ($this->getAll() as $tagDef) {
            if (!$tagDef->isDefined()) {
                $result[] = $tagDef;
            }
        }

        return $result;
    }

    /**
     * Resolves a tag name to its canonical form using a case-insensitive lookup
     * against the defined tags. Returns the original name unchanged when no
     * match is found.
     *
     * Note: must be called after registerTagDefs() and registerCannedTags() have
     * run (i.e., from registerGameTags() onwards during registerItems()).
     */
    public function resolveTagName(string $tagName) : string
    {
        return $this->canonicalTagMap[strtolower($tagName)] ?? $tagName;
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
                $tagName = (string)$tagName;
                $this->canonicalTagMap[strtolower($tagName)] = $tagName;
                $this->registerTag($tagName, $tagDef, true);
            }
        }
    }

    private function registerGameTags() : void
    {
        foreach($this->game->getTagNames() as $tagName) {
            $tagName = $this->resolveTagName($tagName);
            if(!$this->idExists($tagName)) {
                $this->registerTag($tagName, array(), false);
            }
        }
    }

    private function registerCannedTags() : void
    {
        foreach($this->getCannedTags() as $tagName => $tagDef) {
            if(!$this->idExists($tagName)) {
                $this->canonicalTagMap[strtolower($tagName)] = $tagName;
                $this->registerTag($tagName, $tagDef, true);
            }
        }
    }

    /**
     * Connects tags with the mods that have been assigned to them,
     * including any previous-version parameter stored per mod.
     */
    public function registerTagMods(): void
    {
        foreach ($this->game->getModNamesByTags() as $tagName => $modNames) {
            $tagName = $this->resolveTagName((string)$tagName);
            $tagDef = $this->getByID($tagName);
            foreach ($modNames as $modName) {
                $previousVersion = $this->game->getModTagParam($modName, $tagName);
                $tagDef->registerMod($modName, $previousVersion);

                // Propagate to all transitively required tags (implements the transitive
                // tag membership promise of the `requires` field).
                foreach ($tagDef->getInherited() as $requiredTagName) {
                    $this->getByID($requiredTagName)->registerMod($modName);
                }
            }
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

    private function registerTag(string $name, array $tagDef, bool $defined = true) : void
    {
        $this->registerItem(new TagDef(
            $this,
            $name,
            $tagDef['label'] ?? '',
            $tagDef['description'] ?? '',
            $tagDef['requires'] ?? array(),
            $tagDef['url'] ?? null,
            $defined,
            array_map('strval', (array)($tagDef['grants'] ?? array()))
        ));
    }
}

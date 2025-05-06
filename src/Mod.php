<?php
/**
 * @package VortexModExporter
 * @subpackage Games
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\ArrayDataCollection;
use AppUtils\Interfaces\StringPrimaryRecordInterface;

/**
 * Stores information about a single mod.
 *
 * @package VortexModExporter
 * @subpackage Mods
 */
class Mod implements StringPrimaryRecordInterface
{
    public const KEY_TAGGED_NAME = 'taggedName';
    public const KEY_OFFICIAL_NAME = 'officialName';
    public const KEY_HOMEPAGE = 'homepage';
    public const KEY_CATEGORY = 'category';
    public const KEY_ENDORSED = 'endorsed';
    public const KEY_TAGS = 'tags';

    private Game $game;
    private string $name;
    private ArrayDataCollection $data;

    public function __construct(Game $game, string $name, ArrayDataCollection $data)
    {
        $this->game = $game;
        $this->name = $name;
        $this->data = $data;
    }

    public function getID(): string
    {
        return $this->getName();
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTaggedName() : string
    {
        return $this->data->getString(self::KEY_TAGGED_NAME);
    }

    public function getOfficialName() : string
    {
        return $this->data->getString(self::KEY_OFFICIAL_NAME);
    }

    public function getHomepage() : string
    {
        return $this->data->getString(self::KEY_HOMEPAGE);
    }

    public function getCategory() : string
    {
        return $this->data->getString(self::KEY_CATEGORY);
    }

    public function isEndorsed() : bool
    {
        return strtolower($this->data->getString(self::KEY_ENDORSED)) === 'endorsed';
    }

    /**
     * @return string[]
     */
    public function getTags() : array
    {
        return $this->data->getArray(self::KEY_TAGS);
    }

    public function getInheritedTags() : array
    {
        $tagDefs = $this->game->getTagDefs();

        $tags = array();
        foreach ($this->getTags() as $tagName) {
            if(!$tagDefs->idExists($tagName)) {
                echo "Warning: Tag [$tagName] does not exist in tag definitions.\n";
                continue;
            }
            $tagDef = $tagDefs->getByID($tagName);
            array_push($tags, $tagName, ...$tagDef->getInherited());
        }

        $tags = array_unique($tags);

        usort($tags, 'strnatcasecmp');

        return $tags;
    }

    public function getData(): ArrayDataCollection
    {
        return $this->data;
    }
}

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
    public const KEY_TAG_PARAMS = 'tagParams';
    public const KEY_COMMENTS = 'comments';

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

    /**
     * Returns the parameter map for tags that carry a parameter, e.g.
     * a mod tagged [UPD:1.1] contributes ['UPD' => '1.1'].
     *
     * @return array<string,string> tagName => parameter
     */
    public function getTagParams() : array
    {
        return $this->data->getArray(self::KEY_TAG_PARAMS);
    }

    /**
     * Returns the parameter value for a specific tag, or null when
     * no parameter was stored for that tag.
     *
     * @param string $tagName
     * @return string|null
     */
    public function getTagParam(string $tagName) : ?string
    {
        $params = $this->getTagParams();
        return $params[$tagName] ?? null;
    }

    /**
     * Returns comments extracted from parenthesised groups in the mod name,
     * normalised as a sentence-cased string with trailing dots.
     * Returns an empty string when no comments were recorded.
     */
    public function getComments() : string
    {
        return $this->data->getString(self::KEY_COMMENTS);
    }

    public function getInheritedTags() : array
    {
        $tagDefs = $this->game->getTagDefs();

        $tags = array();
        foreach ($this->getTags() as $tagName) {
            $tagName = $tagDefs->resolveTagName($tagName);
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

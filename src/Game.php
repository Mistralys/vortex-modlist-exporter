<?php
/**
 * @package VortexModExporter
 * @subpackage Games
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\ArrayDataCollection;
use AppUtils\FileHelper\JSONFile;
use AppUtils\Interfaces\StringPrimaryRecordInterface;
use DateTime;

/**
 * Stores information about a game and its mods.
 *
 * @package VortexModExporter
 * @subpackage Games
 */
class Game implements StringPrimaryRecordInterface
{
    public const KEY_DATA_GAME = 'game';
    public const KEY_DATA_EXPORT_DATE = 'exportDate';
    public const KEY_DATA_MODS = 'mods';
    public const KEY_DATA_TAGS = 'tags';
    public const KEY_DATA_CATEGORIES = 'categories';
    public const KEY_DATA_DATABASE_DATE = 'databaseDate';
    public const KEY_DEF_LABEL = 'label';
    public const KEY_DEF_TAG_DEFINITIONS = 'tagDefinitions';
    public const KEY_DEF_OPTIONS = 'options';
    public const KEY_DEF_RULES = 'rules';

    private ?ArrayDataCollection $data = null;
    private ?ArrayDataCollection $definition = null;
    private JSONFile $definitionFile;
    private JSONFile $dataFile;

    public function __construct(JSONFile $definitionFile)
    {
        $this->definitionFile = $definitionFile;
        $this->dataFile = JSONFile::factory(OUTPUT_FOLDER.'/'.$this->getVortexID().'-modlist.json');
    }

    public function getID(): string
    {
        return $this->getVortexID();
    }

    public function getVortexID(): string
    {
        return $this->definitionFile->getBaseName();
    }

    public function getLabel() : string
    {
        return $this->getDefinition()->getString(self::KEY_DEF_LABEL);
    }

    public function getExportDate() : DateTime
    {
        return $this->getData()->getDateTime(self::KEY_DATA_EXPORT_DATE);
    }

    public function getDatabaseDate() : DateTime
    {
        return $this->getData()->getDateTime(self::KEY_DATA_DATABASE_DATE);
    }

    private ?GameOptions $options = null;

    public function getOptions() : GameOptions
    {
        if(!isset($this->options)) {
            $this->options = new GameOptions($this->getDefinition()->getArray(self::KEY_DEF_OPTIONS));
        }

        return $this->options;
    }

    public function getDefinitionFile(): JSONFile
    {
        return $this->definitionFile;
    }

    private function getDefinition() : ArrayDataCollection
    {
        if(!isset($this->definition)) {
            $data = array();
            if($this->definitionFile->exists()) {
                $data = $this->definitionFile->getData();
            }

            $this->definition = ArrayDataCollection::create($data);
        }

        return $this->definition;
    }

    private function getData() : ArrayDataCollection
    {
        if(!isset($this->data)) {
            $this->data = ArrayDataCollection::create($this->dataFile->getData());
        }

        return $this->data;
    }

    private ?TagDefs $tagDefs = null;

    public function getTagDefs() : TagDefs
    {
        if(!isset($this->tagDefs)) {
            $this->tagDefs = new TagDefs($this, $this->getDefinition()->getArray(self::KEY_DEF_TAG_DEFINITIONS));
        }

        return $this->tagDefs;
    }

    private ?Mods $mods = null;

    public function getMods() : Mods
    {
        if(!isset($this->mods)) {
            $this->mods = new Mods($this);
        }

        return $this->mods;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getModData() : array
    {
        return $this->getData()->getArray(self::KEY_DATA_MODS);
    }

    /**
     * @return string[]
     */
    public function getTagNames() : array
    {
        // Because keys can be numeric and will be converted to INT by PHP.
        return array_map('strval', array_keys($this->getModNamesByTags()));
    }

    /**
     * @return array<string|int,string[]> Tag name => mod names
     */
    public function getModNamesByTags() : array
    {
        $result = array();
        foreach($this->getData()->getArray(self::KEY_DATA_TAGS) as $tag => $modNames) {
            if(!is_array($modNames)) {
                continue;
            }

            $result[$tag] = array();

            foreach($modNames as $modName) {
                $result[$tag][] = (string)$modName;
            }
        }

        return $result;
    }

    /**
     * Returns a case-insensitive lookup map of tag names defined in the game
     * configuration file and the built-in canned tags.
     *
     * Keys are lower-cased tag names; values are the canonical names as written
     * in the definition file. Used during export to normalise tag names extracted
     * from Vortex mod names.
     *
     * @return array<string,string> lowercase => canonical tag name
     */
    public function getDefinedTagNameMap() : array
    {
        $tagDefs = $this->getDefinition()->getArray(self::KEY_DEF_TAG_DEFINITIONS);
        $map = array();
        foreach(array_keys($tagDefs) as $tagName) {
            $tagName = (string)$tagName;
            $map[strtolower($tagName)] = $tagName;
        }
        $map[strtolower(TagDefs::TAG_UNUSED)] = TagDefs::TAG_UNUSED;
        $map[strtolower(TagDefs::TAG_UNUSED_TEMPORARILY)] = TagDefs::TAG_UNUSED_TEMPORARILY;
        return $map;
    }

    /**
     * Returns a map of tag name to the list of tag names it grants. When a mod
     * carries a tag that has grants, the granted tags are automatically added to
     * the mod during export as if the user had tagged the mod explicitly.
     *
     * @return array<string,string[]> tag name => granted tag names
     */
    public function getGrantsMap() : array
    {
        $tagDefs = $this->getDefinition()->getArray(self::KEY_DEF_TAG_DEFINITIONS);
        $map = array();
        foreach ($tagDefs as $tagName => $tagDef) {
            $tagName = (string)$tagName;
            if (is_array($tagDef) && !empty($tagDef['grants'])) {
                $map[$tagName] = array_map('strval', (array)$tagDef['grants']);
            }
        }
        return $map;
    }

    /**
     * Returns the stored parameter for a specific mod/tag combination, or null
     * when no parameter was recorded (e.g. the tag had no colon-delimited value).
     *
     * @param string $modName  Clean mod name (without bracket tags)
     * @param string $tagName  Base tag name (e.g. "UPD")
     * @return string|null
     */
    public function getModTagParam(string $modName, string $tagName) : ?string
    {
        $modData = $this->getModData();
        $tagParams = $modData[$modName][Mod::KEY_TAG_PARAMS] ?? array();
        return $tagParams[$tagName] ?? null;
    }

    /**
     * Returns the raw rules configuration array from the game definition file.
     * Each entry is an associative array with at least a `"name"` key matching
     * the rule's registered name in {@see \Mistralys\VortexModExporter\ModLint\ModLinter}.
     *
     * Returns an empty array when no `rules` key is present.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getRulesConfig() : array
    {
        return $this->getDefinition()->getArray(self::KEY_DEF_RULES);
    }
}

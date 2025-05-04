<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\ArrayDataCollection;
use AppUtils\FileHelper\JSONFile;
use AppUtils\Interfaces\StringPrimaryRecordInterface;
use DateTime;

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
}
<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\Interfaces\StringPrimaryRecordInterface;

class TagDef implements StringPrimaryRecordInterface
{
    private string $name;
    private string $label;

    /**
     * @var string[]
     */
    private array $requires;
    private ?string $url;

    /**
     * @var string[]
     */
    private array $modNames = array();
    private TagDefs $collection;

    public function __construct(TagDefs $collection, string $name, string $label, array $requires=array(), ?string $url=null)
    {
        $this->collection = $collection;
        $this->name = $name;
        $this->label = $label;
        $this->requires = $requires;
        $this->url = $url;
    }

    public function getID(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        if(!empty($this->label)) {
            return $this->label;
        }

        return null;
    }

    public function getRequires(): array
    {
        return $this->requires;
    }

    public function getURL(): ?string
    {
        return $this->url;
    }

    public function registerMod(string $modName) : void
    {
        if(in_array($modName, $this->modNames)) {
            return;
        }

        $this->modNames[] = $modName;
    }

    /**
     * @param string[] $mods
     * @return void
     */
    public function registerMods(array $mods) : void
    {
        foreach($mods as $modName) {
            $this->registerMod($modName);
        }
    }

    /**
     * @return string[]
     */
    public function getModNames() : array
    {
        return $this->modNames;
    }

    public function getInherited() : array
    {
        $inherited = array();

        foreach($this->getRequires() as $requiredTag)
        {
            $tagDef = $this->collection->getByID($requiredTag);
            array_push($inherited, $requiredTag, ...$tagDef->getInherited());
        }

        return array_unique($inherited);
    }
}

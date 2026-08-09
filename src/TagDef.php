<?php
/**
 * @package VortexModExporter
 * @subpackage Tag Definitions
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\Interfaces\StringPrimaryRecordInterface;

/**
 * Stores information on a tag described in a game's
 * configuration file.
 *
 * @package VortexModExporter
 * @subpackage Tag Definitions
 */
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
     * Tag names that are automatically added to a mod when it carries this tag.
     *
     * @var string[]
     */
    private array $grants;

    /**
     * @var string[]
     */
    private array $modNames = array();

    /**
     * Map of mod name to the previous version captured from a parameterized tag,
     * e.g. [UPD:1.1] stores 'ModName' => '1.1'.
     *
     * @var array<string,string>
     */
    private array $previousVersions = array();

    private TagDefs $collection;
    private string $description;

    private bool $defined = false;

    public function __construct(TagDefs $collection, string $name, string $label, string $description, array $requires=array(), ?string $url=null, bool $defined=false, array $grants=array())
    {
        $this->collection = $collection;
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
        $this->requires = $requires;
        $this->url = $url;
        $this->defined = $defined;
        $this->grants = $grants;
    }

    public function isDefined(): bool
    {
        return $this->defined;
    }

    public function getID(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
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

    /**
     * Returns the tag names that are automatically added to a mod when it
     * carries this tag. These are expanded during export and written into
     * the mod's tag list as if the user had added them explicitly.
     *
     * @return string[]
     */
    public function getGrants(): array
    {
        return $this->grants;
    }

    public function getURL(): ?string
    {
        return $this->url;
    }

    public function registerMod(string $modName, ?string $previousVersion = null) : void
    {
        if(in_array($modName, $this->modNames)) {
            return;
        }

        $this->modNames[] = $modName;

        if($previousVersion !== null) {
            $this->previousVersions[$modName] = $previousVersion;
        }
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
     * Returns the map of mod names to their previous version for this tag.
     * Only mods tagged with a parameter (e.g. [UPD:1.1]) appear here.
     *
     * @return array<string,string> modName => previousVersion
     */
    public function getPreviousVersions() : array
    {
        return $this->previousVersions;
    }

    /**
     * Returns the previous version stored for a specific mod, or null when
     * the mod was tagged without a version parameter.
     *
     * @param string $modName
     * @return string|null
     */
    public function getPreviousVersion(string $modName) : ?string
    {
        return $this->previousVersions[$modName] ?? null;
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

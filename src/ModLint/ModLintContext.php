<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter\ModLint;

/**
 * Snapshot of a single mod's properties passed to each lint rule during export.
 *
 * Construct one of these for every mod entry and hand it to {@see ModLinter::checkMod()}.
 *
 * @package VortexModExporter
 * @subpackage ModLint
 */
class ModLintContext
{
    private string $modName;
    private string $category;
    /** @var string[] */
    private array $tags;
    private string $taggedName;

    /**
     * @param string   $modName    Clean mod name (tags stripped).
     * @param string   $category   Category label resolved from Vortex data.
     * @param string[] $tags       Canonical tag names on this mod (direct only, no inherited).
     * @param string   $taggedName Original tagged name including [Tag] brackets.
     */
    public function __construct(
        string $modName,
        string $category,
        array  $tags,
        string $taggedName
    ) {
        $this->modName    = $modName;
        $this->category   = $category;
        $this->tags       = $tags;
        $this->taggedName = $taggedName;
    }

    /**
     * Clean mod name (all [Tag] brackets and parenthesised comments stripped).
     */
    public function getModName(): string
    {
        return $this->modName;
    }

    /**
     * Category label as resolved from the Vortex database, e.g. "Armour and Clothing".
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * All direct (non-inherited) canonical tag names on this mod.
     *
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Full original mod name including [Tag] brackets.
     */
    public function getTaggedName(): string
    {
        return $this->taggedName;
    }

    /**
     * Returns true when the mod's category matches $category (case-insensitive).
     */
    public function isCategoryMatch(string $category): bool
    {
        return strcasecmp($this->category, $category) === 0;
    }

    /**
     * Returns true when the mod has the given tag (case-insensitive comparison).
     */
    public function hasTag(string $tagName): bool
    {
        $needle = strtolower($tagName);
        foreach ($this->tags as $tag) {
            if (strtolower($tag) === $needle) {
                return true;
            }
        }
        return false;
    }
}

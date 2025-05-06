<?php
/**
 * @package VortexModExporter
 * @subpackage Games
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\ArrayDataCollection;

/**
 * Stores game-related options.
 *
 * @package VortexModExporter
 * @subpackage Games
 */
class GameOptions extends ArrayDataCollection
{
    public const KEY_IGNORE_DATE_TAGS = 'ignoreDateTags';
    public const KEY_IGNORE_UNKNOWN_CATEGORY = 'ignoreUnknownCategory';
    public const KEY_DEF_INCLUDE_UNUSED_MODS = 'includeUnusedMods';
    public const KEY_DEF_INCLUDE_TEMPORARILY_UNUSED = 'includeTemporarilyUnusedMods';

    /**
     * Whether to ignore all tags that are based on a date.
     * They will be excluded from the mod list and documents.
     *
     * @return bool
     */
    public function areDateTagsIgnored() : bool
    {
        return $this->getBool(self::KEY_IGNORE_DATE_TAGS);
    }

    /**
     * Whether to ignore all mods that have an unknown category.
     * They will be excluded from the mod list and documents.
     *
     * @return bool
     */
    public function isUnknownCategoryIgnored() : bool
    {
        return $this->getBool(self::KEY_IGNORE_UNKNOWN_CATEGORY);
    }

    public function areUnusedModsIncluded() : bool
    {
        return $this->getBool(self::KEY_DEF_INCLUDE_UNUSED_MODS);
    }

    public function areTemporarilyUnusedModsIncluded() : bool
    {
        return $this->getBool(self::KEY_DEF_INCLUDE_TEMPORARILY_UNUSED);
    }
}

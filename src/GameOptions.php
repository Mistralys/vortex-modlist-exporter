<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\ArrayDataCollection;

class GameOptions extends ArrayDataCollection
{
    public const KEY_IGNORE_DATE_TAGS = 'ignoreDateTags';
    public const KEY_IGNORE_UNKNOWN_CATEGORY = 'ignoreUnknownCategory';

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
}

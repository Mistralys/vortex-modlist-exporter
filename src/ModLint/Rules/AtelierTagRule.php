<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter\ModLint\Rules;

use Mistralys\VortexModExporter\ModLint\ModLintContext;
use Mistralys\VortexModExporter\ModLint\ModLintIssue;
use Mistralys\VortexModExporter\ModLint\ModLintRuleInterface;

/**
 * Warns when a mod belongs to the "Armour and Clothing" category but does not
 * carry the "Atelier" tag.
 *
 * The Atelier tag indicates the mod ships as an Atelier store, which is the
 * standard distribution mechanism for clothing mods in Cyberpunk 2077. The
 * vast majority of clothing mods that omit this tag are missing it by accident.
 *
 * The category and tag comparisons are both case-insensitive.
 *
 * @package VortexModExporter
 * @subpackage ModLint\Rules
 */
class AtelierTagRule implements ModLintRuleInterface
{
    public const CATEGORY = 'Armour and Clothing';
    public const TAG      = 'Atelier';

    public function check(ModLintContext $context): array
    {
        if (!$context->isCategoryMatch(self::CATEGORY)) {
            return [];
        }

        if ($context->hasTag(self::TAG)) {
            return [];
        }

        return [
            new ModLintIssue(
                ModLintIssue::TYPE_WARNING,
                $context->getModName(),
                sprintf(
                    'Category "%s" but missing the "%s" tag.',
                    self::CATEGORY,
                    self::TAG
                )
            ),
        ];
    }
}

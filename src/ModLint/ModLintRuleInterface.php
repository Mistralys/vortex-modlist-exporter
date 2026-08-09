<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter\ModLint;

/**
 * Contract for a single mod lint rule.
 *
 * Implement this interface to define a self-contained check that inspects a
 * mod's properties via {@see ModLintContext} and returns any number of
 * {@see ModLintIssue} objects (empty array = no problems found).
 *
 * Register new rules via {@see ModLinter::addRule()}.
 *
 * @package VortexModExporter
 * @subpackage ModLint
 */
interface ModLintRuleInterface
{
    /**
     * Inspect the given mod context and return any issues found.
     *
     * Return an empty array when the mod passes the rule cleanly.
     *
     * @param ModLintContext $context Snapshot of the mod being evaluated.
     * @return ModLintIssue[]
     */
    public function check(ModLintContext $context): array;
}

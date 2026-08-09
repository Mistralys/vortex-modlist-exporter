<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter\ModLint;

use Mistralys\VortexModExporter\Game;
use Mistralys\VortexModExporter\ModLint\Rules\AtelierTagRule;

/**
 * Runs all registered lint rules against a mod and collects the results.
 *
 * ## Adding a new rule
 *
 * 1. Create a class in `src/ModLint/Rules/` that implements {@see ModLintRuleInterface}.
 * 2. Register it in {@see self::RULE_REGISTRY} with a unique name string.
 *
 * ## Enabling a rule for a game
 *
 * Add an entry to the game's JSON config under `"rules"`:
 * ```json
 * "rules": [
 *     { "name": "AtelierTagRule" }
 * ]
 * ```
 *
 * @package VortexModExporter
 * @subpackage ModLint
 */
class ModLinter
{
    /**
     * Maps rule name strings (as used in game config JSON) to their implementing class names.
     *
     * To register a new rule, add an entry here.
     *
     * @var array<string, class-string<ModLintRuleInterface>>
     */
    private const RULE_REGISTRY = [
        'AtelierTagRule' => AtelierTagRule::class,
    ];

    /** @var ModLintRuleInterface[] */
    private array $rules = [];

    /**
     * Creates a linter pre-loaded with the rules declared in the given game's config.
     *
     * If a rule name from the config is not present in {@see self::RULE_REGISTRY},
     * a warning is printed to the CLI and that entry is skipped.
     *
     * Returns an empty linter (no rules) when the game config has no `rules` key.
     */
    public static function createFromGame(Game $game): self
    {
        $linter = new self();

        foreach ($game->getRulesConfig() as $ruleConfig) {
            $name = (string)($ruleConfig['name'] ?? '');

            if ($name === '') {
                echo sprintf(
                    '  - LINT WARNING: Rule entry in [%s] config is missing the "name" key. Skipping.' . PHP_EOL,
                    $game->getVortexID()
                );
                continue;
            }

            if (!isset(self::RULE_REGISTRY[$name])) {
                echo sprintf(
                    '  - LINT WARNING: Unknown rule name "%s" in [%s] config. Skipping.' . PHP_EOL,
                    $name,
                    $game->getVortexID()
                );
                continue;
            }

            $class = self::RULE_REGISTRY[$name];
            $linter->addRule(new $class());
        }

        return $linter;
    }

    /**
     * Registers a rule with the linter.
     *
     * Returns the linter instance to allow fluent chaining.
     */
    public function addRule(ModLintRuleInterface $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * Runs all registered rules against a single mod context.
     *
     * @param ModLintContext $context Snapshot of the mod being evaluated.
     * @return ModLintIssue[]        All issues raised across every rule (may be empty).
     */
    public function checkMod(ModLintContext $context): array
    {
        $issues = [];
        foreach ($this->rules as $rule) {
            array_push($issues, ...$rule->check($context));
        }
        return $issues;
    }
}

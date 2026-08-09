

## Synthesis

### Completion Status
- Date: 2026-08-09
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Outcome Summary

Implemented support for parameterized tags in the `[TAG:param]` syntax. Tags of this form are now exported under their base name (e.g. `UPD` for `[UPD:1.1]`), and the parameter (the previous mod version) is preserved in the mod's `tagParams` field in `modlist.json` and surfaced through `TagDef::getPreviousVersion()`. The generated tags reference document now displays the previous version next to each mod listed under a parameterized tag.

### Implementation Summary
- **`ExportModlist.php`**: Tag strings containing `:` are split into base tag name and parameter. Base name is stored in the `tags` map and the mod's `tags` array; the parameter is collected in `$modTagParams` and written to the mod entry under `Mod::KEY_TAG_PARAMS` when non-empty. Duplicate base tag names within one mod are deduplicated.
- **`Mod.php`**: Added `KEY_TAG_PARAMS` constant and `getTagParams(): array` / `getTagParam(string $tagName): ?string` methods.
- **`TagDef.php`**: Added `$previousVersions` map, updated `registerMod()` to accept `?string $previousVersion`, and added `getPreviousVersions(): array` / `getPreviousVersion(string $modName): ?string` getters.
- **`TagDefs.php`**: Rewrote `registerTagMods()` to pass the per-mod tag parameter (looked up via `Game::getModTagParam()`) when registering each mod against its tag definition.
- **`Game.php`**: Added `getModTagParam(string $modName, string $tagName): ?string` to read tag parameters from the stored mod data.
- **`GenerateDocs.php`**: Tags reference now appends ` (prev. v{version})` to a mod's entry when a previous version is recorded for it under that tag.

### Documentation Updates
- `docs/agents/project-manifest/constraints.md`: Documented the `[TAG:param]` colon syntax, the `UPD` canonical example, and the storage/surfacing of the parameter.
- `docs/agents/project-manifest/api-surface.md`: Updated `Mod`, `TagDef`, and `Game` API signatures to include the new constants and methods.

### Verification Summary
- Tests run: PHP syntax check (`php -l`) on all six modified source files
- Static analysis run: None (no PHPStan configured for the project)
- Result: PASS — No syntax errors detected in any file

### Code Insights
- [low] (debt) `src/ComposerScripts/ExportModlist.php`: `$modTags = $matches[1]; sort($modTags);` — `$modTags` is sorted but then the loop iterates over `$matches[1]` instead. The sort is a no-op.
- [low] (convention) `src/ComposerScripts/GenerateDocs.php`: The three `use function` imports at the top (`resolveTitle`, `writeGameModsReference`, `writeGameTagsReference`) reference functions that do not exist globally — they are private methods of the class and should be removed.
- [low] (improvement) `src/TagDef.php` / `src/TagDefs.php`: `registerMods(array $mods)` is now only used in legacy paths and bypasses the per-mod previous version lookup. It could be made `@internal` or removed to prevent incorrect usage.

### Additional Comments
- The feature is backward-compatible: existing `modlist.json` files without `tagParams` keys continue to work — `getTagParams()` returns an empty array and `getPreviousVersion()` returns null.
- The previous version is stored as a plain string without validation. Future iterations could enforce a version format (e.g., semver) if needed.

---

## Synthesis

### Completion Status
- Date: 2026-08-09
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Outcome Summary

Implemented case-insensitive tag matching. Tags extracted from Vortex mod names (e.g. `[Redscript]`) are now normalised to the canonical casing from the game's `tagDefinitions` config (e.g. `RedScript`) both during export and during document generation. Existing `modlist.json` files with wrong-case tags are also handled transparently at runtime.

### Implementation Summary
- **`Game.php`**: Added `getDefinedTagNameMap(): array` — builds a lightweight `lowercase → canonical` lookup map from the definition file's `tagDefinitions` keys and the two canned tags (`Unused`, `UnusedTemp`). Reads only from the definition file; never touches `modlist.json`.
- **`ExportModlist.php`**: Fetches `$definedTagMap` at the start of `exportGame()` and applies it immediately after extracting each tag name, before storing it in `$keepTags`, `$tags`, or `$modTagParams`. This ensures `modlist.json` always stores the canonical form.
- **`TagDefs.php`**: Added private `$canonicalTagMap` property (lowercase → canonical). Populated in `registerTagDefs()` and `registerCannedTags()` as tags are registered. Added `resolveTagName(string): string` method used in `registerGameTags()` (to avoid duplicating defined tags under a different case) and `registerTagMods()` (to look up the correct `TagDef` even when the stored tag name has wrong case).
- **`Mod.php`**: `getInheritedTags()` now calls `$tagDefs->resolveTagName($tagName)` before `idExists()` / `getByID()`, so backward-compatible data from old exports does not trigger spurious warnings.

### Documentation Updates
- `docs/agents/project-manifest/constraints.md`: Added a bullet documenting the case-insensitivity rule, which lookup map is used, and the fallback behaviour for unrecognised tags.
- `docs/agents/project-manifest/api-surface.md`: Added `Game::getDefinedTagNameMap()` and `TagDefs::resolveTagName()` to the public API surface.

### Verification Summary
- Tests run: `composer build` (export + doc generation for all configured games)
- Static analysis run: None (no PHPStan configured for the project)
- Result: PASS — Build completed without errors. Confirmed that `"RedScript"` appears as the sole tag key in `cyberpunk2077-modlist.json`; lowercase occurrences are mod names, not tag keys.

### Code Insights
- [low] (improvement) `src/TagDefs.php`: `resolveTagName()` has a note that it "must be called after registerTagDefs() and registerCannedTags()". This ordering constraint is implicit and could silently break if `registerItems()` order changes. Consider making `canonicalTagMap` a lazy-built property to remove the ordering dependency.
- [low] (convention) `src/TagDefs.php`: The `$canonicalTagMap` is only populated for *defined* tags (those registered by `registerTagDefs` and `registerCannedTags`). Auto-discovered tags from `registerGameTags()` are deliberately excluded. This distinction is intentional but worth documenting in a docblock on `resolveTagName()`.

### Additional Comments
- The fix is fully backward-compatible: `modlist.json` files written before this change may have wrong-case tags; they are normalised at runtime by `TagDefs::resolveTagName()` during doc generation.

---

## Synthesis

### Completion Status
- Date: 2026-08-09
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Outcome Summary

Implemented the `grants` feature for tag definitions. When a tag's definition includes a `"grants"` array, any mod carrying that tag automatically receives the listed tags during export — as if the user had added them explicitly. The original `taggedName` string is never modified; expansion happens post-extraction on the resolved `$keepTags` list. `TagDef` now exposes `getGrants()` so the grants data is also accessible from the document generation pipeline.

### Implementation Summary
- **`TagDef.php`**: Added `$grants` property, updated constructor to accept `array $grants = []`, and added `getGrants(): string[]` getter.
- **`TagDefs.php`**: Updated `registerTag()` to read `grants` from the tag definition array and pass it to the `TagDef` constructor.
- **`Game.php`**: Added `getGrantsMap(): array` — reads the definition file and returns a `tagName => string[]` map of all tags that declare grants.
- **`ExportModlist.php`**: Fetches `$grantsMap` at the start of `exportGame()`. After the tag parsing loop, iterates the current `$keepTags`, collects all granted tags (normalised via `$definedTagMap`), and appends them to both `$keepTags` (the mod's stored tags array) and `$tags` (the global tag→mods mapping). Duplicate-safe: a granted tag is only added once even if multiple carrying tags grant it.

### Documentation Updates
- `docs/agents/project-manifest/api-surface.md`: Added `TagDef::getGrants()` to `TagDef`; updated constructor signature; added `Game::getGrantsMap()` to `Game`.
- `docs/agents/project-manifest/constraints.md`: Added a bullet under "Tag Inheritance" describing the `grants` key, its one-level expansion behaviour, the canonical-casing resolution, and the guarantee that `taggedName` is never mutated.
- `docs/agents/project-manifest/data-flows.md`: Updated the export pipeline flow to include the grants expansion step.

### Verification Summary
- Tests run: `composer build` (export + doc generation for both configured games)
- Static analysis run: None (no PHPStan configured for the project)
- Result: PASS — Build completed without errors. Confirmed that all mods tagged `[BothV]` appear in both the `FemV` and `MaleV` tag groups in `cyberpunk2077-modlist.json`, and that `FemV` and `MaleV` are present in those mods' `tags` arrays.

### Code Insights
- [low] (improvement) `src/ComposerScripts/ExportModlist.php`: Grants are expanded only one level deep (granted tags are not themselves re-expanded). If chained grants are needed later, the two-pass collect-then-apply approach would need to become a queue/BFS loop. A comment in the code documents this limitation.
- [low] (debt) `src/ComposerScripts/ExportModlist.php`: `$modTags = $matches[1]; sort($modTags);` — `$modTags` is sorted but the subsequent loop iterates `$matches[1]` instead, making the sort a no-op. Pre-existing; not introduced by this change.

### Additional Comments
- The feature is backward-compatible: game configs without `grants` in any tag definition produce identical output to before.
- Granted tags are stored in `Mod::KEY_TAGS` (`"tags"`) in `modlist.json`, making them indistinguishable from explicitly declared tags from the consumer's perspective. This is intentional.

---

## Synthesis

### Completion Status
- Date: 2026-08-09
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Outcome Summary

Added a new `composer normalize-game-configs` Composer script (and wired it as the first step of `composer build`) that sorts game config JSON files for VCS stability. The normalizer sorts `options` and `tagDefinitions` keys alphabetically, sorts `requires` and `grants` arrays within each tag definition, and strips empty `requires` arrays. Both existing game configs (`cyberpunk2077.json`, `starfield.json`) were successfully normalized on the first run.

### Implementation Summary
- Created `src/ComposerScripts/NormalizeGameConfigs.php` with a `normalize()` entry point and a private `normalizeGame(Game)` method that reads, normalizes, and rewrites each game config using `JSONFile::putData()` with pretty-print and unescaped slashes.
- Added `ComposerScripts::normalizeGameConfigs()` static entry point to `src/ComposerScripts.php`.
- Prepended `self::normalizeGameConfigs()` to `ComposerScripts::build()` so the full build always normalizes configs before exporting.
- Registered `"normalize-game-configs"` in `composer.json` scripts.
- Ran `composer dumpautoload` to update the classmap.

### Documentation Updates
- `docs/agents/project-manifest/api-surface.md`: Added `normalizeGameConfigs()` to `ComposerScripts` and the new `NormalizeGameConfigs` script object.
- `docs/agents/project-manifest/tech-stack.md`: Added `normalize-game-configs` row to the Composer scripts table.
- `docs/agents/project-manifest/data-flows.md`: Added Section 2 describing the normalization pipeline; renumbered the former Section 2 (Export Pipeline) to Section 3.
- `docs/agents/project-manifest/file-tree.md`: Added `NormalizeGameConfigs.php` to the `ComposerScripts/` subtree entry.
- `AGENTS.md`: Updated the Project Stats build tool entry to include the new Composer command.

### Verification Summary
- Tests run: None (project has no test suite).
- `composer normalize-game-configs` — PASS: both games normalized, no errors.
- `composer build` — PASS: normalize → export (588 + 150 mods) → generate-docs completed without errors.
- Manual spot-check of `games/cyberpunk2077.json`: options sorted alphabetically, tag definitions sorted alphabetically, empty `requires` arrays removed, URLs unescaped, file ends with trailing newline.

### Code Insights
- [low] (debt) `src/TagDefs.php` + `src/Game.php`: The JSON key names used inside `tagDefinitions` entries (`"requires"`, `"grants"`, `"label"`, `"description"`, `"url"`) are string literals scattered across `TagDefs::registerTag()` and `NormalizeGameConfigs`. Defining them as constants on `TagDef` would make future key renames refactor-safe.

### Additional Comments
- The normalizer uses PHP's built-in `ksort()` and `sort()` which sort using locale-independent binary string comparison, giving stable, reproducible ordering across environments.
- `JSONFile::setEscapeSlashes(false)` is explicitly set to prevent URL values from being written with backslash-escaped forward slashes.

---

## Synthesis

### Completion Status
- Date: 2026-08-09
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Outcome Summary

Implemented a modular mod lint rule system that fires during `composer export-modlist`. The system checks each mod's properties against a registered set of rules and prints any issues to the CLI immediately after writing the modlist JSON. The first concrete rule warns when a mod in the "Armour and Clothing" category (case-insensitive) does not carry the "Atelier" tag. The architecture is designed so adding a new rule requires only creating a class in `src/ModLint/Rules/` and registering it in `ModLinter::createDefault()`.

### Implementation Summary
- Created `src/ModLint/ModLintContext.php` — value object wrapping the per-mod snapshot (name, category, tags, tagged name) passed to each rule; exposes `isCategoryMatch()` and `hasTag()` with case-insensitive comparisons.
- Created `src/ModLint/ModLintIssue.php` — value object for a single lint result with three severity constants (`NOTICE`, `WARNING`, `ERROR`) and a `format()` method for CLI output.
- Created `src/ModLint/ModLintRuleInterface.php` — interface requiring `check(ModLintContext): ModLintIssue[]`.
- Created `src/ModLint/ModLinter.php` — runner class with `addRule()` / `checkMod()` and a `createDefault()` static factory that pre-registers all standard rules.
- Created `src/ModLint/Rules/AtelierTagRule.php` — first concrete rule; produces a `TYPE_WARNING` for any "Armour and Clothing" mod missing the "Atelier" tag.
- Modified `src/ComposerScripts/ExportModlist.php` — creates a `ModLinter` at the start of `exportGame()`, calls `checkMod()` per mod, and after saving the JSON calls the private `outputLintIssues()` helper which sorts issues by severity then mod name and prints them.

### Documentation Updates
- `docs/agents/project-manifest/api-surface.md`: Added a "Mod Lint System" section covering all five new classes and their public APIs.
- `docs/agents/project-manifest/file-tree.md`: Added the `src/ModLint/` subtree with annotations for all new files.
- `docs/agents/project-manifest/data-flows.md`: Updated the export pipeline section to show where `ModLinter::checkMod()` is called per mod and where collected issues are output to the CLI.
- `AGENTS.md`: Added a "New lint rule added to `src/ModLint/Rules/`" row to the Manifest Maintenance Rules table.

### Verification Summary
- Tests run: `composer export-modlist`, `composer build`
- Static analysis run: None (no PHPStan configured for the project)
- Result: PASS — Full build completed without errors. Export correctly reports 19 warnings for Cyberpunk 2077 "Armour and Clothing" mods missing the "Atelier" tag; Starfield (no matching category) reports no lint issues.

### Code Insights
- [low] (improvement) `src/ModLint/ModLinter.php`: `createDefault()` is currently the only way to register all standard rules. If the rule list grows long, consider accepting a game context so rules can self-determine whether they are relevant (e.g. a Cyberpunk-specific rule could skip Starfield entirely at registration time rather than at check time).
- [low] (debt) `src/ComposerScripts/ExportModlist.php`: Pre-existing — `$modTags = $matches[1]; sort($modTags);` sorts a variable that is then never read; the subsequent loop iterates `$matches[1]` directly, making the sort a no-op.
- [low] (convention) `src/ModLint/Rules/AtelierTagRule.php`: The category string `"Armour and Clothing"` is Cyberpunk-specific but the rule is game-agnostic. If other games gain an "Armour and Clothing" category in the future, the rule will fire there too. This may be desirable or may need a game-scoping mechanism added to `ModLintContext`.

### Additional Comments
- To add a new rule: create a class in `src/ModLint/Rules/` implementing `ModLintRuleInterface`, then add `->addRule(new YourRule())` to `ModLinter::createDefault()`.
- Issues output is suppressed entirely when a game has no violations, so normal (clean) exports remain uncluttered.

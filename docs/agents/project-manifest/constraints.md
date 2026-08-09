# Constraints & Conventions

## Configuration

- `config.php` **must exist** before any command runs. `bin/prepend.php` calls `die()` if it is missing. Copy `config.dist.php` to `config.php` to create it.
- `VORTEX_APPDATA_FOLDER` must point to a real directory — `bin/prepend.php` validates this with `is_dir()` and `die()`s if it fails.
- `EXPORT_GAMES` limits processing to the listed game IDs. When empty, all games in `games/` are processed.

## Game Configuration Files

- Must be placed directly in `games/` (not in subdirectories) with a `.json` extension.
- The filename base name **must exactly match** the game ID used by Vortex internally (lowercase, no spaces, e.g. `cyberpunk2077`).
- Files placed in `games/examples/` are ignored by the auto-discovery logic (that directory is not scanned).
- Minimum required field: `"label"` (human-readable game name). All other keys are optional.
- The optional `"rules"` array selects which mod lint rules run during export for this game. Each entry is an object with a `"name"` key matching a registered name in `ModLinter::RULE_REGISTRY`. Example: `{ "name": "AtelierTagRule" }`. Games with no `"rules"` key run no lint checks. Unknown rule names emit a CLI warning and are skipped.

## Vortex Backup File

- The exporter reads exactly one file: `{VORTEX_APPDATA_FOLDER}/temp/state_backups_full/manual.json`.
- This file is created by Vortex via **Settings > Workarounds > Database Backup > Create Backup**. The export will `die()` with an error if this file is missing.
- The backup's `persistent.mods.{gameId}` key must exist for each configured game — otherwise the export `die()`s.

## Mod Naming Conventions

- Mods prefixed with `ZZ -` are treated as permanently unused. They are excluded by default.
- Mods prefixed with `ZY -` are treated as temporarily unused (e.g. awaiting an update). Excluded by default.
- Tags embedded in mod names use the syntax `[TagName]` (square brackets). The exporter parses these with `preg_match_all('/\[([^]]+)]/', $name, $matches)`.
- **Tag names are case-insensitive.** During export, each extracted tag is normalised to the canonical casing defined in the game's `tagDefinitions` block (e.g. `[Redscript]` in a mod name is stored as `RedScript`). At doc-generation time, `TagDefs::resolveTagName()` performs the same normalisation for backward compatibility with any pre-existing `modlist.json` data that may carry wrong-case tags. The lookup map is built from explicitly defined tags and the two canned tags (`Unused`, `UnusedTemp`); unrecognised tags are stored exactly as written.
- Tags may carry a parameter using colon syntax: `[TagName:param]`. The exporter splits on the first `:`, treating the left side as the base tag name and the right side as the parameter. The mod is registered under the base tag name. The parameter is stored in the mod's `tagParams` map in `modlist.json` and is surfaced via `Mod::getTagParam()` and `TagDef::getPreviousVersion()`.
- The `UPD` tag is the canonical example of a parameterized tag: `[UPD:1.1]` means the mod has the `UPD` tag and was updated from version `1.1`. The previous version is displayed in the generated tags reference.
- Tags that parse as a valid PHP `strtotime()` date are treated as date tags and ignored when `ignoreDateTags` is `true` (the default).
- Parenthesised text in mod names (round brackets) is extracted as a **comment**. Multiple groups are supported. Each group is sentence-cased (first letter capitalised) and terminated with a period. Groups are joined with a single space into one comment string stored in `Mod::KEY_COMMENTS` (`"comments"`). The groups are stripped from `cleanName` just like tag brackets. Example: `Mod name (Vanilla) [CET] (Adds new items)` → name `"Mod name"`, comments `"Vanilla. Adds new items."`.

## Output Files

- All output files are written to the `output/` directory (`OUTPUT_FOLDER` constant).
- If `GameOptions::getOutputFolder()` is set, output files are additionally **copied** (not moved) to that folder.
- Filenames follow the pattern `{gameId}-modlist.json`, `{gameId}-mods.md`, `{gameId}-tags.md`.

## Tag Inheritance

- The `requires` array in a tag definition causes transitive tag membership: a mod tagged `[Child]` will implicitly also belong to all tags listed in `Child`'s `requires` chain.
- The `grants` array in a tag definition causes **automatic tag injection during export**: when a mod carries a tag that has a `grants` list, each granted tag is added to the mod's tag list as if the user had added it explicitly. Example: `BothV` with `"grants": ["FemV", "MaleV"]` means any mod tagged `[BothV]` is also recorded under both `FemV` and `MaleV`. Granted tags are resolved to canonical casing via `getDefinedTagNameMap()` before being applied. Only one level of grants is expanded (granted tags are not themselves re-expanded for further grants). The original `taggedName` stored in `modlist.json` is never modified by grants.
- Two tags are automatically registered for every game regardless of the game config: `Unused` and `UnusedTemp`. These are appended to mod names when `includeUnusedMods` or `includeTemporarilyUnusedMods` is enabled.

## Collections Pattern

- All collections (`Games`, `Mods`, `TagDefs`) extend `BaseStringPrimaryCollection`. Items are registered in `registerItems()` which is called lazily on first access.
- `Games` is the only process-scoped singleton; `Mods` and `TagDefs` are per-`Game` instance.
- Item IDs in all collections are string-typed. `getDefaultID()` uses `getAutoDefault()` which returns the first registered item's ID.

## No Web Layer

- The project has no HTTP entry points, no web framework, and no session or authentication logic. It must never be exposed as a web endpoint.

## PHP Strict Types

- All PHP files declare `declare(strict_types=1)`. Type coercions are not tolerated.

## Namespace

- All project classes and functions live in the `Mistralys\VortexModExporter` namespace.
- Sub-scripts (`ExportModlist`, `GenerateDocs`) use `Mistralys\VortexModExporter\ComposerScripts` as their namespace.

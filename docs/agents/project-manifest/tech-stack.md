# Tech Stack & Patterns

## Runtime & Language

| Item | Detail |
|---|---|
| Language | PHP 8.4+ |
| Minimum version | `>=8.4` (declared in `composer.json`) |
| Execution model | CLI only — invoked exclusively through Composer scripts |

## Dependencies

| Package | Version | Role |
|---|---|---|
| `mistralys/application-utils-core` | `>=2.3.3` | `ArrayDataCollection`, `FileHelper`, `JSONFile`, `FileInfo`, `FolderInfo`, `Microtime`, `parseURL()` utility functions |
| `mistralys/application-utils-collections` | `>=1.1.2` | `BaseStringPrimaryCollection` — base class for all typed, keyed collections (`Games`, `Mods`, `TagDefs`) |
| `geshi/geshi` | (transitive) | Syntax highlighting (bundled via vendor, not used directly by this project) |
| `neitanod/forceutf8` | (transitive) | UTF-8 normalization (bundled via vendor, not used directly by this project) |

**Dev dependencies:**

| Package | Role |
|---|---|
| `roave/security-advisories` | Prevents installation of packages with known security vulnerabilities |

## Build / Task Runner

Composer scripts are used as the task runner. There is no Makefile, Grunt, webpack, or similar tool.

| Script | Composer command | PHP entry point |
|---|---|---|
| Full build | `composer build` | `ComposerScripts::build()` |
| Normalize game configs | `composer normalize-game-configs` | `ComposerScripts::normalizeGameConfigs()` |
| Export mod list | `composer export-modlist` | `ComposerScripts::exportModlist()` |
| Generate docs | `composer generate-docs` | `ComposerScripts::generateDocs()` |

All scripts bootstrap via `bin/prepend.php`, which loads `config.php` and `vendor/autoload.php`.

## Autoloading

Composer classmap autoloading covers the entire `src/` directory. There are no PSR-4 namespaces; all classes are in the `Mistralys\VortexModExporter` namespace (flat or one level deep for sub-scripts).

## Architectural Patterns

- **Static singleton registry** — `Games` uses a private static `$instance` for process-scoped access.
- **Lazy initialization** — `Game`, `Mods`, `TagDefs`, and `GameOptions` objects are created on first access.
- **Typed collection base class** — `Games`, `Mods`, and `TagDefs` all extend `BaseStringPrimaryCollection` from `application-utils-collections`, providing uniform `getByID()`, `getAll()`, `idExists()`, and `registerItem()` APIs.
- **Data-object pattern** — `Game`, `Mod`, `TagDef`, and `GameOptions` are read-only data carriers backed by `ArrayDataCollection`.
- **Script-object pattern** — `ExportModlist` and `GenerateDocs` are single-method objects instantiated once per run; they are not services or singletons.
- **No framework, no HTTP layer** — pure CLI tool; no web routing, no ORM, no DI container.

# Public API Surface

All classes are in the `Mistralys\VortexModExporter` namespace unless noted.

---

## Entry Points (`src/ComposerScripts.php`)

```php
class ComposerScripts
{
    public static function normalizeGameConfigs(): void;
    public static function exportModlist(): void;
    public static function generateDocs(): void;
    public static function build(): void;  // runs normalizeGameConfigs → exportModlist → generateDocs
}
```

---

## Script Objects (`src/ComposerScripts/`)

### `ComposerScripts\ExportModlist`

```php
class ExportModlist
{
    public function export(): void;
}
```

### `ComposerScripts\GenerateDocs`

```php
class GenerateDocs
{
    public function generate(): void;
}
```

### `ComposerScripts\NormalizeGameConfigs`

```php
class NormalizeGameConfigs
{
    public function normalize(): void;
}
```

---

## Domain Models

### `Game` (`src/Game.php`)

Implements `StringPrimaryRecordInterface` (from `application-utils-collections`).

```php
class Game implements StringPrimaryRecordInterface
{
    // Constants — JSON keys used in definition and data files
    public const KEY_DATA_GAME         = 'game';
    public const KEY_DATA_EXPORT_DATE  = 'exportDate';
    public const KEY_DATA_MODS         = 'mods';
    public const KEY_DATA_TAGS         = 'tags';
    public const KEY_DATA_CATEGORIES   = 'categories';
    public const KEY_DATA_DATABASE_DATE= 'databaseDate';
    public const KEY_DEF_LABEL         = 'label';
    public const KEY_DEF_TAG_DEFINITIONS = 'tagDefinitions';
    public const KEY_DEF_OPTIONS       = 'options';
    public const KEY_DEF_RULES         = 'rules';

    public function __construct(JSONFile $definitionFile);

    public function getID(): string;              // Alias for getVortexID()
    public function getVortexID(): string;         // Base name of the definition file, e.g. "cyberpunk2077"
    public function getLabel(): string;            // Human-readable game name from definition JSON
    public function getExportDate(): DateTime;     // Timestamp from the last modlist.json export
    public function getDatabaseDate(): DateTime;   // Timestamp of the Vortex database backup used
    public function getOptions(): GameOptions;
    public function getDefinitionFile(): JSONFile;
    public function getMods(): Mods;
    public function getModData(): array;           // Raw mod data array from modlist.json
    public function getTagNames(): string[];       // All tag names found in the exported data
    public function getModNamesByTags(): array;    // Map of tagName => modName[]
    public function getModTagParam(string $modName, string $tagName): ?string;  // Parameter stored for mod/tag pair, or null
    public function getDefinedTagNameMap(): array; // lowercase => canonical tag name map from definition file (includes canned tags)
    public function getGrantsMap(): array;          // tag name => string[] of granted tag names; built from definition file
    public function getRulesConfig(): array;        // Raw rules array from definition file; empty array when key absent
    public function getTagDefs(): TagDefs;
}
```

### `GameOptions` (`src/GameOptions.php`)

Extends `ArrayDataCollection` (from `application-utils-core`).

```php
class GameOptions extends ArrayDataCollection
{
    public const KEY_IGNORE_DATE_TAGS          = 'ignoreDateTags';
    public const KEY_IGNORE_UNKNOWN_CATEGORY   = 'ignoreUnknownCategory';
    public const KEY_DEF_INCLUDE_UNUSED_MODS   = 'includeUnusedMods';
    public const KEY_DEF_INCLUDE_TEMPORARILY_UNUSED = 'includeTemporarilyUnusedMods';
    public const KEY_OUTPUT_FOLDER             = 'outputFolder';

    public function areDateTagsIgnored(): bool;
    public function isUnknownCategoryIgnored(): bool;
    public function areUnusedModsIncluded(): bool;
    public function areTemporarilyUnusedModsIncluded(): bool;
    public function getOutputFolder(): ?FolderInfo;  // Creates folder if path is set
}
```

### `Mod` (`src/Mod.php`)

Implements `StringPrimaryRecordInterface`.

```php
class Mod implements StringPrimaryRecordInterface
{
    public const KEY_TAGGED_NAME  = 'taggedName';
    public const KEY_OFFICIAL_NAME= 'officialName';
    public const KEY_HOMEPAGE     = 'homepage';
    public const KEY_CATEGORY     = 'category';
    public const KEY_ENDORSED     = 'endorsed';
    public const KEY_TAGS         = 'tags';
    public const KEY_TAG_PARAMS   = 'tagParams';  // Map of tagName => parameter for parameterized tags
    public const KEY_COMMENTS     = 'comments';   // Normalised comment from parenthesised groups in the mod name

    public function __construct(Game $game, string $name, ArrayDataCollection $data);

    public function getID(): string;          // Alias for getName()
    public function getGame(): Game;
    public function getName(): string;         // Clean name (tags stripped)
    public function getTaggedName(): string;   // Full name including [Tag] brackets
    public function getOfficialName(): string; // Original mod name from Nexus Mods
    public function getHomepage(): string;
    public function getCategory(): string;     // Category label resolved from Vortex category data
    public function isEndorsed(): bool;
    public function getTags(): string[];       // Direct tags only (base names, no parameters)
    public function getTagParams(): array;     // tagName => parameter for parameterized tags, e.g. ['UPD' => '1.1']
    public function getTagParam(string $tagName): ?string;  // Parameter for a single tag, or null
    public function getComments(): string;                   // Sentence-cased comment from parenthesised groups, or empty string
    public function getInheritedTags(): string[]; // Direct tags + all transitively required tags
}
```

### `TagDef` (`src/TagDef.php`)

Implements `StringPrimaryRecordInterface`.

```php
class TagDef implements StringPrimaryRecordInterface
{
    public function __construct(
        TagDefs $collection,
        string  $name,
        string  $label,
        string  $description,
        array   $requires = [],
        ?string $url = null,
        bool    $defined = false,
        array   $grants = []
    );

    public function getID(): string;           // Alias for getName()
    public function getName(): string;          // Tag key as used in [BracketSyntax]
    public function getLabel(): ?string;        // Optional human-readable label
    public function getDescription(): string;
    public function getRequires(): string[];    // Names of tags this tag depends on
    public function getGrants(): string[];      // Tag names automatically added to a mod when it carries this tag
    public function getURL(): ?string;          // Optional URL for related mod/resource
    public function isDefined(): bool;          // true if defined in game config; false if auto-discovered
    public function registerMod(string $modName, ?string $previousVersion = null): void;
    public function registerMods(array $mods): void;
    public function getModNames(): string[];
    public function getPreviousVersions(): array;              // modName => previousVersion for all mods that used a parameter
    public function getPreviousVersion(string $modName): ?string; // Previous version for a specific mod, or null
    public function getInherited(): string[];   // Recursively resolved required tag names
}
```

---

## Collections

### `Games` (`src/Games.php`)

Extends `BaseStringPrimaryCollection`.

```php
class Games extends BaseStringPrimaryCollection
{
    public const UNKNOWN_CATEGORY_NAME = 'Unknown';
    public const PREFIX_UNUSED         = 'ZZ -';
    public const PREFIX_AWAIT_UPDATE   = 'ZY -';

    public static function getInstance(): Games;  // Process-scoped singleton

    public function getDefaultID(): string;
    // Inherited: getByID(string $id): Game
    // Inherited: getAll(): Game[]
    // Inherited: idExists(string $id): bool
}
```

### `Mods` (`src/Mods.php`)

Extends `BaseStringPrimaryCollection`.

```php
class Mods extends BaseStringPrimaryCollection
{
    public function __construct(Game $game);

    public function getDefaultID(): string;
    // Inherited: getByID(string $id): Mod
    // Inherited: getAll(): Mod[]
    // Inherited: idExists(string $id): bool
}
```

### `TagDefs` (`src/TagDefs.php`)

Extends `BaseStringPrimaryCollection`.

```php
class TagDefs extends BaseStringPrimaryCollection
{
    public const TAG_UNUSED             = 'Unused';
    public const TAG_UNUSED_TEMPORARILY = 'UnusedTemp';

    public function __construct(Game $game, array $tagDefs);

    public function getDefaultID(): string;
    public function getUndescribedTags(): TagDef[];  // Tags not in game config (auto-discovered)
    public function resolveTagName(string $tagName): string;  // Normalises tag case to canonical form; returns original if no match
    public function registerTagMods(): void;          // Links tag definitions to mod names from export data
    // Inherited: getByID(string $id): TagDef
    // Inherited: getAll(): TagDef[]
    // Inherited: idExists(string $id): bool
}
```

---

## Mod Lint System (`src/ModLint/`)

Namespace: `Mistralys\VortexModExporter\ModLint`

### `ModLintContext` (`src/ModLint/ModLintContext.php`)

```php
class ModLintContext
{
    public function __construct(
        string $modName,    // Clean name (tags stripped)
        string $category,   // Category label from Vortex
        array  $tags,       // string[] — direct canonical tag names
        string $taggedName  // Full name with [Tag] brackets
    );

    public function getModName(): string;
    public function getCategory(): string;
    public function getTags(): string[];        // Direct (non-inherited) tags
    public function getTaggedName(): string;
    public function isCategoryMatch(string $category): bool;  // Case-insensitive
    public function hasTag(string $tagName): bool;             // Case-insensitive
}
```

### `ModLintIssue` (`src/ModLint/ModLintIssue.php`)

```php
class ModLintIssue
{
    public const TYPE_NOTICE  = 'NOTICE';
    public const TYPE_WARNING = 'WARNING';
    public const TYPE_ERROR   = 'ERROR';

    public function __construct(string $type, string $modName, string $message);

    public function getType(): string;     // One of the TYPE_* constants
    public function getModName(): string;  // Clean name of the flagged mod
    public function getMessage(): string;  // Human-readable issue description
    public function format(): string;      // CLI-ready formatted line
}
```

### `ModLintRuleInterface` (`src/ModLint/ModLintRuleInterface.php`)

```php
interface ModLintRuleInterface
{
    /** @return ModLintIssue[] */
    public function check(ModLintContext $context): array;
}
```

### `ModLinter` (`src/ModLint/ModLinter.php`)

```php
class ModLinter
{
    // Registry maps rule name strings to implementing class names.
    // Add an entry here when creating a new rule class.
    private const RULE_REGISTRY = ['AtelierTagRule' => AtelierTagRule::class, ...];

    public static function createFromGame(Game $game): self;  // Builds linter from game's "rules" config; emits CLI warning for unknown names
    public function addRule(ModLintRuleInterface $rule): self;  // Fluent; register an additional rule
    /** @return ModLintIssue[] */
    public function checkMod(ModLintContext $context): array;   // Run all rules against one mod
}
```

### `Rules\AtelierTagRule` (`src/ModLint/Rules/AtelierTagRule.php`)

```php
// Namespace: Mistralys\VortexModExporter\ModLint\Rules
class AtelierTagRule implements ModLintRuleInterface
{
    public const CATEGORY = 'Armour and Clothing';
    public const TAG      = 'Atelier';

    public function check(ModLintContext $context): array;
    // Returns a TYPE_WARNING issue when a mod is in CATEGORY but lacks TAG.
}
```

---

## Utility Functions (`src/functions.php`)

Namespace: `Mistralys\VortexModExporter`

```php
function slugify(string $label): string;   // Lowercase, spaces→hyphens, non-alphanum stripped
function titleify(string $title): string;  // Underscores→spaces
```

---

## Constants (`bin/prepend.php`, `config.dist.php`)

| Constant | Defined in | Description |
|---|---|---|
| `OUTPUT_FOLDER` | `bin/prepend.php` | Absolute path to the `output/` directory |
| `VORTEX_APPDATA_FOLDER` | `config.php` | Path to Vortex's AppData directory (user-configured) |
| `EXPORT_GAMES` | `config.php` | Array of game IDs to limit processing; empty = all games |

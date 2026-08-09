# Key Data Flows

## 1. Bootstrap (shared by all commands)

Any Composer script call → `ComposerScripts::init()` → loads `config.php` (defines `VORTEX_APPDATA_FOLDER`, `EXPORT_GAMES`) → loads `vendor/autoload.php` → `bin/prepend.php` defines `OUTPUT_FOLDER` constant.

---

## 2. Game Config Normalization (`composer normalize-game-configs`)

```
ComposerScripts::normalizeGameConfigs()
  └─ NormalizeGameConfigs::normalize()
       └─ For each Game in Games::getInstance()->getAll():
            NormalizeGameConfigs::normalizeGame(Game)
              ├─ Reads raw JSON via Game::getDefinitionFile()->getData()
              ├─ Sorts Game::KEY_DEF_OPTIONS keys alphabetically (ksort)
              ├─ Sorts Game::KEY_DEF_TAG_DEFINITIONS keys alphabetically (ksort)
              ├─ For each tag definition:
              │    ├─ Strips empty `requires` arrays
              │    ├─ Sorts non-empty `requires` values alphabetically (sort)
              │    └─ Sorts `grants` values alphabetically (sort)
              └─ Writes normalized JSON back to games/{gameId}.json
                   (pretty-print, unescaped slashes, trailing newline)
```

---

## 3. Export Pipeline (`composer export-modlist`)

```
ComposerScripts::exportModlist()
  └─ ExportModlist::export()
       ├─ Reads Vortex backup file:
       │    {VORTEX_APPDATA_FOLDER}/temp/state_backups_full/manual.json
       │    Extracts: persistent.mods[{gameId}], persistent.categories[{gameId}]
       │
       └─ For each Game in Games::getInstance()->getAll():
            ExportModlist::exportGame(Game, DateTime, modsData[], categoriesData[])
              │
              ├─ Reads Game::getOptions() to apply filtering rules:
              │    - Skip mods with "ZZ -" prefix (unless includeUnusedMods)
              │    - Skip mods with "ZY -" prefix (unless includeTemporarilyUnusedMods)
              │    - Skip mods with Unknown category (unless !ignoreUnknownCategory)
              │    - Strip [DateTag] brackets (if ignoreDateTags)
              │
              ├─ Extracts clean mod name (strips all [Tag] brackets)
              │    Resolves tag names to canonical casing via getDefinedTagNameMap()
              │    Expands granted tags via getGrantsMap() (tags listed in `grants` are
              │      added to the mod's tag list as if explicitly tagged by the user)
              │    Resolves category name from categoriesData
              │    Builds: mods{}, tags{}, categories{} maps
              │
              └─ Writes output/{gameId}-modlist.json via JSONFile::putData()
                   Structure: { game, databaseDate, exportDate, categories, tags, mods }
                   Optionally copies to GameOptions::getOutputFolder()
```

---

## 3. Document Generation Pipeline (`composer generate-docs`)

### 3a. Tags Reference

```
ComposerScripts::generateDocs()
  └─ GenerateDocs::generate()
       └─ For each Game:
            GenerateDocs::writeGameTagsReference(Game)
              ├─ Game::getTagDefs() → TagDefs (lazy-loaded from modlist.json + game config)
              │    TagDefs registers:
              │      1. Explicit tag definitions from games/{gameId}.json
              │      2. Canned built-in tags (Unused, UnusedTemp)
              │      3. Auto-discovered tags from Game::getTagNames()
              │      4. Associates mod names via TagDefs::registerTagMods()
              │
              ├─ Builds Markdown: TOC + per-tag section with description, URL, mod list
              │    Links to mod homepages if available via Mod::getHomepage()
              │
              └─ Writes output/{gameId}-tags.md
                   Optionally copies to GameOptions::getOutputFolder()
```

### 3b. Mods Reference

```
GenerateDocs::writeGameModsReference(Game)
  ├─ Game::getMods() → Mods (lazy-loaded from modlist.json)
  ├─ Groups mods by category (Mod::getCategory())
  ├─ Builds Markdown: category overview + per-mod detail section
  │    Per mod: category, homepage, inherited tags (Mod::getInheritedTags())
  └─ Writes output/{gameId}-mods.md
       Optionally copies to GameOptions::getOutputFolder()
```

---

## 4. Tag Inheritance Resolution

```
Mod::getInheritedTags()
  └─ For each direct tag on the mod:
       TagDef::getInherited()
         └─ Recursively resolves TagDef::getRequires() chains
              Returns: unique set of all ancestor tag names
```

This means a mod tagged `[SKSE]` will also appear under any tag that `SKSE` lists in its `requires` array.

---

## 5. Game Discovery

```
Games::getInstance()
  └─ Games::registerItems()
       └─ FileHelper::createFileFinder(games/)
            Finds all *.json files (excluding examples/ subfolder)
            Creates one Game(JSONFile) per file
```

The game ID is derived from the JSON filename base name (e.g., `cyberpunk2077.json` → ID `cyberpunk2077`). This same ID is used as the Vortex internal game key.

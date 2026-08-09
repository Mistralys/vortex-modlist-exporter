# File Tree

```
vortex-modlist-exporter/
│
├── bin/
│   └── prepend.php             Bootstrap: loads config.php + autoload, defines OUTPUT_FOLDER constant
│
├── games/                      Game configuration files (one JSON per game)
│   ├── cyberpunk2077.json      Game definition + tag definitions for Cyberpunk 2077
│   ├── starfield.json          Game definition + tag definitions for Starfield
│   ├── _readme.md              Describes the expected JSON structure
│   └── examples/               Example game config files (reference only, not processed)
│
├── output/                     Generated output files (git-tracked examples; live files written here)
│   ├── {gameId}-modlist.json   Structured mod list (output of export-modlist)
│   ├── {gameId}-mods.md        Human-readable mod reference by category (output of generate-docs)
│   ├── {gameId}-tags.md        Human-readable tag reference with mod lists (output of generate-docs)
│   ├── _readme.md              Describes the output files
│   └── examples/               Example output files checked into the repository
│
├── src/
│   ├── functions.php           Namespace-level helpers: slugify(), titleify()
│   ├── ComposerScripts.php     Composer script entry points: exportModlist(), generateDocs(), build()
│   ├── Game.php                Domain model: one configured game and its loaded data
│   ├── GameOptions.php         Typed accessor for per-game export options
│   ├── Games.php               Singleton collection of all Game instances (auto-detected from games/)
│   ├── Mod.php                 Domain model: a single mod entry
│   ├── Mods.php                Typed collection of Mod instances for a game
│   ├── TagDef.php              Domain model: a tag definition (described or auto-discovered)
│   ├── TagDefs.php             Typed collection of TagDef instances for a game
│   └── ComposerScripts/
│       ├── ExportModlist.php         Export pipeline: reads Vortex backup → writes {gameId}-modlist.json
│       ├── GenerateDocs.php          Doc pipeline: reads modlist JSON → writes {gameId}-mods.md + {gameId}-tags.md
│       └── NormalizeGameConfigs.php  Normalization: sorts options, tag defs, requires, grants; strips empty requires
│
├── vendor/                     Composer-managed dependencies (not modified)
│
├── bin/prepend.php             (see above)
├── composer.json               Package manifest and Composer script definitions
├── config.dist.php             Template configuration (copy to config.php)
├── config.php                  Local configuration (gitignored; defines VORTEX_APPDATA_FOLDER, EXPORT_GAMES)
├── changelog.md                Project changelog
└── README.md                   User-facing documentation
```

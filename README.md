# Vortex Mod List Exporter

Simple PHP script that can be used to export the list of mods
from a Vortex installation. It preserves the mod labels as they 
are renamed in the interface, and handles tags used to categorize 
the mods.

## Requirements

- PHP 8.2 or higher
- [Composer](https://getcomposer.org) 

## Installation

1. Clone the repository
2. Run `composer install` to install the dependencies
3. Copy the `config.dist.php` file to `config.php`
4. Edit the config settings
5. Create a game config (see below)

## Create a game config

Only games for which a configuration exist are processed.
The configuration is a JSON file that contains the necessary
information on the game and optional tag definitions. 

### Create the file

Create the configuration file under `games/game-name.json`.

`game-name` is the name that Vortex uses internally. This is usually
the game's name, lowercase, without spaces. For example: `cyberpunk2077`,
`reddeadredemption2`.

### Minimum config

At minimum, the file must contain the human-readable name of
the game:

```json
{
  "label": "Cyberpunk 2077"
}
```

### Export options

Some options can be added to the game config to control what
is included in the export. The following options are available:

- `ignoreDateTags` - Ignore tags that contain a date. This is useful
  for tags that are used to track mod updates, but are not relevant
  for the export.
- `ignoreUnknownCategory` - Ignore mods that do not have a category
  assigned. 

```json
{
  "label": "Cyberpunk 2077",
  "options": {
    "ignoreDateTags": true,
    "ignoreUnknownCategory": true
  }
}
```

### Tag definitions

The tags that you use to categorize your mods can be described in
detail in the configuration file. This way, the generated files will
be self-documenting.

Have a look at the example file for [Cyberpunk 2077](./games/examples/cyberpunk2077.json)
which contains a detailed tagging setup.

## Usage

1. Open Vortex
2. Go to Settings > Workarounds
3. In the "Database Backup" section, click "Create Backup"
4. Run `php bin/export-modlist.php` to generate the mod list
5. Run `php bin/generate-docs.php` to generate the Markdown files

You will find the export files in the `output` directory. 
Some examples of what the script generates can be found 
in the [examples](./output/examples) folder.

> **Note:** Re-export the database in Vortex after you've
> done changes, and re-run the script to update the mod list.

## How to use tagging

Add tags in mod names with the syntax `[TagName]`. They can be
used to track dependencies between mods as well as to categorize
them by topic. 

This has several uses:

1. Filtering. In Vortex, the syntax with the brackets makes it
   easy to filter mods by tag. 
2. When exporting the mod list, an overview by tag is generated,
   and the mod tags are included in the exported mod data to
   group them by tag.
3. Make game updates easier by filtering mods to check which ones 
   must and/or have been updated since a game release.

Example: In Skyrim, many mods use the Skyrim Script Extender, aka
SKSE. By tagging all mods that require SKSE with `[SKSE]`, you can
quickly identify which mods depend on it.

## Typical tags

These are tags I personally use for my mod lists. 

- `[Core]` - Absolute minimum core mods needed by other mods to work
- `[Core2]` - Most important mods that you do not want to play without
- `[Core3]` - Important mods, but that you can live without
- `[Vanilla]` - Mod that can run without any other mods
- `[New]` - New, untested mod

As an example, in Cyberpunk, I have all modder resource mods
and script extenders tagged with `[Core]`. After a game update,
I can filter the list by the tag and check those mods for any
updates.

### Handling newly installed mods

I usually mark new mods that I have not yet tested with `[New]`.
In addition to this, I also add a date to the mod name, like this:

`Mod Name [New] [2025-04-21]`

This can help to quickly run down mod-related issues after installing
new ones.

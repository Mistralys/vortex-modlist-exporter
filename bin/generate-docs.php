<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\FileHelper\FileInfo;

require_once __DIR__.'/prepend.php';

foreach(Games::getInstance()->getAll() as $game) {
    echo "Game [".$game->getVortexID()."]...\n";
    writeGameTagsReference($game);
    writeGameModsReference($game);
    echo "  Done.\n";
}

function resolveTitle(TagDef $tag) : string
{
    $label = $tag->getLabel();
    if (!empty($label)) {
        return titleify(sprintf("%s - %s", $tag->getName(), $label));
    }

    return titleify($tag->getName());
}

/**
 * Writes a reference file for the tags.
 */
function writeGameTagsReference(Game $game) : void
{
    echo "  - Writing tags reference...";

    $lines = array();
    $lines[] = "# ".$game->getLabel()." tag reference\n";
    $lines[] = "\n";
    $lines[] = "These are all tags used to describe mods in Vortex for ".$game->getLabel().",\n";
    $lines[] = "according to the mods used in a local Vortex database backup.\n";
    $lines[] = "\n";
    $lines[] = "Generation time: ".$game->getExportDate()->format('Y-m-d H:i:s')."  \n";
    $lines[] = "Vortex database update time: ".$game->getDatabaseDate()->format('Y-m-d H:i:s')."\n";
    $lines[] = "\n";

    foreach($game->getTagDefs()->getAll() as $tagDef)
    {
        $lines[] = sprintf("- [%s](#%s)\n", titleify($tagDef->getName()), slugify(resolveTitle($tagDef)));
    }

    $lines[] = "\n";

    foreach($game->getTagDefs()->getAll() as $tagDef)
    {
        $lines[] = sprintf("## %s\n", resolveTitle($tagDef));
        $lines[] = "\n";

        $url = $tagDef->getURL();
        if(!empty($url)) {
            $lines[] = sprintf("This tag is related to a mod. [Go to source](%s)\n", $url);
            $lines[] = "\n";
        }

        $mods = $game->getMods();

        foreach($tagDef->getModNames() as $modName) {
            $mod = $mods->getByID($modName);
            $lines[] = sprintf("- [%s](%s)\n", $modName, $mod->getHomepage());
        }

        $lines[] = "\n";
    }

    FileInfo::factory(OUTPUT_FOLDER.'/'.$game->getVortexID().'-tags.md')
        ->putContents(implode("", $lines));

    echo "Done.\n";
}

function writeGameModsReference(Game $game) : void
{
    echo "  - Writing mods reference...";

    $lines = array();
    $lines[] = "# ".$game->getLabel()." mod reference\n";
    $lines[] = "\n";
    $lines[] = "These are all mods used in Vortex for ".$game->getLabel().",\n";
    $lines[] = "according to the mods used in a local Vortex database backup.\n";
    $lines[] = "\n";
    $lines[] = "Generation time: ".$game->getExportDate()->format('Y-m-d H:i:s')."  \n";
    $lines[] = "Vortex database update time: ".$game->getDatabaseDate()->format('Y-m-d H:i:s')."\n";
    $lines[] = "\n";

    $cats = array();
    foreach ($game->getMods()->getAll() as $mod) {
        $cat = $mod->getCategory();
        if(!isset($cats[$cat])) {
            $cats[$cat] = array();
        }

        $cats[$cat][] = $mod;
    }

    uksort($cats, 'strnatcasecmp');

    $lines[] = "## Overview\n";
    $lines[] = "\n";

    foreach ($cats as $cat => $mods) {
        $lines[] = '### '.$cat."\n\n";
        foreach ($mods as $mod) {
            $lines[] = sprintf("- [%s](#%s)\n", titleify($mod->getName()), slugify($mod->getName()));
        }
        $lines[] = "\n";
    }

    $lines[] = "\n";

    $lines[] = '## Mod details'."\n";

    foreach ($game->getMods()->getAll() as $mod) {
        $lines[] = sprintf("### %s\n", titleify($mod->getName()));
        $lines[] = "\n";

        $lines[] = sprintf("[Go to source](%s)\n", $mod->getHomepage());
        $lines[] = "\n";

        $tags = $mod->getInheritedTags();
        if (!empty($tags)) {
            $lines[] = "Tags: `".implode("`, `", $tags)."`\n\n";
        }
    }

    FileInfo::factory(OUTPUT_FOLDER.'/'.$game->getVortexID().'-mods.md')
        ->putContents(implode("", $lines));

    echo "Done.\n";
}

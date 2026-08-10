<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter\ComposerScripts;

use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use Mistralys\VortexModExporter\Game;
use Mistralys\VortexModExporter\Games;
use Mistralys\VortexModExporter\Mod;
use Mistralys\VortexModExporter\TagDef;
use function AppUtils\parseURL;
use function Mistralys\VortexModExporter\slugify;
use function Mistralys\VortexModExporter\titleify;
use const Mistralys\VortexModExporter\OUTPUT_FOLDER;

class GenerateDocs
{
    public function generate() : void
    {
        foreach(Games::getInstance()->getAll() as $game) {
            echo "Game [".$game->getVortexID()."]...\n";
            $this->writeGameTagsReference($game);
            $this->writeGameModsReference($game);
            $this->writeGameReadme($game);
            echo "  Done.\n";
        }
    }

    private function writeGameReadme(Game $game) : void
    {
        $gameId = $game->getVortexID();
        $allMods = $game->getMods()->getAll();
        $tagDefs = $game->getTagDefs()->getAll();

        $categories = array();
        foreach($allMods as $mod) {
            $categories[$mod->getCategory()] = true;
        }

        $lines = array();
        $lines[] = sprintf("# %s Mods List\n", $game->getLabel());
        $lines[] = "\n";
        $lines[] = $this->resolveTimestampHeader($game);
        $lines[] = "\n";
        $lines[] = sprintf("- **Total mods:** %d\n", count($allMods));
        $lines[] = sprintf("- **Categories:** %d\n", count($categories));
        $lines[] = sprintf("- **Tags:** %d\n", count($tagDefs));
        $lines[] = "\n";
        $lines[] = "## Resources\n";
        $lines[] = "\n";
        $lines[] = "- [Mod list by category](mods.md)\n";
        $lines[] = "- [Tag reference](tags.md)\n";
        $lines[] = "- [Full mod data (JSON)](modlist.json)\n";
        $lines[] = "\n";

        FileInfo::factory(OUTPUT_FOLDER.'/'.$gameId.'/README.md')
            ->putContents(implode("", $lines));
    }

    private function resolveTitle(TagDef $tag) : string
    {
        $label = $tag->getLabel();
        if (!empty($label)) {
            return titleify(sprintf("%s - %s", $tag->getName(), $label));
        }

        return titleify($tag->getName());
    }

    private function resolveTimestampHeader(Game $game) : string
    {
        return sprintf(
            "Generation time: %s  \nVortex database update time: %s\n",
            $game->getExportDate()->format('Y-m-d H:i:s'),
            $game->getDatabaseDate()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Renders a mod list entry line for a tag reference document.
     * Found mods are rendered as links, missing mods are plain text.
     *
     * @return array{line: string, missing: bool}
     */
    private function renderTagModLine(Game $game, TagDef $tagDef, string $modName) : array
    {
        $previousVersion = $tagDef->getPreviousVersion($modName);
        $versionSuffix = $previousVersion !== null ? sprintf(' (prev. v%s)', $previousVersion) : '';

        $mods = $game->getMods();

        if(!$mods->idExists($modName)) {
            return array(
                'line' => sprintf("- %s%s | WARNING: mod not found\n", $modName, $versionSuffix),
                'missing' => true
            );
        }

        $mod = $mods->getByID($modName);
        $homepage = $mod->getHomepage();

        if (empty($homepage)) {
            return array(
                'line' => sprintf("- %s%s\n", $modName, $versionSuffix),
                'missing' => false
            );
        }

        return array(
            'line' => sprintf("- [%s](%s)%s\n", $modName, $mod->getHomepage(), $versionSuffix),
            'missing' => false
        );
    }

    // region: Tags Reference

    private function writeGameTagsReference(Game $game) : void
    {
        echo "  - Writing tags reference...";

        $gameId = $game->getVortexID();
        $tagsFolder = FolderInfo::factory(OUTPUT_FOLDER.'/'.$gameId.'/tags')->create();

        $tagDefs = $game->getTagDefs()->getAll();

        $totalMods = count($game->getMods()->getAll());
        $totalTags = count($tagDefs);
        $tagsWithMods = 0;

        foreach($tagDefs as $tagDef) {
            if(!empty($tagDef->getModNames())) {
                $tagsWithMods++;
                $this->writeTagFile($game, $tagDef, $tagsFolder);
            }
        }

        $this->writeTagsIndex($game, $tagDefs, $totalMods, $totalTags, $tagsWithMods);

        echo "Done.\n";
    }

    /**
     * @param TagDef[] $tagDefs
     */
    private function writeTagsIndex(Game $game, array $tagDefs, int $totalMods, int $totalTags, int $tagsWithMods) : void
    {
        $gameId = $game->getVortexID();
        $lines = array();
        $lines[] = "# ".$game->getLabel()." tag reference\n";
        $lines[] = "\n";
        $lines[] = "These are all tags used to describe mods in Vortex for ".$game->getLabel().",\n";
        $lines[] = "according to the mods used in a local Vortex database backup.\n";
        $lines[] = "\n";
        $lines[] = $this->resolveTimestampHeader($game);
        $lines[] = "\n";
        $lines[] = "## Stats\n";
        $lines[] = "\n";
        $lines[] = sprintf("- **Total mods:** %d\n", $totalMods);
        $lines[] = sprintf("- **Total tags:** %d\n", $totalTags);
        $lines[] = sprintf("- **Tags with mods:** %d\n", $tagsWithMods);
        $lines[] = "\n";
        $lines[] = "## Tags\n";
        $lines[] = "\n";
        $lines[] = "Each tag links to a dedicated page with the full mod list.\n";
        $lines[] = "\n";
        $lines[] = "| Tag | Description | Mods |\n";
        $lines[] = "|-----|-------------|------|\n";

        foreach($tagDefs as $tagDef)
        {
            $modNames = $tagDef->getModNames();
            $modCount = count($modNames);
            $title = $this->resolveTitle($tagDef);
            $slug = slugify($title);
            $desc = $tagDef->getDescription();

            if(empty($desc)) {
                $label = $tagDef->getLabel();
                $desc = !empty($label) ? $label : '';
            }

            $truncatedDesc = mb_strlen($desc) > 80 ? mb_substr($desc, 0, 77) . '...' : $desc;

            if($modCount > 0) {
                $lines[] = sprintf(
                    "| [%s](tags/%s.md) | %s | %d |\n",
                    titleify($tagDef->getName()),
                    $slug,
                    $truncatedDesc,
                    $modCount
                );
            } else {
                $lines[] = sprintf(
                    "| %s | %s | %d |\n",
                    titleify($tagDef->getName()),
                    $truncatedDesc,
                    $modCount
                );
            }
        }

        $lines[] = "\n";

        $gameFolder = OUTPUT_FOLDER.'/'.$gameId;
        $fileName = 'tags.md';

        $file = FileInfo::factory($gameFolder.'/'.$fileName)
            ->putContents(implode("", $lines));

        $outputFolder = $game->getOptions()->getOutputFolder();
        if($outputFolder !== null) {
            echo sprintf('- Also copied to output folder: [%s].', $outputFolder);
            $file->copyTo($outputFolder.'/'.$fileName);
        }
    }

    private function writeTagFile(Game $game, TagDef $tagDef, FolderInfo $tagsFolder) : void
    {
        $title = $this->resolveTitle($tagDef);
        $slug = slugify($title);
        $modNames = $tagDef->getModNames();

        $foundLines = array();
        $missingLines = array();

        foreach($modNames as $modName) {
            $result = $this->renderTagModLine($game, $tagDef, $modName);
            if($result['missing']) {
                $missingLines[] = $result['line'];
            } else {
                $foundLines[] = $result['line'];
            }
        }

        $totalCount = count($foundLines) + count($missingLines);

        $lines = array();
        $lines[] = sprintf("# %s (%d mods)\n", $title, $totalCount);
        $lines[] = "\n";
        $lines[] = "[Back to tag index](../tags.md)\n";
        $lines[] = "\n";

        $desc = $tagDef->getDescription();
        if(!empty($desc)) {
            $lines[] = sprintf("%s\n", $desc);
            $lines[] = "\n";
        }

        $url = $tagDef->getURL();
        if(!empty($url)) {
            $lines[] = sprintf("This tag is related to a mod. [Mod homepage](%s)\n", $url);
            $lines[] = "\n";
        }

        $requires = $tagDef->getRequires();
        if(!empty($requires)) {
            $reqLinks = array_map(
                function(string $req) use ($game) : string {
                    return $this->resolveTagLink($game, $req);
                },
                $requires
            );
            $lines[] = sprintf("**Requires:** %s\n", implode(', ', $reqLinks));
            $lines[] = "\n";
        }

        $requiredBy = $this->resolveRequiredBy($game, $tagDef);
        if(!empty($requiredBy)) {
            $lines[] = sprintf("**Required by:** %s\n", implode(', ', $requiredBy));
            $lines[] = "\n";
        }

        if(!empty($foundLines)) {
            $lines[] = sprintf("## Active mods (%d)\n", count($foundLines));
            $lines[] = "\n";
            array_push($lines, ...$foundLines);
            $lines[] = "\n";
        }

        if(!empty($missingLines)) {
            $lines[] = sprintf("## Missing mods (%d)\n", count($missingLines));
            $lines[] = "\n";
            $lines[] = "These mods were not found in the current Vortex database.\n";
            $lines[] = "\n";
            array_push($lines, ...$missingLines);
            $lines[] = "\n";
        }

        FileInfo::factory($tagsFolder.'/'.$slug.'.md')
            ->putContents(implode("", $lines));
    }

    private function resolveTagLink(Game $game, string $tagName) : string
    {
        $tagDefs = $game->getTagDefs();
        if(!$tagDefs->idExists($tagName)) {
            return '`'.$tagName.'`';
        }

        $tagDef = $tagDefs->getByID($tagName);
        if(empty($tagDef->getModNames())) {
            return '`'.$tagName.'`';
        }

        $title = $this->resolveTitle($tagDef);
        $slug = slugify($title);
        return sprintf('[%s](%s.md)', titleify($tagName), $slug);
    }

    /**
     * @return string[]
     */
    private function resolveRequiredBy(Game $game, TagDef $tagDef) : array
    {
        $result = array();
        foreach($game->getTagDefs()->getAll() as $otherTag) {
            if(in_array($tagDef->getName(), $otherTag->getRequires(), true)) {
                $result[] = $this->resolveTagLink($game, $otherTag->getName());
            }
        }
        return $result;
    }

    // endregion

    // region: Mods Reference

    private function writeGameModsReference(Game $game) : void
    {
        echo "  - Writing mods reference...";

        $gameId = $game->getVortexID();
        $modsFolder = FolderInfo::factory(OUTPUT_FOLDER.'/'.$gameId.'/mods')->create();

        /** @var array<string, Mod[]> $cats */
        $cats = array();
        foreach ($game->getMods()->getAll() as $mod) {
            $cat = $mod->getCategory();
            if(!isset($cats[$cat])) {
                $cats[$cat] = array();
            }
            $cats[$cat][] = $mod;
        }

        uksort($cats, 'strnatcasecmp');

        foreach($cats as $cat => $mods) {
            $this->writeCategoryFile($game, $cat, $mods, $modsFolder);
        }

        $this->writeModsIndex($game, $cats);

        echo "Done.\n";
    }

    /**
     * @param array<string, Mod[]> $cats
     */
    private function writeModsIndex(Game $game, array $cats) : void
    {
        $gameId = $game->getVortexID();
        $totalMods = 0;
        foreach($cats as $mods) {
            $totalMods += count($mods);
        }

        $lines = array();
        $lines[] = "# ".$game->getLabel()." mod reference\n";
        $lines[] = "\n";
        $lines[] = "These are all mods used in Vortex for ".$game->getLabel().",\n";
        $lines[] = "according to the mods used in a local Vortex database backup.\n";
        $lines[] = "\n";
        $lines[] = $this->resolveTimestampHeader($game);
        $lines[] = "\n";
        $lines[] = "## Stats\n";
        $lines[] = "\n";
        $lines[] = sprintf("- **Total mods:** %d\n", $totalMods);
        $lines[] = sprintf("- **Categories:** %d\n", count($cats));
        $lines[] = "\n";
        $lines[] = "## Categories\n";
        $lines[] = "\n";
        $lines[] = "Each category links to a dedicated page with full mod details.\n";
        $lines[] = "\n";
        $lines[] = "| Category | Mods | Common tags |\n";
        $lines[] = "|----------|------|-------------|\n";

        foreach ($cats as $cat => $mods) {
            $catSlug = slugify($cat);
            $commonTags = $this->resolveCommonTags($mods);
            $tagsCell = !empty($commonTags) ? implode(', ', $commonTags) : '—';
            $lines[] = sprintf(
                "| [%s](mods/%s.md) | %d | %s |\n",
                $cat,
                $catSlug,
                count($mods),
                $tagsCell
            );
        }

        $lines[] = "\n";

        $gameFolder = OUTPUT_FOLDER.'/'.$gameId;
        $fileName = 'mods.md';

        $file = FileInfo::factory($gameFolder.'/'.$fileName)
            ->putContents(implode("", $lines));

        $outputFolder = $game->getOptions()->getOutputFolder();
        if($outputFolder !== null) {
            echo sprintf('- Also copied to output folder: [%s].', $outputFolder);
            $file->copyTo($outputFolder.'/'.$fileName);
        }
    }

    /**
     * @param Mod[] $mods
     */
    private function writeCategoryFile(Game $game, string $cat, array $mods, FolderInfo $modsFolder) : void
    {
        $catSlug = slugify($cat);

        $lines = array();
        $lines[] = sprintf("# %s (%d mods)\n", $cat, count($mods));
        $lines[] = "\n";
        $lines[] = "[Back to mod index](../mods.md)\n";
        $lines[] = "\n";

        foreach ($mods as $mod) {
            $endorsed = $mod->isEndorsed() ? ' ⭐' : '';
            $lines[] = sprintf("## %s%s\n", titleify($mod->getName()), $endorsed);
            $lines[] = "\n";

            $lines[] = sprintf('Category: %s  '.PHP_EOL, $mod->getCategory());

            $homepage = $mod->getHomepage();
            if (!empty($homepage)) {
                $lines[] = sprintf('Homepage: [%s](%s)  ' . PHP_EOL, parseURL($mod->getHomepage())->getHost(), $mod->getHomepage());
            }

            $compatibility = $this->resolveGenderCompatibility($mod);
            if($compatibility !== null) {
                $lines[] = sprintf('Compatibility: %s  '.PHP_EOL, $compatibility);
            }

            $tags = $mod->getInheritedTags();
            $tags = $this->filterDisplayTags($tags);
            if (!empty($tags)) {
                $lines[] = "Tags: `".implode("`, `", $tags)."`\n";
            }

            $comments = $mod->getComments();
            if(!empty($comments)) {
                $lines[] = "Notes: ".$comments."\n";
            }

            $lines[] = "\n";
        }

        FileInfo::factory($modsFolder.'/'.$catSlug.'.md')
            ->putContents(implode("", $lines));
    }

    private const GENDER_TAGS = array('FemV', 'MaleV', 'BothV', 'SeparateV');

    private function resolveGenderCompatibility(Mod $mod) : ?string
    {
        $directTags = $mod->getTags();
        $parts = array();

        if(in_array('BothV', $directTags, true)) {
            $parts[] = 'Male and female V';
        } elseif(in_array('SeparateV', $directTags, true)) {
            $parts[] = 'Male and female V (separate downloads)';
        } else {
            if(in_array('FemV', $directTags, true)) {
                $parts[] = 'Female V';
            }
            if(in_array('MaleV', $directTags, true)) {
                $parts[] = 'Male V';
            }
        }

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * @param string[] $tags
     * @return string[]
     */
    private function filterDisplayTags(array $tags) : array
    {
        return array_values(array_filter(
            $tags,
            static function(string $tag) : bool {
                return !in_array($tag, self::GENDER_TAGS, true);
            }
        ));
    }

    /**
     * @param Mod[] $mods
     * @return string[]
     */
    private function resolveCommonTags(array $mods) : array
    {
        $tagCounts = array();
        foreach($mods as $mod) {
            foreach($mod->getTags() as $tag) {
                if(in_array($tag, self::GENDER_TAGS, true)) {
                    continue;
                }
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }

        arsort($tagCounts);

        $total = count($mods);
        $result = array();
        foreach($tagCounts as $tag => $count) {
            // Only include tags present in at least 20% of the category's mods
            if($count < $total * 0.2) {
                break;
            }
            $result[] = $tag;
            if(count($result) >= 5) {
                break;
            }
        }

        return $result;
    }

    // endregion
}

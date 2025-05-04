<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\ArrayDataCollection;
use AppUtils\Collections\BaseStringPrimaryCollection;

/**
 * @package VortexModExporter
 * @subpackage Mods
 *
 * @method Mod getByID(string $id)
 * @method Mod getDefault()
 * @method Mod[] getAll()
 */
class Mods extends BaseStringPrimaryCollection
{
    private Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function getDefaultID(): string
    {
        return $this->getAutoDefault();
    }

    protected function registerItems(): void
    {
        foreach($this->game->getModData() as $modName => $modData)
        {
            $this->registerItem(new Mod($this->game, $modName, ArrayDataCollection::create($modData)));
        }
    }
}

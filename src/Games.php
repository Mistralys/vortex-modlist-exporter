<?php
/**
 * @package VortexModExporter
 * @subpackage Games
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use AppUtils\Collections\BaseStringPrimaryCollection;
use AppUtils\FileHelper;
use AppUtils\FileHelper\JSONFile;

/**
 * @package VortexModExporter
 * @subpackage Games
 *
 * @method Game getByID(string $id)
 * @method Game getDefault()
 * @method Game[] getAll()
 */
class Games extends BaseStringPrimaryCollection
{
    public const UNKNOWN_CATEGORY_NAME = 'Unknown';
    public const PREFIX_UNUSED = 'ZZ -';
    public const PREFIX_AWAIT_UPDATE = 'ZY -';
    private static ?Games $instance = null;

    public static function getInstance() : Games
    {
        if(!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getDefaultID(): string
    {
        return $this->getAutoDefault();
    }

    protected function registerItems(): void
    {
        foreach($this->detectFiles() as $file) {
            $this->registerItem(new Game($file));
        }
    }

    /**
     * @return JSONFile[]
     */
    private function detectFiles() : array
    {
        return FileHelper::createFileFinder(__DIR__.'/../games')
            ->includeExtension('json')
            ->getFiles()
            ->typeJSON();
    }
}

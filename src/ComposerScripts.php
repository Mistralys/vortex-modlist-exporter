<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

use Mistralys\VortexModExporter\ComposerScripts\ExportModlist;
use Mistralys\VortexModExporter\ComposerScripts\GenerateDocs;

class ComposerScripts
{
    public static function exportModlist() : void
    {
        self::init();

        new ExportModlist()->export();
    }

    public static function generateDocs() : void
    {
        self::init();

        new GenerateDocs()->generate();
    }

    public static function build() : void
    {
        self::init();

        self::exportModlist();
        self::generateDocs();
    }

    private static bool $initialized = false;

    private static function init() : void
    {
        if(self::$initialized) {
            return;
        }

        self::$initialized = true;

        require_once __DIR__.'/../bin/prepend.php';
    }
}

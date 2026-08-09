<?php

declare(strict_types=1);

namespace Mistralys\VortexModExporter\ModLint;

/**
 * A single issue raised by a lint rule when checking a mod during export.
 *
 * @package VortexModExporter
 * @subpackage ModLint
 */
class ModLintIssue
{
    public const TYPE_NOTICE  = 'NOTICE';
    public const TYPE_WARNING = 'WARNING';
    public const TYPE_ERROR   = 'ERROR';

    private string $type;
    private string $modName;
    private string $message;

    /**
     * @param string $type    One of the TYPE_* constants.
     * @param string $modName Clean mod name the issue applies to.
     * @param string $message Human-readable description of the problem.
     */
    public function __construct(string $type, string $modName, string $message)
    {
        $this->type    = $type;
        $this->modName = $modName;
        $this->message = $message;
    }

    /**
     * Issue severity: one of {@see self::TYPE_NOTICE}, {@see self::TYPE_WARNING}, or {@see self::TYPE_ERROR}.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Clean name of the mod that triggered this issue.
     */
    public function getModName(): string
    {
        return $this->modName;
    }

    /**
     * Human-readable description of the issue.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns a single-line CLI-ready string for this issue.
     *
     * Format: "    [WARNING] Mod Name Here: Issue message text."
     */
    public function format(): string
    {
        return sprintf('    [%s] %s: %s', $this->type, $this->modName, $this->message);
    }
}

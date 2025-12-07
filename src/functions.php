<?php
/**
 * Common functions for the command line tools.
 *
 * @package VortexModExporter
 * @subpackage Command Line
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

function slugify(string $label) : string
{
    $label = str_replace(' ', '-', strtolower($label));
    $label = str_replace('_', '-', $label);
    $label = preg_replace('/[^a-z0-9\-]/', '', $label);

    return $label;
}

function titleify(string $title) : string
{
    return str_replace('_', ' ', $title);
}

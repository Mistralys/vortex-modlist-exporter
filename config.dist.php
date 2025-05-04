<?php
/**
 * Configuration file for the Vortex Mod Exporter
 * with all required configuration settings.
 *
 * @package VortexModExporter
 * @subpackage Configuration
 */

declare(strict_types=1);

namespace Mistralys\VortexModExporter;

/**
 * Path to Vortex's AppData folder, where it
 * stores its database files.
 *
 */
const VORTEX_APPDATA_FOLDER = 'C:/Users/Username/AppData/Roaming/Vortex';

/**
 * Game names to limit the export to, e.g. `cyberpunk2077`.
 * If the list is empty, all games will be exported.
 *
 * > NOTE: The game names used by Vortex are always lowercase.
 */
const EXPORT_GAMES = array();

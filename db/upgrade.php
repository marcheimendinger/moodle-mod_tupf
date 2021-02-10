<?php

/**
 * Module upgrade script.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define upgrade steps to be performed to upgrade the plugin from the old version to the current one.
 *
 * @param int $oldversion Version number the plugin is being upgraded from.
 */
function xmldb_tupf_upgrade($oldversion) {
    global $CFG;

    return true;
}
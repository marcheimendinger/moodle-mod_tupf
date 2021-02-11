<?php

/**
 * Caches declaration for the module.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    /**
     * This cache object is used when a user is reviewing words.
     *
     * @key int A TUPF instance ID (`id` from `tupf` table).
     * @value [int] An array of words IDs (`id` from `tupf_words` table).
     */
    'reviewingwords' => [
        'mode' => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
];
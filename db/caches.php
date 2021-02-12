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
     * Contains all words IDs (for each module instance) when a user is reviewing words.
     *
     * @key int A TUPF instance ID (`id` from `tupf` table).
     * @value [int] An array of words IDs (`id` from `tupf_words` table).
     */
    'reviewingwordsids' => [
        'mode' => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => true,
    ],

    /**
     * Contains the index of the current word to review (for each module instance) when a user is reviewing words.
     *
     * @key int A TUPF instance ID (`id` from `tupf` table).
     * @value int An array index (corresponding to an element in `reviewingwords` cache).
     */
    'reviewingwordindex' => [
        'mode' => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
];
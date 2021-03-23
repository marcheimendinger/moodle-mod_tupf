<?php

/**
 * Defines message providers (types of message sent) for the module.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    // Texts translation success.
    'translationconfirmation' => [
        'capability' => 'mod/tupf:addinstance',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDOFF,
        ],
    ],
    // Text translation error.
    'translationerror' => [
        'capability' => 'mod/tupf:addinstance',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDOFF,
        ],
    ],
];
<?php

/**
 * Private module utility functions.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Checks user's authentication and capabilities, and gets the current TUPF object.
 *
 * @param string $url Current page URL.
 * @param int $coursemoduleid Course module ID.
 * @param string $capability Required capability to access the current page. Defaults to 'mod/tupf:review'.
 * @return object TUPF instance from `tupf` table.
 */
function authenticate_and_get_tupf(string $url, int $coursemoduleid, string $capability = 'mod/tupf:review') {
    global $DB, $PAGE;

    if (!$cm = get_coursemodule_from_id('tupf', $coursemoduleid)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
        print_error('coursemisconf');
    }
    if (!$tupf = $DB->get_record('tupf', ['id' => $cm->instance])) {
        print_error('invalidcoursemodule');
    }

    require_course_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability($capability, $context);

    $PAGE->set_url($url, ['id' => $coursemoduleid]);
    $PAGE->set_title($course->shortname.': '.$tupf->name);
    $PAGE->set_heading($course->fullname);

    // Throws an error if texts are not ready (e.g. translated) yet.
    if (!$DB->record_exists('tupf_texts', ['tupfid' => $tupf->id]) ||
            $DB->record_exists('tupf_texts', ['tupfid' => $tupf->id, 'translated' => false])) {
        print_error('errorpendingtexts', 'tupf');
    }

    return $tupf;
}

/**
 * Replacement to built-in `substr_replace` with multi-byte handling.
 * From: https://stackoverflow.com/questions/11239597/substr-replace-encoding-in-php/35638691
 *
 * @param string $original The input string.
 * @param string $replacement The replacement string.
 * @param int $position Position offset.
 * @param int|null $length Optional length of the replacement.
 * @return string The replaced text.
 */
function mb_substr_replace($original, $replacement, $position, $length = 0) {
    $startString = mb_substr($original, 0, $position, 'UTF-8');
    $endString = mb_substr($original, $position + $length, mb_strlen($original), 'UTF-8');

    $out = $startString . $replacement . $endString;

    return $out;
}
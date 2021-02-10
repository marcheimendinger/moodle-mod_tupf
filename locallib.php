<?php

/**
 * Private TUPF module utility functions.
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
 * @return object
 */
function authenticate_and_get_tupf(string $url, int $coursemoduleid)
{
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
    require_capability('mod/tupf:review', $context);

    $PAGE->set_url($url, ['id' => $coursemoduleid]);
    $PAGE->set_title($course->shortname.': '.$tupf->name);
    $PAGE->set_heading($course->fullname);

    return $tupf;
}
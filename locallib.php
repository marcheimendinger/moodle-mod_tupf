<?php

/**
 * Private module utility functions.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die();

// Number of times the translation task will try to process a text before stopping.
define('TUPF_MAX_TRANSLATION_ATTEMPTS', 3);

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

    return $tupf;
}

/**
 * Checks whether texts are ready for the current TUPF instance.
 *
 * @param int $tupfid TUPF instance ID from `tupf` table.
 * @param boolean $throwerror Automatically throws an error if texts are not ready. Defaults to `true`.
 * @return boolean Whether texts are ready.
 */
function tupf_texts_ready(int $tupfid, bool $throwerror = true): bool {
    global $DB;

    $textsready = $DB->record_exists('tupf_texts', ['tupfid' => $tupfid]) &&
        !$DB->record_exists('tupf_texts', ['tupfid' => $tupfid, 'translated' => false]);

    if ($throwerror && !$textsready) {
        print_error('errorpendingtexts', 'tupf');
    }

    return $textsready;
}

/**
 * Cleans new HTML text before recording it.
 *
 * @param string $text HTML text.
 * @return string Cleaned HTML text.
 */
function tupf_clean_text(string $text): string {
    $newtext = str_replace('&nbsp;', ' ', $text); // Replaces non-breaking spaces with standard spaces.
    $newtext = preg_replace('#<a.*?>(.*?)</a>#is', '\1', $newtext); // Removes links from text.
    return $newtext;
}

/**
 * Inserts texts inside `tupf_texts` table.
 * Intended to be used in `lib.php`.
 *
 * @param int $tupfid
 * @param array $textsdata
 * @return void
 */
function tupf_insert_texts($tupfid, $textsdata) {
    global $DB;

    $texts = [];
    foreach ($textsdata as $value) {
        $text = $value['text']; // Editor form field returns an array.
        $text = tupf_clean_text($text);
        if (isset($text) && $text <> '') {
            $texts[] = [
                'tupfid' => $tupfid,
                'text' => $text,
                'timemodified' => time(),
            ];
        }
    }

    $DB->insert_records('tupf_texts', $texts);

    // Triggers the texts translation background task.
    \core\task\manager::queue_adhoc_task(new \mod_tupf\task\translate_texts, true);
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
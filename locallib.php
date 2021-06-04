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
 * Returns the currently selected text ID from cache.
 * When no selected text is present, automatically tries to select a text based on words selection.
 *
 * @param int $tupfid TUPF instance ID from `tupf` table.
 * @return int|bool Text ID from `tupf_texts` table or `false` if nonexistent.
 */
function tupf_get_selected_text(int $tupfid) {
    global $DB, $USER;

    $selectedtextidcache = cache::make('mod_tupf', 'selectedtextid');
    $selectedtextid = $selectedtextidcache->get($tupfid);

    // No selected text. Tries to select the first one with selected words if any.
    if ($selectedtextid === false) {
        $sql = 'SELECT {tupf_words}.textid
        FROM {tupf_words}
        INNER JOIN {tupf_selected_words}
        ON {tupf_selected_words}.wordid = {tupf_words}.id
        WHERE userid = ?
        GROUP BY {tupf_words}.textid';
        $firstselectedtextid = $DB->get_record_sql($sql, [$USER->id], IGNORE_MULTIPLE);

        if ($firstselectedtextid !== false) { // At least one text has words selected.
            $firstselectedtextid = $firstselectedtextid->textid;
            tupf_set_selected_text($tupfid, $firstselectedtextid);
            $selectedtextid = $firstselectedtextid;
        } else {
            return false;
        }
    }

    return $selectedtextid;
}

/**
 * Sets the currently selected text ID to cache.
 *
 * @param int $tupfid TUPF instance ID from `tupf` table.
 * @param int $textid Text ID from `tupf_texts` table.
 * @return void
 */
function tupf_set_selected_text(int $tupfid, int $textid) {
    $selectedtextidcache = cache::make('mod_tupf', 'selectedtextid');

    if ($selectedtextidcache->get($tupfid) !== $textid) {
        $selectedtextidcache->set($tupfid, $textid);

        // Resets reviewing word index cache to avoid outdated data.
        $reviewingwordindexcache = cache::make('mod_tupf', 'reviewingwordindex');
        $reviewingwordindexcache->delete($tupfid);
    }
}

/**
 * Checks whether words are selected for the currently selected text for the current user.
 *
 * @param int $tupfid TUPF instance ID from `tupf` table.
 * @return boolean
 */
function tupf_words_are_selected_for_selected_text(int $tupfid): bool {
    global $DB, $USER;

    $selectedtextid = tupf_get_selected_text($tupfid);

    $sql = 'SELECT {tupf_words}.id
        FROM {tupf_words}
        INNER JOIN {tupf_selected_words}
        ON {tupf_selected_words}.wordid = {tupf_words}.id
        WHERE textid = ? AND userid = ?';

    return $DB->record_exists_sql($sql, [$selectedtextid, $USER->id]);
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
 * Sends a module notification to a given user.
 *
 * @param string $name Notification name from `messages.php`.
 * @param integer $userid User ID to send notification to.
 * @param string $subject Subject text.
 * @param string $body Body text without HTML nor Markdown.
 * @param int $tupfid TUPF module ID.
 * @param string $url Link URL relatively to the module directory (`/mod/tupf/`).
 * @param string $linkname Link name.
 * @return void
 */
function tupf_send_notification(string $name, int $userid, string $subject, string $body, int $tupfid, string $url, string $linkname) {
    $message = new \core\message\message();
    $message->component = 'mod_tupf';
    $message->name = $name; // Notification name from `messages.php`.
    $message->userfrom = \core_user::get_noreply_user();
    $message->userto = $userid;
    $message->subject = $subject;
    $message->fullmessage = $body;
    $message->fullmessageformat = FORMAT_MARKDOWN;
    $message->fullmessagehtml = '<p>'.$body.'</p>';
    $message->smallmessage = $body;
    $message->notification = true; // Notification generated from Moodle, not a user-to-user message.

    $coursemoduleid = get_coursemodule_from_instance('tupf', $tupfid)->id;
    $message->contexturl = (new \moodle_url('/mod/tupf/'.$url, ['id' => $coursemoduleid]));
    $message->contexturlname = $linkname;

    message_send($message);
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
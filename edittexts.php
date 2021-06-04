<?php

/**
 * Texts editor user interface.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');

$coursemoduleid = required_param('id', PARAM_INT);
$deletetextid = optional_param('deletetextid', null, PARAM_INT);
$newtext = optional_param('newtext', null, PARAM_RAW);

$tupf = authenticate_and_get_tupf('/mod/tupf/edittexts.php', $coursemoduleid, 'mod/tupf:addinstance');

// Deletes text (with cascade) from database if submitted.
if (isset($deletetextid)) {
    require_sesskey();

    // Checks if the text belongs to the current TUPF instance (prevents deletion of text from another TUPF instance).
    $texttupfid = $DB->get_field('tupf_texts', 'tupfid', ['id' => $deletetextid]);
    if ($tupf->id !== $texttupfid) {
        print_error('erroraccessdenied', 'tupf');
    }

    $wordsids = array_keys($DB->get_records_menu('tupf_words', ['textid' => $deletetextid], 'id', 'id, textid'));

    foreach ($wordsids as $wordid) {
        if (!$DB->delete_records('tupf_selected_words', ['wordid' => $wordid])) {
            print_error('deletetexterror', 'tupf');
        }
    }

    if (!$DB->delete_records('tupf_words', ['textid' => $deletetextid])) {
        print_error('deletetexterror', 'tupf');
    }

    if (!$DB->delete_records('tupf_texts', ['id' => $deletetextid])) {
        print_error('deletetexterror', 'tupf');
    }
}

// Inserts new text in database if submitted.
if (!empty($newtext)) {
    require_sesskey();

    $newtext = tupf_clean_text($newtext);

    $textinstance = [
        'tupfid' => $tupf->id,
        'text' => $newtext,
        'timemodified' => time(),
    ];

    $DB->insert_record('tupf_texts', $textinstance);

    // Triggers the texts translation background task.
    \core\task\manager::queue_adhoc_task(new \mod_tupf\task\translate_texts, true);
}

$output = $PAGE->get_renderer('mod_tupf');

echo $output->header();

$translating = $DB->record_exists_select(
    'tupf_texts',
    'tupfid = ? AND translated = false AND translationattempts < ?',
    [$tupf->id, TUPF_MAX_TRANSLATION_ATTEMPTS]
);

echo $output->edittexts_heading($translating);

// Existing texts section.

$textset = $DB->get_recordset('tupf_texts', ['tupfid' => $tupf->id]);

if ($textset->valid()) {
    $textindex = 1;
    foreach ($textset as $text) {
        $sqluserscount = 'SELECT COUNT(DISTINCT(userid)) AS `count`
        FROM {tupf_words}
        INNER JOIN {tupf_selected_words}
        ON {tupf_selected_words}.wordid = {tupf_words}.id
        WHERE textid = ?';
        $userscount = $DB->get_record_sql($sqluserscount, [$text->id])->count;

        echo $output->edittexts_text($text, $textindex, $userscount);

        $textindex++;
    }

    $textset->close();
}

// Add new text section.

echo $output->edittexts_form();

echo $output->footer();
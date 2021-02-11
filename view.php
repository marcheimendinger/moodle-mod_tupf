<?php

/**
 * Entry point in the user interface of a module instance.
 *
 * Two possibilities:
 *  - New student: select words from a randomly selected text.
 *  - Recurring student: review his selected words using flashcards.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');

$coursemoduleid = required_param('id', PARAM_INT);

$tupf = authenticate_and_get_tupf('/mod/tupf/view.php', $coursemoduleid);

$output = $PAGE->get_renderer('mod_tupf');

echo $output->header();

// Inserts words selection in database if submitted.
$selectedwordsidsstring = optional_param('selected-words', '', PARAM_TEXT);
if (!empty($selectedwordsidsstring) &&
        !$DB->record_exists('tupf_selected_words', ['tupfid' => $tupf->id, 'userid' => $USER->id])) {
    $selectedwordsids = explode(',', $selectedwordsidsstring);
    $selectedwordsoject = [];
    foreach ($selectedwordsids as $wordid) {
        $selectedwordsoject[] = ['tupfid' => $tupf->id, 'wordid' => $wordid, 'userid' => $USER->id];
    }
    $DB->insert_records('tupf_selected_words', $selectedwordsoject);
}

if ($DB->record_exists('tupf_selected_words', ['tupfid' => $tupf->id, 'userid' => $USER->id])) { // Words review
    echo $output->home_buttons($coursemoduleid, $tupf->name);
} else { // Words selection
    $textsids = $DB->get_fieldset_select('tupf_texts', 'id', 'tupfid = ? AND translated = TRUE', [$tupf->id]);
    shuffle($textsids);
    $textid = $textsids[0];
    $text = $DB->get_record('tupf_texts', ['id' => $textid])->text;
    $words = $DB->get_records('tupf_words', ['textid' => $textid], 'position');

    echo $output->words_selection($text, $words);
}

echo $output->footer();
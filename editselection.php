<?php

/**
 * Words selection editor user interface.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');

$coursemoduleid = required_param('id', PARAM_INT);
$selectedwordsidsstring = optional_param('selected-words', '', PARAM_TEXT);

$tupf = authenticate_and_get_tupf('/mod/tupf/editselection.php', $coursemoduleid);

$PAGE->navbar->add(get_string('selectedwords', 'tupf'), new moodle_url('/mod/tupf/words.php', ['id' => $coursemoduleid]));
$PAGE->navbar->add(get_string('editselection', 'tupf'), $PAGE->url);

$selectedwordsidsdatabase = $DB->get_fieldset_select('tupf_selected_words', 'wordid', 'tupfid = ? AND userid = ?', [$tupf->id, $USER->id]);

if (empty($selectedwordsidsdatabase)) {
    print_error('notavailable');
}

// Updates words selection in database if submitted.
// Diff to add/delete words to reflect changes in selection.
if (!empty($selectedwordsidsstring)) {
    require_sesskey();

    $selectedwordsids = explode(',', $selectedwordsidsstring);

    $wordstoinsert = [];
    foreach ($selectedwordsids as $wordid) {
        if (!in_array($wordid, $selectedwordsidsdatabase)) {
            $wordstoinsert[] = ['tupfid' => $tupf->id, 'wordid' => $wordid, 'userid' => $USER->id];
        }
    }
    $DB->insert_records('tupf_selected_words', $wordstoinsert);

    foreach ($selectedwordsidsdatabase as $wordid) {
        if (!in_array($wordid, $selectedwordsids)) {
            $DB->delete_records('tupf_selected_words', ['tupfid' => $tupf->id, 'wordid' => $wordid, 'userid' => $USER->id]);
        }
    }

    $url = new moodle_url('/mod/tupf/words.php', ['id' => $coursemoduleid]);
    redirect($url);
}

$output = $PAGE->get_renderer('mod_tupf');

echo $output->header();

$textid = $DB->get_field('tupf_words', 'textid', ['id' => $selectedwordsidsdatabase[0]]);
$text = $DB->get_record('tupf_texts', ['id' => $textid])->text;
$words = $DB->get_records('tupf_words', ['textid' => $textid], 'position');

echo $output->words_selection($text, $words, $selectedwordsidsdatabase);

echo $output->footer();
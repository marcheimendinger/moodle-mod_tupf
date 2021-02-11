<?php

/**
 * Words reviewing user interface.
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

echo $output->words_review_heading();

$cache = cache::make('mod_tupf', 'reviewingwords');
$reviewingwordsids = $cache->get($tupf->id);

function get_word_flashcard(int $wordid) {
    global $DB, $USER, $output, $coursemoduleid, $tupf;

    $selectedword = $DB->get_record(
        'tupf_selected_words',
        ['tupfid' => $tupf->id, 'wordid' => $wordid, 'userid' => $USER->id]
    );

    if (empty($selectedword)) {
        print_error('notavailable');
    }

    $selectedword->showncount += 1;
    $selectedword->timelastreviewed = time();
    $DB->update_record('tupf_selected_words', $selectedword);

    $word = $DB->get_record('tupf_words', ['id' => $wordid]);

    if (empty($word)) {
        print_error('notavailable');
    }

    if (empty($word)) {
        print_error('notavailable');
    }

    return $output->words_review_flashcard($coursemoduleid, $word);
}

if ($reviewingwordsids === false) { // Start review.
    $wordsids = array_keys($DB->get_records_menu(
        'tupf_selected_words',
        ['tupfid' => $tupf->id, 'userid' => $USER->id],
        'timelastreviewed DESC', // Oldest words first (we start at the end of the array).
        'wordid'
    ));

    if (empty($wordsids)) {
        print_error('notavailable');
    }

    $wordid = array_pop($wordsids);
    $cache->set($tupf->id, $wordsids);

    echo get_word_flashcard($wordid);
} else if (empty($reviewingwordsids)) { // End review.
    $cache->delete($tupf->id);

    echo $output->words_review_end_buttons($coursemoduleid);
} else { // During a review.
    $wordid = array_pop($reviewingwordsids);
    $cache->set($tupf->id, $reviewingwordsids);

    echo get_word_flashcard($wordid);
}

echo $output->footer();
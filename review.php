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
$gotoprevious = optional_param('previous', 'false', PARAM_BOOL);

$tupf = authenticate_and_get_tupf('/mod/tupf/view.php', $coursemoduleid);

$output = $PAGE->get_renderer('mod_tupf');

echo $output->header();

echo $output->words_review_heading();

$reviewingwordsidscache = cache::make('mod_tupf', 'reviewingwordsids');
$reviewingwordindexcache = cache::make('mod_tupf', 'reviewingwordindex');

$reviewingwordindex = $reviewingwordindexcache->get($tupf->id);

function get_word_flashcard(array $wordsids, int $wordindex) {
    global $DB, $USER, $output, $coursemoduleid, $tupf;

    $wordid = $wordsids[$wordindex];

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

    return $output->words_review_flashcard($coursemoduleid, $word, $wordindex + 1, count($wordsids));
}

if ($reviewingwordindex === false) { // Start review.
    $wordsids = array_keys($DB->get_records_menu(
        'tupf_selected_words',
        ['tupfid' => $tupf->id, 'userid' => $USER->id],
        'timelastreviewed ASC', // Oldest or never reviewed words first.
        'wordid'
    ));

    if (empty($wordsids)) {
        print_error('notavailable');
    }

    $reviewingwordsidscache->set($tupf->id, $wordsids);

    $wordindex = 0;
    $reviewingwordindexcache->set($tupf->id, $wordindex);

    echo get_word_flashcard($wordsids, $wordindex);
} else { // During a review.
    $reviewingwordsids = $reviewingwordsidscache->get($tupf->id);

    if ($gotoprevious === 1) {
        $reviewingwordindex -= 1;
    } else {
        $reviewingwordindex += 1;
    }

    $reviewingwordindexcache->set($tupf->id, $reviewingwordindex);

    if ($reviewingwordindex < 0 || $reviewingwordindex >= count($reviewingwordsids)) { // End review.
        $reviewingwordsidscache->delete($tupf->id);
        $reviewingwordindexcache->delete($tupf->id);

        echo $output->words_review_end_buttons($coursemoduleid);
    } else {
        echo get_word_flashcard($reviewingwordsids, $reviewingwordindex);
    }
}

echo $output->footer();
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
$buttonaction = optional_param('buttonaction', null, PARAM_ACTION); // 'previous', 'nextcorrect', 'nextwrong'

$tupf = authenticate_and_get_tupf('/mod/tupf/review.php', $coursemoduleid);
tupf_texts_ready($tupf->id);

$PAGE->navbar->add(get_string('wordsreview', 'tupf'), $PAGE->url);

$output = $PAGE->get_renderer('mod_tupf');

echo $output->header();

echo $output->words_review_heading();

$reviewingwordsidscache = cache::make('mod_tupf', 'reviewingwordsids');
$reviewingwordindexcache = cache::make('mod_tupf', 'reviewingwordindex');

$reviewingwordindex = $reviewingwordindexcache->get($tupf->id);

/**
 * Gets the word from database and HTML from renderer.
 *
 * @param array $wordsids Word ID from `tupf_words` table.
 * @param integer $wordindex Current word index from cache.
 * @param boolean $backward Whether the user went backward from another word. Defaults to `false`.
 * @return string HTML content.
 */
function get_word_flashcard(array $wordsids, int $wordindex, bool $backward = false) {
    global $DB, $USER, $output, $coursemoduleid, $tupf;

    $wordid = $wordsids[$wordindex];

    $word = $DB->get_record('tupf_words', ['id' => $wordid]);

    if (empty($word)) {
        print_error('notavailable');
    }

    return $output->words_review_flashcard($coursemoduleid, $word, $wordindex + 1, count($wordsids), $backward);
}

if ($reviewingwordindex === false) { // Start review.
    $wordsids = array_keys($DB->get_records_menu(
        'tupf_selected_words',
        ['tupfid' => $tupf->id, 'userid' => $USER->id],
        null,
        'wordid'
    ));

    if (empty($wordsids)) {
        print_error('notavailable');
    }

    shuffle($wordsids);

    $reviewingwordsidscache->set($tupf->id, $wordsids);

    $wordindex = 0;
    $reviewingwordindexcache->set($tupf->id, $wordindex);

    echo get_word_flashcard($wordsids, $wordindex);
} else { // During a review.
    if (!empty($buttonaction)) {
        require_sesskey();
    }

    $reviewingwordsids = $reviewingwordsidscache->get($tupf->id);

    if ($buttonaction == 'previous') {
        $reviewingwordindex -= 1;
    } else if ($buttonaction == 'nextcorrect' || $buttonaction == 'nextwrong') {
        $previouswordid = $reviewingwordsids[$reviewingwordindex];

        $previousword = $DB->get_record(
            'tupf_selected_words',
            ['tupfid' => $tupf->id, 'wordid' => $previouswordid, 'userid' => $USER->id]
        );

        if (empty($previousword)) {
            print_error('notavailable');
        }

        if ($buttonaction == 'nextcorrect') {
            $previousword->correctcount += 1;
        }
        $previousword->showncount += 1;
        $previousword->timelastreviewed = time();

        $DB->update_record('tupf_selected_words', $previousword);

        $reviewingwordindex += 1;
    }

    if ($reviewingwordindex < 0 || $reviewingwordindex >= count($reviewingwordsids)) { // Ends review.
        $reviewingwordsidscache->delete($tupf->id);
        $reviewingwordindexcache->delete($tupf->id);

        echo $output->words_review_end_buttons($coursemoduleid);
    } else { // Shows flashcard.
        $reviewingwordindexcache->set($tupf->id, $reviewingwordindex);

        echo get_word_flashcard($reviewingwordsids, $reviewingwordindex, $buttonaction == 'previous');
    }
}

echo $output->footer();
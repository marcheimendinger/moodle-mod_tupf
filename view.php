<?php

/**
 * Displays a TUPF instance.
 *
 * Two possibilities:
 *  - New student: select words from a randomly selected text.
 *  - Recurring student: review his selected words using flashcards.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

require_once(__DIR__.'/../../config.php');

$coursemoduleid = required_param('id', PARAM_INT);

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

$PAGE->set_url('/mod/tupf/view.php', ['id' => $coursemoduleid]);
$PAGE->set_context($context);
$PAGE->set_title($course->shortname.': '.$tupf->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Shows error if texts are not ready (e.g. translated) yet.
if (!$DB->record_exists('tupf_texts', ['tupfid' => $tupf->id, 'translated' => true]) ||
        $DB->record_exists('tupf_texts', ['tupfid' => $tupf->id, 'translated' => false])) {
    print_error('errorpendingtexts', 'tupf');
}

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

// Shows error if JavaScript is disabled.
echo '<noscript><div class="alert alert-danger" role="alert">';
echo get_string('errornojavascript', 'tupf');
echo '</div></noscript>';

if ($DB->record_exists('tupf_selected_words', ['tupfid' => $tupf->id, 'userid' => $USER->id])) { // Flashcards
    echo html_writer::tag('h1', 'Flashcards');
} else { // Words selection
    $PAGE->requires->js_call_amd('mod_tupf/wordsselection', 'init');

    echo html_writer::tag('h1', get_string('wordsselection', 'tupf'));
    echo html_writer::tag('p', get_string('wordsselection_help', 'tupf'));

    $textsids = $DB->get_fieldset_select('tupf_texts', 'id', 'tupfid = ? AND translated = TRUE', [$tupf->id]);
    shuffle($textsids);
    $textid = $textsids[0];
    $text = $DB->get_record('tupf_texts', ['id' => $textid])->text;
    $words = $DB->get_records('tupf_words', ['textid' => $textid], 'position');

    $offset = 0;
    foreach ($words as $word) {
        $linkStart = '<a href="#" data-word-id="'.$word->id.'" class="tupf-word">';
        $startPosition = $word->position + $offset;
        $text = substr_replace($text, $linkStart, $startPosition, 0);
        $offset += strlen($linkStart);

        $linkEnd = '</a>';
        $endPosition = $word->position + strlen($word->language2raw) + $offset;
        $text = substr_replace($text, $linkEnd, $endPosition, 0);
        $offset += strlen($linkEnd);
    }

    echo $text;

    $form = html_writer::start_tag('form', [
        'id' => 'tupf-words-selection-form',
        'action' => $PAGE->url,
        'method' => 'post'
    ]);
    $form .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'selected-words']);
    $form .= html_writer::tag('input', null, ['type' => 'button', 'id' => 'tupf-submit-button', 'class' => 'btn btn-primary', 'value' => get_string('submit')]);
    $form .= html_writer::end_tag('form');

    echo $form;
}

echo $OUTPUT->footer();
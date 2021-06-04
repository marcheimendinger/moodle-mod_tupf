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
$selectedtextid = optional_param('selected-text', '', PARAM_TEXT);
$selectedwordsidsstring = optional_param('selected-words', '', PARAM_TEXT);

$tupf = authenticate_and_get_tupf('/mod/tupf/view.php', $coursemoduleid);
$textsready = tupf_texts_ready($tupf->id, false);

$output = $PAGE->get_renderer('mod_tupf');

echo $output->header();

if ($textsready) { // All texts are ready to be used.
    // Inserts words selection in database if submitted.
    if (!empty($selectedwordsidsstring)) {
        require_sesskey();

        $textid = tupf_get_selected_text($tupf->id);

        // Initial check to avoid inserting duplicates in database.
        if (!$DB->record_exists_sql(
            'SELECT *
            FROM {tupf_selected_words}
            INNER JOIN {tupf_words}
            ON {tupf_selected_words}.wordid = {tupf_words}.id
            WHERE tupfid = ? AND userid = ? AND textid = ?',
            [$tupf->id, $USER->id, $textid]
        )) {
            $selectedwordsids = explode(',', $selectedwordsidsstring);
            $selectedwordsoject = [];
            foreach ($selectedwordsids as $wordid) {
                $selectedwordsoject[] = ['tupfid' => $tupf->id, 'wordid' => $wordid, 'userid' => $USER->id];
            }
            $DB->insert_records('tupf_selected_words', $selectedwordsoject);
        }
    }

    // Randomly select a text (initial selection).
    if (!$DB->record_exists('tupf_selected_words', ['tupfid' => $tupf->id, 'userid' => $USER->id])) {
        $textsids = $DB->get_fieldset_select('tupf_texts', 'id', 'tupfid = ? AND translated = TRUE', [$tupf->id]);
        shuffle($textsids);
        $textid = $textsids[0];
        tupf_set_selected_text($tupf->id, $textid);
    }

    // Changes text selection.
    if (!empty($selectedtextid)) {
        require_sesskey();

        tupf_set_selected_text($tupf->id, $selectedtextid);
    }

    echo $output->admin_top_links();

    $textsids = $DB->get_fieldset_select('tupf_texts', 'id', 'tupfid = ?', [$tupf->id]);
    echo $output->text_selection($tupf->id, $textsids);

    if (tupf_words_are_selected_for_selected_text($tupf->id)) { // Words are selected for the current text.
        $reviewingwordindexcache = cache::make('mod_tupf', 'reviewingwordindex');
        $reviewingwords = $reviewingwordindexcache->get($tupf->id) !== false;
        echo $output->home_buttons(get_string('home', 'tupf'), $reviewingwords);
    } else { // No words selection for the current text.
        $selectedtextid = tupf_get_selected_text($tupf->id);

        $text = $DB->get_record('tupf_texts', ['id' => $selectedtextid])->text;
        $words = $DB->get_records('tupf_words', ['textid' => $selectedtextid], 'position');
        echo $output->words_selection($text, $words);
    }
} else { // Texts are not ready yet. Do not show them.
    if (has_capability('mod/tupf:addinstance', $PAGE->cm->context)) {
        $translating = $DB->record_exists_select(
            'tupf_texts',
            'tupfid = ? AND translated = false AND translationattempts < ?',
            [$tupf->id, TUPF_MAX_TRANSLATION_ATTEMPTS]
        );

        if ($translating) {
            if ($tupf->userid == $USER->id) {
                echo $output->error(get_string('errorpendingtextsadmin', 'tupf'));
            } else {
                echo $output->error(get_string('errorpendingtexts', 'tupf'));
            }
        } else {
            echo $output->error(get_string('errortranslationsfailed', 'tupf'));
        }

        echo $output->home_edit_texts_button();
    } else {
        echo $output->error(get_string('errorpendingtexts', 'tupf'));
    }
}

echo $output->footer();
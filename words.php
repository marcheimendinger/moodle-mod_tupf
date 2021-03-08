<?php

/**
 * Selected words list user interface.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');

$coursemoduleid = required_param('id', PARAM_INT);

$tupf = authenticate_and_get_tupf('/mod/tupf/words.php', $coursemoduleid);

$PAGE->navbar->add(get_string('selectedwords', 'tupf'), $PAGE->url);

$output = $PAGE->get_renderer('mod_tupf');

echo $output->header();

$words = $DB->get_records_sql(
    'SELECT language1, language2simplified, showncount, correctcount, timelastreviewed FROM {tupf_selected_words} INNER JOIN {tupf_words} ON {tupf_selected_words}.wordid = {tupf_words}.id WHERE tupfid = ? AND userid = ?',
    [$tupf->id, $USER->id]
);

if (empty($words)) {
    print_error('notavailable');
}

echo $output->words_list($words, $tupf, $coursemoduleid);

echo $output->footer();
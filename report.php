<?php

/**
 * Usage analytics user interface.
 * Only accessible to teachers.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');

$coursemoduleid = required_param('id', PARAM_INT);

$tupf = authenticate_and_get_tupf('/mod/tupf/report.php', $coursemoduleid, 'mod/tupf:readreport');

$output = $PAGE->get_renderer('mod_tupf');

echo $output->header();

$textset = $DB->get_recordset('tupf_texts', ['tupfid' => $tupf->id, 'translated' => true]);

if (!$textset->valid()) {
    $textset->close();

    print_error('notavailable');
}

echo $output->report_heading();

$textindex = 1;
foreach ($textset as $text) {
    $sqluserscount = 'SELECT COUNT(DISTINCT(userid)) AS `count`
        FROM {tupf_words}
        INNER JOIN mdl_tupf_selected_words
        ON {tupf_selected_words}.wordid = {tupf_words}.id
        WHERE textid = ?';
    $userscount = $DB->get_record_sql($sqluserscount, [$text->id])->count;

    $sqlwords = 'SELECT position, language2raw, count(userid) AS `userscount`
        FROM {tupf_words}
        INNER JOIN {tupf_selected_words}
        ON {tupf_selected_words}.wordid = {tupf_words}.id
        WHERE textid = ?
        GROUP BY wordid';
    $words = $DB->get_records_sql($sqlwords, [$text->id]);

    echo $output->report_text($text->text, $textindex, $userscount, $words);

    $textindex++;
}

$textset->close();

echo $output->footer();
<?php

/**
 * Interaction with the Moodle core.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds TUPF instance.
 *
 * @param object $data
 * @return int new tupf instance id
 */
function tupf_add_instance($data) {
    global $DB, $USER;

    require_once('locallib.php');

    $data->timemodified = time();
    $data->userid = $USER->id;

    $data->id = $DB->insert_record('tupf', $data);

    tupf_insert_texts($data->id, $data->text);

    return $data->id;
}

/**
 * Updates TUPF instance.
 *
 * @param object $data
 * @return bool true
 */
function tupf_update_instance($data) {
    global $DB;

    require_once('locallib.php');

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('tupf', $data);

    // Prevents texts insertion if already existing.
    if (!$DB->record_exists('tupf_texts', ['tupfid' => $data->id])) {
        tupf_insert_texts($data->id, $data->text);
    }

    return true;
}

/**
 * Deletes TUPF instance.
 *
 * @param int $id
 * @return bool true
 */
function tupf_delete_instance($id) {
    global $DB;

    if (!$tupf = $DB->get_record('tupf', ['id' => $id])) {
        return false;
    }

    $textsids = array_keys($DB->get_records_menu('tupf_texts', ['tupfid' => $tupf->id], 'id', 'id, tupfid'));

    $result = true;

    if (!$DB->delete_records('tupf_selected_words', ['tupfid' => $tupf->id])) {
        $result = false;
    }

    foreach ($textsids as $textid) {
        if (!$DB->delete_records('tupf_words', ['textid' => $textid])) {
            $result = false;
        }
    }

    if (!$DB->delete_records('tupf_texts', ['tupfid' => $tupf->id])) {
        $result = false;
    }

    if (!$DB->delete_records('tupf', ['id' => $tupf->id])) {
        $result = false;
    }

    return $result;
}

/**
 * Adds module specific settings to the settings block.
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $node The node to add module settings to
 */
function tupf_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $PAGE;

    if (has_capability('mod/tupf:addinstance', $PAGE->cm->context)) {
        $node->add(
            get_string('edittextsbutton', 'tupf'),
            new moodle_url('/mod/tupf/edittexts.php', ['id' => $PAGE->cm->id])
        );
    }

    if (has_capability('mod/tupf:readreport', $PAGE->cm->context)) {
        $node->add(
            get_string('showreport', 'tupf'),
            new moodle_url('/mod/tupf/report.php', ['id' => $PAGE->cm->id])
        );
    }
}
<?php

/**
 * Interaction with the Moodle core.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Inserts texts inside `tupf_texts` table.
 * Intended to be used within `tupf_add_instance` and `tupf_update_instance` below.
 *
 * @param int $tupfid
 * @param array $textsdata
 * @return void
 */
function insert_tupf_texts($tupfid, $textsdata) {
    global $DB;

    $texts = [];
    foreach ($textsdata as $value) {
        $text = $value['text']; // Editor form field returns an array.
        $text = str_replace('&nbsp;', ' ', $text); // Replaces non-breaking spaces with standard spaces.
        $text = preg_replace('#<a.*?>(.*?)</a>#is', '\1', $text); // Removes links from text.
        if (isset($text) && $text <> '') {
            $texts[] = [
                'tupfid' => $tupfid,
                'text' => $text,
                'timemodified' => time(),
            ];
        }
    }

    $DB->insert_records('tupf_texts', $texts);

    // Triggers the texts translation background task.
    \core\task\manager::queue_adhoc_task(new \mod_tupf\task\translate_texts, true);
}

/**
 * Adds TUPF instance.
 *
 * @param object $data
 * @return int new tupf instance id
 */
function tupf_add_instance($data) {
    global $DB;

    $data->timemodified = time();

    $data->id = $DB->insert_record('tupf', $data);

    insert_tupf_texts($data->id, $data->text);

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

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('tupf', $data);

    // Prevents texts insertion if already present
    if (!$DB->record_exists('tupf_texts', ['tupfid' => $data->id])) {
        insert_tupf_texts($data->id, $data->text);
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

    if (has_capability('mod/tupf:readreport', $PAGE->cm->context)) {
        $node->add(
            get_string('showreport', 'tupf'),
            new moodle_url('/mod/tupf/report.php', ['id' => $PAGE->cm->id])
        );
    }
}
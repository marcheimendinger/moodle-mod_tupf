<?php

/**
 * Adhoc task that translates submitted texts asynchronously using an external API.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

namespace mod_tupf\task;

defined('MOODLE_INTERNAL') || die();

class translate_texts extends \core\task\adhoc_task {

    /**
     * Runs the task to translate texts.
     * The task re-queues by itself if there are more texts to translate.
     */
    public function execute() {
        mtrace('Task `translate_texts` starting...');

        if ($this->translate()) {
            $this->schedule_new_tupf_task();
        }

        mtrace('Task `translate_texts` finished.');
    }

    /**
     * Translates texts and updates the database accordingly.
     *
     * @return bool Whether there may be more texts to process.
     */
    protected function translate(): bool {
        global $DB;

        $limitmax = 2;
        $count = 0;

        // Gets oldest untranslated texts.
        $sql = 'SELECT {tupf_texts}.id, {tupf_texts}.text, {tupf_texts}.translated, {tupf_texts}.translationattempts, {tupf}.language1, {tupf}.language2
            FROM {tupf_texts}
            INNER JOIN {tupf}
            ON {tupf_texts}.tupfid = {tupf}.id
            WHERE {tupf_texts}.translated = false AND {tupf_texts}.translationattempts < 3
            ORDER BY {tupf_texts}.timemodified';
        $textset = $DB->get_recordset_sql($sql, null, 0, $limitmax);

        if (!$textset->valid()) {
            mtrace('No text to translate. Stopping the task...');

            $textset->close();

            return false;
        }

        foreach ($textset as $text) {
            mtrace('Text #'.$text->id.' processing...');

            $textnew = $text;
            $textnew->translationattempts += 1;

            $wordsresult = $this->fetch_translation($text);

            if (empty($wordsresult)) {
                $DB->update_record('tupf_texts', $textnew);

                if ($textnew->translationattempts > 2) {
                    mtrace('Text #'.$text->id.' could not be processed (tried '.$textnew->translationattempts.' times).');
                } else {
                    mtrace('Text #'.$text->id.' could not be processed. Scheduling a new task...');

                    $this->schedule_new_tupf_task(1);
                }

                continue;
            }

            $words = [];
            foreach ($wordsresult as $wordresult) {
                $word = [];
                $word['textid'] = $text->id;
                $word['position'] = $wordresult[4];
                $word['language2raw'] = $wordresult[0];
                $word['language2simplified'] = $wordresult[1];
                $word['language1'] = $wordresult[5];
                $words[] = $word;
            }

            $DB->insert_records('tupf_words', $words);

            $textnew->translated = true;
            $DB->update_record('tupf_texts', $textnew);

            $count++;

            mtrace('Text #'.$text->id.' successfully processed.');
        }

        $textset->close();

        return $limitmax == $count;
    }

    /**
     * Fetches the text translation from an external API using HTTP.
     * This can take some time.
     *
     * @param stdClass $text Text instance from `tupf_texts` joined to `tupf`.
     * @return array Each item is an array representing a translated word from the text with five elements:
     *      0 - Raw word in source language (string)
     *      1 - Simplified word in source language (string)
     *      2 - Word category (string)
     *      3 - Word position in HTML text (integer)
     *      4 - Translated word in target language (string)
     */
    protected function fetch_translation($text) {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');

        $url = 'https://miaparle.unige.ch/tupf/processhtml';

        $jsoninput = json_encode(['text' => $text->text, 'source' => $text->language2, 'target' => $text->language1]);

        $options = ['CURLOPT_HTTPHEADER' => ['Content-Type: application/json', 'Content-Length: '.strlen($jsoninput)]];

        $curl = new \curl;

        $jsonoutput = $curl->post($url, $jsoninput, $options);

        return json_decode($jsonoutput, true);
    }

    /**
     * Schedules a new `translate_texts` task if is not already scheduled.
     *
     * @param int $minutes Offset in minutes before starting the new task. Defaults to 0.
     */
    protected function schedule_new_tupf_task(int $minutes = 0) {
        $taskscount = count(\core\task\manager::get_adhoc_tasks('mod_tupf\task\translate_texts'));

        if ($taskscount < 2) {
            $task = new translate_texts;
            if ($minutes > 0) {
                $task->set_next_run_time(time() + $minutes * MINSECS);
            }
            \core\task\manager::queue_adhoc_task($task);

            mtrace('Scheduled a new `translate_texts` task.');
        }
    }

}
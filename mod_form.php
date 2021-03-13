<?php

/**
 * Module settings.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_tupf_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $DB, $PAGE;

        require_once('resources/languages.php');

        $mform = $this->_form;

        // General section

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // Texts section

        if ($this->_instance) {
            $repeatno = $DB->count_records('tupf_texts', ['tupfid' => $this->_instance]);
            $readonly = $repeatno > 0;
            $repeatno = $readonly ? $repeatno : 5;
        } else {
            $repeatno = 5;
        }

        $mform->addElement('header', 'texts', get_string('texts', 'tupf'));
        $mform->setExpanded('texts');

        if ($readonly) {
            $url = new moodle_url('/mod/tupf/edittexts.php', ['id' => $PAGE->cm->id]);
            $link = html_writer::tag('a', get_string('edittextslink', 'tupf'), ['href' => $url]);
            $mform->addElement('static', 'information', get_string('edittexts', 'tupf'), $link);
        }

        $mform
            ->addElement('select', 'language2', get_string('textslanguage', 'tupf'), $tupf_languages)
            ->setSelected('en');
        $mform->setType('language2', PARAM_TEXT);

        $mform
            ->addElement('select', 'language1', get_string('translatedtextslanguage', 'tupf'), $tupf_languages)
            ->setSelected('fr');
        $mform->setType('language1', PARAM_TEXT);

        $textform = [
            $mform->createElement($readonly ? 'static' : 'editor', 'text', get_string('textno', 'tupf')),
            $mform->createElement('hidden', 'textid', 0),
        ];

        $repeatoptions = [
            'text' => [
                'type' => PARAM_RAW,
                'helpbutton' => ['text', 'tupf'],
            ],
            'textid' => ['type' => PARAM_INT],
        ];

        $this->repeat_elements(
            $textform,
            $repeatno,
            $repeatoptions,
            'text_repeats',
            'text_add_fields',
            1,
            get_string('addtextfield', 'tupf'),
            true
        );

        if ($readonly) {
            $mform->freeze('language1');
            $mform->freeze('language2');
            $mform->freeze('text_add_fields');
        }

        // Other standard sections

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();

    }

    /**
     * Loads existing texts if any.
     *
     * @param array $default_values
     * @return void
     */
    function data_preprocessing(&$default_values) {
        global $DB;

        if (empty($this->_instance)) {
            return;
        }

        $texts = $DB->get_records_menu('tupf_texts', ['tupfid' => $this->_instance], 'id', 'id, text');

        if ($texts) {
            $textsids = array_keys($texts);
            $texts = array_values($texts);

            foreach ($texts as $key => $value) {
                $default_values['textid'][$key] = $textsids[$key];
                $default_values['text'][$key] = $value;
            }
        }
    }

}
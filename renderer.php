<?php

/**
 * Defines the HTML renderer for the TUPF module.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

defined('MOODLE_INTERNAL') || die;

/**
 * The renderer for the TUPF module.
 */
class mod_tupf_renderer extends plugin_renderer_base {

    /**
     * Builds an error alert displayed if JavaScript is disabled.
     *
     * @return string HTML content.
     */
    public function no_javascript_error() {
        $message = html_writer::tag(
            'a',
            get_string('errornojavascript', 'tupf'),
            ['href' => 'https://www.enable-javascript.com', 'target' => '_blank']
        );
        $div = html_writer::div($message, 'alert alert-danger');
        return html_writer::tag('noscript', $div);
    }

    /**
     * Builds the words selection widget.
     *
     * @param string $text
     * @param [string] $words
     * @return string HTML content.
     */
    public function words_selection($text, $words) {
        $this->page->requires->js_call_amd('mod_tupf/wordsselection', 'init');

        $output = '';

        $output .= $this->output->heading(format_string(get_string('wordsselection', 'tupf')), 2);
        $output .= html_writer::tag('p', get_string('wordsselection_help', 'tupf'));

        $textoutput = $text;
        $offset = 0;
        foreach ($words as $word) {
            $linkStart = html_writer::start_tag('a', ['href' => '#', 'data-word-id' => $word->id, 'class' => 'tupf-word']);
            $startPosition = $word->position + $offset;
            $textoutput = substr_replace($textoutput, $linkStart, $startPosition, 0);
            $offset += strlen($linkStart);

            $linkEnd = html_writer::end_tag('a');
            $endPosition = $word->position + strlen($word->language2raw) + $offset;
            $textoutput = substr_replace($textoutput, $linkEnd, $endPosition, 0);
            $offset += strlen($linkEnd);
        }
        $output .= $textoutput;

        $output .= html_writer::start_tag('form', [
            'id' => 'tupf-words-selection-form',
            'action' => $this->page->url,
            'method' => 'post',
        ]);
        $output .= html_writer::tag('input', '', [
            'type' => 'hidden',
            'name' => 'selected-words',
        ]);
        $output .= html_writer::tag('input', null, [
            'type' => 'button',
            'id' => 'tupf-submit-button',
            'class' => 'btn btn-primary',
            'value' => get_string('submit'),
        ]);
        $output .= html_writer::end_tag('form');

        return $output;
    }

}
<?php

/**
 * HTML renderer for the module.
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
     * Overrides `header()` to add an error if JavaScript is disabled.
     *
     * @return string HTML content.
     */
    public function header() {
        $output = '';

        $output .= parent::header();
        $output .= $this->no_javascript_error();

        return $output;
    }

    /**
     * Builds a centered button to go back to instance module home page.
     *
     * @param integer $coursemoduleid Course module ID.
     * @return string HTML content.
     */
    public function back_home_button(int $coursemoduleid) {
        $url = new moodle_url('/mod/tupf/view.php', ['id' => $coursemoduleid]);
        $button = html_writer::tag('a', get_string('backhome', 'tupf'), ['href' => $url, 'class' => 'btn btn-secondary']);
        return html_writer::div($button, 'text-center my-4');
    }

    /**
     * Builds the home buttons widget.
     *
     * @param integer $coursemoduleid Course module ID.
     * @param string $tupfname Module instance name.
     * @return string HTML content.
     */
    public function home_buttons(int $coursemoduleid, string $tupfname) {
        $output = '';

        $output .= $this->output->heading($tupfname, 2);

        $buttons = '';

        $reviewurl = new moodle_url('/mod/tupf/review.php', ['id' => $coursemoduleid]);
        $buttons .= html_writer::tag('a', get_string('startreview', 'tupf'), ['href' => $reviewurl, 'class' => 'btn btn-secondary mx-2']);

        $wordsurl = new moodle_url('/mod/tupf/words.php', ['id' => $coursemoduleid]);
        $buttons .= html_writer::tag('a', get_string('displaywordslist', 'tupf'), ['href' => $wordsurl, 'class' => 'btn btn-secondary mx-2']);

        $output .= html_writer::div($buttons, 'text-center my-4');

        return $output;
    }

    /**
     * Builds the words selection widget.
     *
     * @param string $text HTML text.
     * @param [string] $words List of translated words.
     * @return string HTML content.
     */
    public function words_selection(string $text, array $words) {
        $this->page->requires->js_call_amd('mod_tupf/wordsselection', 'init');

        $output = '';

        $output .= $this->output->heading(get_string('wordsselection', 'tupf'), 2);
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

    /**
     * Builds the words list widget.
     *
     * @param array $words Selected words for the current user.
     * @return string HTML content.
     */
    public function words_list(array $words) {
        $output = '';

        $output .= $this->output->heading(get_string('selectedwords', 'tupf'), 2);

        $table = new html_table();
        $table->attributes['class'] = 'generaltable mod_index';

        $table->head  = [
            get_string('language1', 'tupf'),
            get_string('language2', 'tupf'),
            get_string('correctcount', 'tupf'),
            get_string('wrongcount', 'tupf'),
        ];

        foreach ($words as $word) {
            $table->data[] = [
                format_string($word->language1),
                format_string($word->language2simplified),
                $word->correctcount,
                $word->showncount - $word->correctcount,
            ];
        }

        $output .= html_writer::table($table);

        return $output;
    }

    /**
     * Builds the words review heading widget.
     *
     * @return string HTML content.
     */
    public function words_review_heading() {
        return $this->output->heading(get_string('wordsreview', 'tupf'), 2);
    }

    /**
     * Builds words review flashcard widget.
     *
     * @param integer $coursemoduleid Course module ID.
     * @param $word Word object from the `tupf_words` table.
     * @param integer $wordindex Position of the currently displayed word.
     * @param integer $totalwordscount Count of all words to review.
     * @return string HTML content.
     */
    public function words_review_flashcard(int $coursemoduleid, $word, int $wordindex, int $totalwordscount) {
        $this->page->requires->js_call_amd('mod_tupf/flashcard', 'init');

        $output = '';

        $output .= html_writer::tag('p', get_string('wordsreview_help', 'tupf'));

        $centercontent = $this->flashcard($word);

        $previousdisabled = $wordindex == 1;
        $centercontent .= $this->button_post(get_string('previousword', 'tupf'), 'buttonaction', 'previous', 'btn btn-link mx-2', $previousdisabled);

        $centercontent .= $this->button_post(get_string('nextwordcorrect', 'tupf'), 'buttonaction', 'nextcorrect', 'btn btn-link mx-2');

        $centercontent .= $this->button_post(get_string('nextwordwrong', 'tupf'), 'buttonaction', 'nextwrong', 'btn btn-link mx-2');

        $centercontent .= html_writer::tag('p', $wordindex.' / '.$totalwordscount, ['class' => 'small my-2']);

        $output .= html_writer::div($centercontent, 'text-center');

        return $output;
    }

    /**
     * Builds words review end buttons widget.
     *
     * @param integer $coursemoduleid Course module ID.
     * @return string HTML content.
     */
    public function words_review_end_buttons(int $coursemoduleid) {
        $content = '';

        $content .= html_writer::tag('p', get_string('reviewend', 'tupf'));

        $viewurl = new moodle_url('/mod/tupf/view.php', ['id' => $coursemoduleid]);
        $reviewurl = new moodle_url('/mod/tupf/review.php', ['id' => $coursemoduleid]);

        $content .= html_writer::tag('a', get_string('backhome', 'tupf'), ['href' => $viewurl, 'class' => 'btn btn-secondary mx-2']);
        $content .= html_writer::tag('a', get_string('restartreview', 'tupf'), ['href' => $reviewurl, 'class' => 'btn btn-secondary mx-2']);

        return html_writer::div($content, 'text-center');
    }

    /**
     * Builds an error alert displayed if JavaScript is disabled.
     *
     * @return string HTML content.
     */
    private function no_javascript_error() {
        $message = html_writer::tag(
            'a',
            get_string('errornojavascript', 'tupf'),
            ['href' => 'https://www.enable-javascript.com', 'target' => '_blank']
        );
        $div = html_writer::div($message, 'alert alert-danger');
        return html_writer::tag('noscript', $div);
    }

    /**
     * Builds word flashcard widget.
     *
     * @param $word Word object from the `tupf_words` table.
     * @return string HTML content.
     */
    private function flashcard($word) {
        $front = html_writer::tag('h4', $word->language1, ['class' => 'align-self-center mb-0']);
        $front = html_writer::div($front, 'tupf-flashcard-front d-flex justify-content-center');

        $back = html_writer::tag('h4', $word->language2simplified, ['class' => 'align-self-center mb-0']);
        $back = html_writer::div($back, 'tupf-flashcard-back d-flex justify-content-center');

        $flashcard = html_writer::div($front.$back, 'tupf-flashcard-inner');

        return html_writer::div($flashcard, 'tupf-flashcard-container mx-auto mb-4');
    }

    /**
     * Builds POST button form.
     *
     * @param string $content Button content. Usually a localized string.
     * @param string $name Button name for POST data.
     * @param string $value Button value for POST data.
     * @param string $class CSS classes. Defaults to a simple link style.
     * @param bool $disabled Disabled state. Defaults to enabled.
     * @return string HTML content.
     */
    private function button_post(string $content, string $name, string $value, string $class = 'btn btn-link', bool $disabled = false) {
        $options = [
            'type' => 'submit',
            'class' => $class,
            'name' => $name,
            'value' => $value,
        ];

        if ($disabled) {
            $options['disabled'] = true;
        }

        $button = html_writer::tag('button', $content, $options);

        return html_writer::tag('form', $button, [
            'action' => $this->page->url,
            'method' => 'post',
            'class' => 'inline'
        ]);
    }

}
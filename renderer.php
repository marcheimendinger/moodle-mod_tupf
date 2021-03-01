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
     * @param [int] $selectedwordsids Optional list of initially selected words IDs.
     * @return string HTML content.
     */
    public function words_selection(string $text, array $words, array $selectedwordsids = []) {
        $this->page->requires->js_call_amd('mod_tupf/wordsselection', 'init');

        $output = '';

        $output .= $this->output->heading(get_string('wordsselection', 'tupf'), 2);
        $output .= html_writer::tag('p', get_string('wordsselection_help', 'tupf'));

        $textoutput = $text;
        $offset = 0;
        foreach ($words as $word) {
            $selectedclass = in_array($word->id, $selectedwordsids) ? ' mark' : '';
            $linkstart = html_writer::start_tag('a', ['href' => '#', 'data-word-id' => $word->id, 'class' => 'tupf-word'.$selectedclass]);
            $startposition = $word->position + $offset;
            $textoutput = substr_replace($textoutput, $linkstart, $startposition, 0);
            $offset += strlen($linkstart);

            $linkend = html_writer::end_tag('a');
            $endposition = $word->position + strlen($word->language2raw) + $offset;
            $textoutput = substr_replace($textoutput, $linkend, $endposition, 0);
            $offset += strlen($linkend);
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
     * @param integer $coursemoduleid Course module ID.
     * @return string HTML content.
     */
    public function words_list(array $words, int $coursemoduleid) {
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
                isset($word->correctcount) ? $word->correctcount : 0,
                $word->showncount - $word->correctcount,
            ];
        }

        $output .= html_writer::table($table);

        $buttons = '';

        $homeurl = new moodle_url('/mod/tupf/view.php', ['id' => $coursemoduleid]);
        $buttons .= html_writer::tag('a', get_string('backhome', 'tupf'), ['href' => $homeurl, 'class' => 'btn btn-secondary mx-2']);

        $editselectionurl = new moodle_url('/mod/tupf/editselection.php', ['id' => $coursemoduleid]);
        $buttons .= html_writer::tag('a', get_string('editselection', 'tupf'), ['href' => $editselectionurl, 'class' => 'btn btn-secondary mx-2']);

        $output .= html_writer::div($buttons, 'text-center my-4');

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

        $columns = '';

        $previousdisabled = $wordindex == 1;
        $columns .= html_writer::div(
            $this->button_post($this->icon('chevron-left'), 'buttonaction', 'previous', 'btn btn-link text-secondary', $previousdisabled),
            'col-md-1 col-sm-2 order-last order-sm-first text-center'
        );

        $columns .= html_writer::div($this->flashcard($word), 'col-sm-auto');

        $buttons = '';
        $buttons .= $this->button_post(
            $this->icon('check'),
            'buttonaction',
            'nextcorrect',
            'btn btn-success btn-lg rounded-pill p-2 m-2'
        );
        $buttons .= $this->button_post(
            $this->icon('x'), 'buttonaction', 'nextwrong', 'btn btn-danger btn-lg rounded-pill p-2 m-2'
        );
        $columns .= html_writer::div($buttons, 'col-md-1 col-sm-2 mt-3 mt-sm-0 text-center');

        $output .= html_writer::div($columns, 'row justify-content-sm-center align-items-center mt-0 mt-sm-4');

        $output .= html_writer::tag('p', $wordindex.' / '.$totalwordscount, ['class' => 'small text-center mt-4 mb-2']);

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
     * Returns an SVG icon from icons.getbootstrap.com.
     *
     * @param string $name Icon name.
     * @param int $size Icon size. Defaults to 32.
     * @return string HTML content.
     */
    private function icon(string $name, int $size = 32) {
        $svg = [
            'check' => '<path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>',
            'chevron-left' => '<path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>',
            'x' => '<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>',
        ];

        if (!array_key_exists($name, $svg)) {
            return '';
        }

        return html_writer::tag(
            'svg',
            $svg[$name],
            ['xmlns' => 'http://www.w3.org/2000/svg', 'width' => $size, 'height' => $size, 'fill' => 'currentColor', 'viewBox' => '0 0 16 16']
        );
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

        return html_writer::div($flashcard, 'tupf-flashcard-container mx-auto');
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
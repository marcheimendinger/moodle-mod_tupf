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
     * @param bool $reviewingwords Whether the user is currently reviewing words.
     * @return string HTML content.
     */
    public function home_buttons(int $coursemoduleid, string $tupfname, bool $reviewingwords) {
        $output = '';

        $output .= $this->output->heading($tupfname, 2);

        $output .= $this->buttons(
            [
                'review.php' => $reviewingwords ? get_string('resumereview', 'tupf') : get_string('startreview', 'tupf'),
                'words.php' => get_string('displaywordslist', 'tupf')
            ],
            $coursemoduleid
        );

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

        $output .= $this->output->heading(get_string('editselection', 'tupf'), 2);
        $output .= html_writer::tag('p', get_string('editselection_help', 'tupf'));

        $textoutput = $text;
        $offset = 0;
        foreach ($words as $word) {
            $selectedclass = in_array($word->id, $selectedwordsids) ? ' mark' : '';
            $linkstart = html_writer::start_tag('span', ['data-word-id' => $word->id, 'class' => 'tupf-word selectable'.$selectedclass]);
            $startposition = $word->position + $offset;
            $textoutput = substr_replace($textoutput, $linkstart, $startposition, 0);
            $offset += strlen($linkstart);

            $linkend = html_writer::end_tag('span');
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
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        $output .= html_writer::tag('input', '', [
            'type' => 'hidden',
            'name' => 'selected-words',
        ]);
        $output .= html_writer::tag('input', null, [
            'type' => 'button',
            'id' => 'tupf-submit-button',
            'class' => 'btn btn-primary my-2',
            'value' => get_string('submit'),
        ]);
        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * Builds the words list widget.
     *
     * @param array $words Selected words for the current user.
     * @param object $tupf Module instance from `tupf` table.
     * @param integer $coursemoduleid Course module ID.
     * @return string HTML content.
     */
    public function words_list(array $words, object $tupf, int $coursemoduleid) {
        require_once('resources/languages.php');

        $output = '';

        $output .= $this->output->heading(get_string('selectedwords', 'tupf'), 2);

        $table = new html_table();
        $table->attributes['class'] = 'generaltable mod_index';

        $table->head  = [
            $tupf_languages[$tupf->language1],
            $tupf_languages[$tupf->language2],
            get_string('correctpercentage', 'tupf'),
        ];

        foreach ($words as $word) {
            $progress = '';
            if ($word->showncount > 0) {
                $correctpercentage = round(($word->correctcount * 100) / $word->showncount);

                $progresscontent = html_writer::div(
                    $correctpercentage > 10 ? $correctpercentage.'%' : '',
                    'progress-bar rounded-pill',
                    ['role' => 'progressbar', 'style' => 'width: '.$correctpercentage.'%;', 'aria-valuenow' => $correctpercentage, 'aria-valuemin' => 0, 'aria-valuemax' => 100]
                );
                $progress = html_writer::div($progresscontent, 'progress rounded-pill');
            }

            $table->data[] = [
                format_string($word->language1),
                format_string($word->language2simplified),
                $progress,
            ];
        }

        $output .= html_writer::table($table);

        $output .= $this->buttons(
            ['editselection.php' => get_string('editselectionbutton', 'tupf')],
            $coursemoduleid
        );

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
            $this->button_post($this->icon('chevron-left'), 'buttonaction', 'previous', 'btn btn-light btn-lg rounded-pill p-2 m-2', $previousdisabled),
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
        $output = '';

        $output .= html_writer::tag('p', get_string('reviewend', 'tupf'), ['class' => 'text-center']);

        $output .= $this->buttons(
            ['view.php' => get_string('backhome', 'tupf'), 'review.php' => get_string('restartreview', 'tupf')],
            $coursemoduleid
        );

        return $output;
    }

    /**
     * Builds link to report widget.
     *
     * @param integer $coursemoduleid Course module ID.
     * @return string HTML content.
     */
    public function report_link(int $coursemoduleid) {
        $url = new moodle_url('/mod/tupf/report.php', ['id' => $coursemoduleid]);
        $link = html_writer::tag('a', get_string('showreport', 'tupf'), ['href' => $url]);
        return html_writer::div($link, 'float-none float-sm-right mb-2 mb-sm-0');
    }

    /**
     * Builds report page heading, including the legend.
     *
     * @return string HTML content.
     */
    public function report_heading() {
        $output = '';

        $output .= $this->output->heading(get_string('report', 'tupf'), 2);

        $output .= html_writer::start_tag('p');
        $output .= get_string('report_help', 'tupf').' ';
        $output .= html_writer::tag('span', get_string('reportlow', 'tupf').' (1-35%)', ['class' => 'tupf-word mark low mx-1']);
        $output .= html_writer::tag('span', get_string('reportmedium', 'tupf').' (35-70%)', ['class' => 'tupf-word mark medium mx-1']);
        $output .= html_writer::tag('span', get_string('reporthigh', 'tupf').' (70-100%)', ['class' => 'tupf-word mark high mx-1']);
        $output .= html_writer::end_tag('p');

        return $output;
    }

    /**
     * Builds text report widget.
     *
     * @param string $text Text in HTML.
     * @param integer $textindex Text number (starts at 1).
     * @param integer $userscount Total number of users using this text.
     * @param array $words Text words objects containing `language2raw`, `position`, and `userscount` (number of users having selected this word).
     * @return string HTML content.
     */
    public function report_text(string $text, int $textindex, int $userscount, array $words) {
        $textoutput = $text;
        $offset = 0;
        foreach ($words as $word) {
            $percentage = ($word->userscount * 100) / $userscount;

            if ($percentage < 35) {
                $usageclass = 'low';
            } else if ($percentage > 70) {
                $usageclass = 'high';
            } else {
                $usageclass = 'medium';
            }

            $spanstart = html_writer::start_tag('span', ['class' => 'tupf-word mark '.$usageclass]);
            $startposition = $word->position + $offset;
            $textoutput = substr_replace($textoutput, $spanstart, $startposition, 0);
            $offset += strlen($spanstart);

            $spanend = html_writer::end_tag('span');
            $endposition = $word->position + strlen($word->language2raw) + $offset;
            $textoutput = substr_replace($textoutput, $spanend, $endposition, 0);
            $offset += strlen($spanend);
        }

        $header = html_writer::tag('h2', get_string('reporttextnumber', 'tupf', $textindex), ['class' => 'card-header']);

        $footer = html_writer::div(get_string('reporttextusage', 'tupf', $userscount), 'card-footer text-muted');

        $content = '';
        $content .= $header;
        $content .= html_writer::div($textoutput, 'card-body');
        $content .= $footer;

        return html_writer::div($content, 'card my-4');
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

        $formcontent = '';

        $formcontent .= html_writer::tag('input', '', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        $formcontent .= html_writer::tag('button', $content, $options);

        return html_writer::tag('form', $formcontent, [
            'action' => $this->page->url,
            'method' => 'post',
            'class' => 'inline',
        ]);
    }

    /**
     * Builds buttons list on a row.
     *
     * @param array $buttonsdata Key as URL (relatively to the module directory `/mod/tupf/`) and value to button content.
     * @param integer $coursemoduleid Course module ID.
     * @param string $buttonclass Buttons class. Defaults to a secondary button styling.
     * @return string HTML content.
     */
    private function buttons(array $buttonsdata, int $coursemoduleid, string $buttonclass = 'btn btn-secondary m-2') {
        $buttons = '';

        foreach ($buttonsdata as $file => $content) {
            $url = new moodle_url('/mod/tupf/'.$file, ['id' => $coursemoduleid]);
            $buttons .= html_writer::tag('a', $content, ['href' => $url, 'class' => $buttonclass]);
        }

        return html_writer::div($buttons, 'text-center my-3 my-sm-4');
    }

}
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
     * Error alert.
     *
     * @param string $content Alert content.
     * @param string $class Alert Bootstrap CSS class. Defaults to danger style.
     * @return string HTML content.
     */
    public function error(string $content, string $class = 'alert-danger') {
        return html_writer::div($content, 'alert '.$class);
    }

    /**
     * Homepage top links to access admin features.
     * Displayed only to allowed users.
     *
     * @return string HTML content.
     */
    public function home_admin_top_links() {
        global $PAGE;

        $content = '';

        if (has_capability('mod/tupf:readreport', $PAGE->cm->context)) {
            $content .= $this->admin_button(
                get_string('showreport', 'tupf'),
                new moodle_url('/mod/tupf/report.php', ['id' => $PAGE->cm->id]),
                'chart'
            );
        }

        if (has_capability('mod/tupf:addinstance', $PAGE->cm->context)) {
            $content .= $this->admin_button(
                get_string('edittextsbutton', 'tupf'),
                new moodle_url('/mod/tupf/edittexts.php', ['id' => $PAGE->cm->id]),
                'pencil'
            );
        }

        return empty($content) ? '' : html_writer::div($content, 'btn-group float-none float-sm-right mb-2 mb-sm-0');
    }

    /**
     * Homepage admin texts editing button.
     * Used while texts are being processed.
     *
     * @return string HTML content.
     */
    public function home_edit_texts_button() {
        global $PAGE;

        $icon = $this->icon('pencil', 14, 'mr-2 mb-1');

        return $this->buttons(
            ['edittexts.php' => $icon.get_string('edittextsbutton', 'tupf')],
            $PAGE->cm->id
        );
    }

    /**
     * Homepage buttons for standard user.
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
     * Words selector.
     *
     * @param string $text HTML text.
     * @param [string] $words List of translated words.
     * @param [int] $selectedwordsids Optional list of initially selected words IDs.
     * @return string HTML content.
     */
    public function words_selection(string $text, array $words, array $selectedwordsids = []) {
        require_once('locallib.php');

        $this->page->requires->js_call_amd('mod_tupf/wordsselection', 'init');

        $output = '';

        $editing = !empty($selectedwordsids);
        $output .= $this->output->heading($editing ? get_string('editselection', 'tupf') : get_string('startselection', 'tupf'), 2);
        $output .= html_writer::tag('p', get_string('editselection_help', 'tupf'));

        $textoutput = html_entity_decode($text);
        $offset = 0;
        foreach ($words as $word) {
            $selectedclass = in_array($word->id, $selectedwordsids) ? ' mark' : '';
            $linkstart = html_writer::start_tag('span', ['data-word-id' => $word->id, 'class' => 'tupf-word selectable'.$selectedclass]);
            $startposition = $word->position + $offset;
            $textoutput = mb_substr_replace($textoutput, $linkstart, $startposition, 0);
            $offset += mb_strlen($linkstart);

            $linkend = html_writer::end_tag('span');
            $endposition = $word->position + mb_strlen($word->language2raw) + $offset;
            $textoutput = mb_substr_replace($textoutput, $linkend, $endposition, 0);
            $offset += mb_strlen($linkend);
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
     * Words table list.
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
            $coursemoduleid,
            'btn btn-secondary m-2'
        );

        return $output;
    }

    /**
     * Words review heading.
     *
     * @return string HTML content.
     */
    public function words_review_heading() {
        return $this->output->heading(get_string('wordsreview', 'tupf'), 2);
    }

    /**
     * Word flashcard for words review.
     *
     * @param integer $coursemoduleid Course module ID.
     * @param $word Word object from the `tupf_words` table.
     * @param integer $wordindex Position of the currently displayed word.
     * @param integer $totalwordscount Count of all words to review.
     * @param bool $backward Whether the user went backward. Defaults to `false`.
     * @return string HTML content.
     */
    public function words_review_flashcard(int $coursemoduleid, $word, int $wordindex, int $totalwordscount, bool $backward = false) {
        $this->page->requires->js_call_amd('mod_tupf/flashcard', 'init');

        $output = '';

        $output .= html_writer::tag('p', get_string('wordsreview_help', 'tupf'));

        $columns = '';

        $previousdisabled = $wordindex == 1;
        $columns .= html_writer::div(
            $this->button_post($this->icon('chevron-left'), 'buttonaction', 'previous', 'btn btn-light btn-lg rounded-pill p-2 m-2', $previousdisabled),
            'col-md-1 col-sm-2 order-last order-sm-first text-center'
        );

        $columns .= html_writer::div($this->flashcard($word, $backward), 'col-sm-auto');

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
     * Words review ending buttons.
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
     * Report heading, including the legend.
     *
     * @return string HTML content.
     */
    public function report_heading() {
        global $PAGE;

        $output = '';

        if (has_capability('mod/tupf:addinstance', $PAGE->cm->context)) {
            $output .= $this->admin_button(
                get_string('edittextsbutton', 'tupf'),
                new moodle_url('/mod/tupf/edittexts.php', ['id' => $PAGE->cm->id]),
                'pencil',
                'mb-2 mb-sm-0 float-sm-right'
            );
        }

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
     * Single text card with stats.
     *
     * @param string $text Text in HTML.
     * @param integer $textindex Text number (starts at 1).
     * @param integer $userscount Total number of users using this text.
     * @param array $words Text words objects containing `language2raw`, `position`, and `userscount` (number of users having selected this word).
     * @return string HTML content.
     */
    public function report_text(string $text, int $textindex, int $userscount, array $words) {
        require_once('locallib.php');

        $textoutput = html_entity_decode($text);
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
            $textoutput = mb_substr_replace($textoutput, $spanstart, $startposition, 0);
            $offset += mb_strlen($spanstart);

            $spanend = html_writer::end_tag('span');
            $endposition = $word->position + mb_strlen($word->language2raw) + $offset;
            $textoutput = mb_substr_replace($textoutput, $spanend, $endposition, 0);
            $offset += mb_strlen($spanend);
        }

        $header = html_writer::tag('h3', get_string('reporttextnumber', 'tupf', $textindex), ['class' => 'card-header']);

        $footer = html_writer::div(get_string('reporttextusage', 'tupf', $userscount), 'card-footer text-muted');

        $content = '';
        $content .= $header;
        $content .= html_writer::div($textoutput, 'card-body');
        $content .= $footer;

        return html_writer::div($content, 'card my-4');
    }

    /**
     * Texts edition heading, including a reload link when a text is currently being translated.
     *
     * @param bool $translating Whether a text is currently being translated.
     * @return string HTML content.
     */
    public function edittexts_heading(bool $translating) {
        global $PAGE;

        $output = '';

        if (has_capability('mod/tupf:readreport', $PAGE->cm->context)) {
            $output .= $this->admin_button(
                get_string('showreport', 'tupf'),
                new moodle_url('/mod/tupf/report.php', ['id' => $PAGE->cm->id]),
                'chart',
                'mb-2 mb-sm-0 float-sm-right',
            );
        }

        $output .= $this->output->heading(get_string('edittexts', 'tupf'), 2);
        $output .= html_writer::tag('p', get_string('edittexts_help', 'tupf'));

        if ($translating) {
            $output .= html_writer::start_tag('p');
            $output .= $this->spinner('mr-1');
            $output .= get_string('refreshtranslationstatus', 'tupf');
            $output .= html_writer::tag('a', get_string('refreshtranslationstatuslink', 'tupf'), ['href' => $this->page->url, 'class' => 'ml-1']);
            $output .= html_writer::end_tag('p');
        }

        return $output;
    }

    /**
     * Single text card with stats and delete button.
     *
     * @param $text Text instance from `tupf_texts` table.
     * @param integer $textindex Text number (starts at 1).
     * @param integer $userscount Total number of users using this text.
     * @return string HTML content.
     */
    public function edittexts_text($text, int $textindex, int $userscount) {
        require_once('locallib.php');

        $beingtranslated = !$text->translated && $text->translationattempts < TUPF_MAX_TRANSLATION_ATTEMPTS;
        $failedtranslation = !$text->translated && $text->translationattempts >= TUPF_MAX_TRANSLATION_ATTEMPTS;

        $headercontent = '';
        $headercontent .= html_writer::start_tag('div', ['class' => 'd-flex flex-row']);
        $headercontent .= html_writer::tag('h3', get_string('reporttextnumber', 'tupf', $textindex), ['class' => 'flex-grow-1 m-0']);
        if ($beingtranslated) {
            $spinner = $this->spinner('mr-1');
            $headercontent .= html_writer::tag('p', $spinner.get_string('translationloading', 'tupf'), ['class' => 'text-info my-auto text-right']);
        } else {
            $headercontent .= $this->button_post(
                get_string('edittextsdelete', 'tupf'),
                'deletetextid',
                $text->id,
                'btn btn-outline-danger',
                false,
                get_string('deletetextconfirmation', 'tupf', $textindex)
            );
        }
        $headercontent .= html_writer::end_tag('div');
        if ($failedtranslation) {
            $cross = $this->icon('exclamation-circle', 18, 'mr-1 mb-1');
            $headercontent .= html_writer::tag('p', $cross.get_string('translationfailed', 'tupf'), ['class' => 'text-danger m-0 mt-2']);
        }
        $header = html_writer::tag('div', $headercontent, ['class' => 'card-header']);

        $footer = html_writer::div(get_string('reporttextusage', 'tupf', $userscount), 'card-footer text-muted');

        $content = '';
        $content .= $header;
        $content .= html_writer::div($text->text, 'card-body');
        $content .= $footer;

        return html_writer::div($content, 'card my-4');
    }

    /**
     * Text insertion form.
     *
     * @return string HTML content.
     */
    public function edittexts_form() {
        global $PAGE;

        $output = '';

        $output .= $this->output->heading(get_string('addtext', 'tupf'), 3, 'mb-2');

        $output .= html_writer::start_tag('form', [
            'id' => 'tupf-add-text-form',
            'action' => $this->page->url,
            'method' => 'post',
        ]);
        $output .= html_writer::tag('input', null, [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        $output .= html_writer::tag('textarea', null, [
            'name' => 'newtext',
            'id' => 'tupf-add-text-textarea',
            'rows' => 10,
            'cols' => 80,
            'class' => 'form-control',
        ]);
        $output .= html_writer::tag('button', get_string('add'), [
            'type' => 'submit',
            'class' => 'btn btn-primary my-2',
        ]);
        $output .= html_writer::end_tag('form');

        // Disables links insertion in editor.
        $attobuttons = 'collapse = collapse
            style1 = title, bold, italic
            list = unorderedlist, orderedlist, indent
            files = emojipicker, image, media, recordrtc, managefiles
            style2 = underline, strike, subscript, superscript
            align = align
            insert = equation, charmap, table, clear
            undo = undo
            accessibility = accessibilitychecker, accessibilityhelper
            other = html';
        $editor = editors_get_preferred_editor(FORMAT_HTML);
        $editor->use_editor(
            'tupf-add-text-textarea',
            ['context' => $PAGE->cm->context, 'autosave' => false, 'atto:toolbar' => $attobuttons]
        );

        return $output;
    }

    /**
     * Returns an SVG icon from https://icons.getbootstrap.com.
     *
     * @param string $name Icon name.
     * @param int $size Icon size. Defaults to 32.
     * @param string $class CSS class. Defaults to empty.
     * @return string HTML content.
     */
    private function icon(string $name, int $size = 32, string $class = '') {
        $svg = [
            'check' => '<path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>',
            'chevron-left' => '<path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>',
            'exclamation-circle' => '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>',
            'chart' => '<path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>',
            'pencil' => '<path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>',
            'x' => '<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>',
        ];

        if (!array_key_exists($name, $svg)) {
            return '';
        }

        return html_writer::tag(
            'svg',
            $svg[$name],
            ['xmlns' => 'http://www.w3.org/2000/svg', 'width' => $size, 'height' => $size, 'fill' => 'currentColor', 'viewBox' => '0 0 16 16', 'class' => $class]
        );
    }

    /**
     * Error alert displayed if JavaScript is disabled.
     *
     * @return string HTML content.
     */
    private function no_javascript_error() {
        $message = html_writer::tag(
            'a',
            get_string('errornojavascript', 'tupf'),
            ['href' => 'https://www.enable-javascript.com', 'target' => '_blank']
        );
        $div = $this->error($message);
        return html_writer::tag('noscript', $div);
    }

    /**
     * Single word flashcard.
     *
     * @param $word Word object from the `tupf_words` table.
     * @param $backward Whether use a backward animation. Defaults to `false`.
     * @return string HTML content.
     */
    private function flashcard($word, bool $backward = false) {
        $front = html_writer::tag('h4', $word->language1, ['class' => 'align-self-center mb-0']);
        $front = html_writer::div($front, 'tupf-flashcard-front d-flex justify-content-center');

        $back = html_writer::tag('h4', $word->language2simplified, ['class' => 'align-self-center mb-0']);
        $back = html_writer::div($back, 'tupf-flashcard-back d-flex justify-content-center');

        $flashcard = html_writer::div($front.$back, 'tupf-flashcard-inner');

        $animation = $backward ? 'tupf-animate-from-left' : 'tupf-animate-from-right';

        return html_writer::div($flashcard, 'tupf-flashcard-container mx-auto '.$animation);
    }

    /**
     * POST button form.
     *
     * @param string $content Button content. Usually a localized string.
     * @param string $name Button name for POST data.
     * @param string $value Button value for POST data.
     * @param string $class CSS classes. Defaults to a simple link style.
     * @param bool $disabled Disabled state. Defaults to enabled.
     * @param string $confirm Content of the optional confirmation popup on submit.
     * @return string HTML content.
     */
    private function button_post(string $content, string $name, string $value, string $class = 'btn btn-link', bool $disabled = false, string $confirm = null) {
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
            'onsubmit' => isset($confirm) ? 'return confirm("'.$confirm.'");' : '',
        ]);
    }

    /**
     * Buttons list on a row.
     *
     * @param array $buttonsdata Key as URL (relatively to the module directory `/mod/tupf/`) and value to button content.
     * @param integer $coursemoduleid Course module ID.
     * @param string $buttonclass Buttons class. Defaults to a secondary button styling.
     * @return string HTML content.
     */
    private function buttons(array $buttonsdata, int $coursemoduleid, string $buttonclass = 'btn btn-outline-primary m-2') {
        $buttons = '';

        foreach ($buttonsdata as $file => $content) {
            $url = new moodle_url('/mod/tupf/'.$file, ['id' => $coursemoduleid]);
            $buttons .= html_writer::tag('a', $content, ['href' => $url, 'class' => $buttonclass]);
        }

        return html_writer::div($buttons, 'text-center my-3 my-sm-4');
    }

    /**
     * Admin button.
     *
     * @param string $name Button name.
     * @param string $url Link URL.
     * @param string $icon Optional icon name.
     * @return void
     */
    private function admin_button(string $name, string $url, string $icon = null, string $class = '') {
        $output = '';

        $output .= html_writer::start_tag('a', ['href' => $url, 'class' => 'btn btn-outline-primary btn-sm '.$class]);
        if (!empty($icon)) {
            $output .= $this->icon($icon, 12, 'ml-1 mr-2 mb-1');
        }
        $output .= $name;
        $output .= html_writer::end_tag('a');

        return $output;
    }

    /**
     * Loading indicator.
     *
     * @param string $class Optional CSS class.
     * @return string HTML content.
     */
    private function spinner($class = '') {
        return html_writer::tag('span', '', ['class' => 'spinner-border spinner-border-sm '.$class, 'role' => 'status', 'aria-hidden' => 'true']);
    }

}
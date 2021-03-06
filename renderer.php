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
     * Top links to access admin features.
     * Adapted to current page.
     * Displayed only to allowed users.
     *
     * @return string HTML content.
     */
    public function admin_top_links() {
        global $PAGE;

        $buttons = '';

        $homeurl = new moodle_url('/mod/tupf/view.php', ['id' => $PAGE->cm->id]);
        $reporturl = new moodle_url('/mod/tupf/report.php', ['id' => $PAGE->cm->id]);
        $edittextsurl = new moodle_url('/mod/tupf/edittexts.php', ['id' => $PAGE->cm->id]);

        if (has_capability('mod/tupf:readreport', $PAGE->cm->context)) {
            $buttons .= $this->small_action_button(
                get_string('showreport', 'tupf'),
                $reporturl,
                'chart',
                $PAGE->url == $reporturl ? 'active' : ''
            );
        }

        if (has_capability('mod/tupf:addinstance', $PAGE->cm->context)) {
            $buttons .= $this->small_action_button(
                get_string('edittextsbutton', 'tupf'),
                $edittextsurl,
                'pencil',
                $PAGE->url == $edittextsurl ? 'active' : ''
            );
        }

        if (!empty($buttons)) {
            $homeButton = $this->small_action_button(
                get_string('home', 'tupf'),
                $homeurl,
                'house-fill',
                $PAGE->url == $homeurl ? 'active' : ''
            );
            $buttons = $homeButton.$buttons;
        }

        return empty($buttons) ? '' : html_writer::div($buttons, 'btn-group mb-2 mb-sm-0 float-sm-right');
    }

    /**
     * Homepage admin texts editing button.
     * Used while texts are being processed.
     *
     * @return string HTML content.
     */
    public function home_edit_texts_button() {
        global $PAGE;

        return $this->buttons([[
            'file' => 'edittexts.php',
            'text' => get_string('edittextsbutton', 'tupf'),
            'icon' => 'pencil',
        ]]);
    }

    /**
     * Homepage buttons for standard user.
     *
     * @param string $title Heading title.
     * @param bool $reviewingwords Whether the user is currently reviewing words.
     * @return string HTML content.
     */
    public function home_buttons(string $title, bool $reviewingwords) {
        $output = '';

        $output .= $this->output->heading($title, 2);

        $output .= $this->buttons([
            [
                'file' => 'review.php#tupf-heading',
                'text' => $reviewingwords ? get_string('resumereview', 'tupf') : get_string('startreview', 'tupf'),
                'icon' => 'card-text',
            ],
            [
                'file' => 'words.php',
                'text' => get_string('displaywordslist', 'tupf'),
                'icon' => 'list',
            ],
        ]);

        return $output;
    }

    /**
     * A dropdown to select a text.
     *
     * @param integer $tupfid TUPF instance ID from `tupf` table.
     * @param array $textsids A list of texts IDs (integers) from `tupf_texts` table.
     * @return string HTML content.
     */
    public function text_selection(int $tupfid, array $textsids) {
        require_once('locallib.php');

        $selectedtextid = tupf_get_selected_text($tupfid);

        $output = '';

        $output .= html_writer::start_tag('form', [
            'id' => 'tupf-text-selection-form',
            'action' => $this->page->url,
            'method' => 'post',
            'class' => 'mb-2'
        ]);

        $output .= html_writer::tag('input', '', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        $output .= html_writer::start_tag('select', [
            'name' => 'selected-text',
            'id' => 'selected-text',
            'onchange' => 'this.form.submit()'
        ]);

        $counter = 1;
        foreach ($textsids as $textid) {
            $options = ['value' => $textid];
            if ($textid == $selectedtextid) {
                $options['selected'] = true;
            }
            $output .= html_writer::tag('option', 'Text '.$counter, $options);
            $counter++;
        }

        $output .= html_writer::end_tag('select');

        $output .= html_writer::end_tag('form');

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
     * @return string HTML content.
     */
    public function words_list(array $words, object $tupf) {
        global $PAGE;

        require_once('resources/languages.php');

        $output = '';

        $buttons = '';

        $buttons .= $this->small_action_button(
            get_string('home', 'tupf'),
            new moodle_url('/mod/tupf/view.php', ['id' => $PAGE->cm->id]),
            'house-fill'
        );

        $buttons .= $this->small_action_button(
            get_string('editselectionbutton', 'tupf'),
            new moodle_url('/mod/tupf/editselection.php', ['id' => $PAGE->cm->id]),
            'pencil'
        );

        $output .= html_writer::div($buttons, 'btn-group mb-2 mb-sm-0 float-sm-right');

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

        return $output;
    }

    /**
     * Words review heading.
     *
     * @return string HTML content.
     */
    public function words_review_heading() {
        return $this->output->heading(get_string('wordsreview', 'tupf'), 2, null, 'tupf-heading');
    }

    /**
     * Word flashcard for words review.
     *
     * @param $word Word object from the `tupf_words` table.
     * @param integer $wordindex Position of the currently displayed word.
     * @param integer $totalwordscount Count of all words to review.
     * @param bool $backward Whether the user went backward. Defaults to `false`.
     * @return string HTML content.
     */
    public function words_review_flashcard($word, int $wordindex, int $totalwordscount, bool $backward = false) {
        $this->page->requires->js_call_amd('mod_tupf/flashcard', 'init');

        $output = '';

        $output .= html_writer::tag('p', get_string('wordsreview_help', 'tupf'));

        $columns = '';

        $previousdisabled = $wordindex == 1;
        $columns .= html_writer::div(
            $this->button_post(
                $this->icon('chevron-left'),
                'buttonaction',
                'previous',
                'btn btn-light btn-lg rounded-pill p-2 m-2',
                $previousdisabled,
                null,
                '#tupf-heading'
            ),
            'col-md-1 col-sm-2 order-last order-sm-first text-center'
        );

        $columns .= html_writer::div($this->flashcard($word, $backward), 'col-sm-auto');

        $buttons = '';
        $buttons .= $this->button_post(
            $this->icon('check'),
            'buttonaction',
            'nextcorrect',
            'btn btn-success btn-lg rounded-pill p-2 m-2',
            false,
            null,
            '#tupf-heading'
        );
        $buttons .= $this->button_post(
            $this->icon('x'), 'buttonaction', 'nextwrong', 'btn btn-danger btn-lg rounded-pill p-2 m-2', false, null, '#tupf-heading'
        );
        $columns .= html_writer::div($buttons, 'col-md-1 col-sm-2 mt-3 mt-sm-0 text-center');

        $output .= html_writer::div($columns, 'row justify-content-sm-center align-items-center mt-0 mt-sm-4');

        $output .= html_writer::tag('p', $wordindex.' / '.$totalwordscount, ['class' => 'small text-center mt-4 mb-2']);

        return $output;
    }

    /**
     * Words review ending buttons.
     *
     * @return string HTML content.
     */
    public function words_review_end_buttons() {
        $output = '';

        $output .= html_writer::tag('p', get_string('reviewend', 'tupf'), ['class' => 'text-center mt-4']);

        $output .= $this->buttons([
            [
                'file' => 'view.php',
                'text' => get_string('backhome', 'tupf'),
                'icon' => 'house',
            ],
            [
                'file' => 'review.php',
                'text' => get_string('restartreview', 'tupf'),
                'icon' => 'repeat',
            ],
        ]);

        return $output;
    }

    /**
     * Report heading, including the legend.
     *
     * @return string HTML content.
     */
    public function report_heading() {
        $output = '';

        $output .= $this->admin_top_links();
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
        $output = '';

        $output .= $this->admin_top_links();
        $output .= $this->output->heading(get_string('edittexts', 'tupf'), 2);
        $output .= html_writer::tag('p', get_string('edittexts_help', 'tupf'));

        if ($translating) {
            $output .= html_writer::start_tag('p');
            $output .= $this->icon('exclamation-circle', 18, 'mr-1 text-info align-text-bottom');
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
            'card-text' => '<path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/><path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 8zm0 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"/>',
            'chart' => '<path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>',
            'check' => '<path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>',
            'chevron-left' => '<path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>',
            'exclamation-circle' => '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>',
            'house' => '<path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4H2.5z"/>',
            'house-fill' => '<path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>',
            'list' => '<path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>',
            'pencil' => '<path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>',
            'repeat' => '<path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/><path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>',
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
     * @param string $anchor Optional anchor to an element on the page after button click.
     * @return string HTML content.
     */
    private function button_post(string $content, string $name, string $value, string $class = 'btn btn-link', bool $disabled = false, string $confirm = null, string $anchor = null) {
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
            'action' => $this->page->url.$anchor,
            'method' => 'post',
            'class' => 'inline',
            'onsubmit' => isset($confirm) ? 'return confirm("'.$confirm.'");' : '',
        ]);
    }

    /**
     * Buttons list on a row.
     *
     * @param array $buttonsdata Array of arrays with `file` (relatively to the module directory `/mod/tupf/`), `text`, and optional `icon`.
     * @param string $buttonclass Buttons class. Defaults to a secondary button styling.
     * @return string HTML content.
     */
    private function buttons(array $buttonsdata, string $buttonclass = 'btn btn-outline-primary m-2') {
        global $PAGE;

        $buttons = '';

        foreach ($buttonsdata as $buttondata) {
            $url = new moodle_url('/mod/tupf/'.$buttondata['file'], ['id' => $PAGE->cm->id]);
            $buttons .= html_writer::start_tag('a', ['href' => $url, 'class' => $buttonclass]);
            if (array_key_exists('icon', $buttondata)) {
                $buttons .= $this->icon($buttondata['icon'], 16, 'mr-2 align-text-bottom');
            }
            $buttons .= $buttondata['text'];
            $buttons .= html_writer::end_tag('a');
        }

        return html_writer::div($buttons, 'text-center my-3 my-sm-4');
    }

    /**
     * Small action button.
     *
     * @param string $name Button name.
     * @param string $url Link URL.
     * @param string $icon Optional icon name.
     * @param string $class Optional additional CSS class.
     * @return void
     */
    private function small_action_button(string $name, string $url, string $icon = null, string $class = '') {
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
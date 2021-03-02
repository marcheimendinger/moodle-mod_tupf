<?php

/**
 * Module localization in English.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

// General
$string['modulename'] = 'TUPF';
$string['pluginname'] = 'TUPF';
$string['modulenameplural'] = 'TUPFs';
$string['modulename_help'] = 'Text Understanding with Personalized Flashcards';
$string['cachedef_reviewingwords'] = 'Stores lists of words to review.';
$string['errornojavascript'] = 'JavaScript is required in this module.';
$string['backhome'] = 'Back home';

// Module settings (in `mod_form.php`)
$string['texts'] = 'Texts';
$string['text'] = 'Text';
$string['textno'] = 'Text {no}';
$string['warning'] = 'Warning';
$string['noeditionwarning'] = 'Be aware that you won\'t be able to modify these settings after submission.';
$string['textslanguage'] = 'Original texts language (L2)';
$string['translatedtextslanguage'] = 'Translated texts language (L1)';
$string['text_help'] = 'Here is where you specify the texts which will be presented to your students.

Each word of these texts will be automatically translated after submission.

You cannot edit these texts after submission.

If you leave some of the text fields blank, they won\'t be displayed. If you need more, click the "Insert one more text field" button as many times as necessary.';
$string['addtextfield'] = 'Insert one more text field';

// `view.php`
$string['errorpendingtexts'] = 'This activity is not ready yet. Come back in a little while.';
$string['startreview'] = 'Start reviewing words';
$string['resumereview'] = 'Continue reviewing words';
$string['displaywordslist'] = 'Show all selected words';

// `review.php`
$string['wordsreview'] = 'Words Review';
$string['wordsreview_help'] = 'Click on the card to reveal its translation.';
$string['previousword'] = 'Previous';
$string['nextwordcorrect'] = 'Correct';
$string['nextwordwrong'] = 'Wrong';
$string['reviewend'] = 'Good job! You reviewed all your words.';
$string['restartreview'] = 'Restart reviewing words';

// `words.php`
$string['selectedwords'] = 'Selected Words';
$string['correctpercentage'] = 'Correct Percentage';
$string['editselection'] = 'Edit words selection';

// `editselection.php`
$string['wordsselection'] = 'Words Selection';
$string['wordsselection_help'] = 'Click on words you don\'t know and you want to learn. You can click again to deselect a word. When you\'re happy with your selection, click on Submit below the text.';
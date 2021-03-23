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
$string['modulename_help'] = 'The TUPF (Text Understanding with Personalized Flashcards) activity module enables students to easily learn new words using virtual flashcards.

* The teacher initially submits one or more texts in the taught language (L2).
* These texts are automatically translated by the module (this can take several minutes).
* The teacher who initially created the activity is notified (by popup when logged-in or by email when logged-out) when translation is done.
* Students are asked on their first visit to select unknown words in one of the randomly selected texts.
* Students can review their selected words using virtual flashcards and indicate whether they knew the word.
* Students can list all their selected words with a percentage of the number of correct times for each word.
* Students can edit their words selection at any time.
* Teachers can add new texts and delete existing ones at any time. They cannot edit existing texts.
* Teachers have access to a report page showing which words are mostly selected by their students.';
$string['cachedef_reviewingwords'] = 'Stores lists of words to review.';
$string['errornojavascript'] = 'JavaScript is required in this activity.';
$string['errorpendingtexts'] = 'This activity is not ready yet. Come back in a little while.';
$string['errorpendingtextsadmin'] = 'This activity is not ready yet. You will be notified when this is done.';
$string['errortranslationsfailed'] = 'Some texts could not be translated. Delete them and retry.';
$string['erroraccessdenied'] = 'You don\'t have access to this feature.';
$string['backhome'] = 'Back home';

// Module settings (in `mod_form.php`)
$string['texts'] = 'Texts';
$string['text'] = 'Text';
$string['textno'] = 'Text {no}';
$string['textslanguage'] = 'Original texts language (L2)';
$string['translatedtextslanguage'] = 'Translated texts language (L1)';
$string['text_help'] = 'Here is where you specify the texts which will be presented to your students.

Each word of these texts will be automatically translated after submission. This can take a while.

You cannot edit these texts after submission. You can only delete them and add new ones.

If you leave some of the text fields blank, they won\'t be displayed. If you need more, click the "Insert one more text field" button as many times as necessary.';
$string['addtextfield'] = 'Insert one more text field';

// Messages
$string['messageprovider:translationconfirmation'] = 'Texts Successfully Translated';
$string['translationconfirmation_body'] = 'Your texts have been successfully translated. This activity is now ready to be used by your students.';
$string['translationconfirmation_link'] = 'Show activity';
$string['messageprovider:translationerror'] = 'Text Translation Error';
$string['translationerror_body'] = 'An error occurred while translating a text. Delete it and retry.';
$string['translationerror_link'] = 'Edit texts';

// `view.php`
$string['startselection'] = 'Select Words';
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
$string['editselectionbutton'] = 'Edit selection';

// `editselection.php`
$string['editselection'] = 'Edit Selected Words';
$string['editselection_help'] = 'Click on words you don\'t know and you want to learn. You can click again to deselect a word. When you\'re happy with your selection, click on Submit below the text.';

// `report.php`
$string['showreport'] = 'Usage';
$string['report'] = 'Words Usage';
$string['report_help'] = 'Here you can see which words are the most selected by your students. Legend:';
$string['reportlow'] = 'Not much';
$string['reportmedium'] = 'Some';
$string['reporthigh'] = 'A lot';
$string['reporttextnumber'] = 'Text {$a}';
$string['reporttextusage'] = 'This text is used by {$a} student(s).';

// `edittexts.php`
$string['edittextsbutton'] = 'Edit texts';
$string['edittextslink'] = 'Click here to edit your texts.';
$string['edittexts'] = 'Texts Edition';
$string['edittexts_help'] = 'Here you can add and delete texts.';
$string['refreshtranslationstatus'] = 'Some texts are currently being translated. This can take several minutes.';
$string['refreshtranslationstatuslink'] = 'Click here to refresh translation status.';
$string['edittextsdelete'] = 'Delete';
$string['translationloading'] = 'Translation in progress...';
$string['translationfailed'] = 'Translation failed. Delete the text and retry.';
$string['deletetextconfirmation'] = 'Do you really want to delete Text {$a}? This action is irreversible and will also delete any flashcard created by the students on the corresponding text.';
$string['deletetexterror'] = 'An error occurred while deleting the text. Please retry.';
$string['addtext'] = 'New text';
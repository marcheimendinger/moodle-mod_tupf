/**
 * Words selection.
 *
 * Fills an array of IDs by clicking on words in a text (links are defined in PHP).
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

import $ from 'jquery';

export const init = () => {
    let selectedWordsIds = $('a.tupf-word.mark').map(function() {
        return $(this).data('word-id');
    }).get();

    let submitButton = $('input#tupf-submit-button');

    if (selectedWordsIds.length == 0) {
        submitButton.prop('disabled', true);
    }

    $('a.tupf-word').on('click', (event) => {
        const element = $(event.target);
        const wordId = element.data('word-id');

        const wordIndex = selectedWordsIds.indexOf(wordId);
        if (wordIndex > -1) { // Deselection
            selectedWordsIds.splice(wordIndex, 1);
            element.removeClass('mark');
        } else { // Selection
            selectedWordsIds.push(wordId);
            element.addClass('mark');
        }

        submitButton.prop('disabled', selectedWordsIds.length == 0);
    });

    submitButton.on('click', () => {
        $('input[name=selected-words]').val(selectedWordsIds.join());
        $('form#tupf-words-selection-form').trigger('submit');
    });
};
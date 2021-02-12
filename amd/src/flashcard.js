/**
 * Flashcard interaction.
 *
 * JavaScript is used (instead of vanilla CSS) to solve compatibility issue with Internet Explorer.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

import $ from 'jquery';

export const init = () => {
    let flipped = false;

    $('.tupf-flashcard-container').on('click', () => {
        $('.tupf-flashcard-back').css('transform', flipped ? 'rotateY(-180deg)' : 'rotateY(0deg)');
        $('.tupf-flashcard-front').css('transform', flipped ? 'rotateY(0deg)' : 'rotateY(180deg)');
        flipped = !flipped;
    });
};
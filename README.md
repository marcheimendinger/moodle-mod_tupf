**⚠️ THIS PROJECT IS CURRENTLY A WORK IN PROGRESS ⚠️**

# Text Understanding with Personalized Flashcards – TUPF

The TUPF activity module for [Moodle](https://moodle.org) enables students to easily learn new words using virtual flashcards.

## Features

* The teacher initially submits one or more texts in the taught language (L2).
* These texts are automatically translated after submission (this can take several minutes) using an [adhoc task](https://docs.moodle.org/dev/Task_API#Adhoc_tasks).
* The translation is done using an external REST API built especially for the module.
* Students are asked on their first visit to select unknown words in one of the randomly selected texts.
* Students can review their selected words using virtual flashcards and indicate whether they knew the word or not.
* Students can list all their selected words with a percentage of the number of correct times for each word.
* Students can edit their words selection at any time.

## Technical Details

The module has been tested on Moodle `3.8` and `3.9` using a MongoDB database and PHP `7.4`.

The module uses standard [Bootstrap](https://getbootstrap.com) classes and therefore should be compatible with any Moodle theme. It has been tested on [Boost](https://docs.moodle.org/310/en/Boost_theme) and [UniGE](https://gitlab.unige.ch/eLearning/moodle/moodle-theme_unige) themes.

Without extensive testing it seems to run quite happily on:
* Google Chrome (macOS 11 and Windows 10)
* Firefox (macOS 11 and Windows 10)
* Safari (macOS 11 and iOS 14)
* Microsoft Edge (Windows 10)
* Internet Explorer (Windows 10)
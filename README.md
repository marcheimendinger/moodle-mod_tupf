**⚠️ THIS PROJECT IS CURRENTLY A WORK IN PROGRESS ⚠️**

# Text Understanding with Personalized Flashcards – TUPF

The TUPF activity module for [Moodle](https://moodle.org) enables students to easily learn new words using virtual flashcards.

## Features

* The teacher initially submits one or more texts in the taught language (L2).
* These texts are automatically translated after submission (this can take several minutes) using an [adhoc task](https://docs.moodle.org/dev/Task_API#Adhoc_tasks).
* The translation is done using an external REST API built especially for the module.
* The teacher who initially created the activity is notified (by popup when logged-in or by email when logged-out) when translation is done.
* Students are asked on their first visit to select unknown words in one of the randomly selected texts.
* Students can review their selected words using virtual flashcards and indicate whether they knew the word.
* Students can list all their selected words with a percentage of the number of correct times for each word.
* Students can edit their words selection at any time.
* Teachers can add new texts and delete existing ones at any time. They cannot edit existing texts.
* Teachers have access to a report page showing which words are mostly selected by their students.

## Installation

Run these commands from the root of your Moodle installation directory (where are `moodle` and `moodledata`).

```shell
cd moodle/mod
git clone https://github.com/marcheimendinger/moodle-mod_tupf
mv moodle-mod_tupf tupf
```

After running these commands, go to Moodle home in your Internet browser while being logged in as admin. A page will be presented, asking you to install the plugin and update the database. Simply follow the instructions on screen by confirming everything.

## Capabilities

Three different capabilities are used by the module.

| Capability             | Granted to (by default)               | Description                              |
| ---------------------- | ------------------------------------- | ---------------------------------------- |
| `mod/tupf:review`      | Students, editing teachers, teachers  | Can select and review words from a text. |
| `mod/tupf:readreport`  | Teachers, editing teachers            | Can read report about texts usage.       |
| `mod/tupf:addinstance` | Editing teachers                      | Can add and delete texts.                |

## Technical Details

The module has been tested on Moodle `3.8` and `3.9` using a MongoDB database and PHP `7.4`.

The module uses standard [Bootstrap](https://getbootstrap.com) classes and therefore should be compatible with any Moodle theme. It has been tested on [Boost](https://docs.moodle.org/310/en/Boost_theme) and [UniGE](https://gitlab.unige.ch/eLearning/moodle/moodle-theme_unige) themes.

Without extensive testing it seems to run quite happily on:
* Google Chrome (macOS 11 and Windows 10)
* Firefox (macOS 11 and Windows 10)
* Safari (macOS 11 and iOS 14)
* Microsoft Edge (Windows 10)
* Internet Explorer (Windows 10)
<?php

/**
 * Lists all module instances for a given course.
 *
 * @package mod_tupf
 * @author Marc Heimendinger
 */

require_once(__DIR__.'/../../config.php');

$courseid = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_course_login($course, true);

$strtupfplural = get_string('modulenameplural', 'tupf');

$PAGE->set_pagelayout('incourse');
$PAGE->set_context(context_course::instance($course->id));
$PAGE->set_url('/mod/tupf/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname.': '.$strtupfplural);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($strtupfplural);

if (!$tupfs = get_all_instances_in_course('tupf', $course)) {
    notice(get_string('thereareno', 'moodle', $strtupfplural), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = [$strsectionname, $strname, $strintro];
    $table->align = ['center', 'left', 'left'];
} else {
    $table->head  = [$strlastmodified, $strname, $strintro];
    $table->align = ['left', 'left', 'left'];
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($tupfs as $tupf) {
    $cm = $modinfo->cms[$tupf->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($tupf->section !== $currentsection) {
            if ($tupf->section) {
                $printsection = get_section_name($course, $tupf->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $tupf->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($tupf->timemodified)."</span>";
    }

    $class = $tupf->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed

    $table->data[] = [
        $printsection,
        "<a $class href=\"view.php?id=$cm->id\">".format_string($tupf->name)."</a>",
        format_module_intro('tupf', $tupf, $cm->id)
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
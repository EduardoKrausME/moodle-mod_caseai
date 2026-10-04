<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * view.php
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$n = optional_param('n', 0, PARAM_INT);
if ($id) {
    $cm = get_coursemodule_from_id('caseai', $id, 0, false, MUST_EXIST);
    $caseai = $DB->get_record('caseai', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $caseai = $DB->get_record('caseai', ['id' => $n], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('caseai', $caseai->id, $caseai->course, false, MUST_EXIST);
}
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/caseai:view', $context);

$PAGE->set_url('/mod/caseai/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($caseai->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($caseai->name));
if ($caseai->intro) {
    echo $OUTPUT->box(format_module_intro('caseai', $caseai, $cm->id), 'generalbox mod_introbox');
}

echo $OUTPUT->heading(get_string('scenario', 'mod_caseai'), 3);
echo $OUTPUT->box(format_text($caseai->scenario, FORMAT_PLAIN), 'generalbox');
if ($caseai->studentrole) {
    echo $OUTPUT->heading(get_string('studentrole', 'mod_caseai'), 3);
    echo $OUTPUT->box(format_text($caseai->studentrole, FORMAT_PLAIN), 'generalbox');
}

if (has_capability('mod/caseai:attempt', $context)) {
    $attempt = $DB->get_record('caseai_attempts', [
        'caseaiid' => $caseai->id,
        'userid' => $USER->id,
        'status' => 'inprogress',
    ], '*', IGNORE_MULTIPLE);
    $url = new moodle_url('/mod/caseai/attempt.php', ['id' => $cm->id]);
    $label = $attempt ? get_string('continueattempt', 'mod_caseai') : get_string('startattempt', 'mod_caseai');
    echo $OUTPUT->single_button($url, $label, 'get');
}
if (has_capability('mod/caseai:viewreports', $context)) {
    echo html_writer::div(html_writer::link(new moodle_url('/mod/caseai/report.php', ['id' => $cm->id]), get_string('viewreport', 'mod_caseai')));
}

echo $OUTPUT->footer();

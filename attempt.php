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
 * attempt.php
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_caseai\local\attempt_service;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('caseai', $id, 0, false, MUST_EXIST);
$caseai = $DB->get_record('caseai', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/caseai:attempt', $context);

$PAGE->set_url('/mod/caseai/attempt.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($caseai->name));
$PAGE->set_heading(format_string($course->fullname));

try {
    $attempt = attempt_service::get_or_create_attempt($caseai, $USER->id);
} catch (moodle_exception $e) {
    throw $e;
}

if ($attempt->status !== 'inprogress') {
    redirect(new moodle_url('/mod/caseai/view.php', ['id' => $cm->id]), get_string('attemptalreadyfinished', 'mod_caseai'));
}

$rounds = $DB->get_records('caseai_rounds', ['attemptid' => $attempt->id], 'roundnum ASC');
$last = $rounds ? end($rounds) : null;

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($caseai->name));
echo html_writer::div(get_string('roundxofy', 'mod_caseai', (object)[
    'current' => $attempt->currentround + 1,
    'max' => $caseai->maxrounds,
]), 'mb-3 text-muted');

if (!$rounds) {
    echo $OUTPUT->box(format_text($caseai->scenario, FORMAT_PLAIN), 'generalbox caseai-scenario');
} else {
    foreach ($rounds as $round) {
        echo html_writer::start_tag('section', ['class' => 'card mb-3']);
        echo html_writer::tag('div', get_string('roundlabel', 'mod_caseai', $round->roundnum), ['class' => 'card-header']);
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        echo html_writer::tag('strong', get_string('yourdecision', 'mod_caseai'));
        echo html_writer::div(format_text($round->decisiontext, FORMAT_PLAIN), 'mb-3');
        echo html_writer::tag('strong', get_string('consequence', 'mod_caseai'));
        echo html_writer::div(format_text($round->narrative, FORMAT_PLAIN), 'mb-3');
        if ($round->nextquestion) {
            echo html_writer::div(format_text($round->nextquestion, FORMAT_PLAIN), 'alert alert-info');
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('section');
    }
}

$prompt = $last && $last->nextquestion ? $last->nextquestion : get_string('firstdecisionprompt', 'mod_caseai');
echo $OUTPUT->heading(format_string($prompt), 3);

$formurl = new moodle_url('/mod/caseai/submit.php');
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attemptid', 'value' => $attempt->id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'stateversion', 'value' => $attempt->stateversion]);
echo html_writer::tag('textarea', '', [
    'name' => 'decision',
    'class' => 'form-control mb-3',
    'rows' => 7,
    'required' => 'required',
    'maxlength' => 12000,
    'aria-label' => get_string('decision', 'mod_caseai'),
]);
echo html_writer::tag('button', get_string('submitdecision', 'mod_caseai'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();

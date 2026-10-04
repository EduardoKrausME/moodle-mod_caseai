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
 * report.php
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use core\output\notification;
use mod_caseai\local\ai_service;

$id = required_param('id', PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$grade = optional_param('grade', null, PARAM_FLOAT);

$cm = get_coursemodule_from_id('caseai', $id, 0, false, MUST_EXIST);
$caseai = $DB->get_record('caseai', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/caseai:viewreports', $context);

$PAGE->set_url('/mod/caseai/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('report', 'mod_caseai'));
$PAGE->set_heading(format_string($course->fullname));

if ($attemptid && $action === 'summary') {
    require_sesskey();
    $attempt = $DB->get_record('caseai_attempts', ['id' => $attemptid, 'caseaiid' => $caseai->id], '*', MUST_EXIST);
    $rounds = $DB->get_records('caseai_rounds', ['attemptid' => $attempt->id], 'roundnum ASC');
    try {
        $summary = ai_service::summarise_attempt($caseai, $attempt, $rounds, $USER->id);
        $attempt->summary = $summary;
        $attempt->timemodified = time();
        $DB->update_record('caseai_attempts', $attempt);
    } catch (Throwable $e) {
        redirect($PAGE->url->out(false, ['attemptid' => $attemptid]), get_string('error:summaryfailed', 'mod_caseai'), null, notification::NOTIFY_ERROR);
    }
    redirect($PAGE->url->out(false, ['attemptid' => $attemptid]));
}

if ($attemptid && $action === 'grade') {
    require_sesskey();
    require_capability('mod/caseai:grade', $context);
    $attempt = $DB->get_record('caseai_attempts', ['id' => $attemptid, 'caseaiid' => $caseai->id], '*', MUST_EXIST);
    if ((float)$caseai->grade <= 0) {
        throw new moodle_exception('error:gradingdisabled', 'mod_caseai');
    }
    if ($grade === null || $grade < 0 || $grade > (float)$caseai->grade) {
        throw new moodle_exception('error:invalidgrade', 'mod_caseai');
    }
    $attempt->grade = $grade;
    $attempt->gradedby = $USER->id;
    $attempt->timegraded = time();
    $attempt->timemodified = time();
    $DB->update_record('caseai_attempts', $attempt);
    caseai_update_attempt_grade($caseai, $attempt);
    redirect($PAGE->url->out(false, ['attemptid' => $attemptid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'mod_caseai'));

if ($attemptid) {
    $attempt = $DB->get_record('caseai_attempts', ['id' => $attemptid, 'caseaiid' => $caseai->id], '*', MUST_EXIST);
    $user = $DB->get_record('user', ['id' => $attempt->userid], '*', MUST_EXIST);
    echo $OUTPUT->heading(fullname($user), 3);
    echo html_writer::div(get_string('attemptstatus', 'mod_caseai', $attempt->status), 'mb-3');

    $rounds = $DB->get_records('caseai_rounds', ['attemptid' => $attempt->id], 'roundnum ASC');
    foreach ($rounds as $round) {
        echo html_writer::start_tag('section', ['class' => 'card mb-3']);
        echo html_writer::tag('div', get_string('roundlabel', 'mod_caseai', $round->roundnum), ['class' => 'card-header']);
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        echo html_writer::tag('h5', get_string('yourdecision', 'mod_caseai'));
        echo html_writer::div(format_text($round->decisiontext, FORMAT_PLAIN));
        echo html_writer::tag('h5', get_string('consequence', 'mod_caseai'), ['class' => 'mt-3']);
        echo html_writer::div(format_text($round->narrative, FORMAT_PLAIN));
        echo html_writer::tag('h5', get_string('statebefore', 'mod_caseai'), ['class' => 'mt-3']);
        echo html_writer::tag('pre', s(json_encode(json_decode($round->statebefore, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)));
        echo html_writer::tag('h5', get_string('stateafter', 'mod_caseai'), ['class' => 'mt-3']);
        echo html_writer::tag('pre', s(json_encode(json_decode($round->stateafter, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)));
        if ($round->rejectedchanges && $round->rejectedchanges !== '[]') {
            echo html_writer::tag('h5', get_string('rejectedchanges', 'mod_caseai'), ['class' => 'mt-3']);
            echo html_writer::tag('pre', s(json_encode(json_decode($round->rejectedchanges, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)));
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('section');
    }

    if ($attempt->summary) {
        echo $OUTPUT->heading(get_string('aisummary', 'mod_caseai'), 3);
        echo $OUTPUT->box(format_text($attempt->summary, FORMAT_PLAIN));
    }
    $summaryurl = new moodle_url('/mod/caseai/report.php', [
        'id' => $cm->id, 'attemptid' => $attempt->id, 'action' => 'summary', 'sesskey' => sesskey(),
    ]);
    echo $OUTPUT->single_button($summaryurl, get_string('generatesummary', 'mod_caseai'), 'post');

    if ((float)$caseai->grade > 0 && has_capability('mod/caseai:grade', $context)) {
        echo $OUTPUT->heading(get_string('manualgrade', 'mod_caseai'), 3);
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url('/mod/caseai/report.php')]);
        foreach (['id' => $cm->id, 'attemptid' => $attempt->id, 'action' => 'grade', 'sesskey' => sesskey()] as $name => $value) {
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
        }
        echo html_writer::empty_tag('input', [
            'type' => 'number', 'name' => 'grade', 'step' => '0.01', 'min' => 0, 'max' => $caseai->grade,
            'value' => $attempt->grade ?? '', 'required' => 'required', 'class' => 'form-control mb-2',
        ]);
        echo html_writer::tag('button', get_string('savegrade', 'mod_caseai'), ['type' => 'submit', 'class' => 'btn btn-primary']);
        echo html_writer::end_tag('form');
    }
} else {
    $attempts = $DB->get_records('caseai_attempts', ['caseaiid' => $caseai->id], 'timemodified DESC');
    $table = new html_table();
    $table->head = [get_string('user'), get_string('status'), get_string('rounds', 'mod_caseai'), get_string('grade')];
    foreach ($attempts as $attempt) {
        $user = $DB->get_record('user', ['id' => $attempt->userid], 'id,firstname,lastname');
        if (!$user) {
            continue;
        }
        $table->data[] = [
            html_writer::link(new moodle_url('/mod/caseai/report.php', ['id' => $cm->id, 'attemptid' => $attempt->id]), fullname($user)),
            s($attempt->status),
            (int)$attempt->currentround,
            $attempt->grade === null ? '-' : format_float($attempt->grade, 2),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();

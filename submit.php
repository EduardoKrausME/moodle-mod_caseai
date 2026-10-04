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
 * submit.php
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use core\output\notification;
use mod_caseai\attempt_service;

$id = required_param('id', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);
$stateversion = required_param('stateversion', PARAM_INT);
$decision = required_param('decision', PARAM_RAW_TRIMMED);
require_sesskey();

$cm = get_coursemodule_from_id('caseai', $id, 0, false, MUST_EXIST);
$caseai = $DB->get_record('caseai', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/caseai:attempt', $context);

if (core_text::strlen($decision) < 1 || core_text::strlen($decision) > 12000) {
    throw new moodle_exception('error:decisionlength', 'mod_caseai');
}

try {
    attempt_service::submit_decision($caseai, $attemptid, $USER->id, $stateversion, $decision, $cm, $course);
    redirect(new moodle_url('/mod/caseai/attempt.php', ['id' => $cm->id]));
} catch (moodle_exception $e) {
    redirect(new moodle_url('/mod/caseai/attempt.php', ['id' => $cm->id]), $e->getMessage(), null, notification::NOTIFY_ERROR);
}

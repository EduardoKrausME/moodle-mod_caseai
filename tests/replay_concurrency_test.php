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

namespace mod_caseai;

use advanced_testcase;
use dml_write_exception;
use mod_caseai\attempt_service;
use moodle_exception;

/**
 * Replay and concurrency guard tests.
 *
 * @package mod_caseai
 * @covers \mod_caseai\attempt_service
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class replay_concurrency_test extends advanced_testcase {
    /**
     * Method test_stale_state_version_is_rejected.
     *
     * @return void Return value.
     */
    public function test_stale_state_version_is_rejected(): void {
        $attempt = (object)['status' => 'inprogress', 'stateversion' => 7];
        $this->expectException(moodle_exception::class);
        attempt_service::validate_submission_state($attempt, 6);
    }

    /**
     * Method test_finished_attempt_is_rejected_even_with_matching_version.
     *
     * @return void Return value.
     */
    public function test_finished_attempt_is_rejected_even_with_matching_version(): void {
        $attempt = (object)['status' => 'completed', 'stateversion' => 7];
        $this->expectException(moodle_exception::class);
        attempt_service::validate_submission_state($attempt, 7);
    }

    /**
     * Method test_attempt_round_unique_index_blocks_duplicate_replay.
     *
     * @return void Return value.
     */
    public function test_attempt_round_unique_index_blocks_duplicate_replay(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $caseaiid = $DB->insert_record('caseai', (object)[
            'course' => $course->id,
            'name' => 'Case',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'scenario' => 'Scenario',
            'studentrole' => '',
            'charactersjson' => '[]',
            'factsjson' => '[]',
            'immutablefactsjson' => '[]',
            'objectives' => '',
            'variablesjson' => '{}',
            'statesjson' => '[]',
            'eventsjson' => '[]',
            'endcriteriajson' => '[]',
            'maxrounds' => 2,
            'rubric' => '',
            'rubricformat' => FORMAT_HTML,
            'grade' => 0,
            'completionattempt' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $attemptid = $DB->insert_record('caseai_attempts', (object)[
            'caseaiid' => $caseaiid, 'userid' => $user->id, 'status' => 'inprogress', 'currentround' => 0,
            'stateversion' => 1, 'currentstate' => '{}', 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $round = (object)[
            'attemptid' => $attemptid, 'roundnum' => 1, 'decisiontext' => 'A', 'narrative' => 'B',
            'nextquestion' => 'C', 'evidencejson' => '[]', 'statebefore' => '{}', 'proposedchanges' => '[]',
            'appliedchanges' => '[]', 'rejectedchanges' => '[]', 'stateafter' => '{}', 'timecreated' => time(),
        ];
        $DB->insert_record('caseai_rounds', $round);
        $this->expectException(dml_write_exception::class);
        $DB->insert_record('caseai_rounds', $round);
    }
}

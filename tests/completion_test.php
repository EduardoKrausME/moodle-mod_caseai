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

/**
 * Completion behaviour tests.
 *
 * @package mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class completion_test extends advanced_testcase {
    /**
     * Method test_completed_attempt_is_detected_by_legacy_callback.
     *
     * @covers ::caseai_get_completion_state
     * @return void Return value.
     */
    public function test_completed_attempt_is_detected_by_legacy_callback(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/mod/caseai/lib.php');
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $caseai = (object)[
            'course' => $course->id, 'name' => 'Case', 'intro' => '', 'introformat' => FORMAT_HTML,
            'scenario' => 'Scenario', 'studentrole' => '', 'charactersjson' => '[]',
            'factsjson' => '[]', 'immutablefactsjson' => '[]',
            'objectives' => '', 'variablesjson' => '{}', 'statesjson' => '[]', 'eventsjson' => '[]',
            'endcriteriajson' => '[]', 'maxrounds' => 1, 'rubric' => '', 'rubricformat' => FORMAT_HTML,
            'grade' => 0, 'completionattempt' => 1, 'timecreated' => time(), 'timemodified' => time(),
        ];
        $caseai->id = $DB->insert_record('caseai', $caseai);
        $DB->insert_record('caseai_attempts', (object)[
            'caseaiid' => $caseai->id, 'userid' => $user->id, 'status' => 'completed', 'currentround' => 1,
            'stateversion' => 2, 'currentstate' => '{}', 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $cm = (object)['instance' => $caseai->id];
        $this->assertTrue(\caseai_get_completion_state($course, $cm, $user->id, COMPLETION_AND));
    }
}

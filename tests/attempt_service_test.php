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
use mod_caseai\attempt_service;

/**
 * Attempt persistence tests which do not invoke the external AI bridge.
 *
 * @package mod_caseai
 * @covers \mod_caseai\attempt_service
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attempt_service_test extends advanced_testcase {
    /**
     * Method test_get_or_create_reuses_open_attempt.
     *
     * @return void Return value.
     */
    public function test_get_or_create_reuses_open_attempt(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $caseai = (object)[
            'course' => $course->id, 'name' => 'Case', 'scenario' => 'Scenario', 'studentrole' => '',
            'charactersjson' => '[]', 'factsjson' => '["context"]', 'immutablefactsjson' => '["fixed"]', 'objectives' => '',
            'variablesjson' => '{"risk":{"type":"integer","initial":1,"min":0,"max":10,"mutable":true}}',
            'statesjson' => '[]', 'eventsjson' => '[]', 'endcriteriajson' => '[]', 'maxrounds' => 5,
            'rubric' => '', 'rubricformat' => FORMAT_HTML, 'grade' => 0, 'completionattempt' => 0,
            'intro' => '', 'introformat' => FORMAT_HTML, 'timecreated' => time(), 'timemodified' => time(),
        ];
        $caseai->id = $DB->insert_record('caseai', $caseai);
        $first = attempt_service::get_or_create_attempt($caseai, $user->id);
        $second = attempt_service::get_or_create_attempt($caseai, $user->id);
        $this->assertSame($first->id, $second->id);
        $state = json_decode($first->currentstate, true);
        $this->assertSame(['fixed'], $state['facts']);
        $this->assertSame(1, $state['variables']['risk']);
    }
}

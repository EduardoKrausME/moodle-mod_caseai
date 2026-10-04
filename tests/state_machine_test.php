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
use mod_caseai\local\state_machine;

/**
 * State machine tests.
 *
 * @package mod_caseai
 * @covers \mod_caseai\local\state_machine
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class state_machine_test extends advanced_testcase {
    /**
     * Method test_initial_state_and_allowed_change.
     *
     * @return void Return value.
     */
    public function test_initial_state_and_allowed_change(): void {
        $definitions = [
            'risk' => ['type' => 'integer', 'initial' => 1, 'min' => 0, 'max' => 10, 'mutable' => true],
            'caseid' => ['type' => 'string', 'initial' => 'A-1', 'mutable' => false],
        ];
        $state = state_machine::initial_state($definitions, ['Fact A']);
        $result = state_machine::apply_proposed_changes($state, $definitions, [
            ['variable' => 'risk', 'value' => 3, 'reason' => 'Decision increased exposure'],
        ], 'Proceed');
        $this->assertSame(3, $result['state']['variables']['risk']);
        $this->assertSame(['Fact A'], $result['state']['facts']);
        $this->assertCount(1, $result['applied']);
        $this->assertEmpty($result['rejected']);
    }

    /**
     * Method test_immutable_unknown_and_out_of_range_changes_are_rejected.
     *
     * @return void Return value.
     */
    public function test_immutable_unknown_and_out_of_range_changes_are_rejected(): void {
        $definitions = [
            'risk' => ['type' => 'integer', 'initial' => 1, 'min' => 0, 'max' => 10, 'mutable' => true],
            'caseid' => ['type' => 'string', 'initial' => 'A-1', 'mutable' => false],
        ];
        $state = state_machine::initial_state($definitions, ['Never changes']);
        $result = state_machine::apply_proposed_changes($state, $definitions, [
            ['variable' => 'caseid', 'value' => 'B-2'],
            ['variable' => 'risk', 'value' => 99],
            ['variable' => 'invented', 'value' => true],
        ], 'Try invalid changes');
        $this->assertSame('A-1', $result['state']['variables']['caseid']);
        $this->assertSame(1, $result['state']['variables']['risk']);
        $this->assertCount(3, $result['rejected']);
    }

    /**
     * Method test_end_criteria.
     *
     * @return void Return value.
     */
    public function test_end_criteria(): void {
        $state = ['variables' => ['status' => 'closed', 'risk' => 4]];
        $this->assertTrue(state_machine::should_end($state, [
            ['variable' => 'status', 'operator' => '==', 'value' => 'closed'],
        ]));
        $this->assertFalse(state_machine::should_end($state, [
            ['variable' => 'risk', 'operator' => '>=', 'value' => 9],
        ]));
    }
}

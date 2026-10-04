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
 * Capability declaration smoke tests.
 *
 * @package mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class capability_test extends advanced_testcase {
    /**
     * Method test_required_capabilities_are_declared.
     *
     * @return void Return value.
     */
    public function test_required_capabilities_are_declared(): void {
        $this->resetAfterTest();
        $capabilities = get_capabilities_from_disk('mod_caseai');
        foreach (['mod/caseai:addinstance', 'mod/caseai:view', 'mod/caseai:attempt', 'mod/caseai:viewreports', 'mod/caseai:grade'] as $capability) {
            $this->assertArrayHasKey($capability, $capabilities);
        }
    }
}

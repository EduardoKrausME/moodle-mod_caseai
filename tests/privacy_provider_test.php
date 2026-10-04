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
use core_privacy\local\metadata\collection;
use mod_caseai\privacy\provider;

/**
 * Privacy provider tests.
 *
 * @package mod_caseai
 * @covers \mod_caseai\privacy\provider
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class privacy_provider_test extends advanced_testcase {
    /**
     * Method test_metadata_provider_is_complete.
     *
     * @return void Return value.
     */
    public function test_metadata_provider_is_complete(): void {
        $collection = new collection('mod_caseai');
        $result = provider::get_metadata($collection);
        $this->assertNotEmpty($result->get_collection());
    }
}

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
use mod_caseai\local\ai_service;
use moodle_exception;

/**
 * AI response parser tests.
 *
 * @package mod_caseai
 * @covers \mod_caseai\local\ai_service
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ai_service_test extends advanced_testcase {
    /**
     * Method test_valid_json_is_parsed.
     *
     * @return void Return value.
     */
    public function test_valid_json_is_parsed(): void {
        $result = ai_service::parse_simulation_response(json_encode([
            'narrative' => 'The situation changes.',
            'statechanges' => [['variable' => 'risk', 'value' => 2, 'reason' => 'x']],
            'nextquestion' => 'What now?',
            'evidence' => ['Called the supplier'],
        ]));
        $this->assertSame('The situation changes.', $result['narrative']);
        $this->assertCount(1, $result['statechanges']);
    }

    /**
     * Method test_markdown_fenced_json_is_tolerated.
     *
     * @return void Return value.
     */
    public function test_markdown_fenced_json_is_tolerated(): void {
        $json = json_encode([
            'narrative' => 'N', 'statechanges' => [], 'nextquestion' => 'Q', 'evidence' => [],
        ]);
        $result = ai_service::parse_simulation_response("```json\n{$json}\n```");
        $this->assertSame('N', $result['narrative']);
    }

    /**
     * Method test_malformed_json_throws.
     *
     * @return void Return value.
     */
    public function test_malformed_json_throws(): void {
        $this->expectException(moodle_exception::class);
        ai_service::parse_simulation_response('{broken');
    }

    /**
     * Method test_missing_contract_field_throws.
     *
     * @return void Return value.
     */
    public function test_missing_contract_field_throws(): void {
        $this->expectException(moodle_exception::class);
        ai_service::parse_simulation_response('{"narrative":"x"}');
    }
}

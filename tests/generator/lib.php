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
 * Data generator for mod_caseai tests.
 *
 * @package mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_caseai_generator extends testing_module_generator {
    /**
     * Creates a Case AI activity instance.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)($record ?? []);
        $defaults = [
            'name' => 'Adaptive case',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'scenario' => 'A decision must be made.',
            'studentrole' => 'Decision maker',
            'charactersjson' => '[]',
            'factsjson' => '[]',
            'immutablefactsjson' => '[]',
            'objectives' => 'Practice professional decision making.',
            'variablesjson' => '{"status":{"type":"enum","initial":"open","values":["open","closed"],"mutable":true}}',
            'statesjson' => '["open","closed"]',
            'eventsjson' => '[]',
            'endcriteriajson' => '[{"variable":"status","operator":"==","value":"closed"}]',
            'maxrounds' => 5,
            'rubric' => '',
            'rubricformat' => FORMAT_HTML,
            'grade' => 0,
            'completionattempt' => 0,
        ];
        foreach ($defaults as $key => $value) {
            if (!property_exists($record, $key)) {
                $record->{$key} = $value;
            }
        }
        return parent::create_instance($record, $options);
    }
}

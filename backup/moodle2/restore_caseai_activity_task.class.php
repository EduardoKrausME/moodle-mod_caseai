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
 * Restore task for mod_caseai.
 *
 * @package mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/caseai/backup/moodle2/restore_caseai_stepslib.php');

/**
 * Class restore_caseai_activity_task
 */
class restore_caseai_activity_task extends restore_activity_task {
    /**
     * No special settings are required.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines activity restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_caseai_activity_structure_step('caseai_structure', 'caseai.xml'));
    }

    /**
     * Defines decoded content rules.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('caseai', ['intro', 'scenario', 'studentrole', 'objectives', 'rubric'], 'caseai'),
        ];
    }

    /**
     * Defines decoded link rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('CASEAIINDEX', '/mod/caseai/index.php?id=$1', 'course'),
        ];
    }
}

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
 * Restore structure for mod_caseai.
 *
 * @package mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_caseai_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines restore paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [new restore_path_element('caseai', '/activity/caseai')];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('caseai_attempt', '/activity/caseai/attempts/attempt');
            $paths[] = new restore_path_element('caseai_round', '/activity/caseai/attempts/attempt/rounds/round');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the main activity row.
     *
     * @param array $data
     */
    protected function process_caseai($data) {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('caseai', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores an attempt.
     *
     * @param array $data
     */
    protected function process_caseai_attempt($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->caseaiid = $this->get_new_parentid('caseai');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!empty($data->gradedby)) {
            $data->gradedby = $this->get_mappingid('user', $data->gradedby, null);
        }
        $newitemid = $DB->insert_record('caseai_attempts', $data);
        $this->set_mapping('caseai_attempt', $oldid, $newitemid);
    }

    /**
     * Restores a round.
     *
     * @param array $data
     */
    protected function process_caseai_round($data) {
        global $DB;
        $data = (object)$data;
        $data->attemptid = $this->get_new_parentid('caseai_attempt');
        $DB->insert_record('caseai_rounds', $data);
    }

    /**
     * Restores activity files.
     */
    protected function after_execute() {
        $this->add_related_files('mod_caseai', 'intro', null);
    }
}

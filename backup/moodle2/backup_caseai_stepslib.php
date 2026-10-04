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
 * Backup structure for mod_caseai.
 *
 * @package mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_caseai_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the nested backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $caseai = new backup_nested_element('caseai', ['id'], [
            'name', 'intro', 'introformat', 'scenario', 'studentrole', 'charactersjson', 'factsjson', 'objectives',
            'immutablefactsjson', 'variablesjson', 'statesjson', 'eventsjson', 'endcriteriajson', 'maxrounds', 'rubric', 'rubricformat',
            'grade', 'completionattempt', 'timecreated', 'timemodified',
        ]);
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid', 'status', 'currentround', 'stateversion', 'currentstate', 'summary', 'grade', 'gradedby',
            'timegraded', 'timecreated', 'timemodified', 'timefinished',
        ]);
        $rounds = new backup_nested_element('rounds');
        $round = new backup_nested_element('round', ['id'], [
            'roundnum', 'decisiontext', 'narrative', 'nextquestion', 'evidencejson', 'statebefore', 'proposedchanges',
            'appliedchanges', 'rejectedchanges', 'stateafter', 'timecreated',
        ]);

        $caseai->add_child($attempts);
        $attempts->add_child($attempt);
        $attempt->add_child($rounds);
        $rounds->add_child($round);

        $caseai->set_source_table('caseai', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $attempt->set_source_table('caseai_attempts', ['caseaiid' => backup::VAR_PARENTID]);
            $round->set_source_table('caseai_rounds', ['attemptid' => backup::VAR_PARENTID]);
            $attempt->annotate_ids('user', 'userid');
            $attempt->annotate_ids('user', 'gradedby');
        }
        $caseai->annotate_files('mod_caseai', 'intro', null);
        return $this->prepare_activity_structure($caseai);
    }
}

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

namespace mod_caseai\completion;

use coding_exception;
use core_completion\activity_custom_completion;

/**
 * Custom completion rules for Case AI.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Defines the custom completion rules owned by this module.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionattempt'];
    }

    /**
     * Returns completion state for the completed-attempt rule.
     *
     * @param string $rule
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;
        if ($rule !== 'completionattempt') {
            throw new coding_exception('Unsupported completion rule: ' . $rule);
        }
        $this->validate_rule($rule);
        return $DB->record_exists('caseai_attempts', [
            'caseaiid' => $this->cm->instance,
            'userid' => $this->userid,
            'status' => 'completed',
        ]) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Describes enabled custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionattempt' => get_string('completionattempt_desc', 'mod_caseai'),
        ];
    }

    /**
     * Returns enabled completion rules for this activity.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionattempt',
            'completionusegrade',
            'completionpassgrade',
        ];
    }
}

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

use cm_info;
use completion_info;
use core\lock\lock_config;
use moodle_exception;
use stdClass;

/**
 * Attempt lifecycle and transactional round persistence.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_service {
    /**
     * Returns the open attempt or creates a new one.
     *
     * @param stdClass $caseai
     * @param int $userid
     * @return stdClass
     */
    public static function get_or_create_attempt(stdClass $caseai, int $userid): stdClass {
        global $DB;
        $factory = lock_config::get_lock_factory('mod_caseai');
        $lock = $factory->get_lock("create:{$caseai->id}:$userid", 10);
        if (!$lock) {
            throw new moodle_exception('error:attemptlocked', 'mod_caseai');
        }
        try {
            $attempt = $DB->get_record('caseai_attempts', [
                'caseaiid' => $caseai->id,
                'userid' => $userid,
                'status' => 'inprogress',
            ], '*', IGNORE_MULTIPLE);
            if ($attempt) {
                $attempt->id = (int)$attempt->id;
                return $attempt;
            }
            $definitions = json_helper::decode($caseai->variablesjson, 'variablesjson');
            $facts = json_helper::decode($caseai->immutablefactsjson ?? '[]', 'immutablefactsjson');
            $state = state_machine::initial_state($definitions, $facts);
            $now = time();
            $record = (object)[
                'caseaiid' => $caseai->id,
                'userid' => $userid,
                'status' => 'inprogress',
                'currentround' => 0,
                'stateversion' => 1,
                'currentstate' => json_helper::encode($state),
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $record->id = (int)$DB->insert_record('caseai_attempts', $record);
            return $record;
        } finally {
            $lock->release();
        }
    }

    /**
     * Validates replay/concurrency guards before a submission is processed.
     *
     * @param stdClass $attempt
     * @param int $expectedversion
     * @return void
     */
    public static function validate_submission_state(stdClass $attempt, int $expectedversion): void {
        if ($attempt->status !== 'inprogress') {
            throw new moodle_exception('error:attemptfinished', 'mod_caseai');
        }
        if ((int)$attempt->stateversion !== $expectedversion) {
            throw new moodle_exception('error:staleattempt', 'mod_caseai');
        }
    }

    /**
     * Submits one decision. AI failure or malformed output leaves state untouched.
     *
     * @param stdClass $caseai
     * @param int $attemptid
     * @param int $userid
     * @param int $expectedversion
     * @param string $decision
     * @param cm_info|stdClass $cm
     * @param stdClass $course
     * @return stdClass
     */
    public static function submit_decision(stdClass $caseai, int $attemptid, int $userid, int $expectedversion,
                                           string $decision, $cm, stdClass $course): stdClass {
        global $DB;
        rate_limiter::consume((int)$caseai->id, $userid);

        $factory = lock_config::get_lock_factory('mod_caseai');
        $lock = $factory->get_lock("attempt:$attemptid", 10);
        if (!$lock) {
            throw new moodle_exception('error:attemptlocked', 'mod_caseai');
        }
        try {
            $attempt = $DB->get_record('caseai_attempts', [
                'id' => $attemptid,
                'caseaiid' => $caseai->id,
                'userid' => $userid,
            ], '*', MUST_EXIST);
            self::validate_submission_state($attempt, $expectedversion);
            if ((int)$attempt->currentround >= (int)$caseai->maxrounds) {
                throw new moodle_exception('error:maxroundsreached', 'mod_caseai');
            }

            $statebefore = json_helper::decode($attempt->currentstate, 'currentstate');
            // External call intentionally happens before DB transaction. No plugin state is changed on failure.
            $ai = ai_service::simulate($caseai, $statebefore, $decision, $userid);
            $definitions = json_helper::decode($caseai->variablesjson, 'variablesjson');
            $transition = state_machine::apply_proposed_changes($statebefore, $definitions, $ai['statechanges'], $decision);
            $stateafter = $transition['state'];
            $nextround = ((int)$attempt->currentround) + 1;
            $criteria = json_helper::decode($caseai->endcriteriajson, 'endcriteriajson');
            $completed = $nextround >= (int)$caseai->maxrounds || state_machine::should_end($stateafter, $criteria);

            $transaction = $DB->start_delegated_transaction();
            // Re-read under transaction while lock is held to guard against stale/replayed submissions.
            $fresh = $DB->get_record('caseai_attempts', ['id' => $attemptid], '*', MUST_EXIST);
            if ((int)$fresh->stateversion !== $expectedversion || $fresh->status !== 'inprogress') {
                throw new moodle_exception('error:staleattempt', 'mod_caseai');
            }
            $DB->insert_record('caseai_rounds', (object)[
                'attemptid' => $attemptid,
                'roundnum' => $nextround,
                'decisiontext' => clean_param($decision, PARAM_TEXT),
                'narrative' => $ai['narrative'],
                'nextquestion' => $ai['nextquestion'],
                'evidencejson' => json_helper::encode($ai['evidence']),
                'statebefore' => json_helper::encode($statebefore),
                'proposedchanges' => json_helper::encode($ai['statechanges']),
                'appliedchanges' => json_helper::encode($transition['applied']),
                'rejectedchanges' => json_helper::encode($transition['rejected']),
                'stateafter' => json_helper::encode($stateafter),
                'timecreated' => time(),
            ]);
            $fresh->currentround = $nextround;
            $fresh->stateversion = ((int)$fresh->stateversion) + 1;
            $fresh->currentstate = json_helper::encode($stateafter);
            $fresh->status = $completed ? 'completed' : 'inprogress';
            $fresh->timemodified = time();
            if ($completed) {
                $fresh->timefinished = time();
            }
            $DB->update_record('caseai_attempts', $fresh);
            $transaction->allow_commit();

            if ($completed && !empty($caseai->completionattempt)) {
                $completion = new completion_info($course);
                if ($completion->is_enabled($cm)) {
                    $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
                }
            }
            return $fresh;
        } finally {
            $lock->release();
        }
    }
}

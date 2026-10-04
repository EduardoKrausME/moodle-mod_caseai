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

namespace mod_caseai\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    core_userlist_provider {

    /**
     * Describes stored personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('caseai_attempts', [
            'userid' => 'privacy:metadata:attempts:userid',
            'currentstate' => 'privacy:metadata:attempts:currentstate',
            'summary' => 'privacy:metadata:attempts:summary',
            'grade' => 'privacy:metadata:attempts:grade',
            'gradedby' => 'privacy:metadata:attempts:gradedby',
            'timecreated' => 'privacy:metadata:attempts:timecreated',
            'timemodified' => 'privacy:metadata:attempts:timemodified',
        ], 'privacy:metadata:attempts');
        $collection->add_database_table('caseai_rounds', [
            'decisiontext' => 'privacy:metadata:rounds:decisiontext',
            'narrative' => 'privacy:metadata:rounds:narrative',
            'evidencejson' => 'privacy:metadata:rounds:evidence',
            'statebefore' => 'privacy:metadata:rounds:state',
            'stateafter' => 'privacy:metadata:rounds:state',
            'timecreated' => 'privacy:metadata:rounds:timecreated',
        ], 'privacy:metadata:rounds');
        $collection->add_database_table('caseai_rate', [
            'userid' => 'privacy:metadata:rate:userid',
            'windowstart' => 'privacy:metadata:rate:windowstart',
            'requestcount' => 'privacy:metadata:rate:requestcount',
        ], 'privacy:metadata:rate');
        $collection->add_external_location_link('local_ai_bridge', [
            'userid' => 'privacy:metadata:bridge:userid',
            'decision' => 'privacy:metadata:bridge:decision',
            'state' => 'privacy:metadata:bridge:state',
        ], 'privacy:metadata:bridge');
        return $collection;
    }

    /**
     * Gets module contexts containing data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {caseai} c ON c.id = cm.instance
                  JOIN {caseai_attempts} a ON a.caseaiid = c.id
                 WHERE a.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'caseai', 'userid' => $userid];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Exports user data.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('caseai', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $caseai = $DB->get_record('caseai', ['id' => $cm->instance]);
            if (!$caseai) {
                continue;
            }
            helper::export_context_files($context, $userid);
            $attempts = $DB->get_records('caseai_attempts', ['caseaiid' => $caseai->id, 'userid' => $userid], 'timecreated ASC');
            foreach ($attempts as $attempt) {
                $subcontext = [get_string('attempt', 'mod_caseai') . ' ' . $attempt->id];
                $data = (object)[
                    'status' => $attempt->status,
                    'currentround' => $attempt->currentround,
                    'currentstate' => json_decode($attempt->currentstate, true),
                    'summary' => $attempt->summary,
                    'grade' => $attempt->grade,
                    'timecreated' => transform::datetime($attempt->timecreated),
                    'timemodified' => transform::datetime($attempt->timemodified),
                    'timefinished' => $attempt->timefinished ? transform::datetime($attempt->timefinished) : null,
                ];
                writer::with_context($context)->export_data($subcontext, $data);
                $rounds = $DB->get_records('caseai_rounds', ['attemptid' => $attempt->id], 'roundnum ASC');
                foreach ($rounds as $round) {
                    writer::with_context($context)->export_data(array_merge($subcontext,
                        [get_string('roundlabel', 'mod_caseai', $round->roundnum)]), (object)[
                        'decision' => $round->decisiontext,
                        'narrative' => $round->narrative,
                        'nextquestion' => $round->nextquestion,
                        'evidence' => json_decode($round->evidencejson ?: '[]', true),
                        'statebefore' => json_decode($round->statebefore, true),
                        'stateafter' => json_decode($round->stateafter, true),
                        'timecreated' => transform::datetime($round->timecreated),
                    ]);
                }
            }
        }
    }

    /**
     * Deletes all plugin data in a module context.
     *
     * @param context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;
        if (!$context instanceof context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('caseai', $context->instanceid);
        if (!$cm) {
            return;
        }
        $attemptids = $DB->get_fieldset_select('caseai_attempts', 'id', 'caseaiid = :caseaiid', ['caseaiid' => $cm->instance]);
        self::delete_attempt_ids($attemptids);
        $DB->delete_records('caseai_attempts', ['caseaiid' => $cm->instance]);
        $DB->delete_records('caseai_rate', ['caseaiid' => $cm->instance]);
    }

    /**
     * Deletes data for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('caseai', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $attemptids = $DB->get_fieldset_select('caseai_attempts', 'id', 'caseaiid = :caseaiid AND userid = :userid', [
                'caseaiid' => $cm->instance, 'userid' => $userid,
            ]);
            self::delete_attempt_ids($attemptids);
            $DB->delete_records('caseai_attempts', ['caseaiid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('caseai_rate', ['caseaiid' => $cm->instance, 'userid' => $userid]);
            $DB->set_field('caseai_attempts', 'gradedby', null, ['caseaiid' => $cm->instance, 'gradedby' => $userid]);
        }
    }

    /**
     * Gets users with personal data in a module context.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }
        $sql = "SELECT a.userid
                  FROM {caseai_attempts} a
                  JOIN {course_modules} cm ON cm.instance = a.caseaiid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['modname' => 'caseai', 'cmid' => $context->instanceid]);
    }

    /**
     * Deletes data for a list of approved users in a context.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('caseai', $context->instanceid);
        if (!$cm || !$userlist->get_userids()) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED, 'user');
        $params = ['caseaiid' => $cm->instance] + $inparams;
        $attemptids = $DB->get_fieldset_select('caseai_attempts', 'id', "caseaiid = :caseaiid AND userid $insql", $params);
        self::delete_attempt_ids($attemptids);
        $DB->delete_records_select('caseai_attempts', "caseaiid = :caseaiid AND userid $insql", $params);
        $DB->delete_records_select('caseai_rate', "caseaiid = :caseaiid AND userid $insql", $params);
        $DB->set_field_select('caseai_attempts', 'gradedby', null, "caseaiid = :caseaiid AND gradedby $insql", $params);
    }

    /**
     * Deletes round data for attempt IDs.
     *
     * @param array $attemptids
     * @return void
     */
    private static function delete_attempt_ids(array $attemptids): void {
        global $DB;
        if (!$attemptids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'attempt');
        $DB->delete_records_select('caseai_rounds', "attemptid $insql", $params);
    }
}

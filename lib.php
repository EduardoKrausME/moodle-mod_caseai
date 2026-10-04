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
 * Library functions for mod_caseai.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Declares supported features.
 *
 * @param string $feature
 * @return mixed
 */
function caseai_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_OTHER,
        FEATURE_GROUPS => true,
        FEATURE_GROUPINGS => true,
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_GRADE_HAS_GRADE => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_BACKUP_MOODLE2 => true,
        default => null,
    };
}

/**
 * Adds an activity instance.
 *
 * @param stdClass $data
 * @param mod_caseai_mod_form|null $mform
 * @return int
 */
function caseai_add_instance($data, $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->id = $DB->insert_record('caseai', $data);
    caseai_grade_item_update($data);
    return $data->id;
}

/**
 * Updates an activity instance.
 *
 * @param stdClass $data
 * @param mod_caseai_mod_form|null $mform
 * @return bool
 */
function caseai_update_instance($data, $mform = null) {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    $result = $DB->update_record('caseai', $data);
    caseai_grade_item_update($data);
    return $result;
}

/**
 * Deletes an activity instance and its plugin-owned data.
 *
 * @param int $id
 * @return bool
 */
function caseai_delete_instance($id) {
    global $DB;
    $caseai = $DB->get_record('caseai', ['id' => $id]);
    if (!$caseai) {
        return false;
    }
    $attemptids = $DB->get_fieldset_select('caseai_attempts', 'id', 'caseaiid = :id', ['id' => $id]);
    if ($attemptids) {
        [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'attempt');
        $DB->delete_records_select('caseai_rounds', "attemptid $insql", $params);
    }
    $DB->delete_records('caseai_attempts', ['caseaiid' => $id]);
    $DB->delete_records('caseai_rate', ['caseaiid' => $id]);
    $DB->delete_records('caseai', ['id' => $id]);
    caseai_grade_item_delete($caseai);
    return true;
}

/**
 * Creates or updates the grade item.
 *
 * @param stdClass $caseai
 * @param array|null $grades
 * @return int
 */
function caseai_grade_item_update($caseai, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $params = ['itemname' => $caseai->name];
    if ((float)$caseai->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = (float)$caseai->grade;
        $params['grademin'] = 0;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }
    return grade_update('mod/caseai', $caseai->course, 'mod', 'caseai', $caseai->id, 0, $grades, $params);
}

/**
 * Deletes the grade item.
 *
 * @param stdClass $caseai
 * @return int
 */
function caseai_grade_item_delete($caseai) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/caseai', $caseai->course, 'mod', 'caseai', $caseai->id, 0, null, ['deleted' => 1]);
}

/**
 * Pushes a manually reviewed attempt grade to the gradebook.
 *
 * @param stdClass $caseai
 * @param stdClass $attempt
 * @return int
 */
function caseai_update_attempt_grade($caseai, $attempt) {
    if ((float)$caseai->grade <= 0 || $attempt->grade === null) {
        return GRADE_UPDATE_OK;
    }
    $grade = new stdClass();
    $grade->userid = $attempt->userid;
    $grade->rawgrade = min((float)$caseai->grade, max(0.0, (float)$attempt->grade));
    $grade->dategraded = $attempt->timegraded ?: time();
    return caseai_grade_item_update($caseai, [$attempt->userid => $grade]);
}

/**
 * Returns custom completion state for legacy completion calls.
 *
 * @param stdClass $course
 * @param cm_info $cm
 * @param int $userid
 * @param int $type
 * @return bool
 */
function caseai_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    $caseai = $DB->get_record('caseai', ['id' => $cm->instance], '*', MUST_EXIST);
    if (empty($caseai->completionattempt)) {
        return $type === COMPLETION_AND;
    }
    return $DB->record_exists('caseai_attempts', [
        'caseaiid' => $caseai->id,
        'userid' => $userid,
        'status' => 'completed',
    ]);
}

/**
 * Populates cached course-module data, including custom completion rules.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|false
 */
function caseai_get_coursemodule_info($coursemodule) {
    global $DB;
    $caseai = $DB->get_record('caseai', ['id' => $coursemodule->instance], 'id,name,intro,introformat,completionattempt');
    if (!$caseai) {
        return false;
    }
    $result = new cached_cm_info();
    $result->name = $caseai->name;
    if ($coursemodule->showdescription) {
        $result->content = format_module_intro('caseai', $caseai, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionattempt'] = (int)$caseai->completionattempt;
    }
    return $result;
}

/**
 * Returns human-readable descriptions for enabled custom completion rules.
 *
 * @param cm_info|stdClass $cm
 * @return array
 */
function mod_caseai_get_completion_active_rule_descriptions($cm) {
    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }
    $descriptions = [];
    if (!empty($cm->customdata['customcompletionrules']['completionattempt'])) {
        $descriptions[] = get_string('completionattempt_desc', 'mod_caseai');
    }
    return $descriptions;
}

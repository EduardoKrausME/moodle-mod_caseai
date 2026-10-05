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
 * Activity settings form.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_caseai\json_helper;
use mod_caseai\state_machine;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Case AI activity form.
 */
class mod_caseai_mod_form extends moodleform_mod {
    /**
     * Defines the form.
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('caseainame', 'mod_caseai'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'caseconfig', get_string('caseconfig', 'mod_caseai'));
        $mform->addElement('textarea', 'scenario', get_string('scenario', 'mod_caseai'), ['rows' => 8, 'cols' => 90]);
        $mform->setType('scenario', PARAM_RAW);
        $mform->addRule('scenario', null, 'required', null, 'client');

        $mform->addElement('textarea', 'studentrole', get_string('studentrole', 'mod_caseai'), ['rows' => 4, 'cols' => 90]);
        $mform->setType('studentrole', PARAM_RAW);
        $mform->addElement('textarea', 'charactersjson', get_string('charactersjson', 'mod_caseai'), ['rows' => 6, 'cols' => 90]);
        $mform->setType('charactersjson', PARAM_RAW);
        $mform->setDefault('charactersjson', '[]');
        $mform->addElement('textarea', 'factsjson', get_string('factsjson', 'mod_caseai'), ['rows' => 6, 'cols' => 90]);
        $mform->setType('factsjson', PARAM_RAW);
        $mform->setDefault('factsjson', '[]');
        $mform->addElement('textarea', 'immutablefactsjson',
            get_string('immutablefactsjson', 'mod_caseai'), ['rows' => 6, 'cols' => 90]);
        $mform->setType('immutablefactsjson', PARAM_RAW);
        $mform->setDefault('immutablefactsjson', '[]');
        $mform->addElement('textarea', 'objectives', get_string('objectives', 'mod_caseai'), ['rows' => 5, 'cols' => 90]);
        $mform->setType('objectives', PARAM_RAW);

        $mform->addElement('header', 'stateconfig', get_string('stateconfig', 'mod_caseai'));
        $mform->addElement('static', 'variableshelp', '', get_string('variablesjson_help', 'mod_caseai'));
        $mform->addElement('textarea', 'variablesjson', get_string('variablesjson', 'mod_caseai'), ['rows' => 12, 'cols' => 90]);
        $mform->setType('variablesjson', PARAM_RAW);
        $mform->setDefault('variablesjson',
            "{\n  \"status\": {\"type\": \"enum\", \"initial\": \"open\", \"values\": [\"open\", \"closed\"], \"mutable\": true}\n}");
        $mform->addElement('textarea', 'statesjson', get_string('statesjson', 'mod_caseai'), ['rows' => 5, 'cols' => 90]);
        $mform->setType('statesjson', PARAM_RAW);
        $mform->setDefault('statesjson', '[]');
        $mform->addElement('textarea', 'eventsjson', get_string('eventsjson', 'mod_caseai'), ['rows' => 6, 'cols' => 90]);
        $mform->setType('eventsjson', PARAM_RAW);
        $mform->setDefault('eventsjson', '[]');
        $mform->addElement('textarea', 'endcriteriajson', get_string('endcriteriajson', 'mod_caseai'), ['rows' => 7, 'cols' => 90]);
        $mform->setType('endcriteriajson', PARAM_RAW);
        $mform->setDefault('endcriteriajson', '[]');
        $mform->addElement('text', 'maxrounds', get_string('maxrounds', 'mod_caseai'));
        $mform->setType('maxrounds', PARAM_INT);
        $mform->setDefault('maxrounds', 10);
        $mform->addRule('maxrounds', null, 'required', null, 'client');

        $mform->addElement('header', 'assessment', get_string('assessment', 'mod_caseai'));
        $mform->addElement('editor', 'rubric_editor', get_string('rubric', 'mod_caseai'), null, ['maxfiles' => 0]);
        $mform->setType('rubric_editor', PARAM_RAW);
        $mform->addElement('text', 'grade', get_string('maximumgrade', 'mod_caseai'));
        $mform->setType('grade', PARAM_FLOAT);
        $mform->setDefault('grade', 0);
        $mform->addElement('static', 'gradingnote', '', get_string('gradingnote', 'mod_caseai'));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Loads editor fields.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        if (isset($defaultvalues['rubric'])) {
            $defaultvalues['rubric_editor'] = [
                'text' => $defaultvalues['rubric'],
                'format' => $defaultvalues['rubricformat'] ?? FORMAT_HTML,
            ];
        }
    }

    /**
     * Normalises editor fields before persistence.
     *
     * @param stdClass $data
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (isset($data->rubric_editor)) {
            $data->rubric = $data->rubric_editor['text'];
            $data->rubricformat = $data->rubric_editor['format'];
        }
    }

    /**
     * Adds custom completion rule.
     *
     * @return array
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $mform->addElement('checkbox', 'completionattempt', '', get_string('completionattempt', 'mod_caseai'));
        return ['completionattempt'];
    }

    /**
     * Checks whether custom completion rule is enabled.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionattempt']);
    }

    /**
     * Validates JSON configuration and limits.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $fields = [
            'charactersjson',
            'factsjson',
            'immutablefactsjson',
            'variablesjson',
            'statesjson',
            'eventsjson',
            'endcriteriajson',
        ];
        foreach ($fields as $field) {
            try {
                json_helper::decode($data[$field] ?? '', $field);
            } catch (moodle_exception $e) {
                $errors[$field] = $e->getMessage();
            }
        }
        if ((int)($data['maxrounds'] ?? 0) < 1 || (int)$data['maxrounds'] > 100) {
            $errors['maxrounds'] = get_string('error:maxrounds', 'mod_caseai');
        }
        if ((float)($data['grade'] ?? 0) < 0 || (float)$data['grade'] > 1000) {
            $errors['grade'] = get_string('error:grade', 'mod_caseai');
        }
        try {
            state_machine::validate_variable_definitions(json_helper::decode($data['variablesjson'] ?? '{}', 'variablesjson'));
        } catch (moodle_exception $e) {
            $errors['variablesjson'] = $e->getMessage();
        }
        return $errors;
    }
}

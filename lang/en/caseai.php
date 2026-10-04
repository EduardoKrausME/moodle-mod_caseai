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
 * caseai.php
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aisummary'] = 'AI-assisted summary';
$string['assessment'] = 'Assessment';
$string['attempt'] = 'Attempt';
$string['attemptalreadyfinished'] = 'This attempt is already finished.';
$string['attemptstatus'] = 'Status: {$a}';
$string['caseai:addinstance'] = 'Add a new adaptive case study';
$string['caseai:attempt'] = 'Attempt adaptive case study';
$string['caseai:grade'] = 'Manually grade case study attempts';
$string['caseai:view'] = 'View adaptive case study';
$string['caseai:viewreports'] = 'View case study reports';
$string['caseainame'] = 'Activity name';
$string['caseconfig'] = 'Case configuration';
$string['charactersjson'] = 'Characters (JSON)';
$string['completionattempt'] = 'Require a completed attempt';
$string['completionattempt_desc'] = 'Student must reach a deterministic ending condition or the configured maximum number of rounds.';
$string['consequence'] = 'Presented consequence';
$string['continueattempt'] = 'Continue case';
$string['decision'] = 'Decision';
$string['endcriteriajson'] = 'Deterministic end criteria (JSON)';
$string['error:airesponsetoolong'] = 'The AI response exceeded the allowed size. The attempt state was not changed.';
$string['error:attemptfinished'] = 'This attempt has already finished.';
$string['error:attemptlocked'] = 'This attempt is currently being updated by another request. Reload the page and try again.';
$string['error:decisionlength'] = 'The decision must contain between 1 and 12000 characters.';
$string['error:enumvaluesrequired'] = 'Enum variable {$a} must define a values array.';
$string['error:grade'] = 'Maximum grade must be between 0 and 1000.';
$string['error:gradingdisabled'] = 'Manual grading is disabled for this activity.';
$string['error:initialrequired'] = 'State variable {$a} must define an initial value.';
$string['error:invalidgrade'] = 'The grade is outside the configured range.';
$string['error:invalidjson'] = 'The field {$a} contains invalid JSON.';
$string['error:invalidstatevalue'] = 'Invalid value for state variable: {$a}';
$string['error:invalidvariabledefinition'] = 'Invalid definition for state variable: {$a}';
$string['error:invalidvariablename'] = 'Invalid state variable name: {$a}';
$string['error:invalidvariabletype'] = 'Unsupported type for state variable: {$a}';
$string['error:jsonmustbearray'] = 'The JSON field {$a} must decode to an array or object.';
$string['error:malformedairesponse'] = 'The AI returned malformed or incomplete JSON. The attempt state was not changed.';
$string['error:maxrounds'] = 'Maximum rounds must be between 1 and 100.';
$string['error:maxroundsreached'] = 'The configured maximum number of rounds has been reached.';
$string['error:ratelimit'] = 'Too many case simulation requests in a short period. Try again after the current window expires.';
$string['error:ratelock'] = 'Could not safely acquire the request rate-limit lock. Try again.';
$string['error:staleattempt'] = 'This page is stale or the decision was already submitted. Reload the attempt before submitting again.';
$string['error:summaryfailed'] = 'The AI-assisted summary could not be generated. Existing attempt data was not changed.';
$string['eventsjson'] = 'Possible events (JSON)';
$string['factsjson'] = 'Facts that are true in the case (JSON)';
$string['firstdecisionprompt'] = 'What do you decide to do?';
$string['generatesummary'] = 'Generate/update summary';
$string['gradingnote'] = 'Grades are never generated automatically. When a maximum grade is greater than zero, a teacher must review the attempt and enter the grade manually.';
$string['immutablefactsjson'] = 'Facts that must never change (JSON)';
$string['manualgrade'] = 'Human-reviewed grade';
$string['maxrounds'] = 'Maximum rounds';
$string['modulename'] = 'Adaptive case study';
$string['modulenameplural'] = 'Adaptive case studies';
$string['nocaseais'] = 'There are no adaptive case studies in this course.';
$string['objectives'] = 'Learning objectives';
$string['pluginadministration'] = 'Adaptive case study administration';
$string['pluginname'] = 'Adaptive case study';
$string['privacy:metadata:attempts'] = 'Stores each student attempt and its deterministic state.';
$string['privacy:metadata:attempts:currentstate'] = 'The deterministic current state of the case.';
$string['privacy:metadata:attempts:grade'] = 'The manually reviewed grade, when grading is enabled.';
$string['privacy:metadata:attempts:gradedby'] = 'The user who manually graded the attempt.';
$string['privacy:metadata:attempts:summary'] = 'An optional AI-assisted summary generated for teacher review.';
$string['privacy:metadata:attempts:timecreated'] = 'When the attempt was created.';
$string['privacy:metadata:attempts:timemodified'] = 'When the attempt was last modified.';
$string['privacy:metadata:attempts:userid'] = 'The user who owns the attempt.';
$string['privacy:metadata:bridge'] = 'Case simulation content is sent through local_ai_bridge to the tenant-configured provider.';
$string['privacy:metadata:bridge:decision'] = 'The student decision is sent to generate the next case turn.';
$string['privacy:metadata:bridge:state'] = 'The current deterministic case state and teacher-defined scenario configuration are sent as context.';
$string['privacy:metadata:bridge:userid'] = 'The Moodle user ID is used by the bridge for tenant routing, permissions and usage accounting.';
$string['privacy:metadata:rate'] = 'Stores persistent counters used to rate-limit simulation requests.';
$string['privacy:metadata:rate:requestcount'] = 'The number of requests made in the current rate-limit window.';
$string['privacy:metadata:rate:userid'] = 'The user whose requests are being counted.';
$string['privacy:metadata:rate:windowstart'] = 'The start of the current rate-limit window.';
$string['privacy:metadata:rounds'] = 'Stores decisions, narrative consequences and deterministic states for each round.';
$string['privacy:metadata:rounds:decisiontext'] = 'The decision entered by the student.';
$string['privacy:metadata:rounds:evidence'] = 'Evidence extracted for later human review.';
$string['privacy:metadata:rounds:narrative'] = 'The AI-generated narrative consequence.';
$string['privacy:metadata:rounds:state'] = 'The deterministic state before or after a round.';
$string['privacy:metadata:rounds:timecreated'] = 'When the round was created.';
$string['ratelimitcount'] = 'Requests per window';
$string['ratelimitcount_desc'] = 'Maximum simulation requests a user can submit per activity during the configured rate-limit window.';
$string['ratelimitwindow'] = 'Rate-limit window (seconds)';
$string['ratelimitwindow_desc'] = 'Length of the persistent rate-limit window. Minimum effective value is 60 seconds.';
$string['rejectedchanges'] = 'Rejected AI state changes';
$string['report'] = 'Case path report';
$string['roundlabel'] = 'Round {$a}';
$string['rounds'] = 'Rounds';
$string['roundxofy'] = 'Round {$a->current} of up to {$a->max}';
$string['rubric'] = 'Optional rubric';
$string['savegrade'] = 'Save grade';
$string['scenario'] = 'Initial scenario';
$string['startattempt'] = 'Start case';
$string['stateafter'] = 'State after';
$string['statebefore'] = 'State before';
$string['stateconfig'] = 'Deterministic state';
$string['statesjson'] = 'Possible states (JSON)';
$string['studentrole'] = 'Student role';
$string['submitdecision'] = 'Submit decision';
$string['variablesjson'] = 'State variable definitions (JSON)';
$string['variablesjson_help'] = 'Each variable needs type, initial and mutable. Supported types: integer, number, boolean, string, enum. Numeric variables may define min/max and enum variables must define values.';
$string['viewreport'] = 'View teacher report';
$string['yourdecision'] = 'Student decision';

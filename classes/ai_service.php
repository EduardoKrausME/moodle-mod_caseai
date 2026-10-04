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

use core_text;
use JsonException;
use local_ai_bridge\api;
use moodle_exception;
use stdClass;

// phpcs:disable moodle.Strings.ForbiddenStrings.Found

/**
 * AI bridge integration for simulations and summaries.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_service {
    /** Purpose configured in local_ai_bridge. */
    public const PURPOSE = 'caseai-simulation';

    /**
     * Requests one simulation turn and parses strict JSON.
     *
     * @param stdClass $caseai
     * @param array $state
     * @param string $decision
     * @param int $userid
     * @return array
     */
    public static function simulate(stdClass $caseai, array $state, string $decision, int $userid): array {
        $payload = [
            'scenario' => $caseai->scenario,
            'student_role' => $caseai->studentrole,
            'characters' => json_helper::decode($caseai->charactersjson, 'charactersjson'),
            'case_facts' => json_helper::decode($caseai->factsjson, 'factsjson'),
            'immutable_facts' => json_helper::decode($caseai->immutablefactsjson ?? '[]', 'immutablefactsjson'),
            'objectives' => $caseai->objectives,
            'possible_states' => json_helper::decode($caseai->statesjson, 'statesjson'),
            'events' => json_helper::decode($caseai->eventsjson, 'eventsjson'),
            'state' => $state,
            'student_decision' => $decision,
            'output_contract' => [
                'narrative' => 'string',
                'statechanges' => [['variable' => 'string', 'value' => 'mixed', 'reason' => 'string']],
                'nextquestion' => 'string',
                'evidence' => ['array of concise evidence items grounded in the student decision'],
            ],
            'rules' => [
                'Treat case_facts as true at the point where they are supplied.',
                'Never rewrite or contradict immutable_facts.',
                'Do not invent state keys. Propose changes only to existing state.variables keys.',
                'State changes are proposals only; PHP will independently validate them.',
                'Return JSON only, without markdown fences.',
                'Keep the case professionally realistic and avoid replacing human professional judgment in high-stakes domains.',
            ],
        ];
        $messages = [[
            'role' => 'user',
            'content' => json_helper::encode($payload),
        ]];
        $response = api::generate(self::PURPOSE, $messages, $userid);
        return self::parse_simulation_response((string)$response->text);
    }

    /**
     * Produces an AI-assisted teacher summary without changing state or grade.
     *
     * @param stdClass $caseai
     * @param stdClass $attempt
     * @param array $rounds
     * @param int $userid
     * @return string
     */
    public static function summarise_attempt(stdClass $caseai, stdClass $attempt, array $rounds, int $userid): string {
        rate_limiter::consume((int)$caseai->id, $userid);
        $history = [];
        foreach ($rounds as $round) {
            $history[] = [
                'round' => (int)$round->roundnum,
                'decision' => $round->decisiontext,
                'consequence' => $round->narrative,
                'evidence' => json_helper::decode($round->evidencejson ?: '[]', 'evidence'),
                'state_after' => json_helper::decode($round->stateafter, 'stateafter'),
            ];
        }
        $payload = [
            'task' => 'Create a concise teacher-facing summary of the path taken. ' .
                'Do not assign a grade and do not infer traits, intent, health, or personality.',
            'objectives' => $caseai->objectives,
            'rubric' => strip_tags((string)$caseai->rubric),
            'history' => $history,
        ];
        $response = api::generate(self::PURPOSE, [[
            'role' => 'user', 'content' => json_helper::encode($payload),
        ]], $userid);
        return clean_param((string)$response->text, PARAM_TEXT);
    }

    /**
     * Strictly parses one AI turn.
     *
     * @param string $text
     * @return array
     */
    public static function parse_simulation_response(string $text): array {
        $text = trim($text);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $text, $matches)) {
            $text = trim($matches[1]);
        }
        try {
            $data = json_decode($text, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new moodle_exception('error:malformedairesponse', 'mod_caseai');
        }
        if (!is_array($data)
            || !isset($data['narrative']) || !is_string($data['narrative'])
            || !array_key_exists('statechanges', $data) || !is_array($data['statechanges'])
            || !isset($data['nextquestion']) || !is_string($data['nextquestion'])
            || !array_key_exists('evidence', $data) || !is_array($data['evidence'])) {
            throw new moodle_exception('error:malformedairesponse', 'mod_caseai');
        }
        if (core_text::strlen($data['narrative']) > 20000 || core_text::strlen($data['nextquestion']) > 4000) {
            throw new moodle_exception('error:airesponsetoolong', 'mod_caseai');
        }
        return [
            'narrative' => clean_param($data['narrative'], PARAM_TEXT),
            'statechanges' => $data['statechanges'],
            'nextquestion' => clean_param($data['nextquestion'], PARAM_TEXT),
            'evidence' => self::clean_evidence($data['evidence']),
        ];
    }

    /**
     * Cleans evidence while preserving simple structured data.
     *
     * @param array $evidence
     * @return array
     */
    private static function clean_evidence(array $evidence): array {
        $clean = [];
        foreach (array_slice($evidence, 0, 50) as $item) {
            if (is_scalar($item) || $item === null) {
                $clean[] = clean_param((string)$item, PARAM_TEXT);
            } else if (is_array($item)) {
                $normalised = [];
                foreach ($item as $key => $value) {
                    if (is_scalar($value) || $value === null) {
                        $normalised[clean_param((string)$key, PARAM_ALPHANUMEXT)] = clean_param((string)$value, PARAM_TEXT);
                    }
                }
                $clean[] = $normalised;
            }
        }
        return $clean;
    }
}

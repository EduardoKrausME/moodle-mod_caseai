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
use moodle_exception;

/**
 * Deterministic case state machine.
 *
 * The AI may propose changes, but this class owns validation and application.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class state_machine {
    /**
     * Validates teacher-defined variable schema.
     *
     * @param array $definitions
     * @return void
     */
    public static function validate_variable_definitions(array $definitions): void {
        foreach ($definitions as $name => $definition) {
            if (!is_string($name) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{0,63}$/', $name)) {
                throw new moodle_exception('error:invalidvariablename', 'mod_caseai', '', (string)$name);
            }
            if (!is_array($definition) || empty($definition['type'])) {
                throw new moodle_exception('error:invalidvariabledefinition', 'mod_caseai', '', $name);
            }
            $type = (string)$definition['type'];
            if (!in_array($type, ['integer', 'number', 'boolean', 'string', 'enum'], true)) {
                throw new moodle_exception('error:invalidvariabletype', 'mod_caseai', '', $name);
            }
            if ($type === 'enum' && (empty($definition['values']) || !is_array($definition['values']))) {
                throw new moodle_exception('error:enumvaluesrequired', 'mod_caseai', '', $name);
            }
            if (!array_key_exists('initial', $definition)) {
                throw new moodle_exception('error:initialrequired', 'mod_caseai', '', $name);
            }
            self::validate_value($name, $definition['initial'], $definition);
        }
    }

    /**
     * Creates the initial state. Structural facts are immutable and kept outside variables.
     *
     * @param array $definitions
     * @param array $facts
     * @return array
     */
    public static function initial_state(array $definitions, array $facts): array {
        self::validate_variable_definitions($definitions);
        $variables = [];
        foreach ($definitions as $name => $definition) {
            $variables[$name] = $definition['initial'];
        }
        return [
            'round' => 0,
            'variables' => $variables,
            'facts' => array_values($facts),
            'decisions' => [],
        ];
    }

    /**
     * Applies only valid changes to mutable variables.
     *
     * @param array $state
     * @param array $definitions
     * @param array $proposed
     * @param string $decision
     * @return array{state: array, applied: array, rejected: array}
     */
    public static function apply_proposed_changes(array $state, array $definitions, array $proposed, string $decision): array {
        $next = $state;
        $applied = [];
        $rejected = [];
        foreach ($proposed as $change) {
            if (!is_array($change) || !array_key_exists('variable', $change) || !array_key_exists('value', $change)) {
                $rejected[] = ['change' => $change, 'reason' => 'malformed_change'];
                continue;
            }
            $name = (string)$change['variable'];
            if (!array_key_exists($name, $definitions)) {
                $rejected[] = ['change' => $change, 'reason' => 'unknown_variable'];
                continue;
            }
            $definition = $definitions[$name];
            if (empty($definition['mutable'])) {
                $rejected[] = ['change' => $change, 'reason' => 'immutable_variable'];
                continue;
            }
            try {
                self::validate_value($name, $change['value'], $definition);
            } catch (moodle_exception $e) {
                $rejected[] = ['change' => $change, 'reason' => 'invalid_value'];
                continue;
            }
            $next['variables'][$name] = $change['value'];
            $applied[] = [
                'variable' => $name,
                'value' => $change['value'],
                'reason' => isset($change['reason']) ? clean_param((string)$change['reason'], PARAM_TEXT) : '',
            ];
        }
        $next['round'] = ((int)($state['round'] ?? 0)) + 1;
        $next['facts'] = $state['facts'] ?? [];
        $next['decisions'] = $state['decisions'] ?? [];
        $next['decisions'][] = clean_param($decision, PARAM_TEXT);
        return ['state' => $next, 'applied' => $applied, 'rejected' => $rejected];
    }

    /**
     * Evaluates configured deterministic end criteria.
     *
     * Criteria are ORed; each criterion is {variable, operator, value}.
     *
     * @param array $state
     * @param array $criteria
     * @return bool
     */
    public static function should_end(array $state, array $criteria): bool {
        foreach ($criteria as $criterion) {
            if (!is_array($criterion) ||
                !isset($criterion['variable'], $criterion['operator']) ||
                !array_key_exists('value', $criterion)) {
                continue;
            }
            $name = (string)$criterion['variable'];
            if (!array_key_exists($name, $state['variables'] ?? [])) {
                continue;
            }
            $actual = $state['variables'][$name];
            $expected = $criterion['value'];
            $result = match ((string)$criterion['operator']) {
                '==' => $actual == $expected,
                '===' => $actual === $expected,
                '!=' => $actual != $expected,
                '>' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
                '>=' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
                '<' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
                '<=' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
                'in' => is_array($expected) && in_array($actual, $expected, true),
                default => false,
            };
            if ($result) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validates one value against its definition.
     *
     * @param string $name
     * @param mixed $value
     * @param array $definition
     * @return void
     */
    private static function validate_value(string $name, $value, array $definition): void {
        $type = (string)$definition['type'];
        $validtype = match ($type) {
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'string' => is_string($value),
            'enum' => in_array($value, $definition['values'] ?? [], true),
            default => false,
        };
        if (!$validtype) {
            throw new moodle_exception('error:invalidstatevalue', 'mod_caseai', '', $name);
        }
        if (($type === 'integer' || $type === 'number') && isset($definition['min']) && $value < $definition['min']) {
            throw new moodle_exception('error:invalidstatevalue', 'mod_caseai', '', $name);
        }
        if (($type === 'integer' || $type === 'number') && isset($definition['max']) && $value > $definition['max']) {
            throw new moodle_exception('error:invalidstatevalue', 'mod_caseai', '', $name);
        }
        if ($type === 'string' && isset($definition['maxlength']) && core_text::strlen($value) > (int)$definition['maxlength']) {
            throw new moodle_exception('error:invalidstatevalue', 'mod_caseai', '', $name);
        }
    }
}

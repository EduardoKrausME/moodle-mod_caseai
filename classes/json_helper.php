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

use JsonException;
use moodle_exception;

/**
 * Strict JSON helpers.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class json_helper {
    /**
     * Decodes JSON as an associative array and throws a Moodle exception on errors.
     *
     * @param string|null $json
     * @param string $fieldname
     * @return array
     */
    public static function decode(?string $json, string $fieldname = 'json'): array {
        $json = trim((string)$json);
        if ($json === '') {
            return [];
        }
        try {
            $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new moodle_exception('error:invalidjson', 'mod_caseai', '', $fieldname);
        }
        if (!is_array($decoded)) {
            throw new moodle_exception('error:jsonmustbearray', 'mod_caseai', '', $fieldname);
        }
        return $decoded;
    }

    /**
     * Encodes data predictably for persistence.
     *
     * @param mixed $data
     * @return string
     */
    public static function encode($data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}

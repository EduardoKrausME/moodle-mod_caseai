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

use core\lock\lock_config;
use moodle_exception;

/**
 * Persistent rate limiter protected by a Moodle lock.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limiter {
    /**
     * Consumes one request from the current user's activity window.
     *
     * @param int $caseaiid
     * @param int $userid
     * @return void
     */
    public static function consume(int $caseaiid, int $userid): void {
        global $DB;
        $configuredlimit = get_config('mod_caseai', 'ratelimitcount');
        $configuredwindow = get_config('mod_caseai', 'ratelimitwindow');
        $limit = max(1, $configuredlimit === false ? 10 : (int)$configuredlimit);
        $window = max(60, $configuredwindow === false ? 300 : (int)$configuredwindow);
        $factory = lock_config::get_lock_factory('mod_caseai');
        $lock = $factory->get_lock("rate:$caseaiid:$userid", 5);
        if (!$lock) {
            throw new moodle_exception('error:ratelock', 'mod_caseai');
        }
        try {
            $now = time();
            $record = $DB->get_record('caseai_rate', ['caseaiid' => $caseaiid, 'userid' => $userid]);
            if (!$record) {
                $DB->insert_record('caseai_rate', (object)[
                    'caseaiid' => $caseaiid,
                    'userid' => $userid,
                    'windowstart' => $now,
                    'requestcount' => 1,
                ]);
                return;
            }
            if (($now - (int)$record->windowstart) >= $window) {
                $record->windowstart = $now;
                $record->requestcount = 1;
                $DB->update_record('caseai_rate', $record);
                return;
            }
            if ((int)$record->requestcount >= $limit) {
                throw new moodle_exception('error:ratelimit', 'mod_caseai');
            }
            $record->requestcount = ((int)$record->requestcount) + 1;
            $DB->update_record('caseai_rate', $record);
        } finally {
            $lock->release();
        }
    }
}

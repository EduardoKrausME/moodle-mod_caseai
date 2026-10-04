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
 * Site settings for mod_caseai.
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_caseai/ratelimitcount',
        get_string('ratelimitcount', 'mod_caseai'),
        get_string('ratelimitcount_desc', 'mod_caseai'),
        10,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'mod_caseai/ratelimitwindow',
        get_string('ratelimitwindow', 'mod_caseai'),
        get_string('ratelimitwindow_desc', 'mod_caseai'),
        300,
        PARAM_INT
    ));
}

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
 * Plugin events and observers
 *
 * @package    mod_programcourse
 * @copyright  2024 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_updated',
        'callback' => 'mod_programcourse_observer::mod_programcourse_name_updated',
    ],
    [
        'eventname' => '\core\event\course_completed',
        'callback' => 'mod_programcourse_observer::mod_programcourse_completed',
    ],
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => 'mod_programcourse_observer::mod_programcourse_cm_created',
    ],
    [
        'eventname' => '\mod_programcourse\event\programcourse_users_enrolled',
        'callback' => 'mod_programcourse_observer::mod_programcourse_update_completions',
    ],
];

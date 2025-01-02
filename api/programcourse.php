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
 * Class programcourse_api
 *
 * @package    mod_programcourse
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Nabil Hamdi <nabil.hamdi@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_programcourse;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/programcourse/classes/database_interface.php');
require_once($CFG->libdir . '/licenselib.php');

/**
 * programcourse API
 *
 * @package    mod_programcourse
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Nabil Hamdi <nabil.hamdi@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class programcourse_api {


        /**
         * Checks if the current user is eligible for a program course.
         *
         * This function determines if the current user is eligible for a program course
         * by checking if the course is a program course and if the user is enrolled in it.
         *
         * @global object $USER The global user object.
         * @param int $currentCourseId The ID of the course of the session .
         * @param int $previouscourseId The ID of the previous course.
         * @return bool True if the user is eligible for the program course, false otherwise.
         */
        public static function is_eligible_for_programcourse($currentCourseId, $previouscourseId): bool  {

            global $USER;

            $dbInterface = \mod_programcourse\database_interface::get_instance();

            return $dbInterface->is_programcourse_by_courseid($currentCourseId) && $dbInterface->is_user_enrolled_in_programcourse($USER->id, $currentCourseId, $previouscourseId);
        }

}
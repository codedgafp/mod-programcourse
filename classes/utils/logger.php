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
 * logger.
 *
 * @package    mod_programcourse
 * @copyright  CGI 2024 (https://www.cgi.com/)
 * @author     Picard Quentin <quentin.picard@cgi.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_programcourse;

use request_exception;

require_once($CFG->dirroot . '/mod/programcourse/classes/exception/request_exception.php');

defined('MOODLE_INTERNAL') || die();

class logger
{
    /**
     * log and throw error
     *
     * @param string $origin_flag
     * @param string $message
     * @param string $log_message
     * @param string $log_stacktrace
     * @param int $code
     * @return void
     * @throws request_exception
     */
    public static function error(string $origin_flag, string $message, string $log_message = "",
                                 string $log_stacktrace = "", int $code = 500): void
    {
        error_log("$origin_flag $message $log_message $log_stacktrace");
        throw new request_exception($message, $code);
    }
}


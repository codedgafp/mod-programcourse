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
 * Ajax request dispatcher
 *
 * @package    mod_programcourse
 * @copyright  CGI 2024 (https://www.cgi.com/)
 * @author     Picard Quentin <quentin.picard@cgi.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_programcourse\front_controller;

require_once(__DIR__ . '/../../../config.php');
require_login();
require_once($CFG->dirroot . '/mod/programcourse/classes/controllers/front_controller.php');
require_once($CFG->dirroot . '/mod/programcourse/classes/exception/request_exception.php');

// Redirection to the login page if the user does not login.
if (!isloggedin()) {
    redirect($CFG->wwwroot . '/login/index.php');
}
try {
    // Call front controller.
    $frontcontroller = new front_controller('programcourse', 'mod_programcourse\\');

    // Call the controller method, result is json.
    echo json_encode($frontcontroller->run());
} catch (Exception|request_exception $e) {
    http_response_code(property_exists($e, 'requestcode') ? $e->getRequestcode() : 500);

    echo json_encode([
        'message' => $e->getMessage(),
        'success' => FALSE,
    ]);
}


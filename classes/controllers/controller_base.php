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
 * Abstract controller class
 * A page is a course section with some other attributes
 *
 * @package    mod_programcourse
 * @copyright  CGI 2024 (https://www.cgi.com/)
 * @author     Picard Quentin <quentin.picard@cgi.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_programcourse;

require_once($CFG->dirroot . '/mod/programcourse/classes/utils/logger.php');

use moodle_exception;

/**
 * Class controller_base
 *
 * @package local_enrollment_service
 */
abstract class controller_base
{

    /**
     * @var array
     */
    protected array $params = [];

    /**
     * controller_base constructor.
     *
     * @param $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * @throws moodle_exception
     */
    public function get_required_param($paramname, $type = null, $default = null)
    {
        if (!isset($this->params[$paramname]) && !$default)
            logger::error("[mod_programcourse@controller_base::get_required_param]",
                "'$paramname' missing");
        return self::get_param($paramname, $type, $default);
    }

    /**
     * Get request param
     *
     * @param string $paramname
     * @param string|null $type default null if the type is not important
     * @param mixed $default default value if the param does not exist
     * @return mixed value of the param (or default value)
     * @throws moodle_exception
     */
    public function get_param(string $paramname, ?string $type = null, $default = false)
    {

        if (isset($this->params[$paramname])) {

            /** @var mixed $class */
            $param = $this->params[$paramname];

            if (!empty($type)) {
                switch ($type) {
                    case PARAM_INT :
                        if (!is_integer($param) && !ctype_digit($param)) {
                            logger::error("[mod_programcourse@controller_base::get_param]",
                                "'$paramname' must be an integer");
                        }
                        $param = (int)$param;
                        break;
                    // Add cases for new types here.
                    default :
                        is_array($param) ? clean_param_array($param, $type, true) : clean_param($param, $type);
                        break;
                }
            }
            return $param;
        }

        return $default;

    }

    /**
     * Success message former
     *
     * @param array $additional
     * @param string|null $message
     * @return array
     */
    public function success(array $additional = [], ?string $message = null): array
    {
        return array_merge(
            ['success' => true],
            $message ? ['message' => $message] : [],
            $additional
        );
    }

    /**
     * Error message former
     *
     * @param string|null $message
     * @return array
     */
    public function error(?string $message = null): array
    {
        return array_merge(
            ['success' => false],
            $message ? ['message' => $message] : []
        );
    }
}

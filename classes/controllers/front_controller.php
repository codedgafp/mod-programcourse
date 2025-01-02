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
 * @package     mod_programcourse
 * @copyright  CGI 2024 (https://www.cgi.com/)
 * @author     Picard Quentin <quentin.picard@cgi.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace  mod_programcourse;

require_once($CFG->dirroot . '/mod/programcourse/classes/utils/logger.php');

use coding_exception;
use dml_exception;
use Exception;
use moodle_exception;
use ReflectionClass;
use ReflectionException;
use request_exception;
use webservice;
use webservice_access_exception;
use stdClass;

require_once($CFG->dirroot . '/webservice/lib.php');

/**
 * Class front_controller
 */
class front_controller
{

    /**
     * @var array|null
     */
    protected ?array $params = [];

    /**
     * @var string
     */
    protected string $controller;

    /**
     * @var string
     */
    protected string $action;

    /**
     * @var string namespace of the plugin using the front controller
     */
    protected string $namespace;

    /**
     * @var string plugin using the front_controller
     */
    protected string $plugin;

    /**
     * @var string plugin type using the front_controller
     */
    protected string $plugintype;

    /**
     * front_controller constructor.
     *
     * @param string $plugin local plugin using the front_controller ex : user, session...
     * @param string $namespace namespace of the plugin using the front controller
     * @param array|null $options
     * @throws ReflectionException
     * @throws moodle_exception
     */
    public function __construct(string $plugin, string $namespace, ?array $options = null)
    {

        $this->namespace = $namespace;
        $this->plugin = $plugin;

        if (!empty($options)) {
            $this->params = $options;
        } else {
            $this->set_params();
        }

        $this->plugintype = $this->params['plugintype'] ?? "mod";

        if (isset($this->params['controller'])) {
            $this->set_controller($this->params['controller']);
        }

        if (isset($this->params['action'])) {
            $this->set_action($this->params['action']);
        }
    }

    /**
     * Set controller
     *
     * @param string $controller
     * @return $this
     * @throws moodle_exception
     */
    public function set_controller(string $controller): front_controller
    {
        global $CFG;

        /** @var string $controllerurl */
        $controllerurl = $CFG->dirroot . '/' . $this->plugintype . '/' . $this->plugin . '/classes/controllers/' . $controller .
            '_controller.php';

        if (!file_exists($controllerurl)) {
            logger::error("[mod_programcourse@front_controller::set_controller]",
                "Controller not found $controller not found");
        }

        require_once($controllerurl);

        /** @var string $controller */
        $controller = strtolower($controller) . "_controller";

        if (!class_exists($this->namespace . $controller)) {
            logger::error("[mod_programcourse@front_controller::set_controller]",
                "'$controller' does not exist");
        }

        $this->controller = $controller;

        return $this;
    }

    /**
     * Set action to call
     *
     * @param string $action
     * @return $this
     * @throws ReflectionException
     */
    public function set_action(string $action): front_controller
    {
        /** @var ReflectionClass $reflector */
        $reflector = new ReflectionClass($this->namespace . $this->controller);

        if (!$reflector->hasMethod($action)) {
            logger::error("[mod_programcourse@front_controller::set_action]",
                "action '$action' of '$this->controller' does not exist");
        }

        $this->action = $action;

        return $this;

    }

    /**
     * Set params from $_GET and $_POST and Raw json
     */
    public function set_params()
    {
        /** @var array|false|null $get */
        $get = filter_input_array(INPUT_GET);

        /** @var array|false|null $post */
        $post = filter_input_array(INPUT_POST);

        // Get data from raw json
        /** @var string|false $jsonData */
        $jsonData = file_get_contents('php://input');

        /** @var array|null $json */
        $json = json_decode($jsonData, true);

        $this->params = array_merge((array)$get, (array)$post, (array)$json);
    }

    /**
     * Execute the controller action
     * @return array
     * @throws request_exception | webservice_access_exception | moodle_exception
     */
    public function run(): array | stdClass
    {
        /** @var string $class */
        $class = $this->namespace . $this->controller;

        /** @var controller_base $class */
        $controller = new $class($this->params);

        if (isset($this->params['token'])) {
            /** @var webservice $class */
            $webservice = new webservice();
            $webservice->authenticate_user($this->params['token']);
        }

        /** @var string $action */
        $action = $controller->get_param('action');
        if (!method_exists($controller, $action))
            logger::error("[mod_programcourse@front_controller::run]",
                "action '$action' of '$this->controller' does not exist");

        $callbackToReturn = call_user_func([$controller, $action]);

        return $callbackToReturn;
    }

}

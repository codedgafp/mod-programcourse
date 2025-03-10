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
 * Catalog controller
 *
 * @package    mod_programcourse
 * @copyright  2021 Edunao SAS (contact@edunao.com)
 * @author     adrien <adrien@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_programcourse;

use mod_programcourse\controller_base;
require_once($CFG->dirroot . '/mod/programcourse/api/programcourse.php');

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot . '/mod/programcourse/classes/controllers/controller_base.php');

class programcourse_controller extends controller_base {

    /**
     * Check if the user is eligible for the program course
     * 
     * @param int $currentCourseId
     * @param int|null $previouscourseId
     * @throws \moodle_exception
     */
   public function get_eligibility_for_programcourse() {
        global $SESSION;

        $currentCourseId = $this->get_param('currentCourseId', PARAM_INT,null);
        $programCourseId = $SESSION->program[$currentCourseId]["courseid"];

        if($currentCourseId != null ){
            $isEligible = programcourse_api::is_programcourse_by_courseid($currentCourseId);
            $returnId = $isEligible ? $programCourseId : null;
            return $this->success( ['message' => $isEligible,'redirectid' => $returnId] );
        }else {
            throw new \moodle_exception('invalidparams', 'Invalid params');
        }      
    }
    
}

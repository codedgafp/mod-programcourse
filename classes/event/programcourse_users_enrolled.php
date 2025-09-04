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

namespace mod_programcourse\event;

defined('MOODLE_INTERNAL') || die();

class programcourse_users_enrolled extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'enrol';
    }

    public static function create_from_instance($enrolprogramdata): self {
        return self::create([
            'context' => \context::instance_by_id($enrolprogramdata->contextid),
            'objectid' => $enrolprogramdata->instanceid,
            'other' => [
                'programcoursecourseid' => $enrolprogramdata->programcoursecourseid,
                'programcourseid' => $enrolprogramdata->programcourseid,
                'coursemodule' => $enrolprogramdata->coursemodule
            ]
        ]);
    }

    public function get_description(): string {
        return "Program enrol instance {$this->objectid} created for course {$this->contextinstanceid}.";
    }
}

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

namespace mod_programcourse;

/**
 * Database Interface.
 *
 * @package    mod_programcourse
 * @copyright  2024 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class database_interface {
    /**
     * @var \moodle_database
     */
    protected \moodle_database $db;

    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var \stdClass[]
     */
    protected static array $module = [];

    /**
     * Mod table.
     */
    const DEFAULT_MODULE_NAME = 'programcourse';

    /**
     * Constructor
     */
    public function __construct() {
        global $DB;

        $this->db = $DB;
    }

    /**
     * Create a singleton
     *
     * @return database_interface
     */
    public static function get_instance(): database_interface {

        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get program course module data.
     *
     * @return \stdClass
     * @throws \dml_exception
     */
    public function get_module_data(): \stdClass {
        // Create cache if not exist.
        if (!isset(self::$module[self::DEFAULT_MODULE_NAME])) {
            self::$module[self::DEFAULT_MODULE_NAME]
                = $this->db->get_record('modules', ['name' => self::DEFAULT_MODULE_NAME]);
        }

        // Return module data.
        return self::$module[self::DEFAULT_MODULE_NAME];
    }

    /**
     * Get program course instance.
     *
     * @param int $instanceid
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function get_course_module_by_instance(int $instanceid): bool|\stdClass {
        $module = $this->get_module_data();
        return $this->db->get_record('course_modules', ['instance' => $instanceid, 'module' => $module->id]);
    }

    /**
     * Get program course instance.
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public function get_course_modules_by_course_id(int $courseid): array {
        return $this->db->get_records_sql('
            SELECT cm.*
            FROM {course_modules} cm
            JOIN {modules} m ON cm.module = m.id
            JOIN {' . self::DEFAULT_MODULE_NAME . '} pc ON cm.instance = pc.id
            WHERE m.name = \'' . self::DEFAULT_MODULE_NAME . '\' AND pc.courseid = :courseid
        ', ['courseid' => $courseid]);
    }

    /**
     * Get enrol instance data.
     *
     * @param int $enrolid
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function get_enrol_instance_by_id(int $enrolid): bool|\stdClass {
        return $this->db->get_record('enrol', ['id' => $enrolid]);
    }

    /**
     * Get module information.
     *
     * @param int $id
     * @param string|null $modname
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function get_course_module_by_id(int $id, ?string $modname = null): bool|\stdClass {
        if (is_null($modname)) {
            $modname = self::DEFAULT_MODULE_NAME;
        }
        return $this->db->get_record($modname, ['id' => $id]);
    }

    /**
     * Delete module information.
     *
     * @param int $cmid
     * @param string|null $modname
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function delete_course_module_by_id(int $cmid, ?string $modname = null): bool|\stdClass {
        if (is_null($modname)) {
            $modname = self::DEFAULT_MODULE_NAME;
        }
        return $this->db->delete_records($modname, ['id' => $cmid]);
    }

    /**
     * Get courses not yet added to the course program.
     *
     * @return \stdClass[]
     * @throws \dml_exception
     */
    public function get_available_courses_for_program(): array {
        global $USER, $COURSE, $CFG;

        // Specific Magistère.
        $coursetocatalog = [];
        if (file_exists($CFG->dirroot . '/local/catalogue_manager/lib.php')) {
            // Get course to open catalog.
            $coursetocatalog = $this->db->get_records_sql('
                SELECT DISTINCT cs.id
                FROM {course} cs
                JOIN {catalog_courses} cc ON cc.courseid = cs.id
                JOIN {catalog} c ON c.id = cc.catalogid
                WHERE c.status  = 1
            ');
        }

        // Get course when user is formateur.
        $editingteacher = $this->get_role_by_name('editingteacher');
        $editingteachercourses = $this->db->get_records_sql('
            SELECT DISTINCT c.id
            FROM {course} c
            JOIN {context} con ON con.instanceid = c.id
            JOIN {role_assignments} ra ON ra.contextid = con.id
            WHERE con.contextlevel = :contextlevel AND
                ra.roleid = :roleid AND
                ra.userid = :userid
        ', ['contextlevel' => CONTEXT_COURSE, 'roleid' => $editingteacher->id, 'userid' => $USER->id]);

        // Ignore self course.
        $courseecetpion[] = $COURSE->id;

        $possiblecoursemerge = array_merge(
            array_column($coursetocatalog, 'id'),
            array_column($editingteachercourses, 'id')
        );

        if (empty($possiblecoursemerge)) {
            return [];
        }

        $possiblecourse = implode(',', $possiblecoursemerge);

        $courseecetpion = implode(',', $courseecetpion);

        // Get course not yet added to the course program.
        return $this->db->get_records_sql('
            SELECT DISTINCT c.*
            FROM {course} c
            WHERE c.id IN (' . $possiblecourse . ') AND
                c.id NOT IN (' . $courseecetpion . ')
            ORDER BY c.fullname
        ');
    }

    /**
     * Add program course mod instance.
     *
     * @param \stdClass $instancedata
     * @return bool|int
     * @throws \dml_exception
     */
    public function add_programcourse_instance(\stdClass $instancedata): bool|int {
        return $this->db->insert_record(self::DEFAULT_MODULE_NAME, $instancedata);
    }

    /**
     * Update program course mod instance.
     *
     * @param \stdClass $instancedata
     * @return bool
     * @throws \dml_exception
     */
    public function update_programcourse_instance(\stdClass $instancedata): bool {
        return $this->db->update_record(self::DEFAULT_MODULE_NAME, $instancedata);
    }

    /**
     * Get program enrol instance link to course.
     *
     * @param int $courseid
     * @param int $linkedcourseid
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function get_enrol_instance_link_to_programcourse(int $courseid, int $linkedcourseid): false|\stdClass {
        return $this->db->get_record('enrol', [
            'enrol' => 'program',
            'customint1' => $courseid,
            'courseid' => $linkedcourseid,
        ]);
    }

    /**
     * Get course link to program course module.
     *
     * @param int $instanceid
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function get_course_link_to_programcourse(int $instanceid): false|\stdClass {
        return $this->db->get_record_sql('
            SELECT c.*
            FROM {course} c
            JOIN {' . self::DEFAULT_MODULE_NAME . '} p ON p.courseid = c.id
            WHERE p.id = ?
        ', [$instanceid], MUST_EXIST);
    }

    /**
     * Update program course module name information.
     *
     * @param int $instanceid
     * @param string $instancename
     * @return void
     * @throws \dml_exception
     */
    public function update_module_instance_name(int $instanceid, string $instancename): void {
        $instancedata = new \stdClass();
        $instancedata->id = $instanceid;
        $instancedata->name = $instancename;
        $instancedata->timemodified = time();

        $this->db->update_record(self::DEFAULT_MODULE_NAME, $instancedata);
    }

    /**
     * Get program course information.
     *
     * @param int $instanceid
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function get_programcourse_info(int $instanceid): false|\stdClass {
        $fieldsinfo = 'id, name, intro, introformat, courseid, completionall';
        return $this->db->get_record(
            self::DEFAULT_MODULE_NAME, ['id' => $instanceid], $fieldsinfo
        );
    }

    /**
     * Get all program course module link to this course.
     *
     * @param int $courseid
     * @return \stdClass[]
     * @throws \dml_exception
     */
    public function get_all_programcourse_link_to_course(int $courseid): array {
        return $this->db->get_records(self::DEFAULT_MODULE_NAME, ['courseid' => $courseid]);
    }

    /**
     * Update completion config.
     *
     * @param int $value
     * @return bool
     * @throws \dml_exception
     */
    public function update_completion_config(int $instanceid, int $value): bool {
        $instancedata = new \stdClass();
        $instancedata->id = $instanceid;
        $instancedata->completionall = $value;
        $instancedata->timemodified = time();

        return $this->db->update_record(self::DEFAULT_MODULE_NAME, $instancedata);
    }

    /**
     * Get role data by name
     *
     * @param string $rolename
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function get_role_by_name(string $rolename): false|\stdClass {
        return $this->db->get_record('role', ['shortname' => $rolename]);
    }
}

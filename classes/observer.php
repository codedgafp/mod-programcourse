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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/programcourse/classes/database_interface.php');
require_once($CFG->dirroot . '/lib/classes/event/course_updated.php');
require_once($CFG->dirroot . '/mod/programcourse/lib.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');
require_once("$CFG->libdir/completionlib.php");

/**
 * Plugin observers
 *
 * @package    mod_programcourse
 * @copyright  2024 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_programcourse_observer
{
    /**
     * Update mod_programcourse if a course link update this fullname.
     *
     * @param \core\event\course_updated $event
     * @return void
     * @throws Exception
     */
    public static function mod_programcourse_name_updated(\core\event\course_updated $event): void
    {
        $updatedfield = $event->other['updatedfields'];

        if (!isset($updatedfield['fullname'])) {
            return;
        }

        $courseid = $event->objectid;

        $dbi = \mod_programcourse\database_interface::get_instance();

        $allmoduleinfo = $dbi->get_all_programcourse_link_to_course($courseid);

        foreach ($allmoduleinfo as $moduleinfo) {
            $module = $dbi->get_course_module_by_instance($moduleinfo->id);
            $module->coursemodule = $module->id;
            \programcourse_update_instance($module);
            \course_modinfo::purge_course_module_cache($module->course, $module->id);
        }
    }

    /**
     * Update mod_programcourse to complet.
     *
     * @param \core\event\course_completed $event
     * @return void
     * @throws Exception
     */
    public static function mod_programcourse_completed(\core\event\course_completed $event): void
    {
        global $DB;

        $coursecompletion = $DB->get_record('course_completions', ['id' => $event->objectid]);
        $courseid = $coursecompletion->course;
        $dbi = \mod_programcourse\database_interface::get_instance();
        $coursemoduleinstances = $dbi->get_course_modules_by_course_id($courseid);
        foreach ($coursemoduleinstances as $coursemoduleinstance) {
            list($course, $cm) = get_course_and_cm_from_cmid($coursemoduleinstance->id);

            // Set up completion object and check it is enabled.
            $completion = new completion_info($course);
            if (!$completion->is_enabled()) {
                continue;
            }

            $completion->update_state($cm, COMPLETION_COMPLETE, $event->relateduserid);
        }
    }

    /**
     * Manager course module creation
     *
     * @param \core\event\course_module_created $event
     * @return void
     */
    public static function mod_programcourse_cm_created(\core\event\course_module_created $event): void
    {
        global $DB;

        // Check module type.
        if ($event->other['modulename'] !== 'programcourse') {
            return;
        }

        $coursemoduleid = $event->objectid;
        $coursemodule = get_coursemodule_from_id('programcourse', $coursemoduleid);
        $coursemoduleinstance = $coursemodule->instance;

        $dbi = \mod_programcourse\database_interface::get_instance();
        $programcourse = $dbi->get_course_module_by_id($coursemoduleinstance);
        $programcoursecourse = $DB->get_record('course', ['id' => $programcourse->course]);

        $completioninfo = new completion_info($programcoursecourse);
        $programcourselinkedcc = $dbi->get_programcourse_linked_course_completions($coursemoduleinstance);
        foreach ($programcourselinkedcc as $coursecompletion) {
            $completioninfo->update_state($coursemodule, COMPLETION_UNKNOWN, $coursecompletion->userid);
        }

        if (strpos($programcourse->name, '(copie)') !== false) {
            // Remove (copie) from module name.
            $programcourse->name = trim(str_replace('(copie)', '', $programcourse->name));
            $dbi->update_programcourse_instance($programcourse);

            course_modinfo::purge_course_module_cache($programcourse->id, $coursemoduleid);
        }
    }
}

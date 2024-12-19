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
 * mod_programcourse lib.
 *
 * @package    mod_programcourse
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Add a programcourse instance.
 *
 * @param stdClass $programcourse
 * @param mod_assignment_mod_form $mform
 * @return int instance id
 */
function programcourse_add_instance($programcourse, $mform = null) {
    $dbi = \mod_programcourse\database_interface::get_instance();

    $course = get_course($programcourse->courseid);

    $programcourse->name = $course->fullname;
    $programcourse->timemodified = time();

    $site = get_site();
    $programcourse->hiddenintro = $programcourse->intro . '<p>' . get_string(
            'initmod',
            'mod_programcourse',
            ['fullname' => $course->fullname, 'id' => $course->id, 'platform' => $site->fullname]
        ) . '</p>';

    $programcourse->id = $dbi->add_programcourse_instance($programcourse);

    $enrol = enrol_get_plugin('program');
    $enrol->add_instance($course, ['customint1' => $programcourse->course]);

    return $programcourse->id;
}

/**
 * Delete a programcourse instance.
 *
 * @param $id
 */
function programcourse_delete_instance($id) {
    $dbi = \mod_programcourse\database_interface::get_instance();

    // Get module.
    if (!$programcourse = $dbi->get_course_module_by_id($id)) {
        return false;
    }

    // Store programcourse linked course id.
    $courseid = $programcourse->course;
    $linkedcourseid = $programcourse->courseid;

    // Delete programcourse enrol instance for this course (which will unenroll all users too) if necessary.
    $enrolinstance = $dbi->get_enrol_instance_link_to_programcourse($courseid, $linkedcourseid);

    if ($enrolinstance !== false) {
        // Delete enrol program instance.
        $enrol = enrol_get_plugin('program');
        $enrol->delete_instance($enrolinstance);
    }

    $cm = get_coursemodule_from_instance('programcourse', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'programcourse', $id, null);

    // Note: all context files are deleted automatically.
    $dbi->delete_course_module_by_id($id);

    return true;
}

/**
 * Update a programcourse instance.
 *
 * @param stdClass $module
 * @return bool True on success, False on failure
 * @throws dml_exception
 */
function programcourse_update_instance($module) {
    $dbi = \mod_programcourse\database_interface::get_instance();

    // Get old data.
    $olddata = $dbi->get_course_module_by_id($module->instance);

    // Set a course module for the first time.
    if ($olddata->courseid == 0) {
        $programcourse = $dbi->get_course_module_by_id($module->instance);
        $programcourse->courseid = $module->courseid;

        $course = get_course($programcourse->courseid);

        $programcourse->name = $course->fullname;
        $programcourse->intro = $module->intro;

        $site = get_site();
        $programcourse->hiddenintro = $programcourse->intro . '<p>' . get_string(
                'initmod',
                'mod_programcourse',
                ['fullname' => $course->fullname, 'id' => $course->id, 'platform' => $site->fullname]
            ) . '</p>';

        $programcourse->timemodified = time();

        $dbi->update_programcourse_instance($programcourse);

        $enrol = enrol_get_plugin('program');
        $enrol->add_instance($course, ['customint1' => $programcourse->course]);
        // Update a program already set up.
    } else {
        $course = $dbi->get_course_link_to_programcourse($module->instance);

        $dbi->update_module_instance_name($module->instance, $course->fullname);

        if (isset($module->completionall) && $olddata->completionall !== $module->completionall) {
            $dbi->update_completion_config($module->instance, $module->completionall);
        }

        $completiontimeexpected = !empty($module->completionexpected) ? $module->completionexpected : null;
        \core_completion\api::update_completion_date_event($module->coursemodule, 'programcourse', $module->instance,
            $completiontimeexpected);
    }

    return true;
}

/**
 * Programcourse mod supports
 *
 * @param $feature
 * @return string|true|null
 */
function programcourse_supports($feature) {
    switch ($feature) {
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_OTHER;

        default:
            return null;
    }
}

/**
 * uCheck if user has completed fully
 *
 * @param int $userid
 * @param int $modinstance
 * @return bool
 * @throws dml_exception
 */
function user_has_completed_fully(int $userid, int $modinstance) {
    // Get linked course object.
    $dbi = \mod_programcourse\database_interface::get_instance();

    $program = $dbi->get_programcourse_info($modinstance);

    // Module is not configured.
    if ($program->courseid == 0) {
        return false;
    }

    $course = $dbi->get_course_link_to_programcourse($modinstance);

    // Instantiate completion object for linked course.
    $completion = new \completion_info($course);
    // Check that completion is activated for linked course.
    if (!$completion->is_enabled()) {
        // TODO: see what to do in this case.
        return false;
    }

    return $completion->is_course_complete($userid);
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function programcourse_get_coursemodule_info($coursemodule) {

    $dbi = \mod_programcourse\database_interface::get_instance();
    $programcourse = $dbi->get_programcourse_info($coursemodule->instance);

    if ($programcourse === false) {
        return null;
    }

    $result = new cached_cm_info();
    $result->name = $programcourse->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('programcourse', $programcourse, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['courseid'] = $programcourse->courseid;
        $result->customdata['customcompletionrules']['completionall'] = $programcourse->completionall;
    }

    return $result;
}

/**
 * Set user program redirect data to user session.
 *
 * @param int $courseid
 * @param int $redirectcourseid
 * @param string $hostname
 * @return void
 */
function programcourse_set_user_program_redirect_data(int $courseid, int $redirectcourseid, string $hostname = '') {
    global $SESSION;

    // Set program session user.
    if (!isset($SESSION->program)) {
        $SESSION->program = [];
    }

    // Set program user data.
    $SESSION->program[$courseid] = [
        'courseid' => $redirectcourseid,
        'hostname' => $hostname,
    ];
}

/**
 * get user program redirect url if existed.
 *
 * @param int $courseid
 * @return false|string
 * @throws Exception
 */
function programcourse_get_user_program_redirect(int $courseid): false|string {
    global $SESSION;

    // Check if user has program data link to course.
    if (!isset($SESSION->program[$courseid])) {
        return false;
    }

    // Get user program data.
    $redirectcourseid = $SESSION->program[$courseid]['courseid'];

    // If the current site use hosts.
    if (isset($SESSION->program[$courseid]['hostname']) && !empty($SESSION->program[$courseid]['hostname'])) {
        $hostname = $SESSION->program[$courseid]['hostname'];

        // Program is in current host.
        if (\local_magistere_common\host_api::is_current_host($hostname)) {
            return course_get_url($redirectcourseid)->out();
        }

        // Host not defined in config.
        if (!$host = \local_magistere_common\host_api::get_host_by_name($hostname)) {
            return false;
        }

        // Return user program link from other host.
        return $host->get_url() . '/course/view.php?id=' . $redirectcourseid;
    }

    return course_get_url($redirectcourseid)->out();
}

/**
 * get user program redirect course id if existed.
 *
 * @param int $courseid
 * @return false|int
 * @throws Exception
 */
function programcourse_get_user_program_redirect_course_id(int $courseid): false|int {
    global $SESSION;

    // Check if user has program data link to course.
    if (!isset($SESSION->program[$courseid])) {
        return false;
    }

    // Return user program course id.
    return $SESSION->program[$courseid]['courseid'];
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_programcourse_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionall':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionall', 'programcourse');
                }
                break;
            default:
                break;
        }
    }

    return $descriptions;
}

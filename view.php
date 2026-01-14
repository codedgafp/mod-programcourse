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
 * View page.
 *
 * @package    mod_programcourse
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/programcourse/lib.php');

$usehosts = false;

// Include host api if exists.
$hostlib = $CFG->dirroot . '/local/magistere_common/api/host.php';

if (file_exists($hostlib)) {
    require_once($hostlib);
    $usehosts = true;
}

$cmid = required_param('id', PARAM_INT);

$PAGE->set_url('/mod/programcourse/view.php', ['id' => $cmid]);

$cm = get_coursemodule_from_id('programcourse', $cmid, 0, true, MUST_EXIST);
$userid = $USER->id;
$course = get_course($cm->course);
$dbi = \mod_programcourse\database_interface::get_instance();
$enrolprogramdbi = \enrol_program\database_interface::get_instance();

if ($course === false) {
    throw new \moodle_exception('coursemisconf');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
programcourse_view($cmid, $context);

// Get programcourse.
$programcourse = $dbi->get_course_module_by_id($cm->instance);

// If the module if not well configured, redirect to the actity form.
if (empty($programcourse->courseid) || $programcourse->courseid == 0) {
    redirect($CFG->wwwroot . '/course/modedit.php?update=' . $cmid . '&return=1');
}

$courseidlink = $programcourse->courseid;
$enrolname = "program";

// Enrol user to linked course if not already enrolled.
if (!is_enrolled(context_course::instance($courseidlink), $userid)) {
    $instances = enrol_get_instances($programcourse->courseid, true);

    // Get good course program enrol instance.
    foreach ($instances as $instance) {
        if ($instance->enrol === $enrolname && $instance->customint1 === $cm->course) {
            $enrolinstance = $instance;
            break;
        }
    }

    // Get program enroll plugin.
    $enrol = enrol_get_plugin($enrolname);

    // Create enrol instance if not exist.
    if (!isset($enrolinstance)) {
        $enrolid = $enrol->add_instance(get_course($courseidlink), ['customint1' => $cm->course]);
        $enrolinstance = $dbi->get_enrol_instance_by_id($enrolid);
    }

    $roleparticipant = $enrolprogramdbi->get_role_by_shortname('participant');
    $enrol->enrol_user($enrolinstance, $userid, $roleparticipant->id);
}

// Set user program data to user session.
$hostname = $usehosts ? \local_magistere_common\host_api::get_my_hostname() : '';
programcourse_set_user_program_redirect_data($courseidlink, $course->id, $hostname);

// Redirect to linked course.
redirect(course_get_url($programcourse->courseid));

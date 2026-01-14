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
 * Session lib tests
 *
 * @package    mod_programcourse
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     rcolet <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
class programcourse_testcase extends advanced_testcase
{
    /**
     * Init $CFG
     */
    public function init_config()
    {
        global $CFG;

        $CFG->mentor_specializations = [
            '\\local_mentor_specialization\\mentor_specialization' =>
                'local/mentor_specialization/classes/mentor_specialization.php',
        ];
    }

    /**
     * Reset the singletons
     *
     * @throws ReflectionException
     */
    public function reset_singletons()
    {
        // Reset the mentor core db interface singleton.
        $dbinterface = \local_mentor_core\database_interface::get_instance();
        $reflection = new ReflectionClass($dbinterface);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true); // Now we can modify that :).
        $instance->setValue(null, null); // Instance is gone.
        $instance->setAccessible(false); // Clean up.

        \local_mentor_core\training_api::clear_cache();
    }


    /**
     * Initialization of the user data
     *
     * @return int
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function init_create_user()
    {
        global $DB;

        // Create user.
        $user = new stdClass();
        $user->lastname = 'lastname';
        $user->firstname = 'firstname';
        $user->email = 'test@test.com';
        $user->username = 'testusername';
        $user->password = 'to be generated';
        $user->mnethostid = 1;
        $user->confirmed = 1;
        $user->auth = 'manual';

        $userid = local_mentor_core\profile_api::create_user($user);
        set_user_preference('auth_forcepasswordchange', 0, $user);

        $field = $DB->get_record('user_info_field', ['shortname' => 'mainentity']);

        $userdata = new stdClass();
        $userdata->fieldid = $field->id;
        $userdata->data = 'New Entity 1';
        $userdata->userid = $userid;

        $DB->insert_record('user_info_data', $userdata);

        return $userid;
    }

    /**
    /**
     * Initialization of the session or trainig data
     *
     * @param false $training
     * @param null $sessionid
     * @return stdClass
     */
    public function init_session_data($training = false, $sessionid = null)
    {
        $data = new stdClass();

        set_config(
            'collections',
            'accompagnement|Accompagnement des transitions professionnelles|#CECECE',
            'local_mentor_specialization'
        );

        if ($training) {
            $data->name = 'Session name';
            $data->shortname = 'Sessionshortname';
            $data->content = 'Session summary';
            $data->status = 'ec';
        } else {
            $data->trainingname = 'training fullname';
            $data->trainingshortname = 'training shortname';
            $data->trainingcontent = 'training summary';
            $data->trainingstatus = 'ec';
        }

        // Fields for taining.
        $data->teaser = 'http://www.edunao.com/';
        $data->teaserpicture = '';
        $data->prerequisite = 'TEST';
        $data->collection = 'accompagnement';
        $data->traininggoal = 'TEST TRAINING ';
        $data->idsirh = 'TEST ID SIRH';
        $data->licenseterms = 'cc-sa';
        $data->typicaljob = 'TEST';
        $data->skills = [];
        $data->certifying = '1';
        $data->presenceestimatedtimehours = '12';
        $data->presenceestimatedtimeminutes = '10';
        $data->remoteestimatedtimehours = '15';
        $data->remoteestimatedtimeminutes = '30';
        $data->trainingmodalities = 'd';
        $data->producingorganization = 'TEST';
        $data->producerorganizationlogo = '';
        $data->designers = 'TEST';
        $data->contactproducerorganization = 'TEST';
        $data->thumbnail = '';

        // Specific fields for session (only for update).
        if ($sessionid) {
            $data->id = $sessionid;
            $data->opento = 'all';
            $data->publiccible = 'TEST';
            $data->termsregistration = 'autre';
            $data->termsregistrationdetail = 'TEST';

            $data->onlinesessionestimatedtimehours = '10';
            $data->onlinesessionestimatedtimeminutes = '15';
            $data->presencesessionestimatedtimehours = '12';
            $data->presencesessionestimatedtimeminutes = '25';

            $data->sessionpermanent = 0;
            $data->sessionstartdate = 1609801200;
            $data->sessionenddate = 1609801200;
            $data->sessionmodalities = 'presentiel';
            $data->accompaniment = 'TEST';
            $data->maxparticipants = 10;
            $data->placesavailable = 8;
            $data->numberparticipants = 2;
            $data->location = 'PARIS';
            $data->organizingstructure = 'TEST ORGANISATION';
            $data->sessionnumber = 1;
            $data->opentolist = '';
        }

        return $data;
    }



    /**
     * Init training creation
     *
     * @return training
     * @throws moodle_exception
     */
    public function init_training_creation()
    {
        global $DB;

        // Remove the miscelleanous category.
        $DB->delete_records('course_categories', ['id' => 1]);

        // Init test data.
        $data = $this->init_session_data(true);

        try {
            // Get entity object for default category.
            $entityid = \local_mentor_core\entity_api::create_entity([
                'name' => 'New Entity 1',
                'shortname' => 'New Entity 1'
            ]);

            $entity = \local_mentor_core\entity_api::get_entity($entityid);
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        // Init data with entity data.
        $data = $this->init_training_entity($data, $entity);

        // Test standard training creation.
        try {
            $training = \local_mentor_core\training_api::create_training($data);
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        return $training;
    }

    /**
     * Init session creation
     *
     * @return int
     * @throws moodle_exception
     */
    public function init_session_creation()
    {
        // Create training.
        $training = $this->init_training_creation();

        $sessionname = 'TESTUNITCREATESESSION';

        // Test standard session creation.
        try {
            $session = \local_mentor_core\session_api::create_session($training->id, $sessionname, true);
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        return $session->id;
    }

    /**
     * Init training categery by entity id
     */
    public function init_training_entity($data, $entity)
    {
        // Get "Formation" category id (child of entity category).
        $formationid = $entity->get_entity_formation_category();
        $data->categorychildid = $formationid;

        $data->categoryid = $entity->id;
        $data->creativestructure = $entity->id;

        return $data;
    }

    /**
     * Init training category by entity id
     */
    public function init_session_entity($data, $entity)
    {
        // Get "Formation" category id (child of entity category).
        $formationid = $entity->get_entity_formation_category();
        $data->categorychildid = $formationid;

        $data->categoryid = $entity->id;
        $data->creativestructure = $entity->id;

        return $data;
    }


    /**
    * Test construct function ok
    *

    */
    public function test_get_available_courses_for_program()
    {

        global $COURSE, $DB;

        $dbi = \mod_programcourse\database_interface::get_instance();
        $dbinterface = \local_mentor_specialization\database_interface::get_instance();
        $db = \local_mentor_core\database_interface::get_instance();

        $this->resetAfterTest(true);
        $this->reset_singletons();
        // $DB->delete_records('course_categories');
        self::setAdminUser();

        // Create session.
        $sessionid = $this->init_session_creation();
        $session = new \local_mentor_core\session($sessionid);
        $sessiontraining = $session->get_training();

        // Set category_options 
        $regionId = $dbinterface->get_all_regions()[1]->id;
        $categoryoption = new \stdClass();
        $categoryoption->categoryid = $session->get_entity()->id;
        $categoryoption->name = 'regionid';
        $categoryoption->value = $regionId;
        $categoryoption->id = $DB->insert_record('category_options', $categoryoption);

        // Update session sharing.
        $session->opento = 'all';
        $session->create_manual_enrolment_instance();
        $session->sessionstartdate = time();
        $session->status = \local_mentor_core\session::STATUS_IN_PROGRESS;
        $session->update($session);
        $sessioncourse = $session->get_course();

        // Set the global $COURSE variable to the created course
        $COURSE = $sessiontraining->get_course();

        // Create user
        $user = new stdClass();
        $user->lastname = 'lastname';
        $user->firstname = 'firstname';
        $user->email = 'test@test.com';
        $user->username = 'testusername';
        $user->password = 'to be generated';
        $user->mnethostid = 1;
        $user->confirmed = 1;
        $user->auth = 'manual';
        $user->profile_field_mainentity = 'New Entity 1';
        $user->profile_field_secondaryentities = ['New Entity 1'];
        $userid = local_mentor_core\profile_api::create_user($user);
        //Set user_info_data
        $userprofile = \local_mentor_core\profile_api::get_profile($userid);
        $regionuserfield = $DB->get_record('user_info_field', ['shortname' => 'region']);
        $regionlist = explode("\n", $regionuserfield->param1);
        $regionfielddata = new stdClass();
        $regionfielddata->userid = $userprofile->id;
        $regionfielddata->fieldid = $regionuserfield->id;
        $regionfielddata->data = $regionlist[2];
        $regionfielddata->dataformat = 0;
        $DB->insert_record('user_info_data', $regionfielddata);
        $userprofile->sync_entities();

        //Asign role formateur && capability 'moodle/course:manageactivities'
        $formateurrole = $db->get_role_by_name('formateur');
        assign_capability('moodle/course:manageactivities', CAP_ALLOW, $formateurrole->id, $sessiontraining->get_context(), true);
        role_assign($formateurrole->id, $userid, $sessiontraining->get_context());

        //Enrol user to the course as a formateur
        $session->create_self_enrolment_instance();
        self::getDataGenerator()->enrol_user($user->id, $sessioncourse->id, \local_mentor_specialization\mentor_profile::ROLE_FORMATEUR);

        self::setUser($user);
        //Get available courses for a program
        $courseList = $dbi->get_available_courses_for_program($COURSE);

        self::assertCount(1, $courseList);
        self::assertEquals($sessioncourse->id, array_values($courseList)[0]->id);
    }

    /**
     * Test is_programcourse_by_courseid function
     */
    public function test_is_programcourse_by_courseid()
    {
        global $DB;

        $this->resetAfterTest(true);
        $this->reset_singletons();
        self::setAdminUser();

        // Create a course.
        $course1 = self::getDataGenerator()->create_course();
        // Create a course.
        $course2 = self::getDataGenerator()->create_course();
        // Insert a record into the programcourse table.
        $programcourse = new stdClass();
        $programcourse->course = $course1->id;
        $programcourse->courseid = $course2->id;
        $programcourse->intro = "intro";
        $programcourse->timemodified = time();
        $DB->insert_record('programcourse', $programcourse);

        // Check if the course is a program course.
        $dbi = \mod_programcourse\database_interface::get_instance();
        $isprogramcourse = $dbi->is_programcourse_by_courseid($course2->id);

        self::assertTrue($isprogramcourse);

        // Check with a non-program course.
        $nonprogramcourse = self::getDataGenerator()->create_course();
        $isprogramcourse = $dbi->is_programcourse_by_courseid($nonprogramcourse->id);

        self::assertFalse($isprogramcourse);
    }

    /**
     * test test_link_course_to_programcourse_is_completed
     */
    public function test_link_course_to_programcourse_is_completed() {
        global $DB;
        self::setAdminUser();
        $this->resetAfterTest(true);

        $programcoursegen = $this->getDataGenerator()->get_plugin_generator('mod_programcourse');

        // create course to complete
        $coursetocomplete = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        // create activity and link to course to complete
        $recordmodule = new stdClass();
        $recordmodule->course = $coursetocomplete;
        $recordmodule->completion = 2;
        $recordmodule->completionview = 1;
        $recordmodule->completionexpected = 0;
        $recordmodule->completionunlocked = 1;
        $recordmodule->visible = 1;
        $foruminstance = $this->getDataGenerator()->create_module('forum', $recordmodule);
        
        // create user
        $participant = $this->getDataGenerator()->create_and_enrol($coursetocomplete, 'participant');
        
        // create course completion
        $coursecompletion = new completion_completion(['course' => $coursetocomplete->id, 'userid' => $participant->id]);

        // completed activity
        $result = core_completion_external::override_activity_completion_status($participant->id, $foruminstance->cmid, COMPLETION_COMPLETE);
        $result = \external_api::clean_returnvalue(core_completion_external::override_activity_completion_status_returns(), $result);
        $this->assertEquals($result['state'], COMPLETION_COMPLETE);

        // completed course
        $coursecompletion->mark_complete();
        
        // create course wich contain the programcourse activity
        $programcoursesession = self::getDataGenerator()->create_course(['enablecompletion' => 1]);
        // create programcourse and link to his course
        $programcourse = $programcoursegen->create_instance([
            'course' => $programcoursesession->id,
            'courseid' => $coursetocomplete->id,
            'completion' => 2
        ]);

        // emulate the "add module" form
        $programcoursemodule = $DB->get_record('course_modules', ['course' => $programcoursesession->id, 'instance' => $programcourse->id]);
        $moduleinfo = new \stdClass();
        $moduleinfo->id = $programcoursemodule->id;
        $moduleinfo->modname = 'programcourse';
        $moduleinfo->instance = $programcourse->id;
        $moduleinfo->name = $programcourse->name;

        $event = \core\event\course_module_created::create_from_cm($moduleinfo);
        $event->trigger();

        // test if the completion is create
        $programcoursemodule = $DB->get_record('course_modules', ['course' => $programcoursesession->id, 'instance' => $programcourse->id]);
        $programcoursemodulecompletion = $DB->get_records('course_modules_completion', ['coursemoduleid' => $programcoursemodule->id, 'userid' => $participant->id]);
        $this->assertCount(1, $programcoursemodulecompletion);
    }

    /**
     * test test_link_course_to_programcourse_is_not_completed
     */
    public function test_link_course_to_programcourse_is_not_completed() {
        global $DB;
        self::setAdminUser();
        $this->resetAfterTest(true);

        $programcoursegen = $this->getDataGenerator()->get_plugin_generator('mod_programcourse');

        // create course to complete
        $coursetocomplete = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        // create activity and link to course to complete
        $recordmodule = new stdClass();
        $recordmodule->course = $coursetocomplete;
        $recordmodule->completion = 2;
        $recordmodule->completionview = 1;
        $recordmodule->completionexpected = 0;
        $recordmodule->completionunlocked = 1;
        $recordmodule->visible = 1;
        $this->getDataGenerator()->create_module('forum', $recordmodule);

        // create user
        $participant = $this->getDataGenerator()->create_and_enrol($coursetocomplete, 'participant');

        // create course wich contain the programcourse activity
        $programcoursesession = self::getDataGenerator()->create_course(['enablecompletion' => 1]);
        // create programcourse and link to his course
        $programcourse = $programcoursegen->create_instance([
            'course' => $programcoursesession->id,
            'courseid' => $coursetocomplete->id,
            'completion' => 2
        ]);

        // emulate the "add module" form
        $programcoursemodule = $DB->get_record('course_modules', ['course' => $programcoursesession->id, 'instance' => $programcourse->id]);
        $moduleinfo = new \stdClass();
        $moduleinfo->id = $programcoursemodule->id;
        $moduleinfo->modname = 'programcourse';
        $moduleinfo->instance = $programcourse->id;
        $moduleinfo->name = $programcourse->name;

        $event = \core\event\course_module_created::create_from_cm($moduleinfo);
        $event->trigger();

        // test if the completion is create
        $programcoursemodule = $DB->get_record('course_modules', ['course' => $programcoursesession->id, 'instance' => $programcourse->id]);
        $programcoursemodulecompletion = $DB->get_records('course_modules_completion', ['coursemoduleid' => $programcoursemodule->id, 'userid' => $participant->id]);
        $this->assertCount(0, $programcoursemodulecompletion);
    }

    public function test_programcourse_viewed_event(): void
    {
        self::setAdminUser();
        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();
        $coursetocomplete = $this->getDataGenerator()->create_course();

        $recordmodule = new \stdClass();
        $recordmodule->course = $course->id;
        $recordmodule->courseid = $coursetocomplete->id;
        $recordmodule->name = "Program course test";
        $recordmodule->intro = null;
        $recordmodule->introformat = 1;
        $recordmodule->completionall = 1;
        $recordmodule->timemodified = new DateTime();
        $recordmodule->hiddenintro = null;
        $programcourse = $this->getDataGenerator()->create_module('programcourse', $recordmodule);

        $cm = get_coursemodule_from_id('programcourse', $programcourse->cmid, 0, true, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $params = [
            'context' => $context,
            'objectid' => $programcourse->id
        ];

        $event = \mod_programcourse\event\programcourse_viewed::create($params);
        $event->trigger();

        $this->assertEquals('\mod_programcourse\event\programcourse_viewed', $event->eventname);
        $this->assertEquals('mod_programcourse', $event->component);
        $this->assertEquals('viewed', $event->action);
        $this->assertEquals('programcourse', $event->target);
        $this->assertEquals($programcourse->id, $event->objectid);
        $this->assertEquals('r', $event->crud);
        $this->assertEquals($context->id, $event->contextid);
        $this->assertEquals($course->id, $event->courseid);
    }
}

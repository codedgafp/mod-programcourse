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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * mod_programcourse form.
 *
 * @package    mod_programcourse
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_programcourse_mod_form extends moodleform_mod {
    /**
     * Form definition.
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function definition(): void {
        $mform = $this->_form;
        $cm = $this->_cm;
        $course = $this->_course;
        $dbi = \mod_programcourse\database_interface::get_instance();

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $attr = [];

        $courseoptions = [];

        $wellconfigured = false;

        // Check if the module is well configured.
        if (!is_null($cm)) {
            // Add juste course set to module to select.
            $programcourse = $dbi->get_course_module_by_id($cm->instance);
            if ($programcourse->courseid != 0) {
                $wellconfigured = true;
            }
        }

        // Display the intro editor.
        $this->standard_intro_elements();

        if ($wellconfigured) {
            $courselink = get_course($programcourse->courseid);
            $courseoptions[$courselink->id] = $courselink->fullname;
            // Disable element if module is set.
            $attr['disabled'] = 'disabled';

        } else {
            $courseoptions = [0 => 'Aucune option séléctionnée'];
            // Get list of all course not yet in this 'program'.
            $courses = $dbi->get_available_courses_for_program();
            $columns = array_combine(
                array_column($courses, 'id'),
                array_map(function($course) {
                    return "{$course->fullname} ({$course->id})";
                }, $courses)
            );
            $courseoptions = $courseoptions + $columns;
        }

        $mform->addElement('select', 'courseid', get_string('courselist', 'mod_programcourse'), $courseoptions, $attr);

        if ($wellconfigured) {
            $mform->setDefault('courseid', $programcourse->courseid);
        } else {
            $mform->addRule('courseid', get_string('required'), 'required', null, 'client');
        }
        $mform->addHelpButton('courseid', 'courselist', 'mod_programcourse');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();

        // Add selectpicker.
        $this->add_select_picker();
    }

    /**
     * Adds 'completionlink' completion rule.
     *
     * @return string[]
     */
    public function add_completion_rules(): array {
        $mform =& $this->_form;

        $mform->addElement('advcheckbox', 'completionall', '', get_string('completionall', 'programcourse'));
        // Set completion all by default.
        $mform->setDefault('completionall', 1);

        return ['completionall'];
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data): void {
        parent::data_postprocessing($data);
        if ($data->completion === '2') {
            $data->completionall ??= 0;
        } else {
            $data->completionall = 0;
        }

        if (!isset($data->introeditor)) {
            $data->introeditor['text'] = '';
            $data->introeditor['itemid'] = 0;
            $data->introeditor['format'] = 1;
        }
    }

    /**
     * Returns completion rule enabled status.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !(empty($data['completionall']));
    }

    /**
     * Add select picker CSS And JS initialisation.
     *
     * @return void
     * @throws coding_exception
     */
    public function add_select_picker(): void {
        global $CFG, $PAGE;

        $PAGE->requires->css('/mod/programcourse/css/bootstrap-select.min.css');
        $requireconfig = [
            'paths' => [
                'selectpicker' => $CFG->wwwroot . '/mod/programcourse/js/bootstrap-select.min',
            ],
            'shim' => [
                'selectpicker' => ['deps' => ['theme_boost/bootstrap/dropdown']],
            ],
        ];
        $PAGE->requires->js_amd_inline('require.config(' . json_encode($requireconfig) . ')');
        $PAGE->requires->js_call_amd('mod_programcourse/mod_programcourse', 'init', []);
    }

    /**
     * Validate form data after submission
     *
     * @param $data
     * @param $files
     * @return array
     * @throws coding_exception
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['courseid'] == 0) {
            $errors['courseid'] = get_string('required');
        }

        return $errors;
    }

    /**
     * Allows module to modify data returned by get_moduleinfo_data() or prepare_new_moduleinfo_data() before calling set_data()
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param array $default_values passed by reference
     */
    function data_preprocessing(&$default_values){
        //Set default completion tracking to automatic
        if (isset($default_values['completion'])) {
            $default_values['completion'] = COMPLETION_TRACKING_AUTOMATIC;
        }       
        
    }
}

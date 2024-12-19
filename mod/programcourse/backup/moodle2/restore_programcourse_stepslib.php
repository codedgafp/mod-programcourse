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
 * Backup program course activity structure step
 *
 * @package    mod_programcourse
 * @copyright  2024 Edunao SAS (contact@edunao.com)
 * @author     Rémi Colet <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_programcourse_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure for the programcourse activity
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('programcourse', '/activity/programcourse');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process restore.
     *
     * @param mixed $data
     * @return void
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function process_programcourse($data) {
        $dbi = \mod_programcourse\database_interface::get_instance();

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->courseid = 0;
        $data->intro = $data->hiddenintro;

        // Insert the programcourse activity instance.
        $newitemid = $dbi->add_programcourse_instance($data);
        // Apply activity instance.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Hook to execute assignment upgrade after restore.
     */
    protected function after_restore() {
        global $DB;
        $programcourseid = $this->get_new_parentid('programcourse');
        $programcourse = $DB->get_record('programcourse', ['id' => $programcourseid]);

        $programcourse->name .= ' (à configurer)';
        $DB->update_record('programcourse', $programcourse);
    }
}

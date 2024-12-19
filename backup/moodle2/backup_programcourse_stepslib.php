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
 * @author     Rémi Colet <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_programcourse_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure for the programcourse activity
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated. Do not backup the courseid.
        $programcourse = new backup_nested_element(
            'programcourse',
            ['id'],
            ['name', 'intro', 'introformat', 'completionall', 'timemodified', 'site', 'hiddenintro']
        );

        // Define sources.
        $programcourse->set_source_table('programcourse', ['id' => backup::VAR_ACTIVITYID]);

        // Define annotations.
        $programcourse->annotate_files('mod_programcourse', 'intro', null);

        // Return the root element (programcourse), wrapped into standard activity structure.
        return $this->prepare_activity_structure($programcourse);
    }

}

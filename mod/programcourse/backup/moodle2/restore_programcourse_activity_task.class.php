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

require_once($CFG->dirroot . '/mod/programcourse/backup/moodle2/restore_programcourse_stepslib.php');

/**
 * Backup program course activity task
 *
 * @package    mod_programcourse
 * @copyright  2024 Edunao SAS (contact@edunao.com)
 * @author     Rémi Colet <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_programcourse_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings that each activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     *
     * @return void
     * @throws base_task_exception
     */
    protected function define_my_steps(): void {
        // Label only has one structure step.
        $this->add_step(new restore_programcourse_activity_structure_step('programcourse_structure', 'programcourse.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     *
     * @return array
     */
    public static function define_decode_contents(): array {
        $contents = [];

        $contents[] = new restore_decode_content('programcourse', ['intro'], 'programcourse');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     *
     * @return array
     */
    public static function define_decode_rules(): array {
        return [];
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * label logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * @return array
     */
    public static function define_restore_log_rules(): array {
        $rules = [];

        $rules[] = new restore_log_rule('programcourse', 'add', 'view.php?id={course_module}', '{programcourse}');
        $rules[] = new restore_log_rule('programcourse', 'update', 'view.php?id={course_module}', '{programcourse}');
        $rules[] = new restore_log_rule('programcourse', 'view', 'view.php?id={course_module}', '{programcourse}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course(): array {
        $rules = [];

        $rules[] = new restore_log_rule('programcourse', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}

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

require_once($CFG->dirroot . '/mod/programcourse/backup/moodle2/backup_programcourse_stepslib.php');

/**
 * Backup program course activity task
 *
 * @package    mod_programcourse
 * @copyright  2024 Edunao SAS (contact@edunao.com)
 * @author     Rémi Colet <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_programcourse_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_programcourse_activity_structure_step('programcourse_structure', 'programcourse.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        //Link to the list of pages.
        $search = "/(" . $base . "\/mod\/programcourse\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@PROGRAMCOURSEINDEX*$2@$', $content);

        // Link to page view by moduleid.
        $search = "/(" . $base . "\/mod\/programcourse\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@PROGRAMCOURSEVIEWBYID*$2@$', $content);

        return $content;
    }
}

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

/**
 * Upgrade file.
 *
 * @package    mod_programcourse
 * @copyright  2024 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_programcourse_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023081702) {
        // Add completionall field to programcourse.
        $table = new xmldb_table('programcourse');
        $field = new xmldb_field(
            'completionall',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'introformat'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2023081702, 'programcourse');
    }

    if ($oldversion < 2024060500) {
        // Remove course/courseid unicity.
        $table = new xmldb_table('programcourse');
        if ($dbman->table_exists($table)) {
            $index = new xmldb_index('programcourse');
            $index->set_attributes(XMLDB_INDEX_UNIQUE, ['course', 'courseid']);
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }
        }
    }

    if ($oldversion < 2024082000) {

        $table = new xmldb_table('programcourse');
        $field = new xmldb_field(
            'hiddenintro',
            XMLDB_TYPE_TEXT,
            null,
            null,
            null,
            null
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2024082000, 'programcourse');
    }

    return true;
}

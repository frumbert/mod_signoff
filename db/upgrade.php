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
 * Shared URL module upgrade code
 $
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @copyright  Work based on : 2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_signoff_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // added completionsubmit column
    if ($oldversion < 2022030501) {

        $table = new xmldb_table('signoff');

        // Define field completionsubmit to be added to signoff.
        $field = new xmldb_field('completionsubmit', XMLDB_TYPE_INTEGER, '1', null, true, null, 0, 'timemodified');
        // Conditionally launch add field signoff.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('completionsign', XMLDB_TYPE_INTEGER, '1', null, true, null, 0, 'completionsubmit');
        // Conditionally launch add field signoff.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Quiz savepoint reached.
        upgrade_mod_savepoint(true, 2022030501, 'signoff');
    }

    return true;
}

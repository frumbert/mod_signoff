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
 * Define all the backup steps that will be used by the backup_signoff_activity_task
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @copyright  Work based on : 2010 onwards Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

 /**
 * Define the complete url structure for backup, with file and id annotations
 */
class backup_signoff_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        //the shared URL module stores no user info

        // Define each element separated
        $signoff = new backup_nested_element('signoff', array('id'), array(
                'activity', 'name', 'intro', 'introformat',
                'notify_self', 'notify_teacher', 'show_signature', 'requiresubmit',
                'label', 'timemodified', 'completionsubmit', 'completionsign'
        ));
        $signoff->set_source_table('signoff', array('id' => backup::VAR_ACTIVITYID));

        $signoffdata = new backup_nested_element('signoff_data', array('id'), array(
            'userid', 'signoffid', 'completed', 'signature', 'timecreated', 'timemodified'
        ));

        // Define sources

        // Define file annotations
        $signoff->annotate_files('mod_signoff', 'intro', null); // This file area hasn't itemid

        // Return the root element (url), wrapped into standard activity structure
        return $this->prepare_activity_structure($signoff);

    }
}

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
 * URL configuration form
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @copyright  Work based on : 2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/signoff/locallib.php');

class mod_signoff_mod_form extends moodleform_mod {
    function definition() {
        // global $COURSE, $DB;
        $mform = $this->_form;
        $yn = [0 => get_string('no'), 1 => get_string('yes')];

        // $config = get_config('signoff');
        // if ($DB->record_exists('signoff', ['course' => $COURSE->id])) {
        //     \core\notification::warning(get_string('instanceexists', 'signoff'));
        // }

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // instance name
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setDefault('name', get_string('modulename', 'signoff'));

        // button label
        $mform->addElement('text', 'label', get_string('button_label', 'signoff'), array('size'=>'48'));
        $mform->setType('label', PARAM_TEXT);
        $mform->addRule('label', null, 'required', null, 'client');
        $mform->addRule('label', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setDefault('label', get_string('button_label_default', 'signoff'));

        // notify teacher(s) ?
        $mform->addElement('select', 'notify_teachers', get_string('notify_teachers', 'signoff'), $yn);
        $mform->setDefault('notify_teachers', 1);

        // notify self?
        $mform->addElement('select', 'notify_self', get_string('notify_self', 'signoff'), $yn);
        $mform->setDefault('notify_self', 0);
        $mform->setAdvanced('notify_self');

        // require signature?
        $mform->addElement('select', 'show_signature', get_string('show_signature', 'signoff'), $yn);
        $mform->setDefault('show_signature', 0);
        $mform->setAdvanced('show_signature');

        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data->completion) &&
                $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (!$autocompletion || empty($data->completionsubmit)) {
                $data->completionsubmit = 0;
            }
        }
    }

    /**
     * Add completion rules to form.
     * @return array
     */
    public function add_completion_rules() {
        $mform =& $this->_form;
        $mform->addElement('checkbox', 'completionsubmit', '', get_string('completionsubmit', 'signoff'));
        // Enable this completion rule by default.
        $mform->setDefault('completionsubmit', 1);
        return array('completionsubmit');
    }

    /**
     * Enable completion rules
     * @param stdclass $data
     * @return array
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }

}

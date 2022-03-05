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
 * This file provides form for splitting discussions
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}
require_once("$CFG->libdir/formslib.php");

/**
 * Form which displays fields for splitting forum post to a separate threads.
 *
 * @package    mod_signoff
 * @copyright  2021 tim.stclair
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_signoff_view_form extends moodleform {

    /**
     * Form constructor.
     *
     */
    public function definition() {
        global $PAGE, $CFG;

        $mform = $this->_form;

        $cmid = $this->_customdata->cm->id;
        $button_text = $this->_customdata->signoff->label;
        $signature = $this->_customdata->signature;

        \MoodleQuickForm::registerElementType(
            'signature_field',
            "$CFG->dirroot/mod/signoff/classes/form/signature.php",
            'mod_signoff_signature_form_element'
        );

        $mform->addElement('signature_field', 'user_signature', $signature);
        $mform->setType('user_signature',PARAM_RAW);

        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id',PARAM_INT);
        $mform->addElement('submit', 'submitbutton', $button_text);
    }

}

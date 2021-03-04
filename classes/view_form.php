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
        global $PAGE;

        $mform = $this->_form;

        $cmid = $this->_customdata->cm->id;
        $button_text = $this->_customdata->signoff->label;

        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id',PARAM_INT);

        // todo: sort out AMD
        // https://github.com/szimek/signature_pad
        if (false && $this->_customdata->signoff->show_signature > 0) {

            $mform->addElement('hidden', 'sigdata', '');
            $mform->setType('sigdata',PARAM_RAW);

            $config = ['paths' => ['signature_pad' => 'https://cdn.jsdelivr.net/npm/signature_pad@2.3.2/dist/signature_pad.min.js']];
            $requirejs = 'require.config(' . json_encode($config) . ')';
            $PAGE->requires->js_amd_inline($requirejs);
            $PAGE->requires->js_call_amd('mod_signoff/signoff','signoff');
            $mform->addElement('html', get_string('signature_template', 'signoff'));
        }

        $mform->addElement('submit', 'submitbutton', $button_text);
    }
}

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
 * mod_signoff signature element.
 *
 * @package   mod_signoff
 * @copyright 2022 Tim
 * @copyright https://github.com/szimek/signature_pad
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/form/hidden.php');

/**
 * signature (canvas-based javascript element for producing and uploading signatures).
 * now, because of the way moodle uses core templates for rendering quickform elements
 * (see https://moodle.org/mod/forum/discuss.php?d=406401), we need to use a hidden field
 * because it doesn't render using the mustache renderer and actually executes toHtml()
 * so we can hook into that and include our required scripts
 * other ways may be possible, but this works ...
 *
 * @package   mod_signoff
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_signoff_signature_form_element extends MoodleQuickForm_hidden {

    /**
     * Constructor
     *
     * @param string $elementName Element name
     * @param mixed $elementValue value for an element
     * @param mixed $attributes Either a typical HTML attribute string or an associative array.
     */
    public function __construct($elementName=null, $elementValue=null, $attributes=null) {
        if ($elementName == null) {
            // This is broken quickforms messing with the constructors.
            return;
        }
        parent::__construct($elementName, $elementValue, $attributes);
    }

    public function toHtml() {
        global $PAGE;
        $PAGE->requires->string_for_js('signature_label', 'mod_signoff');
        $PAGE->requires->string_for_js('clear', 'mod_signoff');
        $PAGE->requires->string_for_js('undo', 'mod_signoff');
        $PAGE->requires->string_for_js('instructions', 'mod_signoff');
        $PAGE->requires->js_call_amd('mod_signoff/index', 'init', [$this->getName()]);
        // a dummy elment we use to locate this form element using javascript - removed at runtime
        $html = '<div id="tmp_' . $this->getName() . '"></div>';
        // the hidden field element that would normally be rendered
        $html .= '<input type="hidden" name="' . $this->getName() . '" value="' . $this->getValue() . '" />';
        return $html;
    }
}

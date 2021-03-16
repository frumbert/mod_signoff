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
 * signoff module main user interface
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/signoff/lib.php");
require_once("$CFG->dirroot/mod/signoff/locallib.php");
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/datalib.php');
require_once("$CFG->dirroot/mod/signoff/classes/view_form.php");

$id = optional_param('id', 0, PARAM_INT);        // Course module ID (signoff's id)

$cm = get_coursemodule_from_id('signoff', $id, 0, false, MUST_EXIST);
$signoff = $DB->get_record('signoff', array('id' => $cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/signoff:view', $context);

// Completion and trigger events.
signoff_view($signoff, $course, $cm, $context);

$PAGE->set_url('/mod/signoff/view.php', array('id' => $cm->id));

// data for form
$data = new stdClass();
$data->signoff = $signoff;
$data->cm = $cm;

if (signoff_has_submission($USER, $cm)) {

	signoff_start_page($signoff, $cm, $course);
	signoff_print_info($USER, $cm);

} else {

	// submit button form
	$mform = new mod_signoff_view_form(null, $data);
	if ($mform->is_cancelled()) {
		// leave as is
		signoff_start_page($signoff, $cm, $course);
		$mform->set_data($data);
		$mform->display();

	} else if ($submitted = $mform->get_data()) {
		// process
		signoff_process_submission($submitted, $USER, $cm);
		redirect($PAGE->url);

	} else {

		signoff_start_page($signoff, $cm, $course);
		$mform->set_data($data);
		$mform->display();
	}

}

if (has_capability('mod/signoff:viewall', $context)) {
    $url = new moodle_url('/mod/signoff/index.php', ['course' => $cm->course, 'instance' => $cm->instance]);
    echo '<p>', html_writer::link($url, get_string('view_submissions','signoff')), '<p>';
}

signoff_print_footer($signoff, $cm, $course);

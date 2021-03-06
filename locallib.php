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
 * Private shared url module utility functions
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/signoff/lib.php");

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function signoff_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

function signoff_start_page($instance, $cm, $course) {
    global $OUTPUT;

    // standard headers
    signoff_print_header($instance, $cm, $course);
    signoff_print_heading($instance, $cm, $course);
    signoff_print_intro($instance, $cm, $course);

    // Info box.
    if ($instance->intro) {
        echo $OUTPUT->box(format_module_intro('signoff', $instance, $cm->id), 'generalbox', 'intro');
    }
}

/**
 * Print url header.
 * @param object $rsrc
 * @param object $cm
 * @param object $course
 * @return void
 */
function signoff_print_header($rsrc, $cm, $course)
{
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname . ': ' . $rsrc->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($rsrc);
    echo $OUTPUT->header();
}

/**
 * Print url heading.
 * @param object $obj
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function signoff_print_heading($obj, $cm, $course, $notused = false)
{
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($obj->name), 2);
}

function signoff_print_footer($obj, $cm, $course) {
    global $OUTPUT;
    echo $OUTPUT->footer();
}

/**
 * Print url introduction.
 * @param object $obj
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function signoff_print_intro($obj, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($obj->displayoptions) ? array() : unserialize($obj->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($obj->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'urlintro');
            echo format_module_intro('signoff', $obj, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

function signoff_print_info($user, $cm) {
    global $DB;
    if ($data = $DB->get_record('signoff_data', array('signoffid' => $cm->instance, 'userid' => $user->id), '*', IGNORE_MISSING)) {
        echo '<p class="alert alert-info">', get_string('self_signedoff', 'signoff', userdate($data->timemodified)), '</p>';
    }
}

function signoff_has_submission($user, $cm) {
    global $DB;

    // TODO check if completed > 0
    return $DB->record_exists('signoff_data', array('signoffid' => $cm->instance, 'userid' => $user->id));
}

function signoff_process_submission($data, $user, $cm) {
    global $DB, $CFG;

    $update = signoff_has_submission($user,$cm);

    if (!isset($data->signature)) $data->signature = '';

    // record signoff in the database
    if ($update) {
        $rec = $DB->get_record('signoff_data', ['signoffid' => $cm->instance, 'userid' => $user->id], '*', MUST_EXIST);
        $rec->completed = time();
        $rec->signature = $data->signature;
        $DB->upate_record('signoff_data', $rec);
    } else {
        $rec = new stdClass();
        $rec->userid = $user->id;
        $rec->signoffid = $cm->instance;
        $rec->timecreated = time();
        $rec->timemodified = time();
        $rec->completed = time();
        $rec->signature = $data->signature;
        $DB->insert_record('signoff_data', $rec);
    }

    // grab contextual information for notification
    $sectionid = 1;
    $signoff = $DB->get_record('signoff', array('id' => $cm->instance), '*', MUST_EXIST);
    $modulecontext = \context_module::instance($cm->id);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', array('id' => $cm->section), '*', MUST_EXIST);
    $coursecontext = \context_course::instance($cm->course);
    $link = (new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $section->section]))->out();
    $template = str_replace('\n', PHP_EOL, get_string('notify_template', 'signoff', [
        'name' => fullname($user,true),
        'course' => format_string($course->fullname, true, array(
            'context' => $coursecontext,
        )),
        'section' => $section->name . '',
        'activity' => format_string($signoff->name, true, array(
            'context' => $modulecontext,
        )),
        'url' => $link
    ]));

    // create a list of users who will be receiving the notification
    $notify = [];

    if ($signoff->notify_self) {
        $notify[] = core_user::get_user($user->id);
    }

    if ($signoff->notify_teacher) {
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teachers = get_role_users($role->id, $coursecontext);
        foreach ($teachers as $teacher) {
            $notify[] = core_user::get_user($teacher->id);
        }
    }

    // send the notification
    // see https://docs.moodle.org/dev/Message_API
    foreach($notify as $to) {

        // not sure how to set up the capability to send email to anyone through messaging, so directly email user;
        email_to_user($to, core_user::get_noreply_user(), get_string('notify_subject', 'signoff'), $template);

        // Attempt to send msg from a provider mod_signoff/emailnotify that is inactive or not allowed for the user id=6
        //    line 224 of /lib/messagelib.php: call to debugging()
        //    line 198 of /mod/signoff/locallib.php: call to message_send()
        //    line 74 of /mod/signoff/view.php: call to signoff_process_submission()

        // $message = new \core\message\message();
        // $message->courseid          = $cm->course;
        // $message->notification      = 1;
        // $message->component         = 'mod_signoff';
        // $message->name              = 'emailnotify';
        // $message->userfrom          = core_user::get_noreply_user();
        // $message->userto            = $to;
        // $message->subject           = get_string('notify_subject', 'signoff');
        // $message->fullmessage       = $template;
        // $message->fullmessageformat = FORMAT_PLAIN;
        // $message->fullmessagehtml   = ''; // markdown_to_html($template);
        // $message->smallmessage      = ''; // get_string('notify_subject', 'signoff');
        // $message->contexturlname    = 'Signoff';
        // $message->contexturl        = (string)new moodle_url('/mod/signoff/view.php', array('id'=>$cm->instance));
        // message_send($message);
    }

}
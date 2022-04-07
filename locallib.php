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

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/resourcelib.php');
require_once($CFG->dirroot.'/mod/signoff/lib.php');
require_once($CFG->dirroot.'/lib/completionlib.php');

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
    global $OUTPUT, $USER;

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
   // var_dump
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

function signoff_print_info($user, $cm, $context) {
    global $DB,$CFG;
    if ($data = $DB->get_record('signoff_data', array('signoffid' => $cm->instance, 'userid' => $user->id), '*', IGNORE_MISSING)) {
        echo '<p class="alert alert-info">', get_string('self_signedoff', 'signoff', userdate($data->timemodified)), '</p>';
        //  [wwwroot]/pluginfile.php/[contextid]/[component]/[filearea]/[itemid][filepath][filename]
        if (!empty($data->signature)) echo "<p><img src='/pluginfile.php/{$context->id}/mod_signoff/signature/{$data->id}' class='mod-signoff--signature'></p>";
    }
}

function signoff_has_submission($user, $cm) {
    global $DB;

    // TODO check if completed > 0
    return $DB->record_exists('signoff_data', array('signoffid' => $cm->instance, 'userid' => $user->id));
}

// List of content (assignments and quizzes in the current section) that hasn't yet been completed
// return false if links were found
function signoff_render_unsubmitted($user,$cm, $requiresubmit) {
global $OUTPUT;
    if ($requiresubmit == '1') {
        $links = signoff_get_unsubmitted_work_in_section($user, $cm);
        if (!empty($links) ) {
            echo $OUTPUT->box_start('generalbox', 'unsubmitted');
            echo '<p>', get_string('unsubmitted', 'signoff'), '</p>';
            echo '<ul>';
            foreach ($links as $link) {
                echo '<li>', $link, '</li>';
            }
            echo '</ul>';
            echo $OUTPUT->box_end();
            return false;
        }
    }

    return true;
}

// find quizzes and assignments in the current section that have not been submitted and return a linked array of these items
function signoff_get_unsubmitted_work_in_section($user, $cm) {
    global $DB;
    $modinfo = get_fast_modinfo($cm->course, $user->id);
    $mods = $modinfo->get_cms();
    $links = [];
    foreach ($mods as $mod) {
        if ($mod->section == $cm->section) {
            $submitted = true;
            switch ($mod->modname) {
                case 'assign':
                    $submitted = $DB->record_exists('assign_submission', ['assignment' => $mod->instance, 'userid' => $user->id, 'status' => 'submitted']);
                break;

                case 'quiz':
                    $submitted = $DB->record_exists('quiz_attempts', ['quiz' => $mod->instance, 'userid' => $user->id, 'state' => 'finished']);
                break;
            }

            if (!$submitted) {
                $url = new moodle_url("/mod/{$mod->modname}/view.php", ['id' => $mod->instance]);
                $links[] = html_writer::link($url, $mod->name, ['class' => 'mod-signoff--link']);
            }
        }
    }
    return $links;
}

function signoff_process_submission($data, $user, $cm) {
    global $DB;

    $update = signoff_has_submission($user,$cm);

    if (!isset($data->user_signature)) $data->user_signature = '';

    // record signoff in the database
    if ($update) {
        $rec = $DB->get_record('signoff_data', ['signoffid' => $cm->instance, 'userid' => $user->id], '*', MUST_EXIST);
        $rec->completed = time();
        $rec->signature = $data->user_signature;
        $DB->upate_record('signoff_data', $rec);
    } else {
        $rec = new stdClass();
        $rec->userid = $user->id;
        $rec->signoffid = $cm->instance;
        $rec->timecreated = time();
        $rec->timemodified = time();
        $rec->completed = time();
        $rec->signature = $data->user_signature;
        $DB->insert_record('signoff_data', $rec);
    }

    // grab contextual information for notification
    $signoff = $DB->get_record('signoff', array('id' => $cm->instance), '*', MUST_EXIST);
    $modulecontext = \context_module::instance($cm->id);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', array('id' => $cm->section), '*', MUST_EXIST);
    $coursecontext = \context_course::instance($cm->course);
    $link = (new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $section->section]))->out();
    $template = str_replace('\n', PHP_EOL, get_string('notify_template', 'signoff', [
        'name' => fullname($user,true),
        'course' => format_string($course->shortname, true, array(
            'context' => $coursecontext,
        )),
        'section' => $section->name . '',
        'activity' => format_string($signoff->name, true, array(
            'context' => $modulecontext,
        )),
        'url' => $link
    ]));


    // process completions
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm)) {
        $mustsubmit = ($signoff->completionsubmit > 0);
        $mustsign = ($signoff->completionsign > 0);
        if ($mustsubmit || ($mustsign && !empty($data->user_signature))) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $user->id);
        } else if ($mustsign && empty($data->user_signature)) {
            $completion->update_state($cm, COMPLETION_INCOMPLETE, $user->id);
        }
    }

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
        email_to_user($to, core_user::get_noreply_user(), get_string('notify_subject', 'signoff'), $template);
    }

}
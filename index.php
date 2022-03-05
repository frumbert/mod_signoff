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
 * List of signoffs in course
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @copyright  Work based on : 2009 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/tablelib.php');

$id = required_param('course', PARAM_INT); // course id
$instance = optional_param('instance', 0, PARAM_INT); // coursemodule

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_signoff\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$signoff       = get_string('modulename', 'signoff');
$signoffs      = get_string('modulenameplural', 'signoff');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/signoff/index.php', array('course' => $course->id, 'instance' => $instance));
$PAGE->set_title($course->shortname.': '.$signoffs);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($signoffs);
echo $OUTPUT->header();
echo $OUTPUT->heading($signoffs);

if (!$activity_instances = get_all_instances_in_course('signoff', $course)) {
    notice(get_string('thereareno', 'moodle', $signoffs), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$table = new flexible_table('signoff_table');
$table->define_baseurl($PAGE->url);
$table->define_columns(['section','activity','picture','fullname','completed','signature']);
$table->define_headers([get_string('section'),get_string('activity'),get_string('pictureofuser'),get_string('fullname'),get_string('completed','signoff'),get_string('signature','signoff')]);
$table->no_sorting('picture');
$table->no_sorting('signature');
$table->sortable(true);
$table->collapsible(true);
$table->pageable(true);
$table->column_class('picture', 'picture');
$table->column_class('fullname', 'bold');
$table->column_class('score', 'bold');
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'signoffs');
$table->set_attribute('class', 'generaltable generalbox');
$table->column_suppress('section');
$table->column_suppress('activity');
$table->setup();
foreach ($activity_instances as $inst) {
    if ($records = $DB->get_records('signoff_data', ['signoffid' => $inst->id])) {
        foreach ($records as $record) {
            $user = get_complete_user_data('id', $record->userid);
            $picture = $OUTPUT->user_picture($user, array('courseid' => $course->id));
            $url = new \moodle_url('/user/view.php', array('id' => $record->userid, 'course' => $course->id));
            $username = \html_writer::link($url, fullname($user));
            $context = context_module::instance($inst->coursemodule);
            $signature = '-';
            if (!empty($record->signature)) {
                $url = new \moodle_url("/pluginfile.php/{$context->id}/mod_signoff/signature/{$record->id}");
                // $signature = \html_writer::img($url, get_string('signature','signoff'),['style'=>'max-width:100%']);
                $signature = \html_writer::link($url, get_string('view_signature','signoff'), ['target'=>'_blank']);
            }

            $row = [];
            $row[] = get_section_name($course,$inst->section);
            $row[] = $inst->label;
            $row[] = $picture;
            $row[] = $username;
            $row[] = userdate($record->completed);
            $row[] = $signature; 

            $table->add_data($row);
        }
    }
}
$table->finish_output();



// // list users under activities inside each section in a nested array
// foreach ($uniq as $section) {
//     $activities = [];
//     $records = [];
//     foreach ($activity_instances as $instance) {
//         if ($instance->section == $section) { // activites in this section
//             $userdata = [];
//             if ($records = $DB->get_records('signoff_data', ['signoffid' => $instance->id])) {
//                 foreach ($records as $record) {
//                     $user = get_complete_user_data('id', $record->userid);
//                     $url = new \moodle_url('/user/view.php', array('id' => $record->userid, 'course' => $course->id));
//                     $userdata[] = [
//                         "userid" => $user->id,
//                         "link" => \html_writer::link($url, fullname($user)),
//                         "picture" =>  $OUTPUT->user_picture($user, array('courseid' => $course->id)),
//                         "completed" => $record->completed,
//                         "signature" => !empty($record->signature)
//                     ];
//                 }
//             }
//             $activities[] = [
//                 "id" => $instance->id,
//                 "label" => $instance->label,
//                 "description" => format_text($instance->intro, $instance->introformat),
//                 "hasusers" => count($userdata) > 0,
//                 "users" => $userdata
//             ];
//         }
//     }
//     $data->section[] = [
//         "name" => get_section_name($course,$section),
//         "hasactivities" => count($activities) > 0,
//         "activities" => $activities
//     ];
// }



// // ready to render
// echo $OUTPUT->render_from_template('mod_signoff/signoffs', $data);

echo $OUTPUT->footer();

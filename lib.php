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
 * Mandatory public API of shared url module
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @copyright  Work based on : 2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in signoff module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function signoff_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:             return MOD_ARCHETYPE_OTHER;
        case FEATURE_IDNUMBER:                  return false;
        case FEATURE_GROUPS:                    return false;
        case FEATURE_GROUPINGS:                 return false;
        case FEATURE_MOD_INTRO:                 return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:   return true;
        case FEATURE_COMPLETION_HAS_RULES:      return true;
        case FEATURE_GRADE_HAS_GRADE:           return false;
        case FEATURE_GRADE_OUTCOMES:            return false;
        case FEATURE_BACKUP_MOODLE2:            return true;
        case FEATURE_SHOW_DESCRIPTION:          return true;
        case FEATURE_ADVANCED_GRADING:          return false;
        case FEATURE_PLAGIARISM:                return false;
        case FEATURE_COMMENT:                   return false;
        default: return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function signoff_reset_userdata($data)
{
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function signoff_get_view_actions()
{
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function signoff_get_post_actions()
{
    return array('update', 'add');
}

/**
 * Add signoff instance.
 * @param object $data (modinfo)
 * @param object $mform
 * @return int new url instance id
 */
function signoff_add_instance($data, $mform)
{

    global $DB;

    // if ($DB->record_exists('signoff', ['course' => (int)$data->course])) return 0;

    $rec = new stdClass();
    $rec->timecreated = time();
    $rec->timemodified = time();
    $rec->name = $data->name;
    $rec->label = $data->label;
    $rec->intro = $data->intro;
    $rec->introformat = (int)$data->introformat;
    $rec->show_signature = isset($data->require_signature) ? ($data->require_signature === "1") : false;
    $rec->notify_self =  isset($data->notify_self) ? ($data->notify_self === "1") : false;
    $rec->notify_teacher =  isset($data->notify_teacher) ? ($data->nitofy_teacher === "1") : true;
    $rec->activity = $data->coursemodule;
    $rec->course = (int)$data->course;
    $rec->id = $DB->insert_record("signoff", $rec);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'signoff', $rec, $completiontimeexpected);

    return $rec->id;

}

/**
 * Update instance. as long as field names match db, this should work
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function signoff_update_instance($data, $mform)
{
    global $CFG, $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    $DB->update_record('signoff', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'signoff', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete shared signoff instance.
 * @param int $id
 * @return bool true
 */
function signoff_delete_instance($id)
{
    global $DB;

    if (!$rec = $DB->get_record('signoff', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('signoff', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'signoff', $id, null);

    // note: all context files are deleted automatically

    $DB->delete_records('signoff', array('id' => $rec->id));
    $DB->delete_records('signoff_data', array('signoffid' => $rec->id));

    return true;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param stdClass $url url object
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 */
function signoff_view($url, $course, $cm, $context)
{

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $url->id
    );

    $event = \mod_signoff\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('signoff', $url);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Serves 3rd party js files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function mod_signoff_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB;

    $pluginpath = $CFG->dirroot.'/mod/signoff/';

    if ($filearea === 'vendorjs') {
        // Typically CDN fall backs would go in vendorjs.
        $path = $pluginpath.'vendorjs/'.implode('/', $args);
        echo file_get_contents($path);
        die;

    } else if ($filearea === 'signature') {
        // stored in the db in this version - a future release may use the file system
        if ($data = $DB->get_record('signoff_data', array('id' => array_pop($args)), 'signature', IGNORE_MISSING)) {
            if (strpos($data->signature, ';base64,') !== false) {
                $mime = substr($data->signature, 5, strpos($data->signature, ';base64,') - 5);
                $uri = 'data://' . substr($data->signature, 5); // Neat! https://stackoverflow.com/a/6735458/1238884
                $binary = file_get_contents($uri);
                header('Content-Type: ' . $mime);
                echo $binary;
                die();
            }
        }
        die("unfounded");
    } else {
        die('unsupported file area');
    }
    die;
}

function remove_signoff_data($rowid) {
    global $DB;
    $DB->delete_records('signoff_data', array('id' => $rowid));
}

/**
 * Obtains the automatic completion state for this signoff based on any conditions
 * in scorm settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function signoff_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    $result = $type;

    // Get signoff.
    if (!$signoff = $DB->get_record('signoff', array('id' => $cm->instance))) {
        print_error('cannotfindsignoff');
    }

    // signature submitted
    if ($signoff->completionsign == "1") {
        $record = $DB->get_record('signoff_data',['signoffid' => $signoff->id, 'userid' => $userid]);
        if ($record && !empty($record->signature)) {
            return completion_info::aggregate_completion_states($type, $result, true);
        } else {
            return completion_info::aggregate_completion_states($type, $result, false);
        }
    }

    // record submitted
    if ($signoff->completionsubmit == "1") {
        $record = $DB->get_record('signoff_data',['signoffid' => $signoff->id, 'userid' => $userid]);
        if ($record) {
            return completion_info::aggregate_completion_states($type, $result, true);
        } else {
            return completion_info::aggregate_completion_states($type, $result, false);
        }
    }

    return $type;
}

/**
 * Sets activity completion state
 *
 * @param object $signoff object
 * @param int $userid User ID
 * @param int $completionstate Completion state
 * @param array $grades grades array of users with grades - used when $userid = 0
 */
function signoff_set_completion($signoff, $userid, $completionstate = COMPLETION_COMPLETE, $grades = array()) {
    $course = new stdClass();
    $course->id = $signoff->course;
    $completion = new completion_info($course);

    // Check if completion is enabled site-wide, or for the course.
    if (!$completion->is_enabled()) {
        return;
    }

    $cm = get_coursemodule_from_instance('signoff', $signoff->id, $signoff->course);
    if (empty($cm) || !$completion->is_enabled($cm)) {
            return;
    }

    if (empty($userid)) { // We need to get all the relevant users from $grades param.
        foreach ($grades as $grade) {
            $completion->update_state($cm, $completionstate, $grade->userid);
        }
    } else {
        $completion->update_state($cm, $completionstate, $userid);
    }
}

function signoff_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, notify_self, notify_teacher, completionsubmit, completionsign';
    if (!$signoff = $DB->get_record('signoff', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $signoff->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('signoff', $signoff, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $signoff->completionsubmit;
        $result->customdata['customcompletionrules']['completionsign'] = $signoff->completionsign;
    }

    // Populate some other values that can be used in calendar or on dashboard.
    if ($signoff->notify_self) {
        $result->customdata['notify_self'] = $signoff->notify_self;
    }
    if ($signoff->notify_teacher) {
        $result->customdata['notify_teacher'] = $signoff->notify_teacher;
    }

    return $result;

}

function mod_signoff_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsubmitdesc', 'signoff', $val);
                }
                break;
            case 'completionreplies':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsigndesc', 'signoff', $val);
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}
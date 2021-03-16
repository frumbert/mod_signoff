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
        case FEATURE_COMPLETION_TRACKS_VIEWS:   return false;
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

    global $DB, $CFG;

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

class mod_signoff_report {

    public function __construct($courseid, $context, $page = 0, $sortitemid = 0) {

    }


   /**
     * Given the name of a user preference (without grade_report_ prefix), locally saves then returns
     * the value of that preference. If the preference has already been fetched before,
     * the saved value is returned. If the preference is not set at the User level, the $CFG equivalent
     * is given (site default).
     * Can be called statically, but then doesn't benefit from caching
     * @param string $pref The name of the preference (do not include the grade_report_ prefix)
     * @param int $objectid An optional itemid or categoryid to check for a more fine-grained preference
     * @return mixed The value of the preference
     */
    public function get_pref($pref, $objectid=null) {
        global $CFG;
        $fullprefname = 'mod_signoff_' . $pref;
        $shortprefname = 'signoff_' . $pref;

        $retval = null;

        if (!isset($this) OR get_class($this) != 'mod_signoff') {
            if (!empty($objectid)) {
                $retval = get_user_preferences($fullprefname . $objectid, self::get_pref($pref));
            } else if (isset($CFG->$fullprefname)) {
                $retval = get_user_preferences($fullprefname, $CFG->$fullprefname);
            } else if (isset($CFG->$shortprefname)) {
                $retval = get_user_preferences($fullprefname, $CFG->$shortprefname);
            } else {
                $retval = null;
            }
        } else {
            if (empty($this->prefs[$pref.$objectid])) {

                if (!empty($objectid)) {
                    $retval = get_user_preferences($fullprefname . $objectid);
                    if (empty($retval)) {
                        // No item pref found, we are returning the global preference
                        $retval = $this->get_pref($pref);
                        $objectid = null;
                    }
                } else {
                    $retval = get_user_preferences($fullprefname, $CFG->$fullprefname);
                }
                $this->prefs[$pref.$objectid] = $retval;
            } else {
                $retval = $this->prefs[$pref.$objectid];
            }
        }

        return $retval;
    }

    /**
     * Uses set_user_preferences() to update the value of a user preference. If 'default' is given as the value,
     * the preference will be removed in favour of a higher-level preference.
     * @param string $pref The name of the preference.
     * @param mixed $pref_value The value of the preference.
     * @param int $itemid An optional itemid to which the preference will be assigned
     * @return bool Success or failure.
     */
    public function set_pref($pref, $pref_value='default', $itemid=null) {
        $fullprefname = 'mod_signoff' . $pref;
        if ($pref_value == 'default') {
            return unset_user_preference($fullprefname.$itemid);
        } else {
            return set_user_preference($fullprefname.$itemid, $pref_value);
        }
    }

    /**
     * Fetches and returns a count of all the users that will be shown on this page.
     * @param boolean $groups include groups limit
     * @param boolean $users include users limit - default false, used for searching purposes
     * @return int Count of users
     */
    public function get_numusers($groups = true, $users = false) {
        global $CFG, $DB;
        $userwheresql = "";
        $groupsql      = "";
        $groupwheresql = "";

        // Limit to users with a gradeable role.
        list($gradebookrolessql, $gradebookrolesparams) = $DB->get_in_or_equal(explode(',', $this->gradebookroles), SQL_PARAMS_NAMED, 'grbr0');

        // Limit to users with an active enrollment.
        list($enrolledsql, $enrolledparams) = get_enrolled_sql($this->context);

        // We want to query both the current context and parent contexts.
        list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($this->context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');

        $params = array_merge($gradebookrolesparams, $enrolledparams, $relatedctxparams);

        if ($users) {
            $userwheresql = $this->userwheresql;
            $params       = array_merge($params, $this->userwheresql_params);
        }

        if ($groups) {
            $groupsql      = $this->groupsql;
            $groupwheresql = $this->groupwheresql;
            $params        = array_merge($params, $this->groupwheresql_params);
        }

        $sql = "SELECT DISTINCT u.id
                       FROM {user} u
                       JOIN ($enrolledsql) je
                            ON je.id = u.id
                       JOIN {role_assignments} ra
                            ON u.id = ra.userid
                       $groupsql
                      WHERE ra.roleid $gradebookrolessql
                            AND u.deleted = 0
                            $userwheresql
                            $groupwheresql
                            AND ra.contextid $relatedctxsql";
        $selectedusers = $DB->get_records_sql($sql, $params);

        $count = 0;
        // Check if user's enrolment is active and should be displayed.
        if (!empty($selectedusers)) {
            $coursecontext = $this->context->get_course_context(true);

            $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol);
            $showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol);
            $showonlyactiveenrol = $showonlyactiveenrol || !has_capability('moodle/course:viewsuspendedusers', $coursecontext);

            if ($showonlyactiveenrol) {
                $useractiveenrolments = get_enrolled_users($coursecontext, '', 0, 'u.id',  null, 0, 0, true);
            }

            foreach ($selectedusers as $id => $value) {
                if (!$showonlyactiveenrol || ($showonlyactiveenrol && array_key_exists($id, $useractiveenrolments))) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Sets up this report's user criteria to restrict the selection of users to display.
     */
    public function setup_users() {
        global $SESSION, $DB;

        $this->userwheresql = "";
        $this->userwheresql_params = array();
        if (isset($SESSION->gradereport['filterfirstname']) && !empty($SESSION->gradereport['filterfirstname'])) {
            $this->userwheresql .= ' AND '.$DB->sql_like('u.firstname', ':firstname', false, false);
            $this->userwheresql_params['firstname'] = $SESSION->gradereport['filterfirstname'].'%';
        }
        if (isset($SESSION->gradereport['filtersurname']) && !empty($SESSION->gradereport['filtersurname'])) {
            $this->userwheresql .= ' AND '.$DB->sql_like('u.lastname', ':lastname', false, false);
            $this->userwheresql_params['lastname'] = $SESSION->gradereport['filtersurname'].'%';
        }
    }

    /**
     * Returns an arrow icon inside an <a> tag, for the purpose of sorting a column.
     * @param string $direction
     * @param moodle_url $sortlink
     */
    protected function get_sort_arrow($direction='move', $sortlink=null) {
        global $OUTPUT;
        $pix = array('up' => 't/sort_desc', 'down' => 't/sort_asc', 'move' => 't/sort');
        $matrix = array('up' => 'desc', 'down' => 'asc', 'move' => 'desc');
        $strsort = $this->get_lang_string('sort' . $matrix[$direction]);

        $arrow = $OUTPUT->pix_icon($pix[$direction], $strsort, '', array('class' => 'sorticon'));
        return html_writer::link($sortlink, $arrow, array('title'=>$strsort));
    }

}
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
 * Strings for component 'signoff', language 'en'
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Sign Off';
$string['modulename'] = 'Sign Off';
$string['modulename_link'] = 'mod/signoff/view';
$string['modulename_help'] = 'Sign Off module allows the learner to notify the teacher that the they have completed the set of conditions leading to this modules release, for instance a series of assignments or essay-type quizzes.'; // TODO ' It optionally also allows the user to input their signature.';
$string['pluginadministration'] = 'Sign Off administration';
$string['completionsubmit'] = 'Student must submit this activity to complete it';
$string['completionsign'] = 'Student must sign and submit this activity to complete it';

$string['modulenameplural'] = 'Sign Offs';
$string['button_label'] = 'Signoff button text';
$string['button_label_default'] = 'Sign off unit';

$string['notify_self'] = 'Notify self?';
$string['notify_teachers'] = 'Notify teacher(s)?';
$string['show_signature'] = 'Show signature?';
$string['signature'] = 'Signature';
$string['view_signature'] = 'View signature';
$string['feedback'] = '{$a} signed this unit off on {$b}';
$string['completed'] = 'Completed on';
$string['signature_label'] = 'Sign here';
$string['undo'] = 'Undo';
$string['clear'] = 'Clear';
$string['instructions'] = 'Use mouse or finger to draw your signature';

$string['completionsubmitdesc'] = 'Must be submitted';
$string['completionsigndesc'] = 'Must be signed and submitted';
$string['remove'] = 'Remove record';

// Capabilities
$string['signoff:addinstance'] = 'Add a new signoff resource';
$string['signoff:view'] = 'View Sign Off';

$string['privacy:metadata'] = 'The Sign Off plugin does not store any personal data.';
$string['messageprovider:emailnotify'] = 'Signoff activity notifications';

$string['self_signedoff'] = 'You submitted this activity on {$a}.';
$string['view_submissions'] = 'View all submissions';

$string['notify_subject'] = 'Signoff notification';
$string['notify_template'] = 'A user has signed off an activity.\n\nUser: {$a->name} \nCourse: {$a->course} \nSection: {$a->section} \nActivity: {$a->activity} \nUrl: {$a->url} \n';

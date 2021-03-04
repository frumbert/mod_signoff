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
 * Shared signoff module admin settings and defaults
 *
 * @package    mod_signoff
 * @copyright  2021 tim st.clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    // $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
    //                                                        RESOURCELIB_DISPLAY_EMBED,
    //                                                        RESOURCELIB_DISPLAY_FRAME,
    //                                                        RESOURCELIB_DISPLAY_OPEN,
    //                                                        RESOURCELIB_DISPLAY_NEW,
    //                                                        RESOURCELIB_DISPLAY_POPUP,
    //                                                       ));
    // $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
    //                                RESOURCELIB_DISPLAY_EMBED,
    //                                RESOURCELIB_DISPLAY_OPEN,
    //                                RESOURCELIB_DISPLAY_POPUP,
    //                               );

    // //--- general settings -----------------------------------------------------------------------------------
    // $settings->add(new admin_setting_configtext('signoff/framesize',
    //     get_string('framesize', 'signoff'), get_string('configframesize', 'signoff'), 130, PARAM_INT));
    // $settings->add(new admin_setting_configmultiselect('signoff/displayoptions',
    //     get_string('displayoptions', 'signoff'), get_string('configdisplayoptions', 'signoff'),
    //     $defaultdisplayoptions, $displayoptions));

    // //--- modedit defaults -----------------------------------------------------------------------------------
    // $settings->add(new admin_setting_heading('signoffmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    // $settings->add(new admin_setting_configcheckbox('signoff/printintro',
    //     get_string('printintro', 'signoff'), get_string('printintroexplain', 'signoff'), 1));
    // $settings->add(new admin_setting_configselect('signoff/display',
    //     get_string('displayselect', 'signoff'), get_string('displayselectexplain', 'signoff'), RESOURCELIB_DISPLAY_AUTO, $displayoptions));
    // $settings->add(new admin_setting_configtext('signoff/popupwidth',
    //     get_string('popupwidth', 'signoff'), get_string('popupwidthexplain', 'signoff'), 620, PARAM_INT, 7));
    // $settings->add(new admin_setting_configtext('signoff/popupheight',
    //     get_string('popupheight', 'signoff'), get_string('popupheightexplain', 'signoff'), 450, PARAM_INT, 7));
}

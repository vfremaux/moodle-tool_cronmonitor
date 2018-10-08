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
 * @package     tool_cronmonitor
 * @category    tool
 * @copyright   2016 Valery Fremaux <valery@edunao.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We have a proper output. Analyse.

function send_notifications($outputstr, $options) {
    global $CFG, $DB, $SITE;

    function tail($str, $num) {
        $lines = explode("\n", $str);
        $totallines = count($lines);
        $buffer = array_slice($lines, $totalines - $num);
        return implode("\n", $buffer);
    }

    $notification = '';
    if (empty($outputstr)) {
        $faulttype = 'EMPTY';
        $notification = '['.$CFG->wwwroot.'] Empty cron output. This is NOT an expected situation and may denote cron execution silent failure'."\n";
    } else {

        if (preg_match('/Cron script completed correctly/', $outputstr)) {
            assert(true);
        } else if (preg_match('/Moodle upgrade pending, cron execution suspended./', $outputstr)) {
            $faulttype = 'UPGRADE';
            $notification = '['.$CFG->wwwroot.'] Unresolved upgrade pending.'."\n";
        } else if (preg_match('/Fatal error(.*)/', $outputstr, $matches)) {
            $faulttype = 'PHP ERROR';
            $notification = '['.$CFG->wwwroot.'] Fatal error in cron : '.$matches[0];
        } else if (preg_match('/Error code: cronerrorpassword/', $outputstr)) {
            $faulttype = 'PASSWORD ERROR';
            $notification = '['.$CFG->wwwroot.'] cron locked by password.'."\n";
        } else {
            $faulttype = 'OTHER ERROR';
            $notification = '['.$CFG->wwwroot.'] cron has some unclassified error. The end of the script is:'."\n\n";
            $notification .= tail($outputstr, 10);
        }
    }

    // We have some notifications.

    $targets = tool_cronmonitor_get_notification_targets();
    tool_cronmonitor_notify($targets, $faulttype, $notification);
    echo $notification;
}

function tool_cronmonitor_get_notification_targets() {
    global $DB, $CFG;

    $userstosendto = $DB->get_field('config_plugins', 'value', array('plugin' => 'tool_cronmonitor', 'name' => 'userstosendto'));

    $targets = array();
    if (empty($userstosendto)) {
        echo('Sending to default targets'."\n");
        $targets = $DB->get_records_list('user', 'id', explode(',', $CFG->siteadmins));
        foreach ($targets as $u) {
            echo "\t".fullname($u)."\n";
        }
    } else {
        $usernames = explode(',', $userstosendto);
        foreach ($usernames as $un) {

            $un = trim($un);

            if (strpos($un, '@') !== false) {
                // This is an email.
                echo('Targetting cronmon to email target '.$un."\n");
                $u = $DB->get_record('user', array('email' => $un, 'mnethostid' => $CFG->mnet_localhost_id));
            } else if (is_numeric($un)) {
                // This is an id.
                echo('Targetting cronmon to id target '.$un."\n");
                $u = $DB->get_record('user', array('id' => $un));
            } else {
                // This is a username.
                echo('Targetting cronmon to username target '.$un."\n");
                $u = $DB->get_record('user', array('username' => $un));
            }
            if ($u) {
                echo "\t".fullname($u)."\n";
                $targets[$u->id] = $u;
            }
        }
    }

    return $targets;
}

function tool_cronmonitor_notify(&$targets, $faulttype, $notification) {
    global $DB, $SITE;

    if (empty($targets)) {
        echo "No notification targets... aborting.\n";
        return;
    }

    $positivemail = $DB->get_field('config_plugins', 'value', array('plugin' => 'tool_cronmonitor', 'name' => 'positivemail'));
    if (!empty($notification)) {

        echo $faulttype."\n";
        echo $notification."\n";

        foreach ($targets as $a) {
            email_to_user($a, $a, '['.$SITE->shortname.':'.$faulttype.'] Cron Monitoring system', $notification);
        }
    } else if ($positivemail) {
        echo "OK.\n";
        if (!empty($targets)) {
            foreach ($targets as $a) {
                email_to_user($a, $a, '['.$SITE->shortname.' CRON OK] Cron Monitoring system', 'Everything fine');
            }
        }
    }
}

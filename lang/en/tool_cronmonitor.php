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
 *
 * @package    tool_cronmonitor
 * @category   tool
 * @author     Valery Fremaux <valery.fremaux@club-internet.fr>
 * @copyright  (C) 2008 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

$string['privacy:metadata'] = "The cronmonitor tool do not manipulate any data belonging to users";

$string['pluginname'] = 'Cron monitor';
$string['configpositivemail'] = 'Send positive mail';
$string['configpositivemail_desc'] = 'If enabled, sends a mail even on check success';
$string['configuserstosendto'] = 'Usernames to send to';
$string['configuserstosendto_desc'] = '
Give a coma separated list of usernames, or full qualified username@mnethostid identities
to send mails to. when empty, will notify all site admins.
';

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
 * @package    tool_cronmonitor
 * @category   tool
 * @copyright  2016 Valery Fremaux <valery@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (is_dir($CFG->dirroot.'/local/adminsettings')) {
    list($hasconfig, $hassiteconfig, $capability) = local_adminsettings_access();
} else {
    // Standard Moodle code
    $capability = 'moodle/site:config';
    $hasconfig = $hassiteconfig = has_capability($capability, context_system::instance());
}

if ($hassiteconfig) {
    // Global limits
    // Detailed per context might override
    // Capability enabled people might override
    $settings = new admin_settingpage('tool_cronmonitor', get_string('pluginname', 'tool_cronmonitor'));
    $ADMIN->add('tools', $settings);

    $settings->add(new admin_setting_configcheckbox('tool_cronmonitor/positivemail', get_string('configpositivemail', 'tool_resourcelimiter'), get_string('configpositivemail_desc', 'tool_resourcelimiter'), 0));

    $settings->add(new admin_setting_configtext('tool_cronmonitor/userstosendto', get_string('configuserstosendto', 'tool_resourcelimiter'), get_string('configuserstosendto_desc', 'tool_resourcelimiter'), 0));
}


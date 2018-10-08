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

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/lib/clilib.php'); // Cli only functions.

// Now get cli options.

list($options, $unrecognized) = cli_get_params(array('help' => false,
                                                     'file' => false,
                                                     'mode' => false,
                                                     'debug' => false,
                                                     'host' => false,
                                                     'user' => false),
                                               array('h' => 'help',
                                                     'H' => 'host',
                                                     'm' => 'mode',
                                                     'd' => 'debug',
                                                     'f' => 'file',
                                                     'u' => 'user')
                                               );

if ($unrecognized) {
    $unrecognized = implode("\n ", $unrecognized);
    cli_error($unrecognized. " is not a recognized option\n");
}

if ($options['help']) {
    $help = "
Monitors the platform cron and checks ts sanity. Mails an alert if blocked or erroneous.

Options:
-h, --help            Print out this help
-H, --host            The virtual moodle to play for. Main moodle install if missing.
-m, --mode            Mode (web, cli or vcli)
-d, --debug           Debug mode
-f, --file            If file is given, will check the cron result in the given file. If not, the monitor will fire
a cron execution to get the cron result.
-u, --user            the system user that operates the script (via sudo). www-data as default.

Example in crontab :
0 */4 * * * /usr/bin/php admin/tool/cronmonitor/cli/cron.php -f /var/log/moodlecrons/moodle.myorg.org.cron.log
";

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

require_once($CFG->dirroot.'/admin/tool/cronmonitor/lib.php'); // Common cronmonitor libs.

if (empty($options['user'])) {
    $options['user'] = 'www-data';
}

if (empty($options['mode'])) {
    $options['mode'] = 'web';
}

$debugcli = '';
if (!empty($options['debug'])) {
    $debugcli = ' --debug ';
}

if (!empty($options['file'])) {
    // We designated a file where the cron log was output.
    if (!file_exists($options['file'])) {
        die('Error reading output file');
    }

    $outputstr = implode('', file($options['file']));
} else {
    if ($options['mode'] == 'cli') {
        // We play a cli cron.
        $cmd = 'sudo -u '.$options['user'].' /usr/bin/php '.$CFG->dirroot.'/admin/cli/cron.php';

        echo 'Executing moodle cron';
        $execres = exec($cmd, $output);
        $outputstr = implode('', $output);
    } else if ($options['mode'] == 'vcli') {
        // We play a vmoodle cli cron in a vmoodle environment.
        $cmd = 'sudo -u '.$options['user'].' ';
        $cmd .= '/usr/bin/php ';
        $cmd .= $CFG->dirroot.'/local/vmoodle/cli/cron.php --host="'.$options['host'].'" ';
        $cmd .= $debugcli;

        echo 'Executing vmoodle cron '.$cmd."\n";
        $execres = exec($cmd, $output);
        $outputstr = implode('', $output);
    } else {
        // We play a web curl cron.
        $params = array();

        if (!empty($CFG->cronremotepassword)) {
            $params = array('password' => $CFG->cronremotepassword);
        }

        $url = new moodle_url('/admin/cron.php', $params);
        $ch = curl_init();

        // Set URL and other appropriate options.
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml charset=UTF-8"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if (!empty($CFG->proxyhost)) {
            if (empty($CFG->proxyport)) {
                $proxyhost = $CFG->proxyhost;
            } else {
                $proxyhost = $CFG->proxyhost.':'.$CFG->proxyport;
            }
            curl_setopt($ch, CURLOPT_PROXY, $proxyhost);

            if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
                $proxyauth = $CFG->proxyuser.':'.$CFG->proxypassword;
                curl_setopt($ch, CURL_AUTHHTTP, CURLAUTH_BASIC);
                curl_setopt($ch, CURL_PROXYAUTH, $proxyauth);
            }

            if (!empty($CFG->proxytype)) {
                if ($CFG->proxytype == 'SOCKS5') {
                    $proxytype = CURLPROXY_SOCKS5;
                } else {
                    $proxytype = CURLPROXY_HTTP;
                }
                curl_setopt($ch, CURL_PROXYTYPE, $proxytype);
            }
        }

        $outputstr = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpcode != 200) {
            $faulttype = 'HTTP RETURN ERROR';
            $notification = '['.$CFG->wwwroot.'] CURL HTTP error on '.$url;
        } else if (!empty($error)) {
            $faulttype = 'HTTP FETCH ERROR';
            $notification = '['.$CFG->wwwroot.'] CURL error on '.$url;
        }

        // Close cURL resource, and free up system resources.
        curl_close($ch);

        if (!empty($notification)) {
            $targets = tool_cronmonitor_get_notification_targets();
            tool_cronmonitor_notify($targets, $notification);
            die("Done.\n");
        }
    }
}

send_notifications($outputstr, $options);
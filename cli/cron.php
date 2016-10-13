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

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->libdir.'/clilib.php'); // Cli only functions.
require_once($CFG->dirroot.'/admin/tool/cronmonitor/lib.php'); // Common cronmonitor libs.

// Now get cli options.

list($options, $unrecognized) = cli_get_params(array('help' => false,
                                                     'file' => false,
                                                     'mode' => false,
                                                     'user' => false),
                                               array('h' => 'help',
                                                     'm' => 'mode',
                                                     'f' => 'file',
                                                     'u' => 'user')
                                               );

if ($unrecognized) {
    $unrecognized = implode("\n ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Monitors the platform cron and checks ts sanity. Mails an alert if blocked or erroneous.

Options:
-h, --help            Print out this help
-m, --mode            Mode (web or cli)
-f, --file            If file is given, will check the cron result in the given file. If not, the monitor will fire
a cron execution to get the cron result.
-u, --user            the system user that operates the script (via sudo). www-data as default.

Example in crontab :
0 */4 * * * /usr/bin/php admin/tool/cronmonitor/cli/cron.php
";

    echo $help;
    die;
}

if (empty($options['user'])) {
    $options['user'] = 'www-data';
}

if (empty($options['mode'])) {
    $options['mode'] = 'web';
}

if (!empty($options['file'])) {
    if (!file_exists($options['file'])) {
        die('Error reading output file');
    }

    $output = implode('', file($options['file']));
} else {
    if ($options['mode'] == 'cli') {
        $cmd = 'sudo -u '.$options['user'].' /usr/bin/php '.$CFG->dirroot.'/admin/cli/cron.php';

        echo 'Executing moodle cron';
        $execres = exec($cmd, $output);
        $outputstr = implode('', $ouptut);
    } else {
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
    }
}

send_notifications($outputstr, $options);
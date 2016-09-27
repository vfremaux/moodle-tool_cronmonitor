<?php

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->libdir.'/clilib.php'); // Cli only functions.

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
    $help =
"Monitors the platform cron and checks ts sanity. Mails an alert if blocked or erroneous.

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

        $execres = exec($cmd, $output);
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
                $proxy_host = $CFG->proxyhost;
            } else {
                $proxy_host = $CFG->proxyhost.':'.$CFG->proxyport;
            }
            curl_setopt($ch, CURLOPT_PROXY, $proxy_host);

            if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
                $proxy_auth = $CFG->proxyuser.':'.$CFG->proxypassword;
                curl_setopt($ch, CURL_AUTHHTTP, CURLAUTH_BASIC);
                curl_setopt($ch, CURL_PROXYAUTH, $proxy_auth);
            }

            if (!empty($CFG->proxytype)) {
                if ($CFG->proxytype == 'SOCKS5') {
                    $proxy_type = CURLPROXY_SOCKS5;
                } else {
                    $proxy_type = CURLPROXY_HTTP;
                }
                curl_setopt($ch, CURL_PROXYTYPE, $proxy_type);
            }
        }

        $output = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpcode != 200) {
            $faulttype = 'HTTP RETURN ERROR';
            $notification = '['.$CFG->wwwroot.'] CURL HTTP error on '.$url;
        }
        else if (!empty($error)) {
            $faulttype = 'HTTP FETCH ERROR';
            $notification = '['.$CFG->wwwroot.'] CURL error on '.$url;
        }

        // Close cURL resource, and free up system resources.
        curl_close($ch);
    }
}

// We have a proper output. Analyse.

$notification = '';
if (empty($output)) {
    $faulttype = 'EMPTY';
    $notification = '['.$CFG->wwwroot.'] Empty cron output. This is NOT an expected situation and may denote cron execution silent failure';
} else {

    if (preg_match('/Cron script completed correctly/', $output)) {
        die('Cron OK'."\n");
    }

    else if (preg_match('/Moodle upgrade pending, cron execution suspended./', $output)) {
        $faulttype = 'UPGRADE';
        $notification = '['.$CFG->wwwroot.'] Unresolved upgrade pending.';
    }

    else if (preg_match('/Fatal error/', $output)) {
        $faulttype = 'PHP ERROR';
        $notification = '['.$CFG->wwwroot.'] Fatal error in cron.';
    }

    elseif (!preg_match('/Error code: cronerrorpassword/', $output)) {
        $faulttype = 'PASSWORD ERROR';
        $notification = '['.$CFG->wwwroot.'] cron locked bvy password.';
    }

    else {
        $faulttype = 'OTHER ERROR';
        $notification = '['.$CFG->wwwroot.'] cron has some unclassified error.';
    }
}

// We have some notifications.

$config = get_config('tool_cronmonitor');

$targets = array();
if (empty($config->userstosendto)) {
    $targets = $DB->get_records_list('user', 'id', explode(',',$CFG->siteadmins));
} else {
    $usernames = explode(','; $config->userstosendto);
    foreach ($usernames as $un) {
        $un = trim($un);
        $u = $DB->get_record('user', array('id' => $un));
        $targets[$u->id] = $u;
    }
}

if (!empty($notification)) {

    mtrace('Mode: '.$options['mode']);
    mtrace($faulttype);
    mtrace($notification);

    foreach($targets as $a) {
        email_to_user($a, $a, '['.$SITE->shortname.':'.$faulttype.'] Cron Monitoring system', $notification);
    }
} else if ($config->positivemail) {
    if (!empty($targets)) {
        foreach ($targets as $a) {
            email_to_user($a, $a, '['.$SITE->shortname.' CRON OK] Cron Monitoring system', 'Everything fine');
        }
    }
}
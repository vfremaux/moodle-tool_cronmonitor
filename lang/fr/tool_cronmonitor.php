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

$string['pluginname'] = 'Moniteur de cron';
$string['configpositivemail'] = 'Envoyer les notifications positives';
$string['configpositivemail_desc'] = 'Si activ�, le moniteur �met un coaurriel m�me en cas de succ�s de la v�rification';
$string['configuserstosendto'] = 'Utilisateurs � notifier';
$string['configuserstosendto_desc'] = '
Donnez une liste s�par�e par des virgules d\'identifants utilisateur, ou une entr�e compl�tement qualifi�e <username>@<mnethostid>.
Si la liste est vide, tous les administrateurs de site seront notifi�s.
';

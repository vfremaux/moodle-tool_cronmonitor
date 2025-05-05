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
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright  (C) 2008 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

$string['pluginname'] = 'Moniteur de cron';
$string['configpositivemail'] = 'Envoyer les notifications positives';
$string['configpositivemail_desc'] = 'Si activé, le moniteur émet un courriel même en cas de succès de la vérification';
$string['configsavecronfailures'] = 'Sauvegarder les erreurs de cron';
$string['configsavecronfailures_desc'] = 'Si activé, les sorties de cron en erreur sont sauvegardées intégralement dans le répertoire <dataroo>/cronfails. Ceci permet d\'examiner la sortie complète du cron en erreur.';
$string['configcronfailuresmaxfiles'] = 'Nombre max de sauvgardes d\'erreur de cron';
$string['configcronfailuresmaxfiles_desc'] = 'Le nombre maximum de fichiers à sauvegarder. Le fichier le plus ancien sera supprimé. 0 pour ne pas limiter.';
$string['configuserstosendto'] = 'Utilisateurs à notifier';
$string['configuserstosendto_desc'] = '
Donnez une liste séparée par des virgules d\'identifants utilisateur, ou une entrée complètement qualifiée <username>@<mnethostid>.
Si la liste est vide, tous les administrateurs de site seront notifiés.
';

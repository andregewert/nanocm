<?php

/* 
 * Copyright (C) 2017 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ubergeek\NanoCm\Module;

use Ubergeek\DatabaseUpdater\Updater;
use Ubergeek\DatabaseUpdater\SqliteDatabase;
use Ubergeek\NanoCm\NanoCm;
use Ubergeek\NanoCm\Setting;
use Ubergeek\NanoCm\StatusCode;
use Ubergeek\NanoCm\User;
use Ubergeek\NanoCm\UserType;
use Ubergeek\NanoCm\Util;

/**
 * Kapselt ein einfaches Setup für die Ersteinrichtung des NanoCM.
 * 
 * Der zentrale FrontController prüft bei seiner Ausführung, ob bereits eine
 * konfigurierte Datenbank vorhanden ist. Wenn das nicht der Fall ist, wird
 * immer das SetupModule ausgeführt. Das Setup besteht aus einem simplen
 * Formular, in das die grundlegendsten Einstellungen vorgenommen werden müssen.
 * Das Setup erstreckt über lediglich eine einzelne Seite. Werden die Eingaben
 * bestätigt, wird sofort die Datenbank erstellt und mit den gemachten Eingaben
 * gefüllt. Ab diesem Zeitpunkt sollte das SetupModule nicht wieder aufgerufen
 * werden (können).
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-12
 */
class SetupModule extends AbstractModule {

    // <editor-fold desc="Properties">

    /** @var string Generierter Content */
    private $content = null;

    /** @var string[] A list of missing php modules / extensions */
    public $missingPhpModules = array();

    // </editor-fold>


    // <editor-fold desc="ControllerInterface">

    public function init() {
        $this->allowUserTemplates = false;
        $this->templateDir = Util::createPath($this->ncm->sysdir, 'tpladm');
        $this->setPageTemplate('page-setup.phtml');
        $this->replaceMeta('Cache-control', 'no-cache, no-store, must-revalidate, max-age=0');
        $this->replaceMeta('Pragma', 'no-cache');
    }

    public function run() {
        if ($this->getAction() == 'save') {

            // TODO Error handling!

            // TODO Requirements should be checked
            // Result should be shown on the setup page

            // Requirements are:
            // PHP modules
            // sys directory has to be writable
            $this->missingPhpModules = $this->checkRequiredPhpModules();

            // Datenbank einrichten
            $updater = new Updater(
                Util::createPath($this->ncm->sysdir, 'db', 'versions'),
                new SqliteDatabase(Util::createPath($this->ncm->sysdir, 'db')),
                $this->log
            );
            $updater->updateDatabaseToLatestVersion();

            // Admin-User anlegen
            $user = new User();
            $user->firstname = $this->getParam('webmaster_firstname');
            $user->lastname = $this->getParam('webmaster_lastname');
            $user->username = $this->getParam('admin_name');
            $user->email = $this->getParam('webmaster_email');
            $user->password = $this->getParam('admin_password1');
            $user->status_code = StatusCode::ACTIVE;
            $user->usertype = UserType::ADMIN;
            $this->ncm->orm->saveUser($user);
            $this->ncm->orm->setUserPasswordByUsername($user->username, $user->password);

            // Basiseinstellungen speichern
            $this->ncm->orm->setSettingValue(Setting::SYSTEM_LANG, $this->getParam('lang'));
            $this->ncm->orm->setSettingValue(Setting::SYSTEM_SITETITLE, $this->getParam('pagetitle'));
            $this->ncm->orm->setSettingValue(Setting::SYSTEM_COPYRIGHTNOTICE, 'Copyright ' . $user->firstname . ' ' . $user->lastname);
            $this->ncm->orm->setSettingValue(Setting::SYSTEM_WEBMASTER_NAME, $user->firstname . ' ' . $user->lastname);
            $this->ncm->orm->setSettingValue(Setting::SYSTEM_WEBMASTER_EMAIL, $user->email);

            $this->content = $this->renderUserTemplate('content-setup-done.phtml');
            
        } else {
            $this->content = $this->renderUserTemplate('content-setup.phtml');
        }
        
        $this->setContent($this->content);
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function checkRequiredPhpModules() {
        $missing = array();

        foreach (NanoCm::$requiredPhpModules as $moduleName) {
            if (!extension_loaded($moduleName)) {
                $missing[] = $moduleName;
            }
        }

        return $missing;
    }

    // </editor-fold>
}
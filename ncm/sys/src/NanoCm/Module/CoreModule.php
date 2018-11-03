<?php

/**
 * NanoCM
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Ubergeek\NanoCm\Module;
use Ubergeek\NanoCm\Article;
use Ubergeek\NanoCm\Page;
use Ubergeek\NanoCm\Setting;

/**
 * Kern(-Ausgabe-)modul des NanoCM.
 * 
 * Dieses Modul implementiert die Kern-Ausgabe-Funktionen des Content Managers.
 * Dazu gehören insbesondere die Startseite, die Darstellung einzelner Artikel
 * sowie das CMS-Archiv.
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-12
 */
class CoreModule extends AbstractModule {

    // <editor-fold desc="Properties">

    /** @var string Generierter Content */
    private $content = '';

    /** @var bool Gibt an, ob es beim letzten Login-Versuch einen Fehler gegeben hat */
    public $loginError = false;

    /** @var Page Gegebenenfalls anzuzeigende Seite */
    public $page = null;

    /** @var Article Gegebenenfalls anzuzeigender Weblog-Artikel */
    public $article = null;

    // </editor-fold>


    public function run() {
        $parts = $this->getRelativeUrlParts();

        // Das CoreModule protokolliert Seitenzugriffe, wenn die Funktion aktiviert ist
        if ($this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLEACCESSLOG) == '1') {
            $this->orm->logHttpRequest(
                $this->ncm->createAccessLogEntry($this->frontController->getHttpRequest()),
                $this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLEGEOLOCATION) == '1'
            );
        }

        switch ($parts[0]) {
            
            // Artikelansicht oder Archiv
            case 'weblog':
                if ($parts[1] == 'article') {
                    $this->log->debug($parts[2]);
                    $this->article = $this->orm->getArticleById(intval($parts[2]));
                    if ($this->article !== null) {
                        $this->setTitle($this->getSiteTitle() . ' - ' . $this->article->headline);
                        $this->content = $this->renderUserTemplate('content-weblog-article.phtml');
                    }
                } elseif ($parts[1] == 'archive') {
                    $this->setTitle($this->getSiteTitle() . ' - Archiv');
                    $this->content = $this->renderUserTemplate('content-weblog-archive.phtml');
                }
                break;
            
            // Anmeldung
            case 'login.php':
                $this->setTitle($this->getSiteTitle() . ' - Anmelden');
                if ($this->getAction() == 'login') {
                    $success = $this->ncm->tryToLoginUser(
                        $this->getParam('username', ''),
                        $this->getParam('password', '')
                    );
                    if ($success) {
                        $this->replaceMeta('location', 'index.php');
                    } else {
                        $this->loginError = true;
                    }
                }
                $this->content = $this->renderUserTemplate('content-login.phtml');
                break;
            
            // Abmeldung
            case 'logout.php':
                $this->ncm->logoutUser();
                $this->replaceMeta('location', 'index.php');
                break;
            
            // Startseite
            case 'index.php';
                $this->setTitle($this->getSiteTitle());
                $this->content = $this->renderUserTemplate('content-start.phtml');
                break;

            // Frei definierbare Pages
            default:
                $this->page = $this->orm->getPageByUrl($this->getRelativeUrl());
                if ($this->page !== null) {
                    $this->setTitle($this->getSiteTitle() . ' - ' . $this->page->headline);
                    $this->log->debug($this->page);
                    $this->content = $this->renderUserTemplate('content-page.phtml');
                }
        }

        $this->setContent($this->content);
    }
}
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

use Ubergeek\NanoCm\StatusCode;
use Ubergeek\Net\Fetch;

/**
 * Startseite des Administrationsbereiches
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
class AdminDashboardModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * @var int Anzahl der zu moderierenden Kommentare
     */
    public $numberOfUnmoderatedComments = 0;

    /**
     * @var int Anzahl der freigeschalteten Kommentare
     */
    public $numberOfActiveComments = 0;

    /**
     * @var int Anzahl der nicht freigeschalteten Kommentare
     */
    public $numberOfInactiveComments = 0;

    /**
     * @var int Anzahl der veröffentlichten / freigeschalteten Artikel
     */
    public $numberOfReleasedArticles = 0;

    /**
     * @var int Anzahl der nicht veröffentlichten / freigeschalteten Artikel
     */
    public $numberOfUnreleasedArticles = 0;

    /**
     * @var int Anzahl der vorhandenen Mediendateien
     */
    public $numberOfMediaFiles = 0;

    /**
     * @var bool Gibt an, ob die Site-Datenbank über den Webserver abrufbar ist
     */
    public $isSiteDbAccessible = false;

    /**
     * @var int Größe in Bytes der Site-Datenbankdatei
     */
    public $sizeOfSiteDb = 0;

    /**
     * @var int Größe in Bytes der Statistik-Datenbankdatei
     */
    public $sizeOfStatsDb = 0;

    // </editor-fold>

    public function run() {
        $this->setTitle($this->getSiteTitle() . ' - Seite verwalten');
        $content = '';

        switch ($this->getRelativeUrlPart(1)) {

            // Verschiedene, übergreifende Popups
            case 'popup';
                $this->setPageTemplate(self::PAGE_NONE);
                switch ($this->getRelativeUrlPart(2)) {
                    case 'editvars':
                        $content = $this->renderUserTemplate('popup-edit-vars.phtml');
                        break;
                }
                break;

            // Trägerseite
            case 'index.php':
            case '':
                $this->numberOfUnmoderatedComments = $this->orm->countCommentsByStatusCode(StatusCode::MODERATION_REQUIRED);
                $this->numberOfActiveComments = $this->orm->countCommentsByStatusCode(StatusCode::ACTIVE);
                $this->numberOfInactiveComments = $this->orm->countInactiveComments();
                $this->numberOfReleasedArticles = $this->orm->countReleasedArticles();
                $this->numberOfUnreleasedArticles = $this->orm->countUnreleasedArticles();
                $this->numberOfMediaFiles = $this->orm->countMediaFiles(false);

                // Überprüfen, ob das Systemverzeichnis / die Site-Datenbank von außen erreichbar ist
                $url = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://';
                $url .= $_SERVER['HTTP_HOST'] . $this->ncm->relativeBaseUrl . '/ncm/sys/db/site.sqlite';
                $this->isSiteDbAccessible = Fetch::isUrlAccessible($url);

                // Größe der Datenbankdateien ermitteln
                $this->sizeOfSiteDb = filesize($this->ncm->getSiteDbFilename());
                $this->sizeOfStatsDb = filesize($this->ncm->getStatsDbFilename());

                $content = $this->renderUserTemplate('content-dashboard.phtml');
        }
        $this->setContent($content);
    }
}
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
    public $numberOfUnmoderatedComments = 0;

    public $numberOfActiveComments = 0;

    public $numberOfInactiveComments = 0;

    public $numberOfReleasedArticles = 0;

    public $numberOfUnreleasedArticles = 0;

    public $isSiteDbAccessible = false;

    public $sizeOfSiteDb = 0;

    public $sizeOfStatsDb = 0;

    public function run() {
        $this->setTitle($this->getSiteTitle() . ' - Seite verwalten');

        $this->numberOfUnmoderatedComments = $this->orm->countCommentsByStatusCode(StatusCode::MODERATION_REQUIRED);
        $this->numberOfActiveComments = $this->orm->countCommentsByStatusCode(StatusCode::ACTIVE);
        $this->numberOfInactiveComments = $this->orm->countInactiveComments();
        $this->numberOfReleasedArticles = $this->orm->countReleasedArticles();
        $this->numberOfUnreleasedArticles = $this->orm->countUnreleasedArticles();

        // Überprüfen, ob das Systemverzeichnis / die Site-Datenbank von außen erreichbar ist
        $url = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')? 'http://' : 'https://';
        $url .= $_SERVER['HTTP_HOST'] . $this->ncm->relativeBaseUrl . '/ncm/sys/db/site.sqlite';
        $this->isSiteDbAccessible = Fetch::isUrlAccessible($url);

        // Größe der Datenbankdateien ermitteln
        $this->sizeOfSiteDb = filesize($this->ncm->getSiteDbFilename());
        $this->sizeOfStatsDb = filesize($this->ncm->getStatsDbFilename());

        $content = $this->renderUserTemplate('content-dashboard.phtml');
        $this->setContent($content);
    }
}
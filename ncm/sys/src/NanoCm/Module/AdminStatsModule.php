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

use Ubergeek\NanoCm\AccessLogEntry;
use Ubergeek\NanoCm\Setting;

/**
 * Einfache Statistiken für das CMS
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
class AdminStatsModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * Eine Liste der auswählbaren Jahreszahlen
     *
     * @var int[]
     */
    public $availableYears = array();

    /**
     * Anzuzeigendes Jahr
     *
     * @var int
     */
    public $searchYear;

    /**
     * Anzuzeigender Monat
     *
     * @var int
     */
    public $searchMonth;

    /**
     * Monatsstatistiken zu den abgerufenen URLs
     *
     * @var array
     */
    public $statsUrls;

    /**
     * Monatsstatistiken zu den verwendeten Browsern
     *
     * @var array
     */
    public $statsBrowsers;

    /**
     * Monatsstatistiken zu den eingesetzten Betriebssystemen
     *
     * @var array
     */
    public $statsOses;

    /**
     * Monatsstatistiken zu den Herkunftsländern bzw. -Regionen
     *
     * @var array
     */
    public $statsCountry;

    /**
     * Monatsstatistiken zu den Session-IDs
     *
     * @var array
     */
    public $statsSessionId;

    /**
     * Gibt an, ob die Statistik-Funktionen eingeschaltet sind
     *
     * @var bool
     */
    public $statsEnabled = false;

    /**
     * Anzuzeigender Ausschnitt aus dem AccessLog
     *
     * @var AccessLogEntry[]
     */
    public $accessLog;

    // </editor-fold>

    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Statistiken');

        $this->searchYear = $this->getOrOverrideSessionVarWithParam('searchYear', date('Y'));
        $this->searchMonth = $this->getOrOverrideSessionVarWithParam('searchMonth', date('m'));
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);
        $this->statsEnabled = $this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLELOGGING) == '1';
        $this->availableYears = $this->orm->getStatisticYears();
        if (count($this->availableYears) == 0) {
            $this->availableYears[] = date('Y');
        }

        switch ($this->getRelativeUrlPart(2)) {

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);
                switch ($this->getRelativeUrlPart(3)) {

                    // Auflistung der zusammengefassten Statistiken
                    case 'list':
                        $this->statsUrls = $this->orm->getMonthlyUrlStats($this->searchYear, $this->searchMonth);
                        $this->statsBrowsers = $this->orm->getMonthlyBrowserStats($this->searchYear, $this->searchMonth);
                        $this->statsOses = $this->orm->getMonthlyOsStats($this->searchYear, $this->searchMonth);
                        $this->statsCountry = $this->orm->getMonthlyRegionStats($this->searchYear, $this->searchMonth);
                        $this->statsSessionId = $this->orm->countUniqueSessionIds($this->searchYear, $this->searchMonth);
                        $content = $this->renderUserTemplate('content-stats-list.phtml');
                        break;

                    // Ausschnitt aus dem AccessLog
                    case 'listaccesslog':
                        $this->pageCount = ceil($this->orm->searchAccessLog($this->searchYear, $this->searchMonth, true) /$this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) $this->searchPage = $this->pageCount;
                        $this->accessLog = $this->orm->searchAccessLog($this->searchYear, $this->searchMonth, false, $this->searchPage);
                        $content = $this->renderUserTemplate('content-stats-accesslog-list.phtml');
                        break;
                }
                break;

            // Trägerseite AccessLog
            case 'accesslog':
                $content = $this->renderUserTemplate('content-stats-accesslog.phtml');
                break;

            // Statistik-Archiv
            case 'archive':
                $content = $this->renderUserTemplate('content-stats-archive.phtml');
                break;

            // Trägerseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-stats.phtml');
                break;
        }

        $this->setContent($content);
    }

}
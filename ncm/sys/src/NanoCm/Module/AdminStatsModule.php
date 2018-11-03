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

/**
 * Einfache Statistiken für das CMS
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
class AdminStatsModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    public $availableYears = array();

    public $availableMonths = array();

    public $searchYear;

    public $searchMonth;

    public $statsUrls;

    public $statsBrowsers;

    public $statsOses;

    public $statsCountry;

    public $statsSessionId;

    // </editor-fold>

    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Statistiken');

        $this->searchYear = $this->getOrOverrideSessionVarWithParam('searchYear', date('Y'));
        $this->searchMonth = $this->getOrOverrideSessionVarWithParam('searchMonth', date('m'));

        switch ($this->getRelativeUrlPart(2)) {

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);
                switch ($this->getRelativeUrlPart(3)) {
                    case 'list':
                        $this->statsUrls = $this->orm->getMonthlyUrlStats($this->searchYear, $this->searchMonth);
                        $this->statsBrowsers = $this->orm->getMonthlyBrowserStats($this->searchYear, $this->searchMonth);
                        $this->statsOses = $this->orm->getMonthlyOsStats($this->searchYear, $this->searchMonth);
                        $this->statsCountry = $this->orm->getMonthlyRegionStats($this->searchYear, $this->searchMonth);
                        $this->statsSessionId = $this->orm->countUniqueSessionIds($this->searchYear, $this->searchMonth);
                        $content = $this->renderUserTemplate('content-stats-list.phtml');
                        break;
                }
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
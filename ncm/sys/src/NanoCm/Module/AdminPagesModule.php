<?php
/**
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ubergeek\NanoCm\Module;
use Ubergeek\NanoCm\Page;

/**
 * Verwaltung der frei definierbaren Seiten
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-01-07
 */
class AdminPagesModule extends AbstractAdminModule {

    /** @var string Generierter Content */
    private $content = '';

    // <editor-fold desc="Properties">

    /**
     * @var Page[]
     */
    public $pages;

    // </editor-fold>


    public function run() {
        $this->setTitle($this->getSiteTitle() . ' - Seiten verwalten');

        switch ($this->getRelativeUrlPart(2)) {
            // AJAX-Aufrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/html');

                switch ($this->getRelativeUrlPart(3)) {
                    case 'list':
                    default:
                        $filter = new Page();
                        $searchterm = $this->getParam('searchterm');
                        $this->pages = $this->orm->searchPages($filter, false, $searchterm);
                        $this->content = $this->renderUserTemplate('content-pages-list.phtml');
                        break;
                }

                break;

            // Übersichtsseite
            case 'index.php':
            case '':
                $this->content = $this->renderUserTemplate('content-pages.phtml');
                break;
        }

        $this->setContent($this->content);
    }

}
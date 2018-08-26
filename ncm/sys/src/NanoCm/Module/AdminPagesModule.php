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
use Ubergeek\NanoCm\StatusCode;

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
     * Die aufzulistenden Pages
     * @var Page[]
     */
    public $pages;

    /**
     * Die anzuzeigende / zu bearbeitende Seite
     * @var Page
     */
    public $page;

    // </editor-fold>


    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Seiten verwalten');

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX-Aufrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/html');

                switch ($this->getRelativeUrlPart(3)) {

                    // Seite(n) löschen

                    // Seite(n) sperren

                    // Seite speichern

                    // Auflistung
                    case 'list':
                    default:
                        $filter = new Page();
                        $searchterm = $this->getParam('searchterm');
                        $this->pages = $this->orm->searchPages($filter, false, $searchterm);
                        $content = $this->renderUserTemplate('content-pages-list.phtml');
                        break;
                }

                break;

            // Seite bearbeiten
            case 'edit':
                $pageId = intval($this->getRelativeUrlPart(3));
                if ($pageId > 0) {
                    $this->page = $this->orm->getPageById($pageId, false);
                }
                if ($this->page == null) {
                    $this->page = $this->createEmptyPage();
                }
                $content = $this->renderUserTemplate('content-pages-edit.phtml');
                break;

            // Übersichtsseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-pages.phtml');
                break;
        }

        $this->setContent($content);
    }

    // <editor-fold desc="Internal methods">

    /**
     * Erstellt das Datenmodell für eine neu angelegte Seite und füllt es mit
     * sinnvollen Standardvorgaben aus.
     * @return Page
     */
    private function createEmptyPage() : Page {
        $page = new Page();
        $page->author_id = $this->ncm->getLoggedInUser()->id;
        $page->status_code = StatusCode::LOCKED;
        $page->creation_timestamp = new \DateTime('now');
        $page->modification_timestamp = new \DateTime('now');
        return $page;
    }

    /**
     * Erstellt ein Page-Objekt anhand der Daten aus dem aktuellen Request
     * @return Page
     */
    private function createPageFromRequest() : Page {
        $page = new Page();

        // TODO implementieren

        return $page;
    }

    // </editor-fold>

}
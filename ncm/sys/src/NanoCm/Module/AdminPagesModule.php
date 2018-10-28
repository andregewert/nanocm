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
use Ubergeek\NanoCm\Page;
use Ubergeek\NanoCm\StatusCode;

/**
 * Verwaltung der frei definierbaren Seiten
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-01-07
 */
class AdminPagesModule extends AbstractAdminModule {

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

    /**
     * Suchbegriff
     * @var string
     */
    public $searchTerm;

    /**
     * Suchfilter: Statuscode
     * @var integer
     */
    public $searchStatusCode;

    /**
     * Die für Artikel-Datensätze verfügbaren Statuscodes
     * @var int[]
     */
    public $availableStatusCodes = array(
        StatusCode::ACTIVE,
        StatusCode::REVIEW_REQUIRED,
        StatusCode::LOCKED
    );

    // </editor-fold>


    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Seiten verwalten');

        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchStatusCode = $this->getOrOverrideSessionVarWithParam('searchStatusCode');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX-Aufrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($this->getRelativeUrlPart(3)) {

                    // Überprüft, ob eine bestimmte URL bereits vergeben ist
                    case 'checkurl':
                        $data = $this->orm->isPageUrlAlreadyExisting(
                            $this->getParam('url'),
                            $this->getParam('id', null)
                        );
                        $content = json_encode($data);
                        break;

                    // Seite(n) endgültig löschen
                    case 'delete':
                        $ids = $this->getParam('ids');
                        $this->orm->deletePagesById($ids);
                        $content = json_encode(true);
                        break;

                    // Seite(n) sperren
                    case 'lock':
                        $ids = $this->getParam('ids');
                        $this->orm->lockPagesById($ids);
                        $content = json_encode(true);
                        break;

                    // Seite speichern
                    case 'save':
                        $page = $this->createPageFromRequest();
                        $this->orm->savePage($page);
                        $content = json_encode($page);
                        break;
                }
                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);

                switch ($this->getRelativeUrlPart(3)) {
                    // Auflistung
                    case 'list':
                        $filter = new Page();
                        $filter->status_code = $this->searchStatusCode;
                        $this->pageCount = ceil($this->orm->searchPages($filter, false, $this->searchTerm, true)/ $this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->pages = $this->orm->searchPages($filter, false, $this->searchTerm, false, $this->searchPage);
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
        return $page;
    }

    /**
     * Erstellt ein Page-Objekt anhand der Daten aus dem aktuellen Request
     * @return Page
     */
    private function createPageFromRequest() : Page {
        $page = new Page();
        $id = intval($this->getParam('id'));
        $oldPage = null;

        if ($id > 0) {
            $oldPage = $this->orm->getPageById($id, false);
        }

        if ($oldPage !== null) {
            $page->id = $id;

            // TODO Die Übernahme dieser Werte ist eigentlich nur dann sinnvoll,
            // wenn sie im nächsten Schritt nur bedingt überschrieben werden ...
            // (bspw. abhängig vom Recht, den Autor nach Belieben einzustellen)
            $page->creation_timestamp = $oldPage->creation_timestamp;
            $page->author_id = $oldPage->author_id;
            $page->status_code = $oldPage->status_code;
            $page->publishing_timestamp = $oldPage->publishing_timestamp;
        }

        $page->author_id = $this->getParam('author_id', 0);
        $page->status_code = $this->getParam('status_code', StatusCode::LOCKED);
        $page->url = $this->getParam('url', '');
        $page->headline = $this->getParam('headline', '');
        $page->content = $this->getParam('content', '');
        if (!empty($this->getParam('publishing_timestamp'))) {
            $page->publishing_timestamp = new \DateTime($this->getParam('publishing_timestamp'));
        }

        if ($page->status_code == StatusCode::ACTIVE && $page->publishing_timestamp == null) {
            $this->log->debug('Bei Veröffentlichung den Publishing timestamp auf NOW setzen');
            $page->publishing_timestamp = new \DateTime();
        }

        return $page;
    }

    // </editor-fold>

}
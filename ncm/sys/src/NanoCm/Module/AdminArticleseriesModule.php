<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2018 André Gewert <agewert@ubergeek.de>
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

use Ubergeek\NanoCm\Articleseries;
use Ubergeek\NanoCm\StatusCode;

/**
 * Verwaltung von Artikelserien
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-10-30
 */
class AdminArticleseriesModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * Die anzuzeigende Liste der Artikelserien
     * @var Articleseries[]
     */
    public $seriesList;

    /**
     * Die zu bearbeitende Artikelserie
     * @var Articleseries
     */
    public $articleseries;

    /**
     * Suche / Filter nach Status-Code
     * @var int
     */
    public $searchStatusCode;

    /**
     * Suchbegriff
     * @var string
     */
    public $searchTerm;

    /**
     * Die für Artikelserien-Datensätze verfügbaren Statuscodes
     * @var int[]
     */
    public $availableStatusCodes = array(
        StatusCode::ACTIVE,
        StatusCode::LOCKED
    );

    // </editor-fold>


    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Artikelserien verwalten');
        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchStatusCode = $this->getOrOverrideSessionVarWithParam('searchStatusCode');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX-Abfragen
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($this->getRelativeUrlPart(3)) {
                    case 'save':
                        $series = $this->createArticleseriesFromRequest();
                        $this->log->debug($series);
                        $content = json_encode($this->orm->saveArticleseries($series));
                        break;

                    case 'delete':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->deleteArticleseriesByIds($ids);
                        $content = json_encode(true);
                        break;

                    case 'lock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setArticleseriesStatusCodeByIds($ids, StatusCode::LOCKED);
                        $content = json_encode(true);
                        break;

                    case 'unlock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setArticleseriesStatusCodeByIds($ids, StatusCode::ACTIVE);
                        $content = json_encode(true);
                        break;
                }
                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);

                switch ($this->getRelativeUrlPart(3)) {
                    // Artikelserie bearbeiten
                    case 'edit':
                        $this->articleseries = $this->orm->getArticleseriesById($this->getParam('id'));
                        if ($this->articleseries == null) $this->articleseries = new Articleseries();
                        $content = $this->renderUserTemplate('content-articleseries-edit.phtml');
                        break;

                    // Auflistung
                    case 'list':
                        $filter = new Articleseries();
                        $filter->status_code = $this->searchStatusCode;
                        $this->pageCount = ceil($this->orm->searchArticleseries($filter, $this->searchTerm, true) /$this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->seriesList = $this->orm->searchArticleseries($filter, $this->searchTerm, false, $this->searchPage);
                        $this->log->debug($this->seriesList);
                        $content = $this->renderUserTemplate('content-articleseries-list.phtml');
                        break;
                }
                break;

            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-articleseries.phtml');
                break;
        }
        $this->setContent($content);
    }


    // <editor-fold desc="Internal methods">

    private function createArticleseriesFromRequest() {
        $id = $this->getParam('id');
        $oldArticleseries = $this->orm->getArticleseriesById($id);

        $series = ($oldArticleseries != null)? $oldArticleseries : new Articleseries();
        $series->status_code = $this->getParam('status_code');
        $series->title = $this->getParam('title');
        $series->description = $this->getParam('description');
        $series->sorting_key = $this->getParam('sorting_key');
        return $series;
    }

    // </editor-fold>
}
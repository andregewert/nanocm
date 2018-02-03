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
use Ubergeek\NanoCm\Article;

/**
 * Verwaltung der Artikel
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
class AdminArticlesModule extends AbstractAdminModule {
    
    // <editor-fold desc="Properties">

    /**
     * Die gefundenen Artikel-Datensätze
     * @var \Ubergeek\NanoCm\Article[]
     */
    protected $articles;
    
    /**
     * Wenn ein einzelner Artikel bearbeitet wird: der Artikel-Datensatz
     * @var \Ubergeek\NanoCm\Article
     */
    protected $article;
    
    // </editor-fold>
    
    
    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Artikel verwalten');
        
        switch ($this->getRelativeUrlPart(2)) {
            // AJAX-Aufrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/html');

                switch ($this->getRelativeUrlPart(3)) {
                    // Artikelliste
                    case 'list':
                    default:
                        $filter = new Article();
                        $filter->status_code = $this->getParam('status');
                        $this->articles = $this->orm->searchArticles($filter, false, 20);
                        $content = $this->renderUserTemplate('content-articles-list.phtml');
                }
                break;

            // Einzelnen Artikel bearbeiten
            case 'edit':
                $articleId = intval($this->getRelativeUrlPart(3));
                $this->article = $this->orm->getArticleById($articleId);
                $content = $this->renderUserTemplate('content-articles-edit.phtml');
                break;

            // Übersichtsseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-articles.phtml');
                break;
        }
        
        $this->setContent($content);
    }

}
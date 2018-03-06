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
    public $articles;
    
    /**
     * Wenn ein einzelner Artikel bearbeitet wird: der Artikel-Datensatz
     * @var \Ubergeek\NanoCm\Article
     */
    public $article;
    
    // </editor-fold>


    /**
     * @throws \Exception
     */
    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Artikel verwalten');
        
        switch ($this->getRelativeUrlPart(2)) {
            // AJAX-Aufrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/html');

                switch ($this->getRelativeUrlPart(3)) {
                    // Artikel speichern
                    case 'save':
                        // TODO implementieren
                        $this->setContentType('text/javascript');
                        $article = $this->createArticleFromRequest();
                        $id = $this->orm->saveArticle($article);

                        // saveArticle
                        $content = json_encode(array(
                            'id'    => $id
                        ));
                        break;

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
                if ($this->article == null) {
                    $this->article = new Article();
                }
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


    // <editor-fold desc="Internal methods">

    private function createArticleFromRequest() {
        $article = new Article();
        $id = intval($this->getParam('id'));
        $oldArticle = null;

        if ($id > 0) {
            $oldArticle = $this->orm->getArticleById($id);
        }

        if ($oldArticle !== null) {
            $article->id = $id;
            $article->creation_timestamp = $oldArticle->creation_timestamp;
            $article->author_id = $oldArticle->author_id;
            $article->status_code = $oldArticle->status_code;
            $article->start_timestamp = $oldArticle->start_timestamp;
            $article->stop_timestamp = $oldArticle->stop_timestamp;
            $article->publishing_timestamp = $oldArticle->publishing_timestamp;
        }

        //$article->status_code = $this->getParam('status_code', 0);
        // -> Status sollte immer separat (und alleine) gesetzt werden

        $article->headline = $this->getParam('headline', '');
        $article->teaser = $this->getParam('teaser', '');
        $article->content = $this->getParam('content', '');
        if (!empty($this->getParam('start_timestamp'))) {
            $article->start_timestamp = new \DateTime($this->getParam('start_timestamp'));
        }
        if (!empty($this->getParam('stop_timestamp'))) {
            $article->stop_timestamp = new \DateTime($this->getParam('stop_timestamp'));
        }
        $article->enable_trackbacks = intval($this->getParam('enable_trackbacks')) == 1;
        $article->enable_comments = intval($this->getParam('enable_comments')) == 1;

        $this->log->debug($article);
        return $article;
    }

    // </editor-fold>

}
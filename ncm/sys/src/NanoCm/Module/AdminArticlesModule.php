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
use Ubergeek\NanoCm\Article;
use Ubergeek\NanoCm\Constants;
use Ubergeek\NanoCm\StatusCode;
use Ubergeek\NanoCm\Tag;

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

    /**
     * Suchbegriff
     * @var string
     */
    public $searchterm;

    /**
     * Suchfilter: Statuscode
     * @var integer
     */
    public $statusCode;

    /**
     * Die für Artikel-Datensätze verfügbaren Statuscodes
     * @var int[]
     */
    public $availableStatusCodes = array(
        StatusCode::ACTIVE,
        StatusCode::REVIEW_REQUIRED,
        StatusCode::LOCKED
    );

    /**
     * Definiert die Sonderzeichen, die über das virtuelle Keyboard eingefügt werden können
     * @var string[]
     */
    public $availableSpecialChars = array(
        160     => 'Geschütztes Leerzeichen',
        8201    => 'Schmales Leerzeichen',
        8239    => 'Schmales geschütztes Leerzeichen',
        8211    => 'Halbgeviertstrich',
        8212    => 'Geviertstrich',
        187     => 'Guillemets',
        171     => 'Guillemets',
        8250    => 'Guillemets 2',
        8249    => 'Guillemets 2',
        8222    => 'Anführungszeichen',
        8220    => 'Anführungszeichen',
        8218    => 'Anführungszeichen 2',
        8216    => 'Anführungszeichen 2',
        8226    => 'Bullet',
        183     => 'Mittelpunkt'
    );

    // </editor-fold>


    /**
     * @throws \Exception
     */
    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Artikel verwalten');

        $this->page = $this->getParam('page');
        $this->searchterm = $this->getParam('searchterm');
        $this->statusCode = $this->getParam('status');

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX-Aufrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/html');

                switch ($this->getRelativeUrlPart(3)) {

                    // Artikel (endgültig) löschen
                    case 'delete':
                        $this->setContentType('text/javascript');
                        $ids = $this->getParam('ids');
                        $this->orm->deleteArticlesById($ids);
                        $content = json_encode(true);
                        break;

                    // Artikel sperrem
                    case 'lock':
                        $this->setContentType('text/javascript');
                        $ids = $this->getParam('ids');
                        $this->orm->lockArticlesById($ids);
                        $content = json_encode(true);
                        break;

                    // Artikel speichern
                    case 'save':
                        $this->setContentType('text/javascript');
                        $article = $this->createArticleFromRequest();
                        $this->orm->saveArticle($article);
                        $content = json_encode($article);
                        break;

                    // Artikelliste
                    case 'list':
                    default:
                        $filter = new Article();
                        $filter->status_code = $this->statusCode;

                        $this->pageCount = ceil($this->orm->searchArticles($filter, false, $this->searchterm, true) /$this->orm->pageLength);
                        $this->articles = $this->orm->searchArticles($filter, false, $this->searchterm, false, $this->page);
                        $content = $this->renderUserTemplate('content-articles-list.phtml');
                }
                break;

            // Einzelnen Artikel bearbeiten
            case 'edit':
                $articleId = intval($this->getRelativeUrlPart(3));
                if ($articleId > 0) {
                    $this->article = $this->orm->getArticleById($articleId, false);
                }
                if ($this->article == null) {
                    $this->article = $this->createEmptyArticle();
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

    /**
     * Erstellt ein neues Artikelmodell und füllt die wichtigsten Daten mit sinnvollen Vorgaben
     * @return Article
     */
    private function createEmptyArticle() : Article {
        $article = new Article();

        $article->author_id = $this->ncm->getLoggedInUser()->id;
        $article->status_code = StatusCode::LOCKED;
        $article->start_timestamp = new \DateTime('now');
        $article->enable_comments = $this->ncm->orm->getSettingValue(Constants::SETTING_SYSTEM_ENABLECOMMENTS, true);
        $article->enable_trackbacks = $this->ncm->orm->getSettingValue(Constants::SETTING_SYSTEM_ENABLETRACKBACKS, true);

        return $article;
    }

    /**
     * Erstellt ein Artikelmodell und füllt es mit den Daten aus dem aktuellen Request
     * @return Article
     */
    private function createArticleFromRequest() : Article {
        $article = new Article();
        $id = intval($this->getParam('id'));
        $oldArticle = null;

        if ($id > 0) {
            $oldArticle = $this->orm->getArticleById($id, false);
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

        // TODO Es muss noch entschieden werden, ob der Autor nach Belieben angegeben werden kann
        $article->author_id = $this->getParam('author_id', 0);
        $article->status_code = $this->getParam('status_code', StatusCode::LOCKED);
        $article->headline = $this->getParam('headline', '');
        $article->teaser = $this->getParam('teaser', '');
        $article->content = $this->getParam('content', '');
        if (!empty($this->getParam('start_timestamp'))) {
            $article->start_timestamp = new \DateTime($this->getParam('start_timestamp'));
        }
        if (!empty($this->getParam('stop_timestamp'))) {
            $article->stop_timestamp = new \DateTime($this->getParam('stop_timestamp'));
        }
        if (!empty($this->getParam('publishing_timestamp'))) {
            $this->log->debug('Nicht leeren Publishing timestamp übernehmen: ' . $this->getParam('publishing_timestamp'));
            $article->publishing_timestamp = new \DateTime($this->getParam('publishing_timestamp'));
        }

        if ($article->status_code == StatusCode::ACTIVE && $article->publishing_timestamp == null) {
            $this->log->debug('Bei Veröffentlichung den Publishing timestamp auf NOW setzen');
            $article->publishing_timestamp = new \DateTime();
        }

        $article->enable_trackbacks = $this->getParam('enable_trackbacks') == 'true';
        $article->enable_comments = $this->getParam('enable_comments') == 'true';
        $article->tags = Tag::splitTagsString($this->getParam('tags'));
        $this->log->debug($article);
        return $article;
    }

    // </editor-fold>

}
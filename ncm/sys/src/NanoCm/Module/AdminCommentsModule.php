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

use Ubergeek\NanoCm\Comment;
use Ubergeek\NanoCm\StatusCode;

/**
 * Verwaltung der Benutzerkonten
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-01-13
 */
class AdminCommentsModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * Die in der Listenansicht anzuzeigenden Kommentare
     * @var Comment[]
     */
    public $comments;

    /**
     * Der aktuell zu bearbeitende Kommentar
     * @var Comment
     */
    public $comment;

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
     * Suchfilter: Spam-Statuscode
     * @var integer
     */
    public $searchSpamStatusCode;

    /**
     * Die für Artikel-Datensätze verfügbaren Statuscodes
     * @var int[]
     */
    public $availableStatusCodes = array(
        StatusCode::MODERATION_REQUIRED,
        StatusCode::MARKED_AS_JUNK,
        StatusCode::ACTIVE,
        StatusCode::LOCKED
    );

    // </editor-fold>

    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Kommentare verwalten');

        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchStatusCode = $this->getOrOverrideSessionVarWithParam('searchStatusCode');
        $this->searchSpamStatusCode = $this->getOrOverrideSessionVarWithParam('searchSpamStatusCode');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX-Aufrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($this->getRelativeUrlPart(3)) {

                    // Kommentare löschen
                    case 'delete':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) {
                            $this->orm->deleteCommentsById($ids);
                        }
                        $content = json_encode(true);
                        break;

                    // Kommentare sperren
                    case 'lock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) {
                            $this->orm->setCommentStatusCodeByIds($ids, StatusCode::LOCKED);
                        }
                        $content = json_encode(true);
                        break;

                    // Kommentare entsperren
                    case 'unlock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) {
                            $this->orm->setCommentStatusCodeByIds($ids, StatusCode::ACTIVE);
                        }
                        $content = json_encode(true);
                        break;

                    // Kommentar speichern
                    case 'save':
                        $comment = $this->createCommentFromRequest();
                        $content = json_encode($this->orm->saveComment($comment));
                        break;
                }
                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);

                switch ($this->getRelativeUrlPart(3)) {
                    // Bearbeitungsmaske
                    case 'edit':
                        $this->comment = $this->orm->getCommentById(
                            $this->getParam('id')
                        );
                        $content = $this->renderUserTemplate('content-comments-edit.phtml');
                        break;

                    // Auflistung der vorhandenen Kommentare
                    case 'list':
                        $filter = new Comment();
                        $filter->status_code = $this->searchStatusCode;

                        $this->pageCount = ceil($this->orm->searchComments($filter, $this->searchTerm, true) /$this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->comments = $this->orm->searchComments($filter, $this->searchTerm, false, $this->searchPage);
                        $content = $this->renderUserTemplate('content-comments-list.phtml');
                        break;
                }

                break;

            // Trägerseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-comments.phtml');
                break;
        }

        $this->setContent($content);
    }

    // <editor-fold desc="Internal methods">

    /**
     * Erstellt ein Comment-Objekt mit den Daten aus dem aktuellen Request
     * @return Comment
     */
    private function createCommentFromRequest() : Comment {
        $comment = new Comment();
        $id = intval($this->getParam('id'));
        $oldComment = null;
        if ($id > 0) $oldComment = $this->orm->getCommentById($id);

        if ($oldComment != null) {
            $comment->id = $id;
            $comment->article_id = $oldComment->article_id;
            $comment->creation_timestamp = $oldComment->creation_timestamp;
            $comment->spam_status = $oldComment->spam_status;
        }

        $comment->modification_timestamp = new \DateTime();
        $comment->status_code = $this->getParam('status_code');
        $comment->username = $this->getParam('username');
        $comment->email = $this->getParam('email');
        $comment->headline = $this->getParam('headline');
        $comment->content = $this->getParam('content');

        return $comment;
    }

    // </editor-fold>

}
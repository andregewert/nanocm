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

                    // Kommentar löschen
                    case 'delete':
                        break;

                    // Kommentar sperren
                    case 'lock':
                        break;

                    // Kommentar als Spam markieren
                    case 'markasspam':
                        break;

                    // Kommentar für Review markieren
                    case 'markforreview':
                        break;
                }
                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);

                switch ($this->getRelativeUrlPart(3)) {
                    case 'list':
                        $filter = new Comment();
                        $filter->status_code = $this->searchStatusCode;

                        $this->pageCount = ceil($this->orm->searchComments($filter, false, $this->searchTerm, true) /$this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->comments = $this->orm->searchComments($filter, false, $this->searchTerm, false, $this->searchPage);
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

    // </editor-fold>

}
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

namespace Ubergeek\NanoCm;

/**
 * Bildet einen NCM-Artikel ab
 * @author agewert@ubergeek.de
 * @package Ubergeek\NanoCm
 * @created 2017-10-27
 */
class Article {
    
    // <editor-fold desc="Properties">
    
    /**
     * Eindeutige ID des Artikels
     *
     * @var int
     */
    public $id;
    
    /**
     * Erstellungszeitpunkt
     *
     * @var \DateTime
     */
    public $creation_timestamp;
    
    /**
     * Änderungs-Zeitpunkt
     *
     * @var \DateTime|null
     */
    public $modification_timestamp = null;

    /**
     * Benutzer-ID des Autors
     *
     * @var int
     */
    public $author_id;

    /**
     * Optional: Medien-ID für Artikelbild
     *
     * @var  int|null
     */
    public $medium_id;
    
    /**
     * Statuscode
     *
     * @var int
     */
    public $status_code;

    /**
     * Artikelüberschrift
     *
     * @var string
     */
    public $headline;
    
    /**
     * Optionaler Anrisstext
     *
     * @var string
     */
    public $teaser;
    
    /**
     * Artikeltext
     *
     * @var string
     */
    public $content;

    /**
     * Startzeitpunkt für die Freischaltung
     *
     * @var \DateTime|null
     */
    public $start_timestamp = null;
    
    /**
     * Endzeitpunkt für die Freischaltung
     *
     * @var \DateTime|null
     */
    public $stop_timestamp = null;
    
    /**
     * Veröffentlichungszeitpunkt
     *
     * @var \DateTime|null
     */
    public $publishing_timestamp = null;
    
    /**
     * Freigabestatus der Trackback-Funktion für diesen Artikel
     * 
     * Die globale Einstellung für die Trackback-Funktionalität hat immer
     * Vorrang vor der artikel-bezogenen Einstellung.
     *
     * @var bool
     */
    public $enable_trackbacks;
    
    /**
     * Freigabestatus der Kommentar-Funktion für diesen Artikel
     * 
     * Die globale Einstellung für die Kommentar-Funktionalität hat immer
     * Vorrang vor der artikel-bezogenen Einstellung.
     *
     * @var bool
     */
    public $enable_comments;

    /**
     * Schlüssel für die Artikelart-Definition
     * @var string
     */
    public $articletype_key;

    /**
     * Optionale Template-Variablen.
     * Die Variablen werden in dieser Property als Array geführt und müssen beim Speichern / Laden entsprechend
     * gemapt werden.
     *
     * @var string
     */
    public $templatevars;

    /**
     * Optional: Die ID einer Artikelserie, zu der dieser Artikel gehören soll
     *
     * @var int
     */
    public $series_id;

    /**
     * Optional: Verknüpfte Artikelart
     *
     * @var Definition
     */
    public $articleType;

    /**
     * Ein Array mit den zugewiesenen Schlagworten
     *
     * @var string[]
     */
    public $tags;
    
    // </editor-fold>
    
    
    // <editor-fold desc="Methods">

    /**
     * Erstellt ein Article-Objekt anhand des übergebenen PDO-Statements
     * @param \PDOStatement $stmt
     * @return Article
     * @throws \Exception
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        /* @var $article \Ubergeek\NanoCm\Article */

        if (($article = $stmt->fetchObject(__CLASS__)) !== false) {
            $article->creation_timestamp = new \DateTime($article->creation_timestamp);
            $article->modification_timestamp = new \DateTime($article->modification_timestamp);
            $article->start_timestamp = new \DateTime($article->start_timestamp);
            if ($article->publishing_timestamp != null) {
                $article->publishing_timestamp = new \DateTime($article->publishing_timestamp);
            }
            if ($article->stop_timestamp != null) {
                $article->stop_timestamp = new \DateTime($article->stop_timestamp);
            }
            $article->enable_trackbacks = $article->enable_trackbacks == 1;
            $article->enable_comments = $article->enable_comments == 1;
            $article->templatevars = json_decode($article->templatevars, true);
            return $article;
        }
        return null;
    }
    
    public function getArticleUrl() : string {
        return '/weblog/article/' . $this->id . '/' . urlencode(Util::simplifyUrlString($this->headline));
    }

    public function getEbookUrl() : string {
        return '/ebook/article/' . $this->id . '/' . urlencode(Util::simplifyUrlString($this->headline));
    }

    public function getCommentUrl(Comment $comment) : string {
        return $this->getArticleUrl() . '#comment-' . intval($comment->id);
    }

    public function getTagSearchUrl(string $tag) : string {
        return '/weblog/tags/' . $tag;
    }

    // </editor-fold>
}